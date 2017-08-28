<?php

namespace AppBundle\Service;


use AppBundle\Cache\AnimalCacher;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Controller\MeasurementAPIControllerInterface;
use AppBundle\Entity\Exterior;
use AppBundle\Entity\InspectorAuthorization;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Util\AdminActionLogWriter;
use AppBundle\Util\MeasurementsUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\AnimalDetailsValidator;
use AppBundle\Validation\ExteriorValidator;
use Symfony\Component\HttpFoundation\Request;

class MeasurementService extends ControllerServiceBase implements MeasurementAPIControllerInterface
{
    const ALLOW_BLANK_INSPECTOR = true;


    /**
     * @param Request $request
     * @param string $ulnString
     * @return JsonResponse
     */
    public function createExteriorMeasurement(Request $request, $ulnString)
    {
        $loggedInUser = $this->getUser();
        $isAdmin = AdminValidator::isAdmin($loggedInUser, AccessLevelType::ADMIN);

        $location = null;
        if (!$isAdmin) {
            return AdminValidator::getStandardErrorResponse();
            //$location = $this->getSelectedLocation($request);
        }

        $animalDetailsValidator = new AnimalDetailsValidator($this->getManager(), $isAdmin, $location, $ulnString);
        if (!$animalDetailsValidator->getIsInputValid()) {
            return $animalDetailsValidator->createJsonResponse();
        }
        $animal = $animalDetailsValidator->getAnimal();

        $allowedExteriorCodes = MeasurementsUtil::getExteriorKinds($this->getManager(), $animal);

        $content = RequestUtil::getContentAsArray($request);
        $exteriorValidator = new ExteriorValidator($this->getManager(), $content, $allowedExteriorCodes, $ulnString, self::ALLOW_BLANK_INSPECTOR);
        if (!$exteriorValidator->getIsInputValid()) {
            return $exteriorValidator->createJsonResponse();
        }
        $inspector = $exteriorValidator->getInspector();
        $measurementDate = $exteriorValidator->getMeasurementDate();

        $exterior = $this->getManager()->getRepository(Exterior::class)
            ->findOneBy(['measurementDate' => $measurementDate, 'animal' => $animal, 'isActive' => true]);

        if($exterior != null) {
            return ResultUtil::errorResult('THERE ALREADY EXISTS AN EXTERIOR MEASUREMENT ON THIS DATE', 428);
        }

        $exterior = new Exterior();

        $exterior->setActionBy($loggedInUser);
        $exterior->setEditDate(new \DateTime());
        $exterior->setAnimal($animal);
        $exterior->setMeasurementDate($measurementDate);
        $exterior->setKind($exteriorValidator->getKind());
        $exterior->setSkull($exteriorValidator->getSkull());
        $exterior->setProgress($exteriorValidator->getProgress());
        $exterior->setMuscularity($exteriorValidator->getMuscularity());
        $exterior->setProportion($exteriorValidator->getProportion());
        $exterior->setExteriorType($exteriorValidator->getExteriorType());
        $exterior->setLegWork($exteriorValidator->getLegWork());
        $exterior->setFur($exteriorValidator->getFur());
        $exterior->setGeneralAppearance($exteriorValidator->getGeneralAppearance());
        $exterior->setHeight($exteriorValidator->getHeight());
        $exterior->setBreastDepth($exteriorValidator->getBreastDepth());
        $exterior->setTorsoLength($exteriorValidator->getTorsoLength());
        $exterior->setMarkings($exteriorValidator->getMarkings());
        $exterior->setInspector($inspector);
        $exterior->setAnimalIdAndDateByAnimalAndDateTime($animal, $measurementDate);

        $this->getManager()->persist($exterior);
        $this->getManager()->flush();

        AdminActionLogWriter::createExterior($this->getManager(), $this->getAccountOwner($request),
            $loggedInUser, $exterior);

        //Update exterior values in animalCache AFTER persisting exterior
        AnimalCacher::cacheExteriorByAnimal($this->getManager(), $animal);

        $output = $this->getBaseSerializer()->getDecodedJson($exterior, JmsGroup::USER_MEASUREMENT);

        return ResultUtil::successResult($output);
    }


