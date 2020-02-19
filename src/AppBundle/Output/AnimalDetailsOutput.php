<?php

namespace AppBundle\Output;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Criteria\AnimalCriteria;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\BodyFat;
use AppBundle\Entity\BodyFatRepository;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareBase;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Exterior;
use AppBundle\Entity\ExteriorRepository;
use AppBundle\Entity\Location;
use AppBundle\Entity\MuscleThickness;
use AppBundle\Entity\MuscleThicknessRepository;
use AppBundle\Entity\ParentInterface;
use AppBundle\Entity\Person;
use AppBundle\Entity\TailLength;
use AppBundle\Entity\TailLengthRepository;
use AppBundle\Entity\Weight;
use AppBundle\Entity\WeightRepository;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\SqlView\Repository\ViewMinimalParentDetailsRepository;
use AppBundle\SqlView\View\ViewMinimalParentDetails;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\PedigreeUtil;
use AppBundle\Util\Validator;
use AppBundle\Validation\UlnValidator;
use Doctrine\Common\Persistence\ObjectManager;
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

        $mother = $animal->getParentMother();
        if ($mother == null) {
            $ulnMother = $replacementString;
        } else {
            $ulnMother = Utils::getUlnStringFromAnimal($mother);
        }

        $father = $animal->getParentFather();
        if ($father == null) {
            $ulnFather = $replacementString;
        } else {
            $ulnFather = Utils::getUlnStringFromAnimal($father);
        }

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

        /** @var BodyFatRepository $bodyFatRepository */
        $bodyFatRepository = $this->getManager()->getRepository(BodyFat::class);
        /** @var ExteriorRepository $exteriorRepository */
        $exteriorRepository = $this->getManager()->getRepository(Exterior::class);
        /** @var WeightRepository $weightRepository */
        $weightRepository = $this->getManager()->getRepository(Weight::class);
        /** @var MuscleThicknessRepository $muscleThicknessRepository */
        $muscleThicknessRepository = $this->getManager()->getRepository(MuscleThickness::class);
        /** @var TailLengthRepository $tailLengthRepository */
        $tailLengthRepository = $this->getManager()->getRepository(TailLength::class);
        /** @var AnimalRepository $animalRepository */
        $animalRepository = $this->getManager()->getRepository(Animal::class);
        /** @var ViewMinimalParentDetailsRepository $viewMinimalParentDetailsRepository */
        $viewMinimalParentDetailsRepository = $this->getSqlViewManager()->get(ViewMinimalParentDetails::class);

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

        $bodyFats = $animal->getBodyFatMeasurements();
        if (sizeof($bodyFats) == 0) {
            $bodyFat = 0.00;
        } else {
            $bodyFat = $bodyFatRepository->getLatestBodyFat($animal);
        }

        $weights = $animal->getWeightMeasurements();
        if (sizeof($weights) == 0) {
            $weight = 0.00;
            $birthWeight = 0.00;
        } else {
            $weight = $weightRepository->getLatestWeight($animal, false);
            $birthWeight = $weightRepository->getLatestBirthWeight($animal);
        }

        $muscleThicknesses = $animal->getMuscleThicknessMeasurements();
        if (sizeof($muscleThicknesses) == 0) {
            $muscleThickness = 0.00;
        } else {
            $muscleThickness = $muscleThicknessRepository->getLatestMuscleThickness($animal);
        }

        $tailLengths = $animal->getTailLengthMeasurements();
        if (sizeof($tailLengths) == 0) {
            $tailLength = 0.00;
        } else {
            $tailLength = $tailLengthRepository->getLatestTailLength($animal);
        }


        $breeder = null;
        $breederUbn = $replacementString;
        $breederName = $replacementString;
        $breederEmailAddress = $replacementString;
        $breederTelephoneNumber = $replacementString;
        $locationOfBirth = $animal->getLocationOfBirth();
        if($locationOfBirth != null) {
            $breederUbn = $locationOfBirth->getUbn();

            $breeder = $locationOfBirth->getOwner();
            if ($breeder != null) {
                $breederName = Utils::fillNullOrEmptyString($breeder->getFullName(), $replacementString);
                $breederEmailAddress = Utils::fillNullOrEmptyString($breeder->getEmailAddress(), $replacementString);
                $breederTelephoneNumber = Utils::fillNullOrEmptyString($breeder->getCellphoneNumber(), $replacementString);
            }
        }

        $company = $location ? $location->getCompany() : null;

        $result = [
        	  "id" => $animal->getId(),
            JsonInputConstant::UBN => $animal->getUbn(),
            Constant::ULN_COUNTRY_CODE_NAMESPACE => Utils::fillNullOrEmptyString($animal->getUlnCountryCode(), $replacementString),
            Constant::ULN_NUMBER_NAMESPACE => Utils::fillNullOrEmptyString($animal->getUlnNumber(), $replacementString),
            Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE => Utils::fillNullOrEmptyString($animal->getPedigreeCountryCode(), $replacementString),
            Constant::PEDIGREE_NUMBER_NAMESPACE => Utils::fillNullOrEmptyString($animal->getPedigreeNumber(), $replacementString),
            JsonInputConstant::WORK_NUMBER => Utils::fillNullOrEmptyString($animal->getAnimalOrderNumber(), $replacementString),
            "collar" => array ("color" => Utils::fillNullOrEmptyString($animal->getCollarColor(), $replacementString),
                "number" => Utils::fillNullOrEmptyString($animal->getCollarNumber(), $replacementString)),
            "name" => Utils::fillNullOrEmptyString($animal->getName(), $replacementString),
	          "nickname" => Utils::fillNullOrEmptyString($animal->getNickname(), $replacementString),
            Constant::DATE_OF_BIRTH_NAMESPACE => Utils::fillNullOrEmptyString($animal->getDateOfBirth(), $replacementString),
            Constant::DATE_OF_DEATH_NAMESPACE => Utils::fillNullOrEmptyString($animal->getDateOfDeath(), $replacementString),
            JsonInputConstant::INBREEDING_COEFFICIENT => Utils::fillNullOrEmptyString($inbreedingCoefficientValue, $replacementString),
            Constant::GENDER_NAMESPACE => Utils::fillNullOrEmptyString($animal->getGender(), $replacementString),
            "litter_size" => Utils::fillNullOrEmptyString($litterSize, $replacementString),
            JsonInputConstant::SUCKLE_COUNT => Utils::fillNullOrEmptyString($suckleCount, $replacementString),
            Constant::MOTHER_NAMESPACE => Utils::fillNullOrEmptyString($ulnMother, $replacementString),
            Constant::FATHER_NAMESPACE => Utils::fillNullOrEmptyString($ulnFather, $replacementString),
            "rearing" => Utils::fillNullOrEmptyString("", $replacementString),
            "suction_size" => Utils::fillNullOrEmptyString("", $replacementString),
            "blind_factor" => Utils::fillNullOrEmptyString("", $replacementString),
            "scrapie_genotype" => Utils::fillNullOrEmptyString($animal->getScrapieGenotype(), $replacementString),
            "breed" => Utils::fillNullOrEmptyString($animal->getBreedCode(), $replacementString),
            "predicate" => Utils::fillNullOrEmptyString($predicate, $replacementString),
            "breed_status" => Utils::fillNullOrEmptyString($animal->getBreedType(), $replacementString),
            JsonInputConstant::IS_ALIVE => Utils::fillNullOrEmptyString($animal->getIsAlive(), $replacementString),

            JsonInputConstant::COUNTRY_OF_BIRTH => Utils::fillNullOrEmptyString($translatedCountryName, $replacementString),

            "measurement" =>
                array(
                    "measurement_date" => Utils::fillNullOrEmptyString($bodyFat['date'], $replacementString),
                    "fat_cover_one" => Utils::fillZero($bodyFat['one'], $replacementString),
                    "fat_cover_two" => Utils::fillZero($bodyFat['two'], $replacementString),
                    "fat_cover_three" => Utils::fillZero($bodyFat['three'], $replacementString),
                    "muscular_thickness" => Utils::fillZero($muscleThickness, $replacementString),
                    "scan_weight" => Utils::fillZero($weight, $replacementString),
                    "tail_length" => Utils::fillZero($tailLength, $replacementString),
                    "birth_weight" => Utils::fillZero($birthWeight, $replacementString),
                    "birth_progress" => Utils::fillZero("", $replacementString)
                ),
            "breed_values" => $this->breedValuesOutput->get($animal),
            "breeder" =>
                array(
                    "breeder" => Utils::fillNullOrEmptyString($breederName, $replacementString),
                    "ubn_breeder" => Utils::fillNullOrEmptyString($breederUbn, $replacementString),
                    "email_address" => Utils::fillNullOrEmptyString($breederEmailAddress, $replacementString),
                    "telephone" => Utils::fillNullOrEmptyString($breederTelephoneNumber, $replacementString),
                    "co-owner" => Utils::fillNullOrEmptyString("", $replacementString) //TODO
                ),
            "note" => Utils::fillNullOrEmptyString($animal->getNote(), $replacementString),
            "body_fats" => $bodyFatRepository->getAllOfAnimalBySql($animal, $replacementString),
            "exteriors" => $exteriorRepository->getAllOfAnimalBySql($animal, $replacementString),
            "muscle_thicknesses" => $muscleThicknessRepository->getAllOfAnimalBySql($animal, $replacementString),
            "weights" => $weightRepository->getAllOfAnimalBySql($animal, $replacementString),
            "tail_lengths" => $tailLengthRepository->getAllOfAnimalBySql($animal, $replacementString),
            "declare_log" => $this->getLog($animal, $replacementString),
            "child_count" => $animalRepository->offspringCount($animal),
            "production" => $production,
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
