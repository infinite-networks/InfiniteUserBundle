<?php declare(strict_types=1);

/**
 * (c) Infinite Networks Pty Ltd <https://www.infinite.net.au/>
 */

namespace Infinite\UserBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;

interface InfiniteUserInterface extends UserInterface
{
    function getId(): int|string;
}
