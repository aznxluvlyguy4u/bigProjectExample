<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Tag;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\Validator;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;


class TagsService extends ControllerServiceBase
{
    /**
     * @param Request $request
     * @param $Id
     * @return JsonResponse
     */
    public function getTagById(Request $request, $Id)
    {
        //validate if Id is of format: AZ123456789
        $isValidUlnFormat = Validator::verifyUlnFormat($Id);

        if(!$isValidUlnFormat){
            return new JsonResponse(
                array("errorCode" => 428,
                    "errorMessage" => "Given tagId format is invalid, supply tagId in the following format: AZ123456789"), 428);
        }

        $client = $this->getAccountOwner($request);
        $tag = $this->getManager()->getRepository(Tag::class)->findOneByString($client, $Id);

        if($tag == null) {
            return new JsonResponse(
                array("errorCode" => 400,
                    "errorMessage" => "No tag found"), 400);
        } else {
            return new JsonResponse($tag, 200);
        }
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getTags(Request $request)
    {
        $client = $this->getAccountOwner($request);
        $location = $this->getSelectedLocation($request);
        $tagRepository = $this->getManager()->getRepository(Tag::class);

        //No explicit filter given, thus find all
        if(!$request->query->has(Constant::STATE_NAMESPACE)) {
            //Only retrieve tags that are either assigned OR unassigned, ignore transferred tags
            $tags = $tagRepository->findTags($client, $location);
        } else { //A state parameter was given, use custom filter to find subset
            $tagStatus = $request->query->get(Constant::STATE_NAMESPACE);
            $tags = $tagRepository->findTags($client, $location, $tagStatus);
        }

        return ResultUtil::successResult($tags);
    }
}