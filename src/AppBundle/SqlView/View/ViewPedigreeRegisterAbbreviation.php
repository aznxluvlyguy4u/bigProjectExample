<?php


namespace AppBundle\SqlView\View;

use JMS\Serializer\Annotation as JMS;

class ViewPedigreeRegisterAbbreviation implements SqlViewInterface
{
    /**
     * @var integer
     * @JMS\Type("integer")
     */
    private $pedigreeRegisterId;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $abbreviation;

    /**
     * @return string
     */
    static function getPrimaryKeyName()
    {
        return 'pedigree_register_id';
    }

    /**
     * @return int
     */
    public function getPrimaryKey()
    {
        return $this->getPedigreeRegisterId();
    }

    /**
     * @return int
     */
    public function getPedigreeRegisterId()
    {
        return $this->pedigreeRegisterId;
    }

    /**
     * @param int $pedigreeRegisterId
     * @return ViewPedigreeRegisterAbbreviation
     */
    public function setPedigreeRegisterId($pedigreeRegisterId)
    {
        $this->pedigreeRegisterId = $pedigreeRegisterId;
        return $this;
    }

    /**
     * @return string
     */
    public function getAbbreviation()
    {
        return $this->abbreviation;
    }

    /**
     * @param string $abbreviation
     * @return ViewPedigreeRegisterAbbreviation
     */
    public function setAbbreviation($abbreviation)
    {
        $this->abbreviation = $abbreviation;
        return $this;
    }



}