<?php

namespace AppBundle\Output;


use AppBundle\Component\BreedGrading\BreedFormat;
use AppBundle\Component\Utils;
use AppBundle\Constant\BreedValueLabel;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\BodyFat;
use AppBundle\Entity\BodyFatRepository;
use AppBundle\Entity\BreedValuesSet;
use AppBundle\Entity\BreedValuesSetRepository;
use AppBundle\Entity\DeclareBase;
use AppBundle\Entity\DeclareBaseRepository;
use AppBundle\Entity\Exterior;
use AppBundle\Entity\ExteriorRepository;
use AppBundle\Entity\GeneticBase;
use AppBundle\Entity\GeneticBaseRepository;
use AppBundle\Entity\Location;
use AppBundle\Entity\MuscleThickness;
use AppBundle\Entity\MuscleThicknessRepository;
use AppBundle\Entity\PedigreeRepository;
use AppBundle\Entity\TailLength;
use AppBundle\Entity\TailLengthRepository;
use AppBundle\Entity\Weight;
use AppBundle\Entity\WeightRepository;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\BreedValueUtil;
use AppBundle\Util\PedigreeUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\ExpressionLanguage\Tests\Node\Obj;

/**
 * Class DeclareAnimalDetailsOutput
 */
class AnimalDetailsOutput
{
    const NESTED_GENERATION_LIMIT = 4;

    /**
     * @param ObjectManager|EntityManagerInterface $em
     * @param Animal $animal
     * @return array
     */
    public static function create(ObjectManager $em, Animal $animal)
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
        $bodyFatRepository = $em->getRepository(BodyFat::class);
        /** @var ExteriorRepository $exteriorRepository */
        $exteriorRepository = $em->getRepository(Exterior::class);
        /** @var WeightRepository $weightRepository */
        $weightRepository = $em->getRepository(Weight::class);
        /** @var MuscleThicknessRepository $muscleThicknessRepository */
        $muscleThicknessRepository = $em->getRepository(MuscleThickness::class);
        /** @var TailLengthRepository $tailLengthRepository */
        $tailLengthRepository = $em->getRepository(TailLength::class);
        /** @var AnimalRepository $animalRepository */
        $animalRepository = $em->getRepository(Animal::class);


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

        $ascendants = PedigreeUtil::findNestedParentsBySingleSqlQuery($em->getConnection(), [$animal->getId()],self::NESTED_GENERATION_LIMIT);

        $result = array(
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
            "predicate" => Utils::fillNullOrEmptyString("", $replacementString),
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
            "breed_values" => self::createBreedValuesSetArray($em, $animal),
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
            "declare_log" => self::getLog($em, $animal, $animal->getLocation(), $replacementString),
            "children" => $animalRepository->getOffspringLogDataBySql($animal, $replacementString),
            "ascendants" => ArrayUtil::get($animal->getUln(), $ascendants, []),
        );

        return $result;
    }


    /**
     * @param ObjectManager $em
     * @param Animal $animal
     * @return array
     */
    private static function createBreedValuesSetArray(ObjectManager $em, Animal $animal)
    {
        $results = array();

        //TODO include breedValues and breedIndices here

        return $results;
    }


    /**
     * @param ObjectManager $em
     * @param Animal $animal
     * @param Location $location
     * @param string $replacementText
     * @return array
     */
    private static function getLog(ObjectManager $em, Animal $animal, $location, $replacementText)
    {
        return $em->getRepository(DeclareBase::class)->getLog($animal, $location, $replacementText);
    }

}