<?php declare(strict_types=1);
/*
 * (c) Infinite Networks Pty Ltd <https://www.infinite.net.au>
 */

namespace Infinite\UserBundle\Event;

use Infinite\UserBundle\Entity\InfiniteUserInterface;
use Infinite\UserBundle\Entity\UserSecurityDataInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

// By default, if 2FA is enabled then all users must periodically enter a 2FA code.
// However, an application may need to to narrow it down to specific pages, roles, etc.
//
// If so, listen to this event. It is raised on login and on every page load if the user
// has not recently entered a 2FA code.
class TwoFactorRequiredEvent extends Event
{
    private bool $twoFactorRequired = true;

    public function __construct(
        private readonly Request $request,
        private readonly InfiniteUserInterface $user,
        private readonly UserSecurityDataInterface $userSecurityData,
    )
    {
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getUser(): InfiniteUserInterface
    {
        return $this->user;
    }

    public function getUserSecurityData(): UserSecurityDataInterface
    {
        return $this->userSecurityData;
    }

    public function isTwoFactorRequired(): bool
    {
        return $this->twoFactorRequired;
    }

    public function setTwoFactorRequired(bool $twoFactorRequired): void
    {
        $this->twoFactorRequired = $twoFactorRequired;
    }
}