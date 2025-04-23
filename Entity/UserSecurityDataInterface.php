<?php declare(strict_types=1);

/**
 * (c) Infinite Networks Pty Ltd <https://www.infinite.net.au/>
 */

namespace Infinite\UserBundle\Entity;

interface UserSecurityDataInterface
{
    static function create(InfiniteUserInterface $user): self;

    function getId(): string|int;
    function getTwoFactorDeadline(): ?\DateTimeImmutable;
    function getTwoFactorSecret(): ?string;
    function getLoginFailureCount(): int;
    function increaseLoginFailureCount(): void;
    function resetLoginFailureCount(): void;
    function getTwoFactorFailureCount(): int;
    function increaseTwoFactorFailureCount(): void;
    function resetTwoFactorFailureCount(): void;
    function getPasswordLastChanged(): \DateTimeImmutable;
    function setPasswordLastChanged(\DateTimeImmutable $passwordLastChanged): void;
    function setTwoFactorDeadline(?\DateTimeImmutable $twoFactorDeadline): void;
    function isLocked(?int $lockoutPolicy): bool;
}
