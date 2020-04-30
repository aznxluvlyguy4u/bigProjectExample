<?php

namespace AppBundle\Entity;

use Psr\Log\LoggerInterface;

/**
 * Class CalcIcParentDetailsRepositoryInterface
 * @package AppBundle\Entity
 */
interface CalcIcParentDetailsRepositoryInterface extends CalcTableRepositoryInterface {

    function fill(?LoggerInterface $logger = null);

}
