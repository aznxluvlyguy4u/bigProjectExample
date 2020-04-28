<?php


namespace AppBundle\Exception;


use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BadRequestHttpExceptionWithMultipleErrors extends BadRequestHttpException
{
    /** @var array */
    private $errors;

    /**
     * @param array     $errors  The internal exception messages
     * @param \Exception $previous The previous exception
     * @param int        $code     The internal exception code
     */
    public function __construct(array $errors = [], \Exception $previous = null, $code = 0)
    {
        $this->errors = $errors;
        parent::__construct('BadRequestHttpException', $previous, $code);
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
