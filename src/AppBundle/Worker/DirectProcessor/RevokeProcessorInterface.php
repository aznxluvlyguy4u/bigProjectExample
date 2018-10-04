<?php


namespace AppBundle\Worker\DirectProcessor;


use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\DeclareTagReplace;

interface RevokeProcessorInterface extends DeclareProcessorBaseInterface
{
    function revokeArrival(DeclareArrival $arrival);
    function revokeExport(DeclareExport $export);
    function revokeDepart(DeclareDepart $depart);
    function revokeImport(DeclareImport $import);
    function revokeLoss(DeclareLoss $loss);
    function revokeTagReplace(DeclareTagReplace $tagReplace);
}