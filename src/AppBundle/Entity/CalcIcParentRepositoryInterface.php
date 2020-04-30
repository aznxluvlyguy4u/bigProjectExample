<?php

namespace AppBundle\Entity;

use Psr\Log\LoggerInterface;

/**
 * Class CalcIcParentRepositoryInterface
 * @package AppBundle\Entity
 */
interface CalcIcParentRepositoryInterface extends CalcTableRepositoryInterface {

    /**
     * @param  array  $parentIdsPairs
     * @param  LoggerInterface|null  $logger
     */
    function fillByParentPairs(array $parentIdsPairs, ?LoggerInterface $logger = null);

}
