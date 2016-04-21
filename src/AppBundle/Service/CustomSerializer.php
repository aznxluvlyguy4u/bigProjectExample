<?php

namespace AppBundle\Service;

use AppBundle\Entity\Ram;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Proxies\__CG__\AppBundle\Entity\Ewe;

/**
 * Class CustomSerializer.
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
class CustomSerializer
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

    public function __construct($serializer, $entityManager)
    {
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
    }

    /**
     * When using serializeToJSON and then deserializeToObject, there
     * is an unnecessary serialization step in the process.
     * It is the first serializeToJSON.
     * To prevent that, this method was created.
     *
     * @param $messageClassNameSpace
     * @param ArrayCollection $contentArray
     * @return array|\JMS\Serializer\scalar|mixed|object
     */
    public function denormalizeToObject($messageClassNameSpace, ArrayCollection $contentArray)
    {
        $jsonInput = $this->fixArrays($messageClassNameSpace, $contentArray);

        $messageClassPathNameSpace = "AppBundle\Entity\\$messageClassNameSpace";

        $messageObject = $this->serializer->deserialize($jsonInput, $messageClassPathNameSpace, $this::jsonNamespace);

        return $messageObject;
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
     * @param $entity
     * @return array|\JMS\Serializer\scalar|mixed|object
     */
    public function deserializeToObject($json, $messageClassNameSpace, ArrayCollection $contentArray = null)
    {
        $jsonInput = $json;

        if ($contentArray != null)
        {
            $jsonInput = $this->fixArrays($messageClassNameSpace, $contentArray);
        }

        $messageClassPathNameSpace = "AppBundle\Entity\\$messageClassNameSpace";

        $messageObject = $this->serializer->deserialize($jsonInput, $messageClassPathNameSpace, $this::jsonNamespace);

        return $messageObject;
    }





    private function fixArrays($messageClassNameSpace, ArrayCollection $contentArray)
    {
        $jsonInput = null;
        switch($messageClassNameSpace) {
            case 'DeclareArrival':
                $contentArray = $this->fixAnimalArray($contentArray);
                $jsonInput = $this->serializeToJSON($contentArray);
                break;

            case '':
                break;

            default:
                //No arrays need to be fixed
                $jsonInput = $this->serializeToJSON($contentArray);
                break;
        }

        return $jsonInput;
    }

    private function fixAnimalArray(ArrayCollection $contentArray)
    {
        $animal = $contentArray['animal'];

        //TODO Filter op pedigree of uln!
        $ulnNumber = $animal['uln_number'];
        $ulnCountryCode = $animal['uln_country_code'];

        $filterArray = array("ulnNumber" => $ulnNumber, "ulnCountryCode" => $ulnCountryCode);
        $retrievedAnimal = $this->entityManager->getRepository('AppBundle:Animal')->findOneBy($filterArray);

        $animalType = null;

        if ($retrievedAnimal instanceof Ram) {
            $animalType = "Ram";
        } else if ($retrievedAnimal instanceof Ewe) {
            $animalType = "Ewe";
        }

        $newAnimalDetails = array_merge($animal, array('type' => $animalType, 'animalType' => 3));
        //$contentArray->set('animal', $newAnimalDetails);
        $contentArray['animal'] = $newAnimalDetails;

        return $contentArray;
    }
}