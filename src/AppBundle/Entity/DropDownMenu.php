<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class DropDown
 * @package AppBundle\Entity
 * @ORM\Table(name="drop_down_menu")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DropDownMenuRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"DropDownMenu" = "DropDownMenu",
 *                        "ContactFormMenu" = "ContactFormMenu",
 *                        "ReasonOfDepartMenu" = "ReasonOfDepartMenu",
 *                        "ReasonOfLossMenu" = "ReasonOfLossMenu",
 *                        "TreatmentMenu" = "TreatmentMenu"})
 */
abstract class DropDownMenu
{
    use EntityClassInfo;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="DropDownOption", mappedBy="menu", cascade={"persist"})
     * @ORM\OrderBy({"rank" = "ASC"})
     * @JMS\Type("AppBundle\Entity\DropDownOption")
     */
    protected $options;


    public function __construct()
    {
        $this->options = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ArrayCollection
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Add dropDownOption
     *
     * @param DropDownOption $dropDownOption
     *
     * @return Animal
     */
    public function addOption(DropDownOption $dropDownOption)
    {
        $this->options[] = $dropDownOption;

        return $this;
    }

    /**
     * Remove dropDownOption
     *
     * @param DropDownOption $dropDownOption
     */
    public function removeOption(DropDownOption $dropDownOption)
    {
        $this->options->removeElement($dropDownOption);
    }

    /**
     * @param ArrayCollection $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }



}