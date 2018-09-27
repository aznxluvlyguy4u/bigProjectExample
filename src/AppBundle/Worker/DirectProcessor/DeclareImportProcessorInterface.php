<?php


namespace AppBundle\Worker\DirectProcessor;


use AppBundle\Entity\DeclareImport;

interface DeclareImportProcessorInterface extends DeclareProcessorBaseInterface
{
    function process(DeclareImport $import);
}