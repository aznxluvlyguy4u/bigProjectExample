<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

interface HealthAPIControllerInterface {
  public function getHealthByLocation(Request $request, $ubn);
}