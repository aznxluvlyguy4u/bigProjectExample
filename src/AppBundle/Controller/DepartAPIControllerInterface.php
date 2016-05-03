<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

interface DepartAPIControllerInterface {
  public function getDepartById(Request $request, $Id);
  public function getDepartures(Request $request);
  public function createDepart(Request $request);
  public function editDepart(Request $request, $Id);
}