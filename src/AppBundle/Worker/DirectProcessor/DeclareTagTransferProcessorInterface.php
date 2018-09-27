<?php


namespace AppBundle\Worker\DirectProcessor;


use AppBundle\Entity\DeclareTagsTransfer;

interface DeclareTagTransferProcessorInterface extends DeclareProcessorBaseInterface
{
    function process(DeclareTagsTransfer $tagsTransfer);
}