<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface TagsSyncAPIControllerInterface
 * @package AppBundle\Controller
 */
interface TagsSyncAPIControllerInterface
{
  /***
   * @param Request $request
   * @return mixed
   */
  public function createRetrieveTags(Request $request);

  /**
   * @param Request $request
   * @return mixed
   */
  public function getRetrieveTagsById(Request $request, $Id);

  /**
   * @param Request $request
   * @return mixed
   */
  public function getRetrieveTags(Request $request);

}