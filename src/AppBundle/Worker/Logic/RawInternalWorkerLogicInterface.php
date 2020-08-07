<?php


namespace AppBundle\Worker\Logic;


interface RawInternalWorkerLogicInterface
{
    function process(string $rvoXmlResponseContent);
}