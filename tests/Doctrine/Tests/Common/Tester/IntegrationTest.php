<?php
namespace Doctrine\Tests;

use Doctrine\Common\Tester\Tester;
use Doctrine\Tests\Common\Tester\Fixture\User;
use Doctrine\Tests\Common\Tester\Fixture\UserProfile;

class UserProfileTest extends \PHPUnit_Framework_TestCase
{
    public function testFlush()
    {
        $db = new Tester();
        $db
            ->useSqlite()
            ->registerBasepath(__DIR__ . '/../../../../')
            ->registerAnnotationMapping('Doctrine/Tests/Common/Tester/Fixture', 'Doctrine\\Tests\\Common\\Tester\\Fixture')
            ->registerEntities(array('Doctrine\Tests\Common\Tester\Fixture\UserProfile'))
        ;

        /**
         *
         * creates a sqlite in memory database and one table user_profiles in it
         */
        $db->rebuild();

        $u = new User();
        $u->setEmail('fpp@example.com');

        $up = new UserProfile($u);

        $up->setFirstName('foo');
        $up->setLastName('bar');

        $db->em()->persist($up);
        $db->em()->flush();

        $this->assertGreaterThan(0, $up->getId());
        $this->assertNull($u->getId());
    }

    public function testFindBy()
    {
        if (false == isset($_SERVER['mysql_db_name'], $_SERVER['mysql_db_username'], $_SERVER['mysql_db_password'])) {
            $this->markTestIncomplete('Some options required to run the test, check your phpunit.xml');
        }

        $db = new Tester();
        $db
            ->useMysql($_SERVER['mysql_db_name'], $_SERVER['mysql_db_username'], $_SERVER['mysql_db_password'])
            ->registerBasepath(__DIR__ . '/../../../../')
            ->registerAnnotationMapping('Doctrine/Tests/Common/Tester/Fixture', 'Doctrine\\Tests\\Common\\Tester\\Fixture\\UserProfile')
            ->registerEntities(array('Doctrine\Tests\Common\Tester\Fixture\UserProfile'))
        ;

        /**
         *
         * creates a mysql database and one table user_profiles in it
         */
        $db->rebuild();

        $u = new User();
        $u->setEmail('fpp@example.com');

        $up = new UserProfile($u);

        $up->setFirstName('foo');
        $up->setLastName('bar');

        $db->em()->persist($up);
        $db->em()->flush();

        $findedUserProfile = $db->em()->getRepository('Doctrine\Tests\Common\Tester\Fixture\UserProfile')->findOneBy(array('firstName' => 'foo'));

        $this->assertSame($up, $findedUserProfile);
    }

    public function testFindByAssosiation()
    {
        $db = new Tester();
        $db
            ->useSqlite()
            ->registerBasepath(__DIR__ . '/../../../../')
            ->registerAnnotationMapping('Doctrine/Tests/Common/Tester/Fixture', 'Doctrine\\Tests\\Common\\Tester\\Fixture')
            ->registerEntities(array(
                'Doctrine\Tests\Common\Tester\Fixture\UserProfile',
                'Doctrine\Tests\Common\Tester\Fixture\User',
            ))
        ;

        /**
         *
         * creates a sqlite in memory database and two tables users and user_profiles with foreign_keys
         */
        $db->rebuild();

        $u = new User();
        $u->setEmail('fpp@example.com');

        $up = new UserProfile($u);

        $up->setFirstName('foo');
        $up->setLastName('bar');

        $db->em()->persist($u);
        $db->em()->persist($up);
        $db->em()->flush();

        $findedUserProfile = $db->em()->getRepository('Doctrine\Tests\Common\Tester\Fixture\UserProfile')->findOneBy(array('user' => $u));

        $this->assertSame($up, $findedUserProfile);
    }
}