<?php


namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class LanguageOption
 * @ORM\Entity(repositoryClass="AppBundle\Entity\LanguageOptionRepository")
 * @package AppBundle\Entity
 */
class LanguageOption
{
    use EntityClassInfo;

    /**
     * @var integer
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Type("integer")
     * @JMS\Groups({
     * })
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", options={"default": "INCOMPLETE"})
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "BASIC",
     *     "DETAILS"
     * })
     */
    private $language;

    /**
     * @var Country
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Country")
     * @JMS\Type("AppBundle\Entity\Country")
     * @JMS\Groups({
     *     "BASIC",
     *     "DETAILS"
     * })
     */
    private $country;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @param string $language
     * @return LanguageOption
     */
    public function setLanguage(string $language): LanguageOption
    {
        $this->language = $language;
        return $this;
    }

    /**
     * @return Country
     */
    public function getCountry(): Country
    {
        return $this->country;
    }

    /**
     * @param Country $country
     * @return LanguageOption
     */
    public function setCountry(Country $country): LanguageOption
    {
        $this->country = $country;
        return $this;
    }


}