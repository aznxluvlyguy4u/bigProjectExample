<?php

namespace AppBundle\Entity;

use Psr\Log\LoggerInterface;

/**
 * Class CalcIcParentRepositoryInterface
 * @package AppBundle\Entity
 */
interface CalcIcParentRepositoryInterface extends CalcTableRepositoryInterface {

    /**
     * @param  int  $year
     * @param  int  $month
     * @param  LoggerInterface|null  $logger
     */
    function fillByYearAndMonth(int $year, int $month, ?LoggerInterface $logger = null);

    /**
     * @param  array  $parentIdsPairs
     * @param  LoggerInterface|null  $logger
     */
    function fillByParentPairs(array $parentIdsPairs, ?LoggerInterface $logger = null);

}
