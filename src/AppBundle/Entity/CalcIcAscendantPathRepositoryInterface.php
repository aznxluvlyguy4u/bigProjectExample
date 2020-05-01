<?php

namespace AppBundle\Entity;

use Psr\Log\LoggerInterface;

/**
 * Class CalcIcAscendantPathRepositoryInterface
 * @package AppBundle\Entity
 */
interface CalcIcAscendantPathRepositoryInterface extends CalcTableRepositoryInterface {

    /**
     * @param  LoggerInterface|null  $logger
     */
    function fill(?LoggerInterface $logger = null);
}
