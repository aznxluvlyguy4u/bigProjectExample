<?php


namespace AppBundle\Controller;


use Symfony\Component\HttpFoundation\Request;

interface ExternalProviderAPIControllerInterface
{
    function getOffices(Request $request);

    function getCustomers(Request $request, $office);
}