<?php

namespace AppBundle\Enumerator;


use AppBundle\Component\Utils;

/**
 * Class RequestType
 * @package AppBundle\Enumerator
 */
class RequestType
{
    //RequestType
    const DECLARATION_DETAIL = 'DECLARATION_DETAIL';
    const DECLARE_ARRIVAL = 'DECLARE_ARRIVAL';
    const DECLARE_BIRTH = 'DECLARE_BIRTH';
    const DECLARE_ANIMAL_FLAG = 'DECLARE_ANIMAL_FLAG';
    const DECLARE_DEPART = 'DECLARE_DEPART';
    const DECLARE_TAGS_TRANSFER = 'DECLARE_TAGS_TRANSFER';
    const DECLARE_TAG_REPLACE = 'DECLARE_TAG_REPLACE';
    const DECLARE_LOSS = 'DECLARE_LOSS';
    const DECLARE_EXPORT = 'DECLARE_EXPORT';
    const DECLARE_IMPORT = 'DECLARE_IMPORT';
    const RETRIEVE_TAGS = 'RETRIEVE_TAGS';
    const REVOKE_DECLARATION = 'REVOKE_DECLARATION';
    const RETRIEVE_ANIMALS = "RETRIEVE_ANIMALS";
    const RETRIEVE_ANIMAL_DETAILS = "RETRIEVE_ANIMAL_DETAILS";
    const RETRIEVE_COUNTRIES = "RETRIEVE_COUNTRIES";
    const RETRIEVE_UBN_DETAILS = "RETRIEVE_UBN_DETAILS";

    //Request entity namespaces
    const DECLARATION_DETAIL_ENTITY = 'DeclarationDetail';
    const DECLARE_ANIMAL_FLAG_ENTITY = 'DeclareAnimalFlag';
    const DECLARE_ARRIVAL_ENTITY = 'DeclareArrival';
    const DECLARE_BIRTH_ENTITY = 'DeclareBirth';
    const DECLARE_DEPART_ENTITY = 'DeclareDepart';
    const DECLARE_TAGS_TRANSFER_ENTITY = 'DeclareTagsTransfer';
    const DECLARE_TAG_REPLACE_ENTITY = 'DeclareTagReplace';
    const DECLARE_LOSS_ENTITY = 'DeclareLoss';
    const DECLARE_EXPORT_ENTITY = 'DeclareExport';
    const DECLARE_IMPORT_ENTITY = 'DeclareImport';
    const RETRIEVE_TAGS_ENTITY = 'RetrieveTags';
    const REVOKE_DECLARATION_ENTITY = 'RevokeDeclaration';
    const RETRIEVE_ANIMALS_ENTITY = "RetrieveAnimals";
    const RETRIEVE_ANIMAL_DETAILS_ENTITY = "RetrieveAnimalDetails";
    const RETRIEVE_COUNTRIES_ENTITY = "RetrieveCountries";
    const RETRIEVE_UBN_DETAILS_ENTITY = "RetrieveUbnDetails";

    //Response entity namespaces
    const DECLARE_ARRIVAL_RESPONSE_ENTITY = 'DeclareArrivalResponse';


    public static function getRequestTypeFromEntityNameSpace($entityNameSpace)
    {
        switch($entityNameSpace) {
            case RequestType::DECLARATION_DETAIL_ENTITY:
                return RequestType::DECLARATION_DETAIL;

            case RequestType::DECLARE_ANIMAL_FLAG_ENTITY:
                return RequestType::DECLARE_ANIMAL_FLAG;

            case RequestType::DECLARE_ARRIVAL_ENTITY:
                return RequestType::DECLARE_ARRIVAL;

            case RequestType::DECLARE_BIRTH_ENTITY:
                return RequestType::DECLARE_BIRTH;

            case RequestType::DECLARE_DEPART_ENTITY:
                return RequestType::DECLARE_DEPART;

            case RequestType::DECLARE_TAGS_TRANSFER_ENTITY:
                return RequestType::DECLARE_TAGS_TRANSFER;

            case RequestType::DECLARE_TAG_REPLACE_ENTITY:
                return RequestType::DECLARE_TAG_REPLACE;

            case RequestType::DECLARE_LOSS_ENTITY:
                return RequestType::DECLARE_LOSS;

            case RequestType::DECLARE_EXPORT_ENTITY:
                return RequestType::DECLARE_EXPORT;

            case RequestType::DECLARE_IMPORT_ENTITY:
                return RequestType::DECLARE_IMPORT;

            case RequestType::RETRIEVE_TAGS_ENTITY:
                return RequestType::RETRIEVE_TAGS;

            case RequestType::REVOKE_DECLARATION_ENTITY:
                return RequestType::REVOKE_DECLARATION;

            case RequestType::RETRIEVE_ANIMAL_DETAILS_ENTITY:
                return RequestType::RETRIEVE_ANIMAL_DETAILS;

            case RequestType::RETRIEVE_ANIMALS_ENTITY:
                return RequestType::RETRIEVE_ANIMALS;

            case RequestType::RETRIEVE_COUNTRIES_ENTITY:
                return RequestType::RETRIEVE_COUNTRIES;

            case RequestType::RETRIEVE_UBN_DETAILS_ENTITY:
                return RequestType::RETRIEVE_UBN_DETAILS;

            default:
                return null;
        }
    }

    public static function getRequestTypeFromObject($object)
    {
        return RequestType::getRequestTypeFromEntityNameSpace(Utils::getClassName($object));
    }
}