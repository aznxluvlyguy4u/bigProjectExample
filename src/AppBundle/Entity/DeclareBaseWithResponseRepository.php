<?php

namespace AppBundle\Entity;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Traits\EntityClassInfo;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class DeclareBaseRepository
 * @package AppBundle\Entity
 */
class DeclareBaseWithResponseRepository extends BaseRepository implements DeclareBaseRepositoryInterface
{
    use EntityClassInfo;

    /**
     * @param $messageId
     * @return DeclareBaseWithResponse|DeclareAnimalFlag|DeclarationDetail
     */
    public function getErrorDetails($messageId)
    {
        /** @var DeclareBaseWithResponse $declare */
        $declare = $this->findOneByMessageId($messageId);

        if (!$declare) {
            throw new NotFoundHttpException('No declare found for given messageId: '.$messageId);
        }

        if ($declare->getRequestState() !== RequestStateType::FAILED) {
            throw new NotFoundHttpException('Declare does NOT have FAILED requestState, but: '.$declare->getRequestState());
        }

        return $declare;
    }


    /**
     * @param $requestId
     * @return null|object
     */
    public function getByRequestId($requestId)
    {
        return $this->findOneBy(['requestId' => $requestId]);
    }
}