<?php

namespace AppBundle\Service;

use AppBundle\Component\Utils;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareTagReplace;
use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Client;
use AppBundle\Entity\RetrieveTags;
use AppBundle\Entity\RetrieveUbnDetails;
use AppBundle\Entity\RetrieveCountries;
use AppBundle\Entity\RevokeDeclaration;
use AppBundle\Entity\RetrieveAnimals;
use AppBundle\Entity\RetrieveAnimalDetails;
use AppBundle\Enumerator\AnimalType;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Animal;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\RecoveryIndicatorType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Enumerator\TagStateType;
use AppBundle\Enumerator\TagType;
use AppBundle\Enumerator\UIDType;
use AppBundle\Setting\ActionFlagSetting;
use AppBundle\Util\LocationHealthUpdater;
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
    function returnAnimalArray(Animal $retrievedAnimal, $unsetChildren = true)
    {
        //Parse to json
        $retrievedAnimalJson = $this->serializeToJSON($retrievedAnimal);
        //Parse json to content array to add additional 'animal type' property
        $retrievedAnimalContentArray = json_decode($retrievedAnimalJson, true);

        if($unsetChildren ==  true) {
            unset($retrievedAnimalContentArray[Constant::CHILDREN_NAMESPACE]);
            unset($retrievedAnimalContentArray[Constant::SURROGATE_CHILDREN_NAMESPACE]);
        }

        return  $retrievedAnimalContentArray;
    }

    /**
     * @param Animal $retrievedAnimal
     * @return array
     */
    function returnAnimalArrayIncludingParentsAndSurrogate(Animal $retrievedAnimal)
    {
        $childContentArray = $this->returnAnimalArray($retrievedAnimal);

        $childContentArray['parent_father'] = $this->returnAnimalArray($retrievedAnimal->getParentFather());
        $childContentArray['parent_mother'] =  $this->returnAnimalArray($retrievedAnimal->getParentMother());
        $childContentArray['surrogate'] =  $this->returnAnimalArray($retrievedAnimal->getSurrogate());

        return  $childContentArray;
    }

    /**
     * @inheritdoc
     */
    function parseDeclarationDetail(ArrayCollection $declarationDetailcontentArray, Client $client, $isEditMessage)
    {
        $declarationDetailcontentArray["type"] = RequestType::DECLARATION_DETAIL_ENTITY;

        // TODO: Implement parseDeclarationDetail() method.
        $declarationDetailcontentArray = null;

        return $declarationDetailcontentArray;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareAnimalFlag(ArrayCollection $declareAnimalFlagContentArray, Client $client, $isEditMessage)
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
            $ubnPreviousOwner = $declareArrivalContentArray['ubn_previous_owner'];
            $declareArrivalRequest->setUbnPreviousOwner($ubnPreviousOwner);
            $declareArrivalRequest->setRequestState(RequestStateType::OPEN);

            //Update health status based on UbnPreviousOwner
            $locationOfDestination = $declareArrivalRequest->getLocation();
            $locationOfDestination = LocationHealthUpdater::updateByGivenUbnOfOrigin($this->entityManager, $locationOfDestination, $ubnPreviousOwner);
            $declareArrivalRequest->setLocation($locationOfDestination);

            $requestState = $declareArrivalContentArray['request_state'];
            if(Utils::hasSuccessfulLastResponse($requestState)) {
                $declareArrivalRequest->setRecoveryIndicator(RecoveryIndicatorType::J);
                $lastResponse = Utils::returnLastResponse($declareArrivalRequest->getResponses());
                if($lastResponse != null) {
                   $declareArrivalRequest->setMessageNumberToRecover($lastResponse->getMessageNumber());
                }
            } else {
                $declareArrivalRequest->setRecoveryIndicator(RecoveryIndicatorType::N);
            }
            
        } else {
            $retrievedAnimal = $this->entityGetter->retrieveAnimal($declareArrivalContentArray);

            //Add retrieved animal properties including type to initial animalContentArray
            $declareArrivalContentArray->set(Constant::ANIMAL_NAMESPACE, $this->returnAnimalArray($retrievedAnimal));
            
            //denormalize the content to an object
            $json = $this->serializeToJSON($declareArrivalContentArray);
            $declareArrivalRequest = $this->deserializeToObject($json, RequestType::DECLARE_ARRIVAL_ENTITY);

            //Get the location from the animal before the new location is set on the animal
            $locationOfDestination = $client->getCompanies()->get(0)->getLocations()->get(0); //TODO Phase 2: Acceptt given Location
            $locationOfOrigin = $retrievedAnimal->getLocation();
            $locationOfDestination = LocationHealthUpdater::updateByGivenLocationOfOrigin($locationOfDestination, $locationOfOrigin);
            $declareArrivalRequest->setLocation($locationOfDestination);

            //Add retrieved animal to DeclareArrival
            $declareArrivalRequest->setAnimal($retrievedAnimal);
            $declareArrivalRequest->setAnimalObjectType(Utils::getClassName($retrievedAnimal));

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
    function parseDeclareBirth(ArrayCollection $declareBirthContentArray, Client $client,$isEditMessage)
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
                $tag->setTagStatus(TagStateType::UNASSIGNED);
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
                    case GenderType::FEMALE:
                        $animalObject = new Ewe();
                        break;
                    case GenderType::MALE:
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
            $tag->setTagStatus(TagStateType::ASSIGNING);
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
        $retrievedAnimalArray = $this->returnAnimalArrayIncludingParentsAndSurrogate($retrievedAnimal);

        //Move nested fields to the proper level
        $declareBirthContentArray['birth_weight'] = $declareBirthContentArray['animal']['birth_weight'];
        $declareBirthContentArray['has_lambar'] = $declareBirthContentArray['animal']['has_lambar'];
        $declareBirthContentArray['birth_tail_length'] = $declareBirthContentArray['animal']['birth_tail_length'];
        $declareBirthContentArray['gender'] = $declareBirthContentArray['animal']['gender'];
        
        //Add retrieved animal properties including type to initial animalContentArray
        $declareBirthContentArray->set(Constant::ANIMAL_NAMESPACE, $retrievedAnimalArray);

        //denormalize the content to an object
        $json = $this->serializeToJSON($declareBirthContentArray);
        $declareBirthRequest = $this->deserializeToObject($json, RequestType::DECLARE_BIRTH_ENTITY);

        //Add retrieved animal to DeclareBirth
        $declareBirthRequest->setAnimal($retrievedAnimal);
        //Note setting the Animal will overwrite the animal values
        $animalArray = $declareBirthContentArray['animal'];
        $fatherArray = $animalArray['parent_father'];
        $declareBirthRequest->setUlnCountryCodeFather($fatherArray[Constant::ULN_COUNTRY_CODE_NAMESPACE]);
        $declareBirthRequest->setUlnFather($fatherArray[Constant::ULN_NUMBER_NAMESPACE]);

        $motherArray = $animalArray['parent_mother'];
        $declareBirthRequest->setUlnCountryCodeMother($motherArray[Constant::ULN_COUNTRY_CODE_NAMESPACE]);
        $declareBirthRequest->setUlnMother($motherArray[Constant::ULN_NUMBER_NAMESPACE]);

        $surrogateArray = $animalArray['surrogate'];
        $declareBirthRequest->setUlnCountryCodeSurrogate($surrogateArray[Constant::ULN_COUNTRY_CODE_NAMESPACE]);
        $declareBirthRequest->setUlnSurrogate($surrogateArray[Constant::ULN_NUMBER_NAMESPACE]);

        if($isEditMessage) {
            $requestState = $declareBirthContentArray['request_state'];
            if(Utils::hasSuccessfulLastResponse($requestState)) {
                $declareBirthRequest->setRecoveryIndicator(RecoveryIndicatorType::J);
                $lastResponse = Utils::returnLastResponse($declareBirthRequest->getResponses());
                if($lastResponse != null) {
                    $declareBirthRequest->setMessageNumberToRecover($lastResponse->getMessageNumber());
                }
            } else {
                $declareBirthRequest->setRecoveryIndicator(RecoveryIndicatorType::N);
            }
        }

        return $declareBirthRequest;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareDepart(ArrayCollection $declareDepartContentArray, Client $client,$isEditMessage)
    {
        $declareDepartContentArray["type"] = RequestType::DECLARE_DEPART_ENTITY;
        $isExportAnimal = $declareDepartContentArray['is_export_animal'];

        //Retrieve animal entity
        $retrievedAnimal = $this->entityGetter->retrieveAnimal($declareDepartContentArray);
        $retrievedAnimal->setIsExportAnimal($isExportAnimal);

        //Add retrieved animal properties including type to initial animalContentArray
        $declareDepartContentArray->set(Constant::ANIMAL_NAMESPACE, $this->returnAnimalArray($retrievedAnimal));

        //denormalize the content to an object
        $json = $this->serializeToJSON($declareDepartContentArray);
        $declareDepartRequest = $this->deserializeToObject($json, RequestType::DECLARE_DEPART_ENTITY);

        //Add retrieved animal to DeclareArrival
        $declareDepartRequest->setAnimal($retrievedAnimal);
        $declareDepartRequest->setAnimalObjectType(Utils::getClassName($retrievedAnimal));

        if($isEditMessage) {
            $requestState = $declareDepartContentArray['request_state'];
            if(Utils::hasSuccessfulLastResponse($requestState)) {
                $declareDepartRequest->setRecoveryIndicator(RecoveryIndicatorType::J);
                $lastResponse = Utils::returnLastResponse($declareDepartRequest->getResponses());
                if($lastResponse != null) {
                    $declareDepartRequest->setMessageNumberToRecover($lastResponse->getMessageNumber());
                }
            } else {
                $declareDepartRequest->setRecoveryIndicator(RecoveryIndicatorType::N);
            }
        }

        return $declareDepartRequest;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareTagsTransfer(ArrayCollection $contentArray, Client $client, $isEditMessage)
    {
        $contentArray["type"] = RequestType::DECLARE_TAGS_TRANSFER_ENTITY;

        $ubnNewOwner = $contentArray['ubn_new_owner'];
        $relationNumberAcceptant = $contentArray['relation_number_acceptant'];

        $declareTagsTransfer = new DeclareTagsTransfer();
        $declareTagsTransfer->setRelationNumberAcceptant($relationNumberAcceptant);
        $declareTagsTransfer->setUbnNewOwner($ubnNewOwner);
        $fetchedTag = null;
        $tagsRepository = $this->entityManager->getRepository(Constant::TAG_REPOSITORY);
        $tagsContentArray = $contentArray->get('tags');

        foreach($tagsContentArray as $tag) {
            //create filter to search tag
            $tagFilter = array("ulnCountryCode" => $tag[Constant::ULN_COUNTRY_CODE_NAMESPACE],
                "ulnNumber" => $tag[Constant::ULN_NUMBER_NAMESPACE]);

            //Fetch tag from database
            $fetchedTag = $tagsRepository->findOneBy($tagFilter);

            //If tag was found, add it to the declare transfer request
            if($fetchedTag != null) {

                //Check if Tag status is UNASSIGNED && No animal is assigned to it
                if($fetchedTag->getTagStatus() == TagStateType::UNASSIGNED && $fetchedTag->getAnimal() == null) {

                    //add tag to result set
                    $declareTagsTransfer->addTag($fetchedTag);
                }
            }
        }

        //TODO: NO EDIT YET, Phase 2+

        return $declareTagsTransfer;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareTagReplace(ArrayCollection $contentArray, Client $client, $isEditMessage)
    {
        $contentArray["type"] = RequestType::DECLARE_TAG_REPLACE_ENTITY;

        $animal = $contentArray['animal'];
        $tag = $contentArray['tag'];
        $replaceDate = $contentArray['replace_date'];
        $ulnCountryCodeToReplace = $animal['uln_country_code'];
        $ulnNumberToReplace = $animal['uln_number'];

        $declareTagReplace = new DeclareTagReplace();
        $declareTagReplace->setUlnCountryCodeToReplace($ulnCountryCodeToReplace);
        $declareTagReplace->setUlnNumberToReplace($ulnNumberToReplace);
        $declareTagReplace->setReplaceDate($replaceDate);

        $fetchedTag = null;
        $tagsRepository = $this->entityManager->getRepository(Constant::TAG_REPOSITORY);

        //create filter to search tag
        $tagFilter = array("ulnCountryCode" =>  $ulnCountryCodeReplacement = $tag['uln_country_code'],
          "ulnNumber" => $ulnNumberReplacement= $tag['uln_number']);

        //Fetch tag from database
        $fetchedTag = $tagsRepository->findOneBy($tagFilter);

        //If tag was found, add it to the declare tag replac request
        if($fetchedTag != null) {

            //Check if Tag status is UNASSIGNED && No animal is assigned to it
            if($fetchedTag->getTagStatus() == TagStateType::UNASSIGNED && $fetchedTag->getAnimal() == null) {

                //add tag to result set
                $declareTagReplace->addTag($fetchedTag);

                $declareTagReplace->setUlnCountryCodeReplacement($fetchedTag->getUlnCountryCode());
                $declareTagReplace->setUlnNumberReplacement($fetchedTag->getUlnNumber());
            }
        }

        //TODO: NO EDIT YET, Phase 2+

        return $declareTagReplace;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareLoss(ArrayCollection $declareLossContentArray, Client $client,$isEditMessage)
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
        $declareLossRequest->setAnimalObjectType(Utils::getClassName($retrievedAnimal));

        if($isEditMessage) {
            $requestState = $declareLossContentArray['request_state'];
            if(Utils::hasSuccessfulLastResponse($requestState)) {
                $declareLossRequest->setRecoveryIndicator(RecoveryIndicatorType::J);
                $lastResponse = Utils::returnLastResponse($declareLossRequest->getResponses());
                if($lastResponse != null) {
                    $declareLossRequest->setMessageNumberToRecover($lastResponse->getMessageNumber());
                }
            } else {
                $declareLossRequest->setRecoveryIndicator(RecoveryIndicatorType::N);
            }
        }

        return $declareLossRequest;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareExport(ArrayCollection $declareExportContentArray, Client $client,$isEditMessage)
    {
        $declareExportContentArray["type"] = RequestType::DECLARE_EXPORT_ENTITY;

        $exportDate = $declareExportContentArray['depart_date'];
        $isExportAnimal = $declareExportContentArray['is_export_animal'];

        $retrievedAnimal = $this->entityGetter->retrieveAnimal($declareExportContentArray);
        $retrievedAnimal->setIsExportAnimal($isExportAnimal);

        //Add retrieved animal properties including type to initial animalContentArray
        $declareExportContentArray->set(Constant::ANIMAL_NAMESPACE, $this->returnAnimalArray($retrievedAnimal));

        //denormalize the content to an object
        $json = $this->serializeToJSON($declareExportContentArray);
        $declareExportRequest = $this->deserializeToObject($json, RequestType::DECLARE_EXPORT_ENTITY);

        $declareExportRequest->setAnimal($retrievedAnimal);
        $declareExportRequest->setExportDate(new \DateTime($exportDate));
        $declareExportRequest->setIsExportAnimal($isExportAnimal);
        $declareExportRequest->setReasonOfExport($declareExportContentArray['reason_of_depart']);
        $declareExportRequest->setAnimalObjectType(Utils::getClassName($retrievedAnimal));

        if($isEditMessage) {
            $requestState = $declareExportContentArray['request_state'];
            if(Utils::hasSuccessfulLastResponse($requestState)) {
                $declareExportRequest->setRecoveryIndicator(RecoveryIndicatorType::J);
                $lastResponse = Utils::returnLastResponse($declareExportRequest->getResponses());
                if($lastResponse != null) {
                    $declareExportRequest->setMessageNumberToRecover($lastResponse->getMessageNumber());
                }
            } else {
                $declareExportRequest->setRecoveryIndicator(RecoveryIndicatorType::N);
            }
        }

        return $declareExportRequest;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareImport(ArrayCollection $declareImportContentArray, Client $client, $isEditMessage)
    {
        //TODO Phase 2: Built in explicit check for non-EU/EU countries. Now it is filtered by animalUlnNumberOrigin field being null or not.

        $declareImportContentArray["type"] = RequestType::DECLARE_IMPORT_ENTITY;

        $importDate = $declareImportContentArray['arrival_date'];
        if($declareImportContentArray->containsKey('country_origin')) {
            $animalCountryOrigin = $declareImportContentArray['country_origin'];
        } else {
            $animalCountryOrigin = null;
        }

        if($declareImportContentArray->containsKey('animal_uln_number_origin')) {
            $animalUlnNumberOrigin = $declareImportContentArray['animal_uln_number_origin'];
        } else {
            $animalUlnNumberOrigin = null;
        }

        //TODO explicitly check the countries
        //For EU countries the ulnCountryCode needs to be the AnimalCountyOrigin (=country_origin)
        if($animalUlnNumberOrigin == null) {
            $declareImportContentArray[Constant::ULN_COUNTRY_CODE_NAMESPACE] = $animalCountryOrigin;
        }


        if($isEditMessage) {
            $requestId = $declareImportContentArray['request_id'];
            $declareImportRequest = $this->entityManager->getRepository(Constant::DECLARE_IMPORT_REPOSITORY)->getImportByRequestId($client, $requestId);

            //Update values here
            $declareImportRequest->setImportDate(new \DateTime($declareImportContentArray['arrival_date']));

            if($declareImportContentArray->containsKey('country_origin')) {
                $declareImportRequest->setAnimalCountryOrigin($animalCountryOrigin);
            }

            if($declareImportContentArray->containsKey('animal_uln_number_origin')) {
                $declareImportRequest->setAnimalUlnNumberOrigin($animalUlnNumberOrigin);
            }

            $declareImportRequest->setRequestState(RequestStateType::OPEN);


            $requestState = $declareImportContentArray['request_state'];
            if(Utils::hasSuccessfulLastResponse($requestState)) {
                $declareImportRequest->setRecoveryIndicator(RecoveryIndicatorType::J);
                $lastResponse = Utils::returnLastResponse($declareImportRequest->getResponses());
                if($lastResponse != null) {
                    $declareImportRequest->setMessageNumberToRecover($lastResponse->getMessageNumber());
                }
            } else {
                $declareImportRequest->setRecoveryIndicator(RecoveryIndicatorType::N);
            }


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
            $declareImportRequest->setAnimalCountryOrigin($animalCountryOrigin);
            $declareImportRequest->setImportDate(new \DateTime($importDate));
            $declareImportRequest->setAnimalObjectType(Utils::getClassName($retrievedAnimal));

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

        //At the moment all imports are from location with unknown health status
        $locationOfDestination = $client->getCompanies()->get(0)->getLocations()->get(0); //TODO Phase 2+, accept different Locations
        $locationOfDestination = LocationHealthUpdater::updateWithoutOriginHealthData($locationOfDestination);
        $declareImportRequest->setLocation($locationOfDestination);

        return $declareImportRequest;
    }

    /**
     * @inheritdoc
     */
    function parseRetrieveTags(ArrayCollection $contentArray, Client $client,$isEditMessage)
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
    function parseRevokeDeclaration(ArrayCollection $revokeDeclarationContentArray, Client $client, $isEditMessage)
    {
        $revokeDeclarationContentArray["type"] = RequestType::REVOKE_DECLARATION_ENTITY;
        $revokeDeclaration = new RevokeDeclaration();

        if($revokeDeclarationContentArray->containsKey(Constant::MESSAGE_ID_SNAKE_CASE_NAMESPACE)) {
            $revokeDeclaration->setMessageId($revokeDeclarationContentArray[Constant::MESSAGE_ID_SNAKE_CASE_NAMESPACE]);
        }

        return $revokeDeclaration;
    }

    /**
     * @inheritdoc
     */
    function parseRetrieveAnimals(ArrayCollection $contentArray, Client $client, $isEditMessage) {
        $retrieveAnimals = new RetrieveAnimals();

        return $retrieveAnimals;
    }

    /**
     * @inheritdoc
     */
    function parseRetrieveAnimalDetails(ArrayCollection $contentArray, Client $client,$isEditMessage) {
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
     * @inheritdoc
     */
    function parseRetrieveEUCountries(ArrayCollection $contentArray, Client $client, $isEditMessage) {
        // TODO: Implement parseRetrieveEUCountries() method.
        $contentArray["type"] = RequestType::RETRIEVE_COUNTRIES_ENTITY;
    }

    /**
     * @param ArrayCollection $contentArray
     * @param $isEditMessage
     * @return RetrieveUbnDetails
     */
    function parseRetrieveUBNDetails(ArrayCollection $contentArray, Client $client, $isEditMessage) {
        $retrieveUbnDetails = new RetrieveUbnDetails();

        return $retrieveUbnDetails;
    }

    /**
     * @param array $animalArray
     * @return array
     */
    function extractUlnFromAnimal($animalArray)
    {
        if(array_key_exists(Constant::ULN_COUNTRY_CODE_NAMESPACE, $animalArray) && array_key_exists(Constant::ULN_NUMBER_NAMESPACE, $animalArray)) {
            if( ($animalArray[Constant::ULN_COUNTRY_CODE_NAMESPACE] != null && $animalArray[Constant::ULN_COUNTRY_CODE_NAMESPACE] != "" )
                && ($animalArray[Constant::ULN_NUMBER_NAMESPACE] != null && $animalArray[Constant::ULN_NUMBER_NAMESPACE] != "" ) ) {
                
                return array(Constant::ULN_COUNTRY_CODE_NAMESPACE => $animalArray[Constant::ULN_COUNTRY_CODE_NAMESPACE],
                                   Constant::ULN_NUMBER_NAMESPACE => $animalArray[Constant::ULN_NUMBER_NAMESPACE]);
            }
            
        } elseif (array_key_exists(Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE, $animalArray) && array_key_exists(Constant::PEDIGREE_NUMBER_NAMESPACE, $animalArray)) {
            if (($animalArray[Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE] != null && $animalArray[Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE] != "")
                && ($animalArray[Constant::PEDIGREE_NUMBER_NAMESPACE] != null && $animalArray[Constant::PEDIGREE_NUMBER_NAMESPACE] != "") ) {

                return $this->entityManager->getRepository(Constant::ANIMAL_REPOSITORY)->getUlnByPedigree(
                    $animalArray[Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE], $animalArray[Constant::PEDIGREE_NUMBER_NAMESPACE]);
            }
        }

        return array(Constant::ULN_COUNTRY_CODE_NAMESPACE => null,
            Constant::ULN_NUMBER_NAMESPACE => null);
    }
}