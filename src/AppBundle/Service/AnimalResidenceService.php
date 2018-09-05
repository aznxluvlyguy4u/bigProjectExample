<?php


namespace AppBundle\Service;


use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalResidence;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\AnimalResidenceValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

class AnimalResidenceService extends ControllerServiceBase implements AnimalResidenceServiceInterface
{
    /**
     * @param Request $request
     * @param Animal $animal
     * @return array
     */
    function getResidencesByAnimal(Request $request, Animal $animal)
    {
        if(!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN)) {
            throw AdminValidator::standardException();
        }

        return $this->getBaseSerializer()->getDecodedJson(
            $animal->getAnimalResidenceHistory(),
            [JmsGroup::EDIT_OVERVIEW, JmsGroup::BASIC_SUB_ANIMAL_DETAILS],
            true
        );
    }


    /**
     * @param Request $request
     * @return array
     */
    function createResidence(Request $request)
    {
        if(!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN)) {
            throw AdminValidator::standardException();
        }

        $newResidences = $this->getBaseSerializer()->getObjectsFromRequestContent($request->getContent(),AnimalResidence::class, true);

        if (empty($newResidences)) {
            return [];
        }

        $animalIds = [];
        foreach ($newResidences as $newResidence) {
            $newResidence = $this->buildValidateCreateSingleResidence($newResidence);
            $this->getManager()->persist($newResidence);
            $animalIds[] = $newResidence->getAnimalId();
        }

        if (!empty($animalIds)) {
            $this->getManager()->flush();
        }

        return $this->getDecodedJsonResidencesByAnimalArray($animalIds);
    }


    /**
     * NOTE no validation is done for duplicate or overlapping animal residences
     *
     * @param AnimalResidence $animalResidence
     * @return AnimalResidence
     */
    private function buildValidateCreateSingleResidence(AnimalResidence $animalResidence)
    {
        $validationErrors = [];
        $animalId = $animalResidence->getAnimalId();
        if ($animalId) {
            $animal = $this->getManager()->getRepository(Animal::class)->find($animalId);
            if (!$animal) {
                $validationErrors[] = 'No animal found for animalId '.$animalId;
            } else {
                $animalResidence->setAnimal($animal);
            }

        } else {
            $validationErrors[] = 'AnimalId is missing';
        }

        $locationId = $animalResidence->getLocationApiKeyId();
        if ($locationId) {
            $location = $this->getManager()->getRepository(Location::class)->findOneBy(
                ['locationId' => $locationId]
            );
            if (!$location) {
                $validationErrors[] = 'No location found for animalId '.$locationId;
            } else {
                $animalResidence->setLocation($location);
            }

        } else {
            $validationErrors[] = 'Location.locationId is missing';
        }

        if ($animalResidence->getCountry()) {
            if (!AnimalResidenceValidator::isValidCountryCode($this->getConnection(), $animalResidence->getCountry())) {
                $validationErrors[] = 'countryCode is invalid: '.$animalResidence->getCountry();
            }
        } else {
            $validationErrors[] = 'countryCode is missing';
        }

        if (!empty($validationErrors)) {
            throw new PreconditionFailedHttpException(implode('. ', $validationErrors));
        }


        $animalResidence->setLogDate(new \DateTime());
        $animalResidence->setIsPending(false);

        return $animalResidence;
    }


    /**
     * @param int[] $animalIds
     * @return array
     */
    private function getDecodedJsonResidencesByAnimalArray($animalIds)
    {
        if (empty($animalIds)) {
            return [];
        }

        $animalResidences = $this->getManager()->getRepository(AnimalResidence::class)->getByAnimalIds($animalIds);

        return $this->getBaseSerializer()->getDecodedJson(
            $animalResidences,
            [JmsGroup::EDIT_OVERVIEW, JmsGroup::BASIC_SUB_ANIMAL_DETAILS],
            true
        );
    }


    function editResidence(Request $request, AnimalResidence $animalResidence)
    {
        if(!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN)) {
            throw AdminValidator::standardException();
        }

        // TODO not implemented
        throw new NotFoundHttpException();
    }

    /**
     * @param Request $request
     * @param AnimalResidence $animalResidence
     * @return bool
     */
    function deleteResidence(Request $request, AnimalResidence $animalResidence)
    {
        if(!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN)) {
            throw AdminValidator::standardException();
        }

        $this->getManager()->remove($animalResidence);
        $this->getManager()->flush();
        return true;
    }

}