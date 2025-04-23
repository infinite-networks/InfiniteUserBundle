<?php declare(strict_types=1);

/**
 * (c) Infinite Networks Pty Ltd <https://www.infinite.net.au/>
 */

namespace Infinite\UserBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Infinite\UserBundle\Configuration\Configuration;
use Infinite\UserBundle\Entity\InfiniteUserInterface;
use Infinite\UserBundle\Entity\LoginHistoryInterface;
use Infinite\UserBundle\Security\DeviceAuthenticationManager;
use Infinite\UserBundle\Security\SecurityDataManager;
use Infinite\UserBundle\Security\TwoFactorChecker;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\FirewallMapInterface;

readonly class RequestListener implements EventSubscriberInterface
{
    public function __construct(
        private Configuration               $configuration,
        private DeviceAuthenticationManager $deviceAuthenticationManager,
        private ManagerRegistry             $doctrine,
        private FirewallMapInterface        $firewallMap,
        private SecurityDataManager         $securityDataManager,
        private TokenStorageInterface       $tokenStorage,
        private TwoFactorChecker            $twoFactorChecker,
    )
    {
    }

    public function onRequest(RequestEvent $event): void
    {
        $config = $this->firewallMap->getFirewallConfig($event->getRequest());

        if ($config?->getName() !== 'main') {
            return;
        }

        $user = $this->getUser();

        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();

        /** @var ?LoginHistoryInterface $loginLog */
        if ($this->deviceAuthenticationManager->getLoginHistoryId()) {
            $loginLog = $em->getRepository($this->configuration->loginHistoryClass)->find($this->deviceAuthenticationManager->getLoginHistoryId());
        } else {
            $loginLog = null;
        }

        if (!$user) {
            if ($loginLog) {
                // User's PHP session has expired and they were not using "remember me".
                // Mark the login history entry as logged out.
                $loginLog->updateLogoutDate();
                $em->flush();
            }
            return;
        }

        $usd = $this->securityDataManager->getUserSecurityData($user);

        if ($usd->isLocked($this->configuration->lockoutPolicy)) {
            // Force immediate logout.
            // (If $loginLog is null then the user is about to be logged out anyway)
            $loginLog?->updateLogoutDate();
        }

        $loginLog?->updateLastActivity();
        $em->flush();

        if (!$loginLog || $loginLog->getLogoutDate()) {
            // Row deleted from database or marked for logout. Force a logout.
            $event->getRequest()->getSession()->clear();
            $this->tokenStorage->setToken(null);
            $this->deviceAuthenticationManager->forgetMe();
            $event->setResponse(new RedirectResponse('/'));
            return;
        }

        if (!$this->configuration->twoFactorEnabled) {
            return;
        }

        switch ($this->twoFactorChecker->check2FA($user, $event->getRequest())) {
            case TwoFactorChecker::TWO_FACTOR_NEEDED:
                // Force user to authenticate now.
                $event->getRequest()->getSession()->set('infub_2fa_next_url', $event->getRequest()->getUri());
                $event->setResponse(new RedirectResponse($this->configuration->twoFactorLoginPage));

                return;

            case TwoFactorChecker::TWO_FACTOR_NEEDED_SOON:
                // Display a nag message.
                $event->getRequest()->attributes->set('infub_2fa_deadline', $usd->getTwoFactorDeadline());

                return;
        }
    }

    private function getUser(): ?InfiniteUserInterface
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        if ($user instanceof InfiniteUserInterface) {
            return $user;
        } else {
            return null;
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onRequest',
        ];
    }
}
