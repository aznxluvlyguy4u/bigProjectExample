<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class DeclarationBase
 * @ORM\Table(name="declaration_base")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclarationBaseRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap(
 *   {
 *      "DeclarationDetail" = "DeclarationDetail"
 *   }
 * )
 * @package AppBundle\Entity\DeclareBase
 */
abstract class DeclarationBase {

  /**
   * @ORM\Column(type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  protected $id;

  /**
   * @ORM\Column(type="datetime")
   * @Assert\Date
   * @Assert\NotBlank
   * @JMS\Type("DateTime")
   */
  protected $logDate;

  /**
   * @ORM\Column(type="string")
   * @Assert\Length(max = 20)
   * @Assert\NotBlank
   * @JMS\Type("string")
   */
  protected $requestId;

  /**
   * @ORM\Column(type="string")
   * @Assert\Length(max = 20)
   * @Assert\NotBlank
   * @JMS\Type("string")
   */
  protected $messageId;

  /**
   * @ORM\Column(type="string")
   * @Assert\NotBlank
   * @JMS\Type("string")
   */
  protected $requestState;

  /**
   * @ORM\Column(type="string")
   * @Assert\Length(max = 20)
   * @Assert\NotBlank
   * @JMS\Type("string")
   */
  protected $relationNumberKeeper;

  /**
   * @var string
   *
   * @ORM\Column(type="string")
   * @Assert\NotBlank
   * @Assert\Length(max = 12)
   * @JMS\Type("string")
   */
  protected $ubn;
}