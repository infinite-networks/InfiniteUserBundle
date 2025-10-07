<?php declare(strict_types=1);
/*
 * (c) Infinite Networks Pty Ltd <https://www.infinite.net.au>
 */

namespace Infinite\UserBundle\Entity;

interface LoginHistoryInterface
{
    static function create(string $userIdentifier, ?InfiniteUserInterface $user, string $userAgent, string $ip): self;

    public function getId(): string|int|null;
    public function getUserIdentifier(): string;
    public function isPasswordOK(): bool;
    public function setPasswordOK(bool $passwordOK): void;
    public function getTwoFactorOK(): ?bool;
    public function setTwoFactorOK(?bool $twoFactorOK): void;
    public function getLoginDate(): \DateTimeImmutable;
    public function getLastActivity(): \DateTimeImmutable;
    public function getLogoutDate(): ?\DateTimeImmutable;
    public function getOs(): string;
    public function getBrowser(): string;
    public function getBrowserVersion(): string;
    public function getIp(): string;
    public function updateLastActivity(): void;
    public function updateLogoutDate(): void;
}
