<?php


namespace AppBundle\Entity;

use AppBundle\Enumerator\GenderType;
use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class TagSyncErrorLog
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\TagSyncErrorLogRepository")
 * @package AppBundle\Entity
 */
class TagSyncErrorLog
{
    use EntityClassInfo;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $logDate;

    /**
     * @var RetrieveTags
     *
     * @ORM\ManyToOne(targetEntity="RetrieveTags")
     * @ORM\JoinColumn(name="retrieve_tags_id", referencedColumnName="id")
     */
    private $retrieveTags;

    /**
     * @var string
     * @JMS\Type("string")
     * @Assert\NotBlank
     * @ORM\Column(type="string", nullable=false)
     */
    private $ulnNumber;

    /**
     * @var string
     * @JMS\Type("string")
     * @Assert\NotBlank
     * @Assert\Regex("/([A-Z]{2})\b/")
     * @Assert\Length(max = 2)
     * @ORM\Column(type="string", nullable=false)
     */
    private $ulnCountryCode;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @JMS\Type("boolean")
     */
    private $isBlankNeuter;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     */
    private $isFixed;


    /**
     * TagSyncErrorLog constructor.
     * @param RetrieveTags $retrieveTags
     * @param Animal $animal
     */
    public function __construct(RetrieveTags $retrieveTags, Animal $animal)
    {
        $this->logDate = new \DateTime();
        $this->retrieveTags = $retrieveTags;
        $this->setBlockingAnimal($animal);
        $this->isFixed = false;
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
     * @return TagSyncErrorLog
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLogDate()
    {
        return $this->logDate;
    }

    /**
     * @param \DateTime $logDate
     * @return TagSyncErrorLog
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;
        return $this;
    }

    /**
     * @return RetrieveTags
     */
    public function getRetrieveTags()
    {
        return $this->retrieveTags;
    }

    /**
     * @param RetrieveTags $retrieveTags
     * @return TagSyncErrorLog
     */
    public function setRetrieveTags($retrieveTags)
    {
        $this->retrieveTags = $retrieveTags;
        return $this;
    }

    /**
     * @param Animal $blockingAnimal
     * @return TagSyncErrorLog
     */
    public function setBlockingAnimal($blockingAnimal)
    {
        if($blockingAnimal instanceof Animal) {
            $this->setUlnCountryCode($blockingAnimal->getUlnCountryCode());
            $this->setUlnNumber($blockingAnimal->getUlnNumber());
            $this->setIsBlankNeuter(
                $blockingAnimal->getGender() == GenderType::NEUTER &&
                $blockingAnimal->getName() === null && $blockingAnimal->getPedigreeNumber() === null &&
                $blockingAnimal->getParentFather() === null && $blockingAnimal->getParentMother() === null &&
                $blockingAnimal->getBreedType() === null && $blockingAnimal->getBreedCode() === null
            );
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getUlnNumber()
    {
        return $this->ulnNumber;
    }

    /**
     * @param string $ulnNumber
     * @return TagSyncErrorLog
     */
    public function setUlnNumber($ulnNumber)
    {
        $this->ulnNumber = $ulnNumber;
        return $this;
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
     * @return TagSyncErrorLog
     */
    public function setUlnCountryCode($ulnCountryCode)
    {
        $this->ulnCountryCode = $ulnCountryCode;
        return $this;
    }

    /**
     * @return bool
     */
    public function isBlankNeuter()
    {
        return $this->isBlankNeuter;
    }

    /**
     * @param bool $isBlankNeuter
     * @return TagSyncErrorLog
     */
    public function setIsBlankNeuter($isBlankNeuter)
    {
        $this->isBlankNeuter = $isBlankNeuter;
        return $this;
    }

    /**
     * @return bool
     */
    public function isFixed()
    {
        return $this->isFixed;
    }

    /**
     * @param bool $isFixed
     * @return TagSyncErrorLog
     */
    public function setIsFixed($isFixed)
    {
        $this->isFixed = $isFixed;
        return $this;
    }



}