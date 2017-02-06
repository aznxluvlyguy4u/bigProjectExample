<?php


namespace AppBundle\Validation;


use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Exterior;
use AppBundle\Entity\ExteriorRepository;
use AppBundle\Entity\Inspector;
use AppBundle\Entity\InspectorAuthorization;
use AppBundle\Entity\InspectorAuthorizationRepository;
use AppBundle\Entity\InspectorRepository;
use AppBundle\Entity\Measurement;
use AppBundle\Entity\MeasurementRepository;
use AppBundle\Entity\PedigreeRegister;
use AppBundle\Entity\Person;
use AppBundle\JsonFormat\ValidationResults;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;

class ExteriorValidator extends BaseValidator
{
    const DEFAULT_MIN_EXTERIOR_VALUE = 69;
    const DEFAULT_MAX_EXTERIOR_VALUE = 99;
    const ALLOW_BLANK_KIND = true;
    const ALLOW_ANIMALS_WITHOUT_A_PEDIGREE_REGISTER = false;

    /** @var string */
    private $measurementDateString;

    /** @var Inspector */
    private $inspector;

    /** @var string */
    private $kind;

    /** @var float */
    private $skull;

    /** @var float */
    private $progress;

    /** @var float */
    private $muscularity;

    /** @var float */
    private $proportion;

    /** @var float */
    private $exteriorType;

    /** @var float */
    private $legWork;

    /** @var float */
    private $fur;

    /** @var float */
    private $generalAppearence;

    /** @var float */
    private $height;

    /** @var float */
    private $breastDepth;

    /** @var float */
    private $torsoLength;

    /** @var float */
    private $markings;

    /** @var \DateTime */
    private $measurementDate;

    /** @var \DateTime */
    private $newMeasurementDate;

    /** @var array */
    private $allowedExteriorCodes;

    /** @var array */
    private $authorizedInspectorIds;

    /** @var boolean */
    private $allowBlankInspector;

    /** @var boolean */
    private $isEdit;

    /** @var ObjectManager */
    private $em;

    /** @var Connection */
    private $conn;

    /** @var Animal */
    private $animal;

    /**
     * ExteriorValidator constructor.
     * @param ObjectManager $em
     * @param ArrayCollection $content
     * @param array $allowedExteriorCodes
     * @param string $ulnString
     * @param boolean $allowBlankInspector
     * @param string $measurementDateString
     */
    public function __construct(ObjectManager $em, ArrayCollection $content, $allowedExteriorCodes, $ulnString, $allowBlankInspector, $measurementDateString = null)
    {
        $this->measurementDateString = $measurementDateString;
        $this->allowedExteriorCodes = $allowedExteriorCodes;
        $this->allowBlankInspector = $allowBlankInspector;
        $this->isEdit = $measurementDateString != null;
        $this->em = $em;
        $this->conn = $em->getConnection();

        parent::__construct($em, $content);

        //Validate Animal data
        $animal = null;
        if($ulnString) {
            /** @var AnimalRepository $animalRepository */
            $animalRepository = $em->getRepository(Animal::class);
            $animal = $animalRepository->findAnimalByUlnString($ulnString);

            if($animal == null) {
                $this->errors[] = 'No animal found for given uln';
                return;
            }
        }
        $this->animal = $animal;

        if($animal->getPedigreeRegister() == null && self::ALLOW_ANIMALS_WITHOUT_A_PEDIGREE_REGISTER) {
            $this->errors[] = 'The animal is not part of a pedigreeRegister';
            return;
        }


        /** @var InspectorAuthorizationRepository $repository */
        $repository = $em->getRepository(InspectorAuthorization::class);
        $this->authorizedInspectorIds = $repository->getAuthorizedInspectorIdsExteriorByUln($ulnString);

        $this->inspector = null;
        $this->isInputValid = $this->validateContentArray();
    }


