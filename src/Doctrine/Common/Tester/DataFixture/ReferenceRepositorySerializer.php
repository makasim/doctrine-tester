<?php
namespace Doctrine\Common\Tester\DataFixture;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManager;

use Doctrine\ORM\Proxy\Proxy;

class ReferenceRepositorySerializer
{
    private $manager;

    public function __construct(EntityManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param \Doctrine\Common\DataFixtures\ReferenceRepository $referenceRepository
     *
     * @return string
     */
    public function serialize(ReferenceRepository $referenceRepository)
    {
        $toSerialize = array();
        foreach($referenceRepository->getReferences() as $name => $reference) {
            $reference = $referenceRepository->getReference($name);

            $toSerialize[$name]['identifier'] = $this->manager->getUnitOfWork()->getEntityIdentifier($reference);

            if ($reference instanceof Proxy) {
                $ro = new \ReflectionObject($reference);
                $toSerialize[$name]['class'] = $ro->getParentClass()->getName();
            } else {
                $toSerialize[$name]['class'] =  get_class($reference);
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

    public function fill(ReferenceRepository $referenceRepository, array $unserializedData)
    {
        foreach ($unserializedData as $name => $data) {
            $reference = $this->manager->getReference(
                $data['class'],
                $data['identifier']
            );

            $referenceRepository->setReference($name, $reference);
        }

        return $referenceRepository;
    }
}