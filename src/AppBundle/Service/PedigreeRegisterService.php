<?php

namespace AppBundle\Service;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Controller\PedigreeRegisterAPIControllerInterface;
use AppBundle\Entity\PedigreeRegister;
use AppBundle\Entity\PedigreeRegisterRepository;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PedigreeRegisterService
 * @package AppBundle\Service
 */
class PedigreeRegisterService extends ControllerServiceBase implements PedigreeRegisterAPIControllerInterface
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    function getPedigreeRegisters(Request $request)
    {
        $includeNonNsfoRegisters = RequestUtil::getBooleanQuery($request, JsonInputConstant::INCLUDE_NON_NSFO_REGISTERS);
        /** @var PedigreeRegisterRepository $repository */
        $repository = $this->getManager()->getRepository(PedigreeRegister::class);

        if($includeNonNsfoRegisters) {
            $pedigreeRegisters = $repository->findAll();
        } else {
            $pedigreeRegisters = $repository->getNsfoRegisters();
        }

        $output = $this->getBaseSerializer()->getDecodedJson($pedigreeRegisters);
        return ResultUtil::successResult($output);
    }

}