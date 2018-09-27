<?php


namespace AppBundle\Worker\DirectProcessor;


use AppBundle\Entity\DeclareTagReplace;

interface DeclareTagReplaceProcessorInterface extends DeclareProcessorBaseInterface
{
    function process(DeclareTagReplace $tagReplace);
}