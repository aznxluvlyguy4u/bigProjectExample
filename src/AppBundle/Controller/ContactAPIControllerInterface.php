<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

interface ContactAPIControllerInterface {
  public function postContactEmail(Request $request);
}