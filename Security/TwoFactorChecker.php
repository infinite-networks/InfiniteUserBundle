<?php declare(strict_types=1);
/*
 * (c) Infinite Networks Pty Ltd <https://www.infinite.net.au>
 */

namespace Infinite\UserBundle\Security;

use App\Entity\UserSecurityData;
use Doctrine\Persistence\ManagerRegistry;
use Infinite\UserBundle\Configuration\Configuration;
use Infinite\UserBundle\Entity\InfiniteUserInterface;
use Infinite\UserBundle\Entity\UserSecurityDataInterface;
use Infinite\UserBundle\Event\TwoFactorRequiredEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class TwoFactorChecker
{
    // User must be redirected to enter a 2FA code or set it up if not already configured.
    const TWO_FACTOR_NEEDED = '2fa-needed';

    // User should receive a nag message to set up 2FA.
    const TWO_FACTOR_NEEDED_SOON = '2fa-needed-soon';

    // Catch-all result for all other situations:
    // * User not logged in
    // * User doesn't need to use 2FA
    // * User is already on the 2FA page
    // * User has already entered a 2FA code in this session
    // * We're running a functional test and the functional test isn't specifically testing 2FA.
    const TWO_FACTOR_NOT_NEEDED = '2fa-not-needed';

    public function __construct(
        private readonly Configuration $configuration,
        private readonly DeviceAuthenticationManager $deviceAuthenticationManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ManagerRegistry $doctrine,
    )
    {
    }

    /**
     * Some users need to use 2FA on every single page.
     * This function checks if the current user is such a user.
     * This can be called early in the request lifecycle.
     */
    public function check2FA(?InfiniteUserInterface $user, Request $request): string
    {
        if (!$this->configuration->twoFactorEnabled) {
            return static::TWO_FACTOR_NOT_NEEDED;
        }

        if (null === $user) {
            // User not logged in.
            return static::TWO_FACTOR_NOT_NEEDED;
        }

        $session = $request->getSession();

        if ($this->deviceAuthenticationManager->hasFresh2FA($user->getUserIdentifier())) {
            // User has entered 2FA very recently.
            return static::TWO_FACTOR_NOT_NEEDED;
        }

        if ($request->getPathInfo() === '/logout' || $request->getPathInfo() === $this->configuration->twoFactorLoginPage) {
            // User is on a page that shouldn't be redirected.
            return static::TWO_FACTOR_NOT_NEEDED;
        }

        /** @var UserSecurityDataInterface $usd */
        $usd = $this->doctrine->getRepository($this->configuration->userSecurityDataClass)->findOneBy(['id' => $user->getId()]);

        if (!$usd) {
            $usd = $this->configuration->userSecurityDataClass::create($user);
            $this->doctrine->getManager()->persist($usd);
        }

        $event = new TwoFactorRequiredEvent($request, $user, $usd);
        $this->eventDispatcher->dispatch($event);

        if (!$event->isTwoFactorRequired()) {
            return static::TWO_FACTOR_NOT_NEEDED;
        }

        if ($usd->getTwoFactorDeadline() >= new \DateTimeImmutable) {
            return static::TWO_FACTOR_NEEDED_SOON;
        }

        return static::TWO_FACTOR_NEEDED;
    }
}
