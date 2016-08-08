<?php

namespace AppBundle\Validation;


use AppBundle\Constant\Constant;
use AppBundle\Entity\Employee;
use AppBundle\Util\AnimalArrayReader;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\JsonResponse;

class EmployeeValidator
{
    const ERROR_CODE = 428;
    const ERROR_MESSAGE = 'NOT A VALID EMPLOYEE ACCESS TOKEN';
    const VALID_CODE = 200;
    const VALID_MESSAGE = 'ACCESS TOKEN IS VALID';

    /** @var boolean */
    private $isValid;

    /** @var Employee */
    private $employee;

    /**
     * UbnValidator constructor.
     * @param Employee $employee
     */
    public function __construct(Employee $employee = null)
    {
        $this->employee = $employee;
        $this->validate();
    }

    /**
     * @return bool
     */
    public function getIsValid() {
        return $this->isValid;
    }

    private function validate()
    {
        if($this->employee == null) {
            $this->isValid = false;
        } else {
            $this->isValid = true;
        }
    }

    /**
     * Only create this JsonResponse when there actually are errors.
     *
     * @return JsonResponse
     */
    public function createJsonErrorResponse()
    {
        $uln = null;
        $pedigree = null;

        if($this->isValid) {
            $code = self::VALID_CODE;
            $message = self::VALID_MESSAGE;
        } else {
            $code = self::ERROR_CODE;
            $message = self::ERROR_MESSAGE;
        }

        $result = array(
            Constant::CODE_NAMESPACE => $code,
            Constant::MESSAGE_NAMESPACE => $message);

        return new JsonResponse($result, $code);
    }

}