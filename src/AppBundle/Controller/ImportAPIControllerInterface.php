<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

interface ImportAPIControllerInterface {
  public function getImportById(Request $request, $Id);
  public function getImportByState(Request $request);
  public function createImport(Request $request);
  public function editImport(Request $request, $Id);
}