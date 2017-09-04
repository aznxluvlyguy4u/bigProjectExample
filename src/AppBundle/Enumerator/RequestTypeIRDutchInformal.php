<?php


namespace AppBundle\Enumerator;


use AppBundle\Traits\EnumInfo;

class RequestTypeIRDutchInformal
{
    use EnumInfo;

    const DeclarationDetail = 'Melding Detail';
    const DeclareAnimalFlag = 'Diervlag';
    const DeclareArrival = 'Aanvoer';
    const DeclareBirth = 'Geboorte';
    const DeclareDepart = 'Afvoer';
    const DeclareTagsTransfer = 'Merken Overdracht';
    const DeclareTagReplace = 'Omnummering';
    const DeclareLoss = 'Sterfte';
    const DeclareExport = 'Export';
    const DeclareImport = 'Import';
    const RetrieveTags = 'MerkenSync';
    const RevokeDeclaration = 'Intrekking';
    const RetrieveAnimals = "Dieren";
    const RetrieveAnimalDetails = "DierDetails";
    const RetrieveCountries = "Landen info";
    const RetrieveUbnDetails = "UBN info";

}