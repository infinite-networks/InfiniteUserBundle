<?php declare(strict_types=1);
/*
 * (c) Infinite Networks Pty Ltd <https://www.infinite.net.au>
 */

namespace Infinite\UserBundle\Event;

use Infinite\UserBundle\Entity\InfiniteUserInterface;
use Infinite\UserBundle\Entity\LoginHistoryInterface;
use Infinite\UserBundle\Entity\UserSecurityDataInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * User has logged in from a device for the first time.
 *
 * Use this event to email a user to let them know they've logged in from a new device.
 */
class NewDeviceLoginEvent extends Event
{
    public function __construct(
        private readonly LoginHistoryInterface $loginHistory,
        private readonly InfiniteUserInterface $user,
        private readonly UserSecurityDataInterface $userSecurityData,
    )
    {
    }

    public function getLoginHistory(): LoginHistoryInterface
    {
        return $this->loginHistory;
    }

    public function getUser(): InfiniteUserInterface
    {
        return $this->user;
    }

    public function getUserSecurityData(): UserSecurityDataInterface
    {
        return $this->userSecurityData;
    }
}