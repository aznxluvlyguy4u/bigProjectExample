<?php

namespace AppBundle\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareImport;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class LocationHealthQueue
 * @ORM\Entity(repositoryClass="AppBundle\Entity\LocationHealthQueueRepository")
 * @package AppBundle\Entity
 */
class LocationHealthQueue
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="DeclareArrival", mappedBy="locationHealthQueue")
     */
    private $arrivals;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="DeclareImport", mappedBy="locationHealthQueue")
     */
    private $imports;

    /**
     * MessagesToUpdateForLocationHealth constructor.
     */
    public function __construct()
    {
        $this->arrivals = new ArrayCollection();
        $this->imports = new ArrayCollection();
    }

    /**
     * Add arrival
     *
     * @param DeclareArrival $arrival
     *
     * @return DeclareArrival
     */
    public function addArrival(DeclareArrival $arrival)
    {
        $this->arrivals->add($arrival);

        return $this;
    }

    /**
     * Remove arrival
     *
     * @param DeclareArrival $arrival
     */
    public function removeArrival(DeclareArrival $arrival)
    {
        $this->arrivals->removeElement($arrival);
    }

    /**
     * @return ArrayCollection
     */
    public function getArrivals()
    {
        return $this->arrivals;
    }

    /**
     * @param ArrayCollection $arrivals
     */
    public function setArrivals($arrivals)
    {
        $this->arrivals = $arrivals;
    }

    /**
     * Add import
     *
     * @param DeclareImport $import
     *
     * @return DeclareImport
     */
    public function addImport(DeclareImport $import)
    {
        $this->imports->add($import);

        return $this;
    }

    /**
     * Remove import
     *
     * @param DeclareImport $import
     */
    public function removeImport(DeclareImport $import)
    {
        $this->imports->removeElement($import);
    }
    
    /**
     * @return ArrayCollection
     */
    public function getImports()
    {
        return $this->imports;
    }

    /**
     * @param ArrayCollection $imports
     */
    public function setImports($imports)
    {
        $this->imports = $imports;
    }

    /**
     * @param DeclareArrival|DeclareImport $arrivalOrImport
     */
    public function addDeclaration($arrivalOrImport)
    {
        if($arrivalOrImport instanceof DeclareArrival){
            $this->addArrival($arrivalOrImport);
        } else if($arrivalOrImport instanceof DeclareImport) {
            $this->addImport($arrivalOrImport);
        }
    }

    /**
     * @param DeclareArrival|DeclareImport $arrivalOrImport
     */
    public function removeDeclaration($arrivalOrImport)
    {
        if($arrivalOrImport instanceof DeclareArrival){
            $this->removeArrival($arrivalOrImport);
        } else if($arrivalOrImport instanceof DeclareImport) {
            $this->removeImport($arrivalOrImport);
        }
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
