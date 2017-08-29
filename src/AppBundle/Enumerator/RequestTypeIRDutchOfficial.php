<?php


namespace AppBundle\Enumerator;


use AppBundle\Traits\EnumInfo;

class RequestTypeIRDutchOfficial
{
    use EnumInfo;

    const DeclarationDetail = 'Raadplegen Melding Detail';
    const DeclareAnimalFlag = 'Diervlagmelding';
    const DeclareArrival = 'Aanvoermelding';
    const DeclareBirth = 'Geboortemelding';
    const DeclareDepart = 'Afvoermelding';
    const DeclareTagsTransfer = 'Overdracht Merken';
    const DeclareTagReplace = 'Vervangende Merk Melding';
    const DeclareLoss = 'Sterftemelding';
    const DeclareExport = 'Exportmelding';
    const DeclareImport = 'Importmelding';
    const RetrieveTags = 'Raadplegen Merken';
    const RevokeDeclaration = 'Intrekking';
    const RetrieveAnimals = "Raadplegen Dieren";
    const RetrieveAnimalDetails = "Raadplegen Dier Details";
    const RetrieveCountries = "Raadplegen Landen";
    const RetrieveUbnDetails = "Raadplegen Meldingeendheden";

}