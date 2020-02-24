<?php

namespace AppBundle\Output;


use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Criteria\AnimalCriteria;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalCache;
use AppBundle\Entity\AnimalCacheRepository;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareBase;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Exterior;
use AppBundle\Entity\ExteriorRepository;
use AppBundle\Entity\Location;
use AppBundle\Entity\ParentInterface;
use AppBundle\Entity\Person;
use AppBundle\Entity\Weight;
use AppBundle\Entity\WeightRepository;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\SqlView\Repository\ViewBreedValueMaxGenerationDateRepository;
use AppBundle\SqlView\Repository\ViewMinimalParentDetailsRepository;
use AppBundle\SqlView\View\ViewBreedValueMaxGenerationDate;
use AppBundle\SqlView\View\ViewMinimalParentDetails;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\PedigreeUtil;
use AppBundle\Util\Validator;
use AppBundle\Validation\UlnValidator;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

/**
 * Class DeclareAnimalDetailsOutput
 */
class AnimalDetailsOutput extends OutputServiceBase
{
    const NESTED_GENERATION_LIMIT = 4;

    /** @var BreedValuesOutput */
    private $breedValuesOutput;

    /** @var array */
    private $ownerUbns;

    /**
     * @required
     *
     * @param BreedValuesOutput $breedValuesOutput
     */
    public function setBreedValuesOutput(BreedValuesOutput $breedValuesOutput)
    {
        $this->breedValuesOutput = $breedValuesOutput;
    }


    /**
     * @param Animal $animal
     * @param Person $user
     * @param Location|null $location
     * @param bool $includeAscendants
     * @return array
     * @throws \Exception
     */
    public function getForUserEnvironment(Animal $animal, Person $user, ?Location $location, $includeAscendants = false)
    {
        return $this->get($animal, $user, $location, $includeAscendants);
    }


    /**
     * @param Person $user
     * @param Location|null $location
     */
    private function validateLocation(Person $user, ?Location $location)
    {
        if (!($user instanceof Employee) && !$location) {
            throw new PreconditionFailedHttpException('If user is not an admin, location cannot be null');
        }
    }


