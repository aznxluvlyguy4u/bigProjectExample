<?php


namespace AppBundle\Entity;


interface BasicRvoDeclareInterface extends DeclareBaseInterface
{
    function getLocation();
    function setLocation(Location $location);
    function getRevoke();
    function setRevoke(RevokeDeclaration $revoke);
}