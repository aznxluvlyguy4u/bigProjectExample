<?php


namespace AppBundle\Service;


use AppBundle\Entity\Collar;
use AppBundle\Util\ResultUtil;
use Symfony\Component\HttpFoundation\Request;

class CollarService extends ControllerServiceBase
{
    /**
     * @param Request $request
     * @return \AppBundle\Component\HttpFoundation\JsonResponse
     */
    function getCollarCodes(Request $request)
    {
        $collarColourCodes = $this->getManager()
            ->getRepository(Collar::class)
            ->findAll();

        return ResultUtil::successResult($collarColourCodes);
    }
}