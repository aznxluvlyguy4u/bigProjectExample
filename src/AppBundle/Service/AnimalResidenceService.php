<?php


namespace AppBundle\Service;


use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalResidence;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\EditTypeEnum;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Util\DateUtil;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\AnimalResidenceValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

class AnimalResidenceService extends ControllerServiceBase implements AnimalResidenceServiceInterface
{
    /** @var boolean */
    private $hasChanged;

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
        return $this->getAnimalResidenceOutput($animal->getAnimalResidenceHistory());
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
            $newResidence = $this->setCreateMetaData($newResidence);
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

        if (!$animalResidence->getStartDate()) {
            $validationErrors[] = 'startDate is missing';
        }

        if (!empty($validationErrors)) {
            throw new PreconditionFailedHttpException(implode('. ', $validationErrors));
        }


        $animalResidence->setLogDate(new \DateTime());
        $animalResidence->setIsPending(false);

        return $animalResidence;
    }


    /**
     * @param AnimalResidence $animalResidence
     * @return AnimalResidence
     */
    private function setCreateMetaData(AnimalResidence $animalResidence): AnimalResidence
    {
        $editType = $this->getEditTypeByEnum(EditTypeEnum::ADMIN_CREATE);

        $animalResidence->setStartDateEditedBy($this->getUser());
        $animalResidence->setStartDateEditType($editType);
        $animalResidence->setEndDateEditedBy($this->getUser());
        $animalResidence->setEndDateEditType($editType);

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
        return $this->getAnimalResidenceOutput($animalResidences);
    }


    function editResidence(Request $request, AnimalResidence $animalResidence)
    {
        if(!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN)) {
            throw AdminValidator::standardException();
        }

        $temporaryResidenceWithEditData = $this->getBaseSerializer()->getObjectsFromRequestContent($request->getContent(),AnimalResidence::class, false);

        $animalResidence = $this->buildValidateEditSingleResidenceInclMetaData($animalResidence, $temporaryResidenceWithEditData);

        if ($this->hasChanged) {
            $this->getManager()->persist($animalResidence);
            $this->getManager()->flush();
        }

        return $this->getAnimalResidenceOutput($animalResidence);
    }


    /**
     * NOTE no validation is done for duplicate or overlapping animal residences
     *
     * @param AnimalResidence $originalAnimalResidence
     * @param AnimalResidence $temporaryResidenceWithEditData
     * @return AnimalResidence
     */
    private function buildValidateEditSingleResidenceInclMetaData(
        AnimalResidence $originalAnimalResidence,
        AnimalResidence $temporaryResidenceWithEditData): AnimalResidence
    {
        $validationErrors = [];
        $this->hasChanged = false;
        $editType = $this->getEditTypeByEnum(EditTypeEnum::ADMIN_EDIT);
        $newAnimalId = $temporaryResidenceWithEditData->getAnimalId();
        if ($newAnimalId) {
            $animal = $this->getManager()->getRepository(Animal::class)->find($newAnimalId);
            if (!$animal) {
                $validationErrors[] = 'No animal found for animalId '.$newAnimalId;
            } else {

                if ($originalAnimalResidence->getAnimal() === null) {
                    $temporaryResidenceWithEditData->setAnimal($animal);
                    $this->hasChanged = true;

                } elseif ($originalAnimalResidence->getAnimalId() !== $temporaryResidenceWithEditData->getAnimalId()) {
                    $validationErrors[] = 'Animal is not allowed to be changed, animalId '.$newAnimalId;
                }
            }

        } else {
            $validationErrors[] = 'AnimalId is missing';
        }

        $newLocationId = $temporaryResidenceWithEditData->getLocationApiKeyId();
        if ($newLocationId) {
            $newLocation = $this->getManager()->getRepository(Location::class)->findOneBy(
                ['locationId' => $newLocationId]
            );
            if (!$newLocation) {
                $validationErrors[] = 'No location found for animalId '.$newLocationId;
            } else if ($newLocationId !== $originalAnimalResidence->getLocationApiKeyId()) {
                $originalAnimalResidence->setLocation($newLocation);
                $this->hasChanged = true;
            }

        } else {
            $validationErrors[] = 'Location.locationId is missing';
        }

        $newCountry = $temporaryResidenceWithEditData->getCountry();
        if ($newCountry) {
            if (!AnimalResidenceValidator::isValidCountryCode($this->getConnection(), $newCountry)) {
                $validationErrors[] = 'countryCode is invalid: '.$newCountry;
            } elseif ($originalAnimalResidence->getCountry() !== $newCountry) {
                $originalAnimalResidence->setCountry($newCountry);
                $this->hasChanged = true;
            }
        } else {
            $validationErrors[] = 'countryCode is missing';
        }

        $newStartDate = $temporaryResidenceWithEditData->getStartDate();
        if (!$newStartDate) {
            $validationErrors[] = 'startDate is missing';
        } elseif (!$this->areDatesEqual($originalAnimalResidence->getStartDate(), $newStartDate)) {
            $originalAnimalResidence->setStartDate($newStartDate);
            $originalAnimalResidence->setStartDateEditedBy($this->getUser());
            $originalAnimalResidence->setStartDateEditType($editType);
            $this->hasChanged = true;
        }

        $newEndDate = $temporaryResidenceWithEditData->getEndDate();
        if (!$this->areDatesEqual($originalAnimalResidence->getEndDate(), $newEndDate)) {
            $originalAnimalResidence->setEndDate($newEndDate);
            $originalAnimalResidence->setEndDateEditedBy($this->getUser());
            $originalAnimalResidence->setEndDateEditType($editType);
            $this->hasChanged = true;
        }

        $newIsPending = $temporaryResidenceWithEditData->isPending();
        if (!is_bool($newIsPending)) {
            $validationErrors[] = 'isPending must be a boolean';
        } elseif ($originalAnimalResidence->isPending() !== $newIsPending) {
            $originalAnimalResidence->setIsPending($newIsPending);
            $this->hasChanged = true;
        }

        if (!empty($validationErrors)) {
            throw new PreconditionFailedHttpException(implode('. ', $validationErrors));
        }

        if ($this->hasChanged) {
            $originalAnimalResidence->setLogDate(new \DateTime());
        }

        return $originalAnimalResidence;
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


    /**
     * @param AnimalResidence|AnimalResidence[] $animalResidenceOrResidences
     * @return array|mixed
     */
    private function getAnimalResidenceOutput($animalResidenceOrResidences)
    {
        return $this->getBaseSerializer()->getDecodedJson(
            $animalResidenceOrResidences,
            [JmsGroup::EDIT_OVERVIEW, JmsGroup::BASIC_SUB_ANIMAL_DETAILS],
            true
        );
    }


    /**
     * For the AnimalResidenceService functions just ignore timezone and timezoneType
     *
     * @param \DateTime|null $date1
     * @param \DateTime|null $date2
     * @return bool
     */
    private function areDatesEqual($date1, $date2): bool
    {
        return DateUtil::hasSameDateIgnoringTimezoneAndTimeZoneType($date1, $date2);
    }
}