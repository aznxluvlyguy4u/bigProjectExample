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

class AnimalDetailsValidator extends BaseValidator
{
    const ERROR_UNAUTHORIZED_FOR_ANIMAL = "Animal does not belong to this ubn, or is not a Historic Animal that was made public";
    const ERROR_NON_EXISTENT_ANIMAL = "Animal does not exist in our world :(";
    const ERROR_NON_PUBLIC_ANIMAL = "The current owner has not made this animal public";

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
                    $this->isInputValid = Validator::isAnimalPublicForLocation($this->em, $this->animal, $location);

                    if($this->isInputValid == false) {
                        $this->errors[] = self::ERROR_UNAUTHORIZED_FOR_ANIMAL;

                        if($this->animal == null) {
                            $this->errors[] = self::ERROR_NON_EXISTENT_ANIMAL;
                        } elseif(!$this->animal->isAnimalPublic()){
                            $this->errors[] = self::ERROR_NON_PUBLIC_ANIMAL;
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