    /**
     * @param Animal $animal
     * @param Person $user
     * @param Location|null $location
     * @param bool $includeAscendants
     * @return array
     * @throws \Exception
     */
    public function get(Animal $animal, Person $user, ?Location $location, $includeAscendants = false)
    {
        $this->validateLocation($user, $location);

        $replacementString = "";

        $litterSize = $replacementString;
        $suckleCount = $replacementString;
        $litter = $animal->getLitter();
        if ($litter) {
            $litterSize = $litter->getSize();
            $suckleCount = $litter->getSuckleCount();
        }

        //Birth
        $translatedCountryName = $replacementString;
        $countryDetailsOfBirth = $animal->getCountryDetailsOfBirth();
        if ($countryDetailsOfBirth) {
            $countryName = $countryDetailsOfBirth->getName();
            $translatedCountryName = $this->getTranslator()->trans($countryName);
        }


        $inbreedingCoefficientValue = $replacementString;
        $inbreedingCoefficient = $animal->getInbreedingCoefficient();
        if ($inbreedingCoefficient) {
            $inbreedingCoefficientValue = $inbreedingCoefficient->getValue();
        }

        /** @var ExteriorRepository $exteriorRepository */
        $exteriorRepository = $this->getManager()->getRepository(Exterior::class);
        /** @var WeightRepository $weightRepository */
        $weightRepository = $this->getManager()->getRepository(Weight::class);
        /** @var AnimalCacheRepository $animalCacheRepository */
        $animalCacheRepository = $this->getManager()->getRepository(AnimalCache::class);

        $animalCache = $animalCacheRepository->findByAnimalId($animal->getId());

        /** @var AnimalRepository $animalRepository */
        $animalRepository = $this->getManager()->getRepository(Animal::class);
        /** @var ViewMinimalParentDetailsRepository $viewMinimalParentDetailsRepository */
        $viewMinimalParentDetailsRepository = $this->getSqlViewManager()->get(ViewMinimalParentDetails::class);

        /** @var ViewBreedValueMaxGenerationDateRepository $viewBreedValueMaxGenerationDateRepository */
        $viewBreedValueMaxGenerationDateRepository = $this->getSqlViewManager()->get(ViewBreedValueMaxGenerationDate::class);

        $animalId = $animal->getId();
        $fatherId = $animal->getParentFatherId();
        $motherId = $animal->getParentMotherId();

        $animalIds[] = $animalId;
        if ($fatherId) { $animalIds[] = $fatherId; }
        if ($motherId) { $animalIds[] = $motherId; }

        $viewMinimalParentDetails = $viewMinimalParentDetailsRepository->findByAnimalIds($animalIds);
        /** @var ViewMinimalParentDetails $viewMinimalAnimalDetails */
        $viewMinimalAnimalDetails = $viewMinimalParentDetails->get($animalId);

        $predicate = $viewMinimalAnimalDetails ? $viewMinimalAnimalDetails->getFormattedPredicate() : null;
        $production = $viewMinimalAnimalDetails ? $viewMinimalAnimalDetails->getProduction() : null;

        $scanMeasurements = $animal->getScanMeasurementSet();

        $company = $location ? $location->getCompany() : null;

        $result = [
            // Primary Details
        	JsonInputConstant::ID => $animal->getId(),
            JsonInputConstant::IS_ALIVE => $animal->getIsAlive(),
            JsonInputConstant::UBN => $animal->getUbn(),

            JsonInputConstant::ULN_COUNTRY_CODE => Utils::fillNullOrEmptyString($animal->getUlnCountryCode(), $replacementString),
            JsonInputConstant::ULN_NUMBER => Utils::fillNullOrEmptyString($animal->getUlnNumber(), $replacementString),

            JsonInputConstant::PEDIGREE_COUNTRY_CODE => Utils::fillNullOrEmptyString($animal->getPedigreeCountryCode(), $replacementString),
            JsonInputConstant::PEDIGREE_NUMBER => Utils::fillNullOrEmptyString($animal->getPedigreeNumber(), $replacementString),

            JsonInputConstant::NICKNAME => Utils::fillNullOrEmptyString($animal->getNickname(), $replacementString),
            JsonInputConstant::WORK_NUMBER => Utils::fillNullOrEmptyString($animal->getAnimalOrderNumber(), $replacementString),

            JsonInputConstant::COLLAR => [
                JsonInputConstant::COLOR => Utils::fillNullOrEmptyString($animal->getCollarColor(), $replacementString),
                JsonInputConstant::NUMBER => Utils::fillNullOrEmptyString($animal->getCollarNumber(), $replacementString)
            ],

            JsonInputConstant::DATE_OF_BIRTH => Utils::fillNullOrEmptyString($animal->getDateOfBirth(), $replacementString),
            JsonInputConstant::DATE_OF_DEATH => Utils::fillNullOrEmptyString($animal->getDateOfDeath(), $replacementString),

            JsonInputConstant::COUNTRY_OF_BIRTH => Utils::fillNullOrEmptyString($translatedCountryName, $replacementString),

            JsonInputConstant::GENDER => Utils::fillNullOrEmptyString($animal->getGender(), $replacementString),

            JsonInputConstant::LITTER_SIZE => Utils::fillNullOrEmptyString($litterSize, $replacementString),
            JsonInputConstant::SUCKLE_COUNT => Utils::fillNullOrEmptyString($suckleCount, $replacementString),
            JsonInputConstant::PRODUCTION => $production,

            JsonInputConstant::SCRAPIE_GENOTYPE => Utils::fillNullOrEmptyString($animal->getScrapieGenotype(), $replacementString),
            JsonInputConstant::INBREEDING_COEFFICIENT => Utils::fillNullOrEmptyString($inbreedingCoefficientValue, $replacementString),

            // Predicate & Statuses
            JsonInputConstant::BREED => Utils::fillNullOrEmptyString($animal->getBreedCode(), $replacementString),
            JsonInputConstant::PREDICATE => Utils::fillNullOrEmptyString($predicate, $replacementString),
            JsonInputConstant::PREDICATE_DETAILS => $this->getPredicateDetails($animal, $predicate),
            JsonInputConstant::BREED_TYPE => Utils::fillNullOrEmptyString($animal->getBreedType(), $replacementString),
            JsonInputConstant::BLINDNESS_FACTOR => $animal->getBlindnessFactor(),

            // Scan measurements
            JsonInputConstant::SCAN_MEASUREMENTS =>
                [
                    JsonInputConstant::MEASUREMENT_DATE => $scanMeasurements ? $scanMeasurements->getMeasurementDate() : null,
                    "fat_cover_one" => $scanMeasurements ? $scanMeasurements->getFat1Value() : null,
                    "fat_cover_two" => $scanMeasurements ? $scanMeasurements->getFat2Value() : null,
                    "fat_cover_three" => $scanMeasurements ? $scanMeasurements->getFat3Value() : null,
                    "muscular_thickness" => $scanMeasurements ? $scanMeasurements->getMuscleThicknessValue() : null,
                    "scan_weight" => $scanMeasurements ? $scanMeasurements->getScanWeightValue() : null,
                ],

            // Birth measurements
            JsonInputConstant::BIRTH => [
                JsonInputConstant::TAIL_LENGTH => $animalCache->getTailLength(),
                JsonInputConstant::BIRTH_WEIGHT => $animalCache->getBirthWeight(),
                JsonInputConstant::BIRTH_PROGRESS => $animal->getBirthProgress(),
            ],

            // Contact data
            JsonInputConstant::BREEDER => $this->getContactData($animal->getLocationOfBirth()),
            JsonInputConstant::HOLDER => $this->getContactData($animal->getLocation()),

            // Rearing
            JsonInputConstant::REARING => [
                JsonInputConstant::LABEL => $this->getRearingLabel($animal),
                JsonInputConstant::LAMBAR => $animal->getLambar(),
                JsonInputConstant::SURROGATE => $this->getSurrogate($animal->getSurrogate()),
            ],

            // Calculated values
            "child_count" => $animalRepository->offspringCount($animal),

            // Breed Values
            JsonInputConstant::BREED_VALUE_MAX_GENERATION_DATE => $viewBreedValueMaxGenerationDateRepository->getMaxGenerationDateAsDdMmYyyy(),
            "breed_values" => $this->breedValuesOutput->get($animal),

            "exteriors" => $exteriorRepository->getAllOfAnimalBySql($animal, $replacementString),
            "weights" => $weightRepository->getAllOfAnimalBySql($animal, $replacementString),

            "declare_log" => $this->getLog($animal, $replacementString),
        ];

        if ($fatherId) {
            /** @var ViewMinimalParentDetails $viewParentFather */
            $viewParentFather = $viewMinimalParentDetails->get($fatherId);
            $viewParentFather->setIsOwnHistoricAnimal($this->isHistoricAnimalOfOwner($viewParentFather, $user));
            $result["parent_father"] = $this->getSerializer()->getDecodedJson($viewParentFather);
            $result["parent_father"][ReportLabel::IS_USER_ALLOWED_TO_ACCESS_ANIMAL_DETAILS] =
                UlnValidator::isUserAllowedToAccessAnimalDetails($viewParentFather, $user, $company);
        }

        if ($motherId) {
            /** @var ViewMinimalParentDetails $viewParentMother */
            $viewParentMother = $viewMinimalParentDetails->get($motherId);
            $viewParentMother->setIsOwnHistoricAnimal($this->isHistoricAnimalOfOwner($viewParentMother, $user));
            $result["parent_mother"] = $this->getSerializer()->getDecodedJson($viewParentMother);
            $result["parent_mother"][ReportLabel::IS_USER_ALLOWED_TO_ACCESS_ANIMAL_DETAILS] =
                UlnValidator::isUserAllowedToAccessAnimalDetails($viewParentMother, $user, $company);
        }

        if ($includeAscendants) {
            $ascendants = PedigreeUtil::findNestedParentsBySingleSqlQuery($this->getManager()->getConnection(), [$animal->getId()],self::NESTED_GENERATION_LIMIT);
            $result["ascendants"] = ArrayUtil::get($animal->getUln(), $ascendants, []);
        }

        if ($animal->getPedigreeRegister()) {
            $result["pedigree_register"] = [
                "id" => $animal->getPedigreeRegister()->getId(),
                "abbreviation" => $animal->getPedigreeRegister()->getAbbreviation(),
                "full_name" => $animal->getPedigreeRegister()->getFullName(),
            ];
        }

        $isOwnAnimal = false;
        if($user instanceof Client) {
            if($animal->getIsAlive()) {
                foreach ($user->getCompanies() as $company) {
                    if ($animal->getLocation() && $animal->getLocation()->getCompany()->getId() === $company->getId()) {
                        $isOwnAnimal = true;
                        break;
                    }
                }
            }
        }
        $result[JsonInputConstant::IS_OWN_ANIMAL] = $isOwnAnimal;

        $this->ownerUbns = null;

        return $result;
    }


