<?php

namespace AppBundle\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class APIControllerInterface
 * @package AppBundle\Controller
 */
interface APIControllerInterface {
  public function getAuthenticatedUser(Request $request);
}