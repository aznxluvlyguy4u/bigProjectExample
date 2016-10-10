<?php

namespace AppBundle\Output;


use AppBundle\Component\Utils;
use AppBundle\Constant\BreedValueLabel;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\BodyFat;
use AppBundle\Entity\BreedValuesSet;
use AppBundle\Entity\BreedValuesSetRepository;
use AppBundle\Entity\Exterior;
use AppBundle\Entity\GeneticBase;
use AppBundle\Entity\GeneticBaseRepository;
use AppBundle\Entity\MuscleThickness;
use AppBundle\Entity\TailLength;
use AppBundle\Entity\Weight;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\ExpressionLanguage\Tests\Node\Obj;

/**
 * Class DeclareAnimalDetailsOutput
 */
class AnimalDetailsOutput
{
    /**
     * @param ObjectManager $em
     * @param Animal $animal
     * @return array
     */
    public static function create(ObjectManager $em, Animal $animal)
    {
        $replacementString = "";

        $mother = $animal->getParentMother();
        if($mother == null) {
            $ulnMother = $replacementString;
        } else {
            $ulnMother = Utils::getUlnStringFromAnimal($mother);
        }

        $father = $animal->getParentFather();
        if($father == null) {
            $ulnFather = $replacementString;
        } else {
            $ulnFather = Utils::getUlnStringFromAnimal($father);
        }

        $litter = $animal->getLitter();
        if($litter == null) {
            $litterSize = $replacementString;
        } else {
            $litterSize = $litter->getSize();
        }

        $exteriors = $animal->getExteriorMeasurements();
        if(sizeof($exteriors) == 0) {
            $exteriorDate = '';
            $skull = 0.00;
            $progress = 0.00;
            $muscularity = 0.00;
            $proportion = 0.00;
            $type = 0.00;
            $legWork = 0.00;
            $fur = 0.00;
            $generalAppearance = 0.00;
            $height = 0.00;
            $breastDepth = 0.00;
            $torsoLength = 0.00;
            $markings = 0.00;
            $kind = '';
        } else {
            /** @var Exterior $exterior */
            $exterior = $em->getRepository(Exterior::class)->getLatestExterior($animal);
            $exteriorDate = $exterior->getMeasurementDate();
            $skull = $exterior->getSkull();
            $progress = $exterior->getProgress();
            $muscularity = $exterior->getMuscularity();
            $proportion = $exterior->getProportion();
            $type = $exterior->getExteriorType();
            $legWork = $exterior->getLegWork();
            $fur = $exterior->getFur();
            $generalAppearance = $exterior->getGeneralAppearence();
            $height = $exterior->getHeight();
            $breastDepth = $exterior->getBreastDepth();
            $torsoLength = $exterior->getTorsoLength();
            $markings = $exterior->getMarkings();
            $kind = $exterior->getKind();
        }

        $bodyFats = $animal->getBodyFatMeasurements();
        if(sizeof($bodyFats) == 0) {
            $bodyFat = 0.00;
        } else {
            $bodyFat = $em->getRepository(BodyFat::class)->getLatestBodyFat($animal);
        }

        $weights = $animal->getWeightMeasurements();
        if(sizeof($weights) == 0) {
            $weight = 0.00;
            $birthWeight = 0.00;
        } else {
            $weight = $em->getRepository(Weight::class)->getLatestWeight($animal, false);
            $birthWeight = $em->getRepository(Weight::class)->getLatestBirthWeight($animal);
        }

        $muscleThicknesses = $animal->getMuscleThicknessMeasurements();
        if(sizeof($muscleThicknesses) == 0) {
            $muscleThickness = 0.00;
        } else {
            $muscleThickness = $em->getRepository(MuscleThickness::class)->getLatestMuscleThickness($animal);
        }

        $tailLengths = $animal->getTailLengthMeasurements();
        if(sizeof($tailLengths) == 0) {
            $tailLength = 0.00;
        } else {
            $tailLength = $em->getRepository(TailLength::class)->getLatestTailLength($animal);
        }

        $breeder = $animal->getBreeder();
        if($breeder == null) {
            $breederName = $replacementString;
            $breederUbn = $replacementString;
            $breederEmailAddress = $replacementString;
            $breederTelephoneNumber = $replacementString;
        } else {
            $breederName = $replacementString; //TODO replace with real value
            $breederUbn = $replacementString; //TODO replace with real value
            $breederEmailAddress = $replacementString; //TODO replace with real value
            $breederTelephoneNumber = $replacementString; //TODO replace with real value
        }

        $result = array(
                  Constant::ULN_COUNTRY_CODE_NAMESPACE =>      Utils::fillNullOrEmptyString($animal->getUlnCountryCode(), $replacementString),
                  Constant::ULN_NUMBER_NAMESPACE =>            Utils::fillNullOrEmptyString($animal->getUlnNumber(), $replacementString),
                  Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE => Utils::fillNullOrEmptyString($animal->getPedigreeCountryCode(), $replacementString),
                  Constant::PEDIGREE_NUMBER_NAMESPACE =>       Utils::fillNullOrEmptyString($animal->getPedigreeNumber(), $replacementString),
                  JsonInputConstant::WORK_NUMBER =>    Utils::fillNullOrEmptyString($animal->getAnimalOrderNumber(), $replacementString),
                  "collar_number" =>                   Utils::fillNullOrEmptyString("", $replacementString),
                  "name" =>                            Utils::fillNullOrEmptyString($animal->getName(), $replacementString),
                  Constant::DATE_OF_BIRTH_NAMESPACE => Utils::fillNullOrEmptyString($animal->getDateOfBirth(), $replacementString),
                  "inbred_coefficient" =>              Utils::fillNullOrEmptyString("", $replacementString),
                  Constant::GENDER_NAMESPACE =>        Utils::fillNullOrEmptyString($animal->getGender(), $replacementString),
                  "litter_size" =>                     Utils::fillNullOrEmptyString($litterSize, $replacementString),
                  Constant::MOTHER_NAMESPACE =>        Utils::fillNullOrEmptyString($ulnMother, $replacementString),
                  Constant::FATHER_NAMESPACE =>        Utils::fillNullOrEmptyString($ulnFather, $replacementString),
                  "rearing" =>                         Utils::fillNullOrEmptyString("", $replacementString),
                  "suction_size" =>              Utils::fillNullOrEmptyString("", $replacementString),
                  "blind_factor" =>              Utils::fillNullOrEmptyString("", $replacementString),
                  "scrapie_genotype" =>          Utils::fillNullOrEmptyString($animal->getScrapieGenotype(), $replacementString),
                  "breed" =>                     Utils::fillNullOrEmptyString($animal->getBreedCode(), $replacementString),
                  "predicate" =>                 Utils::fillNullOrEmptyString("", $replacementString),
                  "breed_status" =>              Utils::fillNullOrEmptyString($animal->getBreedType(), $replacementString),
                  JsonInputConstant::IS_ALIVE => Utils::fillNullOrEmptyString($animal->getIsAlive(), $replacementString),
                "exterior" =>
                    array(
                        "measurement_date" =>      Utils::fillNullOrEmptyString($exteriorDate, $replacementString),
                        "head" =>               Utils::fillZero($skull, $replacementString),
                        "progress" =>           Utils::fillZero($progress, $replacementString),
                        "muscularity" =>        Utils::fillZero($muscularity, $replacementString),
                        "proportion" =>         Utils::fillZero($proportion, $replacementString),
                        "type" =>               Utils::fillZero($type, $replacementString),
                        "leg_work" =>           Utils::fillZero($legWork, $replacementString),
                        "pelt" =>               Utils::fillZero($fur, $replacementString),
                        "general_appearance" => Utils::fillZero($generalAppearance, $replacementString),
                        "height" =>             Utils::fillZero($height, $replacementString),
                        "breast_depth" =>       Utils::fillZero($breastDepth, $replacementString),
                        "torso_length" =>       Utils::fillZero($torsoLength, $replacementString),
                        "markings" =>           Utils::fillZero($markings, $replacementString),
                        "kind" =>               Utils::fillZero($kind, $replacementString)
                    ),
                "measurement" =>
                    array(
                        "measurement_date" =>   Utils::fillNullOrEmptyString($bodyFat['date'], $replacementString),
                        "fat_cover_one" =>      Utils::fillZero($bodyFat['one'], $replacementString),
                        "fat_cover_two" =>      Utils::fillZero($bodyFat['two'], $replacementString),
                        "fat_cover_three" =>    Utils::fillZero($bodyFat['three'], $replacementString),
                        "muscular_thickness" => Utils::fillZero($muscleThickness, $replacementString),
                        "scan_weight" =>        Utils::fillZero($weight, $replacementString),
                        "tail_length" =>        Utils::fillZero($tailLength, $replacementString),
                        "birth_weight" =>       Utils::fillZero($birthWeight, $replacementString),
                        "birth_progress" =>     Utils::fillZero("", $replacementString)
                    ),
                "breed_values" => self::createBreedValuesSetArray($em, $animal),
                "breeder" =>
                    array(
                        "breeder" =>       Utils::fillNullOrEmptyString($breederName, $replacementString),
                        "ubn_breeder" =>   Utils::fillNullOrEmptyString($breederUbn, $replacementString),
                        "email_address" => Utils::fillNullOrEmptyString($breederEmailAddress, $replacementString),
                        "telephone" =>     Utils::fillNullOrEmptyString($breederTelephoneNumber, $replacementString),
                        "co-owner" =>      Utils::fillNullOrEmptyString("", $replacementString) //TODO
                    ),
                "note" => Utils::fillNullOrEmptyString($animal->getNote(), $replacementString)
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

        /** @var BreedValuesSetRepository $breedValuesSetRepository */
        $breedValuesSetRepository = $em->getRepository(BreedValuesSet::class);

        /** @var GeneticBaseRepository $geneticBaseRepository */
        $geneticBaseRepository = $em->getRepository(GeneticBase::class);

        $years = $geneticBaseRepository->getAllYears();

        foreach($years as $year) {
            $geneticBases = $geneticBaseRepository->getNullCheckedGeneticBases($year);

            $correctedBreedValues = $breedValuesSetRepository->getBreedValuesCorrectedByGeneticBaseWithAccuracies($animal->getId(), $year, $geneticBases);

            $lambMeatIndexValues = $breedValuesSetRepository->getLambMeatIndexWithAccuracy($animal);
            
            $results[] = [
                'year' => $year,
                'growth' => $correctedBreedValues[BreedValueLabel::GROWTH],
                'muscle_thickness' => $correctedBreedValues[BreedValueLabel::MUSCLE_THICKNESS],
                'fat' => $correctedBreedValues[BreedValueLabel::FAT],
                'growth_accuracy' => $correctedBreedValues[BreedValueLabel::GROWTH_ACCURACY],
                'muscle_thickness_accuracy' => $correctedBreedValues[BreedValueLabel::MUSCLE_THICKNESS_ACCURACY],
                'fat_accuracy' => $correctedBreedValues[BreedValueLabel::FAT_ACCURACY],
                'lamb_meat_index' => $lambMeatIndexValues[BreedValueLabel::LAMB_MEAT_INDEX],
                'lamb_meat_index_accuracy' => $lambMeatIndexValues[BreedValueLabel::LAMB_MEAT_INDEX_ACCURACY]
            ];
        }

        return $results;
    }


}