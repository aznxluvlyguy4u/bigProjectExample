<?php


namespace AppBundle\Entity;


interface DeclareBaseRepositoryInterface
{
    function getErrorDetails($messageId);
}