    private function getSurrogate(?Ewe $ewe): array
    {
        if (!$ewe) {
            return [];
        }
        return $this->getSerializer()->normalizeToArray($ewe, [JmsGroup::BASIC]);
    }


    private function getRearingLabel(Animal $animal): ?string
    {
        if ($animal->getLambar() === true) {
            return 'LAMBAR';
        }

        if ($animal->getSurrogate() != null) {
            return $animal->getSurrogate()->getUln();
        }

        return null;
    }


    private function getPredicateDetails(Animal $animal, $formattedPredicate): array
    {
        return [
            JsonInputConstant::FORMATTED => $formattedPredicate,
            JsonInputConstant::TYPE => $animal->getPredicate(),
            JsonInputConstant::SCORE => $animal->getPredicateScore(),
        ];
    }


    /**
     * Warning! DO NOT save any entity after calling this function.
     */
    private function getContactData(?Location $location): ?array
    {
        if ($location) {
            $address = $location->getAddress();
            if ($address) {
                $countryDetails = $address->getCountryDetails();
                if ($countryDetails) {
                    $translatedCountryName = $this->getTranslator()->trans($countryDetails->getName());
                    $location->getCountryDetails()->setName($translatedCountryName);
                }
            }

            return $this->getSerializer()->getDecodedJson($location, [JmsGroup::ANIMAL_DETAILS]);
        }
        return null;
    }


