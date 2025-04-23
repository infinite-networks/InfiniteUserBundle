<?php declare(strict_types=1);

/**
 * This file is part of Aspen Rhino
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 */

namespace Infinite\UserBundle\Security\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class PinExpiredException extends AuthenticationException
{
    public function getMessageKey(): string
    {
        return 'Your PIN has expired. Please log in normally.';
    }
}