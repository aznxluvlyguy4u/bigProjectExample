<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

/**
 * Class TagsTransferAPIControllerInterface
 * @package AppBundle\Controller
 */
interface TagsTransferAPIControllerInterface
{
  /**
   * @param Request $request
   * @return mixed
   */
  public function createTagsTransfer(Request $request);

}