<?php
namespace Doctrine\Common\Tester\DataFixture;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManager;

use Doctrine\ORM\Proxy\Proxy;

class ReferanceRepositorySerializer
{
    private $manager;

    public function __construct(EntityManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param \Doctrine\Common\DataFixtures\ReferenceRepository $referanceRepository
     *
     * @return string
     */
    public function serialize(ReferenceRepository $referanceRepository)
    {
        $toSerialize = array();
        foreach($referanceRepository->getReferences() as $name => $referance) {
            $referance = $referanceRepository->getReference($name);

            $toSerialize[$name]['identifier'] = $this->manager->getUnitOfWork()->getEntityIdentifier($referance);

            if ($referance instanceof Proxy) {
                $ro = new \ReflectionObject($referance);
                $toSerialize[$name]['class'] = $ro->getParentClass()->getName();
            } else {
                $toSerialize[$name]['class'] =  get_class($referance);
            }
        }

        return serialize($toSerialize);
    }

    /**
     * @param $serialized
     *
     * @return \Doctrine\Common\DataFixtures\ReferenceRepository
     */
    public function unserialize($serialized)
    {
        return $this->fill(
            new ReferenceRepository($this->manager),
            unserialize($serialized)
        );
    }

    public function fill(ReferenceRepository $referanceRepository, array $unserializedData)
    {
        foreach ($unserializedData as $name => $data) {
            $reference = $this->manager->getReference(
                $data['class'],
                $data['identifier']
            );

            $referanceRepository->setReference($name, $reference);
        }

        return $referanceRepository;
    }
}