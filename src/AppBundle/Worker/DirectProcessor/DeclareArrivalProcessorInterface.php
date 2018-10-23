<?php


namespace AppBundle\Worker\DirectProcessor;


use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\Location;

interface DeclareArrivalProcessorInterface extends DeclareProcessorBaseInterface
{
    function process(DeclareArrival $arrival, Location $origin);
}