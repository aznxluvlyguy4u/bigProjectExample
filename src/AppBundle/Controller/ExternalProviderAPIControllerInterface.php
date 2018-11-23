<?php


namespace AppBundle\Controller;


interface ExternalProviderAPIControllerInterface
{
    function getOffices();

    function getCustomers($office);
}