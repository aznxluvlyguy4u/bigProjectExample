<?php


namespace AppBundle\Validation;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\SqlView\Repository\ViewAnimalHistoricLocationsRepository;
use AppBundle\SqlView\Repository\ViewAnimalIsPublicDetailsRepository;
use AppBundle\SqlView\SqlViewManagerInterface;
use AppBundle\SqlView\View\ViewAnimalHistoricLocations;
use AppBundle\SqlView\View\ViewAnimalIsPublicDetails;
use AppBundle\Util\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Response;

class AnimalDetailsValidator extends BaseValidator
{
    const ERROR_UNAUTHORIZED_FOR_ANIMAL = "Animal does not belong to this ubn, or is not a Historic Animal that was made public";
    const ERROR_NON_EXISTENT_ANIMAL = "ANIMAL WAS NOT FOUND";
    const ERROR_NON_PUBLIC_ANIMAL = "The current owner has not made this animal public";

    /** @var ObjectManager */
    private $em;

    /** @var Animal */
    private $animal;

    /** @var SqlViewManagerInterface */
    private $sqlViewManager;

    /**
     * AnimalDetailsValidator constructor.
     * @param ObjectManager $em
     * @param SqlViewManagerInterface $sqlViewManager
     * @param boolean $isAdmin
     * @param Location $location
     * @param string $idOrUlnString
     */
    public function __construct(ObjectManager $em, SqlViewManagerInterface $sqlViewManager,
                                $isAdmin, $location, $idOrUlnString)
    {
        parent::__construct($em, new ArrayCollection());
        $this->em = $em;
        $this->sqlViewManager = $sqlViewManager;

        $this->isInputValid = false;

        $isPrimaryKey = $this->isIdentifierPrimaryKey($idOrUlnString);

        if(Validator::verifyUlnFormat($idOrUlnString) || $isPrimaryKey) {

            /** @var AnimalRepository $repository */
            $repository = $this->em->getRepository(Animal::class);

            if ($isPrimaryKey) {
                // Find animal by animal Id. Query is 100x faster than search by ULN
                $this->animal = $repository->find(intval($idOrUlnString));
            } else {
                $this->animal = $repository->findAnimalByUlnString($idOrUlnString);
            }

            if($this->animal) {
                if($isAdmin) {
                    $this->isInputValid = true;
                } else {
                    $company = $location->getCompany();
                    if ($company && !empty($company->getUbns())) {
                        $this->isInputValid = Validator::isUserAllowedToAccessAnimalDetails(
                            $this->getViewAnimalHistoricLocations(),
                            $company,
                            $this->isPublicAnimal(),
                            $company->getUbns()
                        );
                    }

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


    private function isIdentifierPrimaryKey($identifier): bool {
        return ctype_digit($identifier) || is_int($identifier);
    }


    public function getViewAnimalHistoricLocations(): ViewAnimalHistoricLocations
    {
        /** @var ViewAnimalHistoricLocationsRepository $viewAnimalHistoricLocations */
        $viewAnimalHistoricLocations = $this->sqlViewManager->get(ViewAnimalHistoricLocations::class);
        return $viewAnimalHistoricLocations->findOneByAnimalId($this->animal->getId());
    }


    public function isPublicAnimal(): bool
    {
        /** @var ViewAnimalIsPublicDetailsRepository $viewAnimalIsPublicDetailsRepository */
        $viewAnimalIsPublicDetailsRepository = $this->sqlViewManager->get(ViewAnimalIsPublicDetails::class);
        return $viewAnimalIsPublicDetailsRepository->findOneByAnimalId($this->animal->getId())->isPublic();
    }


    /**
     * @return Animal|Ewe|Neuter|Ram|null
     */
    public function getAnimal()
    {
        return $this->animal;
    }


    /**
     * @return JsonResponse
     */
    public function createJsonResponse()
    {
        if($this->isInputValid){
            return Validator::createJsonResponse(self::VALID_MESSAGE, Response::HTTP_OK);
        } else {
            if (!$this->animal) {
                return Validator::createJsonResponse('Geen dier gevonden', Response::HTTP_NOT_FOUND);
            }

            return Validator::createJsonResponse(self::ERROR_MESSAGE, Response::HTTP_PRECONDITION_REQUIRED, $this->errors);
        }
    }
}
