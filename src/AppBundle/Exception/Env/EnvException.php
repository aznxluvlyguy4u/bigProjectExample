<?php


namespace AppBundle\Exception\Env;


use Throwable;

class EnvException extends \Exception
{
    public function __construct(
        string $invalidValue,
        string $key,
        array $validOptions,
        Throwable $previous = null
    )
    {
        $message = "Environment variable '$key:$invalidValue' is invalid. Valid values: "
            .implode(',', $validOptions);
        parent::__construct($message, 500, $previous);
    }
}