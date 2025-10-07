<?php declare(strict_types=1);
/*
 * (c) Infinite Networks Pty Ltd <https://www.infinite.net.au>
 */

namespace Infinite\UserBundle\Security;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TwoFactorControllerHelperResult
{
    public function __construct(array $options)
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(['redirectToHome' => false, 'customRedirect' => null, 'showLoginCodePage' => false, 'showRegistrationPage' => false, 'loginSuccess' => false, 'registrationSuccess' => false, 'csrfError' => false, 'form' => null, 'pastDeadline' => false]);
        $options = $resolver->resolve($options);
        $this->redirectToHome = $options['redirectToHome'];
        $this->customRedirect = $options['customRedirect'];
        $this->showLoginCodePage = $options['showLoginCodePage'];
        $this->showRegistrationPage = $options['showRegistrationPage'];
        $this->loginSuccess = $options['loginSuccess'];
        $this->registrationSuccess = $options['registrationSuccess'];
        $this->csrfError = $options['csrfError'];
        $this->form = $options['form'];
        $this->pastDeadline = $options['pastDeadline'];
    }

    public bool $redirectToHome;
    public ?string $customRedirect;
    public bool $showLoginCodePage;
    public bool $showRegistrationPage;
    public bool $loginSuccess;
    public bool $registrationSuccess;
    public bool $csrfError;
    public bool $pastDeadline;
    public ?FormInterface $form;
}
