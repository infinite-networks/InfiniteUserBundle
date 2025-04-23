<?php declare(strict_types=1);

/**
 * (c) Infinite Networks Pty Ltd <https://www.infinite.net.au/>
 */

namespace Infinite\UserBundle\Security;

use Doctrine\Persistence\ManagerRegistry;
use Infinite\UserBundle\Configuration\Configuration;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

class InfiniteRememberMeAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        private readonly Configuration $configuration,
        private readonly DeviceAuthenticationManager $deviceAuthenticationManager,
        private readonly ManagerRegistry $doctrine,
        private readonly TokenStorageInterface $tokenStorage,
    )
    {
    }

    public function supports(Request $request): ?bool
    {
        if ($this->tokenStorage->getToken()) {
            return false;
        }

        return !!$this->deviceAuthenticationManager->getRememberedLoginHistoryId();
    }

    public function authenticate(Request $request): Passport
    {
        $loginHistoryId = $this->deviceAuthenticationManager->getRememberedLoginHistoryId();

        $repo = $this->doctrine->getManager()->getRepository($this->configuration->loginHistoryClass);

        if (!($loginLog = $repo->find($loginHistoryId))) {
            throw new \RuntimeException('LoginHistory item missing');
        }

        $userBadge = new UserBadge($loginLog->getUserIdentifier());
        $passport = new SelfValidatingPassport($userBadge);
        $passport->setAttribute('is_remember_me', true);

        return $passport;
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        return new PostAuthenticationToken($passport->getUser(), $firewallName, $passport->getUser()->getRoles());
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return null;
    }
}
