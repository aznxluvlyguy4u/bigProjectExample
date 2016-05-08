<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

interface BirthAPIControllerInterface {
  public function getBirthById(Request $request, $Id);
  public function getBirths(Request $request);
  public function createBirth(Request $request);
  public function updateBirth(Request $request, $Id);
}