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


        return  $retrievedAnimalContentArray;
    }

    /**
     * @inheritdoc
     */
    function parseDeclarationDetail(ArrayCollection $contentArray, $isEditMessage)
    {
        // TODO: Implement parseDeclarationDetail() method.
        $declarationDetail = null;

        return $declarationDetail;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareAnimalFlag(ArrayCollection $contentArray, $isEditMessage)
    {
        // TODO: Implement parseDeclareAnimalFlag() method.
        $declareAnimalFlag = null;

        return $declareAnimalFlag;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareArrival(ArrayCollection $declareArrivalContentArray, $isEditMessage)
    {
        //Retrieve animal entity
        $retrievedAnimal = $this->entityGetter->retrieveAnimal($declareArrivalContentArray);

        //Add retrieved animal properties including type to initial animalContentArray
        $declareArrivalContentArray->set(Constant::ANIMAL_NAMESPACE, $this->returnAnimalArray($retrievedAnimal));

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
    function parseDeclareBirth(ArrayCollection $declareBirthContentArray, $isEditMessage)
    {
        $declareBirthRequest = null;

        //If it's not an edit message, just retrieve animal and set animal, otherwise don't setup
        //so the updated animal details will be persisted
        if($isEditMessage) {
            //denormalize the content to an object
            $animal = $declareBirthContentArray->get(Constant::ANIMAL_NAMESPACE);
            $animalObject = null;

            //Find registered tag, through given ulnCountryCode & ulnNumber, so we can assign the tag to this animal
            $tag = $this->entityGetter->retrieveTag($animal[Constant::ULN_COUNTRY_CODE_NAMESPACE], $animal[Constant::ULN_NAMESPACE]);

            if($tag == null){
                return null;
            }

            //Create animal-type based on gender
            if(array_key_exists(Constant::GENDER_NAMESPACE, $animal)) {
                switch($animal[Constant::GENDER_NAMESPACE]){
                    case AnimalType::FEMALE:
                        $animalObject = new Ewe();
                        break;
                    case AnimalType::MALE:
                        $animalObject = new Ram();
                        break;
                    default:
                        $animalObject = new Neuter();
                        break;
                }

            } else {
                $animalObject = new Neuter();
            }

            //Assign tag to this animal
            $animalObject->setAssignedTag($tag);

            //Convert to array pass animal into declareBirthContentArray
            $animalJson = $this->serializeToJSON($animalObject);
            $animalContentArray = json_decode($animalJson, true);
            $declareBirthContentArray->set(Constant::ANIMAL_NAMESPACE, $animalContentArray);
            $json = $this->serializeToJSON($declareBirthContentArray->toArray());
            $declareBirthRequest = $this->deserializeToObject($json, RequestType::DECLARE_BIRTH_ENTITY);

            return $declareBirthRequest;
        }

        //Retrieve animal entity
        $retrievedAnimal = $this->entityGetter->retrieveAnimal($declareBirthContentArray);

        //Add retrieved animal properties including type to initial animalContentArray
        $declareBirthContentArray->set(Constant::ANIMAL_NAMESPACE, $this->returnAnimalArray($retrievedAnimal));

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
    function parseDeclareDepart(ArrayCollection $declareDepartContentArray, $isEditMessage)
    {
        //Retrieve animal entity
        $retrievedAnimal = $this->entityGetter->retrieveAnimal($declareDepartContentArray);

        //Add retrieved animal properties including type to initial animalContentArray
        $declareDepartContentArray->set(Constant::ANIMAL_NAMESPACE, $this->returnAnimalArray($retrievedAnimal));

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
    function parseDeclareEartagsTransfer(ArrayCollection $contentArray, $isEditMessage)
    {
        // TODO: Implement parseDeclareEartagsTransfer() method.
        $declareEartagsTransfer = null;

        return $declareEartagsTransfer;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareLoss(ArrayCollection $contentArray, $isEditMessage)
    {
        // TODO: Implement parseDeclareLoss() method.
        $declareLoss = null;

        return $declareLoss;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareExport(ArrayCollection $declareExportContentArray, $isEditMessage)
    {
        $retrievedAnimal = $this->entityGetter->retrieveAnimal($declareExportContentArray);

        //Add retrieved animal properties including type to initial animalContentArray
        $declareExportContentArray->set(Constant::ANIMAL_NAMESPACE, $this->returnAnimalArray($retrievedAnimal));

        //denormalize the content to an object
        $json = $this->serializeToJSON($declareExportContentArray);
        $declareExportRequest = $this->deserializeToObject($json, RequestType::DECLARE_EXPORT_ENTITY);

        //Add retrieved animal to DeclareArrival
        $declareExportRequest->setAnimal($retrievedAnimal);

        return $declareExportRequest;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareImport(ArrayCollection $declareImportContentArray, $isEditMessage)
    {
        //Retrieve animal entity
        $retrievedAnimal = $this->entityGetter->retrieveAnimal($declareImportContentArray);

        //Add retrieved animal properties including type to initial animalContentArray
        $declareImportContentArray->set(Constant::ANIMAL_NAMESPACE, $this->returnAnimalArray($retrievedAnimal));

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
    function parseRetrieveEartags(ArrayCollection $contentArray, $isEditMessage)
    {
        // TODO: Implement parseRetrieveEartags() method.
        $retrieveEartags = null;

        return $retrieveEartags;
    }

    /**
     * @inheritdoc
     */
    function parseRevokeDeclaration(ArrayCollection $contentArray, $isEditMessage)
    {
        // TODO: Implement parseRevokeDeclaration() method.
        $revokeDeclaration = null;

        return $revokeDeclaration;
    }
}