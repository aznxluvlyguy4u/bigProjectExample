<?php


namespace AppBundle\Validation;


use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Util\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;

class AnimalDetailsValidator extends BaseValidator
{

    /** @var ObjectManager */
    private $em;

    /** @var Animal */
    private $animal;

    /**
     * AnimalDetailsValidator constructor.
     * @param ObjectManager $em
     * @param boolean $isAdmin
     * @param Location $location
     * @param string $ulnString
     */
    public function __construct(ObjectManager $em, $isAdmin, $location, $ulnString)
    {
        parent::__construct($em, new ArrayCollection());
        $this->em = $em;

        $this->isInputValid = false;

        if(Validator::verifyUlnFormat($ulnString)) {
            
            /** @var AnimalRepository $repository */
            $repository = $this->em->getRepository(Animal::class);
            $this->animal = $repository->findAnimalByUlnString($ulnString);

            if($this->animal) {
                if($isAdmin) {
                    $this->isInputValid = true;
                } else {
                    $this->isInputValid = $this->animal->isAnimalPublicForLocation($em, $location);

                    if($this->isInputValid == false) {
                        $this->errors[] = "Animal does not belong to this ubn, or is not a Historic Animal that was made public";

                        if($this->animal == null) {
                            $this->errors[] = "Animal does not exist in our world :(";
                        } elseif(!$this->animal->isAnimalPublic()){
                            $this->errors[] = "The current owner has not made this animal public";
                        }
                    }
                }

            }
        }


    }

    /**
     * @return Animal|Ewe|Neuter|Ram|null
     */
    public function getAnimal()
    {
        return $this->animal;
    }
}