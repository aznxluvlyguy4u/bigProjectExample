<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Entity\DeclareImport;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\ResultUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;

class ImportService extends DeclareControllerServiceBase
{
    /**
     * @param Request $request
     * @param $Id
     * @return JsonResponse
     */
    public function getImportById(Request $request, $Id)
    {
        $location = $this->getSelectedLocation($request);
        $import = $this->getManager()->getRepository(DeclareImport::class)->getImportByRequestId($location, $Id);

        return new JsonResponse($import, 200);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getImports(Request $request)
    {
        $location = $this->getSelectedLocation($request);
        $stateExists = $request->query->has(Constant::STATE_NAMESPACE);
        $repository = $this->getManager()->getRepository(DeclareImport::class);

        if(!$stateExists) {
            $declareImports = $repository->getImports($location);

        } else if ($request->query->get(Constant::STATE_NAMESPACE) == Constant::HISTORY_NAMESPACE ) {

            $declareImports = new ArrayCollection();
            foreach($repository->getImports($location, RequestStateType::OPEN) as $import) {
                $declareImports->add($import);
            }

            foreach($repository->getImports($location, RequestStateType::REVOKING) as $import) {
                $declareImports->add($import);
            }
            foreach($repository->getImports($location, RequestStateType::FINISHED) as $import) {
                $declareImports->add($import);
            }

        } else { //A state parameter was given, use custom filter to find subset
            $state = $request->query->get(Constant::STATE_NAMESPACE);
            $declareImports = $repository->getImports($location, $state);
        }

        return ResultUtil::successResult($declareImports);
    }
}