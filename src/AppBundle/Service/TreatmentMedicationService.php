<?php

namespace AppBundle\Service;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Controller\TreatmentMedicationAPIControllerInterface;
use AppBundle\Entity\TreatmentMedication;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Util\AdminActionLogWriter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\Validator;
use AppBundle\Validation\AdminValidator;
use Exception;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TreatmentMedicationService
 * @package AppBundle\Service
 */
class TreatmentMedicationService extends TreatmentServiceBase implements TreatmentMedicationAPIControllerInterface
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    function getByQuery(Request $request)
    {
        $activeOnly = RequestUtil::getBooleanQuery($request,QueryParameter::ACTIVE_ONLY, true);

        $treatmentMedications = $this->treatmentMedicationRepository->findByQueries($activeOnly);
        $output = $this->getBaseSerializer()->getDecodedJson($treatmentMedications, [JmsGroup::TREATMENT_TEMPLATE]);

        return ResultUtil::successResult($output);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    function create(Request $request)
    {
        $admin = $this->getEmployee();
        if($admin === null) { return AdminValidator::getStandardErrorResponse(); }

        //Deserialization and Validation
        /** @var TreatmentMedication $treatmentMedicationFromContent */
        $treatmentMedicationFromContent = $this->baseValidateDeserializedTreatmentMedication($request);
        if ($treatmentMedicationFromContent instanceof JsonResponse) { return $treatmentMedicationFromContent; }

        /** @var TreatmentMedication $treatmentMedicationInDb */
        $treatmentMedicationInDb = $this->treatmentMedicationRepository
            ->findOneBy(['name' => $treatmentMedicationFromContent->getName()]);

        $treatmentMedication = $treatmentMedicationFromContent;
        if ($treatmentMedicationInDb) {
            if ($treatmentMedicationInDb->isActive()) {
                return Validator::createJsonResponse('Behandelings medicatie bestaat al', 428);
            } else {
                //Reactivate
                $treatmentMedicationInDb->setIsActive(true);
                $treatmentMedication = $treatmentMedicationInDb;
            }
        }

        $this->getManager()->persist($treatmentMedication);
        $this->getManager()->flush();

//        AdminActionLogWriter::createTreatmentType($this->getManager(), $admin, $request, $treatmentMedication);

        $output = $this->getBaseSerializer()->getDecodedJson($treatmentMedication, [JmsGroup::TREATMENT_TEMPLATE]);
        return ResultUtil::successResult($output);
    }

    /**
     * @param Request $request
     * @param int $treatmentMedicationId
     * @return JsonResponse
     */
    function edit(Request $request, $treatmentMedicationId)
    {
        $admin = $this->getEmployee();
        if($admin === null) { return AdminValidator::getStandardErrorResponse(); }

        /** @var TreatmentMedication $treatmentMedicationInDb */
        $treatmentMedicationInDb = $this->treatmentMedicationRepository->find($treatmentMedicationId);
        if ($treatmentMedicationInDb === null) { return Validator::createJsonResponse('TreatmentMedication not found for given id', 428); }
        if ($treatmentMedicationInDb->isActive() === false) { return Validator::createJsonResponse('Medication has been deactivated', 428); }

        //Deserialization and Validation
        $treatmentMedicationFromContent = $this->baseValidateDeserializedTreatmentMedication($request);
        if ($treatmentMedicationFromContent instanceof JsonResponse) { return $treatmentMedicationFromContent; }

        $treatmentMedicationInDbByValues = $this->treatmentMedicationRepository->findOneBy(['name' => $treatmentMedicationFromContent->getName()]);

        $isAnyValueUpdated = false;
        $this->actionLogName = '';

        $newName = $treatmentMedicationFromContent->getName();
        $oldName = $treatmentMedicationInDb->getName();

        if ($oldName !== $newName) {
            $this->appendUpdateName($oldName, $newName);
            $isAnyValueUpdated = true;
        }

        $treatmentMedicationOutput = $treatmentMedicationInDb;
        if ($isAnyValueUpdated) {

            $isSimpleUpdate = false;
            if ($treatmentMedicationInDbByValues !== null) {

                if ($treatmentMedicationInDbByValues->getId() === $treatmentMedicationInDb->getId()) {
                    $isSimpleUpdate = true;
                } else {
                    if ($treatmentMedicationInDbByValues->isActive()) {
                        return Validator::createJsonResponse('Er bestaat al een behandelings medicijn met dezelfde type', 428);
                    } else {
                        $treatmentMedicationInDbByValues->setIsActive(true);
                        $this->getManager()->persist($treatmentMedicationInDbByValues);
                        $treatmentMedicationOutput = $treatmentMedicationInDbByValues;

                        $treatmentMedicationInDb->setIsActive(false);
                        $this->getManager()->persist($treatmentMedicationInDb);

                        $this->getManager()->flush();
                    }
                }
            } else {
                $isSimpleUpdate = true;
            }

            if ($isSimpleUpdate) {
                $treatmentMedicationInDb->setName($newName);
                $this->getManager()->persist($treatmentMedicationInDb);
                $this->getManager()->flush();
            }

//            AdminActionLogWriter::editTreatmentMedication($this->getManager(), $admin, $this->actionLogDescription);
        }

        $output = $this->getBaseSerializer()->getDecodedJson($treatmentMedicationOutput, [JmsGroup::TREATMENT_TEMPLATE]);
        return ResultUtil::successResult($output);
    }

    /**
     * @param Request $request
     * @param int $treatmentMedicationId
     * @return JsonResponse
     * @throws Exception
     */
    function delete(Request $request, $treatmentMedicationId)
    {
        $admin = $this->getEmployee();
        if($admin === null) { return AdminValidator::getStandardErrorResponse(); }

        /** @var TreatmentMedication $treatmentMedication */
        $treatmentMedication = $this->treatmentMedicationRepository->find($treatmentMedicationId);
        if ($treatmentMedication === null) { return Validator::createJsonResponse('TreatmentMedication not found for given id', 428); }
        if ($treatmentMedication->isActive() === false) { return Validator::createJsonResponse('Template has already been deactivated', 428); }

        $treatmentMedication->setIsActive(false);
        $this->getManager()->persist($treatmentMedication);
        $this->getManager()->flush();

//        AdminActionLogWriter::deleteTreatmentMedication($this->getManager(), $admin, $treatmentMedication);

        $output = $this->getBaseSerializer()->getDecodedJson($treatmentMedication, [JmsGroup::TREATMENT_TEMPLATE]);
        return ResultUtil::successResult($output);
    }

    /**
     * @param Request $request
     * @return JsonResponse|TreatmentMedication
     */
    private function baseValidateDeserializedTreatmentMedication($request)
    {
        /** @var TreatmentMedication $treatmentMedication */
        $treatmentMedication = $this->getBaseSerializer()->deserializeToObject($request->getContent(), TreatmentMedication::class);
        if (!($treatmentMedication instanceof TreatmentMedication)) {
            return Validator::createJsonResponse('Json body must have the TreatmentMedication structure', 428);
        }

        if ($treatmentMedication->getName() === null) {
            return Validator::createJsonResponse('Name is missing', 428);
        }

        return $treatmentMedication;
    }

    /**
     * @param string|int|float $oldValue
     * @param string|int|float $newValue
     * @return string
     */
    protected function appendUpdateName($oldValue, $newValue)
    {
        $oldValue = $oldValue === null ? '' : $oldValue;
        $newValue = $newValue === null ? '' : $newValue;

        return $this->appendName($oldValue . ' => ' . $newValue);
    }


    /**
     * @param string $string
     * @return string
     */
    protected function appendName($string)
    {
        if ($this->actionLogName === null) {
            $this->actionLogName = '';
        }

        $prefix = '';
        if ($this->actionLogName !== '') {
            $prefix = ', ';
        }

        $this->actionLogName = $this->actionLogName . $prefix . $string;

        return $this->actionLogName;
    }
}