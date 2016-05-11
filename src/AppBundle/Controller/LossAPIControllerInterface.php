<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

interface LossAPIControllerInterface {
  public function getLossById(Request $request, $Id);
  public function getLosses(Request $request);
  public function createLoss(Request $request);
  public function editLoss(Request $request, $Id);
}