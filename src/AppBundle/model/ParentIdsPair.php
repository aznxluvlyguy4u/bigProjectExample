<?php


namespace AppBundle\model;


class ParentIdsPair
{
    /** @var int */
    private $ramId;

    /** @var int */
    private $eweId;

    /**
     * ParentIdsPair constructor.
     * @param int $ramId
     * @param int $eweId
     */
    public function __construct(int $ramId, int $eweId)
    {
        $this->ramId = $ramId;
        $this->eweId = $eweId;
    }


    /**
     * @return int
     */
    public function getRamId(): int
    {
        return $this->ramId;
    }

    /**
     * @param int $ramId
     * @return ParentIdsPair
     */
    public function setRamId(int $ramId): ParentIdsPair
    {
        $this->ramId = $ramId;
        return $this;
    }

    /**
     * @return int
     */
    public function getEweId(): int
    {
        return $this->eweId;
    }

    /**
     * @param int $eweId
     * @return ParentIdsPair
     */
    public function setEweId(int $eweId): ParentIdsPair
    {
        $this->eweId = $eweId;
        return $this;
    }


}