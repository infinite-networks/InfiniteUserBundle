<?php declare(strict_types=1);
/*
 * (c) Infinite Networks Pty Ltd <https://www.infinite.net.au>
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