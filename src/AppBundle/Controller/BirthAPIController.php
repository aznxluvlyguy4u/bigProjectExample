<?php

namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Service\BirthService;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/api/v1/births")
 */
class BirthAPIController extends APIController implements BirthAPIControllerInterface
{

    /**
     * Get all births for a given litter
     *
     * @ApiDoc(
     *   section = "Births",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Get all births for a given litter"
     * )
     * @param Request $request the request object
     * @param string $litterId
     * @return JsonResponse
     * @Route("/{litterId}")
     * @Method("GET")
     */
    public function getBirth(Request $request, $litterId)
    {
        return $this->get(BirthService::class)->getBirth($request, $litterId);
    }

    /**
    * Retrieve all births for a given location
    *
    * @ApiDoc(
    *   section = "Births",
    *   requirements={
    *     {
    *       "name"="AccessToken",
    *       "dataType"="string",
    *       "requirement"="",
    *       "description"="A valid accesstoken belonging to the user that is registered with the API"
    *     }
    *   },
    *   resource = true,
    *   description = "Retrieve all births for a given location"
    * )
    * @param Request $request the request object
    * @return JsonResponse
    * @Route("")
    * @Method("GET")
    */
    public function getHistoryBirths(Request $request)
    {
        return $this->get(BirthService::class)->getHistoryBirths($request);
    }

    /**
    * Create a new birth of an animal
    *
    * @ApiDoc(
    *   section = "Births",
    *   requirements={
    *     {
    *       "name"="AccessToken",
    *       "dataType"="string",
    *       "requirement"="",
    *       "description"="A valid accesstoken belonging to the user that is registered with the API"
    *     }
    *   },
    *   resource = true,
    *   description = " Create a new birth of an animal"
    * )
    * Create a new DeclareBirth request
    * @param Request $request the request object
    * @return JsonResponse
    * @Route("")
    * @Method("POST")
    */
    public function createBirth(Request $request)
    {
        return $this->get(BirthService::class)->createBirth($request);
    }


    /**
     * Resend one OPEN birth declaration to RVO that are missing a response message.
     *
     * Only for SuperAdmin
     *
     * @ApiDoc(
     *   section = "Births",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Resend OPEN birth declarations to RVO that are missing a response message"
     * )
     * Create a new DeclareBirth request
     * @param Request $request the request object
     * @param int $litterId
     * @return JsonResponse
     * @Route("/{litterId}", requirements={"litterId"="\d+"})
     * @Method("POST")
     */
    public function resendCreateBirths(Request $request, $litterId)
    {
        return $this->get(BirthService::class)->resendCreateBirthsByLitterId($litterId);
    }


    /**
     * Resend OPEN birth declarations to RVO that are missing a response message.
     *
     * Only for developer.
     *
     * @ApiDoc(
     *   section = "Births",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Resend OPEN birth declarations to RVO that are missing a response message"
     * )
     * Create a new DeclareBirth request
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/resend")
     * @Method("POST")
     */
    public function resendAllCreateBirths(Request $request)
    {
        return $this->get(BirthService::class)->resendAllCreateBirths($request);
    }



    /**
     * Revoke a birth of an animal
     *
     * @ApiDoc(
     *   section = "Births",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Revoke a birth of an animal"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/revoke")
     * @Method("POST")
     */
    public function revokeBirth(Request $request)
    {
        return $this->get(BirthService::class)->revokeBirth($request);
    }

    /**
     * Get a list of suggested candidate fathers based on matings done within 145 + (-12 & +12) days, from now, and all other Rams on current location
     *
     * @ApiDoc(
     *   section = "Births",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Get a list of suggested candidate fathers based on matings done within 145 + (-12 & +12) days, from now and all other Rams on current location"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/{uln}/candidate-fathers")
     * @Method("POST")
     */
    public function getCandidateFathers(Request $request, $uln)
    {
        return $this->get(BirthService::class)->getCandidateFathers($request, $uln);
    }

    /**
     * Get a list of suggested candidate surrogates based on births done within 5,5 months from given date of birth, and all other Ewes on current location
     *
     * @ApiDoc(
     *   section = "Births",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Get a list of suggested candidate surrogates based on births done within 5,5 months from given date of birth, and all other Ewes on current location"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/{uln}/candidate-surrogates")
     * @Method("POST")
     */
    public function getCandidateSurrogateMothers(Request $request, $uln)
    {
        return $this->get(BirthService::class)->getCandidateSurrogateMothers($request, $uln);
    }

    /**
     * Get a list of suggested mothers based on matings done within 145 days and all other Ewes on current location
     *
     * @ApiDoc(
     *   section = "Births",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Get a list of suggested mothers based on matings done within 145 days and all other Ewes on current location"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/candidate-mothers")
     * @Method("POST")
     */
    public function getCandidateMothers(Request $request)
    {
        return $this->get(BirthService::class)->getCandidateMothers($request);
    }

    /**
     * TODO delete me from both Front-end and API
     * Temporarily endpoint to let catch errors.
     *
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("-errors")
     * @Method("GET")
     */
    public function getBirthErrors(Request $request)
    {
        return $this->get(BirthService::class)->getBirthErrors($request);
    }


    /**
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/internal")
     * @Method("POST")
     */
    public function processInternalQueueMessage(Request $request)
    {
        return $this->get(BirthService::class)->processInternalQueueMessage($request);
    }


}