<?php

/*
 * This file is part of the RollerworksMultiUserBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\MultiUserBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class WebTestCaseFunctional extends WebTestCase
{
    private static $dbIsSetUp = false;

    protected static function createKernel(array $options = array())
    {
        return new AppKernel(
            isset($options['config']) ? $options['config'] : 'default.yml'
        );
    }

    protected function tearDown()
    {
        parent::tearDown();

        self::$dbIsSetUp = false;
    }

    /**
     * @param array $options
     * @param array $server
     *
     * @return \Symfony\Bundle\FrameworkBundle\Client
     */
    protected static function newClient(array $options = array(), array $server = array())
    {
        $client = static::createClient(array_merge(array('config' => 'default.yml'), $options), $server);

        if (false === self::$dbIsSetUp) {
            $em = $client->getContainer()->get('doctrine.orm.default_entity_manager');

            // Initialize the database
            $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
            $schemaTool->dropDatabase();
            $schemaTool->updateSchema($em->getMetadataFactory()->getAllMetadata(), false);

            self::$dbIsSetUp = true;
        }

        return $client;
    }

    protected function createUser($userSys, $username, $email = 'test@example.com', $password = 'very-not-secure')
    {
        $container = static::$kernel->getContainer();
        $userManager = $container->get($userSys.'.user_manager');

        $user = $userManager->createUser();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setPlainPassword($password);
        $user->setEnabled(true);

        if (false !== strpos($userSys, 'admin')) {
            $user->addRole('ROLE_ADMIN');
        }

        $userManager->updateUser($user);

        return $user;
    }
}
