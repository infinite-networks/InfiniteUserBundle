<?php declare(strict_types=1);

/**
 * (c) Infinite Networks Pty Ltd <https://www.infinite.net.au/>
 */

namespace Infinite\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\Exception\LockedException;

trait UserSecurityDataTrait
{
    #[ORM\Column(type: 'datetime_immutable')]
    protected \DateTimeImmutable $passwordLastChanged;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    protected ?string $confirmationToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected ?\DateTimeImmutable $passwordRequestedAt = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    protected ?\DateTimeImmutable $passwordExpiresAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected ?\DateTimeImmutable $passwordExpiryEmailSentAt = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    protected int $loginFailureCount = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    protected int $twoFactorFailureCount = 0;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected ?\DateTimeImmutable $twoFactorDeadline = null;

    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $twoFactorSecret = null;

    public function getPasswordLastChanged(): \DateTimeImmutable
    {
        return $this->passwordLastChanged;
    }

    public function setPasswordLastChanged(\DateTimeImmutable $passwordLastChanged): void
    {
        $this->passwordLastChanged = $passwordLastChanged;
    }

    public function getConfirmationToken(): ?string
    {
        return $this->confirmationToken;
    }

    public function setConfirmationToken(?string $confirmationToken): void
    {
        $this->confirmationToken = $confirmationToken;
    }

    public function getPasswordRequestedAt(): ?\DateTimeImmutable
    {
        return $this->passwordRequestedAt;
    }

    public function setPasswordRequestedAt(?\DateTimeImmutable $passwordRequestedAt): void
    {
        $this->passwordRequestedAt = $passwordRequestedAt;
    }

    public function getPasswordExpiresAt(): ?\DateTimeImmutable
    {
        return $this->passwordExpiresAt;
    }

    public function setPasswordExpiresAt(?\DateTimeImmutable $passwordExpiresAt): void
    {
        $this->passwordExpiresAt = $passwordExpiresAt;
    }

    public function getPasswordExpiryEmailSentAt(): ?\DateTimeImmutable
    {
        return $this->passwordExpiryEmailSentAt;
    }

    public function setPasswordExpiryEmailSentAt(?\DateTimeImmutable $passwordExpiryEmailSentAt): void
    {
        $this->passwordExpiryEmailSentAt = $passwordExpiryEmailSentAt;
    }

    public function getLoginFailureCount(): int
    {
        return $this->loginFailureCount;
    }

    public function increaseLoginFailureCount(): void
    {
        $this->loginFailureCount++;
    }

    public function resetLoginFailureCount(): void
    {
        $this->loginFailureCount = 0;
    }

    public function getTwoFactorFailureCount(): int
    {
        return $this->twoFactorFailureCount;
    }

    public function increaseTwoFactorFailureCount(): void
    {
        $this->twoFactorFailureCount++;
    }

    public function resetTwoFactorFailureCount(): void
    {
        $this->twoFactorFailureCount = 0;
    }

    public function getTwoFactorDeadline(): ?\DateTimeImmutable
    {
        return $this->twoFactorDeadline;
    }

    public function setTwoFactorDeadline(?\DateTimeImmutable $twoFactorDeadline): void
    {
        $this->twoFactorDeadline = $twoFactorDeadline;
    }

    public function getTwoFactorSecret(): ?string
    {
        return $this->twoFactorSecret;
    }

    public function setTwoFactorSecret(?string $twoFactorSecret): void
    {
        $this->twoFactorSecret = $twoFactorSecret;
    }

    public function isLocked(?int $lockoutPolicy): bool
    {
        return $lockoutPolicy !== null && (
            $this->loginFailureCount >= $lockoutPolicy ||
            $this->twoFactorFailureCount >= $lockoutPolicy
        );
    }
}
