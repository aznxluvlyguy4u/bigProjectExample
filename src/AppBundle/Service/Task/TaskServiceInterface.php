<?php


namespace AppBundle\Service\Task;


interface TaskServiceInterface
{
    function start(bool $recalculate = false);
    function run();
    function cancel();
}