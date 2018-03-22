<?php


namespace AppBundle\Service\Migration;


interface DatabaseContentInitializerInterface
{
    function initialize();
    function update();
}