<?php declare(strict_types=1);

/**
 * (c) Infinite Networks Pty Ltd <https://www.infinite.net.au/>
 */

namespace Infinite\UserBundle\Form\Model;

use OTPHP\TOTP;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Note: this class is used both for setting up 2FA and logging in with 2FA.
 */
class TwoFactorLoginModel
{
    public ?string $code = null;

    public string $secret;
    private ?TOTP $totp = null;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    /**
     * Lazily create a TOTP instance.
     * This must only be called AFTER processing the submitted form.
     */
    public function getTotp(): TOTP
    {
        if (!$this->totp) {
            $this->totp = TOTP::createFromSecret($this->secret, Clock::get());;
        }

        return $this->totp;
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        if (!$this->getTotp()->verify($this->code, null, 1)) {
            $context
                ->buildViolation('Incorrect code. Please try again.')
                ->atPath('code')
                ->addViolation();
        }
    }
}
