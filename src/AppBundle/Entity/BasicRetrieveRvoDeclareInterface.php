<?php


namespace AppBundle\Entity;


interface BasicRetrieveRvoDeclareInterface
{
    /**
     * @return Location
     */
    function getLocation();
    function setLocation(Location $location);
}