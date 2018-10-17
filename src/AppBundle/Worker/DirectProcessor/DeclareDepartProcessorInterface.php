<?php


namespace AppBundle\Worker\DirectProcessor;


use AppBundle\Entity\DeclareDepart;

interface DeclareDepartProcessorInterface extends DeclareProcessorBaseInterface
{
    function process(DeclareDepart $depart);
}