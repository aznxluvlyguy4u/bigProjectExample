<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

/**
 * Class AnimalAPIControllerInterface
 * @package AppBundle\Controller
 */
interface AnimalAPIControllerInterface
{

  /**
   * @param Request $request
   * @return mixed
   */
  function createRetrieveAnimals(Request $request);

  /**
   * @param Request $request
   * @param string $ulnString
   * @return mixed
   */
  function getAnimalDetailsByUln(Request $request, $ulnString);

  /**
   * @param Request $request
   * @return mixed
   */
  function getLiveStock(Request $request);

  /**
   * @param Request $request
   * @return mixed
   */
  function createAnimalDetails(Request $request);
}