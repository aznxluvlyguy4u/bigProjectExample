<?php


namespace AppBundle\Worker\Logic;


interface InternalWorkerLogicInterface
{
    function process(string $rvoXmlResponseContent);
}