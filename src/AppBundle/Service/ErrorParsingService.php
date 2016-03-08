<?php

namespace AppBundle\Service;

use Symfony\Component\Validator\ConstraintViolationList;

class ErrorParsingService
{
    public function parse(ConstraintViolationList $errors)
    {
        $array = array();

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $array[] = array(
                    'property' => $error->getPropertyPath(),
                    'value' => $error->getInvalidValue(),
                    'message' => $error->getMessage(),
                );
            }
        } else {
            $array = null;
        }

        return $array;
    }
}
