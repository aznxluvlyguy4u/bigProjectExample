<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class RetrieveEuropeanCountries
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RetrieveEuropeanCountriesRepository")
 * @package AppBundle\Entity
 */
class RetrieveEuropeanCountries extends DeclareBase {

}
