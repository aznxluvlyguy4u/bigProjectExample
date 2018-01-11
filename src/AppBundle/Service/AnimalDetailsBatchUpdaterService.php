<?php


namespace AppBundle\Service;


use AppBundle\Constant\JsonInputConstant;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use Symfony\Component\HttpFoundation\Request;

class AnimalDetailsBatchUpdaterService extends ControllerServiceBase
{
    public function updateAnimalDetails(Request $request)
    {
        $content = RequestUtil::getContentAsArray($request);

        $animals = $content->get(JsonInputConstant::ANIMALS);

        return ResultUtil::successResult($animals);
    }
}