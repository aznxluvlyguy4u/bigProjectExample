<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Entity\DeclareExport;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\ResultUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;

class ExportService extends DeclareControllerServiceBase
{
    /**
     * @param Request $request
     * @param $Id
     * @return JsonResponse
     */
    public function getExportById(Request $request, $Id) {
        $location = $this->getSelectedLocation($request);
        $export = $this->getManager()->getRepository(DeclareExport::class)->getExportByRequestId($location, $Id);
        return new JsonResponse($export, 200);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getExports(Request $request)
    {
        $location = $this->getSelectedLocation($request);
        $stateExists = $request->query->has(Constant::STATE_NAMESPACE);
        $repository = $this->getManager()->getRepository(Constant::DECLARE_EXPORT_REPOSITORY);

        if(!$stateExists) {
            $declareExports = $repository->getExports($location);

        } else if ($request->query->get(Constant::STATE_NAMESPACE) == Constant::HISTORY_NAMESPACE ) {

            $declareExports = new ArrayCollection();
            foreach($repository->getExports($location, RequestStateType::OPEN) as $export) {
                $declareExports->add($export);
            }

            foreach($repository->getExports($location, RequestStateType::REVOKING) as $export) {
                $declareExports->add($export);
            }
            foreach($repository->getExports($location, RequestStateType::FINISHED) as $export) {
                $declareExports->add($export);
            }

        } else { //A state parameter was given, use custom filter to find subset
            $state = $request->query->get(Constant::STATE_NAMESPACE);
            $declareExports = $repository->getExports($location, $state);
        }

        return ResultUtil::successResult($declareExports);
    }
}