    /**
     * @param Request $request
     * @param $ulnString
     * @param $measurementDateString
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function editExteriorMeasurement(Request $request, $ulnString, $measurementDateString)
    {
        $loggedInUser = $this->getUser();
        $isAdmin = AdminValidator::isAdmin($loggedInUser, AccessLevelType::ADMIN);

        $location = null;
        if (!$isAdmin) {
            return AdminValidator::getStandardErrorResponse();
            //$location = $this->getSelectedLocation($request);
        }

        $animalDetailsValidator = new AnimalDetailsValidator($this->getManager(), $isAdmin, $location, $ulnString);
        if (!$animalDetailsValidator->getIsInputValid()) {
            return $animalDetailsValidator->createJsonResponse();
        }
        $animal = $animalDetailsValidator->getAnimal();

        $content = RequestUtil::getContentAsArray($request);

        if($content->get('is_active') === false) {

            /* Deactivate the Exterior measurement without updating any other values */

            $validationResults = ExteriorValidator::validateDeactivation($this->getManager(), $animal, $measurementDateString);
            if(!$validationResults->isValid()) {
                return $validationResults->getJsonResponse();
            }

            /** @var Exterior $exterior */
            $exterior = $validationResults->getValidResultObject();
            $exterior->setIsActive(false);
            $exterior->setDeleteDate(new \DateTime());
            $exterior->setDeletedBy($loggedInUser);
            $this->getManager()->persist($exterior);
            $this->getManager()->flush();

            AdminActionLogWriter::deactivateExterior($this->getManager(), $this->getAccountOwner($request), $loggedInUser, $exterior);

            //Update exterior values in animalCache AFTER persisting exterior
            AnimalCacher::cacheExteriorByAnimal($this->getManager(), $animal);

