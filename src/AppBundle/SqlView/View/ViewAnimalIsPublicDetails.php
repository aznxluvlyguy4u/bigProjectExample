<?php


namespace AppBundle\SqlView\View;

use JMS\Serializer\Annotation as JMS;

class ViewAnimalIsPublicDetails implements SqlViewInterface
{
    /**
     * @var integer
     * @JMS\Type("integer")
     */
    private $animalId;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $uln;

    /**
     * @var bool
     * @JMS\Type("boolean")
     */
    private $isPublic;

    /**
     * ViewAnimalIsPublicDetails constructor.
     * @param  int  $animalId
     * @param  bool  $isPublic it should be true by default!
     */
    public function __construct(int $animalId, bool $isPublic = true)
    {
        $this->animalId = $animalId;
        $this->isPublic = $isPublic;
    }

    /**
     * @return string
     */
    static function getPrimaryKeyName()
    {
        return 'animal_id';
    }

    /**
     * @return int
     */
    public function getPrimaryKey()
    {
        return $this->getAnimalId();
    }

    /**
     * @return int
     */
    public function getAnimalId()
    {
        return $this->animalId;
    }

    /**
     * @return string
     */
    public function getUln(): string
    {
        return $this->uln;
    }

    /**
     * @param  string  $uln
     * @return ViewAnimalIsPublicDetails
     */
    public function setUln(string $uln): ViewAnimalIsPublicDetails
    {
        $this->uln = $uln;
        return $this;
    }

    /**
     * @param int $animalId
     * @return ViewAnimalIsPublicDetails
     */
    public function setAnimalId($animalId)
    {
        $this->animalId = $animalId;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->isPublic ?? false;
    }

    /**
     * @param bool $isPublic
     * @return ViewAnimalIsPublicDetails
     */
    public function setIsPublic(bool $isPublic): ViewAnimalIsPublicDetails
    {
        $this->isPublic = $isPublic;
        return $this;
    }

}
