<?php


namespace AppBundle\model\request;

use JMS\Serializer\Annotation as JMS;

class AnimalAnnotationRequest
{
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $body;

    /**
     * @var string
     * @return string
     */
    public function getBody(): string
    {
        return empty($this->body) ? '' : strval($this->body);
    }
}
