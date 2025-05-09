<?php declare(strict_types=1);

/**
 * (c) Infinite Networks Pty Ltd <https://www.infinite.net.au/>
 */

namespace Infinite\UserBundle\Form\Type;

use Infinite\UserBundle\Form\Model\TwoFactorLoginModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TwoFactorRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('code', TextType::class, [
            'required' => true,
        ]);
        $builder->add('secret', HiddenType::class);
    }

    public function getBlockPrefix(): string
    {
        return 'two_factor_register';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TwoFactorLoginModel::class,
        ]);
    }
}
