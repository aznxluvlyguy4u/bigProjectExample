<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

interface BirthAPIControllerInterface {
  public function getHistoryBirths(Request $request);
  public function createBirth(Request $request);
  public function getBirth(Request $request, $messageNumber);
}