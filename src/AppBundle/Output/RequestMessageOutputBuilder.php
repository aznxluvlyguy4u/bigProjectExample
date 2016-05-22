<?php

namespace AppBundle\Output;


use AppBundle\Component\Utils;
use AppBundle\Enumerator\RequestType;

class RequestMessageOutputBuilder
{
    public static function createOutputArray($messageObject, $isUpdate = false)
    {
        $entityNameSpace = Utils::getClassName($messageObject);

        switch($entityNameSpace) {
            case RequestType::DECLARATION_DETAIL_ENTITY:
                //TODO
                if($isUpdate) {
                    return null; //update Array
                } else {
                    return null; //post Array
                }

            case RequestType::DECLARE_ANIMAL_FLAG_ENTITY:
                //TODO
                if($isUpdate) {
                    return null; //update Array
                } else {
                    return null; //post Array
                }

            case RequestType::DECLARE_ARRIVAL_ENTITY:
                if($isUpdate) {
                    return DeclareArrivalOutput::createUpdateRequestArray($messageObject);
                } else {
                    return DeclareArrivalOutput::createPostRequestArray($messageObject);
                }

            case RequestType::DECLARE_BIRTH_ENTITY:
                //TODO
                if($isUpdate) {
                    return null; //update Array
                } else {
                    return null; //post Array
                }

            case RequestType::DECLARE_DEPART_ENTITY:
                if($isUpdate) {
                    return DeclareDepartOutput::createUpdateRequestArray($messageObject);
                } else {
                    return DeclareDepartOutput::createPostRequestArray($messageObject);
                }

            case RequestType::DECLARE_TAGS_TRANSFER_ENTITY:
                //TODO
                if($isUpdate) {
                    return null; //update Array
                } else {
                    return null; //post Array
                }

            case RequestType::DECLARE_LOSS_ENTITY:
                //TODO
                if($isUpdate) {
                    return null; //update Array
                } else {
                    return null; //post Array
                }

            case RequestType::DECLARE_EXPORT_ENTITY:
                if($isUpdate) {
                    return DeclareExportOutput::createUpdateRequestArray($messageObject);
                } else {
                    return DeclareExportOutput::createPostRequestArray($messageObject);
                }

            case RequestType::DECLARE_IMPORT_ENTITY:
                if($isUpdate) {
                    return DeclareImportOutput::createUpdateRequestArray($messageObject);
                } else {
                    return DeclareImportOutput::createPostRequestArray($messageObject);
                }

            case RequestType::RETRIEVE_TAGS_ENTITY:
                //TODO
                if($isUpdate) {
                    return null; //update Array
                } else {
                    return null; //post Array
                }

            case RequestType::REVOKE_DECLARATION_ENTITY:
                //TODO
                if($isUpdate) {
                    return null; //update Array
                } else {
                    return null; //post Array
                }

            case RequestType::RETRIEVE_ANIMAL_DETAILS_ENTITY:
                //TODO
                if($isUpdate) {
                    return null; //update Array
                } else {
                    return null; //post Array
                }

            case RequestType::RETRIEVE_ANIMALS_ENTITY:
                //TODO
                if($isUpdate) {
                    return null; //update Array
                } else {
                    return null; //post Array
                }

            case RequestType::RETRIEVE_EU_COUNTRIES_ENTITY:
                //TODO
                if($isUpdate) {
                    return null; //update Array
                } else {
                    return null; //post Array
                }

            case RequestType::RETRIEVE_UBN_DETAILS_ENTITY:
                //TODO
                if($isUpdate) {
                    return null; //update Array
                } else {
                    return null; //post Array
                }

            default:
                if($isUpdate) {
                    return null; //update Array
                } else {
                    return null; //post Array
                }
        }
    }
}