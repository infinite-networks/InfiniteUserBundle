<?php declare(strict_types=1);

/**
 * This file is part of Aspen Rhino.
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 */

namespace Infinite\UserBundle\Security;

use Infinite\UserBundle\Configuration\Configuration;
use Infinite\UserBundle\Entity\InfiniteUserInterface;
use Infinite\UserBundle\Form\Model\PinModel;
use Infinite\UserBundle\Form\Type\PinType;
use Infinite\UserBundle\Security\Exception\InvalidPinException;
use Infinite\UserBundle\Security\Exception\PinExpiredException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CredentialsExpiredException;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

readonly class InfinitePinAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        private Configuration $configuration,
        private DeviceAuthenticationManager $deviceAuthenticationManager,
        private FormFactoryInterface $formFactory,
        private SecurityDataManager $securityDataManager,
    )
    {
    }

    public function handlePinSetup(Request $request, InfiniteUserInterface $user): FormInterface
    {
        $pinModel = new PinModel();
        $form = $this->formFactory->create(PinType::class, $pinModel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $usd = $this->securityDataManager->getUserSecurityData($user);
            $this->deviceAuthenticationManager->setPin($user->getUserIdentifier(), $usd->getPasswordLastChanged(), $pinModel->pin);
        }

        return $form;
    }

    public function createCookie(string $name): Cookie
    {
        return new Cookie('infub_pma', $name, new \DateTimeImmutable('+ 100 years'), '/', null, null, false);
    }

    public function supports(Request $request): ?bool
    {
        return $request->isMethod('POST') &&
            rawurldecode($request->getPathInfo()) === $this->configuration->pinCheckUrl &&
            $this->deviceAuthenticationManager->getUserIdentifier() &&
            $this->deviceAuthenticationManager->getPin();
    }

    public function authenticate(Request $request): Passport
    {
        return new Passport(
            new UserBadge($this->deviceAuthenticationManager->getUserIdentifier()),
            new CustomCredentials(function ($credentials, InfiniteUserInterface $user) {
                $usd = $this->securityDataManager->getUserSecurityData($user);

                if ($usd->getPasswordLastChanged()->format('YmdHis') !== $this->deviceAuthenticationManager->getPasswordTimestamp()) {
                    throw new PinExpiredException();
                }

                if ($credentials !== $this->deviceAuthenticationManager->getPin()) {
                    throw new InvalidPinException();
                }

                return true;
            }, $request->request->get('_infub_pin'))
        );
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        return new PostAuthenticationToken($passport->getUser(), $firewallName, $passport->getUser()->getRoles());
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $request->attributes->set('infub_was_pin_login', '1');

        $targetUrl = $request->getSession()->get('_security.main.target_path');

        if (!$targetUrl) {
            $targetUrl = '/';
        } else {
            // Snip http://domain and possibly /app_dev.php
            $targetUrl = preg_replace('~\w+://[^/]*(/app_dev.php)?~', '', $targetUrl);
        }

        return new RedirectResponse($targetUrl);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);

        return new RedirectResponse('/login');
    }
}
