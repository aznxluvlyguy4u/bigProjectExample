<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class RetrieveEuropeanCountriesResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RetrieveEuropeanCountriesResponseRepository")
 * @package AppBundle\Entity
 */
class RetrieveEuropeanCountriesResponse extends DeclareBaseResponse
{

}
