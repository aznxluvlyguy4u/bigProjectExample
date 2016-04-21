<?php

namespace AppBundle\Service;

use AppBundle\Enumerator\AnimalType;
use AppBundle\Entity\Ram;
use AppBundle\Enumerator\MessageClass;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Proxies\__CG__\AppBundle\Entity\Ewe;

/**
 * Class IRSerializer.
 *
 * There is an issue with the discriminator maps for joined tables.
 * During deserialization to an object the data for the discriminator maps is lost.
 * In this class we are trying to rectify that problem by inserting the necessary
 * information in a hardcoded way, before the JSON is deserialize into an object.
 *
 * Doctrine hasn't fixed this in more than a few years, eventhough a pull request
 * with the solution has already been offered for more than 1,5 years.
 * And this is a core functionality of Doctrine.
 *
 * Therefore we need to customize the serializer to deal with this.
 *
 * @package AppBundle\Service
 */
class IRSerializer implements IRSerializerInterface
{
    const jsonNamespace  = 'json';

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var \JMS\Serializer\Serializer
     */
    private $serializer;

    /**
     * @var \AppBundle\Service\EntityGetter
     */
    private $entityGetter;

    public function __construct($serializer, $entityManager, $entityGetter)
    {
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->entityGetter = $entityGetter;
    }

    /**
     * @param $object
     * @return mixed|string
     */
    public function serializeToJSON($object)
    {
        return $this->serializer->serialize($object, $this::jsonNamespace);
    }

    /**
     * @param $json
     * @param $messageClassNameSpace
     * @return array|\JMS\Serializer\scalar|mixed|object
     */
    public function deserializeToObject($json, $messageClassNameSpace)
    {
        $messageClassPathNameSpace = "AppBundle\Entity\\$messageClassNameSpace";

        $messageObject = $this->serializer->deserialize($json, $messageClassPathNameSpace, $this::jsonNamespace);

        return $messageObject;
    }


    function parseDeclarationDetail(ArrayCollection $contentArray)
    {
        // TODO: Implement parseDeclarationDetail() method.
        $messageObject = null;

        return $messageObject;
    }


    function parseDeclareAnimalFlag(ArrayCollection $contentArray)
    {
        // TODO: Implement parseDeclareAnimalFlag() method.
        $messageObject = null;

        return $messageObject;
    }


    function parseDeclareArrival(ArrayCollection $contentArray)
    {
        $animal = $contentArray['animal'];
        $retrievedAnimal = $this->entityGetter->retrieveAnimal($animal);

        $animalGender = null;

        if ($retrievedAnimal instanceof Ram) {
            $animalGender = "Ram";
        } else if ($retrievedAnimal instanceof Ewe) {
            $animalGender = "Ewe";
        }

        $newAnimalDetails = array_merge($animal, array('type' => $animalGender));

        $contentArray['animal'] = $newAnimalDetails;

        //denormalize the content to an object
        $json = $this->serializeToJSON($contentArray);
        $messageObject = $this->deserializeToObject($json, MessageClass::DeclareArrival);

        return $messageObject;
    }


    function parseDeclareBirth(ArrayCollection $contentArray)
    {
        // TODO: Implement parseDeclareBirth() method.
        $messageObject = null;

        return $messageObject;
    }


    function parseDeclareDepart(ArrayCollection $contentArray)
    {
        // TODO: Implement parseDeclareDepart() method.
        $messageObject = null;

        return $messageObject;
    }


    function parseDeclareEartagsTransfer(ArrayCollection $contentArray)
    {
        // TODO: Implement parseDeclareEartagsTransfer() method.
        $messageObject = null;

        return $messageObject;
    }


    function parseDeclareLoss(ArrayCollection $contentArray)
    {
        // TODO: Implement parseDeclareLoss() method.
        $messageObject = null;

        return $messageObject;
    }


    function parseDeclareExport(ArrayCollection $contentArray)
    {
        // TODO: Implement parseDeclareExport() method.
        $messageObject = null;

        return $messageObject;
    }


    function parseDeclareImport(ArrayCollection $contentArray)
    {
        // TODO: Implement parseDeclareImport() method.
        $messageObject = null;

        return $messageObject;
    }


    function parseRetrieveEartags(ArrayCollection $contentArray)
    {
        // TODO: Implement parseRetrieveEartags() method.
        $messageObject = null;

        return $messageObject;
    }


    function parseRevokeDeclaration(ArrayCollection $contentArray)
    {
        // TODO: Implement parseRevokeDeclaration() method.
        $messageObject = null;

        return $messageObject;
    }
}