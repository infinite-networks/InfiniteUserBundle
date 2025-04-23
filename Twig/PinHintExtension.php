<?php declare(strict_types=1);

/**
 * (c) Infinite Networks Pty Ltd <https://www.infinite.net.au/>
 */

namespace Infinite\UserBundle\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PinHintExtension extends AbstractExtension
{
    public function __construct(
        private RequestStack $requestStack,
    )
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('infub_should_show_pin_hint', [$this, 'shouldShowPinHint']),
        ];
    }

    public function shouldShowPinHint(): bool
    {
        if (!$this->requestStack->getCurrentRequest()->attributes->get('infub_postpone_pin_hint') &&
            $this->requestStack->getSession()->has('infub_show_pin_hint')) {
            $this->requestStack->getSession()->remove('infub_show_pin_hint');
            return true;
        }
        return false;
    }
}
