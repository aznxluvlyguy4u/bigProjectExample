<?php

namespace AppBundle\Controller;

use AppBundle\Cache\AnimalCacher;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Exterior;
use AppBundle\Entity\ExteriorRepository;
use AppBundle\Entity\Inspector;
use AppBundle\Entity\InspectorAuthorization;
use AppBundle\Entity\InspectorAuthorizationRepository;
use AppBundle\Entity\InspectorRepository;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\InspectorMeasurementType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Util\MeasurementsUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\AnimalDetailsValidator;
use AppBundle\Validation\ExteriorValidator;
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

    const ALLOW_BLANK_INSPECTOR = true;

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
            return $adminValidator->createJsonErrorResponse();
            //$location = $this->getSelectedLocation($request);
        }

        $animalDetailsValidator = new AnimalDetailsValidator($em, $isAdmin, $location, $ulnString);
        if (!$animalDetailsValidator->getIsInputValid()) {
            return $animalDetailsValidator->createJsonResponse();
        }
        $animal = $animalDetailsValidator->getAnimal();

        $allowedExteriorCodes = MeasurementsUtil::getExteriorKinds($em, $animal);

        $content = $this->getContentAsArray($request);
        $exteriorValidator = new ExteriorValidator($em, $content, $allowedExteriorCodes, $ulnString, self::ALLOW_BLANK_INSPECTOR);
        if (!$exteriorValidator->getIsInputValid()) {
            return $exteriorValidator->createJsonResponse();
        }
        $inspector = $exteriorValidator->getInspector();
        $measurementDate = $exteriorValidator->getMeasurementDate();

        /** @var ExteriorRepository $repository */
        $repository = $em->getRepository(Exterior::class);
        /** @var Exterior $exterior */
        $exterior = $repository->findOneBy(['measurementDate' => $measurementDate, 'animal' => $animal]);
        if($exterior != null) {
            $output = 'THERE ALREADY EXISTS AN EXTERIOR MEASUREMENT ON THIS DATE';
            $code = 428;

        } else {
            $exterior = new Exterior();

            $exterior->setActionBy($loggedInUser);
            $exterior->setEditDate(new \DateTime());
            $exterior->setAnimal($animal);
            $exterior->setMeasurementDate($measurementDate);
            $exterior->setKind($exteriorValidator->getKind());
            $exterior->setSkull($exteriorValidator->getSkull());
            $exterior->setProgress($exteriorValidator->getProgress());
            $exterior->setMuscularity($exteriorValidator->getMuscularity());
            $exterior->setProportion($exteriorValidator->getProportion());
            $exterior->setExteriorType($exteriorValidator->getExteriorType());
            $exterior->setLegWork($exteriorValidator->getLegWork());
            $exterior->setFur($exteriorValidator->getFur());
            $exterior->setGeneralAppearence($exteriorValidator->getGeneralAppearence());
            $exterior->setHeight($exteriorValidator->getHeight());
            $exterior->setBreastDepth($exteriorValidator->getBreastDepth());
            $exterior->setTorsoLength($exteriorValidator->getTorsoLength());
            $exterior->setMarkings($exteriorValidator->getMarkings());
            $exterior->setInspector($inspector);
            $exterior->setAnimalIdAndDateByAnimalAndDateTime($animal, $measurementDate);

            $em->persist($exterior);
            $em->flush();

            //Update exterior values in animalCache AFTER persisting exterior
            AnimalCacher::cacheExteriorByAnimal($em, $animal);

            $output = 'OK';
            $code = 200;
        }

        return new JsonResponse([Constant::RESULT_NAMESPACE => $output, Constant::CODE_NAMESPACE => $code], $code);
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
     * @param String $measurementDateString
     * @return jsonResponse
     * @Route("/{ulnString}/exteriors/{measurementDateString}")
     * @Method("PUT")
     */
    public function editExteriorMeasurement(Request $request, $ulnString, $measurementDateString)
    {
        $loggedInUser = $this->getLoggedInUser($request);
        $adminValidator = new AdminValidator($loggedInUser, AccessLevelType::ADMIN);
        $isAdmin = $adminValidator->getIsAccessGranted();
        $em = $this->getDoctrine()->getManager();

        $location = null;
        if (!$isAdmin) {
            return $adminValidator->createJsonErrorResponse();
            //$location = $this->getSelectedLocation($request);
        }

        $animalDetailsValidator = new AnimalDetailsValidator($em, $isAdmin, $location, $ulnString);
        if (!$animalDetailsValidator->getIsInputValid()) {
            return $animalDetailsValidator->createJsonResponse();
        }
        $animal = $animalDetailsValidator->getAnimal();

        $allowedExteriorCodes = MeasurementsUtil::getExteriorKinds($em, $animal);

        $content = $this->getContentAsArray($request);
        $exteriorValidator = new ExteriorValidator($em, $content, $allowedExteriorCodes, $ulnString, self::ALLOW_BLANK_INSPECTOR, $measurementDateString);
        if (!$exteriorValidator->getIsInputValid()) {
            return $exteriorValidator->createJsonResponse();
        }
        $inspector = $exteriorValidator->getInspector();
        $measurementDate = $exteriorValidator->getMeasurementDate();


        /** @var ExteriorRepository $repository */
        $repository = $em->getRepository(Exterior::class);
        /** @var Exterior $exterior */
        $exterior = $repository->findOneBy(['measurementDate' => $measurementDate, 'animal' => $animal]);

        if($exterior instanceof Exterior) {
            $exterior->setActionBy($loggedInUser);
            $exterior->setEditDate(new \DateTime());
            $exterior->setAnimal($animal);
            $exterior->setMeasurementDate($measurementDate);
            $exterior->setKind($exteriorValidator->getKind());
            $exterior->setSkull($exteriorValidator->getSkull());
            $exterior->setProgress($exteriorValidator->getProgress());
            $exterior->setMuscularity($exteriorValidator->getMuscularity());
            $exterior->setProportion($exteriorValidator->getProportion());
            $exterior->setExteriorType($exteriorValidator->getExteriorType());
            $exterior->setLegWork($exteriorValidator->getLegWork());
            $exterior->setFur($exteriorValidator->getFur());
            $exterior->setGeneralAppearence($exteriorValidator->getGeneralAppearence());
            $exterior->setHeight($exteriorValidator->getHeight());
            $exterior->setBreastDepth($exteriorValidator->getBreastDepth());
            $exterior->setTorsoLength($exteriorValidator->getTorsoLength());
            $exterior->setMarkings($exteriorValidator->getMarkings());
            $exterior->setInspector($inspector);
            $exterior->setAnimalIdAndDateByAnimalAndDateTime($animal, $measurementDate);

            $em->persist($exterior);
            $em->flush();

            //Update exterior values in animalCache AFTER persisting exterior
            AnimalCacher::cacheExteriorByAnimal($em, $animal);

            $output = 'OK';
            $code = 200;
        } else {
            $output = 'Exterior for given date and uln does not exists!';
            $code = 428;
        }

        return new JsonResponse([Constant::RESULT_NAMESPACE => $output, Constant::CODE_NAMESPACE => $code], $code);
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
        //Uln has already been validated above
        $animal = $animalDetailsValidator->getAnimal();

        $output = MeasurementsUtil::getExteriorKindsOutput($em, $animal);

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

        $admin = $this->getAuthenticatedEmployee($request);
        $adminValidator = new AdminValidator($admin, AccessLevelType::SUPER_ADMIN);
        if(!$adminValidator->getIsAccessGranted()) { //validate if user is at least a SUPER_ADMIN
            return $adminValidator->createJsonErrorResponse();
        }

        /** @var InspectorAuthorizationRepository $repository */
        $repository = $em->getRepository(InspectorAuthorization::class);
        $output = $repository->getAuthorizedInspectorsExteriorByUln($ulnString);
        return new JsonResponse([Constant::RESULT_NAMESPACE => $output], 200);
    }

}