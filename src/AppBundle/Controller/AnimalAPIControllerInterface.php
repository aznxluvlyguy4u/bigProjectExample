<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;

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
   * @param string $ulnString
   * @return jsonResponse
   */
  function getAnimalDetailsByUln(Request $request, $ulnString);

  /**
   * @param Request $request
   * @return jsonResponse
   */
  function getLiveStock(Request $request);

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