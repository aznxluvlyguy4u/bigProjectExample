<?php


namespace AppBundle\Service;


use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalResidence;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\EditTypeEnum;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Util\AdminActionLogWriter;
use AppBundle\Util\DateUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\AnimalResidenceValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

class AnimalResidenceService extends ControllerServiceBase implements AnimalResidenceServiceInterface
{
    /** @var array */
    private $changes;

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
        $this->changes = [];
        foreach ($newResidences as $changeKey => $newResidence) {
            $newResidence = $this->buildValidateCreateSingleResidence($newResidence, $changeKey);
            $newResidence = $this->setCreateMetaData($newResidence);
            $this->getManager()->persist($newResidence);
            $animalIds[] = $newResidence->getAnimalId();
        }

        if (!empty($animalIds)) {
            AdminActionLogWriter::createAnimalResidence($this->getManager(), $this->getUser(), $this->changes);
            $this->getManager()->flush();
        }

        $this->clearPrivateVariables();
        return $this->getDecodedJsonResidencesByAnimalArray($animalIds);
    }


    /**
     * NOTE no validation is done for duplicate or overlapping animal residences
     *
     * @param AnimalResidence $animalResidence
     * @param int $changeKey
     * @return AnimalResidence
     */
    private function buildValidateCreateSingleResidence(AnimalResidence $animalResidence, $changeKey = 0)
    {
        $validationErrors = [];
        $animalId = $animalResidence->getAnimalId();
        if ($animalId) {
            $animal = $this->getManager()->getRepository(Animal::class)->find($animalId);
            if (!$animal) {
                $validationErrors[] = 'No animal found for animalId '.$animalId;
            } else {
                $animalResidence->setAnimal($animal);
                $this->changes[$changeKey][ReportLabel::ULN] = $animal->getUln();
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
                $this->changes[$changeKey][ReportLabel::UBN] = $location->getUbn();
            }

        } else {
            $validationErrors[] = 'Location.locationId is missing';
        }

        if ($animalResidence->getCountry()) {
            if (!AnimalResidenceValidator::isValidCountryCode($this->getConnection(), $animalResidence->getCountry())) {
                $validationErrors[] = 'countryCode is invalid: '.$animalResidence->getCountry();
            } else {
                $this->changes[$changeKey][ReportLabel::COUNTRY] = $animalResidence->getCountry();
            }
        } else {
            $validationErrors[] = 'countryCode is missing';
        }

        if (!$animalResidence->getStartDate()) {
            $validationErrors[] = 'startDate is missing';
        } else {
            $this->changes[$changeKey][ReportLabel::START] = $animalResidence->getStartDate();
        }

        if ($animalResidence->getEndDate()) {
            $this->changes[$changeKey][ReportLabel::END] = $animalResidence->getEndDate();
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

        if (!empty($this->changes)) {
            AdminActionLogWriter::editAnimalResidence($this->getManager(), $this->getUser(), $this->changes);
            $this->getManager()->persist($animalResidence);
            $this->getManager()->flush();
        }

        $this->clearPrivateVariables();

        $returnCompleteResidenceHistory = RequestUtil::getBooleanQuery($request,QueryParameter::FULL_OUTPUT,true);

        if ($returnCompleteResidenceHistory && $animalResidence->getAnimal()) {
            return $this->getAnimalResidenceOutput($animalResidence->getAnimal()->getAnimalResidenceHistory());
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
        $this->changes = [];
        $editType = $this->getEditTypeByEnum(EditTypeEnum::ADMIN_EDIT);

        $newAnimalId = $temporaryResidenceWithEditData->getAnimalId();
        if ($newAnimalId) {
            $animal = $this->getManager()->getRepository(Animal::class)->find($newAnimalId);
            if (!$animal) {
                $validationErrors[] = 'No animal found for animalId '.$newAnimalId;
            } else {

                if ($originalAnimalResidence->getAnimal() === null) {
                    $temporaryResidenceWithEditData->setAnimal($animal);
                    $this->changes[ReportLabel::ULN] = ['Set Animal with ULN', $animal->getUln()];

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
                $this->changes[ReportLabel::UBN] = [$originalAnimalResidence->getUbn(), $temporaryResidenceWithEditData->getUbn()];
            }

        } else {
            $validationErrors[] = 'Location.locationId is missing';
        }

        $newCountry = $temporaryResidenceWithEditData->getCountry();
        if ($newCountry) {
            if (!AnimalResidenceValidator::isValidCountryCode($this->getConnection(), $newCountry)) {
                $validationErrors[] = 'countryCode is invalid: '.$newCountry;
            } elseif ($originalAnimalResidence->getCountry() !== $newCountry) {
                $this->changes[ReportLabel::COUNTRY] = [$originalAnimalResidence->getCountry(), $newCountry];
                $originalAnimalResidence->setCountry($newCountry);
            }
        } else {
            $validationErrors[] = 'countryCode is missing';
        }

        $newStartDate = $temporaryResidenceWithEditData->getStartDate();
        if (!$newStartDate) {
            $validationErrors[] = 'startDate is missing';
        } elseif (!$this->areDatesEqual($originalAnimalResidence->getStartDate(), $newStartDate)) {
            $this->changes[ReportLabel::START] = [$originalAnimalResidence->getStartDate(), $newStartDate];
            $originalAnimalResidence->setStartDate($newStartDate);
            $originalAnimalResidence->setStartDateEditedBy($this->getUser());
            $originalAnimalResidence->setStartDateEditType($editType);
        }

        $newEndDate = $temporaryResidenceWithEditData->getEndDate();
        if (!$this->areDatesEqual($originalAnimalResidence->getEndDate(), $newEndDate)) {
            $this->changes[ReportLabel::END] = [$originalAnimalResidence->getEndDate(), $newEndDate];
            $originalAnimalResidence->setEndDate($newEndDate);
            $originalAnimalResidence->setEndDateEditedBy($this->getUser());
            $originalAnimalResidence->setEndDateEditType($editType);
        }

        $newIsPending = $temporaryResidenceWithEditData->isPending();
        if (!is_bool($newIsPending)) {
            $validationErrors[] = 'isPending must be a boolean';
        } elseif ($originalAnimalResidence->isPending() !== $newIsPending) {
            $this->changes[ReportLabel::IS_PENDING] = [$originalAnimalResidence->isPending(), $newIsPending];
            $originalAnimalResidence->setIsPending($newIsPending);
        }

        if (!empty($validationErrors)) {
            throw new PreconditionFailedHttpException(implode('. ', $validationErrors));
        }

        if (!empty($this->changes)) {
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

        $animal = $animalResidence->getAnimal();
        AdminActionLogWriter::deleteAnimalResidence($this->getManager(), $this->getUser(), $animalResidence);
        $this->getManager()->remove($animalResidence);
        $this->getManager()->flush();

        if ($animal) {
            return $this->getAnimalResidenceOutput($animal->getAnimalResidenceHistory());
        }
        return $this->getAnimalResidenceOutput([]);
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


    private function clearPrivateVariables(): void
    {
        $this->changes = null;
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