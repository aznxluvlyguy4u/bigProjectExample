<?php

namespace AppBundle\Service;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Controller\VwaEmployeeAPIControllerInterface;
use AppBundle\Entity\VwaEmployee;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class VwaEmployeeService
 * @package AppBundle\Service
 */
class VwaEmployeeService extends AuthServiceBase implements VwaEmployeeAPIControllerInterface
{
    const VWA_PASSWORD_LENGTH = 9;


    /**
     * @param Request $request
     * @return JsonResponse
     */
    function getAll(Request $request)
    {
        // TODO: Implement getById() method.

        return ResultUtil::successResult('ok');
    }


    /**
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    function getById(Request $request, $id)
    {
        // TODO: Implement getById() method.

        return ResultUtil::successResult('ok');
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    function create(Request $request)
    {
        // TODO: Implement create() method.

        return ResultUtil::successResult('ok');
    }


    /**
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    function edit(Request $request, $id)
    {
        // TODO: Implement edit() method.

        return ResultUtil::successResult('ok');
    }

    /**
     * @param string $id
     * @param Request $request
     * @return mixed
     */
    function deactivate(Request $request, $id)
    {
        // TODO: Implement deactivate() method.

        return ResultUtil::successResult('ok');
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function invite(Request $request)
    {
        // TODO: Implement invite() method. READ ids from content

        return ResultUtil::successResult('ok');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    function authorize(Request $request)
    {
        // TODO: Implement authorize() method.

        return ResultUtil::successResult('ok');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    function passwordResetRequest(Request $request)
    {
        // TODO: Implement passwordReset() method.

        return ResultUtil::successResult('ok');
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    function passwordResetConfirmation(Request $request)
    {
        // TODO: Implement passwordReset() method.

        return ResultUtil::successResult('ok');
    }


}