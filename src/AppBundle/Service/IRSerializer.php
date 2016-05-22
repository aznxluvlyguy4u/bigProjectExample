<?php

namespace AppBundle\Service;

use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Client;
use AppBundle\Entity\RetrieveTags;
use AppBundle\Entity\RevokeDeclaration;
use AppBundle\Entity\RetrieveAnimals;
use AppBundle\Entity\RetrieveAnimalDetails;
use AppBundle\Enumerator\AnimalType;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Animal;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Enumerator\TagStateType;
use AppBundle\Enumerator\TagType;
use AppBundle\Enumerator\UIDType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\JsonArrayType;
use Doctrine\ORM\EntityManager;

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
     * @param $object
     * @return array
     */
    public function normalizeToArray($object)
    {
        $json = $this->serializeToJSON($object);
        $array = json_decode($json, true);

        return $array;
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
    function parseDeclareArrival(ArrayCollection $declareArrivalContentArray, Client $client, $isEditMessage)
    {
        $declareArrivalContentArray["type"] = RequestType::DECLARE_ARRIVAL_ENTITY;

        //Retrieve animal entity
        if($isEditMessage) {
            $requestId = $declareArrivalContentArray['request_id'];
            $declareArrivalRequest = $this->entityManager->getRepository(Constant::DECLARE_ARRIVAL_REPOSITORY)->getArrivalByRequestId($client, $requestId);

            //Update values here
            $declareArrivalRequest->setArrivalDate(new \DateTime($declareArrivalContentArray['arrival_date']));
            $declareArrivalRequest->setUbnPreviousOwner($declareArrivalContentArray['ubn_previous_owner']);
            $declareArrivalRequest->setRequestState(RequestStateType::OPEN);
            
        } else {
            $retrievedAnimal = $this->entityGetter->retrieveAnimal($declareArrivalContentArray);

            //Add retrieved animal properties including type to initial animalContentArray
            $declareArrivalContentArray->set(Constant::ANIMAL_NAMESPACE, $this->returnAnimalArray($retrievedAnimal));
            
            //denormalize the content to an object
            $json = $this->serializeToJSON($declareArrivalContentArray);
            $declareArrivalRequest = $this->deserializeToObject($json, RequestType::DECLARE_ARRIVAL_ENTITY);

            //Add retrieved animal to DeclareArrival
            $declareArrivalRequest->setAnimal($retrievedAnimal);

            $contentAnimal = $declareArrivalContentArray['animal'];

            if($contentAnimal != null) {

                if(array_key_exists(Constant::ULN_NUMBER_NAMESPACE, $contentAnimal) && array_key_exists(Constant::ULN_COUNTRY_CODE_NAMESPACE, $contentAnimal)) {
                    $declareArrivalRequest->setUlnCountryCode($contentAnimal[Constant::ULN_COUNTRY_CODE_NAMESPACE]);
                    $declareArrivalRequest->setUlnNumber($contentAnimal[Constant::ULN_NUMBER_NAMESPACE]);
                }

                if(array_key_exists(Constant::PEDIGREE_NUMBER_NAMESPACE, $contentAnimal) && array_key_exists(Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE, $contentAnimal)) {
                    $declareArrivalRequest->setPedigreeCountryCode($contentAnimal[Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE]);
                    $declareArrivalRequest->setPedigreeNumber($contentAnimal[Constant::PEDIGREE_NUMBER_NAMESPACE]);
                }
            }
        }

        return $declareArrivalRequest;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareBirth(ArrayCollection $declareBirthContentArray, $isEditMessage)
    {
        $declareBirthRequest = null;

        $declareBirthContentArray["type"] = RequestType::DECLARE_BIRTH_ENTITY;
        $animal = $declareBirthContentArray['animal'];
        $requestId = $declareBirthContentArray['request_id'];

        if($isEditMessage) {

            $declareBirth = $this->entityManager->getRepository(Constant::DECLARE_BASE_REPOSITORY)->findOneBy(array("requestId"=>$requestId));
            $retrievedAnimal = $declareBirth->getAnimal();
            $tag = $retrievedAnimal->getAssignedTag();

            $ulnCountryCodeNew = $declareBirthContentArray['animal']['uln_country_code'];
            $ulnNumberNew = $declareBirthContentArray['animal']['uln_number'];

            if($tag->getUlnCountryCode()!=$ulnCountryCodeNew || $tag->getUlnNumber()!=$ulnNumberNew) {
                $tag->setTagStatus(Constant::UNASSIGNED_NAMESPACE);
                $tag->setAnimal(null);
                $this->entityManager->persist($tag);
                $this->entityManager->flush();
                $tag = $this->entityManager->getRepository(Constant::TAG_REPOSITORY)->findOneBy(array("ulnCountryCode"=>$ulnCountryCodeNew, "ulnNumber"=>$ulnNumberNew));
            }


            $declareBirthNew = new DeclareBirth();
            $declareBirthNew = $declareBirth;

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

            $dateOfBirth = new \DateTime($declareBirthContentArray['date_of_birth']);
            $animalObject->setDateOfBirth($dateOfBirth);
            $declareBirthNew->setDateOfBirth($dateOfBirth);

            $animalObject->setAnimalCategory($retrievedAnimal->getAnimalCategory());
            $animalObject->setAnimalHairColour($retrievedAnimal->getAnimalHairColour());
            $animalObject->setDateOfBirth($retrievedAnimal->getDateOfBirth());
            //Skip date of death and setting declarations because this is a brand new animal
            //Gender is automatically set when creating an animal
            //
            $animalObject->setLocation($retrievedAnimal->getLocation());
            $animalObject->setName($retrievedAnimal->getName());
            $animalObject->setParentFather($retrievedAnimal->getParentFather());
            $animalObject->setParentMother($retrievedAnimal->getParentMother());
            $animalObject->setParentNeuter($retrievedAnimal->getParentNeuter());
            $animalObject->setPedigreeCountryCode($retrievedAnimal->getPedigreeCountryCode());
            $animalObject->setPedigreeNumber($retrievedAnimal->getPedigreeNumber());
            $animalObject->setSurrogate($retrievedAnimal->getSurrogate());
//            $animalObject->setUlnCountryCode($ulnCountryCodeNew);
//            $animalObject->setUlnNumber($ulnNumberNew);

            $this->entityManager->remove($declareBirth);
            $this->entityManager->flush();

//            $animalObject->setAssignedTag($tag);
            $tag->setAnimal($animalObject);
            $tag->setTagStatus(Constant::ASSIGNED_NAMESPACE);
            $this->entityManager->persist($tag);
            $this->entityManager->persist($animalObject->setAssignedTag($tag));
            $this->entityManager->flush();

            $declareBirthNew->setAnimal($animalObject);
            $this->entityManager->persist($declareBirthNew);
            $this->entityManager->flush();

            return $declareBirthNew;

        }
        //Retrieve animal entity
        $retrievedAnimal = $this->entityGetter->retrieveAnimal($declareBirthContentArray);

        //Move nested fields to the proper level
        $declareBirthContentArray['birth_weight'] = $declareBirthContentArray['animal']['birth_weight'];
        $declareBirthContentArray['has_lambar'] = $declareBirthContentArray['animal']['has_lambar'];
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
        $declareTagsTransfer->setRelationNumberAcceptant($contentArray[Constant::UBN_NEW_OWNER_NAMESPACE]);


        //Fetch tag from database
        $fetchedTag = $this->entityGetter->retrieveTag($contentArray[Constant::ULN_COUNTRY_CODE_NAMESPACE], $contentArray[Constant::ULN_NUMBER_NAMESPACE]);

        $declareTagsTransfer->addTag($fetchedTag);

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

        $exportDate = $declareExportContentArray['depart_date'];

        $retrievedAnimal = $this->entityGetter->retrieveAnimal($declareExportContentArray);

        //Add retrieved animal properties including type to initial animalContentArray
        $declareExportContentArray->set(Constant::ANIMAL_NAMESPACE, $this->returnAnimalArray($retrievedAnimal));

        //denormalize the content to an object
        $json = $this->serializeToJSON($declareExportContentArray);
        $declareExportRequest = $this->deserializeToObject($json, RequestType::DECLARE_EXPORT_ENTITY);

        $declareExportRequest->setAnimal($retrievedAnimal);
        $declareExportRequest->setExportDate(new \DateTime($exportDate));

        return $declareExportRequest;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareImport(ArrayCollection $declareImportContentArray, Client $client, $isEditMessage)
    {
        $declareImportContentArray["type"] = RequestType::DECLARE_IMPORT_ENTITY;

        $importDate = $declareImportContentArray['arrival_date'];

        if($isEditMessage) {
            $requestId = $declareImportContentArray['request_id'];
            $declareImportRequest = $this->entityManager->getRepository(Constant::DECLARE_IMPORT_REPOSITORY)->getImportByRequestId($client, $requestId);

            //Update values here
            $declareImportRequest->setImportDate(new \DateTime($declareImportContentArray['arrival_date']));
            $declareImportRequest->setAnimalCountryOrigin($declareImportContentArray['animal_country_origin']);
            $declareImportRequest->setRequestState(RequestStateType::OPEN);

        } else {
            //Retrieve animal entity
            $retrievedAnimal = $this->entityGetter->retrieveAnimal($declareImportContentArray);
            $retrievedAnimal->setIsImportAnimal(true);

            //Add retrieved animal properties including type to initial animalContentArray
            $declareImportContentArray->set(Constant::ANIMAL_NAMESPACE, $this->returnAnimalArray($retrievedAnimal));

            //denormalize the content to an object
            $json = $this->serializeToJSON($declareImportContentArray);
            $declareImportRequest = $this->deserializeToObject($json, RequestType::DECLARE_IMPORT_ENTITY);

            //Add retrieved animal and import date to DeclareImport
            $declareImportRequest->setAnimal($retrievedAnimal);
            $declareImportRequest->setImportDate(new \DateTime($importDate));

            $contentAnimal = $declareImportContentArray['animal'];

            if($contentAnimal != null) {

                if(array_key_exists(Constant::ULN_NUMBER_NAMESPACE, $contentAnimal) && array_key_exists(Constant::ULN_COUNTRY_CODE_NAMESPACE, $contentAnimal)) {
                    $declareImportRequest->setUlnCountryCode($contentAnimal[Constant::ULN_COUNTRY_CODE_NAMESPACE]);
                    $declareImportRequest->setUlnNumber($contentAnimal[Constant::ULN_NUMBER_NAMESPACE]);
                }

                if(array_key_exists(Constant::PEDIGREE_NUMBER_NAMESPACE, $contentAnimal) && array_key_exists(Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE, $contentAnimal)) {
                    $declareImportRequest->setPedigreeCountryCode($contentAnimal[Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE]);
                    $declareImportRequest->setPedigreeNumber($contentAnimal[Constant::PEDIGREE_NUMBER_NAMESPACE]);
                }
            }
        }

        return $declareImportRequest;
    }

    /**
     * @inheritdoc
     */
    function parseRetrieveTags(ArrayCollection $contentArray, $isEditMessage)
    {
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
        $retrieveAnimalDetails = new RetrieveAnimalDetails();

        if($contentArray->containsKey(Constant::ULN_NUMBER_NAMESPACE) && $contentArray->containsKey(Constant::ULN_COUNTRY_CODE_NAMESPACE)) {
            $ulnNumber = $contentArray->get(Constant::ULN_NUMBER_NAMESPACE);
            $ulnCountryCode = $contentArray->get(Constant::ULN_COUNTRY_CODE_NAMESPACE);

            $retrieveAnimalDetails->setUlnNumber($ulnNumber);
            $retrieveAnimalDetails->setUlnCountryCode($ulnCountryCode);
        } else if($contentArray->containsKey(Constant::ANIMAL_ORDER_NUMBER_NAMESPACE)) {
            $animalOrderNumber = $contentArray->get(Constant::ANIMAL_ORDER_NUMBER_NAMESPACE);
            $retrieveAnimalDetails->setAnimalOrderNumber($animalOrderNumber);
        }

        return $retrieveAnimalDetails;
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