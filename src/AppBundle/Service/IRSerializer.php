<?php

namespace AppBundle\Service;

use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\DeclarationDetail;
use AppBundle\Entity\DeclareAnimalFlag;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\DeclareLoss;
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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use JMS\Serializer\SerializationContext;

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
     * @var ObjectManager
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
    public function serializeToJSON($object, $type = 'DEFAULT')
    {
        if($type == 'DECLARE') {
            return $this->serializer->serialize($object, Constant::jsonNamespace, SerializationContext::create()->setGroups(array('declare')));
        }

        if($type == 'DEFAULT') {
            return $this->serializer->serialize($object, Constant::jsonNamespace, SerializationContext::create()->setGroups(array('Default')));
        }
    }

    /**
     * @param $json
     * @param $messageClassNameSpace
     * @return array|\JMS\Serializer\scalar|mixed|object|DeclareArrival|DeclareImport|DeclareExport|DeclareDepart|DeclareBirth|DeclareLoss|DeclareAnimalFlag|DeclarationDetail|DeclareTagsTransfer|RetrieveTags|RevokeDeclaration|RetrieveAnimals|RetrieveAnimals|RetrieveCountries|RetrieveUBNDetails|DeclareTagReplace
     */
    public function deserializeToObject($json, $messageClassNameSpace)
    {
        $messageClassPathNameSpace = "AppBundle\\Entity\\" . $messageClassNameSpace;

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

        if($retrievedAnimal instanceof Ram) {
            $retrievedAnimalContentArray['type'] = 'Ram';
        }

        if($retrievedAnimal instanceof Ewe) {
            $retrievedAnimalContentArray['type'] = 'Ewe';
        }

        if($retrievedAnimal instanceof Neuter) {
            $retrievedAnimalContentArray['type'] = 'Neuter';
        }

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
            $location = $declareArrivalContentArray[Constant::LOCATION_NAMESPACE];
            $declareArrivalRequest = $this->entityManager->getRepository(Constant::DECLARE_ARRIVAL_REPOSITORY)->getArrivalByRequestId($location, $requestId);
            $requestState = $declareArrivalRequest->getRequestState();

            //Update values here
            $declareArrivalRequest->setArrivalDate(new \DateTime($declareArrivalContentArray['arrival_date']));
            $ubnPreviousOwner = $declareArrivalContentArray['ubn_previous_owner'];
            $declareArrivalRequest->setUbnPreviousOwner($ubnPreviousOwner);

            if(Utils::hasSuccessfulLastResponse($requestState)) {
                $declareArrivalRequest->setRecoveryIndicator(RecoveryIndicatorType::J);
                $lastResponse = Utils::returnLastResponse($declareArrivalRequest->getResponses());
                if($lastResponse != null) {
                   $declareArrivalRequest->setMessageNumberToRecover($lastResponse->getMessageNumber());
                }
            } else {
                $declareArrivalRequest->setRecoveryIndicator(RecoveryIndicatorType::N);
            }
            $declareArrivalRequest->setRequestState(RequestStateType::OPEN);

        } else {
            $retrievedAnimal = $this->entityGetter->retrieveAnimal($declareArrivalContentArray);
            $retrievedAnimal->setIsImportAnimal(false);

            $declareArrivalRequest = new DeclareArrival();
            $declareArrivalRequest->setAnimal($retrievedAnimal);
            $declareArrivalRequest->setArrivalDate(new \DateTime($declareArrivalContentArray['arrival_date']));
            $declareArrivalRequest->setUbnPreviousOwner($declareArrivalContentArray['ubn_previous_owner']);
            $declareArrivalRequest->setAnimalObjectType(Utils::getClassName($retrievedAnimal));
            $declareArrivalRequest->setIsArrivedFromOtherNsfoClient($declareArrivalContentArray->get(JsonInputConstant::IS_ARRIVED_FROM_OTHER_NSFO_CLIENT));
            $declareArrivalRequest->setIsImportAnimal(false);

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
    function parseDeclareBirth(ArrayCollection $declareBirthContentArray, Client $client, $isEditMessage)
    {
        /** @var Animal $animal */
        $declareBirthRequest = null;
        $declareBirthContentArray["type"] = RequestType::DECLARE_BIRTH_ENTITY;
        $animal = $declareBirthContentArray['animal'];

        $declareBirthRequest = new DeclareBirth();
        $declareBirthRequest->setUlnCountryCode($animal->getUlnCountryCode());
        $declareBirthRequest->setUlnNumber($animal->getUlnNumber());
        $declareBirthRequest->setUlnCountryCodeMother($animal->getParentMother()->getUlnCountryCode());
        $declareBirthRequest->setUlnMother($animal->getParentMother()->getUlnNumber());
        $declareBirthRequest->setDateOfBirth($animal->getDateOfBirth());
        $declareBirthRequest->setHasLambar($animal->getLambar());
        $declareBirthRequest->setLitter($animal->getLitter());
        $declareBirthRequest->setBirthType($declareBirthContentArray['birth_type']);
        $declareBirthRequest->setLocation($declareBirthContentArray['location']);
        $declareBirthRequest->setLitterSize($declareBirthContentArray['litter_size']);
        $declareBirthRequest->setBirthWeight($declareBirthContentArray['birth_weight']);
        $declareBirthRequest->setBirthTailLength($declareBirthContentArray['tail_length']);

        if($animal->getParentFather() != null) {
            $declareBirthRequest->setUlnCountryCodeFather($animal->getParentFather()->getUlnCountryCode());
            $declareBirthRequest->setUlnFather($animal->getParentFather()->getUlnNumber());
        }

        if($declareBirthContentArray['nurture_type'] == 'SURROGATE') {
            $declareBirthRequest->setUlnCountryCodeSurrogate($animal->getSurrogate()->getUlnCountryCode());
            $declareBirthRequest->setUlnSurrogate($animal->getSurrogate()->getUlnNumber());
        }

        if($animal instanceof Ram) {
            $declareBirthRequest->setGender('MALE');
        }

        if($animal instanceof Ewe) {
            $declareBirthRequest->setGender('FEMALE');
        }

        if($animal instanceof Neuter) {
            $declareBirthRequest->setGender('NEUTER');
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
        $declareDepartRequest = new DeclareDepart();
        $declareDepartRequest->setAnimal($retrievedAnimal);
        $declareDepartRequest->setDepartDate(new \DateTime($declareDepartContentArray['depart_date']));
        $declareDepartRequest->setReasonOfDepart($declareDepartContentArray['reason_of_depart']);
        $declareDepartRequest->setAnimalObjectType(Utils::getClassName($retrievedAnimal));
        $declareDepartRequest->setUbnNewOwner($declareDepartContentArray['ubn_new_owner']);

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

        $replaceDate = Utils::getNullCheckedArrayCollectionDateValue('replace_date', $contentArray);
        //Set replaceDate = logDate in MessageBuilder if 'replace_date' was not given.

        //denormalize the content to an object
        $retrievedAnimal = $this->entityGetter->retrieveAnimal($contentArray);
        $declareTagReplace = new DeclareTagReplace();

        $declareTagReplace->setReplaceDate($replaceDate);

        $declareTagReplace->setUlnCountryCodeToReplace($retrievedAnimal->getUlnCountryCode());
        $declareTagReplace->setUlnNumberToReplace($retrievedAnimal->getUlnNumber());
        $declareTagReplace->setAnimalOrderNumberToReplace($retrievedAnimal->getAnimalOrderNumber());
        $declareTagReplace->setAnimalType(AnimalType::sheep);
        $declareTagReplace->setAnimal(null);

        $fetchedTag = null;
        $tagsRepository = $this->entityManager->getRepository(Constant::TAG_REPOSITORY);

        //create filter to search tag
        $tag = $contentArray['tag'];
        $tagFilter = array("ulnCountryCode" =>  $ulnCountryCodeReplacement = $tag['uln_country_code'],
          "ulnNumber" => $ulnNumberReplacement= $tag['uln_number']);

        //Fetch tag from database
        $fetchedTag = $tagsRepository->findOneBy($tagFilter);

        //If tag was found, add it to the declare tag replace request
        if($fetchedTag != null) {

            //Check if Tag status is UNASSIGNED && No animal is assigned to it
            if($fetchedTag->getTagStatus() == TagStateType::UNASSIGNED && $fetchedTag->getAnimal() == null) {

                //add tag to result set
                $declareTagReplace->setUlnCountryCodeReplacement($fetchedTag->getUlnCountryCode());
                $declareTagReplace->setUlnNumberReplacement($fetchedTag->getUlnNumber());
                $declareTagReplace->setAnimalOrderNumberReplacement($fetchedTag->getAnimalOrderNumber());
                $fetchedTag->setTagStatus(TagStateType::REPLACING);
                $this->entityManager->persist($fetchedTag);
                $this->entityManager->flush();
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
        $declareLossContentArray['animal'] = $retrievedAnimal;

        $dateOfDeath = Utils::getNullCheckedArrayCollectionDateValue(JsonInputConstant::DATE_OF_DEATH, $declareLossContentArray);
        $reasonOfLoss = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::REASON_OF_LOSS, $declareLossContentArray);
        $ubnProcessor = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::UBN_PROCESSOR, $declareLossContentArray);

        $declareLossRequest = new DeclareLoss();
        //Add retrieved animal to DeclareLoss
        $declareLossRequest->setAnimal($retrievedAnimal);
        $declareLossRequest->setDateOfDeath($dateOfDeath);
        $declareLossRequest->setUbnDestructor($ubnProcessor);
        $declareLossRequest->setReasonOfLoss($reasonOfLoss);
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

        $declareExportRequest = new DeclareExport();
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
    function parseDeclareImport(ArrayCollection $declareImportContentArray, Client $client, $locationOfDestination, $isEditMessage)
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

            //Add retrieved animal and import date to DeclareImport
            $declareImportRequest = new DeclareImport();
            $declareImportRequest->setAnimal($retrievedAnimal);
            $declareImportRequest->setAnimalCountryOrigin($animalCountryOrigin);
            $declareImportRequest->setImportDate(new \DateTime($importDate));
            $declareImportRequest->setAnimalObjectType(Utils::getClassName($retrievedAnimal));
            $declareImportRequest->setIsImportAnimal(true);

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
    function parseRetrieveUbnDetails(ArrayCollection $contentArray, Client $client, $isEditMessage) {
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