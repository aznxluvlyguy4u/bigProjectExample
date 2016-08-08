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
   * @param $tokenCode
   * @return mixed
   */
  public function getAuthenticatedUser(Request $request, $tokenCode);

  /**
   * @param Request $request
   * @return mixed
   */
  public function isAccessTokenValid(Request $request);
}