<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

interface RevokeAPIControllerInterface {
  public function createRevoke(Request $request);
}