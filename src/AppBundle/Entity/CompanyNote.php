<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use \DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class CompanyNote
 * @ORM\Entity(repositoryClass="AppBundle\Entity\CompanyNoteRepository")
 * @package AppBundle\Entity
 */
class CompanyNote
{
    use EntityClassInfo;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $creationDate;

    /**
     * @var Employee
     *
     * @ORM\ManyToOne(targetEntity="Employee", inversedBy="notes", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Employee")
     */
    private $creator;

    /**
     * @var Company
     *
     * @ORM\ManyToOne(targetEntity="Company", inversedBy="notes", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Company")
     */
    private $company;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Type("string")
     */
    private $note;

    /**
     * CompanyNotes constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param DateTime $creationDate
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;
    }

    /**
     * @return Employee
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @param Employee $creator
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;
    }

    /**
     * @return Company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param Company $company
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }

    /**
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @param string $note
     */
    public function setNote($note)
    {
        $this->note = $note;
    }
}