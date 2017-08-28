<?php

namespace AppBundle\Service;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Controller\TreatmentTypeAPIControllerInterface;
use AppBundle\Entity\TreatmentType;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Enumerator\TreatmentTypeOption;
use AppBundle\Util\AdminActionLogWriter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\Validator;
use AppBundle\Validation\AdminValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TreatmentTemplateService
 * @package AppBundle\Service
 */
class TreatmentTypeService extends TreatmentServiceBase implements TreatmentTypeAPIControllerInterface
{
    public function __construct(EntityManagerInterface $em, IRSerializer $serializer,
                                CacheService $cacheService, UserService $userService)
    {
        parent::__construct($em, $serializer, $cacheService, $userService);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    function getByQuery(Request $request)
    {
        $activeOnly = RequestUtil::getBooleanQuery($request,QueryParameter::ACTIVE_ONLY, true);
        $type = self::getValidateType($request->query->get(QueryParameter::TYPE_QUERY));
        if ($type instanceof JsonResponse) { return $type; }

        $templates = $this->treatmentTypeRepository->findByQueries($activeOnly, $type);
        $output = $this->getBaseSerializer()->getDecodedJson($templates, [JmsGroup::TREATMENT_TEMPLATE]);

        return ResultUtil::successResult($output);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    function create(Request $request)
    {
        $admin = $this->getEmployee();
        if($admin === null) { return AdminValidator::getStandardErrorResponse(); }

        //Deserialization and Validation
        $treatmentTypeFromContent = $this->baseValidateDeserializedTreatmentType($request);
        if ($treatmentTypeFromContent instanceof JsonResponse) { return $treatmentTypeFromContent; }

        $treatmentTypeInDb = $this->treatmentTypeRepository
            ->findOneByTypeAndDescription($treatmentTypeFromContent->getType(), $treatmentTypeFromContent->getDescription());

        $treatmentType = $treatmentTypeFromContent;
        if ($treatmentTypeInDb) {
            if ($treatmentTypeInDb->isActive()) {
                return Validator::createJsonResponse('Behandelingstype bestaat al', 428);
            } else {
                //Reactivate
                $treatmentTypeInDb->setIsActive(true);
                $treatmentType = $treatmentTypeInDb;
            }
        }

        $treatmentType->__construct();
        $treatmentType->setCreationBy($this->getUser());

        $this->getManager()->persist($treatmentType);
        $this->getManager()->flush();

        AdminActionLogWriter::createTreatmentType($this->getManager(), $admin, $request, $treatmentType);

        $output = $this->getBaseSerializer()->getDecodedJson($treatmentType, [JmsGroup::TREATMENT_TEMPLATE]);
        return ResultUtil::successResult($output);
    }


    /**
     * @param Request $request
     * @return JsonResponse|TreatmentType
     */
    private function baseValidateDeserializedTreatmentType($request)
    {
        /** @var TreatmentType $treatmentType */
        $treatmentType = $this->getBaseSerializer()->deserializeToObject($request->getContent(), TreatmentType::class);
        if (!($treatmentType instanceof TreatmentType)) {
            return Validator::createJsonResponse('Json body must have the TreatmentType structure', 428);
        }

        $description = $treatmentType->getDescription();
        if ($description === null) {
            return Validator::createJsonResponse('Description is missing', 428);
        }

        $type = TreatmentTypeService::getValidateType($treatmentType->getType());
        if ($type instanceof JsonResponse) { return $type; }

        return $treatmentType;
    }


    /**
     * @param Request $request
     * @param int $treatmentTypeId
     * @return JsonResponse
     */
    function edit(Request $request, $treatmentTypeId)
    {
        $admin = $this->getEmployee();
        if($admin === null) { return AdminValidator::getStandardErrorResponse(); }

        /** @var TreatmentType $treatmentTypeInDb */
        $treatmentTypeInDb = $this->treatmentTypeRepository->find($treatmentTypeId);
        if ($treatmentTypeInDb === null) { return Validator::createJsonResponse('TreatmentType not found for given id', 428); }
        if ($treatmentTypeInDb->isActive() === false) { return Validator::createJsonResponse('Template has been deactivated', 428); }
        $type = $treatmentTypeInDb->getType();

        //Deserialization and Validation
        $treatmentTypeFromContent = $this->baseValidateDeserializedTreatmentType($request);
        if ($treatmentTypeFromContent instanceof JsonResponse) { return $treatmentTypeFromContent; }

        if ($treatmentTypeFromContent->getType() !== null && $treatmentTypeFromContent->getType() !== $type) {
            //Prevent unpredictable results by blocking the editing of the type.
            return Validator::createJsonResponse('Template type may not be edited!', 428);
        }

        $treatmentTypeInDbByValues = $this->treatmentTypeRepository
            ->findOneByTypeAndDescription($treatmentTypeFromContent->getType(), $treatmentTypeFromContent->getDescription());

        $isAnyValueUpdated = false;
        $this->actionLogDescription = '';

        $newDescription = $treatmentTypeFromContent->getDescription();
        $oldDescription = $treatmentTypeInDb->getDescription();

        if ($oldDescription !== $newDescription) {
            $this->appendUpdateDescription($oldDescription, $newDescription);
            $isAnyValueUpdated = true;
        }

        $treatmentTypeOutput = $treatmentTypeInDb;
        if ($isAnyValueUpdated) {

            $isSimpleUpdate = false;
            if ($treatmentTypeInDbByValues !== null) {

                if ($treatmentTypeInDbByValues->getId() === $treatmentTypeInDb->getId()) {
                    $isSimpleUpdate = true;

                } else {

                    if ($treatmentTypeInDbByValues->isActive()) {
                        return Validator::createJsonResponse(
                            'Er bestaat al een behandelingstype met dezelfde type '
                            .$treatmentTypeInDb->getDutchType().'('.$type.') en beschrijving', 428);

                    } else {
                        $treatmentTypeInDbByValues->setIsActive(true);
                        $this->getManager()->persist($treatmentTypeInDbByValues);
                        $treatmentTypeOutput = $treatmentTypeInDbByValues;

                        $treatmentTypeInDb->setIsActive(false);
                        $this->getManager()->persist($treatmentTypeInDb);

                        $this->getManager()->flush();
                    }
                }

            } else {
                $isSimpleUpdate = true;
            }

            if ($isSimpleUpdate) {
                $treatmentTypeInDb->setDescription($newDescription);
                $this->getManager()->persist($treatmentTypeInDb);
                $this->getManager()->flush();
            }

            AdminActionLogWriter::editTreatmentType($this->getManager(), $admin, $this->actionLogDescription);
        }

        $output = $this->getBaseSerializer()->getDecodedJson($treatmentTypeOutput, [JmsGroup::TREATMENT_TEMPLATE]);
        return ResultUtil::successResult($output);
    }


    /**
     * @param Request $request
     * @param int $treatmentTypeId
     * @return JsonResponse
     */
    function delete(Request $request, $treatmentTypeId)
    {
        $admin = $this->getEmployee();
        if($admin === null) { return AdminValidator::getStandardErrorResponse(); }

        /** @var TreatmentType $treatmentType */
        $treatmentType = $this->treatmentTypeRepository->find($treatmentTypeId);
        if ($treatmentType === null) { return Validator::createJsonResponse('TreatmentType not found for given id', 428); }
        if ($treatmentType->isActive() === false) { return Validator::createJsonResponse('Template has already been deactivated', 428); }

        $treatmentType->setIsActive(false);
        $this->getManager()->persist($treatmentType);
        $this->getManager()->flush();

        AdminActionLogWriter::deleteTreatmentType($this->getManager(), $admin, $treatmentType);

        $output = $this->getBaseSerializer()->getDecodedJson($treatmentType, [JmsGroup::TREATMENT_TEMPLATE]);
        return ResultUtil::successResult($output);
    }


    /**
     * @param string $type
     * @return JsonResponse|string|null
     */
    public static function getValidateType($type)
    {
        if ($type === null) { return $type; }

        $allowedTypes = TreatmentTypeOption::getConstants();
        if (is_string($type)) {
            $type = strtoupper($type);
            if (key_exists($type, $allowedTypes)) {
                return $type;
            }
        }
        return Validator::createJsonResponse(
            'Invalid type ['.$type.'] given in query. '
            .'Only allowed types are '.implode(', ', $allowedTypes), 428);
    }
}