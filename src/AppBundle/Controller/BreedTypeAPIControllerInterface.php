<?php


namespace AppBundle\Controller;


use Symfony\Component\HttpFoundation\Request;

interface BreedTypeAPIControllerInterface
{
    function getBreedTypes(Request $request);
}