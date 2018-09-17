<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Tag;
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

    /**
     * @param Request $request
     * @return mixed
     */
  public function createTags(Request $request);

    /**
     * @param Request $request
     * @param Tag $tag
     * @return mixed
     */
  public function deleteTag(Request $request, Tag $tag);
}