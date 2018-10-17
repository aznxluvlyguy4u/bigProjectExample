<?php


namespace AppBundle\Worker\DirectProcessor;


use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\DeclareTagReplace;
use AppBundle\Entity\Person;

interface RevokeProcessorInterface extends DeclareProcessorBaseInterface
{
    function revokeArrival(DeclareArrival $arrival, Client $client, Person $actionBy);
    function revokeExport(DeclareExport $export, Client $client, Person $actionBy);
    function revokeDepart(DeclareDepart $depart, Client $client, Person $actionBy);
    function revokeImport(DeclareImport $import, Client $client, Person $actionBy);
    function revokeLoss(DeclareLoss $loss, Client $client, Person $actionBy);
    function revokeTagReplace(DeclareTagReplace $tagReplace, Client $client, Person $actionBy);
}