<?php


namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class DepartArrivalTransaction
 *
 * @ORM\Table(name="depart_arrival_transaction")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DepartArrivalTransactionRepository")
 * @package AppBundle\Entity
 */
class DepartArrivalTransaction
{
    use EntityClassInfo;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Type("integer")
     * @JMS\Groups({
     * })
     */
    protected $id;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     * })
     */
    protected $logDate;

    /**
     * @var DeclareArrival|null
     *
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\DeclareArrival",
     *     mappedBy="transaction", cascade={"persist","refresh"})
     * @ORM\JoinColumn(name="arrival_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * @JMS\Type("AppBundle\Entity\DeclareArrival")
     */
    private $arrival;

    /**
     * @var DeclareDepart|null
     *
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\DeclareDepart",
     *     mappedBy="transaction", cascade={"persist","refresh"})
     * @ORM\JoinColumn(name="depart_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * @JMS\Type("AppBundle\Entity\DeclareDepart")
     */
    private $depart;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     * @JMS\Groups({
     * })
     */
    protected $isRvoMessage;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false, options={"default":true})
     * @JMS\Type("boolean")
     * @JMS\Groups({
     * })
     */
    protected $isArrivalInitiated;

    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person", cascade={"refresh"})
     * @ORM\JoinColumn(name="action_by_id", referencedColumnName="id")
     * @JMS\Groups({
     * })
     */
    protected $actionBy;

    /**
     * DepartArrivalTransaction constructor.
     */
    public function __construct()
    {
        $this->logDate = new \DateTime();
        $this->isRvoMessage = false;
        $this->isArrivalInitiated = true;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return DepartArrivalTransaction
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLogDate()
    {
        return $this->logDate;
    }

    /**
     * @param mixed $logDate
     * @return DepartArrivalTransaction
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;
        return $this;
    }

    /**
     * @return DeclareArrival|null
     */
    public function getArrival(): ?DeclareArrival
    {
        return $this->arrival;
    }

    /**
     * @param DeclareArrival|null $arrival
     * @return DepartArrivalTransaction
     */
    public function setArrival(?DeclareArrival $arrival): DepartArrivalTransaction
    {
        $this->arrival = $arrival;
        if ($arrival) {
            $arrival->setTransaction($this);
        }
        return $this;
    }

    /**
     * @return DeclareDepart|null
     */
    public function getDepart(): ?DeclareDepart
    {
        return $this->depart;
    }

    /**
     * @param DeclareDepart|null $depart
     * @return DepartArrivalTransaction
     */
    public function setDepart(?DeclareDepart $depart): DepartArrivalTransaction
    {
        $this->depart = $depart;
        if ($depart) {
            $depart->setTransaction($this);
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function isRvoMessage(): bool
    {
        return $this->isRvoMessage;
    }

    /**
     * @param bool $isRvoMessage
     * @return DepartArrivalTransaction
     */
    public function setIsRvoMessage(bool $isRvoMessage): DepartArrivalTransaction
    {
        $this->isRvoMessage = $isRvoMessage;
        return $this;
    }

    /**
     * @return bool
     */
    public function isArrivalInitiated(): bool
    {
        return $this->isArrivalInitiated;
    }

    /**
     * @param bool $isArrivalInitiated
     * @return DepartArrivalTransaction
     */
    public function setIsArrivalInitiated(bool $isArrivalInitiated): DepartArrivalTransaction
    {
        $this->isArrivalInitiated = $isArrivalInitiated;
        return $this;
    }

    /**
     * @return Person
     */
    public function getActionBy(): Person
    {
        return $this->actionBy;
    }

    /**
     * @param Person $actionBy
     * @return DepartArrivalTransaction
     */
    public function setActionBy(Person $actionBy): DepartArrivalTransaction
    {
        $this->actionBy = $actionBy;
        return $this;
    }

    /**
     * @return Client|null
     */
    public function getArrivalOwner(): ?Client
    {
        return $this->arrival !== null && $this->arrival->getLocation() ? $this->arrival->getLocation()->getOwner() : null;
    }

    /**
     * @return Client|null
     */
    public function getDepartOwner(): ?Client
    {
        return $this->depart->getLocation() ? $this->depart->getLocation()->getOwner() : null;
    }
}