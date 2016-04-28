<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

interface ArrivalAPIControllerInterface {
  public function getArrivalById(Request $request, $Id);
  public function getArrivals(Request $request);
  public function createArrival(Request $request);
  public function editArrival(Request $request, $Id);
}