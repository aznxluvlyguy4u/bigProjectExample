<?php

namespace AppBundle\Service;

use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Entity\Employee;
use AppBundle\Entity\RetrieveTags;
use AppBundle\Entity\RevokeDeclaration;
use AppBundle\Entity\RetrieveAnimals;
use AppBundle\Enumerator\AnimalType;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Animal;
use AppBundle\Enumerator\RequestType;
use AppBundle\Enumerator\TagStateType;
use AppBundle\Enumerator\TagType;
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

        return  $retrievedAnimalContentArray;
    }

    /**
     * @inheritdoc
     */
    function parseDeclarationDetail(ArrayCollection $declarationDetailcontentArray, $isEditMessage)
    {
        $declarationDetailcontentArray["type"] = RequestType::DECLARATION_DETAIL_ENTITY;

        // TODO: Implement parseDeclarationDetail() method.
        $declarationDetailcontentArray = null;

        return $declarationDetailcontentArray;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareAnimalFlag(ArrayCollection $declareAnimalFlagContentArray, $isEditMessage)
    {
        $declareAnimalFlagContentArray["type"] = RequestType::DECLARE_ANIMAL_FLAG_ENTITY;

        // TODO: Implement parseDeclareAnimalFlag() method.
        $declareAnimalFlagContentArray = null;

        return $declareAnimalFlagContentArray;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareArrival(ArrayCollection $declareArrivalContentArray, $isEditMessage)
    {
        $declareArrivalContentArray["type"] = RequestType::DECLARE_ARRIVAL_ENTITY;

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
        $declareBirthContentArray["type"] = RequestType::DECLARE_BIRTH_ENTITY;

        $declareBirthRequest = null;

        //TODO in this if block a new animal is created to allow switching genders. However this results in dataloss!!! Make sure to map the data from the old animal to the new one.
        //If it's not an edit message, just retrieve animal and set animal, otherwise don't setup
        //so the updated animal details will be persisted
        if($isEditMessage) {
            //denormalize the content to an object
            $animal = $declareBirthContentArray->get(Constant::ANIMAL_NAMESPACE);
            $animalObject = null;

            //Find registered tag, through given ulnCountryCode & ulnNumber, so we can assign the tag to this animal
            $tag = $this->entityGetter->retrieveTag($animal[Constant::ULN_COUNTRY_CODE_NAMESPACE], $animal[Constant::ULN_NUMBER_NAMESPACE]);

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

        //Move nested fields to the proper level
        $declareBirthContentArray['birth_weight'] = $declareBirthContentArray['animal']['birth_weight'];
        $declareBirthContentArray['is_lambar'] = $declareBirthContentArray['animal']['is_lambar'];
        $declareBirthContentArray['birth_tail_length'] = $declareBirthContentArray['animal']['birth_tail_length'];

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
        $declareDepartContentArray["type"] = RequestType::DECLARE_DEPART_ENTITY;

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
    function parseDeclareTagsTransfer(ArrayCollection $contentArray, $isEditMessage)
    {
        $contentArray["type"] = RequestType::DECLARE_TAGS_TRANSFER_ENTITY;

        $declareTagsTransfer = new DeclareTagsTransfer();
        $fetchedTag = null;
        $tagsRepository = $this->entityManager->getRepository(Constant::DECLARE_TAGS_TRANSFER_REPOSITORY);

        //Set relationNumberAcceptant
        if($contentArray->containsKey(Constant::RELATION_NUMBER_ACCEPTANT_SNAKE_CASE_NAMESPACE)) {
            $declareTagsTransfer->setRelationNumberAcceptant($contentArray[Constant::RELATION_NUMBER_ACCEPTANT_SNAKE_CASE_NAMESPACE]);
        }

        //Add Tag(s)
        if($contentArray->containsKey(Constant::TAGS_NAMESPACE)) {
            $tagsContentArray = $contentArray[Constant::TAGS_NAMESPACE];

            // Check if each tagItem has a ulnNumber and ulnCountryCode, so we can retrieve it from database
            foreach($tagsContentArray as $tagItem) {
                if(array_key_exists(Constant::ULN_NUMBER_NAMESPACE, $tagItem) && array_key_exists(Constant::ULN_COUNTRY_CODE_NAMESPACE, $tagItem) ) {

                    //Fetch tag from database
                    $fetchedTag = $this->entityGetter->retrieveTag($tagItem[Constant::ULN_COUNTRY_CODE_NAMESPACE], $tagItem[Constant::ULN_NUMBER_NAMESPACE]);

                    switch($fetchedTag->getTagStatus()) {
                        case TagStateType::UNASSIGNED:
                            //Ensure tag is not assigned
                            if($fetchedTag->getAnimal() == null) {
                                //Set tagState to transferring, save to database
                                $fetchedTag->setTagStatus(TagStateType::TRANSFERRING_TO_NEW_OWNER);
                                $tagsRepository->update($fetchedTag);

                                $declareTagsTransfer->addTag($fetchedTag);
                            }
                            break;
                        case TagStateType::ASSIGNED:
                            //TODO - what to do when a Tag to be transferred is already assigned to an Animal?
                        break;
                        case TagStateType::TRANSFERRING_TO_NEW_OWNER || TagStateType::TRANSFERRED_TO_NEW_OWNER:
                            //TODO - what to do when a Tag to be transferred is already in transfer or is already transferred?
                            break;
                        default:
                            break;

                    }
                }
            }


            $fetchedTag = null;
        }

        return $declareTagsTransfer;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareLoss(ArrayCollection $declareLossContentArray, $isEditMessage)
    {
        $declareLossContentArray["type"] = RequestType::DECLARE_LOSS_ENTITY;

        //Retrieve animal entity
        $retrievedAnimal = $this->entityGetter->retrieveAnimal($declareLossContentArray);

        //Add retrieved animal properties including type to initial animalContentArray
        $declareLossContentArray['animal'] =  $this->returnAnimalArray($retrievedAnimal);

        //denormalize the content to an object
        $json = $this->serializeToJSON($declareLossContentArray);
        $declareLossRequest = $this->deserializeToObject($json, RequestType::DECLARE_LOSS_ENTITY);

        //Add retrieved animal to DeclareLoss
        $declareLossRequest->setAnimal($retrievedAnimal);

        return $declareLossRequest;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareExport(ArrayCollection $declareExportContentArray, $isEditMessage)
    {
        $declareExportContentArray["type"] = RequestType::DECLARE_EXPORT_ENTITY;

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
        $declareImportContentArray["type"] = RequestType::DECLARE_IMPORT_ENTITY;

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
    function parseRetrieveTags(ArrayCollection $contentArray, $isEditMessage)
    {
        $contentArray["type"] = RequestType::RETRIEVE_TAGS_ENTITY;

        $retrieveTags = new RetrieveTags();

        //No custom filter content given, revert to default values
        if($contentArray->count() == 0) {
            $retrieveTags->setTagType(TagType::FREE);
            $retrieveTags->setAnimalType(AnimalType::sheep);

            return $retrieveTags;
        }

        //set animalType
        if($contentArray->containsKey(Constant::ANIMAL_TYPE_SNAKE_CASE_NAMESPACE)) {
            $retrieveTags->setAnimalType($contentArray->get(Constant::ANIMAL_TYPE_SNAKE_CASE_NAMESPACE));
        }

        //set tagType
        if($contentArray->containsKey(Constant::TAG_TYPE_SNAKE_CASE_NAMESPACE)) {
            $retrieveTags->setTagType($contentArray->get(Constant::TAG_TYPE_SNAKE_CASE_NAMESPACE));
        }

        return $retrieveTags;
    }

    /**
     * @inheritdoc
     */
    function parseRevokeDeclaration(ArrayCollection $revokeDeclarationContentArray, $isEditMessage)
    {
        $revokeDeclarationContentArray["type"] = RequestType::REVOKE_DECLARATION_ENTITY;
        $revokeDeclaration = new RevokeDeclaration();

        if($revokeDeclarationContentArray->containsKey(Constant::MESSAGE_ID_SNAKE_CASE_NAMESPACE)) {
            $revokeDeclaration->setMessageId($revokeDeclarationContentArray[Constant::MESSAGE_ID_SNAKE_CASE_NAMESPACE]);
        }

        return $revokeDeclaration;
    }

    /**
     * @param ArrayCollection $contentArray
     * @param $isEditMessage
     * @return RetrieveAnimals
     */
    function parseRetrieveAnimals(ArrayCollection $contentArray, $isEditMessage) {
        $retrieveAnimals = new RetrieveAnimals();

        return $retrieveAnimals;
    }

    /**
     * @param ArrayCollection $contentArray
     * @param $isEditMessage
     * @return RetrieveAnimalDetails
     */
    function parseRetrieveAnimalDetails(ArrayCollection $contentArray, $isEditMessage) {
        // TODO: Implement parseRetrieveAnimalDetails() method.
        $contentArray["type"] = RequestType::RETRIEVE_ANIMAL_DETAILS_ENTITY;

    }

    /**
     * @param ArrayCollection $contentArray
     * @param $isEditMessage
     * @return RetrieveEUCountries
     */
    function parseRetrieveEUCountries(ArrayCollection $contentArray, $isEditMessage) {
        // TODO: Implement parseRetrieveEUCountries() method.
        $contentArray["type"] = RequestType::RETRIEVE_EU_COUNTRIES_ENTITY;
    }

    /**
     * @param ArrayCollection $contentArray
     * @param $isEditMessage
     * @return RetrieveUBNDetails
     */
    function parseRetrieveUBNDetails(ArrayCollection $contentArray, $isEditMessage) {
        // TODO: Implement parseRetrieveUBNDetails() method.
        $contentArray["type"] = RequestType::RETRIEVE_UBN_DETAILS_ENTITY;
    }
}