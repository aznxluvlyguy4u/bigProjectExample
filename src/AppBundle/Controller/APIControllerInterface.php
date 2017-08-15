<?php

namespace AppBundle\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class APIControllerInterface
 * @package AppBundle\Controller
 */
interface APIControllerInterface {
  /**
   * @param Request $request
   * @return mixed
   */
  public function getAccountOwner(Request $request);

  /**
   * @param Request $request
   * @return mixed
   */
  public function isAccessTokenValid(Request $request);
}