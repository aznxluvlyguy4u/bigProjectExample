<?php

namespace AppBundle\Service;


use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\DeclareTagReplace;
use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use AppBundle\Entity\RetrieveAnimalDetails;
use AppBundle\Entity\RetrieveAnimals;
use AppBundle\Entity\RetrieveCountries;
use AppBundle\Entity\RetrieveTags;
use AppBundle\Entity\RetrieveUbnDetails;
use AppBundle\Entity\RevokeDeclaration;
use Doctrine\Common\Collections\ArrayCollection;

interface IRSerializerInterface
{

    /**
     * @param ArrayCollection $contentArray
     * @param Client $client
     * @param Location $location
     * @param boolean $isEditMessage
     * @return DeclareArrival
     */
    function parseDeclareArrival(ArrayCollection $contentArray, Client $client, Location $location, $isEditMessage);

    /**
     * @param ArrayCollection $declareBirthContentArray
     * @param Client $client
     * @param Person $loggedInUser
     * @param Location $location
     * @param boolean $isEditMessage
     * @return DeclareBirth
     */
    function parseDeclareBirth(ArrayCollection $declareBirthContentArray, Client $client, Person $loggedInUser, Location $location, $isEditMessage);

    /**
     * @param ArrayCollection $contentArray
     * @param Client $client
     * @param Location $location
     * @param boolean $isEditMessage
     * @return DeclareDepart
     */
    function parseDeclareDepart(ArrayCollection $contentArray, Client $client, Location $location, $isEditMessage);

    /**
     * @param ArrayCollection $contentArray
     * @param Client $client
     * @param Location $location
     * @param boolean $isEditMessage
     * @return DeclareTagsTransfer
     */
    function parseDeclareTagsTransfer(ArrayCollection $contentArray, Client $client, Location $location, $isEditMessage);

    /**
     * @param ArrayCollection $contentArray
     * @param Client $client
     * @param Location $location
     * @param boolean $isEditMessage
     * @return DeclareTagReplace
     */
    function parseDeclareTagReplace(ArrayCollection $contentArray, Client $client, Location $location, $isEditMessage);

    /**
     * @param ArrayCollection $contentArray
     * @param Client $client
     * @param Location $location
     * @param boolean $isEditMessage
     * @return DeclareLoss
     */
    function parseDeclareLoss(ArrayCollection $contentArray, Client $client, Location $location, $isEditMessage);

    /**
     * @param ArrayCollection $contentArray
     * @param Client $client
     * @param Location $location
     * @param boolean $isEditMessage
     * @return DeclareExport
     */
    function parseDeclareExport(ArrayCollection $contentArray, Client $client, Location $location, $isEditMessage);

    /**
     * @param ArrayCollection $contentArray
     * @param Client $client
     * @param Location $location
     * @param boolean $isEditMessage
     * @return DeclareImport
     */
    function parseDeclareImport(ArrayCollection $contentArray, Client $client, $location, $isEditMessage);

    /**
     * @param ArrayCollection $contentArray
     * @param Client $client
     * @param boolean $isEditMessage
     * @return RetrieveTags
     */
    function parseRetrieveTags(ArrayCollection $contentArray, Client $client, $isEditMessage);

    /**
     * @param ArrayCollection $contentArray
     * @param Client $client
     * @param boolean $isEditMessage
     * @return RevokeDeclaration
     */
    function parseRevokeDeclaration(ArrayCollection $contentArray, Client $client, $isEditMessage);

    /**
     * @param ArrayCollection $contentArray
     * @param Client $client
     * @param boolean $isEditMessage
     * @return RetrieveAnimals
     */
    function parseRetrieveAnimals(ArrayCollection $contentArray, Client $client, $isEditMessage);

    /**
     * @param ArrayCollection $contentArray
     * @param Client $client
     * @param boolean $isEditMessage
     * @return RetrieveAnimalDetails
     */
    function parseRetrieveAnimalDetails(ArrayCollection $contentArray, Client $client, $isEditMessage);

    /**
     * @param ArrayCollection $contentArray
     * @param Client $client
     * @param boolean $isEditMessage
     * @return RetrieveCountries
     */
    function parseRetrieveEUCountries(ArrayCollection $contentArray, Client $client, $isEditMessage);

    /**
     * @param ArrayCollection $contentArray
     * @param Client $client
     * @param boolean $isEditMessage
     * @return RetrieveUBNDetails
     */
    function parseRetrieveUbnDetails(ArrayCollection $contentArray, Client $client, $isEditMessage);

}