<?php
namespace Doctrine\Common\Tester;

use Doctrine\ORM\EntityManager;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\ReferenceRepository;

use Doctrine\Common\Tester\DataFixture\Executor\ORMExecutor;

class FixtureManager
{
    protected $em;
    
    protected $executor;

    protected $referenceRepository;

    public function __construct(EntityManager $em, ReferenceRepository $referenceRepository = null)
    {
        $this->em = $em;
        $this->executor = new ORMExecutor(
            $this->em,
            new ORMPurger($this->em),
            $referenceRepository
        );
    }

    /**
     * @return \Doctrine\Common\DataFixtures\ReferenceRepository
     */
    public function referenceRepository()
    {
        return $this->executor->getReferenceRepository();
    }

    public function get($reference)
    {
        return $this->executor->getReferenceRepository()->getReference($reference);
    }
}