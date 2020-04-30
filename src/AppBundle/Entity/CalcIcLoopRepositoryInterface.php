<?php

namespace AppBundle\Entity;

use Psr\Log\LoggerInterface;

/**
 * Class CalcIcLoopRepositoryInterface
 * @package AppBundle\Entity
 */
interface CalcIcLoopRepositoryInterface extends CalcTableRepositoryInterface {

    /**
     * @param  int  $animalIdOrigin1
     * @param  int  $animalIdOrigin2
     * @param  LoggerInterface|null  $logger
     */
    function fill(int $animalIdOrigin1, int $animalIdOrigin2, ?LoggerInterface $logger = null);

    /**
     * @return float
     */
    function calculateInbreedingCoefficientFromLoopsAndParentDetails(): float;

}
