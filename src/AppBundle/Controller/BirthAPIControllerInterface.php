<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

interface BirthAPIControllerInterface {
  public function getHistoryBirths(Request $request);
  public function createBirth(Request $request);
  public function getBirth(Request $request, $messageNumber);
  public function getCandidateFathers(Request $request, $ulnMother);
  public function getCandidateSurrogates(Request $request, $ulnMother);
}