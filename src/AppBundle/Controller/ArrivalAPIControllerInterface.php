<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

interface ArrivalAPIControllerInterface {
  public function getArrivalById(Request $request, $Id);
  public function getArrivals(Request $request);
  public function createArrival(Request $request);
  public function updateArrival(Request $request, $Id);
  public function getArrivalErrors(Request $request);
  public function getArrivalHistory(Request $request);
}