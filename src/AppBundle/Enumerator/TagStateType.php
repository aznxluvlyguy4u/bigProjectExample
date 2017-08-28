<?php

namespace AppBundle\Enumerator;

/**
 * Class TagStateType
 * @package AppBundle\Enumerator
 */
class TagStateType {
  const ASSIGNED = "ASSIGNED";
  const ASSIGNING = "ASSIGNING";
  const UNASSIGNED = "UNASSIGNED";
  const TRANSFERRING_TO_NEW_OWNER = "TRANSFERRING";
  const TRANSFERRED_TO_NEW_OWNER = "TRANSFERRED";
  const REPLACING = "REPLACING";
  const REPLACED = "REPLACED";
  const RESERVED = "RESERVED";
}