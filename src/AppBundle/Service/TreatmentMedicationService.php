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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
        $treatmentMedication = $this->baseValidateDeserializedTreatmentMedication($request);

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
        /** @var TreatmentMedication $treatmentMedicationFromContent */
        $treatmentMedicationFromContent = $this->baseValidateDeserializedTreatmentMedication($request);

        $treatmentMedicationInDb
            ->setName($treatmentMedicationFromContent->getName())
            ->setTreatmentDuration($treatmentMedicationFromContent->getTreatmentDuration())
            ->setWaitingDays($treatmentMedicationFromContent->getWaitingDays())
            ->setDosage($treatmentMedicationFromContent->getDosage())
            ->setRegNl($treatmentMedicationFromContent->getRegNl())
            ->setDosageUnit($treatmentMedicationFromContent->getDosageUnit());

        $this->getManager()->persist($treatmentMedicationInDb);
        $this->getManager()->flush();

        $output = $this->getBaseSerializer()->getDecodedJson($treatmentMedicationFromContent, [JmsGroup::TREATMENT_TEMPLATE]);
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

        $unlinkedTemplates = [];
        foreach ($treatmentMedication->getTreatmentTemplates() as $template) {
            $unlinkedTemplates[] = $template->getDescription();
            $template->removeMedication($treatmentMedication);
            $this->getManager()->persist($template);
        }

        $this->getManager()->persist($treatmentMedication);
        $this->getManager()->flush();

        AdminActionLogWriter::deleteTreatmentMedication($this->getManager(), $admin, $treatmentMedication);

        $output = $this->getBaseSerializer()->getDecodedJson($treatmentMedication, [JmsGroup::TREATMENT_TEMPLATE]);
        return ResultUtil::successResult([
            'medication' => $output,
            'unlinkedTemplates' => $unlinkedTemplates,
        ]);
    }

    /**
     * @param Request $request
     * @return TreatmentMedication
     */
    private function baseValidateDeserializedTreatmentMedication($request)
    {
        /** @var TreatmentMedication $treatmentMedication */
        $treatmentMedication = $this->getBaseSerializer()->deserializeToObject($request->getContent(), TreatmentMedication::class);
        if (!($treatmentMedication instanceof TreatmentMedication)) {
            throw new BadRequestHttpException('Json body must have the TreatmentMedication structure');
        }

        if ($treatmentMedication->getName() === null) {
            throw new BadRequestHttpException('Name is missing');
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
