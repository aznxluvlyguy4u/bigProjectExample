<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

interface ExportAPIControllerInterface {
  public function getExportById(Request $request, $Id);
  public function getExports(Request $request);
}