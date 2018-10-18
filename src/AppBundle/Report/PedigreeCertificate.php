<?php

namespace AppBundle\Report;


use AppBundle\Component\BreedGrading\BreedFormat;
use AppBundle\Component\Utils;
use AppBundle\Constant\Environment;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\CompanyAddress;
use AppBundle\Entity\Exterior;
use AppBundle\Entity\ExteriorRepository;
use AppBundle\Entity\Litter;
use AppBundle\Entity\LitterRepository;
use AppBundle\Entity\LocationAddress;
use AppBundle\Entity\PedigreeCode;
use AppBundle\Entity\PedigreeRegister;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\AnimalType;
use AppBundle\Enumerator\AnimalTypeInLatin;
use AppBundle\Enumerator\BreedType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Output\BreedValuesOutput;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\BreedCodeUtil;
use AppBundle\Util\DisplayUtil;
use AppBundle\Util\NullChecker;
use AppBundle\Util\NumberUtil;
use AppBundle\Util\SectionUtil;
use AppBundle\Util\StarValueUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\Translation;
use AppBundle\Util\Validator;
use AppBundle\Validation\AdminValidator;
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
    const EMPTY_DATE_OF_BIRTH = '';
    const GENERAL_NULL_FILLER = '';

    const LITTER_SIZE = 'litterSize';
    const LITTER_GROUP = 'litterGroup';
    const N_LING = 'nLing';

    const STARS_NULL_VALUE = null;
    const EMPTY_SCRAPIE_GENOTYPE = '';

    const GENERATION_OF_ASCENDANTS = 3;

    const LAST_MATE_MAX_DAYS_BEFORE_TODAY = 160;

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
    /** @var boolean */
    private $useTestData;

    /** @var BreedValuesOutput */
    private $breedValuesOutput;

    /** @var string */
    private $breedValuesLastGenerationDate;
    /** @var array */
    private $breedFullNamesByCodes;

    public function __construct(EntityManagerInterface $em,
                                TranslatorInterface $translator,
                                $useTestData,
                                $environment)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();

        $this->litterRepository = $em->getRepository(Litter::class);
        $this->exteriorRepository = $em->getRepository(Exterior::class);

        $this->translator = $translator;

        $this->useTestData = $useTestData && ($environment === Environment::DEV || $environment === Environment::STAGE || $environment === Environment::LOCAL);
    }

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
     * @return array
     */
    private function getBreedFullnamesByCodes(): array
    {
        if (empty($this->breedFullNamesByCodes)) {
            $this->breedFullNamesByCodes = $this->em->getRepository(PedigreeCode::class)
                ->getFullNamesByCodes();
        }
        return $this->breedFullNamesByCodes;
    }


    private function clearPrivateVariables()
    {
        $this->breedFullNamesByCodes = null;
    }


    /**
     * @param Person $actionBy
     * @param string $ubn
     * @param int $animalId
     * @param string $trimmedClientName
     * @param string $ownerEmailAddress
     * @param CompanyAddress $companyAddress
     * @return array
     * @throws \Exception
     */
    public function generate($actionBy, $ubn, $animalId, $trimmedClientName, $ownerEmailAddress, $companyAddress)
    {
        $this->data = array();

        $this->data[ReportLabel::ACTION_BY_FULL_NAME] =
            ($actionBy ? $actionBy->getFullName() : self::GENERAL_NULL_FILLER);
        $this->data[ReportLabel::ACTION_BY_IS_SUPER_ADMIN] =
            AdminValidator::isAdmin($actionBy, AccessLevelType::SUPER_ADMIN);

        //Set Default Owner details
        $this->data[ReportLabel::OWNER_NAME] = $trimmedClientName != null ? $trimmedClientName: self::GENERAL_NULL_FILLER;
        $this->data[ReportLabel::OWNER_EMAIL_ADDRESS] = Validator::getFillerCheckedEmailAddress(
            $ownerEmailAddress, self::GENERAL_NULL_FILLER
        );
        $this->data[ReportLabel::ADDRESS] = $companyAddress != null ? $companyAddress : $this->getEmptyLocationAddress();
        $postalCode = $companyAddress != null ? StringUtil::addSpaceInDutchPostalCode($companyAddress->getPostalCode(), self::GENERAL_NULL_FILLER) : self::GENERAL_NULL_FILLER;
        $this->data[ReportLabel::POSTAL_CODE] = $postalCode;
        $this->data[ReportLabel::UBN] = $ubn != null ? $ubn : self::GENERAL_NULL_FILLER;
        //Set CurrentOwner Details!
        $this->setOwnerDataFromAnimalIdBySql($animalId);

        $this->setBreederDataFromAnimalIdBySql($animalId);

        /** @var PedigreeRegister $pedigreeRegister */
        $pedigreeRegister = $this->em->getRepository(PedigreeRegister::class)->getByAnimalId($animalId);
        $this->data[ReportLabel::PEDIGREE_REGISTER] = $pedigreeRegister
            ? $pedigreeRegister->getPedigreeRegisterForCertification() : null;

        // Add shared data
        $this->breedValuesLastGenerationDate = $this->breedValuesOutput->getBreedValuesLastGenerationDate(self::GENERAL_NULL_FILLER);
        $this->data[ReportLabel::BREED_VALUES_EVALUATION_DATE] = $this->breedValuesLastGenerationDate;

        $keyAnimal = ReportLabel::CHILD_KEY;

        $generation = 0;
        $this->addAnimalValues($keyAnimal, $animalId, $generation);
        $this->addParents($animalId, $keyAnimal, $generation);

        $this->clearPrivateVariables();

        return $this->getData();
    }


    /**
     * @param $animalId
     */
    private function setOwnerDataFromAnimalIdBySql($animalId)
    {
        if(is_string($animalId) || is_int($animalId)) {
            $sql = "SElECT l.ubn, c.company_name, d.street_name, d.address_number, d.address_number_suffix,
                    d.postal_code, d.city, owner.email_address
                FROM animal a
                INNER JOIN location l ON a.location_id = l.id
                INNER JOIN company c ON l.company_id = c.id
                INNER JOIN person owner ON c.owner_id = owner.id
                INNER JOIN address d ON d.id = c.address_id
                WHERE a.id = ".intval($animalId);
            $result = $this->em->getConnection()->query($sql)->fetch();

            $currentUbnOfAnimal = is_array($result) ? ArrayUtil::get('ubn', $result, null): null;

            if($currentUbnOfAnimal == $this->data[ReportLabel::UBN]) {
                return; //just use current default values

            } elseif($currentUbnOfAnimal == null) {
                //Set all owner values as empty
                $this->data[ReportLabel::OWNER_NAME] = self::GENERAL_NULL_FILLER;
                $this->data[ReportLabel::OWNER_EMAIL_ADDRESS] = self::GENERAL_NULL_FILLER;
                $this->data[ReportLabel::ADDRESS] = $this->getEmptyLocationAddress();
                $this->data[ReportLabel::POSTAL_CODE] = self::GENERAL_NULL_FILLER;
                $this->data[ReportLabel::UBN] = self::GENERAL_NULL_FILLER;

            } else {
                //Use currentOwner values
                $companyName = $this->getNullCheckedArrayValue('company_name', $result);
                $streetName = $this->getNullCheckedArrayValue('street_name', $result);
                $addressNumber = $this->getNullCheckedArrayValue('address_number', $result);
                $addressNumberSuffix = $this->getNullCheckedArrayValue('address_number_suffix', $result, '');
                $rawPostalCode = $this->getNullCheckedArrayValue('postal_code', $result);
                $postalCode = $this->nullFillString($rawPostalCode);
                $city = $this->getNullCheckedArrayValue('city', $result);
                $ownerEmailAddress = ArrayUtil::get('email_address', $result);

                $address = new LocationAddress();
                $address->setStreetName($streetName);
                $address->setAddressNumber($addressNumber);
                $address->setAddressNumberSuffix($addressNumberSuffix);
                $address->setPostalCode($postalCode);
                $address->setCity($city);

                $this->data[ReportLabel::OWNER_NAME] = StringUtil::trimStringWithAddedEllipsis($companyName, PedigreeCertificates::MAX_LENGTH_FULL_NAME);
                $this->data[ReportLabel::OWNER_EMAIL_ADDRESS] = Validator::getFillerCheckedEmailAddress(
                    $ownerEmailAddress, self::GENERAL_NULL_FILLER
                );
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
            $sql = "SElECT l.ubn, c.company_name, d.street_name, d.address_number, d.address_number_suffix, d.postal_code, d.city,            n.breeder_number, n.source, a.ubn_of_birth, p.email_address
                    FROM animal a
                      INNER JOIN location l ON a.location_of_birth_id = l.id
                      LEFT JOIN company c ON l.company_id = c.id
                      LEFT JOIN address d ON d.id = c.address_id
                      LEFT JOIN breeder_number n ON n.ubn_of_birth = a.ubn_of_birth
                      LEFT JOIN person p ON p.id = c.owner_id
                    WHERE a.id = ".intval($animalId)."
                    ORDER BY n.source DESC LIMIT 1";
            $result = $this->em->getConnection()->query($sql)->fetch();

            if(!is_array($result)) {
                //Set all breeder values as empty
                $this->data[ReportLabel::BREEDER_NAME] = self::GENERAL_NULL_FILLER;
                $this->data[ReportLabel::BREEDER_EMAIL_ADDRESS] = self::GENERAL_NULL_FILLER;
                $this->data[ReportLabel::ADDRESS_BREEDER] = $this->getEmptyLocationAddress();
                $this->data[ReportLabel::POSTAL_CODE_BREEDER] = self::GENERAL_NULL_FILLER;
                $this->data[ReportLabel::BREEDER_NUMBER] = self::GENERAL_NULL_FILLER;
            } else {
                $ubnOfBreeder = $this->getNullCheckedArrayValue('ubn_of_birth', $result);

                //Use currentOwner values
                $companyName = $this->getNullCheckedArrayValue('company_name', $result);
                $streetName = $this->getNullCheckedArrayValue('street_name', $result);
                $addressNumber = $this->getNullCheckedArrayValue('address_number', $result);
                $addressNumberSuffix = $this->getNullCheckedArrayValue('address_number_suffix', $result, '');
                $rawPostalCode = $this->getNullCheckedArrayValue('postal_code', $result);
                $postalCode = $this->nullFillString($rawPostalCode);
                $city = $this->getNullCheckedArrayValue('city', $result);

                $address = new LocationAddress();
                $address->setStreetName($streetName);
                $address->setAddressNumber($addressNumber);
                $address->setAddressNumberSuffix($addressNumberSuffix);
                $address->setPostalCode($postalCode);
                $address->setCity($city);

                $this->data[ReportLabel::BREEDER_EMAIL_ADDRESS] = Validator::getFillerCheckedEmailAddress(
                    $this->getNullCheckedArrayValue('email_address', $result),
                    self::GENERAL_NULL_FILLER
                );

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
        $emptyAddress->setStreetName(self::GENERAL_NULL_FILLER);
        $emptyAddress->setAddressNumber(self::GENERAL_NULL_FILLER);
        $emptyAddress->setAddressNumberSuffix('');
        $emptyAddress->setCity(self::GENERAL_NULL_FILLER);
        $emptyAddress->setPostalCode(self::GENERAL_NULL_FILLER);
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

                $breedCodeLettersAndFullNameSets = $this->extractBreedCodeLettersAndFullNameSets($breedCode);

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
                $breedCodeLettersAndFullNameSets = [];
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
            $production = DisplayUtil::parseProductionStringFromGivenParts($productionAge, $litterCount,
                $totalOffSpringCount, $bornAliveOffspringCount, $addProductionAsterisk, self::GENERAL_NULL_FILLER);

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
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::SKULL] = $this->fillZeroFloat($animalCache[JsonInputConstant::SKULL]);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::DEVELOPMENT] = $this->fillZeroFloat($animalCache[JsonInputConstant::PROGRESS]);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::MUSCULARITY] = $this->fillZeroFloat($animalCache[JsonInputConstant::MUSCULARITY]);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::PROPORTION] = $this->fillZeroFloat($animalCache[JsonInputConstant::PROPORTION]);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::TYPE] = $this->fillZeroFloat($animalCache[JsonInputConstant::EXTERIOR_TYPE]);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::LEGWORK] = $this->fillZeroFloat($animalCache[JsonInputConstant::LEG_WORK]);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::FUR] = $this->fillZeroFloat($animalCache[JsonInputConstant::FUR]);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::GENERAL_APPEARANCE] = $this->fillZeroFloat($animalCache[JsonInputConstant::GENERAL_APPEARANCE]);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::HEIGHT] = $this->fillZeroFloat($animalCache[JsonInputConstant::HEIGHT]);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::TORSO_LENGTH] = $this->fillZeroFloat($animalCache[JsonInputConstant::TORSO_LENGTH]);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREAST_DEPTH] = $this->fillZeroFloat($animalCache[JsonInputConstant::BREAST_DEPTH]);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::MARKINGS] = $this->fillZeroFloat($animalCache[JsonInputConstant::MARKINGS]);

            //Litter
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::LITTER_SIZE] = $litterSize;
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::N_LING] = $nLing;

            $sectionType = SectionUtil::getSectionType($breedType, self::GENERAL_NULL_FILLER);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::SECTION_TYPE] = $sectionType;

            $this->setDisplayZooTechnicalData($key, $sectionType);

            //Offspring
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::LITTER_COUNT] = Utils::fillZero($litterCount, self::GENERAL_NULL_FILLER);

            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::ULN] = $this->nullFillString($uln);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::PEDIGREE] = $this->nullFillString($stn);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::SCRAPIE] = Utils::fillNullOrEmptyString($scrapieGenotype, self::EMPTY_SCRAPIE_GENOTYPE);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED] = $this->nullFillString($breed);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED_TYPE] = $this->nullFillString(Translation::getDutchUcFirst($breedType));
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED_CODE] = $this->nullFillString($breedCode);
            /* Dates. The null checks for dates are done here including the formatting */
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::DATE_OF_BIRTH] = Utils::fillNullOrEmptyString($dateOfBirthString, self::EMPTY_DATE_OF_BIRTH);
            //NOTE measurementDate and inspectionDate are identical!
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::INSPECTION_DATE] = self::getTypeAndInspectionDateByDateTime(
                $animalCache[JsonInputConstant::KIND], $exteriorMeasurementDate, self::GENERAL_NULL_FILLER
            );
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::ANIMAL_TYPE_IN_LATIN] = $animalTypeInLatin ?? self::GENERAL_NULL_FILLER;
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::ANIMAL_TYPE] = $animalType ?? self::GENERAL_NULL_FILLER;

            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED_CODES] = $breedCodeLettersAndFullNameSets ?? [];

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

            if ($generation === 0) {
                $this->setLastMate($animalId, $key);
            }

        } else {
            $this->addAnimalValuesBySql($key, $animalId, $generation);
        }

    }


    private function extractBreedCodeLettersAndFullNameSets($breedCode): array
    {
        if (empty($breedCode) || $breedCode === self::GENERAL_NULL_FILLER) {
            return [];
        }

        $fullNamesByCodes = $this->getBreedFullnamesByCodes();

        $breedCodeNameAndCodeSets = [];
        $parts = BreedCodeUtil::getBreedCodePartsFromBreedCodeString($breedCode);

        // ksort($parts); SORT ALPHABETICALLY

        foreach ($parts as $code => $value) {
            $fullName = ArrayUtil::get($code, $fullNamesByCodes);
            if ($fullName) {
                $breedCodeNameAndCodeSets[] = [
                    ReportLabel::BREED_CODE_LETTERS => $code,
                    ReportLabel::BREED_CODE_FULLNAME => $fullName,
                ];
            }
        }

        return $breedCodeNameAndCodeSets;
    }


    /**
     * @param int|string $key
     * @param array $breedGrades
     */
    private function addBreedValuesToArrayFromSqlResult($key, $breedGrades)
    {
        if(!is_array($breedGrades)) {
            $this->addEmptyBreedIndexes($key);
            $this->addEmptyBreedValuesSet($key);
            return;
        }

        $isFirstGeneration = strlen($key) <= 1;
        if ($key == ReportLabel::CHILD_KEY || $isFirstGeneration) {
            $this->addBreedValuesSet($key, $breedGrades);
        }

        $isFirstOrSecondGeneration = strlen($key) <= 2;
        if($key == ReportLabel::CHILD_KEY || $isFirstOrSecondGeneration) {
            $this->addBreedIndexes($key, $breedGrades);
        }
    }


    /**
     * @param $resultTableValueVariable
     * @return bool
     */
    private function useBreedIndexFormatForBreedValue($resultTableValueVariable): bool
    {
        return $resultTableValueVariable === 'odin_bc';
    }


    private function addBreedValuesSet($key, $breedGrades)
    {
        $exteriorBreedValuesOutput = $this->breedValuesOutput->getForPedigreeCertificate($breedGrades, self::GENERAL_NULL_FILLER);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED_VALUES] = $exteriorBreedValuesOutput[ReportLabel::VALUES];

        $hasAnyBreedValues = $exteriorBreedValuesOutput[ReportLabel::HAS_ANY_VALUES];
        foreach ($this->breedValuesOutput->getBreedValueResultTableColumnNamesSets() as $set)
        {
            $resultTableValueVariable = $set['result_table_value_variable'];
            $resultTableAccuracyVariable = $set['result_table_accuracy_variable'];

            $value = ArrayUtil::get($resultTableValueVariable, $breedGrades);
            $accuracy = ArrayUtil::get($resultTableAccuracyVariable, $breedGrades);

            $isEmpty = empty($value) || empty($accuracy);

            if ($this->useBreedIndexFormatForBreedValue($resultTableValueVariable)) {
                $formattedValue = BreedValuesOutput::getFormattedBreedIndex($value, self::GENERAL_NULL_FILLER);
                $formattedAccuracy = BreedValuesOutput::getFormattedBreedIndexAccuracy($accuracy, self::GENERAL_NULL_FILLER);
            } else {
                $formattedValue = BreedValuesOutput::getFormattedBreedValue($value, self::GENERAL_NULL_FILLER);
                $formattedAccuracy = BreedValuesOutput::getFormattedBreedValueAccuracy($accuracy, self::GENERAL_NULL_FILLER);
            }

            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED_VALUES][$resultTableValueVariable] = [
                ReportLabel::VALUE => $formattedValue,
                ReportLabel::ACCURACY => $formattedAccuracy,
                ReportLabel::IS_EMPTY => $isEmpty,
            ];

            if (!$isEmpty) {
                $hasAnyBreedValues = true;
            }
        }

        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED_VALUES_EVALUATION_DATE] =
            $hasAnyBreedValues ? $this->breedValuesLastGenerationDate : self::GENERAL_NULL_FILLER;

        foreach ($this->breedValuesOutput->getExteriorKeysWithSuffixes() as $exteriorKey)
        {
            unset($this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED_VALUES][$exteriorKey]);
        }

        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED_VALUES][ReportLabel::IS_EMPTY] = false;
    }


    private function addEmptyBreedValuesSet($key)
    {
        $this->addBreedValuesSet($key, []);
    }


    /**
     * @param string $key
     * @param int $animalId
     * @param int $generation
     */
    private function addAnimalValuesBySql($key, $animalId, $generation)
    {
        $exteriorReplacementString = self::GENERAL_NULL_FILLER;
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
                    cache.born_alive_offspring_count, cache.gave_birth_as_one_year_old,
                    cache.n_ling
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

            // nLing
            $nLing = $nLing === self::GENERAL_NULL_FILLER || empty($nLing) ? $animalData['n_ling'] : $nLing;

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

        $production = DisplayUtil::parseProductionStringFromGivenParts($productionAge, $litterCount,
            $totalOffSpringCount, $bornAliveOffspringCount, $addProductionAsterisk, self::GENERAL_NULL_FILLER);

        /* Set values into array */
        //Note the BreedValues and LambMeatIndex values are already set above

        //Exterior
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::SKULL] = $this->fillZeroFloat($latestExteriorArray[JsonInputConstant::SKULL]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::DEVELOPMENT] = $this->fillZeroFloat($latestExteriorArray[JsonInputConstant::PROGRESS]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::MUSCULARITY] = $this->fillZeroFloat($latestExteriorArray[JsonInputConstant::MUSCULARITY]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::PROPORTION] = $this->fillZeroFloat($latestExteriorArray[JsonInputConstant::PROPORTION]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::TYPE] = $this->fillZeroFloat($latestExteriorArray[JsonInputConstant::EXTERIOR_TYPE]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::LEGWORK] = $this->fillZeroFloat($latestExteriorArray[JsonInputConstant::LEG_WORK]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::FUR] = $this->fillZeroFloat($latestExteriorArray[JsonInputConstant::FUR]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::GENERAL_APPEARANCE] = $this->fillZeroFloat($latestExteriorArray[JsonInputConstant::GENERAL_APPEARANCE]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::HEIGHT] = $this->fillZeroFloat($latestExteriorArray[JsonInputConstant::HEIGHT]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::TORSO_LENGTH] = $this->fillZeroFloat($latestExteriorArray[JsonInputConstant::TORSO_LENGTH]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREAST_DEPTH] = $this->fillZeroFloat($latestExteriorArray[JsonInputConstant::BREAST_DEPTH]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::MARKINGS] = $this->fillZeroFloat($latestExteriorArray[JsonInputConstant::MARKINGS]);

        //Litter
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::LITTER_SIZE] = $litterSize;
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::N_LING] = $nLing;

        //Production
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::PRODUCTION] = $production;

        //Offspring
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::LITTER_COUNT] = Utils::fillZero($litterCount,self::GENERAL_NULL_FILLER);

        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::ULN] = $this->nullFillString($uln);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::PEDIGREE] = $this->nullFillString($stn);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::SCRAPIE] = Utils::fillNullOrEmptyString($scrapieGenotype, self::EMPTY_SCRAPIE_GENOTYPE);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED] = $this->nullFillString($breed);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED_TYPE] = $this->nullFillString(Translation::getDutchUcFirst($breedType));
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED_CODE] = $this->nullFillString($breedCode);
        /* Dates. The null checks for dates are done here including the formatting */
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::DATE_OF_BIRTH] = Utils::fillNullOrEmptyString($dateOfBirthString, self::EMPTY_DATE_OF_BIRTH);
        //NOTE measurementDate and inspectionDate are identical!
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::INSPECTION_DATE] = self::getTypeAndInspectionDateByDateTime(
            $latestExteriorArray[JsonInputConstant::KIND], $inspectionDateDateTime, self::GENERAL_NULL_FILLER
        );

        $sectionType = SectionUtil::getSectionType($breedType, self::GENERAL_NULL_FILLER);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::SECTION_TYPE] = $sectionType;
        $this->setDisplayZooTechnicalData($key, $sectionType);

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
     * @param string $animalKey
     * @param array $breedGrades
     */
    private function addBreedIndexes($animalKey, $breedGrades)
    {
//        $unformattedIndexValue = $breedGrades['lamb_meat_index'];
//        $accuracy = $breedGrades['lamb_meat_accuracy'];

        if ($this->useTestData) {

            $this->addBreedIndex(
                $animalKey,
                ReportLabel::INDEX_FATHER,
                -8,
                0.5
            );

            $this->addBreedIndex(
                $animalKey,
                ReportLabel::INDEX_MOTHER,
                -1,
                0.9
            );

            $this->addBreedIndex(
                $animalKey,
                ReportLabel::INDEX_EXTERIOR,
                0,
                0.5
            );

            $this->addBreedIndex(
                $animalKey,
                ReportLabel::INDEX_BREED,
                3,
                0.44
            );

        } else {

            // TODO use real values when indexes are available

            $this->addBreedIndex(
                $animalKey,
                ReportLabel::INDEX_FATHER,
                0,
                0
            );

            $this->addBreedIndex(
                $animalKey,
                ReportLabel::INDEX_MOTHER,
                0,
                0
            );

            $this->addBreedIndex(
                $animalKey,
                ReportLabel::INDEX_EXTERIOR,
                0,
                0
            );

            $this->addBreedIndex(
                $animalKey,
                ReportLabel::INDEX_BREED,
                0,
                0
            );
        }
    }


    /**
     * @param string $animalKey
     * @param array $breedGrades
     */
    private function addEmptyBreedIndexes($animalKey)
    {
        $this->addBreedIndex(
            $animalKey,
            ReportLabel::INDEX_FATHER,
            0,
            0
        );

        $this->addBreedIndex(
            $animalKey,
            ReportLabel::INDEX_MOTHER,
            0,
            0
        );

        $this->addBreedIndex(
            $animalKey,
            ReportLabel::INDEX_EXTERIOR,
            0,
            0
        );

        $this->addBreedIndex(
            $animalKey,
            ReportLabel::INDEX_BREED,
            0,
            0
        );
    }


    /**
     * @param string $animalKey
     * @param string $indexType
     * @param float $unformattedIndexValue
     * @param float $accuracy
     */
    private function addBreedIndex($animalKey, $indexType, $unformattedIndexValue, $accuracy)
    {
        $isIndexEmpty = BreedFormat::isIndexEmpty($unformattedIndexValue, $accuracy);
        $formattedIndexValue = BreedFormat::getFormattedIndexValue($unformattedIndexValue, $accuracy);
        $formattedIndexAccuracy = BreedFormat::getFormattedIndexAccuracy($unformattedIndexValue, $accuracy);
        $starsValue = StarValueUtil::getStarValue(($isIndexEmpty ? null : $unformattedIndexValue));
        $starsOutput = StarValueUtil::getStarsOutput($starsValue);

        $this->data[ReportLabel::ANIMALS][$animalKey][ReportLabel::INDEXES][$indexType] = [
            ReportLabel::IS_EMPTY => $isIndexEmpty,
            ReportLabel::VALUE => $formattedIndexValue,
            ReportLabel::ACCURACY => $formattedIndexAccuracy,
            ReportLabel::STARS_VALUE => $starsValue,
            ReportLabel::STARS_OUTPUT => $starsOutput,
        ];
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


    private function setLastMate($animalId, $key)
    {
        $maxDays = self::LAST_MATE_MAX_DAYS_BEFORE_TODAY;
        $sql = "SELECT
                  m.ki,
                  CONCAT(r.uln_country_code, r.uln_number) as uln_stud_ram,
                  r.uln_country_code as uln_country_code_stud_ram,
                  r.uln_number as uln_number_stud_ram,
                  m.start_date,
                  m.end_date,
                  (m.start_date = m.end_date) as is_single_date,
                  DATE_PART('day', NOW() - m.end_date) <= $maxDays as display_last_mate_info
                FROM animal a
                  INNER JOIN mate m ON m.stud_ewe_id = a.id
                  INNER JOIN declare_nsfo_base dnb on m.id = dnb.id
                  INNER JOIN animal r ON r.id = m.stud_ram_id
                  LEFT JOIN litter l ON l.mate_id = m.id
                WHERE (dnb.request_state = '".RequestStateType::FINISHED."' 
                    OR dnb.request_state = '".RequestStateType::FINISHED_WITH_WARNING."') AND
                  a.id = $animalId
                    AND l.id ISNULL
                ORDER BY end_date DESC, start_date DESC LIMIT 1";
        $result = $this->em->getConnection()->query($sql)->fetch();

        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::LAST_MATE] = $result;
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::LAST_MATE][ReportLabel::IS_EMPTY] = empty($result);
    }

    /**
     * @param $key
     * @param $array
     * @param string $nullFiller
     * @return string|null
     */
    private function getNullCheckedArrayValue($key, $array, $nullFiller = self::GENERAL_NULL_FILLER)
    {
        return ArrayUtil::get($key, $array, $nullFiller);
    }

    /**
     * @param $value
     * @return string
     */
    private function nullFillString($value)
    {
        return Utils::fillNullOrEmptyString($value, self::GENERAL_NULL_FILLER);
    }

    /**
     * @param $value
     * @return string
     */
    private function fillZeroFloat($value)
    {
        return Utils::fillZeroFloat($value,self::GENERAL_NULL_FILLER);
    }


    /**
     * @param $animalKey
     * @param $sectionType
     */
    private function setDisplayZooTechnicalData($animalKey, $sectionType)
    {
        if ($animalKey === ReportLabel::CHILD_KEY) {
            // These values should be set in the beginning!
            /** @var PedigreeRegister $pedigreeRegister */
            $pedigreeRegister = $this->data[ReportLabel::PEDIGREE_REGISTER];
            $isOfficiallyRecognizedPedigreeRegister = $pedigreeRegister && $pedigreeRegister->isOfficiallyRecognized();
            $actionByIsSuperAdmin = $this->data[ReportLabel::ACTION_BY_IS_SUPER_ADMIN];

            $displayZooTechnicalData =
                $actionByIsSuperAdmin &&
                $isOfficiallyRecognizedPedigreeRegister &&
                $sectionType === SectionUtil::MAIN_SECTION
            ;
            $this->data[ReportLabel::DISPLAY_ZOO_TECHNICAL_DATA] = $displayZooTechnicalData;
        }
    }

}