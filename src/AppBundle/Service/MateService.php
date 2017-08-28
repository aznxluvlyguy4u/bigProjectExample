<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\MateBuilder;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Mate;
use AppBundle\Output\MateOutput;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\MateValidator;
use Symfony\Component\HttpFoundation\Request;

class MateService extends ControllerServiceBase
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createMate(Request $request)
    {
        $content = RequestUtil::getContentAsArray($request);
        $client = $this->getAccountOwner($request);
        $location = $this->getSelectedLocation($request);
        $loggedInUser = $this->getUser();

        $log = ActionLogWriter::createMate($this->getManager(), $client, $loggedInUser, $location, $content);

        $validateEweGender = true;
        $mateValidator = new MateValidator($this->getManager(), $content, $client, $validateEweGender);
        if(!$mateValidator->getIsInputValid()) { return $mateValidator->createJsonResponse(); }

        $mate = MateBuilder::post($this->getManager(), $content, $client, $loggedInUser, $location);

        //TODO when messaging system is complete, have the studRam owner confirm the mate
        MateBuilder::approveMateDeclaration($mate, $loggedInUser);

        $this->persistAndFlush($mate);

        $output = MateOutput::createMateOverview($mate);

        $log = ActionLogWriter::completeActionLog($this->getManager(), $log);

        return ResultUtil::successResult($output);
    }


    /**
     * @param Request $request
     * @param $messageId
     * @return JsonResponse
     */
    public function editMate(Request $request, $messageId)
    {
        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();
        $content = RequestUtil::getContentAsArray($request);
        $content->set(JsonInputConstant::MESSAGE_ID, $messageId);
        $location = $this->getSelectedLocation($request);

        $log = ActionLogWriter::editMate($this->getManager(), $client, $loggedInUser, $location, $content);

        $validateEweGender = true;
        $isPost = false;
        $mateValidator = new MateValidator($this->getManager(), $content, $client, $validateEweGender, $isPost);
        if(!$mateValidator->getIsInputValid()) { return $mateValidator->createJsonResponse(); }

        $mate = $mateValidator->getMateFromMessageId();
        $mate = MateBuilder::edit($this->getManager(), $mate, $content, $client, $loggedInUser, $location);

        //TODO when messaging system is complete, have the studRam owner confirm the mate
        MateBuilder::approveMateDeclaration($mate, $loggedInUser);

        $this->persistAndFlush($mate);

        $output = MateOutput::createMateOverview($mate);

        $log = ActionLogWriter::completeActionLog($this->getManager(), $log);

        return ResultUtil::successResult($output);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getMateHistory(Request $request)
    {
        $location = $this->getSelectedLocation($request);
        $matings = $this->getManager()->getRepository(Mate::class)->getMatingsHistoryOutput($location);
        return ResultUtil::successResult($matings);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getMateErrors(Request $request)
    {
        $location = $this->getSelectedLocation($request);
        $matings = $this->getManager()->getRepository(Mate::class)->getMatingsErrorOutput($location);
        return new JsonResponse([JsonInputConstant::RESULT => $matings],200);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getMatingsToBeVerified(Request $request)
    {
        $location = $this->getSelectedLocation($request);
        $matings = $this->getManager()->getRepository(Mate::class)->getMatingsStudRamOutput($location);
        return new JsonResponse([JsonInputConstant::RESULT => $matings],200);
    }
}