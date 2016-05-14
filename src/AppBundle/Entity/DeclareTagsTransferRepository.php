<?php

namespace AppBundle\Entity;

use AppBundle\Constant\Constant;

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
}