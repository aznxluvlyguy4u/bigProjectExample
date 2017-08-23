<?php

namespace AppBundle\Entity;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\ResultUtil;

/**
 * Class DeclareNsfoBaseRepository
 * @package AppBundle\Entity
 */
class DeclareNsfoBaseRepository extends BaseRepository implements DeclareBaseRepositoryInterface
{
    /**
     * @param $messageId
     * @return \AppBundle\Component\HttpFoundation\JsonResponse|DeclareBase|DeclareArrival|DeclareImport|DeclareDepart|DeclareExport|DeclareLoss|DeclareTagReplace|DeclareTagsTransfer|RevokeDeclaration|DeclareBirth|Litter
     */
    public function getErrorDetails($messageId)
    {
        /** @var DeclareBase $declare */
        $declare = $this->findOneByMessageId($messageId);

        if ($declare === null) {
            return ResultUtil::errorResult('No declare found for given messageId: '.$messageId, 428);
        }


        $errorJsonResponse = ResultUtil::errorResult('Declare does NOT have FAILED or OPEN requestState, but: '.$declare->getRequestState(), 428);

        if ($declare instanceof Litter) {
            //Specific litter failed validation
            if ($declare->getRequestState() !== RequestStateType::FAILED &&
                $declare->getRequestState() !== RequestStateType::OPEN &&
                $declare->getStatus() !== RequestStateType::INCOMPLETE) {
                return $errorJsonResponse;
            }

        } else {
            if ($declare->getRequestState() !== RequestStateType::FAILED) {
                return $errorJsonResponse;
            }
        }

        return $declare;
    }
}