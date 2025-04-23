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
use Infinite\UserBundle\Entity\UserSecurityDataInterface;
use Infinite\UserBundle\Event\NewDeviceLoginEvent;
use Infinite\UserBundle\Security\DeviceAuthenticationManager;
use Infinite\UserBundle\Security\SecurityDataManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\Event\TokenDeauthenticatedEvent;

readonly class LoginListener implements EventSubscriberInterface
{
    public function __construct(
        private Configuration               $configuration,
        private DeviceAuthenticationManager $deviceAuthenticationManager,
        private ManagerRegistry             $doctrine,
        private EventDispatcherInterface    $eventDispatcher,
        private SecurityDataManager         $securityDataManager,
    )
    {
    }

    public function checkPassport(CheckPassportEvent $event): void
    {
        $user = $event->getPassport()->getUser();

        if (!$user instanceof InfiniteUserInterface) {
            return;
        }

        $usd = $this->securityDataManager->getUserSecurityData($user);

        if ($usd->isLocked($this->configuration->lockoutPolicy)) {
            throw new LockedException('Account locked due to authentication failures. Reset your password to unlock.');
        }
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof InfiniteUserInterface) {
            return;
        }

        if ($event->getPassport()->getAttribute('is_remember_me')) {
            return;
        }

        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();

        // Logic common to password and PIN logins: write a LoginHistory entry and save it in the session.

        /** @var LoginHistoryInterface $loginLog */
        $loginLog = $this->configuration->loginHistoryClass::create(
            $user->getUserIdentifier(),
            $user,
            $event->getRequest()->headers->get('User-Agent', ''),
            $event->getRequest()->getClientIp()
        );

        $loginLog->setPasswordOK(true);

        $em->persist($loginLog);

        $usd = $this->securityDataManager->getUserSecurityData($user);
        $usd->resetLoginFailureCount();

        $this->doctrine->getManager()->flush();

        // Also write a remember me expiry date, if applicable.
        $isRememberMe = $this->configuration->rememberMeEnabled &&
            $event->getRequest()->request->get('infub_remember_me');

        if ($this->deviceAuthenticationManager->loginSuccess(
            $user,
            $usd,
            $loginLog,
            $isRememberMe ? new \DateTimeImmutable("now + {$this->configuration->rememberMeLifetime} days") : null
        )) {
            $this->eventDispatcher->dispatch(new NewDeviceLoginEvent($loginLog, $user, $usd));
        }

        if ($this->configuration->pinEnabled && !$event->getRequest()->attributes->has('infub_was_pin_login')) {
            $event->getRequest()->getSession()->set('infub_show_pin_hint', 1);
        }
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $userBadge = $event->getPassport()->getBadge(UserBadge::class);
        $userIdentifier = $userBadge->getUserIdentifier();
        $em = $this->doctrine->getManager();

        try {
            $user = $userBadge->getUser();
        } catch (UserNotFoundException $ex) {
            $user = null;
        }

        if ($user !== null && !($user instanceof InfiniteUserInterface)) {
            return;
        }

        /** @var LoginHistoryInterface $loginLog */
        $loginLog = $this->configuration->loginHistoryClass::create(
            $userIdentifier,
            $user,
            $event->getRequest()->headers->get('User-Agent', ''),
            $event->getRequest()->getClientIp()
        );

        $loginLog->setPasswordOK(false);
        $em->persist($loginLog);

        if ($user) {
            /** @var UserSecurityDataInterface $usd */
            $usd = $em->getRepository($this->configuration->userSecurityDataClass)->find($user->getId());
            $usd->increaseLoginFailureCount();
        }

        $em->flush();
    }

    public function onLogout(LogoutEvent|TokenDeauthenticatedEvent $event): void
    {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();

        /** @var LoginHistoryInterface $loginLog */
        $loginLog = $em->getRepository($this->configuration->loginHistoryClass)->find($this->deviceAuthenticationManager->getLoginHistoryId());

        $this->deviceAuthenticationManager->forgetMe();

        if ($loginLog) {
            $loginLog->updateLogoutDate();
            $em->flush();
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckPassportEvent::class        => 'checkPassport',
            LoginSuccessEvent::class         => 'onLoginSuccess',
            LoginFailureEvent::class         => 'onLoginFailure',
            LogoutEvent::class               => 'onLogout',
            TokenDeauthenticatedEvent::class => 'onLogout',
        ];
    }
}
