<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Exterior;
use AppBundle\Entity\ExteriorRepository;
use AppBundle\Entity\Inspector;
use AppBundle\Entity\InspectorAuthorization;
use AppBundle\Entity\InspectorAuthorizationRepository;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\InspectorMeasurementType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Util\TimeUtil;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\AnimalDetailsValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1/measurements")
 */
class MeasurementAPIController extends APIController implements MeasurementAPIControllerInterface
{



    /**
     *
     * Update an exterior measurement for a specific ULN and measurementDate. For example NL100029511721 and 2016-12-05
     *
     * @ApiDoc(
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Update an exterior measurement for a specific ULN and measurementDate",
     *   input = "AppBundle\Entity\Exterior",
     *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
     * )
     *
     * @param Request $request the request object
     * @param String $ulnString
     * @return jsonResponse
     * @Route("/{ulnString}/exteriors")
     * @Method("POST")
     */
    public function createExteriorMeasurement(Request $request, $ulnString)
    {
        $loggedInUser = $this->getLoggedInUser($request);
        $adminValidator = new AdminValidator($loggedInUser, AccessLevelType::ADMIN);
        $isAdmin = $adminValidator->getIsAccessGranted();
        $em = $this->getDoctrine()->getManager();

        $location = null;
        if (!$isAdmin) {
            $location = $this->getSelectedLocation($request);
        }

        $animalDetailsValidator = new AnimalDetailsValidator($em, $isAdmin, $location, $ulnString);
        if (!$animalDetailsValidator->getIsInputValid()) {
            return $animalDetailsValidator->createJsonResponse();
        }
        $animal = $animalDetailsValidator->getAnimal();

        //TODO validate content
        //TODO validate measurementDateString format YYYY-MM-DD AND set time is 0hour
        //TODO AND VALIDATE IF MEASUREMENT_DATE ALREADY EXISTS !!!
        $content = $this->getContentAsArray($request);
        /** @var ExteriorRepository $repository */
        $inspectorRepository = $em->getRepository(Inspector::class);
        $inspector = $inspectorRepository->findOneBy(['personId' => $content->get(JsonInputConstant::INSPECTOR_ID)]);

        $exterior = new Exterior();

        $measurementDate = new \DateTime($content->get(JsonInputConstant::MEASUREMENT_DATE));

        $exterior->setActionBy($loggedInUser);
        $exterior->setEditDate(new \DateTime());
        $exterior->setMeasurementDate($measurementDate);
        $exterior->setKind($content->get(JsonInputConstant::KIND));
        $exterior->setSkull($content->get(JsonInputConstant::SKULL));
        $exterior->setProgress($content->get(JsonInputConstant::PROGRESS));
        $exterior->setMuscularity($content->get(JsonInputConstant::MUSCULARITY));
        $exterior->setProportion($content->get(JsonInputConstant::PROPORTION));
        $exterior->setExteriorType($content->get(JsonInputConstant::TYPE));
        $exterior->setLegWork($content->get(JsonInputConstant::LEG_WORK));
        $exterior->setFur($content->get(JsonInputConstant::FUR));
        $exterior->setGeneralAppearence($content->get(JsonInputConstant::GENERAL_APPEARANCE));
        $exterior->setHeight($content->get(JsonInputConstant::HEIGHT));
        $exterior->setBreastDepth($content->get(JsonInputConstant::BREAST_DEPTH));
        $exterior->setTorsoLength($content->get(JsonInputConstant::TORSO_LENGTH));
        $exterior->setMarkings($content->get(JsonInputConstant::MARKINGS));
        $exterior->setInspector($inspector);
        $exterior->setAnimalIdAndDateByAnimalAndDateTime($animal, $measurementDate);

        $em->persist($exterior);
        $em->flush();

        $output = 'OK';
        return new JsonResponse([Constant::RESULT_NAMESPACE => $output], 200);
    }


