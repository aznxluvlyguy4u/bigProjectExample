<?php

namespace AppBundle\Service;

use AppBundle\Entity\Employee;
use AppBundle\Enumerator\AnimalType;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Animal;
use AppBundle\Enumerator\RequestType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Client;

/**
 * Class IRSerializer.
 *
 * There is an issue with the JMS Deserializing process, when we want to deserialize with an entity that has an
 * abstract base class with join table inheritance, the discriminator-type cannot be inferred/determined
 * during deserialization to an object, the data for the discriminator maps is lost.
 * This Serializer class adds the 'type' of the object to be deserialized explicitly to a
 * content array, which in turn can be serialized to JSON and then be deserialized to a given Entity
 * Though there is a 'Discriminator' annotation, see http://jmsyst.com/libs/serializer/master/reference/annotations#discriminator &
 * https://gist.github.com/h4cc/8313723 & for letting the (de)serializer know the base class, it does not work.
 * See issue: https://github.com/schmittjoh/JMSSerializerBundle/issues/292 & https://github.com/schmittjoh/JMSSerializerBundle/issues/299
 * Note that there has been effort of fixing the discriminator problem, a pull request
 * with a solution has already been opened, but up till now no communication or has been given on the given
 * pull request, see https://github.com/schmittjoh/serializer/pull/382
 * Therefore we need to customize the serializer to deal with this, thus this class will handle custom steps needed before the deserializing process.
 *
 * @package AppBundle\Service
 */
class IRSerializer implements IRSerializerInterface
{
    const DISCRIMINATOR_TYPE_NAMESPACE = "type";

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
        return $this->serializer->serialize($object, Constant::jsonNamespace);
    }

    /**
     * @param $json
     * @param $messageClassNameSpace
     * @return array|\JMS\Serializer\scalar|mixed|object
     */
    public function deserializeToObject($json, $messageClassNameSpace)
    {
        $messageClassPathNameSpace = "AppBundle\Entity\\$messageClassNameSpace";

        $messageObject = $this->serializer->deserialize($json, $messageClassPathNameSpace, Constant::jsonNamespace);

        return $messageObject;
    }

    /**
     * @param Animal $retrievedAnimal
     * @return array
     */
    function returnAnimalArray(Animal $retrievedAnimal)
    {
        //Parse to json
        $retrievedAnimalJson = $this->serializeToJSON($retrievedAnimal);
        //Parse json to content array to add additional 'animal type' property
        $retrievedAnimalContentArray = json_decode($retrievedAnimalJson, true);

        //Add animal type to content array
        $retrievedAnimalContentArray[$this::DISCRIMINATOR_TYPE_NAMESPACE] = $retrievedAnimal->getObjectType();

        // FIXME
        unset( $retrievedAnimalContentArray['arrivals']);
        unset( $retrievedAnimalContentArray['departures']);
        unset( $retrievedAnimalContentArray['imports']);
        unset( $retrievedAnimalContentArray['children']);

        return  $retrievedAnimalContentArray;
    }

    /**
     * @inheritdoc
     */
    function parseDeclarationDetail(ArrayCollection $contentArray)
    {
        // TODO: Implement parseDeclarationDetail() method.
        $declarationDetail = null;

        return $declarationDetail;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareAnimalFlag(ArrayCollection $contentArray)
    {
        // TODO: Implement parseDeclareAnimalFlag() method.
        $declareAnimalFlag = null;

        return $declareAnimalFlag;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareArrival(ArrayCollection $declareArrivalContentArray)
    {
        //Retrieve animal entity
        $retrievedAnimal = $this->entityGetter->retrieveAnimal($declareArrivalContentArray['animal']);

        //Add retrieved animal properties including type to initial animalContentArray
        $declareArrivalContentArray['animal'] =  $this->returnAnimalArray($retrievedAnimal);

        //denormalize the content to an object
        $json = $this->serializeToJSON($declareArrivalContentArray);
        $declareArrivalRequest = $this->deserializeToObject($json, RequestType::DECLARE_ARRIVAL_ENTITY);

        //Add retrieved animal to DeclareArrival
        $declareArrivalRequest->setAnimal($retrievedAnimal);

        return $declareArrivalRequest;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareBirth(ArrayCollection $declareBirthContentArray)
    {
        //Retrieve animal entity
        $retrievedAnimal = $this->entityGetter->retrieveAnimal($declareBirthContentArray['animal']);

        //Add retrieved animal properties including type to initial animalContentArray
        $declareBirthContentArray['animal'] = $this->returnAnimalArray($retrievedAnimal);

        //denormalize the content to an object
        $json = $this->serializeToJSON($declareBirthContentArray);
        $declareBirthRequest = $this->deserializeToObject($json, RequestType::DECLARE_BIRTH_ENTITY);

        //Add retrieved animal to DeclareBirth
        $declareBirthRequest->setAnimal($retrievedAnimal);

        return $declareBirthRequest;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareDepart(ArrayCollection $declareDepartContentArray)
    {
        //Retrieve animal entity
        $retrievedAnimal = $this->entityGetter->retrieveAnimal($declareDepartContentArray['animal']);

        //Add retrieved animal properties including type to initial animalContentArray
        $declareDepartContentArray['animal'] =  $this->returnAnimalArray($retrievedAnimal);

        //denormalize the content to an object
        $json = $this->serializeToJSON($declareDepartContentArray);
        $declareDepartRequest = $this->deserializeToObject($json, RequestType::DECLARE_DEPART_ENTITY);

        //Add retrieved animal to DeclareArrival
        $declareDepartRequest->setAnimal($retrievedAnimal);

        return $declareDepartRequest;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareEartagsTransfer(ArrayCollection $contentArray)
    {
        // TODO: Implement parseDeclareEartagsTransfer() method.
        $declareEartagsTransfer = null;

        return $declareEartagsTransfer;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareLoss(ArrayCollection $contentArray)
    {
        // TODO: Implement parseDeclareLoss() method.
        $declareLoss = null;

        return $declareLoss;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareExport(ArrayCollection $contentArray)
    {
        // TODO: Implement parseDeclareExport() method.
        $declareExport = null;

        return $declareExport;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareImport(ArrayCollection $declareImportContentArray)
    {
        //Retrieve animal entity
        $retrievedAnimal = $this->entityGetter->retrieveAnimal($declareImportContentArray['animal']);

        //Add retrieved animal properties including type to initial animalContentArray
        $declareImportContentArray['animal'] =  $this->returnAnimalArray($retrievedAnimal);

        //denormalize the content to an object
        $json = $this->serializeToJSON($declareImportContentArray);
        $declareImportRequest = $this->deserializeToObject($json, RequestType::DECLARE_IMPORT_ENTITY);

        //Add retrieved animal to DeclareArrival
        $declareImportRequest->setAnimal($retrievedAnimal);

        return $declareImportRequest;
    }

    /**
     * @inheritdoc
     */
    function parseRetrieveEartags(ArrayCollection $contentArray)
    {
        // TODO: Implement parseRetrieveEartags() method.
        $retrieveEartags = null;

        return $retrieveEartags;
    }

    /**
     * @inheritdoc
     */
    function parseRevokeDeclaration(ArrayCollection $contentArray)
    {
        // TODO: Implement parseRevokeDeclaration() method.
        $revokeDeclaration = null;

        return $revokeDeclaration;
    }
}