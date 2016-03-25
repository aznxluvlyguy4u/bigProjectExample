<?php

namespace AppBundle\Entity;

/**
 * Class BaseEntity
 * @package AppBundle\Entity
 */
class BaseEntity
{
  public function getClassName()
  {
    $className = (new \ReflectionClass($this))->getShortName();

    return $className;
  }

  public function getFullyQualifiedClassName()
  {
    $fullyQualifiedClassName = get_class($this);

    return $fullyQualifiedClassName;
  }
}