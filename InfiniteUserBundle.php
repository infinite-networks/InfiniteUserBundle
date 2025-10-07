<?php declare(strict_types=1);
/*
 * (c) Infinite Networks Pty Ltd <https://www.infinite.net.au>
 */

namespace Infinite\UserBundle;

use Doctrine\Persistence\ManagerRegistry;
use Infinite\UserBundle\Configuration\Configuration;
use Infinite\UserBundle\EventListener\LoginListener;
use Infinite\UserBundle\EventListener\RequestListener;
use Infinite\UserBundle\Random\RandomBytes;
use Infinite\UserBundle\Security\AuthenticatedEncryption;
use Infinite\UserBundle\Security\DeviceAuthenticationManager;
use Infinite\UserBundle\Security\InfiniteRememberMeAuthenticator;
use Infinite\UserBundle\Security\InfinitePinAuthenticator;
use Infinite\UserBundle\Security\SecurityDataManager;
use Infinite\UserBundle\Security\TwoFactorChecker;
use Infinite\UserBundle\Security\TwoFactorControllerHelper;
use Infinite\UserBundle\Twig\PinHintExtension;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

class InfiniteUserBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('user_class')->isRequired()->end()
                ->scalarNode('login_history_class')->isRequired()->end()
                ->scalarNode('user_security_data_class')->isRequired()->end()
                ->scalarNode('lockout_policy')->defaultValue(30)->end()
                ->scalarNode('remember_me_enabled')->defaultValue(true)->end()
                ->scalarNode('remember_me_lifetime')->defaultValue(3650)->end()
                ->scalarNode('two_factor_enabled')->defaultValue(true)->end()
                ->scalarNode('two_factor_grace')->defaultValue(14)->end()
                ->scalarNode('two_factor_lifetime')->defaultValue(30)->end()
                ->scalarNode('two_factor_label')->defaultValue('Project')->end()
                ->scalarNode('two_factor_issuer')->defaultValue('Company Name')->end()
                ->scalarNode('two_factor_login_page')->defaultValue('/authenticate')->end()
                ->scalarNode('pin_enabled')->defaultValue(true)->end()
                ->scalarNode('pin_check_url')->defaultValue('/pin_login_check')->end()
            ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // Configuration
        $container->services()
            ->set(Configuration::class, Configuration::class)
            ->args([
                $config,
            ]);

        // Event listeners
        $container->services()
            ->set(LoginListener::class, LoginListener::class)
            ->args([
                service(Configuration::class),
                service(DeviceAuthenticationManager::class),
                service(ManagerRegistry::class),
                service(EventDispatcherInterface::class),
                service(SecurityDataManager::class),
            ])
            ->tag('kernel.event_subscriber');
        $container->services()
            ->set(RequestListener::class, RequestListener::class)
            ->args([
                service(Configuration::class),
                service(DeviceAuthenticationManager::class),
                service(ManagerRegistry::class),
                service('security.firewall.map'),
                service(SecurityDataManager::class),
                service(TokenStorageInterface::class),
                service(TwoFactorChecker::class),
            ])
            ->tag('kernel.event_subscriber');

        // Security
        $container->services()
            ->set(AuthenticatedEncryption::class, AuthenticatedEncryption::class)
            ->args([
                '%kernel.secret%',
            ]);
        $container->services()
            ->set(DeviceAuthenticationManager::class, DeviceAuthenticationManager::class)
            ->args([
                service(AuthenticatedEncryption::class),
                service(RequestStack::class),
                $config['two_factor_lifetime']
            ])
            ->tag('kernel.event_subscriber');
        $container->services()
            ->set(InfiniteRememberMeAuthenticator::class, InfiniteRememberMeAuthenticator::class)
            ->args([
                service(Configuration::class),
                service(DeviceAuthenticationManager::class),
                service(ManagerRegistry::class),
                service(TokenStorageInterface::class),
            ]);
        $container->services()
            ->set(InfinitePinAuthenticator::class, InfinitePinAuthenticator::class)
            ->args([
                service(Configuration::class),
                service(DeviceAuthenticationManager::class),
                service(FormFactoryInterface::class),
                service(SecurityDataManager::class),
            ]);
        $container->services()
            ->set(RandomBytes::class, RandomBytes::class);
        $container->services()
            ->set(SecurityDataManager::class, SecurityDataManager::class)
            ->args([
                service(Configuration::class),
                service(ManagerRegistry::class),
            ]);
        $container->services()
            ->set(TwoFactorChecker::class, TwoFactorChecker::class)
            ->args([
                service(Configuration::class),
                service(DeviceAuthenticationManager::class),
                service(EventDispatcherInterface::class),
                service(ManagerRegistry::class),
            ]);
        $container->services()
            ->set(TwoFactorControllerHelper::class, TwoFactorControllerHelper::class)
            ->args([
                service(Configuration::class),
                service(DeviceAuthenticationManager::class),
                service(EventDispatcherInterface::class),
                service(FormFactoryInterface::class),
                service(ManagerRegistry::class),
                service(RandomBytes::class),
                service(RequestStack::class),
                service(SecurityDataManager::class),
                service(TokenStorageInterface::class),
            ]);

        // Twig extensions
        $container->services()
            ->set(PinHintExtension::class, PinHintExtension::class)
            ->args([
                service(RequestStack::class),
            ])
            ->tag('twig.extension');
    }
}