    /**
     * @return bool
     */
    private function validateContentArray()
    {
        $validityCheck = true;

        if(is_string($this->measurementDateString)) {
            $isDateStringValid = TimeUtil::isFormatYYYYMMDD($this->measurementDateString);
            if($isDateStringValid) {
                $this->measurementDate = new \DateTime($this->measurementDateString);

                $newMeasurementDateTimeString = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::MEASUREMENT_DATE, $this->content);
                //Only use date part of dateTime
                $newMeasurementDateString = Utils::getNullCheckedArrayValue(0, explode('T', $newMeasurementDateTimeString));
                $isNewDateStringValid = TimeUtil::isFormatYYYYMMDD($newMeasurementDateString);
                if($isNewDateStringValid) {
                    $newMeasurementDate = new \DateTime($newMeasurementDateString);

                    //Check for duplicate dates only if the date is actually changed
                    if(TimeUtil::isDateTimesOnTheSameDay($newMeasurementDate, $this->measurementDate) && !$this->isEdit) {

                        if($this->doesExteriorMeasurementAlreadyExistOnDate($newMeasurementDate)) {
                            $this->errors[] = 'There already exists another exterior for this animal on the given date. Choose another date';
                            $this->isInputValid = false;
                            return false;
                        }
                    }

                    $this->newMeasurementDate = $newMeasurementDate;
                }  else {
                    $this->errors[] = 'Given newMeasurementDate in body does not have a valid format. It must have the following format YYYY-MM-DD';
                    $this->isInputValid = false;
                }
            } else {
                $this->errors[] = 'Given measurementDate in url does not have a valid format. It must have the following format YYYY-MM-DD';
                $this->isInputValid = false;
            }

        } else {
            $measurementDateTimeString = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::MEASUREMENT_DATE, $this->content);
            //Only use date part of dateTime
            $measurementDateString = Utils::getNullCheckedArrayValue(0, explode('T', $measurementDateTimeString));
            $isDateStringValid = TimeUtil::isFormatYYYYMMDD($measurementDateString);
            if($isDateStringValid) {
                $measurementDate = new \DateTime($measurementDateString);

                if($this->doesExteriorMeasurementAlreadyExistOnDate($measurementDate)) {
                    $this->errors[] = 'There already exists another exterior for this animal on the given date. Choose another date';
                    $this->isInputValid = false;
                    return false;
                }

                $this->measurementDate = $measurementDate;
            }
        }

        $isValid = $this->validateExteriorKind();
        if(!$isValid) { $validityCheck = false; }

        $exteriorValueKeys = [
            JsonInputConstant::SKULL,
            JsonInputConstant::PROGRESS,
            JsonInputConstant::MUSCULARITY,
            JsonInputConstant::PROPORTION,
            JsonInputConstant::TYPE,
            JsonInputConstant::LEG_WORK,
            JsonInputConstant::FUR,
            JsonInputConstant::GENERAL_APPEARANCE,
            JsonInputConstant::HEIGHT,
            JsonInputConstant::BREAST_DEPTH,
            JsonInputConstant::TORSO_LENGTH,
            JsonInputConstant::MARKINGS,
        ];

        foreach ($exteriorValueKeys as $exteriorValueKey) {
            $isValid = $this->validateExteriorNumberValue($exteriorValueKey);
            if(!$isValid) { $validityCheck = false; }
        }

        $isValidInspectorIdInput = $this->validateInspectorId();
        if(!$isValidInspectorIdInput) { $validityCheck = false; }

        return $validityCheck;
    }


    /**
     * @param \DateTime $measurementDate
     * @return bool
     */
    private function doesExteriorMeasurementAlreadyExistOnDate(\DateTime $measurementDate, $defaultResponse = true)
    {
        if($this->animal instanceof Animal) {
            $animalId = $this->animal->getId();

            $sql = "SELECT x.id FROM exterior x
                      INNER JOIN measurement m ON x.id = m.id
                    WHERE m.is_active = TRUE AND animal_id = ".$animalId." AND DATE(m.measurement_date) = DATE('".TimeUtil::getTimeStampForSql($measurementDate)."')";
            $count = $this->conn->query($sql)->rowCount();

            return $count > 0;
        }
        return $defaultResponse;
    }


    private function validateExteriorKind()
    {
        $code = $this->content->get(JsonInputConstant::KIND);
        if(array_key_exists($code, $this->allowedExteriorCodes) || //must be a valid exteriorKindCode OR
            (!$this->content->containsKey(JsonInputConstant::KIND) && self::ALLOW_BLANK_KIND) // must be blank IF that is allowed
        ) {
            $this->kind = strval($code);
            $isValidValue = true;

        } else {
            $this->kind = null;
            $isValidValue = false;
            $allowBlankErrorText = self::ALLOW_BLANK_KIND ? ' OR MUST BE BLANK' : '';
            $this->errors[] = "KIND VALUE MUST BE '".implode("' OR '", $this->allowedExteriorCodes)."'".$allowBlankErrorText;
        }
        return $isValidValue;
    }


    /**
     * @param string $keyOfExteriorValue
     * @return bool
     */
    private function validateExteriorNumberValue($keyOfExteriorValue)
    {
        $value = $this->content->get($keyOfExteriorValue);

        //First convert comma to dot
        $value = strtr($value, [',' => '.']);
        strpos('.', $value);

        if($this->validateNumberValue($keyOfExteriorValue, $value)) {
            $value = floatval($value);
            $isValidValue = true;

        } else {
            $value = 0.0;
            $isValidValue = false;
            $minValue = self::getMinValues()[$keyOfExteriorValue];
            $maxValue = self::getMaxValues()[$keyOfExteriorValue];
            self::getMaxValues()[$keyOfExteriorValue];
            $this->errors[] = $keyOfExteriorValue. ' VALUE MUST BE '.$minValue.' <= X <= '.$maxValue.'. OR FOR AN EMPTY VALUE IS MUST BE AN EMPTY STRING OR 0.';
        }

        switch ($keyOfExteriorValue) {
            case JsonInputConstant::KIND:           $this->kind = $value; break;
            case JsonInputConstant::SKULL:          $this->skull = $value; break;
            case JsonInputConstant::PROGRESS:       $this->progress = $value; break;
            case JsonInputConstant::MUSCULARITY:    $this->muscularity = $value; break;
            case JsonInputConstant::PROPORTION:     $this->proportion = $value; break;
            case JsonInputConstant::TYPE:           $this->exteriorType = $value; break;
            case JsonInputConstant::LEG_WORK:       $this->legWork = $value; break;
            case JsonInputConstant::FUR:            $this->fur = $value; break;
            case JsonInputConstant::GENERAL_APPEARANCE: $this->generalAppearence = $value; break;
            case JsonInputConstant::HEIGHT:         $this->height = $value; break;
            case JsonInputConstant::BREAST_DEPTH:   $this->breastDepth = $value; break;
            case JsonInputConstant::TORSO_LENGTH:   $this->torsoLength = $value; break;
            case JsonInputConstant::MARKINGS:       $this->markings = $value; break;
        }

        return $isValidValue;
    }


    /**
     * @param string $keyOfExteriorValue
     * @param float|int $value
     * @return bool
     */
    public function validateNumberValue($keyOfExteriorValue, $value)
    {
        $minValues = static::getMinValues();
        $maxValues = static::getMaxValues();

        $minValue = $minValues[$keyOfExteriorValue];
        $maxValue = $maxValues[$keyOfExteriorValue];


        //Can only contain one comma or one dot
        if(substr_count($value,'.') > 1) {
            $this->errors[] = 'The value for '.$keyOfExteriorValue.' may not contain more than one decimal point';
            return false;
        }

        if(!Validator::isStringFloatFormat($value)) {
            $this->errors[] = 'The value for '.$keyOfExteriorValue.' must be in the format of a float';
            return false;
        }

        //Value must be empty (=0) or must be between the allowed min and max values
        return $value == 0 || ($minValue <= intval($value) && intval($value) <= $maxValue);
    }


    /**
     * @return array
     */
    public static function getMinValues()
    {
        return [
            JsonInputConstant::SKULL        => self::DEFAULT_MIN_EXTERIOR_VALUE,
            JsonInputConstant::PROGRESS     => self::DEFAULT_MIN_EXTERIOR_VALUE,
            JsonInputConstant::MUSCULARITY  => self::DEFAULT_MIN_EXTERIOR_VALUE,
            JsonInputConstant::PROPORTION   => self::DEFAULT_MIN_EXTERIOR_VALUE,
            JsonInputConstant::TYPE         => self::DEFAULT_MIN_EXTERIOR_VALUE,
            JsonInputConstant::LEG_WORK     => self::DEFAULT_MIN_EXTERIOR_VALUE,
            JsonInputConstant::FUR          => self::DEFAULT_MIN_EXTERIOR_VALUE,
            JsonInputConstant::GENERAL_APPEARANCE => self::DEFAULT_MIN_EXTERIOR_VALUE,
            JsonInputConstant::HEIGHT       => 0,
            JsonInputConstant::BREAST_DEPTH => 0,
            JsonInputConstant::TORSO_LENGTH => 0,
            JsonInputConstant::MARKINGS     => self::DEFAULT_MIN_EXTERIOR_VALUE,
        ];
    }


    /**
     * @return array
     */
    public static function getMaxValues()
    {
        return [
            JsonInputConstant::SKULL        => self::DEFAULT_MAX_EXTERIOR_VALUE,
            JsonInputConstant::PROGRESS     => self::DEFAULT_MAX_EXTERIOR_VALUE,
            JsonInputConstant::MUSCULARITY  => self::DEFAULT_MAX_EXTERIOR_VALUE,
            JsonInputConstant::PROPORTION   => self::DEFAULT_MAX_EXTERIOR_VALUE,
            JsonInputConstant::TYPE         => self::DEFAULT_MAX_EXTERIOR_VALUE,
            JsonInputConstant::LEG_WORK     => self::DEFAULT_MAX_EXTERIOR_VALUE,
            JsonInputConstant::FUR          => self::DEFAULT_MAX_EXTERIOR_VALUE,
            JsonInputConstant::GENERAL_APPEARANCE => self::DEFAULT_MAX_EXTERIOR_VALUE,
            JsonInputConstant::HEIGHT       => self::DEFAULT_MAX_EXTERIOR_VALUE,
            JsonInputConstant::BREAST_DEPTH => self::DEFAULT_MAX_EXTERIOR_VALUE,
            JsonInputConstant::TORSO_LENGTH => self::DEFAULT_MAX_EXTERIOR_VALUE,
            JsonInputConstant::MARKINGS     => self::DEFAULT_MAX_EXTERIOR_VALUE,
        ];
    }


    private function validateInspectorId()
    {
        $inspectorNotNull = $this->content->containsKey(JsonInputConstant::INSPECTOR_ID);
        $isInspectorBlank = !$inspectorNotNull;

        if($isInspectorBlank && $this->allowBlankInspector) {
            /*
             * If the inspector is supposed to be blank, then the api should receive a content without an "inspector_id" key.
             * If the "inspector_id" key exists then it is mandatory for for the value to be a valid inspectorId.
             */
            return true;
        }

        $inspectorId = $this->content->get(JsonInputConstant::INSPECTOR_ID);

        /** @var InspectorRepository $repository */
        $inspectorRepository = $this->manager->getRepository(Inspector::class);
        $inspector = $inspectorRepository->findOneBy(['personId' => $inspectorId]);

        if($inspector == null) {
            $this->errors[] = 'INSPECTOR DOES NOT EXIST';
            return false;
        } elseif(!array_key_exists(strval($inspectorId), $this->authorizedInspectorIds)) {
            $this->errors[] = 'INSPECTOR EXISTS, BUT IS NOT A VALID INSPECTOR FOR THIS PEDIGREE';
            return false;
        }
        $this->inspector = $inspector;

        return true;
    }


    /**
     * @return Inspector|null
     */
    public function getInspector()
    {
        return $this->inspector;
    }

    /**
     * @return string
     */
    public function getKind()
    {
        return $this->kind;
    }

    /**
     * @return float
     */
    public function getSkull()
    {
        return $this->skull;
    }

    /**
     * @return float
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * @return float
     */
    public function getMuscularity()
    {
        return $this->muscularity;
    }

    /**
     * @return float
     */
    public function getProportion()
    {
        return $this->proportion;
    }

    /**
     * @return float
     */
    public function getExteriorType()
    {
        return $this->exteriorType;
    }

    /**
     * @return float
     */
    public function getLegWork()
    {
        return $this->legWork;
    }

    /**
     * @return float
     */
    public function getFur()
    {
        return $this->fur;
    }

    /**
     * @return float
     */
    public function getGeneralAppearence()
    {
        return $this->generalAppearence;
    }

    /**
     * @return float
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @return float
     */
    public function getBreastDepth()
    {
        return $this->breastDepth;
    }

    /**
     * @return float
     */
    public function getTorsoLength()
    {
        return $this->torsoLength;
    }

    /**
     * @return float
     */
    public function getMarkings()
    {
        return $this->markings;
    }

    /**
     * @return \DateTime
     */
    public function getMeasurementDate()
    {
        return $this->measurementDate;
    }

    /**
     * @return \DateTime
     */
    public function getNewMeasurementDate()
    {
        return $this->newMeasurementDate;
    }

    /**
     * @return Animal
     */
    public function getAnimal()
    {
        return $this->animal;
    }


    /**
     * @param ObjectManager $em
     * @param Animal $animal
     * @param $measurementDateString
     * @return ValidationResults
     */
    public static function validateDeactivation(ObjectManager $em, $animal, $measurementDateString)
    {
        $validationResults = new ValidationResults(true);

        if(!($animal instanceof Animal)) {
            $validationResults->addError('Er is geen correct dier meegegeven');
            $validationResults->setIsValid(false);
            $animal = null;
        }

        $isDateStringValid = TimeUtil::isFormatYYYYMMDD($measurementDateString);
        if(!$isDateStringValid) {
            $validationResults->addError('Het format van de datum is onjuist. Het moet dit format hebben JJJJ-MM-DD');
            $validationResults->setIsValid(false);
            $measurementDate = null;
        } else {
            $measurementDate = new \DateTime($measurementDateString);
        }

        if(!$validationResults->isValid()) {
            return $validationResults;
        }
        //Only continue if other inputs were valid

        $exterior = null;
        if($animal != null && $measurementDate != null) {
            /** @var ExteriorRepository $exteriorRepository */
            $exteriorRepository = $em->getRepository(Exterior::class);
            /** @var Exterior $exterior */
            $exterior = $exteriorRepository->findOneBy(['measurementDate' => $measurementDate, 'animal' => $animal, 'isActive' => true]);

            if($exterior != null) {
                $validationResults->setIsValid(true);
                $validationResults->setValidResultObject($exterior);
                return $validationResults;
            } else {
                $validationResults->setIsValid(false);
                $validationResults->addError('Geen actief exterieur meting gevonden voor gegeven uln en datum');
            }
        }

        $validationResults->setIsValid(false);
        return $validationResults;
    }
}