<?php


namespace AppBundle\SqlView\View;

use JMS\Serializer\Annotation as JMS;

class ViewPersonFullName implements SqlViewInterface
{
    /**
     * @var integer
     * @JMS\Type("integer")
     */
    private $personId;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $fullName;

    /**
     * @return string
     */
    static function getPrimaryKeyName()
    {
        return 'person_id';
    }

    /**
     * @return int
     */
    public function getPersonId()
    {
        return $this->personId;
    }

    /**
     * @return string
     */
    public function getFullName()
    {
        return $this->fullName;
    }

}