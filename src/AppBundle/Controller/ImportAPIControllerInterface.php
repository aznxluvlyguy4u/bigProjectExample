<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

interface ImportAPIControllerInterface {
  public function getImportById(Request $request, $Id);
  public function getImports(Request $request);
}