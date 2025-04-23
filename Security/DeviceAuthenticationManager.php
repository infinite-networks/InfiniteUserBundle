<?php declare(strict_types=1);

/**
 * (c) Infinite Networks Pty Ltd <https://www.infinite.net.au/>
 */

namespace Infinite\UserBundle\Security;

use Infinite\UserBundle\Entity\InfiniteUserInterface;
use Infinite\UserBundle\Entity\LoginHistoryInterface;
use Infinite\UserBundle\Entity\UserSecurityDataInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Stores device authentication settings in a persistent cookie.
 *
 * This uses authenticated encryption so it's tamper-proof.
 */
class DeviceAuthenticationManager implements EventSubscriberInterface
{
    const TFA_DATE_KEY           = '2';  // Timestamp (YmdHis) when 2FA was last completed
    const UID_KEY                = 'u';  // Username/email of last successful authentication
    const PIN_KEY                = 'p';  // PIN for *this* device
    const PASSWORD_TIMESTAMP_KEY = 'pt'; // Snapshot of password timestamp (YmdHis) at last login attempt
    const LOGIN_HISTORY_KEY      = 'l';  // LoginHistory::id of last login attempt
    const REMEMBER_ME_KEY        = 'r';  // Timestamp (YmdHis) for when the Remember Me functionality should expire

    const COOKIE_NAME   = 'infubauth';
    const SNAPSHOT_NAME = 'infubauth_original';

    public function __construct(
        private readonly AuthenticatedEncryption $authenticatedEncryption,
        private readonly RequestStack $requestStack,
        private readonly int $twoFactorLifetime
    )
    {
    }

    public function hasFresh2FA(string $userIdentifier): bool
    {
        $vars = $this->getDeviceVars();

        if (!isset($vars[self::TFA_DATE_KEY]) || !isset($vars[self::UID_KEY])) {
            return false;
        }

        if ($vars[self::UID_KEY] !== $userIdentifier) {
            return false;
        }

        if ($vars[self::TFA_DATE_KEY] < date('YmdHis', strtotime($this->twoFactorLifetime.' days ago'))) {
            return false;
        }

        return true;
    }

    public function setFresh2FA(string $userIdentifier): void
    {
        $this->setDeviceVar(self::TFA_DATE_KEY, date('YmdHis'));
        $this->setDeviceVar(self::UID_KEY, $userIdentifier);
    }

    public function getLoginHistoryId(): int|null
    {
        return $this->getDeviceVar(self::LOGIN_HISTORY_KEY);
    }

    public function getUserIdentifier(): string|null
    {
        return $this->getDeviceVar(self::UID_KEY);
    }

    public function getPasswordTimestamp(): string|null
    {
        return $this->getDeviceVar(self::PASSWORD_TIMESTAMP_KEY);
    }

    public function getRememberedLoginHistoryId(): int|null
    {
        $rememberMeExpiry = $this->getDeviceVar(self::REMEMBER_ME_KEY);

        if (!$rememberMeExpiry || $rememberMeExpiry <= date('YmdHis')) {
            return null;
        }

        return $this->getDeviceVar(self::LOGIN_HISTORY_KEY);
    }

    public function getPin(): string|null
    {
        return $this->getDeviceVar(self::PIN_KEY);
    }

    public function setPin(string $userIdentifier, \DateTimeInterface $passwordLastChanged, int|string $pin): void
    {
        $this->setDeviceVar(self::UID_KEY, $userIdentifier);
        $this->setDeviceVar(self::PASSWORD_TIMESTAMP_KEY, $passwordLastChanged->format('YmdHis'));
        $this->setDeviceVar(self::PIN_KEY, (string)$pin);
    }

    public function loginSuccess(
        InfiniteUserInterface $user,
        UserSecurityDataInterface $userSecurityData,
        LoginHistoryInterface $loginHistory,
        ?\DateTimeInterface $rememberMeExpiry
    ): bool
    {
        $isNewDevice = false;

        // If a different user logs in, wipe the device's PIN and 2FA state.
        if ($this->getDeviceVar(self::UID_KEY) !== $user->getUserIdentifier()) {
            $this->wipeUserSettings();
            $isNewDevice = true;
        }

        $this->setDeviceVar(self::UID_KEY, $user->getUserIdentifier());
        $this->setDeviceVar(self::LOGIN_HISTORY_KEY, $loginHistory->getId());
        $this->setDeviceVar(self::PASSWORD_TIMESTAMP_KEY, $userSecurityData->getPasswordLastChanged()->format('YmdHis'));
        $this->setDeviceVar(self::REMEMBER_ME_KEY, $rememberMeExpiry?->format('YmdHis'));

        return $isNewDevice;
    }

    public function forgetMe(): void
    {
        $this->setDeviceVar(self::REMEMBER_ME_KEY, null);
    }

    /**
     * If $user changes their password, all other device PINs need to be invalidated.
     * This is achieved by updating the user's password timestamp.
     *
     * To keep the user's PIN valid on _this_ device, we update the timestamp here.
     */
    public function maybeUpdatePasswordTimestamp(InfiniteUserInterface $user, UserSecurityDataInterface $usd): void
    {
        if ($this->getDeviceVar(self::UID_KEY) === $user->getUserIdentifier()) {
            $this->setDeviceVar(self::PASSWORD_TIMESTAMP_KEY, $usd->getPasswordLastChanged()->format('YmdHis'));
        }
    }

    public function wipeUserSettings(): void
    {
        $this->requestStack->getCurrentRequest()->attributes->set(self::COOKIE_NAME, []);
    }

    private function getDeviceVar(string $name): string|int|null
    {
        $vars = $this->getDeviceVars();
        return $vars[$name] ?? null;
    }

    private function getDeviceVars(): array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null !== ($vars = $request->attributes->get(self::COOKIE_NAME))) {
            return $vars;
        }

        $hexData = $request->cookies->get(self::COOKIE_NAME);

        if (!$hexData) {
            return [];
        }

        try {
            $jsonVars = $this->authenticatedEncryption->decrypt($hexData);
            $vars = json_decode($jsonVars, true);
        } catch (\Throwable $ex) {
            // Doesn't matter, just continue with blank vars
            $vars = [];
        }

        $request->attributes->set(self::COOKIE_NAME, $vars);
        $request->attributes->set(self::SNAPSHOT_NAME, $vars);

        return $vars;
    }

    private function setDeviceVar(string $key, string|int|null $value): void
    {
        $vars = $this->getDeviceVars();
        $vars[$key] = $value;
        $request = $this->requestStack->getCurrentRequest();

        $request->attributes->set(self::COOKIE_NAME, $vars);
    }

    public function updateDeviceCookie(ResponseEvent $event): void
    {
        // Do nothing if vars not modified
        $deviceSettings = $event->getRequest()->attributes->get(self::COOKIE_NAME);
        $lastDeviceSettings = $event->getRequest()->attributes->get(self::SNAPSHOT_NAME);

        if ($deviceSettings === $lastDeviceSettings) {
            return;
        }

        // Save encrypted vars to cookie
        $event->getResponse()->headers->setCookie(new Cookie(
            self::COOKIE_NAME,
            $this->authenticatedEncryption->encrypt(json_encode($deviceSettings)),
            new \DateTime('+ 10 years')
        ));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'updateDeviceCookie',
        ];
    }
}
