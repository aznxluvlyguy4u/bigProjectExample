<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
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
class MeasurementAPIController extends APIController implements MeasurementAPIControllerInterface {


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
   * @Route("/exteriors/{ulnString}/{measurementDate}")
   * @Method("PUT")
   */
  public function editExteriorMeasurements(Request $request, $ulnString, $measurementDate)
  {
    $loggedInUser = $this->getLoggedInUser($request);
    $adminValidator = new AdminValidator($loggedInUser, AccessLevelType::ADMIN);
    $isAdmin = $adminValidator->getIsAccessGranted();
    $em = $this->getDoctrine()->getManager();

    $location = null;
    if(!$isAdmin) {
      $location = $this->getSelectedLocation($request);
    }

    $animalDetailsValidator = new AnimalDetailsValidator($em, $isAdmin, $location, $ulnString);
    if(!$animalDetailsValidator->getIsInputValid()) {
      return $animalDetailsValidator->createJsonResponse();
    }

    $content = $this->getContentAsArray($request);


    dump($content);die;

    $output = 'WAZZAAAAP';
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
   * @Route("/exteriors/kinds/{ulnString}")
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

    $output = 'WAZZAAAAP';
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
   * @return jsonResponse
   * @Route("/exteriors/inspectors")
   * @Method("GET")
   */
  public function getAllowedInspectorsForMeasurements(Request $request)
  {
    $output = [
        [
            'person_id' => '00000000000000000',
            'first_name' => '',
            'last_name' => 'Hans te Mebel',
        ],
        [
            'person_id' => '00000000000000000',
            'first_name' => '',
            'last_name' => 'Johan Knaap',
        ],
        [
            'person_id' => '00000000000000000',
            'first_name' => '',
            'last_name' => 'Marjo van Bergen',
        ],
        [
            'person_id' => '00000000000000000',
            'first_name' => '',
            'last_name' => 'Wout Rodenburg',
        ],
        [
            'person_id' => '00000000000000000',
            'first_name' => '',
            'last_name' => 'Ido Altenburg',
        ],
        [
            'person_id' => '00000000000000000',
            'first_name' => '',
            'last_name' => 'Niet NSFO',
        ],
    ];
    return new JsonResponse([Constant::RESULT_NAMESPACE => $output], 200);
  }
  
}