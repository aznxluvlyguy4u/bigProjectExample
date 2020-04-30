<?php


namespace AppBundle\model\report;


class InbreedingCoefficientInput
{
    /** @var array */
    private $ramsData;

    /** @var array */
    private $ewesData;

    /**
     * InbreedingCoefficientInput constructor.
     * @param  array  $ramsData
     * @param  array  $ewesData
     */
    public function __construct(array $ramsData, array $ewesData)
    {
        $this->ramsData = $ramsData;
        $this->ewesData = $ewesData;
    }

    /**
     * @return array
     */
    public function getRamsData(): array
    {
        return $this->ramsData;
    }

    /**
     * @return array
     */
    public function getEwesData(): array
    {
        return $this->ewesData;
    }

    /**
     * @return array|int[]
     */
    public function getRamIds(): array
    {
        return $this->mapIdsFromAnimalData($this->getRamsData());
    }

    /**
     * @return array|int[]
     */
    public function getEweIds(): array
    {
        return $this->mapIdsFromAnimalData($this->getEwesData());
    }

    /**
     * @param  array  $parentData
     * @return array|int[]
     */
    private function mapIdsFromAnimalData(array $parentData): array
    {
        return array_map(
            function (array $array) {
                return $array['id'];
            },
            $parentData
        );
    }
}
