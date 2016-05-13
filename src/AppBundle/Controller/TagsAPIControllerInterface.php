<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface TagsAPIControllerInterface
 * @package AppBundle\Controller
 */
interface TagsAPIControllerInterface {
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

  /**
   * @param Request $request
   * @return mixed
   */
  public function createTagTransfer(Request $request, $tagId, $ubnId);

  /**
   * @param Request $request
   * @return mixed
   */
  public function createTagsTransfer(Request $request);

  /**
   * @param Request $request
   * @return mixed
   */
  public function getTagsTransferById(Request $request, $Id);

  /**
   * @param Request $request
   * @return mixed
   */
  public function getTagsTransfers(Request $request);

  /**
   * @param Request $request
   * @return mixed
   */
  public function updateTagsTransfer(Request $request, $Id);
}