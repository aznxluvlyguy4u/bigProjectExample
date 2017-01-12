<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface CollarAPIControllerInterface
 * @package AppBundle\Controller
 */
interface CollarAPIControllerInterface 
{
  /**
   * @param Request $request
   * @return mixed
   */
  function getCollarCodes(Request $request);
}