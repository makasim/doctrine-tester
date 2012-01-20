<?php
namespace Doctrine\Common\Tester;

use Doctrine\ORM\Mapping\ClassMetadataFactory;

class PartialClassMetadataFactory extends ClassMetadataFactory
{
    protected $useOnlyClasses = array();

    public function setUseOnlyClasses(array $classes)
    {
        $this->useOnlyClasses = $classes;
    }

    public function getMetadataFor($className)
    {
        $metadata = parent::getMetadataFor($className);
        if (false == $this->useOnlyClasses) {
            return $metadata;
        }

        $this->removeNotUsedAssociations($metadata);

        return $metadata;
    }

    public function getAllMetadata()
    {
        $metadatas = parent::getAllMetadata();
        if (false == $this->useOnlyClasses) {
            return $metadatas;
        }

        foreach($metadatas as &$metadata) {
            if (false === array_search($metadata->name, $this->useOnlyClasses, false)) {
                $metadata = null;
                continue;
            }


            $this->removeNotUsedAssociations($metadata);
        };

        return array_filter($metadatas);
    }

    protected function removeNotUsedAssociations($metadata)
    {
        foreach ($metadata->associationMappings as $fieldName => $mapping) {
            if (false === array_search($mapping['targetEntity'], $this->useOnlyClasses, false)) {
                unset($metadata->associationMappings[$fieldName]);
                unset($metadata->reflFields[$fieldName]);
            }
        }
    }
}