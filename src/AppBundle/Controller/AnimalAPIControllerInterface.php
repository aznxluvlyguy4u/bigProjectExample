<?php

namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AnimalAPIControllerInterface
 * @package AppBundle\Controller
 */
interface AnimalAPIControllerInterface
{

  /**
   * @param Request $request
   * @return jsonResponse
   */
  function createRetrieveAnimals(Request $request);

  /**
   * @param Request $request
   * @param string $ulnStringOrId
   * @return jsonResponse
   */
  function getAnimalDetailsByUlnOrId(Request $request, $ulnStringOrId);

  /**
   * @param Request $request
   * @return jsonResponse
   */
  function getLiveStock(Request $request);

  /**
   * @param Request $request
   * @return jsonResponse
   */
  function getLatestRvoLeadingRetrieveAnimals(Request $request);

  /**
   * @param Request $request
   * @return jsonResponse
   */
  function createAnimalDetails(Request $request);

  /**
   * @param Request $request
   * @param $ulnString
   * @return jsonResponse
   */
  function changeGenderOfUln(Request $request);
}
