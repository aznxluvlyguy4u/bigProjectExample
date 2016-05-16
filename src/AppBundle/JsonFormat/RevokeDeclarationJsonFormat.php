<?php

namespace AppBundle\JsonFormat;


class RevokeDeclarationJsonFormat
{
    private $messageNumber;

    /**
     * @return string
     */
    public function getMessageNumber()
    {
        return $this->messageNumber;
    }

    /**
     * @param string $messageNumber
     */
    public function setMessageNumber($messageNumber)
    {
        $this->messageNumber = $messageNumber;
    }

}