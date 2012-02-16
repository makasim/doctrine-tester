<?php
namespace Doctrine\Common\Tester\DataFixture\Executor;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor as BaseORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;

class ORMExecutor extends BaseORMExecutor
{
    public function __construct(EntityManager $em, ORMPurger $purger = null, ReferenceRepository $referenceRepository = null)
    {
        parent::__construct($em, $purger);

        // there should be a way to set it from outside
        if ($referenceRepository) {
            $this->referenceRepository = $referenceRepository;
        }
    }
}