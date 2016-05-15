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
  function getAnimals(Request $request);

  /**
   * @param Request $request
   * @return mixed
   */
  function getAnimalDetails(Request $request);
}