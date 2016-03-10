<?php

namespace AppBundle\Service;

use Symfony\Component\HttpFoundation\Request;

class ValidateEntityService
{
    private $validator;
    private $serializer;
    private $error;

    public function __construct($validator, $serializer)
    {
        $this->validator = $validator;
        $this->serializer = $serializer;
    }

    public function validate(Request $request, $class)
    {
        $entity = $this->serializer->deserialize($request->getContent(), $class, 'json');
        $errors = $this->validator->validate($entity);

        $error_array['error'] = array();

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $error_array['error'][] = array(
                    'property' => $error->getPropertyPath(),
                    'value' => $error->getInvalidValue(),
                    'message' => $error->getMessage(),
                );
            }

            $this->error = $error_array;

            return;
        } else {
            return $entity;
        }
    }

    public function getErrors()
    {
        return $this->error;
    }
}
