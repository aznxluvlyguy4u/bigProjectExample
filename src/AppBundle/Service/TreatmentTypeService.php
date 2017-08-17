<?php

namespace AppBundle\Service;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Controller\TreatmentTypeAPIControllerInterface;
use AppBundle\Entity\TreatmentType;
use AppBundle\Entity\TreatmentTypeRepository;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Enumerator\TreatmentTypeOption;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\Validator;
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
        $output = $this->serializer->getDecodedJson($templates, [JmsGroup::TREATMENT_TEMPLATE]);

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