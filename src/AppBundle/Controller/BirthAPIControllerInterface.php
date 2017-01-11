<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

interface BirthAPIControllerInterface {
  public function getHistoryBirths(Request $request);
//  public function createFalseBirth(Request $request);
  public function createBirth(Request $request);
}