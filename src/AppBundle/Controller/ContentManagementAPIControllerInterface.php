<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface ContentManagementAPIControllerInterface
 * @package AppBundle\Controller
 */
interface ContentManagementAPIControllerInterface
{
  public function getContentManagement(Request $request);
  public function editContentManagement(Request $request);
}