<?php

namespace AppBundle\Report;


use AppBundle\Component\BreedGrading\BreedFormat;
use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\CompanyAddress;
use AppBundle\Entity\Exterior;
use AppBundle\Entity\ExteriorRepository;
use AppBundle\Entity\Litter;
use AppBundle\Entity\LitterRepository;
use AppBundle\Entity\LocationAddress;
use AppBundle\Entity\PedigreeRegister;
use AppBundle\Entity\PedigreeRegisterRepository;
use AppBundle\Enumerator\AnimalType;
use AppBundle\Enumerator\AnimalTypeInLatin;
use AppBundle\Util\DisplayUtil;
use AppBundle\Util\NullChecker;
use AppBundle\Util\StarValueUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\Translation;
use AppBundle\Util\TwigOutputUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class PedigreeCertificate
{
    const SHOW_PREDICATE_IN_REPORT = true;
    const SHOW_BLINDNESS_FACTOR_IN_REPORT = true;
    const SHOW_NICKNAME = true;
    const MAX_LENGTH_FULL_NAME = 30;
    const MAX_LENGTH_CITY_NAME = 16;
    const MISSING_PEDIGREE_REGISTER = '';
    const EMPTY_DATE_OF_BIRTH = '-';
    const GENERAL_NULL_FILLER = '-';

    const LITTER_SIZE = 'litterSize';
    const LITTER_GROUP = 'litterGroup';
    const N_LING = 'nLing';

    const STARS_NULL_VALUE = null;
    const EMPTY_SCRAPIE_GENOTYPE = '-/-';

    const GENERATION_OF_ASCENDANTS = 3;

    /** @var array */
    private $data;
    /** @var  ObjectManager */
    private $em;
    /** @var Connection */
    private $conn;
    /** @var LitterRepository */
    private $litterRepository;
    /** @var ExteriorRepository */
    private $exteriorRepository;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();

        $this->litterRepository = $em->getRepository(Litter::class);
        $this->exteriorRepository = $em->getRepository(Exterior::class);

        $this->translator = $translator;
    }


    /**
     * @param string $ubn
     * @param int $animalId
     * @param string $trimmedClientName
     * @param CompanyAddress $companyAddress
     * @param string $breedValuesLastGenerationDate
     * @return array
     */
    public function generate($ubn, $animalId, $trimmedClientName, $companyAddress,
                                $breedValuesLastGenerationDate)
    {
        $this->data = array();

        //Set Default Owner details
        $this->data[ReportLabel::OWNER_NAME] = $trimmedClientName != null ? $trimmedClientName: self::GENERAL_NULL_FILLER;
        $this->data[ReportLabel::ADDRESS] = $companyAddress != null ? $companyAddress : $this->getEmptyLocationAddress();
        $postalCode = $companyAddress != null ? StringUtil::addSpaceInDutchPostalCode($companyAddress->getPostalCode(), self::GENERAL_NULL_FILLER) : self::GENERAL_NULL_FILLER;
        $this->data[ReportLabel::POSTAL_CODE] = $postalCode;
        $this->data[ReportLabel::UBN] = $ubn != null ? $ubn : self::GENERAL_NULL_FILLER;
        //Set CurrentOwner Details!
        $this->setOwnerDataFromAnimalIdBySql($animalId);

        //TODO Phase 2: Add breeder information
        $this->data[ReportLabel::BREEDER] = null; //TODO delete this from twig file
        $this->setBreederDataFromAnimalIdBySql($animalId);

        /** @var PedigreeRegisterRepository $pedigreeRegisterRepository */
        $pedigreeRegisterRepository = $this->em->getRepository(PedigreeRegister::class);
        $this->data[ReportLabel::PEDIGREE_REGISTER_NAME] = $this->parsePedigreeRegisterText($pedigreeRegisterRepository->getFullnameByAnimalId($animalId));

        // Add shared data
        $this->data[ReportLabel::BREED_VALUES_EVALUATION_DATE] = $breedValuesLastGenerationDate;

        $keyAnimal = ReportLabel::CHILD_KEY;

        $generation = 0;
        $this->addAnimalValues($keyAnimal, $animalId, $generation);
        $this->addParents($animalId, $keyAnimal, $generation);

        return $this->getData();
    }


    /**
     * @param $animalId
     */
    private function setOwnerDataFromAnimalIdBySql($animalId)
    {
        if(is_string($animalId) || is_int($animalId)) {
            $sql = "SElECT l.ubn, c.company_name, d.street_name, d.address_number, d.address_number_suffix, d.postal_code, d.city FROM animal a
                INNER JOIN location l ON a.location_id = l.id
                INNER JOIN company c ON l.company_id = c.id
                INNER JOIN address d ON d.id = c.address_id
                WHERE a.id = ".intval($animalId);
            $result = $this->em->getConnection()->query($sql)->fetch();

            $currentUbnOfAnimal = is_array($result) ? Utils::getNullCheckedArrayValue('ubn', $result) : null;

            if($currentUbnOfAnimal == $this->data[ReportLabel::UBN]) {
                return; //just use current default values

            } elseif($currentUbnOfAnimal == null) {
                //Set all owner values as empty
                $this->data[ReportLabel::OWNER_NAME] = self::GENERAL_NULL_FILLER;
                $this->data[ReportLabel::ADDRESS] = $this->getEmptyLocationAddress();
                $this->data[ReportLabel::POSTAL_CODE] = self::GENERAL_NULL_FILLER;
                $this->data[ReportLabel::UBN] = self::GENERAL_NULL_FILLER;

            } else {
                //Use currentOwner values
                $companyName = Utils::fillNullOrEmptyString(Utils::getNullCheckedArrayValue('company_name', $result), self::GENERAL_NULL_FILLER);
                $streetName = Utils::fillNullOrEmptyString(Utils::getNullCheckedArrayValue('street_name', $result), self::GENERAL_NULL_FILLER);
                $addressNumber = Utils::fillNullOrEmptyString(Utils::getNullCheckedArrayValue('address_number', $result), self::GENERAL_NULL_FILLER);
                $addressNumberSuffix = Utils::fillNullOrEmptyString(Utils::getNullCheckedArrayValue('address_number_suffix', $result), '');
                $rawPostalCode = Utils::getNullCheckedArrayValue('postal_code', $result);
                $postalCode = Utils::fillNullOrEmptyString($rawPostalCode, self::GENERAL_NULL_FILLER);
                $city = Utils::fillNullOrEmptyString(Utils::getNullCheckedArrayValue('city', $result), self::GENERAL_NULL_FILLER);

                $address = new LocationAddress();
                $address->setStreetName($streetName);
                $address->setAddressNumber($addressNumber);
                $address->setAddressNumberSuffix($addressNumberSuffix);
                $address->setPostalCode($postalCode);
                $address->setCity($city);

                $this->data[ReportLabel::OWNER_NAME] = StringUtil::trimStringWithAddedEllipsis($companyName, PedigreeCertificates::MAX_LENGTH_FULL_NAME);
                $this->data[ReportLabel::ADDRESS] = $address;
                $this->data[ReportLabel::POSTAL_CODE] = StringUtil::addSpaceInDutchPostalCode($rawPostalCode, self::GENERAL_NULL_FILLER);
                $this->data[ReportLabel::UBN] = $currentUbnOfAnimal;
            }
        }
    }


    /**
     * @param $animalId
     */
    private function setBreederDataFromAnimalIdBySql($animalId)
    {
        if(is_string($animalId) || is_int($animalId)) {
            $sql = "SElECT l.ubn, c.company_name, d.street_name, d.address_number, d.address_number_suffix, d.postal_code, d.city, n.breeder_number, n.source, a.ubn_of_birth FROM animal a
                      INNER JOIN location l ON a.location_of_birth_id = l.id
                      LEFT JOIN company c ON l.company_id = c.id
                      LEFT JOIN address d ON d.id = c.address_id
                      LEFT JOIN breeder_number n ON n.ubn_of_birth = a.ubn_of_birth
                    WHERE a.id = ".intval($animalId)."
                    ORDER BY n.source DESC LIMIT 1";
            $result = $this->em->getConnection()->query($sql)->fetch();

            if(!is_array($result)) {
                //Set all breeder values as empty
                $this->data[ReportLabel::BREEDER_NAME] = self::GENERAL_NULL_FILLER;
                $this->data[ReportLabel::ADDRESS_BREEDER] = $this->getEmptyLocationAddress();
                $this->data[ReportLabel::POSTAL_CODE_BREEDER] = self::GENERAL_NULL_FILLER;
                $this->data[ReportLabel::BREEDER_NUMBER] = self::GENERAL_NULL_FILLER;
            } else {
                $ubnOfBreeder = Utils::getNullCheckedArrayValue('ubn_of_birth', $result);

                //Use currentOwner values
                $breederNumber = Utils::fillNullOrEmptyString(Utils::getNullCheckedArrayValue('breeder_number', $result), self::GENERAL_NULL_FILLER);
                $companyName = Utils::fillNullOrEmptyString(Utils::getNullCheckedArrayValue('company_name', $result), self::GENERAL_NULL_FILLER);
                $streetName = Utils::fillNullOrEmptyString(Utils::getNullCheckedArrayValue('street_name', $result), self::GENERAL_NULL_FILLER);
                $addressNumber = Utils::fillNullOrEmptyString(Utils::getNullCheckedArrayValue('address_number', $result), self::GENERAL_NULL_FILLER);
                $addressNumberSuffix = Utils::fillNullOrEmptyString(Utils::getNullCheckedArrayValue('address_number_suffix', $result), '');
                $rawPostalCode = Utils::getNullCheckedArrayValue('postal_code', $result);
                $postalCode = Utils::fillNullOrEmptyString($rawPostalCode, self::GENERAL_NULL_FILLER);
                $city = Utils::fillNullOrEmptyString(Utils::getNullCheckedArrayValue('city', $result), self::GENERAL_NULL_FILLER);

                $address = new LocationAddress();
                $address->setStreetName($streetName);
                $address->setAddressNumber($addressNumber);
                $address->setAddressNumberSuffix($addressNumberSuffix);
                $address->setPostalCode($postalCode);
                $address->setCity($city);

                $this->data[ReportLabel::BREEDER_NAME] = StringUtil::trimStringWithAddedEllipsis($companyName, PedigreeCertificates::MAX_LENGTH_FULL_NAME);
                $this->data[ReportLabel::ADDRESS_BREEDER] = $address;
                $this->data[ReportLabel::POSTAL_CODE_BREEDER] = StringUtil::addSpaceInDutchPostalCode($rawPostalCode, self::GENERAL_NULL_FILLER);
                $this->data[ReportLabel::BREEDER_NUMBER] = $ubnOfBreeder;
            }
        }
    }
    

    /**
     * @return LocationAddress
     */
    private function getEmptyLocationAddress()
    {
        $emptyAddress = new LocationAddress();
        $emptyAddress->setStreetName('-');
        $emptyAddress->setAddressNumber('-');
        $emptyAddress->setAddressNumberSuffix('');
        $emptyAddress->setCity('-');
        $emptyAddress->setPostalCode('-');
        return $emptyAddress;
    }
    
    
    /**
     * Recursively add the previous generations of ascendants.
     *
     * @param int $animalId
     * @param string $keyAnimal
     * @param int $generation
     */
    private function addParents($animalId = null, $keyAnimal, $generation)
    {
        if($generation < self::GENERATION_OF_ASCENDANTS) {

            if($animalId != null) {
                $fatherId = $this->data[ReportLabel::ANIMALS][$keyAnimal][ReportLabel::FATHER_ID];
                $motherId = $this->data[ReportLabel::ANIMALS][$keyAnimal][ReportLabel::MOTHER_ID];
            } else {
                $fatherId = null;
                $motherId = null;
            }

            $keyFather = self::getFatherKey($keyAnimal);
            $keyMother = self::getMotherKey($keyAnimal);

            $this->addAnimalValuesBySql($keyFather, $fatherId, $generation);
            $this->addAnimalValuesBySql($keyMother, $motherId, $generation);

            $generation++;

            //Recursive loop for both parents AFTER increasing the generationCount
            $this->addParents($fatherId, $keyFather, $generation);
            $this->addParents($motherId, $keyMother, $generation);
        }
    }

    /**
     * @param string $keyAnimal
     * @return string
     */
    public static function getFatherKey($keyAnimal)
    {
        if($keyAnimal == ReportLabel::CHILD_KEY) {
            $keyFather = ReportLabel::FATHER_KEY;
        } else {
            $keyFather = $keyAnimal . ReportLabel::FATHER_KEY;
        }

        return $keyFather;
    }

    /**
     * @param string $keyAnimal
     * @return string
     */
    public static function getMotherKey($keyAnimal)
    {
        if($keyAnimal == ReportLabel::CHILD_KEY) {
            $keyMother = ReportLabel::MOTHER_KEY;
        } else {
            $keyMother = $keyAnimal . ReportLabel::MOTHER_KEY;
        }

        return $keyMother;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }


    /**
     * @param string $key
     * @param int $animalId
     * @param int $generation
     */
    private function addAnimalValues($key, $animalId, $generation)
    {
        $sql = "SELECT * FROM animal_cache c
                  LEFT JOIN result_table_breed_grades r ON r.animal_id = c.animal_id
                WHERE c.animal_id = ".$animalId;
        $animalCache = $this->conn->query($sql)->fetch();

        if($animalCache) {

            $animalTypeInLatin = null;
            $animalType = null;

            if($animalId != null) {
                $sql = "SELECT a.id, CONCAT(a.uln_country_code, a.uln_number) as uln, CONCAT(a.pedigree_country_code, a.pedigree_number) as stn,
                    scrapie_genotype, breed, breed_type, breed_code, date_of_birth, gender, predicate, predicate_score,
                    parent_father_id as father_id, parent_mother_id as mother_id, blindness_factor, c.company_name, d.city,
                    a.nickname,
                    a.animal_type,
                    birth_address.country as country_of_birth,
                    pedigree_code.code as breed_code_letters,
                    pedigree_code.full_name as breed_code_fullname
                FROM animal a
                    LEFT JOIN location l ON a.location_of_birth_id = l.id
                    LEFT JOIN company c ON l.company_id = c.id
                    LEFT JOIN address d ON d.id = c.address_id
                    LEFT JOIN address birth_address ON l.address_id = birth_address.id
                    LEFT JOIN pedigree_code ON pedigree_code.code = substring(a.breed_code, 1, 2)
                WHERE a.id = ".$animalId;
                $animalData = $this->conn->query($sql)->fetch();

                //AnimalData
                $uln = $animalData[JsonInputConstant::ULN];
                $stn = $animalData[JsonInputConstant::STN];
                $breed = $animalData[JsonInputConstant::BREED];
                $breedCode = $animalData[JsonInputConstant::BREED_CODE];
                $breedCodeLetters = $animalData[JsonInputConstant::BREED_CODE_LETTERS];
                $breedCodeFullname = $animalData[JsonInputConstant::BREED_CODE_FULLNAME];
                $countryOfBirth = $animalData[JsonInputConstant::COUNTRY_OF_BIRTH];
                $breedType = $animalData[JsonInputConstant::BREED_TYPE];
                $scrapieGenotype = $animalData[JsonInputConstant::SCRAPIE_GENOTYPE];
                $gender = $animalData[JsonInputConstant::GENDER];

                $animalTypeDatabaseIntValue = $animalData[JsonInputConstant::ANIMAL_TYPE];
                if ($animalTypeDatabaseIntValue) {
                    $animalTypeInLatin = AnimalTypeInLatin::getByDatabaseEnum($animalTypeDatabaseIntValue);
                    $animalType = strtoupper($this->translator->trans(AnimalType::getByDatabaseEnum($animalTypeDatabaseIntValue)));
                }

                $nickname = null;
                if(self::SHOW_NICKNAME) {
                    $nickname = $animalData[JsonInputConstant::NICKNAME];
                }

                $predicate = self::GENERAL_NULL_FILLER;
                if(self::SHOW_PREDICATE_IN_REPORT) {
                    $formattedPredicate = DisplayUtil::parsePredicateString($animalData[JsonInputConstant::PREDICATE], $animalData[JsonInputConstant::PREDICATE_SCORE]);
                    $predicate = $formattedPredicate != null ? $formattedPredicate : self::GENERAL_NULL_FILLER;
                }
                $blindnessFactor = self::GENERAL_NULL_FILLER;
                if(self::SHOW_BLINDNESS_FACTOR_IN_REPORT) {
                    $formattedBlindnessFactor = Translation::getDutchUcFirst($animalData[JsonInputConstant::BLINDNESS_FACTOR]);
                    $blindnessFactor = $formattedBlindnessFactor != null ? $formattedBlindnessFactor : self::GENERAL_NULL_FILLER;
                }

                $dateOfBirthString = self::EMPTY_DATE_OF_BIRTH;
                $dateOfBirthDateTime = null;
                if($animalData[JsonInputConstant::DATE_OF_BIRTH] != null) {
                    $dateOfBirthDateTime = new \DateTime($animalData[JsonInputConstant::DATE_OF_BIRTH]);
                    $dateOfBirthString = $dateOfBirthDateTime->format('d-m-Y');
                }

                $companyName = $animalData[JsonInputConstant::COMPANY_NAME];
                $companyName = StringUtil::trimStringWithAddedEllipsis($companyName, self::MAX_LENGTH_FULL_NAME);
                $city = $animalData[JsonInputConstant::CITY];
                $city = StringUtil::trimStringWithAddedEllipsis($city, self::MAX_LENGTH_CITY_NAME);

                //These ids are only used only inside this class and not in the twig file
                $this->data[ReportLabel::ANIMALS][$key][ReportLabel::MOTHER_ID] = $animalData[ReportLabel::MOTHER_ID];
                $this->data[ReportLabel::ANIMALS][$key][ReportLabel::FATHER_ID] = $animalData[ReportLabel::FATHER_ID];
            } else {
                $uln = null;
                $stn = null;
                $scrapieGenotype = null;
                $breed = null;
                $breedCode = null;
                $breedType = null;
                $scrapieGenotype = null;
                $gender = null;
                $animalTypeInLatin = null;
                $breedCodeLetters = null;
                $breedCodeFullname = null;
                $countryOfBirth = null;
                $nickname = null;
                $predicate = self::GENERAL_NULL_FILLER;
                $blindnessFactor = self::GENERAL_NULL_FILLER;

                $dateOfBirthString = self::EMPTY_DATE_OF_BIRTH;
                $dateOfBirthDateTime = null;

                $companyName = null;
                $city = null;

                //These ids are only used only inside this class and not in the twig file
                $this->data[ReportLabel::ANIMALS][$key][ReportLabel::MOTHER_ID] = null;
                $this->data[ReportLabel::ANIMALS][$key][ReportLabel::FATHER_ID] = null;
            }

            //ProductionValues
            $productionAge = intval($animalCache['production_age']);
            $litterCount = intval($animalCache['litter_count']);
            $totalOffSpringCount = intval($animalCache['total_offspring_count']);
            $bornAliveOffspringCount = intval($animalCache['born_alive_offspring_count']);
            $addProductionAsterisk = boolval($animalCache['gave_birth_as_one_year_old']);
            $production = DisplayUtil::parseProductionStringFromGivenParts($productionAge, $litterCount, $totalOffSpringCount, $bornAliveOffspringCount, $addProductionAsterisk);

            $nLing = $animalCache['n_ling'];
            $nLingPart = explode('-', $nLing);
            $litterSize = 0;
            if(count($nLingPart) > 0) {
                $litterSize = intval($nLingPart[0]);
            }

            $breederName = self::GENERAL_NULL_FILLER;
            if($companyName != null && $city != null) {
                $breederName = $companyName.'; '.$city;
            }

            if($generation < self::GENERATION_OF_ASCENDANTS - 1) {
                //Only retrieve the breedValues and lambMeatIndices for the child, parents and grandparents.
                $this->addBreedValuesToArrayFromSqlResult($key, $animalCache);
            }


            $exteriorMeasurementDateString = $animalCache[JsonInputConstant::EXTERIOR_MEASUREMENT_DATE];
            if($exteriorMeasurementDateString == null) {
                $exteriorMeasurementDate = null;
            } else {
                $exteriorMeasurementDate = new \DateTime($exteriorMeasurementDateString);
            }

            /* Set values into array */
            //Note the BreedValues and LambMeatIndex values are already set above

            //Exterior
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::SKULL] = Utils::fillZeroFloat($animalCache[JsonInputConstant::SKULL]);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::DEVELOPMENT] = Utils::fillZeroFloat($animalCache[JsonInputConstant::PROGRESS]);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::MUSCULARITY] = Utils::fillZeroFloat($animalCache[JsonInputConstant::MUSCULARITY]);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::PROPORTION] = Utils::fillZeroFloat($animalCache[JsonInputConstant::PROPORTION]);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::TYPE] = Utils::fillZeroFloat($animalCache[JsonInputConstant::EXTERIOR_TYPE]);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::LEGWORK] = Utils::fillZeroFloat($animalCache[JsonInputConstant::LEG_WORK]);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::FUR] = Utils::fillZeroFloat($animalCache[JsonInputConstant::FUR]);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::GENERAL_APPEARANCE] = Utils::fillZeroFloat($animalCache[JsonInputConstant::GENERAL_APPEARANCE]);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::HEIGHT] = Utils::fillZeroFloat($animalCache[JsonInputConstant::HEIGHT]);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::TORSO_LENGTH] = Utils::fillZeroFloat($animalCache[JsonInputConstant::TORSO_LENGTH]);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREAST_DEPTH] = Utils::fillZeroFloat($animalCache[JsonInputConstant::BREAST_DEPTH]);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::MARKINGS] = Utils::fillZeroFloat($animalCache[JsonInputConstant::MARKINGS]);

            //Litter
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::LITTER_SIZE] = $litterSize;
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::N_LING] = $nLing;

            //Offspring
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::LITTER_COUNT] = Utils::fillZero($litterCount);

            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::ULN] = Utils::fillNullOrEmptyString($uln);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::PEDIGREE] = Utils::fillNullOrEmptyString($stn);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::SCRAPIE] = Utils::fillNullOrEmptyString($scrapieGenotype, self::EMPTY_SCRAPIE_GENOTYPE);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED] = Utils::fillNullOrEmptyString($breed);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED_TYPE] = Utils::fillNullOrEmptyString(Translation::getDutchUcFirst($breedType));
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED_CODE] = Utils::fillNullOrEmptyString($breedCode);
            /* Dates. The null checks for dates are done here including the formatting */
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::DATE_OF_BIRTH] = Utils::fillNullOrEmptyString($dateOfBirthString, self::EMPTY_DATE_OF_BIRTH);
            //NOTE measurementDate and inspectionDate are identical!
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::INSPECTION_DATE] = self::getTypeAndInspectionDateByDateTime(
                $animalCache[JsonInputConstant::KIND], $exteriorMeasurementDate, self::GENERAL_NULL_FILLER
            );
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::ANIMAL_TYPE_IN_LATIN] = $animalTypeInLatin ?? self::GENERAL_NULL_FILLER;
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::ANIMAL_TYPE] = $animalType ?? self::GENERAL_NULL_FILLER;
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED_CODE_LETTERS] = $breedCodeLetters ?? self::GENERAL_NULL_FILLER;
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED_CODE_FULLNAME] = $breedCodeFullname ?? self::GENERAL_NULL_FILLER;
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::COUNTRY_OF_BIRTH] = $countryOfBirth ?? self::GENERAL_NULL_FILLER;

            /* variables translated to Dutch */
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::GENDER] = Translation::getGenderInDutch($gender);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BLINDNESS_FACTOR] = $blindnessFactor;
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::PREDICATE] = $predicate;
            
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::PRODUCTION] = $production;
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREEDER_NAME] = $breederName;

            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::NAME] = $nickname != null ? $nickname : self::GENERAL_NULL_FILLER;

            //TODO Add these variables to the entities INCLUDING NULL CHECKS!!!
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREEDER_NUMBER] = self::GENERAL_NULL_FILLER; //At this moment replace by only breederName

            if(!key_exists(ReportLabel::LITTER_GROUP, $this->data[ReportLabel::ANIMALS][$key])) {
                $this->data[ReportLabel::ANIMALS][$key][ReportLabel::LITTER_GROUP] = self::GENERAL_NULL_FILLER;
            }

        } else {
            $this->addAnimalValuesBySql($key, $animalId, $generation);
        }

    }


    /**
     * @param int|string $key
     * @param array $breedGrades
     */
    private function addBreedValuesToArrayFromSqlResult($key, $breedGrades)
    {
        if(is_array($breedGrades)) {
            $muscleThickness = BreedFormat::formatMuscleThicknessBreedValue($breedGrades['muscle_thickness'], $breedGrades['muscle_thickness_accuracy']);
            $bodyFat = BreedFormat::formatFatThickness3BreedValue($breedGrades['fat_thickness3'], $breedGrades['fat_thickness3accuracy']);
            $growth = BreedFormat::formatGrowthBreedValue($breedGrades['growth'], $breedGrades['growth_accuracy']);
            $litterSizeBreedValue = BreedFormat::formatBreedValue($breedGrades['total_born'], $breedGrades['total_born_accuracy']);
            $tailLength = BreedFormat::formatBreedValue($breedGrades['tail_length'], $breedGrades['tail_length_accuracy']);

            $lambMeatIndex = BreedFormat::getJoinedLambMeatIndex($breedGrades['lamb_meat_index'], $breedGrades['lamb_meat_accuracy']);
            $lambMeatIndexWithoutAccuracy = $breedGrades['lamb_meat_index'];
        } else {
            $muscleThickness = BreedFormat::EMPTY_BREED_VALUE;
            $bodyFat = BreedFormat::EMPTY_BREED_VALUE;
            $growth = BreedFormat::EMPTY_BREED_VALUE;
            $litterSizeBreedValue = BreedFormat::EMPTY_BREED_VALUE;
            $tailLength = BreedFormat::EMPTY_BREED_VALUE;

            $lambMeatIndex = BreedFormat::EMPTY_INDEX_VALUE;
            $lambMeatIndexWithoutAccuracy = null;
        }

        // Set values in result array
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::MUSCLE_THICKNESS] = $muscleThickness;
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BODY_FAT] = $bodyFat;
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::GROWTH] = $growth;
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::LITTER_GROUP] = $litterSizeBreedValue;

        //NOTE this value is not used in the pdf, but it must be fill so there is no null pointer exception
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::TAIL_LENGTH] = Utils::fillNullOrEmptyString(null);

        //LambMeatIndex with Accuracy
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::VL] = $lambMeatIndex;
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::SL] = $tailLength;

        if($key == ReportLabel::CHILD_KEY) {
            $this->addBreedIndex($lambMeatIndexWithoutAccuracy);
        }
    }


    /**
     * @param string $key
     * @param int $animalId
     * @param int $generation
     */
    private function addAnimalValuesBySql($key, $animalId, $generation)
    {
        $exteriorReplacementString = null;
        $latestExteriorArray = $this->exteriorRepository->getLatestExteriorBySql($animalId, $exteriorReplacementString);

        if($generation < self::GENERATION_OF_ASCENDANTS - 1) {
            //Only retrieve the breedValues and lambMeatIndices for the child, parents and grandparents.

            //Breedvalues: The actual breed value not the measurements!

            if(ctype_digit($animalId) || is_int($animalId)) {
                //Use a LEFT JOIN, so the necessary keys will always be returned,
                //even if the result_table_breed_grades record does not exist.
                $sql = "SELECT r.* FROM animal 
                LEFT JOIN result_table_breed_grades r ON r.animal_id = animal.id  
                WHERE animal.id = ".$animalId;
                $breedGrades = $this->conn->query($sql)->fetch();
            } else {
                $breedGrades = null;
            }

            $this->addBreedValuesToArrayFromSqlResult($key, $breedGrades);
        }

        //Litter in which animal was born
        $litterData = $this->litterRepository->getLitterData($animalId);
        $litterSize = self::GENERAL_NULL_FILLER;
        $nLing = self::GENERAL_NULL_FILLER;

        if($litterData != null) {
            $litterSize = $litterData[JsonInputConstant::SIZE];
            $nLing = $litterData[JsonInputConstant::N_LING];
        }


        if($animalId != null) {
            $sql = "SELECT a.id, CONCAT(a.uln_country_code, a.uln_number) as uln, CONCAT(a.pedigree_country_code, a.pedigree_number) as stn,
                    a.scrapie_genotype, a.breed, a.breed_type, a.breed_code, a.date_of_birth, a.gender, a.predicate, a.predicate_score,
                    a.parent_father_id as father_id, a.parent_mother_id as mother_id, a.blindness_factor, c.company_name, d.city,
                    a.nickname,
                    cache.production_age, cache.litter_count, cache.total_offspring_count,
                    cache.born_alive_offspring_count, cache.gave_birth_as_one_year_old
                FROM animal a
                    LEFT JOIN location l ON a.location_of_birth_id = l.id
                    LEFT JOIN company c ON l.company_id = c.id
                    LEFT JOIN address d ON d.id = c.address_id
                    LEFT JOIN animal_cache cache ON a.id = cache.animal_id
                WHERE a.id = ".$animalId;
            $animalData = $this->em->getConnection()->query($sql)->fetch();

            //AnimalData
            $uln = $animalData[JsonInputConstant::ULN];
            $stn = $animalData[JsonInputConstant::STN];
            $breed = $animalData[JsonInputConstant::BREED];
            $breedCode = $animalData[JsonInputConstant::BREED_CODE];
            $breedType = $animalData[JsonInputConstant::BREED_TYPE];
            $scrapieGenotype = $animalData[JsonInputConstant::SCRAPIE_GENOTYPE];
            $gender = $animalData[JsonInputConstant::GENDER];

            $nickname = null;
            if(self::SHOW_NICKNAME) {
                $nickname = $animalData[JsonInputConstant::NICKNAME];
            }

            $predicate = self::GENERAL_NULL_FILLER;
            if(self::SHOW_PREDICATE_IN_REPORT) {
                $formattedPredicate = DisplayUtil::parsePredicateString($animalData[JsonInputConstant::PREDICATE], $animalData[JsonInputConstant::PREDICATE_SCORE]);
                $predicate = $formattedPredicate != null ? $formattedPredicate : self::GENERAL_NULL_FILLER;
            }
            $blindnessFactor = self::GENERAL_NULL_FILLER;
            if(self::SHOW_BLINDNESS_FACTOR_IN_REPORT) {
                $formattedBlindnessFactor = Translation::getDutchUcFirst($animalData[JsonInputConstant::BLINDNESS_FACTOR]);
                $blindnessFactor = $formattedBlindnessFactor != null ? $formattedBlindnessFactor : self::GENERAL_NULL_FILLER;
            }

            $dateOfBirthString = self::EMPTY_DATE_OF_BIRTH;
            $dateOfBirthDateTime = null;
            if($animalData[JsonInputConstant::DATE_OF_BIRTH] != null) {
                $dateOfBirthDateTime = new \DateTime($animalData[JsonInputConstant::DATE_OF_BIRTH]);
                $dateOfBirthString = $dateOfBirthDateTime->format('d-m-Y');
            }

            $companyName = $animalData[JsonInputConstant::COMPANY_NAME];
            $companyName = StringUtil::trimStringWithAddedEllipsis($companyName, self::MAX_LENGTH_FULL_NAME);
            $city = $animalData[JsonInputConstant::CITY];
            $city = StringUtil::trimStringWithAddedEllipsis($city, self::MAX_LENGTH_CITY_NAME);

            //ProductionValues
            $productionAge = intval($animalData['production_age']);
            $litterCount = intval($animalData['litter_count']);
            $totalOffSpringCount = intval($animalData['total_offspring_count']);
            $bornAliveOffspringCount = intval($animalData['born_alive_offspring_count']);
            $addProductionAsterisk = boolval($animalData['gave_birth_as_one_year_old']);

            //These ids are only used only inside this class and not in the twig file
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::MOTHER_ID] = $animalData[ReportLabel::MOTHER_ID];
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::FATHER_ID] = $animalData[ReportLabel::FATHER_ID];
        } else {
            $uln = null;
            $stn = null;
            $scrapieGenotype = null;
            $breed = null;
            $breedCode = null;
            $breedType = null;
            $scrapieGenotype = null;
            $gender = null;
            $nickname = null;
            $predicate = self::GENERAL_NULL_FILLER;
            $blindnessFactor = self::GENERAL_NULL_FILLER;

            $dateOfBirthString = self::EMPTY_DATE_OF_BIRTH;
            $dateOfBirthDateTime = null;

            $companyName = null;
            $city = null;

            $productionAge = null;
            $litterCount = null;
            $totalOffSpringCount = null;
            $bornAliveOffspringCount = null;
            $addProductionAsterisk = null;

            //These ids are only used only inside this class and not in the twig file
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::MOTHER_ID] = null;
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::FATHER_ID] = null;
        }

        $breederName = self::GENERAL_NULL_FILLER;
        if($companyName != null && $city != null) {
            $breederName = $companyName.'; '.$city;
        }

        $inspectionDateString = null;
        $inspectionDateDateTime = null;
        if($latestExteriorArray[JsonInputConstant::MEASUREMENT_DATE] != null && $latestExteriorArray[JsonInputConstant::MEASUREMENT_DATE] != $exteriorReplacementString) {
            $inspectionDateDateTime = new \DateTime($latestExteriorArray[JsonInputConstant::MEASUREMENT_DATE]);
            //$inspectionDateString = $inspectionDateDateTime->format('d-m-Y');
        }

        $production = DisplayUtil::parseProductionStringFromGivenParts($productionAge, $litterCount, $totalOffSpringCount, $bornAliveOffspringCount, $addProductionAsterisk);

        /* Set values into array */
        //Note the BreedValues and LambMeatIndex values are already set above

        //Exterior
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::SKULL] = Utils::fillZeroFloat($latestExteriorArray[JsonInputConstant::SKULL]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::DEVELOPMENT] = Utils::fillZeroFloat($latestExteriorArray[JsonInputConstant::PROGRESS]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::MUSCULARITY] = Utils::fillZeroFloat($latestExteriorArray[JsonInputConstant::MUSCULARITY]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::PROPORTION] = Utils::fillZeroFloat($latestExteriorArray[JsonInputConstant::PROPORTION]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::TYPE] = Utils::fillZeroFloat($latestExteriorArray[JsonInputConstant::EXTERIOR_TYPE]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::LEGWORK] = Utils::fillZeroFloat($latestExteriorArray[JsonInputConstant::LEG_WORK]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::FUR] = Utils::fillZeroFloat($latestExteriorArray[JsonInputConstant::FUR]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::GENERAL_APPEARANCE] = Utils::fillZeroFloat($latestExteriorArray[JsonInputConstant::GENERAL_APPEARANCE]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::HEIGHT] = Utils::fillZeroFloat($latestExteriorArray[JsonInputConstant::HEIGHT]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::TORSO_LENGTH] = Utils::fillZeroFloat($latestExteriorArray[JsonInputConstant::TORSO_LENGTH]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREAST_DEPTH] = Utils::fillZeroFloat($latestExteriorArray[JsonInputConstant::BREAST_DEPTH]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::MARKINGS] = Utils::fillZeroFloat($latestExteriorArray[JsonInputConstant::MARKINGS]);

        //Litter
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::LITTER_SIZE] = $litterSize;
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::N_LING] = $nLing;

        //Production
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::PRODUCTION] = $production;

        //Offspring
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::LITTER_COUNT] = Utils::fillZero($litterCount);

        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::ULN] = Utils::fillNullOrEmptyString($uln);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::PEDIGREE] = Utils::fillNullOrEmptyString($stn);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::SCRAPIE] = Utils::fillNullOrEmptyString($scrapieGenotype, self::EMPTY_SCRAPIE_GENOTYPE);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED] = Utils::fillNullOrEmptyString($breed);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED_TYPE] = Utils::fillNullOrEmptyString(Translation::getDutchUcFirst($breedType));
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED_CODE] = Utils::fillNullOrEmptyString($breedCode);
        /* Dates. The null checks for dates are done here including the formatting */
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::DATE_OF_BIRTH] = Utils::fillNullOrEmptyString($dateOfBirthString, self::EMPTY_DATE_OF_BIRTH);
        //NOTE measurementDate and inspectionDate are identical!
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::INSPECTION_DATE] = self::getTypeAndInspectionDateByDateTime(
            $latestExteriorArray[JsonInputConstant::KIND], $inspectionDateDateTime, self::GENERAL_NULL_FILLER
        );

        /* variables translated to Dutch */
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::GENDER] = Translation::getGenderInDutch($gender);

        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BLINDNESS_FACTOR] = $blindnessFactor;
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::PREDICATE] = $predicate;
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREEDER_NAME] = $breederName;

        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::NAME] = $nickname != null ? $nickname : self::GENERAL_NULL_FILLER;

        //TODO Add these variables to the entities INCLUDING NULL CHECKS!!!

        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREEDER_NUMBER] = self::GENERAL_NULL_FILLER;

        if(!key_exists(ReportLabel::LITTER_GROUP, $this->data[ReportLabel::ANIMALS][$key])) {
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::LITTER_GROUP] = self::GENERAL_NULL_FILLER;
        }
    }


    /**
     * @param float $lambMeatIndex
     */
    private function addBreedIndex($lambMeatIndex)
    {
        //Empty
        $breederStarCount = 0;
        $motherBreederStarCount = 0;
        $fatherBreederStarCount = 0;
        $exteriorStarCount = 0;
        
        $lambMeatStarCount = StarValueUtil::getStarValue($lambMeatIndex);

        $this->data[ReportLabel::BREEDER_INDEX_STARS] = TwigOutputUtil::createStarsIndex($breederStarCount);
        $this->data[ReportLabel::M_BREEDER_INDEX_STARS] = TwigOutputUtil::createStarsIndex($motherBreederStarCount);
        $this->data[ReportLabel::F_BREEDER_INDEX_STARS] = TwigOutputUtil::createStarsIndex($fatherBreederStarCount);
        $this->data[ReportLabel::EXT_INDEX_STARS] = TwigOutputUtil::createStarsIndex($exteriorStarCount);
        $this->data[ReportLabel::VL_INDEX_STARS] = TwigOutputUtil::createStarsIndex($lambMeatStarCount);

        $this->data[ReportLabel::BREEDER_INDEX_NO_ACC] = 'ab/acc';
        $this->data[ReportLabel::M_BREEDER_INDEX_NO_ACC] = 'mb/acc';
        $this->data[ReportLabel::F_BREEDER_INDEX_NO_ACC] = 'fb/acc';
        $this->data[ReportLabel::EXT_INDEX_NO_ACC] = 'ex/acc';
    }


    /**
     * @param string $kind
     * @param \DateTime $measurementDate
     * @param string $replacementString
     * @return string
     */
    private static function getTypeAndInspectionDateByDateTime($kind, $measurementDate, $replacementString = self::GENERAL_NULL_FILLER)
    {
        $kindExists = NullChecker::isNotNull($kind) && $kind != self::GENERAL_NULL_FILLER;
        $measurementDateExists = NullChecker::isNotNull($measurementDate);

        if($kindExists && $measurementDateExists) {
            return $kind.' '.$measurementDate->format('d-m-Y');

        } elseif (!$kindExists && $measurementDateExists) {
            return $measurementDate->format('d-m-Y');

        } else {
            return $replacementString;
        }

    }


    /**
     * @param string $registerName
     * @return string
     */
    private function parsePedigreeRegisterText($registerName)
    {
        if($registerName != null && $registerName != '') {
            return 'Namens: '.$registerName;
        } else {
            return self::MISSING_PEDIGREE_REGISTER;
        }
    }



}