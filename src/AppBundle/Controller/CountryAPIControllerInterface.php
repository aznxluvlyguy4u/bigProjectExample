<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface CountryAPIControllerInterface
 * @package AppBundle\Controller
 */
interface CountryAPIControllerInterface
{
  /**
   * @param Request $request
   * @return mixed
   */
  function getCountryCodes(Request $request);

  /**
   * @param Request $request
   * @return mixed
   */
  function getEUCountries(Request $request);
}