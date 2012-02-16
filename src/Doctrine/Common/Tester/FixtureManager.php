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

    protected $referanceRepository;

    public function __construct(EntityManager $em, ReferenceRepository $referanceRepository = null)
    {
        $this->em = $em;
        $this->executor = new ORMExecutor(
            $this->em,
            new ORMPurger($this->em),
            $referanceRepository
        );
    }

    /**
     * @return \Doctrine\Common\DataFixtures\ReferenceRepository
     */
    public function referanceRepository()
    {
        return $this->executor->getReferenceRepository();
    }

    public function get($referance)
    {
        return $this->executor->getReferenceRepository()->getReference($referance);
    }
}