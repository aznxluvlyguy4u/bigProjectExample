<?php
/**
 * Created by IntelliJ IDEA.
 * User: c0d3
 * Date: 13/05/16
 * Time: 21:13
 */

namespace AppBundle\Enumerator;

/**
 * Class TagStateType
 * @package AppBundle\Enumerator
 */
class TagStateType {
  const ASSIGNED = "assigned";
  const UNASSIGNED = "unassigned";
  const TRANSFERRING_TO_NEW_OWNER = "transferring";
  const TRANSFERRED_TO_NEW_OWNER = "transferred";
}