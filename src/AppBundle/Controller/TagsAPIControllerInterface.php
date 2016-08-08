<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface TagsAPIControllerInterface
 * @package AppBundle\Controller
 */
interface TagsAPIControllerInterface
{
  /**
   * @param Request $request
   * @param $Id
   * @return mixed
   */
  public function getTagById(Request $request, $Id);

  /**
   * @param Request $request
   * @return mixed
   */
  public function getTags(Request $request);
}