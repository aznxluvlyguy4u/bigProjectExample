<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

interface HideErrorAPIControllerInterface {
  public function updateError(Request $request);
}