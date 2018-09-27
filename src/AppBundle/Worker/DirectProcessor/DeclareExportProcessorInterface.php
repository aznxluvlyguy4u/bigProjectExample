<?php


namespace AppBundle\Worker\DirectProcessor;


use AppBundle\Entity\DeclareExport;

interface DeclareExportProcessorInterface extends DeclareProcessorBaseInterface
{
    function process(DeclareExport $export);
}