    /**
     *
     * Update an exterior measurement for a specific ULN and measurementDate. For example NL100029511721 and 2016-12-05
     *
     * @ApiDoc(
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Update an exterior measurement for a specific ULN and measurementDate",
     *   input = "AppBundle\Entity\Exterior",
     *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
     * )
     *
     * @param Request $request the request object
     * @param String $ulnString
     * @param String $measurementDate
     * @return jsonResponse
     * @Route("/{ulnString}/exteriors/{measurementDate}")
     * @Method("PUT")
     */
    public function editExteriorMeasurement(Request $request, $ulnString, $measurementDate)
    {
        $loggedInUser = $this->getLoggedInUser($request);
        $adminValidator = new AdminValidator($loggedInUser, AccessLevelType::ADMIN);
        $isAdmin = $adminValidator->getIsAccessGranted();
        $em = $this->getDoctrine()->getManager();

        $location = null;
        if (!$isAdmin) {
            $location = $this->getSelectedLocation($request);
        }

        $animalDetailsValidator = new AnimalDetailsValidator($em, $isAdmin, $location, $ulnString);
        if (!$animalDetailsValidator->getIsInputValid()) {
            return $animalDetailsValidator->createJsonResponse();
        }
        $animal = $animalDetailsValidator->getAnimal();

        //TODO validate content
        $content = $this->getContentAsArray($request);
        /** @var ExteriorRepository $repository */
        $inspectorRepository = $em->getRepository(Inspector::class);
        $inspector = $inspectorRepository->findOneBy(['personId' => $content->get(JsonInputConstant::INSPECTOR_ID)]);

        //TODO validate measurementDateString format YYYY-MM-DD AND set time is 0hour
        $measurementDate = new \DateTime($measurementDate);


        /** @var ExteriorRepository $repository */
        $repository = $em->getRepository(Exterior::class);
        /** @var Exterior $exterior */
        $exterior = $repository->findOneBy(['measurementDate' => $measurementDate, 'animal' => $animal]);

        $exterior->setActionBy($loggedInUser);
        $exterior->setEditDate(new \DateTime());
        $exterior->setMeasurementDate($measurementDate);
        $exterior->setKind($content->get(JsonInputConstant::KIND));
        $exterior->setSkull($content->get(JsonInputConstant::SKULL));
        $exterior->setProgress($content->get(JsonInputConstant::PROGRESS));
        $exterior->setMuscularity($content->get(JsonInputConstant::MUSCULARITY));
        $exterior->setProportion($content->get(JsonInputConstant::PROPORTION));
        $exterior->setExteriorType($content->get(JsonInputConstant::TYPE));
        $exterior->setLegWork($content->get(JsonInputConstant::LEG_WORK));
        $exterior->setFur($content->get(JsonInputConstant::FUR));
        $exterior->setGeneralAppearence($content->get(JsonInputConstant::GENERAL_APPEARANCE));
        $exterior->setHeight($content->get(JsonInputConstant::HEIGHT));
        $exterior->setBreastDepth($content->get(JsonInputConstant::BREAST_DEPTH));
        $exterior->setTorsoLength($content->get(JsonInputConstant::TORSO_LENGTH));
        $exterior->setMarkings($content->get(JsonInputConstant::MARKINGS));
        $exterior->setInspector($inspector);
        $exterior->setAnimalIdAndDateByAnimalAndDateTime($animal, $measurementDate);

        $em->persist($exterior);
        $em->flush();

        $output = 'OK';
        return new JsonResponse([Constant::RESULT_NAMESPACE => $output], 200);
    }


    /**
     *
     * Return the allowed exterior measurement kinds for a specific ULN and measurementDate. For example NL100029511721 and 2016-12-05
     *
     * @ApiDoc(
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Update an exterior measurement for a specific ULN and measurementDate",
     *   input = "AppBundle\Entity\Exterior",
     *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
     * )
     *
     * @param Request $request the request object
     * @param String $ulnString
     * @return jsonResponse
     * @Route("/{ulnString}/exteriors/kinds")
     * @Method("GET")
     */
    public function getAllowedExteriorKinds(Request $request, $ulnString)
    {

        /*
         * VG voorlopig gekeurd: 5-14 maanden (leeftijd)
    DD direct definitief (als het nog geen VG heeft): 14-26 maanden (leeftijd)
    DF definitief (als het al een VG heeft): 14-26 maanden (leeftijd)
    DO dood voor keuring (kan altijd voor een dier dat dood is)
    HK herkeuring (moet al een DD of DF of VG hebben)
    HH herhaalde keuring > 26 maanden (leeftijd) & (moet al een DD of DF hebben)
         */

        $output = [
            [
                'code' => 'DD',
            ],
            [
                'code' => 'DF',
            ],
            [
                'code' => 'DO',
            ],
            [
                'code' => 'HK',
            ],
            [
                'code' => 'HH',
            ],
        ];
        return new JsonResponse([Constant::RESULT_NAMESPACE => $output], 200);
    }


    /**
     *
     * Return the allowed exterior measurement kinds for a specific ULN and measurementDate. For example NL100029511721 and 2016-12-05
     *
     * @ApiDoc(
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Update an exterior measurement for a specific ULN and measurementDate",
     *   input = "AppBundle\Entity\Exterior",
     *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
     * )
     *
     * @param Request $request the request object
     * @param string $ulnString
     * @return jsonResponse
     * @Route("/{ulnString}/exteriors/inspectors")
     * @Method("GET")
     */
    public function getAllowedInspectorsForExteriorMeasurements(Request $request, $ulnString)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var InspectorAuthorizationRepository $repository */
        $repository = $em->getRepository(InspectorAuthorization::class);
        $output = $repository->getAuthorizedInspectorsExteriorByUln($ulnString);
        return new JsonResponse([Constant::RESULT_NAMESPACE => $output], 200);
    }

}