    /**
     * @param Person $user
     * @return array|string[]
     */
    private function getOwnerUbns(Person $user)
    {
        if (!$this->ownerUbns) {
            if ($user instanceof Client) {
                $this->ownerUbns = $user->getUbns();
            } else {
                $this->ownerUbns = [];
            }
        }

        return $this->ownerUbns;
    }


    /**
     * @param ViewMinimalParentDetails $minimalParentDetails
     * @param Person $user
     * @return bool
     */
    private function isHistoricAnimalOfOwner(ViewMinimalParentDetails $minimalParentDetails, Person $user)
    {
        if (!($user instanceof Client)) {
            return false;
        }

        foreach ($minimalParentDetails->getHistoricUbnsAsArray() as $historicUbn) {
            if (in_array($historicUbn, $this->getOwnerUbns($user))) {
                return true;
            }
        }
        return false;
    }


    /**
     * @param ViewMinimalParentDetails $animal
     * @param Person $person
     * @param Location|null $location
     * @return bool
     */
    public static function isUserAllowedToAccessAnimalDetails(ViewMinimalParentDetails $animal, Person $person, ?Location $location)
    {
        if ($person instanceof Employee) {
            return true;
        }

        if (!($person instanceof Client) || !$location || !$location->getCompany() || !$location->getId()) {
            return false;
        }

        $company = $location->getCompany();
        $currentUbnsOfUser = $company->getUbns(true);
        if (empty($currentUbnsOfUser)) {
            return false;
        }

        return Validator::isUserAllowedToAccessAnimalDetails($animal, $company, $currentUbnsOfUser, $location->getId());
    }


