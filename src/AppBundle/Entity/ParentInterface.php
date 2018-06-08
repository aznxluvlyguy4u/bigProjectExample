<?php


namespace AppBundle\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

interface ParentInterface
{
    /**
     * Add child
     *
     * @param Animal $child
     *
     * @return Ram
     */
    public function addChild(Animal $child);

    /**
     * Remove child
     *
     * @param Animal $child
     */
    public function removeChild(Animal $child);

    /**
     * Get children
     *
     * @return Collection|Animal[]
     */
    public function getChildren();


    /**
     * @return ArrayCollection
     */
    public function getMatings();

    /**
     * @param ArrayCollection $matings
     */
    public function setMatings($matings);


    /**
     * Add litter
     *
     * @param Litter $litter
     *
     * @return Ewe
     */
    public function addLitter(Litter $litter);

    /**
     * Remove litter
     *
     * @param Litter $litter
     */
    public function removeLitter(Litter $litter);

    /**
     * Get litters
     *
     * @return Collection
     */
    public function getLitters();
}