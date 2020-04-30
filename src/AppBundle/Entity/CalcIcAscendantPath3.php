<?php


namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;

/**
 * This calc table is only used for storing temporary data for calculations.
 * So no Java Hibernate class is necessary, if no relations to normal tables are used.
 * Quick writes are necessary, so no index is used.
 *
 * Class CalcIcAscendantPath3
 * @ORM\Table(name="calc_ic_ascendant_path3")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\CalcIcAscendantPath3Repository")
 * @package AppBundle\Entity
 */
class CalcIcAscendantPath3
{
    use EntityClassInfo;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=false)
     */
    private $animalId;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=false)
     */
    private $lastParentId;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=false)
     */
    private $depth;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=false)
     */
    private $path;

    /**
     * @var array
     * @ORM\Column(type="simple_array", nullable=false)
     */
    private $parents;


    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param  int  $id
     * @return CalcIcAscendantPath
     */
    public function setId(int $id): CalcIcAscendantPath
    {
        $this->id = $id;
        return $this;
    }


    /**
     * @return int
     */
    public function getAnimalId(): int
    {
        return $this->animalId;
    }

    /**
     * @param  int  $animalId
     * @return CalcIcAscendantPath
     */
    public function setAnimalId(int $animalId): CalcIcAscendantPath
    {
        $this->animalId = $animalId;
        return $this;
    }

    /**
     * @return int
     */
    public function getLastParentId(): int
    {
        return $this->lastParentId;
    }

    /**
     * @param  int  $lastParentId
     * @return CalcIcAscendantPath
     */
    public function setLastParentId(int $lastParentId): CalcIcAscendantPath
    {
        $this->lastParentId = $lastParentId;
        return $this;
    }

    /**
     * @return int
     */
    public function getDepth(): int
    {
        return $this->depth;
    }

    /**
     * @param  int  $depth
     * @return CalcIcAscendantPath
     */
    public function setDepth(int $depth): CalcIcAscendantPath
    {
        $this->depth = $depth;
        return $this;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param  string  $path
     * @return CalcIcAscendantPath
     */
    public function setPath(string $path): CalcIcAscendantPath
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return array
     */
    public function getParents(): array
    {
        return $this->parents;
    }

    /**
     * @param  array  $parents
     * @return CalcIcAscendantPath
     */
    public function setParents(array $parents): CalcIcAscendantPath
    {
        $this->parents = $parents;
        return $this;
    }



}
