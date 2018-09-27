<?php


namespace AppBundle\Worker\DirectProcessor;


use AppBundle\Entity\DeclareLoss;

interface DeclareLossProcessorInterface extends DeclareProcessorBaseInterface
{
    function process(DeclareLoss $loss);
}