    /**
     * @param Animal $animal
     * @param Person $user
     * @param Location|null $location
     * @return array
     * @throws \Exception
     */
    public function getChildrenOutput(Animal $animal, Person $user, ?Location $location): array
    {
        $this->validateLocation($user, $location);
        return $this->getChildren($animal, $user, $location);
    }


    /**
     * @param Animal $animal
     * @param Person $user
     * @param Location|null $location
     * @return array
     * @throws \Exception
     */
    private function getChildren(Animal $animal, Person $user, ?Location $location): array
    {
        /** @var ViewMinimalParentDetailsRepository $viewMinimalParentDetailsRepository */
        $viewMinimalParentDetailsRepository = $this->getSqlViewManager()->get(ViewMinimalParentDetails::class);

        $genderPrimaryParent = $animal->getGender();

        $company = $location ? $location->getCompany() : null;
        $childrenArray = [];

        if ($animal instanceof ParentInterface) {

            $sortedChildren = $animal->getChildren()->matching(AnimalCriteria::sortByDateOfBirthAndAnimalOrderNumber(true));

            foreach ($sortedChildren as $child) {

                $childArray = $this->getSerializer()->getDecodedJson($child, [JmsGroup::CHILD],true);

                /** @var ViewMinimalParentDetails $viewDetails */
                $viewDetails = $viewMinimalParentDetailsRepository->findOneByAnimalId($child->getId());
                if ($viewDetails) {
                    $childArray[JsonInputConstant::PRODUCTION] = $viewDetails->getProduction();
                    $childArray[JsonInputConstant::N_LING] = $viewDetails->getNLing();
                    $childArray[JsonInputConstant::GENERAL_APPEARANCE] = $viewDetails->getGeneralAppearance();
                    $childArray[JsonInputConstant::IS_PUBLIC] = $viewDetails->isPublic();
                    $childArray[JsonInputConstant::IS_OWN_HISTORIC_ANIMAL] = $this->isHistoricAnimalOfOwner($viewDetails, $user);
                    $childArray[ReportLabel::IS_USER_ALLOWED_TO_ACCESS_ANIMAL_DETAILS] =
                        UlnValidator::isUserAllowedToAccessAnimalDetails($viewDetails, $user, $company);
                }

                switch ($genderPrimaryParent) {
                    case GenderType::FEMALE:
                        $secondaryParent = $child->getParentFather();
                        $secondaryParentKey = 'parent_father';
                        break;

                    case GenderType::MALE:
                        $secondaryParent = $child->getParentMother();
                        $secondaryParentKey = 'parent_mother';
                        break;

                    default:
                        $secondaryParent = null;
                        $secondaryParentKey = null;
                        break;
                }

                if ($secondaryParent && $secondaryParentKey) {
                    $childArray[$secondaryParentKey] = $this->getSerializer()->getDecodedJson($secondaryParent, [JmsGroup::PARENT_OF_CHILD],true);;
                }

                $childrenArray[] = $childArray;
            }

        }

        return $childrenArray;
    }


    /**
     * @param Animal $animal
     * @param string $replacementText
     * @return array
     */
    private function getLog(Animal $animal, $replacementText)
    {
        return $this->getManager()->getRepository(DeclareBase::class)->getLog($animal, $replacementText);
    }

}
