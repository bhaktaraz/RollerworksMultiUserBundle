<?php

/*
 * This file is part of the RollerworksMultiUserBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\MultiUserBundle\Tests\DependencyInjection;

use Rollerworks\Bundle\MultiUserBundle\DependencyInjection\Factory\UserServicesFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @todo Test canonicalizer
 * @todo Test custom user-manager setting
 */
class UserServicesFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerBuilder
     */
    protected $containerBuilder;

    public function testRegisterBasic()
    {
        $factory = new UserServicesFactory($this->containerBuilder);

        $config = array(
            array(
                'path' => '/',
                'user_class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\User',
                'services_prefix' => 'acme_user',
                'routes_prefix' => 'acme_user',

                'profile' => false,
                'registration' => false,
                'resetting' => false,
                'change_password' => false,
            ),
        );

        $factory->create('acme', $config);

        $this->assertTrue($this->containerBuilder->hasAlias('acme_user.user_manager'));
        $this->assertTrue($this->containerBuilder->hasAlias('acme_user.group_manager'));

        $this->assertTrue($this->containerBuilder->hasDefinition('rollerworks_multi_user.user_system.acme'));

        $def = $this->containerBuilder->getDefinition('acme_user.user_manager.default');
        $this->assertEquals(new Reference('rollerworks_multi_user.acme_user.model_manager'), $def->getArgument(3));
        $this->assertEquals('%acme_user.model.user.class%', $def->getArgument(4));

        $def = $this->containerBuilder->getDefinition('rollerworks_multi_user.user_system.acme');
        $this->assertEquals('Rollerworks\Bundle\MultiUserBundle\Model\UserConfig', $def->getClass());
        $this->assertEquals(array(array('alias' => 'acme', 'class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\User', 'path' => '/', 'host' => null, 'db_driver' => 'orm')), $def->getTag('rollerworks_multi_user.user_system'));
        $this->assertTrue($def->isLazy());

        $this->assertEquals(array('acme_user', 'acme_user', new Reference('acme_user.user_manager'), new Reference('acme_user.group_manager')), $def->getArguments());
    }

    public function testRequestMatcher()
    {
        $factory = new UserServicesFactory($this->containerBuilder);

        $config = array(
            array(
                'request_matcher' => 'acme_user.request_matcher',
                'user_class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\User',
                'services_prefix' => 'acme_user',
                'routes_prefix' => 'acme_user',

                'profile' => false,
                'registration' => false,
                'resetting' => false,
                'change_password' => false,
            ),
        );

        $factory->create('acme', $config);

        $this->assertTrue($this->containerBuilder->hasAlias('acme_user.user_manager'));
        $this->assertTrue($this->containerBuilder->hasAlias('acme_user.group_manager'));

        $this->assertTrue($this->containerBuilder->hasDefinition('rollerworks_multi_user.user_system.acme'));

        $def = $this->containerBuilder->getDefinition('acme_user.user_manager.default');
        $this->assertEquals(new Reference('rollerworks_multi_user.acme_user.model_manager'), $def->getArgument(3));
        $this->assertEquals('%acme_user.model.user.class%', $def->getArgument(4));

        $def = $this->containerBuilder->getDefinition('rollerworks_multi_user.user_system.acme');
        $this->assertEquals('Rollerworks\Bundle\MultiUserBundle\Model\UserConfig', $def->getClass());
        $this->assertEquals(array(array('alias' => 'acme', 'class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\User', 'path' => null, 'host' => null, 'request_matcher' => 'acme_user.request_matcher', 'db_driver' => 'orm')), $def->getTag('rollerworks_multi_user.user_system'));
        $this->assertTrue($def->isLazy());

        $this->assertEquals(array('acme_user', 'acme_user', new Reference('acme_user.user_manager'), new Reference('acme_user.group_manager')), $def->getArguments());
    }

    /**
     * @dataProvider provideModelManagerConfigs
     */
    public function testModelManager($driver, $service, $class)
    {
        $factory = new UserServicesFactory($this->containerBuilder);

        $config = array(
            array(
                'path' => '/',
                'db_driver' => $driver,
                'user_class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\User',
                'services_prefix' => 'acme_user',
                'routes_prefix' => 'acme_user',

                'profile' => false,
                'registration' => false,
                'resetting' => false,
                'change_password' => false,
            ),
        );

        $factory->create('acme', $config);

        $this->assertTrue($this->containerBuilder->hasDefinition('rollerworks_multi_user.acme_user.model_manager'));

        $def = $this->containerBuilder->getDefinition('rollerworks_multi_user.acme_user.model_manager');
        $this->assertEquals($service, $def->getFactoryService());
        $this->assertEquals($class, $def->getClass());

        $def = $this->containerBuilder->findDefinition(sprintf('rollerworks_multi_user.%s.user_listener', $driver));
        $this->assertRegExp('{Rollerworks\\\\Bundle\\\\MultiUserBundle\\\\Doctrine\\\\'.$driver.'\\\\UserListener}i', $def->getClass());

        if ('orm' === $driver) {
            $this->assertTrue($def->hasTag('doctrine.event_subscriber'));
        } elseif ('mongodb' === $driver) {
            $this->assertTrue($def->hasTag('doctrine_mongodb.odm.event_subscriber'));
        } else {
            $this->assertTrue($def->hasTag('doctrine_couchdb.event_subscriber'));
        }
    }

    public function testCustomModelManager()
    {
        $factory = new UserServicesFactory($this->containerBuilder);

        $config = array(
            array(
                'path' => '/',
                'db_driver' => 'orm',
                'model_manager_name' => 'admin',
                'user_class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\User',
                'services_prefix' => 'acme_user',
                'routes_prefix' => 'acme_user',

                'profile' => false,
                'registration' => false,
                'resetting' => false,
                'change_password' => false,
            ),
        );

        $factory->create('acme', $config);

        $this->assertTrue($this->containerBuilder->hasDefinition('rollerworks_multi_user.acme_user.model_manager'));

        $def = $this->containerBuilder->getDefinition('rollerworks_multi_user.acme_user.model_manager');
        $this->assertEquals('doctrine', $def->getFactoryService());
        $this->assertEquals('Doctrine\ORM\EntityManager', $def->getClass());
        $this->assertTrue($this->containerBuilder->hasParameter('acme_user.backend_type_orm'));
        $this->assertEquals('admin', $this->containerBuilder->getParameter('acme_user.model_manager_name'));

        $def = $this->containerBuilder->findDefinition('rollerworks_multi_user.orm.user_listener');
        $this->assertRegExp('{Rollerworks\\\\Bundle\\\\MultiUserBundle\\\\Doctrine\\\\Orm\\\\UserListener}i', $def->getClass());

        $this->assertTrue($def->hasTag('doctrine.event_subscriber'));
    }

    public function testDefaultMailer()
    {
        $factory = new UserServicesFactory($this->containerBuilder);

        $config = array(
            array(
                'path' => '/',
                'user_class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\User',
                'services_prefix' => 'acme_user',
                'routes_prefix' => 'acme_user',

                'profile' => false,
                'change_password' => false,

                'confirmation' => array(),
                'registration' => array(),
                'resetting' => array(),
            ),
        );

        $factory->create('acme', $config);

        $this->assertTrue($this->containerBuilder->hasAlias('acme_user.mailer'));
        $this->assertTrue($this->containerBuilder->hasDefinition('acme_user.mailer.default'));
        $this->assertEquals('acme_user.mailer.default', (string) $this->containerBuilder->getAlias('acme_user.mailer'));

        $def = $this->containerBuilder->getDefinition('acme_user.mailer.default');
        $this->assertEquals(new Reference('rollerworks_multi_user.routing.user_discriminator_url_generator'), $def->getArgument(1));
        $this->assertEquals(array(
            'confirmation.template' => sprintf('%%%s.registration.confirmation.email.template%%', 'acme_user'),
            'resetting.template' => sprintf('%%%s.resetting.email.template%%', 'acme_user'),
            'from_email' => array(
                'confirmation' => sprintf('%%%s.registration.confirmation.from_email%%', 'acme_user'),
                'resetting' => sprintf('%%%s.resetting.email.from_email%%', 'acme_user'),
            ),
        ), $def->getArgument(3));

        $def = $this->containerBuilder->getDefinition('rollerworks_multi_user.user_system.acme');
        $this->assertTemplateConfigEqual('RollerworksMultiUserBundle:UserBundle/Registration:email.txt.twig', 'acme_user', 'registration.confirmation', 'email', $def);
        $this->assertTemplateConfigEqual('RollerworksMultiUserBundle:UserBundle/Resetting:email.txt.twig', 'acme_user', 'resetting', 'email', $def);
    }

    public function testTwigSwiftMailer()
    {
        $factory = new UserServicesFactory($this->containerBuilder);

        $config = array(
            array(
                'path' => '/',
                'user_class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\User',
                'services_prefix' => 'acme_user',
                'routes_prefix' => 'acme_user',

                'service' => array(
                    'mailer' => 'fos_user.mailer.twig_swift',
                ),

                'profile' => false,
                'change_password' => false,

                'confirmation' => array(),
                'registration' => array(),
                'resetting' => array(),
            ),
        );

        $factory->create('acme', $config);

        $this->assertTrue($this->containerBuilder->hasAlias('acme_user.mailer'));
        $this->assertTrue($this->containerBuilder->hasDefinition('acme_user.mailer.twig_swift'));
        $this->assertEquals('acme_user.mailer.twig_swift', (string) $this->containerBuilder->getAlias('acme_user.mailer'));

        $def = $this->containerBuilder->getDefinition('acme_user.mailer.twig_swift');
        $this->assertEquals(new Reference('rollerworks_multi_user.routing.user_discriminator_url_generator'), $def->getArgument(1));
        $this->assertEquals(array(
            'template' => array(
                'confirmation' => sprintf('%%%s.registration.confirmation.email.template%%', 'acme_user'),
                'resetting' => sprintf('%%%s.resetting.email.template%%', 'acme_user'),
            ),
            'from_email' => array(
                'confirmation' => sprintf('%%%s.registration.confirmation.from_email%%', 'acme_user'),
                'resetting' => sprintf('%%%s.resetting.email.from_email%%', 'acme_user'),
            ),
        ), $def->getArgument(3));

        $def = $this->containerBuilder->getDefinition('rollerworks_multi_user.user_system.acme');
        $this->assertTemplateConfigEqual('RollerworksMultiUserBundle:UserBundle/Registration:email.txt.twig', 'acme_user', 'registration.confirmation', 'email', $def);
        $this->assertTemplateConfigEqual('RollerworksMultiUserBundle:UserBundle/Resetting:email.txt.twig', 'acme_user', 'resetting', 'email', $def);
    }

    public function testCustomMailer()
    {
        $this->containerBuilder->register('acme_mailer.user_mailer', 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub');

        $factory = new UserServicesFactory($this->containerBuilder);

        $config = array(
            array(
                'path' => '/',
                'user_class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\User',
                'services_prefix' => 'acme_user',
                'routes_prefix' => 'acme_user',

                'service' => array(
                    'mailer' => 'acme_mailer.user_mailer',
                ),

                'profile' => false,
                'change_password' => false,

                'confirmation' => array(),
                'registration' => array(),
                'resetting' => array(),
            ),
        );

        $factory->create('acme', $config);

        $this->assertTrue($this->containerBuilder->hasAlias('acme_user.mailer'));
        $this->assertTrue($this->containerBuilder->hasDefinition('acme_mailer.user_mailer'));
        $this->assertEquals('acme_mailer.user_mailer', (string) $this->containerBuilder->getAlias('acme_user.mailer'));

        $def = $this->containerBuilder->getDefinition('acme_mailer.user_mailer');
        $this->assertEquals(array(), $def->getArguments());

        $def = $this->containerBuilder->getDefinition('rollerworks_multi_user.user_system.acme');
        $this->assertTemplateConfigEqual('RollerworksMultiUserBundle:UserBundle/Registration:email.txt.twig', 'acme_user', 'registration.confirmation', 'email', $def);
        $this->assertTemplateConfigEqual('RollerworksMultiUserBundle:UserBundle/Resetting:email.txt.twig', 'acme_user', 'resetting', 'email', $def);
    }

    public function testCustomMailerNoOverwrite()
    {
        $this->containerBuilder->register('acme_user.mailer', 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub');

        $factory = new UserServicesFactory($this->containerBuilder);

        $config = array(
            array(
                'path' => '/',
                'user_class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\User',
                'services_prefix' => 'acme_user',
                'routes_prefix' => 'acme_user',

                'service' => array(
                    'mailer' => 'acme_user.mailer',
                ),

                'profile' => false,
                'change_password' => false,

                'confirmation' => array(),
                'registration' => array(),
                'resetting' => array(),
            ),
        );

        $factory->create('acme', $config);

        $this->assertFalse($this->containerBuilder->hasAlias('acme_user.mailer'));
        $this->assertTrue($this->containerBuilder->hasDefinition('acme_user.mailer'));

        $def = $this->containerBuilder->getDefinition('acme_user.mailer');
        $this->assertEquals(array(), $def->getArguments());

        $def = $this->containerBuilder->getDefinition('rollerworks_multi_user.user_system.acme');
        $this->assertTemplateConfigEqual('RollerworksMultiUserBundle:UserBundle/Registration:email.txt.twig', 'acme_user', 'registration.confirmation', 'email', $def);
        $this->assertTemplateConfigEqual('RollerworksMultiUserBundle:UserBundle/Resetting:email.txt.twig', 'acme_user', 'resetting', 'email', $def);
    }

    public function testProfileConfigurationDefaults()
    {
        $factory = new UserServicesFactory($this->containerBuilder);

        $config = array(
            array(
                'path' => '/',
                'user_class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\User',
                'services_prefix' => 'acme_user',
                'routes_prefix' => 'acme_user',

                'registration' => false,
                'resetting' => false,
                'change_password' => false,

                'profile' => array(),
            ),
        );

        $factory->create('acme', $config);

        $this->assertTrue($this->containerBuilder->hasAlias('acme_user.user_manager'));
        $this->assertTrue($this->containerBuilder->hasAlias('acme_user.group_manager'));

        $this->assertTrue($this->containerBuilder->hasDefinition('rollerworks_multi_user.user_system.acme'));

        $def = $this->containerBuilder->getDefinition('acme_user.user_manager.default');
        $this->assertEquals(new Reference('rollerworks_multi_user.acme_user.model_manager'), $def->getArgument(3));
        $this->assertEquals('%acme_user.model.user.class%', $def->getArgument(4));

        $def = $this->containerBuilder->getDefinition('rollerworks_multi_user.user_system.acme');
        $this->assertEquals('Rollerworks\Bundle\MultiUserBundle\Model\UserConfig', $def->getClass());
        $this->assertEquals(array(array('alias' => 'acme', 'class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\User', 'path' => '/', 'host' => null, 'db_driver' => 'orm')), $def->getTag('rollerworks_multi_user.user_system'));
        $this->assertTrue($def->isLazy());

        $expected = array(
            'class' => 'Rollerworks\Bundle\MultiUserBundle\Form\Type\ProfileFormType',
            'type' => 'acme_user_profile',
            'name' => 'acme_user_profile_form',
            'validation_groups' => array('Profile', 'Default'),
        );

        $this->assertFormDefinitionEqual($expected, 'acme_user', 'profile', $def);

        $expected = array(
            'edit' => 'RollerworksMultiUserBundle:UserBundle/Profile:edit.html.twig',
            'show' => 'RollerworksMultiUserBundle:UserBundle/Profile:show.html.twig',
        );

        foreach ($expected as $name => $resource) {
            $this->assertTemplateConfigEqual($resource, 'acme_user', 'profile', $name, $def);
        }
    }

    public function testProfileConfiguration()
    {
        $factory = new UserServicesFactory($this->containerBuilder);

        $config = array(
            array(
                'path' => '/',
                'user_class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\User',
                'services_prefix' => 'acme_user',
                'routes_prefix' => 'acme_user',

                'registration' => false,
                'resetting' => false,
                'change_password' => false,

                'profile' => array(
                    'form' => array(
                        'class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\Form\Type\ProfileType',
                        'type' => 'acme_user_profile',
                        'name' => 'acme_user_profile_form',
                        'validation_groups' => array('Profile'),
                    ),
                ),
            ),
        );

        $factory->create('acme', $config);

        $this->assertTrue($this->containerBuilder->hasAlias('acme_user.user_manager'));
        $this->assertTrue($this->containerBuilder->hasAlias('acme_user.group_manager'));

        $this->assertTrue($this->containerBuilder->hasDefinition('rollerworks_multi_user.user_system.acme'));

        $def = $this->containerBuilder->getDefinition('acme_user.user_manager.default');
        $this->assertEquals(new Reference('rollerworks_multi_user.acme_user.model_manager'), $def->getArgument(3));
        $this->assertEquals('%acme_user.model.user.class%', $def->getArgument(4));

        $def = $this->containerBuilder->getDefinition('rollerworks_multi_user.user_system.acme');
        $this->assertEquals('Rollerworks\Bundle\MultiUserBundle\Model\UserConfig', $def->getClass());
        $this->assertEquals(array(array('alias' => 'acme', 'class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\User', 'path' => '/', 'host' => null, 'db_driver' => 'orm')), $def->getTag('rollerworks_multi_user.user_system'));
        $this->assertTrue($def->isLazy());

        $expected = array(
            'class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\Form\Type\ProfileType',
            'type' => 'acme_user_profile',
            'name' => 'acme_user_profile_form',
            'validation_groups' => array('Profile'),
        );

        $this->assertFormDefinitionEqual($expected, 'acme_user', 'profile', $def);

        $expected = array(
            'edit' => 'RollerworksMultiUserBundle:UserBundle/Profile:edit.html.twig',
            'show' => 'RollerworksMultiUserBundle:UserBundle/Profile:show.html.twig',
        );

        foreach ($expected as $name => $resource) {
            $this->assertTemplateConfigEqual($resource, 'acme_user', 'profile', $name, $def);
        }
    }

    public function testRegistrationConfiguration()
    {
        $factory = new UserServicesFactory($this->containerBuilder);

        $config = array(
            array(
                'path' => '/',
                'user_class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\User',
                'services_prefix' => 'acme_user',
                'routes_prefix' => 'acme_user',

                'profile' => false,
                'resetting' => false,
                'change_password' => false,
                'registration' => array(),
            ),
        );

        $factory->create('acme', $config);

        $this->assertTrue($this->containerBuilder->hasAlias('acme_user.user_manager'));
        $this->assertTrue($this->containerBuilder->hasAlias('acme_user.group_manager'));
        $this->assertTrue($this->containerBuilder->hasDefinition('rollerworks_multi_user.user_system.acme'));

        $def = $this->containerBuilder->getDefinition('acme_user.user_manager.default');
        $this->assertEquals(new Reference('rollerworks_multi_user.acme_user.model_manager'), $def->getArgument(3));
        $this->assertEquals('%acme_user.model.user.class%', $def->getArgument(4));

        $def = $this->containerBuilder->getDefinition('rollerworks_multi_user.user_system.acme');
        $this->assertEquals('Rollerworks\Bundle\MultiUserBundle\Model\UserConfig', $def->getClass());
        $this->assertEquals(array(array('alias' => 'acme', 'class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\User', 'path' => '/', 'host' => null, 'db_driver' => 'orm')), $def->getTag('rollerworks_multi_user.user_system'));
        $this->assertUserConfigEquals($def, 'registering.confirmation.enabled', false, true);
        $this->assertTrue($def->isLazy());

        $expected = array(
            'class' => 'Rollerworks\Bundle\MultiUserBundle\Form\Type\RegistrationFormType',
            'type' => 'acme_user_registration',
            'name' => 'acme_user_registration_form',
            'validation_groups' => array('Registration', 'Default'),
        );

        $this->assertFormDefinitionEqual($expected, 'acme_user', 'registration', $def);

        $expected = array(
            'register' => 'RollerworksMultiUserBundle:UserBundle/Registration:register.html.twig',
            'check_email' => 'RollerworksMultiUserBundle:UserBundle/Registration:checkEmail.html.twig',
        );

        foreach ($expected as $name => $resource) {
            $this->assertTemplateConfigEqual($resource, 'acme_user', 'registration', $name, $def);
        }
    }

    public function testRegistrationConfigurationWithConfirmationEnabled()
    {
        $factory = new UserServicesFactory($this->containerBuilder);

        $config = array(
            array(
                'path' => '/',
                'user_class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\User',
                'services_prefix' => 'acme_user',
                'routes_prefix' => 'acme_user',

                'profile' => false,
                'resetting' => false,
                'change_password' => false,
                'registration' => array(
                    'confirmation' => array(
                        'enabled' => true,
                    ),
                ),
            ),
        );

        $factory->create('acme', $config);

        $this->assertTrue($this->containerBuilder->hasAlias('acme_user.user_manager'));
        $this->assertTrue($this->containerBuilder->hasAlias('acme_user.group_manager'));

        $this->assertTrue($this->containerBuilder->hasDefinition('rollerworks_multi_user.user_system.acme'));

        $def = $this->containerBuilder->getDefinition('acme_user.user_manager.default');
        $this->assertEquals(new Reference('rollerworks_multi_user.acme_user.model_manager'), $def->getArgument(3));
        $this->assertEquals('%acme_user.model.user.class%', $def->getArgument(4));

        $def = $this->containerBuilder->getDefinition('rollerworks_multi_user.user_system.acme');
        $this->assertEquals('Rollerworks\Bundle\MultiUserBundle\Model\UserConfig', $def->getClass());
        $this->assertEquals(array(array('alias' => 'acme', 'class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\User', 'path' => '/', 'host' => null, 'db_driver' => 'orm')), $def->getTag('rollerworks_multi_user.user_system'));
        $this->assertUserConfigEquals($def, 'registering.confirmation.enabled', true);
        $this->assertTrue($def->isLazy());

        $expected = array(
            'class' => 'Rollerworks\Bundle\MultiUserBundle\Form\Type\RegistrationFormType',
            'type' => 'acme_user_registration',
            'name' => 'acme_user_registration_form',
            'validation_groups' => array('Registration', 'Default'),
        );

        $this->assertFormDefinitionEqual($expected, 'acme_user', 'registration', $def);

        $expected = array(
            'register' => 'RollerworksMultiUserBundle:UserBundle/Registration:register.html.twig',
            'check_email' => 'RollerworksMultiUserBundle:UserBundle/Registration:checkEmail.html.twig',
        );

        foreach ($expected as $name => $resource) {
            $this->assertTemplateConfigEqual($resource, 'acme_user', 'registration', $name, $def);
        }
    }

    public function testResettingConfiguration()
    {
        $factory = new UserServicesFactory($this->containerBuilder);

        $config = array(
            array(
                'path' => '/',
                'user_class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\User',
                'services_prefix' => 'acme_user',
                'routes_prefix' => 'acme_user',

                'profile' => false,
                'registration' => false,
                'change_password' => false,
                'resetting' => array(),
            ),
        );

        $factory->create('acme', $config);

        $this->assertTrue($this->containerBuilder->hasAlias('acme_user.user_manager'));
        $this->assertTrue($this->containerBuilder->hasAlias('acme_user.group_manager'));

        $this->assertTrue($this->containerBuilder->hasDefinition('rollerworks_multi_user.user_system.acme'));

        $def = $this->containerBuilder->getDefinition('acme_user.user_manager.default');
        $this->assertEquals(new Reference('rollerworks_multi_user.acme_user.model_manager'), $def->getArgument(3));
        $this->assertEquals('%acme_user.model.user.class%', $def->getArgument(4));

        $def = $this->containerBuilder->getDefinition('rollerworks_multi_user.user_system.acme');
        $this->assertEquals('Rollerworks\Bundle\MultiUserBundle\Model\UserConfig', $def->getClass());
        $this->assertEquals(array(array('alias' => 'acme', 'class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\User', 'path' => '/', 'host' => null, 'db_driver' => 'orm')), $def->getTag('rollerworks_multi_user.user_system'));
        $this->assertTrue($def->isLazy());

        $found = false;
        foreach ($def->getMethodCalls() as $call) {
            if ('setConfig' !== $call[0]) {
                continue;
            }

            if ('resetting.token_ttl' === $call[1][0]) {
                $found = true;
                $this->assertEquals('%acme_user.resetting.token_ttl%', $call[1][1]);

                break;
            }
        }

        if (!$found) {
            $this->fail('Failed finding the tokenTtl configuration.');
        }

        $expected = array(
            'class' => 'Rollerworks\Bundle\MultiUserBundle\Form\Type\ResettingFormType',
            'type' => 'acme_user_resetting',
            'name' => 'acme_user_resetting_form',
            'validation_groups' => array('Resetting', 'Default'),
        );

        $this->assertFormDefinitionEqual($expected, 'acme_user', 'resetting', $def);

        $expected = array(
            'check_email' => 'RollerworksMultiUserBundle:UserBundle/Resetting:checkEmail.html.twig',
            'email' => 'RollerworksMultiUserBundle:UserBundle/Resetting:email.txt.twig',
            'password_already_requested' => 'RollerworksMultiUserBundle:UserBundle/Resetting:passwordAlreadyRequested.html.twig',
            'request' => 'RollerworksMultiUserBundle:UserBundle/Resetting:request.html.twig',
            'reset' => 'RollerworksMultiUserBundle:UserBundle/Resetting:reset.html.twig',
        );

        foreach ($expected as $name => $resource) {
            $this->assertTemplateConfigEqual($resource, 'acme_user', 'resetting', $name, $def);
        }
    }

    public function testChangePasswordConfiguration()
    {
        $factory = new UserServicesFactory($this->containerBuilder);

        $config = array(
            array(
                'path' => '/',
                'user_class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\User',
                'services_prefix' => 'acme_user',
                'routes_prefix' => 'acme_user',

                'profile' => false,
                'registration' => false,
                'resetting' => false,
                'change_password' => array(),
            ),
        );

        $factory->create('acme', $config);

        $this->assertTrue($this->containerBuilder->hasAlias('acme_user.user_manager'));
        $this->assertTrue($this->containerBuilder->hasAlias('acme_user.group_manager'));

        $this->assertTrue($this->containerBuilder->hasDefinition('rollerworks_multi_user.user_system.acme'));

        $def = $this->containerBuilder->getDefinition('acme_user.user_manager.default');
        $this->assertEquals(new Reference('rollerworks_multi_user.acme_user.model_manager'), $def->getArgument(3));
        $this->assertEquals('%acme_user.model.user.class%', $def->getArgument(4));

        $def = $this->containerBuilder->getDefinition('rollerworks_multi_user.user_system.acme');
        $this->assertEquals('Rollerworks\Bundle\MultiUserBundle\Model\UserConfig', $def->getClass());
        $this->assertEquals(array(array('alias' => 'acme', 'class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\User', 'path' => '/', 'host' => null, 'db_driver' => 'orm')), $def->getTag('rollerworks_multi_user.user_system'));
        $this->assertTrue($def->isLazy());

        $expected = array(
            'class' => 'Rollerworks\Bundle\MultiUserBundle\Form\Type\ChangePasswordFormType',
            'type' => 'acme_user_change_password',
            'name' => 'acme_user_change_password_form',
            'validation_groups' => array('ChangePassword', 'Default'),
        );

        $this->assertFormDefinitionEqual($expected, 'acme_user', 'change_password', $def);

        $expected = array(
            'change_password' => 'RollerworksMultiUserBundle:UserBundle/ChangePassword:changePassword.html.twig',
        );

        foreach ($expected as $name => $resource) {
            $this->assertTemplateConfigEqual($resource, 'acme_user', 'change_password', $name, $def);
        }
    }

    public function testGroupConfiguration()
    {
        $factory = new UserServicesFactory($this->containerBuilder);

        $config = array(
            array(
                'path' => '/',
                'user_class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\User',
                'services_prefix' => 'acme_user',
                'routes_prefix' => 'acme_user',

                'profile' => false,
                'registration' => false,
                'resetting' => false,
                'change_password' => false,

                'group' => array(
                    'group_class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\Group',
                ),
            ),
        );

        $factory->create('acme', $config);

        $this->assertTrue($this->containerBuilder->hasAlias('acme_user.user_manager'));
        $this->assertTrue($this->containerBuilder->hasAlias('acme_user.group_manager'));

        $this->assertTrue($this->containerBuilder->hasDefinition('rollerworks_multi_user.user_system.acme'));

        $def = $this->containerBuilder->getDefinition('acme_user.user_manager.default');
        $this->assertEquals(new Reference('rollerworks_multi_user.acme_user.model_manager'), $def->getArgument(3));
        $this->assertEquals('%acme_user.model.user.class%', $def->getArgument(4));

        $def = $this->containerBuilder->getDefinition('acme_user.group_manager.default');
        $this->assertEquals(new Reference('rollerworks_multi_user.acme_user.model_manager'), $def->getArgument(0));
        $this->assertEquals('%acme_user.model.group.class%', $def->getArgument(1));

        $def = $this->containerBuilder->getDefinition('rollerworks_multi_user.user_system.acme');
        $this->assertEquals('Rollerworks\Bundle\MultiUserBundle\Model\UserConfig', $def->getClass());
        $this->assertEquals(array(array('alias' => 'acme', 'class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\User', 'path' => '/', 'host' => null, 'db_driver' => 'orm')), $def->getTag('rollerworks_multi_user.user_system'));
        $this->assertTrue($def->isLazy());

        $expected = array(
            'class' => 'Rollerworks\Bundle\MultiUserBundle\Form\Type\GroupFormType',
            'type' => 'acme_user_change_password',
            'name' => 'acme_user_change_password_form',
            'validation_groups' => array('Registration', 'Default'),
        );

        $this->assertFormDefinitionEqual($expected, 'acme_user', 'group', $def);

        $expected = array(
            'edit' => 'RollerworksMultiUserBundle:UserBundle/Group:edit.html.twig',
            'list' => 'RollerworksMultiUserBundle:UserBundle/Group:list.html.twig',
            'new' => 'RollerworksMultiUserBundle:UserBundle/Group:new.html.twig',
            'show' => 'RollerworksMultiUserBundle:UserBundle/Group:show.html.twig',
        );

        foreach ($expected as $name => $resource) {
            $this->assertTemplateConfigEqual($resource, 'acme_user', 'group', $name, $def);
        }
    }

    public function testInvalidFormConfiguration()
    {
        $factory = new UserServicesFactory($this->containerBuilder);

        $config = array(
            array(
                'path' => '/',
                'user_class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\User',
                'services_prefix' => 'acme_user',
                'routes_prefix' => 'acme_user',

                'profile' => false,
                'resetting' => false,
                'change_password' => false,
                'registration' => array(
                    'form' => array(
                        'type' => 'fos_user_registration',
                        'class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\Form\Type\RegistrationFormType',
                    ),
                ),
            ),
        );

        $this->setExpectedException('RuntimeException', 'Form type "fos_user_registration" uses the "fos_user_" prefix with a custom class. Please overwrite the getName() method to return a unique name.');

        $factory->create('acme', $config);
    }

    public function testFormNameIsChangedWhenDefault()
    {
        $factory = new UserServicesFactory($this->containerBuilder);

        $config = array(
            array(
                'path' => '/',
                'user_class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\User',
                'services_prefix' => 'acme_user',
                'routes_prefix' => 'acme_user',

                'profile' => false,
                'resetting' => false,
                'change_password' => false,
                'registration' => array(
                    'form' => array(
                        'type' => 'acme_user_registration',
                        'class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\Form\Type\RegistrationFormType',
                    ),
                ),
            ),
        );

        $factory->create('acme', $config);

        $def = $this->containerBuilder->getDefinition('rollerworks_multi_user.user_system.acme');
        $expected = array(
            'class' => 'Rollerworks\Bundle\MultiUserBundle\Tests\Stub\Form\Type\RegistrationFormType',
            'type' => 'acme_user_registration',
            'name' => 'acme_user_registration_form',
            'validation_groups' => array('Registration', 'Default'),
        );

        $this->assertFormDefinitionEqual($expected, 'acme_user', 'registration', $def);
    }

    public static function provideModelManagerConfigs()
    {
        return array(
            array('orm', 'doctrine', 'Doctrine\ORM\EntityManager'),
            array('mongodb', 'doctrine_mongodb', 'Doctrine\ODM\MongoDB\DocumentManager'),
            array('couchdb', 'doctrine_couchdb', 'Doctrine\ODM\CouchDB\DocumentManager'),
        );
    }

    protected function assertUserConfigEquals(Definition $definition, $configKey, $expectedValue, $allowMissing = false)
    {
        $this->assertTrue($definition->hasMethodCall('setConfig'));

        $found = false;

        foreach ($definition->getMethodCalls() as $call) {
            if ('setConfig' !== $call[0] || $configKey !== $call[1][0]) {
                continue;
            }

            $this->assertEquals($expectedValue, $call[1][1]);
            $found = true;

            // Don't stop the loop the ensure no configuration is overwritten
        }

        if (!$allowMissing && !$found) {
            $this->fail(sprintf('No configuration was found with key "%s".', $configKey));
        }
    }

    protected function assertFormDefinitionEqual($expected, $servicePrefix, $type, Definition $userSystem = null)
    {
        $formDefinition = $this->containerBuilder->getDefinition(sprintf('%s.%s.form.type', $servicePrefix, $type));

        if (isset($expected['class'])) {
            $this->assertEquals($expected['class'], $formDefinition->getClass());
        }

        if (isset($expected['alias'])) {
            $this->assertEquals(array(array('alias' => $expected['alias'])), $formDefinition->getTag('form.type'));
        }

        if ('resetting' !== $type) {
            $formFactoryDefinition = $this->containerBuilder->getDefinition(sprintf('%s.%s.form.factory', $servicePrefix, $type));

            $this->assertEquals(sprintf('%%%s.%s.form.name%%', $servicePrefix, $type), $formFactoryDefinition->getArgument(1));
            $this->assertEquals(sprintf('%%%s.%s.form.type%%', $servicePrefix, $type), $formFactoryDefinition->getArgument(2));
            $this->assertEquals(sprintf('%%%s.%s.form.validation_groups%%', $servicePrefix, $type), $formFactoryDefinition->getArgument(3));
        }

        if ($userSystem) {
            foreach ($userSystem->getMethodCalls() as $call) {
                if ('setForm' !== $call[0]) {
                    continue;
                }

                $this->assertEquals(array(
                    $type,
                    sprintf('%%%s.%s.form.name%%', $servicePrefix, $type),
                    sprintf('%%%s.%s.form.type%%', $servicePrefix, $type),
                    sprintf('%%%s.%s.form.validation_groups%%', $servicePrefix, $type),
                ), $call[1]);
            }
        }
    }

    protected function assertTemplateConfigEqual($expected, $servicePrefix, $section, $name, Definition $userSystem = null)
    {
        $actual = $this->containerBuilder->getParameter(sprintf($servicePrefix.'.%s.%s.template', $section, $name));
        $this->assertEquals($expected, $actual);

        if ($userSystem) {
            $found = false;

            foreach ($userSystem->getMethodCalls() as $call) {
                if ('setTemplate' !== $call[0]) {
                    continue;
                }

                if ($call[1][0] === sprintf('%s.%s', $section, $name)) {
                    $found = true;
                    $this->assertEquals(sprintf('%%%s.%s.%s.template%%', $servicePrefix, $section, $name), $call[1][1]);

                    break;
                }
            }

            if (!$found) {
                $this->fail(sprintf('No template configuration found for: "%s.%s".', $section, $name));
            }
        }
    }

    protected function setUp()
    {
        $this->containerBuilder = new ContainerBuilder();
    }
}
