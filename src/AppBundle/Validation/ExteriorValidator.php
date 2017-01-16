<?php


namespace AppBundle\Validation;


use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Inspector;
use AppBundle\Entity\InspectorAuthorization;
use AppBundle\Entity\InspectorAuthorizationRepository;
use AppBundle\Entity\InspectorRepository;
use AppBundle\Entity\Person;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

class ExteriorValidator extends BaseValidator
{
    const MIN_EXTERIOR_VALUE = 69;
    const MAX_EXTERIOR_VALUE = 99;

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

    /** @var array */
    private $allowedExteriorCodes;

    /** @var array */
    private $authorizedInspectorIds;

    /** @var boolean */
    private $allowBlankInspector;

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

        parent::__construct($em, $content);

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
            }
        } else {
            $measurementDateTimeString = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::MEASUREMENT_DATE, $this->content);
            //Only use date part of dateTime
            $measurementDateString = Utils::getNullCheckedArrayValue(0, explode('T', $measurementDateTimeString));
            $isDateStringValid = TimeUtil::isFormatYYYYMMDD($measurementDateString);
            if($isDateStringValid) {
                $this->measurementDate = new \DateTime($measurementDateString);
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


    private function validateExteriorKind()
    {
        $code = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::KIND, $this->content);
        if(array_key_exists($code, $this->allowedExteriorCodes)) {
            $this->kind = strval($code);
            $isValidValue = true;

        } else {
            $this->kind = null;
            $isValidValue = false;
            $this->errors[] = "KIND VALUE MUST BE '".implode("' OR '", $this->allowedExteriorCodes)."'";
        }
        return $isValidValue;
    }


    /**
     * @param string $keyOfExteriorValue
     * @return bool
     */
    private function validateExteriorNumberValue($keyOfExteriorValue)
    {
        $value = Utils::getNullCheckedArrayCollectionValue($keyOfExteriorValue, $this->content);
        if($value == 0 || (self::MIN_EXTERIOR_VALUE <= intval($value) && intval($value) <= self::MAX_EXTERIOR_VALUE)) {
            $value = floatval($value);
            $isValidValue = true;

        } else {
            $value = 0.0;
            $isValidValue = false;
            $this->errors[] = $keyOfExteriorValue. ' VALUE MUST BE 69 <= X <= 99. OR FOR AN EMPTY VALUE IS MUST BE AN EMPTY STRING OR 0.';
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


}