<?php


namespace AppBundle\Controller;


use Symfony\Component\HttpFoundation\Request;

interface ExternalProviderAPIControllerInterface
{
    function getOffices();

    function getCustomers($office);
}