<?php
namespace Doctrine\Common\Tester;

use Doctrine\ORM\EntityManager;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

use Exception;

class FixtureManager
{
    protected $em;
    
    protected $fixtures = array();
    
    protected $executor;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->executor = new ORMExecutor($this->em, new ORMPurger($this->em));
    }
    
    public function registerFixture($name, AbstractFixture $fixture)
    {
        $this->fixtures[$name] = $fixture;
    }

    public function load($names)
    {
        is_array($names) || $names = array($names);

        $loader = new Loader();
        foreach ($names as $name) {
            if (false == isset($this->fixtures[$name])) {
                throw new Exception('A fixture with name `'.$name.'` was not registerd');
            }
            
            $loader->addFixture($this->fixtures[$name]);
        }
        
        $this->executor->execute($loader->getFixtures(), true);

        return $this;
    }
    
    public function clean()
    {
        $purger = new ORMPurger($this->em);
        $purger->purge();
        
        $this->executor = new ORMExecutor($this->em, $purger);
        
        $this->em->clear();

        return $this;
    }

    public function get($referance)
    {
        return $this->executor->getReferenceRepository()->getReference($referance);
    }
}