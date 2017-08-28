<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Pedigree
 * @ORM\Entity(repositoryClass="AppBundle\Entity\PedigreeRepository")
 * @package AppBundle\Entity
 */
class Pedigree
{
    use EntityClassInfo;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $pedigreeCode;

    /**
     * @var Company
     *
     * @ORM\ManyToOne(targetEntity="Company", inversedBy="pedigrees", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Company")
     */
    private $company;

    /**
     * Pedigree constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return string
     */
    public function getPedigreeCode()
    {
        return $this->pedigreeCode;
    }

    /**
     * @param string $pedigreeCode
     */
    public function setPedigreeCode($pedigreeCode)
    {
        $this->pedigreeCode = $pedigreeCode;
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

}