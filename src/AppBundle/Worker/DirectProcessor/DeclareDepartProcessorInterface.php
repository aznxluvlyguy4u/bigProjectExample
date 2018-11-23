<?php


namespace AppBundle\Worker\DirectProcessor;


use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\Location;

interface DeclareDepartProcessorInterface extends DeclareProcessorBaseInterface
{
    function process(DeclareDepart $depart, Location $destination);
}