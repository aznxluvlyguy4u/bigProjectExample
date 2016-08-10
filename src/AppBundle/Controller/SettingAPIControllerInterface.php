<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface SettingAPIControllerInterface
 * @package AppBundle\Controller
 */
interface SettingAPIControllerInterface
{
  public function editReasonsOfLoss(Request $request);
  public function editReasonsOfDepart(Request $request);
  public function editTreatmentOptions(Request $request);
  public function editContactFormOptions(Request $request);
}