<?php

namespace AppBundle\Entity;

use AppBundle\Constant\Constant;

/**
 * Class DeclareEartagsTransferRepository
 * @package AppBundle\Entity
 */
class DeclareEartagsTransferRepository extends BaseRepository {

  /**
   * @param $declareTagsTransferUpdate
   * @param $Id
   * @return null|object
   */
  public function updateDeclareTagsTransferMessage($declareTagsTransferUpdate, $Id) {
    $declareEartagsTransfer = $this->findOneBy(array (Constant::REQUEST_ID_NAMESPACE => $Id));

    if($declareEartagsTransfer == null) {
      return null;
    }

    if ($declareTagsTransferUpdate->getTags() != null) {
      $declareEartagsTransfer->setTag($declareTagsTransferUpdate->getTags());
    }

    if ($declareTagsTransferUpdate->getRelationNumberAcceptant()) {
      $declareEartagsTransfer->setRelationNumberAcceptant($declareTagsTransferUpdate->getRelationNumberAcceptant());
    }

    return $declareEartagsTransfer;
  }
}