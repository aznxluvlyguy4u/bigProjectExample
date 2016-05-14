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

    if ($declareTagsTransferUpdate->getTags() != null) {
      $declareTagsTransfer->setTag($declareTagsTransferUpdate->getTags());
    }

    if ($declareTagsTransferUpdate->getRelationNumberAcceptant()) {
      $declareTagsTransfer->setRelationNumberAcceptant($declareTagsTransferUpdate->getRelationNumberAcceptant());
    }

    return $declareTagsTransfer;
  }
}