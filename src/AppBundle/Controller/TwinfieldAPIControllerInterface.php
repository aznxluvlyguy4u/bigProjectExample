<?php


namespace AppBundle\Controller;


use Symfony\Component\HttpFoundation\Request;

interface TwinfieldAPIControllerInterface
{
    function getCustomers(Request $request);
}