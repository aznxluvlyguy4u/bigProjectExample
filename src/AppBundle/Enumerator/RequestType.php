<?php

namespace AppBundle\Enumerator;


class RequestType
{
    const DECLARATION_DETAIL = 'DECLARATION_DETAIL';
    const DECLARE_ARRIVAL = 'DECLARE_ARRIVAL';
    const DECLARE_BIRTH = 'DECLARE_BIRTH';
    const DECLARE_ANIMAL_FLAG = 'DECLARE_ANIMAL_FLAG';
    const DECLARE_DEPART = 'DECLARE_DEPART';
    const DECLARE_EARTAGS_TRANSFER = 'DECLARE_EARTAGS_TRANSFER';
    const DECLARE_LOSS = 'DECLARE_LOSS';
    const DECLARE_EXPORT = 'DECLARE_EXPORT';
    const DECLARE_IMPORT = 'DECLARE_IMPORT';
    const RETRIEVE_TAGS = 'RETRIEVE_TAGS';
    const REVOKE_DECLARATION = 'REVOKE_DECLARATION';

    const DECLARATION_DETAIL_ENTITY = 'DeclarationDetail';
    const DECLARE_ARRIVAL_ENTITY = 'DeclareArrival';
    const DECLARE_BIRTH_ENTITY = 'DeclareBirth';
    const DECLARE_ANIMAL_FLAG_ENTITY = 'DeclareAnimalFlag';
    const DECLARE_DEPART_ENTITY = 'DeclareDepart';
    const DECLARE_TAGS_TRANSFER_ENTITY = 'DeclareTagsTransfer';
    const DECLARE_LOSS_ENTITY = 'DeclareLoss';
    const DECLARE_EXPORT_ENTITY = 'DeclareExport';
    const DECLARE_IMPORT_ENTITY = 'DeclareImport';
    const RETRIEVE_TAGS_ENTITY = 'RetrieveTags';
    const REVOKE_DECLARATION_ENTITY = 'RevokeDeclaration';
}