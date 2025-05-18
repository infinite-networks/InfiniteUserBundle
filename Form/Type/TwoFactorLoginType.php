<?php declare(strict_types=1);

/**
 * (c) Infinite Networks Pty Ltd <https://www.infinite.net.au/>
 */

namespace Infinite\UserBundle\Form\Type;

use Infinite\UserBundle\Form\Model\TwoFactorLoginModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TwoFactorLoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('code', TextType::class, [
            'required' => true,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'two_factor_login';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TwoFactorLoginModel::class,
        ]);
    }
}
