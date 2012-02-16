<?php

namespace Doctrine\Common\Tester\DataFixture\Purger;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

class MysqlORMPurger extends ORMPurger
{
    private $em;

    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;
        parent::setEntityManager($em);
    }

    public function purge()
    {
        $this->em->getConnection()->executeUpdate("SET foreign_key_checks = 0;");

        parent::purge();

        $this->em->getConnection()->executeUpdate("SET foreign_key_checks = 1;");
    }
}