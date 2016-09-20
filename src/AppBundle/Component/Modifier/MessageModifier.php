<?php

namespace AppBundle\Component\Modifier;


use AppBundle\Entity\DeclarationDetail;
use AppBundle\Entity\DeclareAnimalFlag;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Entity\RetrieveAnimals;
use AppBundle\Entity\RetrieveCountries;
use AppBundle\Entity\RetrieveTags;
use AppBundle\Entity\RetrieveUBNDetails;
use AppBundle\Entity\RevokeDeclaration;
use AppBundle\Component\Utils;
use AppBundle\Enumerator\RequestType;
use Doctrine\Common\Persistence\ObjectManager;

class MessageModifier
{
    /**
     * @var ObjectManager
     */
    private static $em;

    public function __construct(ObjectManager $em)
    {
        self::$em = $em;
    }

    /**
     * @param null|DeclareArrival|DeclareImport|DeclareExport|DeclareDepart|DeclareBirth|DeclareLoss|DeclareAnimalFlag|DeclarationDetail|DeclareTagsTransfer|RetrieveTags|RevokeDeclaration|RetrieveAnimals|RetrieveAnimals|RetrieveCountries|RetrieveUBNDetails $messageObject
     * @return null|DeclareArrival|DeclareImport|DeclareExport|DeclareDepart|DeclareBirth|DeclareLoss|DeclareAnimalFlag|DeclarationDetail|DeclareTagsTransfer|RetrieveTags|RevokeDeclaration|RetrieveAnimals|RetrieveAnimals|RetrieveCountries|RetrieveUBNDetails
     */
    public static function modifyBeforePersistingRequestStateByQueueStatus($messageObject, $doctrine)
    {
        $entityNameSpace = Utils::getClassName($messageObject);
        
        switch($entityNameSpace) {
            case RequestType::DECLARATION_DETAIL_ENTITY:
                return $messageObject;

            case RequestType::DECLARE_ANIMAL_FLAG_ENTITY:
                return $messageObject;

            case RequestType::DECLARE_ARRIVAL_ENTITY:
                return  AnimalRemover::removeUnverifiedAnimalFromMessageObject($messageObject, $doctrine);

            case RequestType::DECLARE_BIRTH_ENTITY:
                return AnimalRemover::removeChildFromDeclareBirth($messageObject, $doctrine);

            case RequestType::DECLARE_DEPART_ENTITY:
                return  $messageObject;

            case RequestType::DECLARE_TAGS_TRANSFER_ENTITY:
                return $messageObject;

            case RequestType::DECLARE_LOSS_ENTITY:
                return  $messageObject;

            case RequestType::DECLARE_EXPORT_ENTITY:
                return  $messageObject;

            case RequestType::DECLARE_IMPORT_ENTITY:
                return  AnimalRemover::removeUnverifiedAnimalFromMessageObject($messageObject, $doctrine);

            case RequestType::RETRIEVE_TAGS_ENTITY:
                return $messageObject;

            case RequestType::REVOKE_DECLARATION_ENTITY:
                return $messageObject;

            case RequestType::RETRIEVE_ANIMAL_DETAILS_ENTITY:
                return $messageObject;

            case RequestType::RETRIEVE_ANIMALS_ENTITY:
                return $messageObject;

            case RequestType::RETRIEVE_COUNTRIES_ENTITY:
                return $messageObject;

            case RequestType::RETRIEVE_UBN_DETAILS_ENTITY:
                return $messageObject;

            default:
                return $messageObject;
        }
    }


}