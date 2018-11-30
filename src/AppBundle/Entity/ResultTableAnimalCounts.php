<?php


namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * Class ResultTableAnimalCounts
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ResultTableAnimalCountsRepository")
 * @package AppBundle\Entity
 */
class ResultTableAnimalCounts
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
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $logDate;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $batchStartDate;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $latestRvoLeadingSyncDateBeforeBatchStart;

    /**
     * @var Location
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Location", inversedBy="resultTableAnimalCounts")
     * @ORM\JoinColumn(name="location_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @JMS\Type("AppBundle\Entity\Location")
     * @JMS\Exclude
     */
    private $location;

    /**
     * @var Company
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Company", inversedBy="resultTableAnimalCounts")
     * @ORM\JoinColumn(name="company_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @JMS\Type("AppBundle\Entity\Company")
     * @JMS\Exclude
     */
    private $company;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $animalsOneYearOrOlder;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $animalsYoungerThanOneYear;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $allAnimals;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $ewesOneYearOrOlder;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $ewesYoungerThanOneYear;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $ramsOneYearOrOlder;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $ramsYoungerThanOneYear;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $neutersOneYearOrOlder;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $neutersYoungerThanOneYear;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $pedigreeAnimalsOneYearOrOlder;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $pedigreeAnimalsYoungerThanOneYear;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $nonPedigreeAnimalsOneYearOrOlder;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $nonPedigreeAnimalsYoungerThanOneYear;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $pedigreeEwesSixMonthsOrOlder;


}