<?php


namespace AppBundle\Entity;


interface BasicRvoDeclareInterface extends DeclareBaseInterface
{
    /**
     * @return Location
     */
    function getLocation();
    function setLocation(Location $location);

    /**
     * @return RevokeDeclaration
     */
    function getRevoke();
    function setRevoke(RevokeDeclaration $revoke);

    /**
     * @return \DateTime
     */
    function getEventDate();
}