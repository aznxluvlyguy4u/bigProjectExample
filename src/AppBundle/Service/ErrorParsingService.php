<?php

namespace AppBundle\Service;

use Symfony\Component\Validator\ConstraintViolationList;

class ErrorParsingService
{
    public function parse(ConstraintViolationList $errors)
    {
        $array['error'] = array();

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $array['error'][] = array(
                    'property' => $error->getPropertyPath(),
                    'value' => $error->getInvalidValue(),
                    'message' => $error->getMessage(),
                );
            }
        }

        return $array;
    }
}
