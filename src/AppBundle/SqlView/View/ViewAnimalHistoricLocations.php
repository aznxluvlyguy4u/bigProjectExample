<?php


namespace AppBundle\SqlView\View;

use JMS\Serializer\Annotation as JMS;

class ViewAnimalHistoricLocations implements SqlViewInterface
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
     * Array as json string
     * @var string
     * @JMS\Type("string")
     */
    private $historicUbns;

    /**
     * Array as json string
     * @var string
     * @JMS\Type("string")
     */
    private $historicLocationIds;

    /**
     * ViewAnimalHistoricLocations constructor.
     * @param  int  $animalId
     * @param  string  $historicUbns
     * @param  string  $historicLocationIds
     */
    public function __construct(int $animalId, string $historicUbns = '[]', string $historicLocationIds = '[]')
    {
        $this->animalId = $animalId;
        $this->historicUbns = $historicUbns;
        $this->historicLocationIds = $historicLocationIds;
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
     * @param int $animalId
     * @return ViewAnimalHistoricLocations
     */
    public function setAnimalId($animalId)
    {
        $this->animalId = $animalId;
        return $this;
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
     * @return ViewAnimalHistoricLocations
     */
    public function setUln(string $uln): ViewAnimalHistoricLocations
    {
        $this->uln = $uln;
        return $this;
    }

    /**
     * @return array
     */
    public function getHistoricUbnsAsArray(): array
    {
        if (!empty($this->historicUbns)) {
            $ubns = json_decode($this->historicUbns);
            if (is_array($ubns)) {
                return $ubns;
            }
        }
        return [];
    }

    /**
     * @return string|null
     */
    public function getHistoricUbns()
    {
        return $this->historicUbns;
    }

    /**
     * @param string $historicUbns
     * @return ViewAnimalHistoricLocations
     */
    public function setHistoricUbns(string $historicUbns): ViewAnimalHistoricLocations
    {
        $this->historicUbns = $historicUbns;
        return $this;
    }

    /**
     * @return array
     */
    public function getHistoricLocationIdsAsArray(): array
    {
        return empty($this->historicLocationIds) ? [] : json_decode($this->historicLocationIds,false);
    }

    /**
     * @return string|null
     */
    public function getHistoricLocationIds()
    {
        return $this->historicLocationIds;
    }

    /**
     * @param string $historicLocationIds
     * @return ViewAnimalHistoricLocations
     */
    public function setHistoricLocationIds(string $historicLocationIds): ViewAnimalHistoricLocations
    {
        $this->historicLocationIds = $historicLocationIds;
        return $this;
    }

}
