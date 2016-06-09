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
                if($isUpdate) {
                    return null; //update Array
                } else {
                    return null; //post Array
                }

            case RequestType::DECLARE_ANIMAL_FLAG_ENTITY:
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
                if($isUpdate) {
                    return DeclareBirthOutput::createUpdateRequestArray($messageObject);
                } else {
                    return DeclareBirthOutput::createPostRequestArray($messageObject);
                }

            case RequestType::DECLARE_DEPART_ENTITY:
                if($isUpdate) {
                    return DeclareDepartOutput::createUpdateRequestArray($messageObject);
                } else {
                    return DeclareDepartOutput::createPostRequestArray($messageObject);
                }

            case RequestType::DECLARE_TAGS_TRANSFER_ENTITY:
                if($isUpdate) {
                    return null; //at this moment no update for tag transfers
                } else {
                    return DeclareTagsTransferOutput::createPostRequestArray($messageObject); //post Array
                }

            case RequestType::DECLARE_LOSS_ENTITY:
                if($isUpdate) {
                    return DeclareLossOutput::createUpdateRequestArray($messageObject);
                } else {
                    return DeclareLossOutput::createPostRequestArray($messageObject);
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
                if($isUpdate) {
                    return null; //update Array
                } else {
                    return null; //post Array
                }

            case RequestType::REVOKE_DECLARATION_ENTITY:
                if($isUpdate) {
                    return null; //update Array
                } else {
                    return null; //post Array
                }

            case RequestType::RETRIEVE_ANIMAL_DETAILS_ENTITY:
                if($isUpdate) {
                    return null; //update Array
                } else {
                    return null; //post Array
                }

            case RequestType::RETRIEVE_ANIMALS_ENTITY:
                if($isUpdate) {
                    return null; //update Array
                } else {
                    return null; //post Array
                }

            case RequestType::RETRIEVE_COUNTRIES_ENTITY:
                if($isUpdate) {
                    return null; //update Array
                } else {
                    return null; //post Array
                }

            case RequestType::RETRIEVE_UBN_DETAILS_ENTITY:
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