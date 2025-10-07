<?php declare(strict_types=1);
/*
 * (c) Infinite Networks Pty Ltd <https://www.infinite.net.au>
 */

namespace Infinite\UserBundle\Security;

use Doctrine\Persistence\ManagerRegistry;
use Infinite\UserBundle\Configuration\Configuration;
use Infinite\UserBundle\Entity\InfiniteUserInterface;
use Infinite\UserBundle\Entity\UserSecurityDataInterface;

readonly class SecurityDataManager
{
    public function __construct(
        private Configuration   $configuration,
        private ManagerRegistry $doctrine,
    )
    {
    }

    public function getUserSecurityData(InfiniteUserInterface $user): UserSecurityDataInterface
    {
        $usd = $this->doctrine->getRepository($this->configuration->userSecurityDataClass)->findOneBy(['id' => $user->getId()]);

        if (!$usd) {
            /** @var UserSecurityDataInterface $usd */
            $usd = $this->configuration->userSecurityDataClass::create($user);
            $usd->setPasswordLastChanged(new \DateTimeImmutable);

            if ($this->configuration->twoFactorGrace !== null) {
                $usd->setTwoFactorDeadline(new \DateTimeImmutable("+ {$this->configuration->twoFactorGrace} days"));
            }

            $this->doctrine->getManager()->persist($usd);
            $this->doctrine->getManager()->flush();
        }

        return $usd;
    }

    public function save(UserSecurityDataInterface $usd): void
    {
        $this->doctrine->getManager()->persist($usd);
        $this->doctrine->getManager()->flush();
    }
}
