<?php

namespace AppBundle\Output;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\BodyFat;
use AppBundle\Entity\BodyFatRepository;
use AppBundle\Entity\DeclareBase;
use AppBundle\Entity\Exterior;
use AppBundle\Entity\ExteriorRepository;
use AppBundle\Entity\Location;
use AppBundle\Entity\MuscleThickness;
use AppBundle\Entity\MuscleThicknessRepository;
use AppBundle\Entity\ParentInterface;
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
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class DeclareAnimalDetailsOutput
 */
class AnimalDetailsOutput extends OutputServiceBase
{
    const NESTED_GENERATION_LIMIT = 4;

    /** @var BreedValuesOutput */
    private $breedValuesOutput;

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
     * @param bool $includeAscendants
     * @return array
     * @throws \Exception
     */
    public function getForUserEnvironment(Animal $animal, $includeAscendants = false)
    {
        return $this->get($animal, $includeAscendants);
    }

    /**
     * @param Animal $animal
     * @param boolean $includeAscendants
     * @return array
     * @throws \Exception
     */
    public function get(Animal $animal, $includeAscendants = false)
    {
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

        $litter = $animal->getLitter();
        if ($litter == null) {
            $litterSize = $replacementString;
        } else {
            $litterSize = $litter->getSize();
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

        $result = [
            JsonInputConstant::UBN => $animal->getUbn(),
            Constant::ULN_COUNTRY_CODE_NAMESPACE => Utils::fillNullOrEmptyString($animal->getUlnCountryCode(), $replacementString),
            Constant::ULN_NUMBER_NAMESPACE => Utils::fillNullOrEmptyString($animal->getUlnNumber(), $replacementString),
            Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE => Utils::fillNullOrEmptyString($animal->getPedigreeCountryCode(), $replacementString),
            Constant::PEDIGREE_NUMBER_NAMESPACE => Utils::fillNullOrEmptyString($animal->getPedigreeNumber(), $replacementString),
            JsonInputConstant::WORK_NUMBER => Utils::fillNullOrEmptyString($animal->getAnimalOrderNumber(), $replacementString),
            "collar" => array ("color" => Utils::fillNullOrEmptyString($animal->getCollarColor(), $replacementString),
                "number" => Utils::fillNullOrEmptyString($animal->getCollarNumber(), $replacementString)),
            "name" => Utils::fillNullOrEmptyString($animal->getName(), $replacementString),
            Constant::DATE_OF_BIRTH_NAMESPACE => Utils::fillNullOrEmptyString($animal->getDateOfBirth(), $replacementString),
            "inbred_coefficient" => Utils::fillNullOrEmptyString("", $replacementString),
            Constant::GENDER_NAMESPACE => Utils::fillNullOrEmptyString($animal->getGender(), $replacementString),
            "litter_size" => Utils::fillNullOrEmptyString($litterSize, $replacementString),
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
            "declare_log" => $this->getLog($animal, $animal->getLocation(), $replacementString),
            "children" => $this->getChildren($animal),
            "production" => $production,
        ];

        if ($fatherId) {
            $result["parent_father"] = $viewMinimalParentDetails->get($fatherId);
        }

        if ($motherId) {
            $result["parent_mother"] = $viewMinimalParentDetails->get($motherId);
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

        return $result;
    }


    private function getChildren(Animal $animal)
    {
        /** @var ViewMinimalParentDetailsRepository $viewMinimalParentDetailsRepository */
        $viewMinimalParentDetailsRepository = $this->getSqlViewManager()->get(ViewMinimalParentDetails::class);

        $genderPrimaryParent = $animal->getGender();

        $children = [];

        if ($animal instanceof ParentInterface) {

            foreach ($animal->getChildren() as $child) {

                $childArray = $this->getSerializer()->getDecodedJson($child, [JmsGroup::CHILD],true);

                /** @var ViewMinimalParentDetails $viewDetails */
                $viewDetails = $viewMinimalParentDetailsRepository->findOneByAnimalId($child->getId());
                if ($viewDetails) {
                    $childArray[JsonInputConstant::PRODUCTION] = $viewDetails->getProduction();
                    $childArray[JsonInputConstant::N_LING] = $viewDetails->getNLing();
                    $childArray[JsonInputConstant::GENERAL_APPEARANCE] = $viewDetails->getGeneralAppearance();
                    $childArray[JsonInputConstant::IS_PUBLIC] = $viewDetails->isPublic();
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

                $children[] = $childArray;
            }

        }

        return $children;
    }


    /**
     * @param ObjectManager $this->getManager()
     * @param Animal $animal
     * @param Location $location
     * @param string $replacementText
     * @return array
     */
    private function getLog(Animal $animal, $location, $replacementText)
    {
        return $this->getManager()->getRepository(DeclareBase::class)->getLog($animal, $location, $replacementText);
    }

}