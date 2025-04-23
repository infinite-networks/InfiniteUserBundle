<?php declare(strict_types=1);

/**
 * This file is part of Aspen Rhino
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 */

namespace Infinite\UserBundle\Security\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class InvalidPinException extends AuthenticationException
{
    public function getMessageKey(): string
    {
        return 'Incorrect PIN.';
    }
}