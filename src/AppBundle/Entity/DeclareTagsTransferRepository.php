<?php

namespace AppBundle\Entity;

use AppBundle\Constant\Constant;
use AppBundle\Enumerator\TagStateType;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclareTagsTransferRepository
 * @package AppBundle\Entity
 */
class DeclareTagsTransferRepository extends BaseRepository {

  /**
   * @param $declareTagsTransferUpdate
   * @param $Id
   * @return null|object
   */
  public function updateDeclareTagsTransferMessage($declareTagsTransferUpdate, $Id) {
    $declareTagsTransfer = $this->findOneBy(array (Constant::REQUEST_ID_NAMESPACE => $Id));

    if($declareTagsTransfer == null) {
      return null;
    }

    if (sizeof($declareTagsTransferUpdate->getTags()) > 0) {

      $taglistUpdate =$declareTagsTransferUpdate->getTags();

      foreach($taglistUpdate as $tagListItemUpdate ) {
        $declareTagsTransfer->addTag($tagListItemUpdate);
      }

      $taglistUpdate = null;
    }

    if ($declareTagsTransferUpdate->getRelationNumberAcceptant()) {
      $declareTagsTransfer->setRelationNumberAcceptant($declareTagsTransferUpdate->getRelationNumberAcceptant());
    }

    return $declareTagsTransfer;
  }


  public function validateTags($client, $content) //DeclareTagTransfer ContentArray
  {
    //TODO Phase 2, return a detailed result of all the individual tags in an array of ArrayCollection. Which ones are missing, unassigned or not unassigned.

    //In phase 1 just do a very simple check
    foreach($content[Constant::TAGS_NAMESPACE] as $tag) {
      $ulnCountryCode = $tag[Constant::ULN_COUNTRY_CODE_NAMESPACE];
      $ulnNumber= $tag[Constant::ULN_NUMBER_NAMESPACE];

      $valid = $this->validateTag($client, $ulnCountryCode, $ulnNumber);

      if($valid[Constant::VALIDITY_NAMESPACE] == false && $valid[Constant::TAG_NAMESPACE] != null) {
        $errorMessage = "Tag " . $ulnCountryCode . $ulnNumber . " is unavailable for tag transfer.";
        return array(Constant::VALIDITY_NAMESPACE => false, Constant::MESSAGE_NAMESPACE => $errorMessage);

      } else if($valid[Constant::VALIDITY_NAMESPACE] == false && $valid[Constant::TAG_NAMESPACE] == null) {
        $errorMessage = "Tag " . $ulnCountryCode . $ulnNumber . " has not been found in your collection.";
        return array(Constant::VALIDITY_NAMESPACE => false, Constant::MESSAGE_NAMESPACE => $errorMessage);
      }
    }

    return array(Constant::VALIDITY_NAMESPACE => true, Constant::MESSAGE_NAMESPACE => null);
  }

  /**
   * Validate if tag. Returns:
   * - null, if no tag was found
   * - true, if unassigned tag was found
   * - false, if tag was found but was not unassigned
   *
   * @param Client $client
   * @param string $ulnCountryCode
   * @param string $ulnNumber
   * @return bool|null
   */
  public function validateTag(Client $client, $ulnCountryCode, $ulnNumber)
  {
    $tag = $this->getEntityManager()->getRepository(Constant::TAG_REPOSITORY)->findOneByUln($client, $ulnCountryCode, $ulnNumber);

    if($tag == null) {
      return array(Constant::VALIDITY_NAMESPACE => false, Constant::TAG_NAMESPACE => null);

    } else {
      if($tag->getTagStatus() == TagStateType::UNASSIGNED) {
        return array(Constant::VALIDITY_NAMESPACE => true, Constant::TAG_NAMESPACE => $tag);

      } else {
        return array(Constant::VALIDITY_NAMESPACE => false, Constant::TAG_NAMESPACE => $tag);
      }

    }

  }
}