<?php

namespace AppBundle\JsonFormat;


use AppBundle\Entity\Tag;

/*
 *
 */
class TagJsonFormat
{
    /**
     * @var string
     */
    private $ulnCountryCode;

    /**
     * @var string
     */
    private $ulnNumber;

    /**
     * @return string
     */
    public function getUlnNumber()
    {
        return $this->ulnNumber;
    }

    /**
     * @param string $ulnNumber
     */
    public function setUlnNumber($ulnNumber)
    {
        $this->ulnNumber = $ulnNumber;
    }

    /**
     * @return string
     */
    public function getUlnCountryCode()
    {
        return $this->ulnCountryCode;
    }

    /**
     * @param string $ulnCountryCode
     */
    public function setUlnCountryCode($ulnCountryCode)
    {
        $this->ulnCountryCode = $ulnCountryCode;
    }

    public function setTag(Tag $tag)
    {
        $this->setUlnCountryCode($tag->getUlnCountryCode());
        $this->setUlnNumber($tag->getUlnNumber());
    }
}