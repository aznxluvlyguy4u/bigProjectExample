<?php

namespace AppBundle\Validation;

use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use Doctrine\Common\Collections\Collection;
use AppBundle\Constant\Constant;
use \Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class WeightValidator
 * @package AppBundle\Validation
 */
class WeightValidator
{
    const DEFAULT_MAX_WEIGHT = 200.00;
    const DEFAULT_MIN_WEIGHT = 0.00;
    const RESPONSE_INVALID_INPUT_SINGLE = "ONE WEIGHT IS INVALID";
    const RESPONSE_INVALID_INPUT_PLURAL = "SOME WEIGHTS ARE INVALID";
    const RESPONSE_INVALID_INPUT_TOTAL = "ALL WEIGHTS ARE INVALID";
    const ERROR_CODE = 428;

    private $areWeightsValid;
    private $minWeight;
    private $measurements;
    private $errors;

    /**
     * PasswordValidator constructor.
     * @param Collection $content
     * @param float $minWeight
     * @param float $maxWeight
     */
    public function __construct(Collection $content,
                                $minWeight = WeightValidator::DEFAULT_MIN_WEIGHT,
                                $maxWeight = WeightValidator::DEFAULT_MAX_WEIGHT)
    {
        //Initialize variables
        $this->errors = array();

        //Set given values
        $this->measurements = $content->get(JsonInputConstant::WEIGHT_MEASUREMENTS);
        $this->minWeight = $minWeight;
        $this->maxWeight = $maxWeight;

        //Validate password
        $this->validate();
    }

    public function getAreWeightsValid() { return $this->areWeightsValid; }

    /**
     *
     */
    private function validate()
    {
        //Initialize default validity
        $this->areWeightsValid = true;

        foreach($this->measurements as $measurement)
        {
            //Conditions that may invalidate a password
            $this->verifyWeightMinValue($measurement);
            $this->verifyWeightMaxValue($measurement);
        }
    }

    /**
     * @param array $measurement
     */
    private function verifyWeightMinValue($measurement)
    {
        if($measurement[JsonInputConstant::WEIGHT] < $this->minWeight)
        {
            $this->areWeightsValid = false;
            $ulnString = $measurement[Constant::ULN_COUNTRY_CODE_NAMESPACE] . $measurement[Constant::ULN_NUMBER_NAMESPACE];
            $this->errors[] = 'WEIGHT OF ' . $ulnString . ' IS LESS THAN ' . $this->minWeight . '.';
        }
    }

    /**
     * @param array $measurement
     */
    private function verifyWeightMaxValue($measurement)
    {
        if($measurement[JsonInputConstant::WEIGHT] > $this->maxWeight)
        {
            $this->areWeightsValid = false;
            $ulnString = $measurement[Constant::ULN_COUNTRY_CODE_NAMESPACE] . $measurement[Constant::ULN_NUMBER_NAMESPACE];
            $this->errors[] = 'WEIGHT OF ' . $ulnString . ' IS MORE THAN ' . $this->maxWeight . '.';
        }
    }

    /**
     * Only create this JsonResponse when there actually are errors.
     *
     * @return JsonResponse
     */
    public function createJsonErrorResponse()
    {
        if(sizeof($this->errors) == 1){
            $errorMessage = WeightValidator::RESPONSE_INVALID_INPUT_SINGLE;

        } else if (sizeof($this->errors) == sizeof($this->measurements)) {
            $errorMessage = WeightValidator::RESPONSE_INVALID_INPUT_TOTAL;

        } else {
            $errorMessage = WeightValidator::RESPONSE_INVALID_INPUT_PLURAL;
        }

        $result = array(
            Constant::CODE_NAMESPACE => WeightValidator::ERROR_CODE,
            Constant::MESSAGE_NAMESPACE => $errorMessage,
            Constant::ERRORS_NAMESPACE => $this->errors);

        return new JsonResponse($result, WeightValidator::ERROR_CODE);
    }

}