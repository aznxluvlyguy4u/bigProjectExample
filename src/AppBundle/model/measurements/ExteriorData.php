<?php


namespace AppBundle\model\measurements;


class ExteriorData extends MeasurementData implements MeasurementDataInterface
{
    /** @var float */
    public $skull;

    /** @var float */
    public $muscularity;

    /** @var float */
    public $proportion;

    /** @var float */
    public $exteriorType;

    /** @var float */
    public $legWork;

    /** @var float */
    public $fur;

    /** @var float */
    public $generalAppearance;

    /** @var float */
    public $height;

    /** @var float */
    public $breastDepth;

    /** @var float */
    public $torsoLength;

    /** @var float */
    public $markings;

    /** @var float */
    public $progress;

    /** @var string */
    public $kind;

    public function __construct(array $sqlResult)
    {
        parent::__construct($sqlResult);

        $this->skull = floatval($sqlResult['skull']);
        $this->muscularity = floatval($sqlResult['muscularity']);
        $this->proportion = floatval($sqlResult['proportion']);
        $this->exteriorType = floatval($sqlResult['exterior_type']);
        $this->legWork = floatval($sqlResult['leg_work']);
        $this->fur = floatval($sqlResult['fur']);
        $this->generalAppearance = floatval($sqlResult['general_appearance']);
        $this->height = floatval($sqlResult['height']);
        $this->breastDepth = floatval($sqlResult['breast_depth']);
        $this->torsoLength = floatval($sqlResult['torso_length']);
        $this->markings = floatval($sqlResult['markings']);
        $this->progress = floatval($sqlResult['progress']);
        $this->kind = $sqlResult['kind'];
    }

    public function getAnimalIdAndDate(): string
    {
        return $this->animalIdAndDate;
    }
}
