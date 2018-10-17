<?php


namespace AppBundle\Worker\DirectProcessor;


use AppBundle\Entity\DeclareArrival;

interface DeclareArrivalProcessorInterface extends DeclareProcessorBaseInterface
{
    function process(DeclareArrival $arrival);
}