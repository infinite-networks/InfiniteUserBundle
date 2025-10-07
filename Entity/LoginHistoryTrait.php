<?php declare(strict_types=1);
/*
 * (c) Infinite Networks Pty Ltd <https://www.infinite.net.au>
 */

namespace Infinite\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

trait LoginHistoryTrait
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: 'string', length: 254)]
    protected string $userIdentifier = '';

    #[ORM\Column(type: 'boolean')]
    protected bool $passwordOK = false;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected ?bool $twoFactorOK = null;

    #[ORM\Column(type: 'datetime_immutable')]
    protected \DateTimeImmutable $loginDate;

    #[ORM\Column(type: 'datetime_immutable')]
    protected \DateTimeImmutable $lastActivity;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected ?\DateTimeImmutable $logoutDate = null;

    #[ORM\Column(type: 'string', length: 100)]
    protected string $os;

    #[ORM\Column(type: 'string', length: 100)]
    protected string $browser;

    #[ORM\Column(type: 'string', length: 20)]
    protected string $browserVersion;

    #[ORM\Column(name: 'ipAddress', type: 'string', length: 39)]
    protected string $ip;

    protected function initialiseLoginHistory(string $userIdentifier, string $userAgent, string $ip): void
    {
        $ua = \donatj\UserAgent\parse_user_agent($userAgent);

        $this->userIdentifier = $userIdentifier;
        $this->loginDate = new \DateTimeImmutable;
        $this->lastActivity = new \DateTimeImmutable;
        $this->os = $ua['platform'] ?? '';
        $this->browser = $ua['browser'] ?? '';
        $this->browserVersion = $ua['version'] ?? '';
        $this->ip = $ip;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return $this->userIdentifier;
    }

    public function isPasswordOK(): bool
    {
        return $this->passwordOK;
    }

    public function getTwoFactorOK(): ?bool
    {
        return $this->twoFactorOK;
    }

    public function setTwoFactorOK(?bool $twoFactorOK): void
    {
        $this->twoFactorOK = $twoFactorOK;
    }

    public function getLoginDate(): \DateTimeImmutable
    {
        return $this->loginDate;
    }

    public function getLastActivity(): \DateTimeImmutable
    {
        return $this->lastActivity;
    }

    public function getLogoutDate(): ?\DateTimeImmutable
    {
        return $this->logoutDate;
    }

    public function getOs(): string
    {
        return $this->os;
    }

    public function getBrowser(): string
    {
        return $this->browser;
    }

    public function getBrowserVersion(): string
    {
        return $this->browserVersion;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function setPasswordOK(bool $passwordOK): void
    {
        $this->passwordOK = $passwordOK;
    }

    public function updateLastActivity(): void
    {
        $this->lastActivity = new \DateTimeImmutable();
    }

    public function updateLogoutDate(): void
    {
        if (!$this->logoutDate) {
            $this->logoutDate = new \DateTimeImmutable;
        }
    }
}
