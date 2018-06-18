<?php


namespace AppBundle\Controller;


use Symfony\Component\HttpFoundation\Request;

interface TwinfieldAPIControllerInterface
{
    function getOffices(Request $request);

    function getCustomers(Request $request, $office);
}