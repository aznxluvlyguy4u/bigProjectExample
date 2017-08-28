<?php


namespace AppBundle\Service;


use AppBundle\Cache\AnimalCacher;
use AppBundle\Component\DeclareWeightBuilder;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\DeclareWeight;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\DeclareWeightValidator;
use Symfony\Component\HttpFoundation\Request;

class WeightService extends ControllerServiceBase
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createWeightMeasurements(Request $request)
    {
        $content = RequestUtil::getContentAsArray($request);
        $client = $this->getAccountOwner($request);
        $location = $this->getSelectedLocation($request);
        $loggedInUser = $this->getUser();

        $log = ActionLogWriter::createDeclareWeight($this->getManager(), $client, $loggedInUser, $content);

        $weightValidator = new DeclareWeightValidator($this->getManager(), $content, $client);
        if(!$weightValidator->getIsInputValid()) {
            return $weightValidator->createJsonResponse();
        }

        $declareWeight = DeclareWeightBuilder::post($this->getManager(), $content, $client, $loggedInUser, $location);
        $this->getManager()->persist($declareWeight->getWeightMeasurement());
        $this->persistAndFlush($declareWeight);
        AnimalCacher::cacheWeightByAnimal($this->getManager(), $declareWeight->getAnimal());

        $log = ActionLogWriter::completeActionLog($this->getManager(), $log);

        return ResultUtil::successResult('OK');
    }


    /**
     * @param Request $request
     * @param $messageId
     * @return JsonResponse
     */
    public function editWeightMeasurements(Request $request, $messageId)
    {
        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();
        $content = RequestUtil::getContentAsArray($request);
        $content->set(JsonInputConstant::MESSAGE_ID, $messageId);
        $location = $this->getSelectedLocation($request);

        $log = ActionLogWriter::editDeclareWeight($this->getManager(), $client, $loggedInUser, $content);

        $isPost = false;
        $weightValidator = new DeclareWeightValidator($this->getManager(), $content, $client, $isPost);
        if(!$weightValidator->getIsInputValid()) {
            return $weightValidator->createJsonResponse();
        }

        $declareWeight = $weightValidator->getDeclareWeightFromMessageId();
        $declareWeight = DeclareWeightBuilder::edit($this->getManager(), $declareWeight, $content, $client, $loggedInUser, $location);

        $this->persistAndFlush($declareWeight);
        AnimalCacher::cacheWeightByAnimal($this->getManager(), $declareWeight->getAnimal());

        $log = ActionLogWriter::completeActionLog($this->getManager(), $log);

        return ResultUtil::successResult('OK');
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getDeclareWeightHistory(Request $request)
    {
        $location = $this->getSelectedLocation($request);
        $declareWeights = $this->getManager()->getRepository(DeclareWeight::class)->getDeclareWeightsHistoryOutput($location);
        return ResultUtil::successResult($declareWeights);
    }
}