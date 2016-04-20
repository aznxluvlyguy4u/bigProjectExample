<?php

namespace AppBundle\Service;

use AppBundle\Entity\Ram;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Proxies\__CG__\AppBundle\Entity\Ewe;

/**
 * Class Decrappifier.
 *
 * There is an issue with the discriminator maps for joined tables.
 * During deserialization to an object the data for the discriminator maps is lost.
 * In this class we are trying to rectify that problem by inserting the necessary
 * information in a hardcoded way, before the JSON is deserialize into an object.
 *
 * Doctrine hasn't fixed this is more than a few years, eventhough a pull request
 * with the solution has already been offered for more than 1,5 years.
 * And this is one of the core functionalities of Doctrine.
 * This is crap, thus the decrappifier.
 *
 * @package AppBundle\Service
 */
class Decrappifier
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

    public function denormalizeToObject($messageClassNameSpace, ArrayCollection $contentArray, $decrappify)
    {
        $json = $this->serializeToJSON($contentArray);

        if ($decrappify == true){ //decrappify if true
            $contentArrayInput = $contentArray;
        } else { //if false, don't decrappify
            $contentArrayInput = null;
        }

        $messageObject = $this->deserializeToObject($json, $messageClassNameSpace, $contentArrayInput);

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
        //TODO Switch on messageClassNameSpace = Declare/Request type
        $jsonInput = $json;

        if ($contentArray != null)
        {
            $animal = $contentArray['animal'];

            if($animal != null) {

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

                $jsonInput = $this->serializeToJSON($contentArray);

//                dump($contentArray);
//                die();
            }
        }
        $messageClassPathNameSpace = "AppBundle\Entity\\$messageClassNameSpace";

        $messageObject = $this->serializer->deserialize($jsonInput, $messageClassPathNameSpace, $this::jsonNamespace);

        return $messageObject;
    }
}