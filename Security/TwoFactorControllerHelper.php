<?php declare(strict_types=1);

/**
 * This file is part of Aspen Rhino.
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 */

namespace Infinite\UserBundle\Security;

use Doctrine\Persistence\ManagerRegistry;
use Infinite\UserBundle\Configuration\Configuration;
use Infinite\UserBundle\Entity\InfiniteUserInterface;
use Infinite\UserBundle\Entity\UserSecurityDataInterface;
use Infinite\UserBundle\Form\Model\TwoFactorLoginModel;
use Infinite\UserBundle\Form\Type\TwoFactorLoginType;
use Infinite\UserBundle\Form\Type\TwoFactorRegistrationType;
use Infinite\UserBundle\Random\RandomBytes;
use ParagonIE\ConstantTime\Base32;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

readonly class TwoFactorControllerHelper
{
    public function __construct(
        private Configuration               $configuration,
        private DeviceAuthenticationManager $deviceAuthenticationManager,
        private EventDispatcherInterface    $eventDispatcher,
        private FormFactoryInterface        $formFactory,
        private ManagerRegistry             $doctrine,
        private RandomBytes                 $randomBytes,
        private RequestStack                $requestStack,
        private SecurityDataManager         $securityDataManager,
        private TokenStorageInterface       $tokenStorage,
    )
    {
    }

    public function handlePage(): TwoFactorControllerHelperResult
    {
        $request = $this->requestStack->getCurrentRequest();
        $user = $this->tokenStorage->getToken()?->getUser();

        if (!($user instanceof InfiniteUserInterface)) {
            return $this->done($request);
        }

        $usd = $this->securityDataManager->getUserSecurityData($user);

        if (!$this->configuration->twoFactorEnabled ||
            $this->deviceAuthenticationManager->hasFresh2FA($user->getUserIdentifier())
        ) {
            return $this->done($request);
        }

        if ($usd->getTwoFactorSecret()) {
            return $this->handle2FALogin($request, $user, $usd);
        } else {
            return $this->handle2FARegistration($request, $user, $usd);
        }
    }

    private function handle2FALogin(Request $request, InfiniteUserInterface $user, UserSecurityDataInterface $usd): TwoFactorControllerHelperResult
    {
        $model = new TwoFactorLoginModel($usd->getTwoFactorSecret());
        $form = $this->formFactory->create(TwoFactorLoginType::class, $model);
        $form->handleRequest($request);

        $ll = $this->doctrine->getRepository($this->configuration->loginHistoryClass)->find($this->deviceAuthenticationManager->getLoginHistoryId());

        if ($form->isSubmitted() && $form->isValid()) {
            $this->deviceAuthenticationManager->setFresh2FA($user->getUserIdentifier());
            $ll->setTwoFactorOk(true);
            $usd->resetTwoFactorFailureCount();
            $this->doctrine->getManager()->flush();

            return $this->done($request);
        }

        if ($form->isSubmitted() && $form->get('code')->getErrors()->count()) {
            $usd->increaseTwoFactorFailureCount();

            if ($usd->isLocked($this->configuration->lockoutPolicy)) {
                $request->getSession()->clear();
                $this->tokenStorage->setToken(null);
                $this->deviceAuthenticationManager->forgetMe();

                return new TwoFactorControllerHelperResult([
                    'customRedirect' => $request->getRequestUri(),
                ]);
            }
        }

        $ll->setTwoFactorOk(false);
        $this->doctrine->getManager()->flush($ll);

        return new TwoFactorControllerHelperResult(['showLoginCodePage' => true, 'form' => $form]);
    }

    private  function handle2FARegistration(Request $request, InfiniteUserInterface $user, UserSecurityDataInterface $usd): TwoFactorControllerHelperResult
    {
        $model = new TwoFactorLoginModel(trim(Base32::encodeUpper($this->randomBytes->randomBytes(20)), '='));
        $form = $this->formFactory->create(TwoFactorRegistrationType::class, $model);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $usd->setTwoFactorDeadline(new \DateTimeImmutable);
                $usd->setTwoFactorSecret($model->secret);
                $this->doctrine->getManager()->flush();

                $this->deviceAuthenticationManager->setFresh2FA($user->getUserIdentifier());

                $result = $this->done($request);
                $result->registrationSuccess = true;

                return $result;
            } elseif ($form->getErrors(false)->count()) {
                // If there are any top-level errors, assume they're CSRF errors.
                // In this case, we can't trust $model->secret, so start over.
                return new TwoFactorControllerHelperResult([
                    'customRedirect' => $request->getRequestUri(),
                    'csrfError' => true,
                ]);
            }
        }

        $model->getTotp()->setLabel($this->configuration->twoFactorLabel);
        $model->getTotp()->setIssuer($this->configuration->twoFactorIssuer);

        return new TwoFactorControllerHelperResult([
            'showRegistrationPage' => true,
            'form' => $form,
            'pastDeadline' => new \DateTimeImmutable > $usd->getTwoFactorDeadline()
        ]);
    }

    private function done(Request $request): TwoFactorControllerHelperResult
    {
        $nextUrl = $request->getSession()->remove('infub_2fa_next_url');

        if ($nextUrl) {
            return new TwoFactorControllerHelperResult(['customRedirect' => $nextUrl]);
        }

        return new TwoFactorControllerHelperResult(['redirectToHome' => true]);
    }
}
