<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * This class is used to store replacementStrings from the dataImports in the database.
 * 
 * Class DataImportStringReplacement
 * @package AppBundle\Entity
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DataImportStringReplacementRepository")
 */
class DataImportStringReplacement
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Type("string")
     */
    private $primaryString;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Type("string")
     */
    private $secondaryString;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $type;

    public function __construct($primaryString = null, $secondaryString = null, $type)
    {
        $this->primaryString = $primaryString;
        $this->secondaryString = $secondaryString;
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getPrimaryString()
    {
        return $this->primaryString;
    }

    /**
     * @param string $primaryString
     */
    public function setPrimaryString($primaryString)
    {
        $this->primaryString = $primaryString;
    }

    /**
     * @return string
     */
    public function getSecondaryString()
    {
        return $this->secondaryString;
    }

    /**
     * @param string $secondaryString
     */
    public function setSecondaryString($secondaryString)
    {
        $this->secondaryString = $secondaryString;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }







}