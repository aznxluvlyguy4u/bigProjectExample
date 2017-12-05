<?php

namespace AppBundle\Service;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Controller\BreedTypeAPIControllerInterface;
use AppBundle\Enumerator\BreedType;
use AppBundle\Util\ResultUtil;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class BreedTypeService
 * @package AppBundle\Service
 */
class BreedTypeService extends ControllerServiceBase implements BreedTypeAPIControllerInterface
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    function getBreedTypes(Request $request)
    {
        $values = BreedType::getAllInDutch();
        unset($values[BreedType::EN_BASIS]);
        unset($values[BreedType::EN_MANAGEMENT]);

        return ResultUtil::successResult($values);
    }

}