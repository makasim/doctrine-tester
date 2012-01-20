<?php

namespace Doctrine\Common\Tester;

use Symfony\Component\ClassLoader\UniversalClassLoader;

use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\SchemaTool;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\IndexedReader;
use Doctrine\Common\Annotations\CachedReader;

use Doctrine\Common\Annotations\AnnotationRegistry;

use Doctrine\DBAL\Types\Type;

class Tester
{    
    protected $annotationMapping = array();
    
    protected $xmlMapping = array();

    protected $em;
    
    protected $entitiesName = array();
    
    protected $fixtures = array();
    
    protected $fixtureManager = array();
    
    protected $dbalTypes = array();
    
    protected $basepathes = array();
    
    protected $connectionParams = array();

    protected $mappingTypes = array();
    
    public function __construct()
    {
        //assume the lib in vendor/doctrine-tester dir
        //$this->registerBasepath($srcPath = __DIR__ . '/../../');
        
        $this->useSqlite();
    }
    
    public function registerBasepath($path)
    {
        $this->basepathes[] = realpath($path);
        
        return $this;
    }
    
    public function registerFixtures(array $fixtures)
    {
        $this->fixtures = $fixtures;
        
        return $this;
    }
    
    public function registerDBALType($name, $class)
    {
        $this->dbalTypes[$name] = $class;
        
        return $this;
    }
    
    public function registerEntities(array $entitiesName)
    {
        $this->entitiesName = $entitiesName;
        
        return $this;
    }
    
    public function registerAnnotationMapping($path, $namespace)
    {
        $this->annotationMapping[$namespace] = $path;
        
        return $this;
    }
    
    public function registerXmlMapping($path, $namespace)
    {
        $this->xmlMapping[$namespace] = $path;
        
        return $this;
    }

    public function registerMappingType($dbType, $doctrineType)
    {
        $this->mappingTypes[$dbType] = $doctrineType;

        return $this;
    }
    
    /**
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public function em()
    {
        if (false == $this->em) {
            $this->em = $this->initEm();
        }
        
        return $this->em;
    }
    
    protected function initEm()
    {
        $conf = new Configuration();
        $conf->setAutoGenerateProxyClasses(true);
        $conf->setProxyDir(\sys_get_temp_dir());
        $conf->setProxyNamespace('Proxies');
        $conf->setMetadataDriverImpl($this->initMetadataDriverImpl());
        $conf->setQueryCacheImpl(new ArrayCache());
        $conf->setMetadataCacheImpl(new ArrayCache());
        $conf->setClassMetadataFactoryName('Doctrine\Common\Tester\PartialClassMetadataFactory');
        
        foreach ($this->dbalTypes as $name => $class) {
            if (false == Type::hasType($name)) {
                Type::addType($name, $class);
            }
        }

        $entityManager = EntityManager::create($this->connectionParams, $conf);
        if (!empty($this->mappingTypes)) {
            $platform = $entityManager->getConnection()->getDatabasePlatform();
            foreach ($this->mappingTypes as $dbType => $doctrineType) {
                $platform->registerDoctrineTypeMapping($dbType, $doctrineType);
            }
        }


        if ($this->entitiesName) {
            $entityManager->getMetadataFactory()->setUseOnlyClasses($this->entitiesName);
        }

        return $entityManager;
    }
    
    public function useSqlite()
    {
        $this->connectionParams = array('driver' => 'pdo_sqlite', 'path' => ':memory:');
        
        return $this;
    }
    
    public function useEm(EntityManager $em)
    {
        $this->em = $em;
        
        return $this;
    }
    
    public function useMysql($dbname, $user, $pass)
    {
        $this->connectionParams = array(
            'driver' => 'pdo_mysql',
            'dbname' => $dbname,
            'user' => $user,
            'password' => $pass,
            'host' => 'localhost',
            'charset' => "utf8");
        
        return $this;
    }
    
    protected function initMetadataDriverImpl()
    {
        $chainDriver = new DriverChain();
        
        if ($this->annotationMapping) {
            $rc = new \ReflectionClass('\Doctrine\ORM\Mapping\Driver\AnnotationDriver');
            AnnotationRegistry::registerFile(dirname($rc->getFileName()) . '/DoctrineAnnotations.php');

            $pathes = array();
            foreach ($this->annotationMapping as $path) {
                $pathes[] = $this->guessPath($path);
            }

            $annotationDriver = new AnnotationDriver(new AnnotationReader(), $pathes);
            foreach ($this->annotationMapping as $namespace => $path) {
                $chainDriver->addDriver($annotationDriver, $namespace);
            }
        }

        if ($this->xmlMapping) {
            $pathes = array();
            foreach ($this->xmlMapping as $namespace => $path) {
                $pathes[$this->guessPath($path)] = $namespace;
            }

            $xmlDriver = new SimplifiedXmlDriver($pathes);
            $xmlDriver->setGlobalBasename('mapping');
            foreach ($this->xmlMapping as $namespace => $path) {
                $chainDriver->addDriver($xmlDriver, $namespace);
            }
        }
        
        return $chainDriver;
    }


    
    protected function initFixtureManager()
    {
        $fixtureManager = new FixtureManager($this->em());
        
        foreach ($this->fixtures as $name => $fixture) {
            $fixtureManager->registerFixture($name, $fixture);
        }
        
        return $fixtureManager;
    }
    
    protected function fixtureManager()
    {
        if (false == $this->fixtureManager) {
            $this->fixtureManager = $this->initFixtureManager();
        }
        
        return $this->fixtureManager;
    }
    
    protected function guessPath($originalPath)
    {
        if (is_dir($originalPath)) return $originalPath;
        
        foreach ($this->basepathes as $basepath) {
            $path = $basepath . '/' . $originalPath;
            
            if (is_dir($path)) return $path;
        }
        
        throw new \Exception('Can guess the path `'.$originalPath.'` and basepathes: `'.implode('`, `', $this->basepathes).'`');
    }
    
    public function rebuild()
    {
        $em = $this->em();

        $classes = $em->getMetadataFactory()->getAllMetadata();

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($classes);
        
        $em->clear();
        
        return $this;
    }
    
    public function load(array $fixtures)
    {
        $this->fixtureManager()->load($fixtures);
        
        return $this;
    }
    
    public function clean()
    {
        $this->fixtureManager()->clean();
        
        return $this;
    }

    /**
         *
         * @return Doctrine\ORM\EntityRepository
         */
    public function repository($entity)
    {
        is_object($entity) && $entity = get_class($entity);

        return $this->em()->getRepository($entity);
    }
    
    public function get($referance)
    {
        return $this->fixtureManager()->get($referance);
    }
}