            $output = $this->getBaseSerializer()->getDecodedJson($exterior, JmsGroup::USER_MEASUREMENT);

        } else {

            /* Edit the Exterior measurement */

            $currentMeasurementDate = new \DateTime($measurementDateString);
            /** @var Exterior $exterior */
            $exterior = $this->getManager()->getRepository(Exterior::class)
                ->findOneBy(['measurementDate' => $currentMeasurementDate, 'animal' => $animal, 'isActive' => true]);

            if(!($exterior instanceof Exterior)) {
                return ResultUtil::errorResult('Exterior for given date and uln does not exists!', 428);
            }

            $currentKind = $exterior->getKind();

            $allowedExteriorCodes = MeasurementsUtil::getExteriorKinds($this->getManager(), $animal, $currentKind);

            $exteriorValidator = new ExteriorValidator($this->getManager(), $content, $allowedExteriorCodes, $ulnString, self::ALLOW_BLANK_INSPECTOR, $measurementDateString);
            if (!$exteriorValidator->getIsInputValid()) {
                return $exteriorValidator->createJsonResponse();
            }
            $inspector = $exteriorValidator->getInspector();

            $oldExterior = clone $exterior;

            $exterior->setActionBy($loggedInUser);
            $exterior->setEditDate(new \DateTime());
            $exterior->setAnimal($animal);
            $exterior->setMeasurementDate($exteriorValidator->getNewMeasurementDate());
            $exterior->setKind($exteriorValidator->getKind());
            $exterior->setSkull($exteriorValidator->getSkull());
            $exterior->setProgress($exteriorValidator->getProgress());
            $exterior->setMuscularity($exteriorValidator->getMuscularity());
            $exterior->setProportion($exteriorValidator->getProportion());
            $exterior->setExteriorType($exteriorValidator->getExteriorType());
            $exterior->setLegWork($exteriorValidator->getLegWork());
            $exterior->setFur($exteriorValidator->getFur());
            $exterior->setGeneralAppearance($exteriorValidator->getGeneralAppearance());
            $exterior->setHeight($exteriorValidator->getHeight());
            $exterior->setBreastDepth($exteriorValidator->getBreastDepth());
            $exterior->setTorsoLength($exteriorValidator->getTorsoLength());
            $exterior->setMarkings($exteriorValidator->getMarkings());
            $exterior->setInspector($inspector);
            $exterior->setAnimalIdAndDateByAnimalAndDateTime($animal, $currentMeasurementDate);

            $this->getManager()->persist($exterior);
            $this->getManager()->flush();

            AdminActionLogWriter::updateExterior($this->getManager(), $this->getAccountOwner($request),
                $loggedInUser, $exterior, $oldExterior);

            //Update exterior values in animalCache AFTER persisting exterior
            AnimalCacher::cacheExteriorByAnimal($this->getManager(), $animal);

            $output = $this->getBaseSerializer()->getDecodedJson($exterior, JmsGroup::USER_MEASUREMENT);

            $oldExterior = null;

        }

        return ResultUtil::successResult($output);
    }


    /**
     * @param Request $request
     * @param $ulnString
     * @return JsonResponse
     */
    public function getAllowedExteriorKinds(Request $request, $ulnString)
    {
        $loggedInUser = $this->getUser();
        $isAdmin = AdminValidator::isAdmin($loggedInUser, AccessLevelType::ADMIN);
        $location = $isAdmin ? null : $this->getSelectedLocation($request);

        $animalDetailsValidator = new AnimalDetailsValidator($this->getManager(), $isAdmin, $location, $ulnString);
        if (!$animalDetailsValidator->getIsInputValid()) {
            return $animalDetailsValidator->createJsonResponse();
        }
        //Uln has already been validated above
        $animal = $animalDetailsValidator->getAnimal();

        $output = MeasurementsUtil::getExteriorKindsOutput($this->getManager(), $animal);
        return ResultUtil::successResult($output);
    }


    /**
     * @param Request $request
     * @param $ulnString
     * @param $measurementDateString
     * @return JsonResponse
     */
    public function getAllowedExteriorKindsForEdit(Request $request, $ulnString, $measurementDateString)
    {
        $loggedInUser = $this->getUser();
        $isAdmin = AdminValidator::isAdmin($loggedInUser, AccessLevelType::ADMIN);
        $location = $isAdmin ? null : $this->getSelectedLocation($request);

        $animalDetailsValidator = new AnimalDetailsValidator($this->getManager(), $isAdmin, $location, $ulnString);
        if (!$animalDetailsValidator->getIsInputValid()) {
            return $animalDetailsValidator->createJsonResponse();
        }
        //Uln has already been validated above
        $animal = $animalDetailsValidator->getAnimal();

        $measurementDate = new \DateTime($measurementDateString);

        /** @var Exterior $exterior */
        $exterior = $this->getManager()->getRepository(Exterior::class)
            ->findOneBy(['measurementDate' => $measurementDate, 'animal' => $animal, 'isActive' => true]);

        if($exterior == null) {
            return ResultUtil::errorResult('Exterior for given date and uln does not exists!', 428);
        }

        $currentKind = $exterior->getKind();
        $output = MeasurementsUtil::getExteriorKindsOutput($this->getManager(), $animal, $currentKind);

        return ResultUtil::successResult($output);
    }


    /**
     * @param Request $request
     * @param $ulnString
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getAllowedInspectorsForExteriorMeasurements(Request $request, $ulnString)
    {
        if (!AdminValidator::isAdmin($this->getEmployee(), AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        $output = $this->getManager()->getRepository(InspectorAuthorization::class)->getAuthorizedInspectorsExteriorByUln($ulnString);
        return ResultUtil::successResult($output);
    }

}