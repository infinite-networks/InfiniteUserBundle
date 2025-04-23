<?php declare(strict_types=1);

/**
 * (c) Infinite Networks Pty Ltd <https://www.infinite.net.au/>
 */

namespace Infinite\UserBundle\Configuration;

readonly class Configuration
{
    public function __construct(
        array $config,
    )
    {
        $this->userClass = $config['user_class'];
        $this->loginHistoryClass = $config['login_history_class'];
        $this->userSecurityDataClass = $config['user_security_data_class'];
        $this->lockoutPolicy = $config['lockout_policy'];
        $this->rememberMeEnabled = $config['remember_me_enabled'];
        $this->rememberMeLifetime = $config['remember_me_lifetime'];
        $this->twoFactorEnabled = $config['two_factor_enabled'];
        $this->twoFactorGrace = $config['two_factor_grace'];
        $this->twoFactorLifetime = $config['two_factor_lifetime'];
        $this->twoFactorLoginPage = $config['two_factor_login_page'];
        $this->twoFactorLabel = $config['two_factor_label'];
        $this->twoFactorIssuer = $config['two_factor_issuer'];
        $this->pinEnabled = $config['pin_enabled'];
        $this->pinCheckUrl = $config['pin_check_url'];
    }

    public string $userClass;
    public string $loginHistoryClass;
    public string $userSecurityDataClass;
    public ?int   $lockoutPolicy;
    public bool   $rememberMeEnabled;
    public int    $rememberMeLifetime;
    public bool   $twoFactorEnabled;
    public ?int   $twoFactorGrace;
    public int    $twoFactorLifetime;
    public string $twoFactorLoginPage;
    public string $twoFactorLabel;
    public string $twoFactorIssuer;
    public bool   $pinEnabled;
    public string $pinCheckUrl;
}