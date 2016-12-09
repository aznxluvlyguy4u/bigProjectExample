<?php

namespace AppBundle\Entity;

use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Output\DeclareTagsTransferResponseOutput;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

/**
 * Class TagTransferItemResponseRepository
 * @package AppBundle\Entity
 */
class TagTransferItemResponseRepository extends BaseRepository {

    /**
     * @param Client $client
     * @param Location $location
     * @param $messageNumber
     * @return TagTransferItemResponse|null
     */
    public function getTagTransferItemResponseByMessageNumber(Client $client, Location $location, $messageNumber)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('relationNumberKeeper', $client->getRelationNumberKeeper()))
            ->andWhere(Criteria::expr()->eq('location', $location))
            ->andWhere(Criteria::expr()->eq('messageNumber', $messageNumber))
            ->orderBy(['logDate' => Criteria::ASC]);

        $tagsTransferItemResponse = $this->getManager()->getRepository(TagTransferItemResponse::class)
            ->matching($criteria);

        return $tagsTransferItemResponse;
    }

    /**
     * @param Client $client
     * @param Location $location
     * @return ArrayCollection
     */
    public function getTagTransferItemRequestsWithLastHistoryResponses(Client $client, Location $location)
    {
        //Get only the declareTagTransfers of the given client
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('relationNumberKeeper', $client->getRelationNumberKeeper()))
            ->andWhere(Criteria::expr()->eq('location', $location))
            ->orderBy(['logDate' => Criteria::ASC]);

        $declareTagsTransfers = $this->getManager()->getRepository(DeclareTagsTransfer::class)
            ->matching($criteria);


        $results = new ArrayCollection();

        foreach ($declareTagsTransfers as $declareTagsTransfer) {
            foreach ($declareTagsTransfer->getTagTransferRequests() as $tagTransferItemRequest) {

                $requestState = $tagTransferItemRequest->getRequestState();
                $isHistoryRequestStateType = $requestState == RequestStateType::OPEN ||
                                             $requestState == RequestStateType::REVOKING ||
                                             $requestState == RequestStateType::REVOKED ||
                                             $requestState == RequestStateType::FINISHED ||
                                             $requestState == RequestStateType::FINISHED_WITH_WARNING;

                if($isHistoryRequestStateType) {
                    $lastTagTransferItemResponse = $this->getLastResponse($tagTransferItemRequest);
                    $results->add(DeclareTagsTransferResponseOutput::createHistoryResponse($tagTransferItemRequest, $lastTagTransferItemResponse));
                }
            }
        }

        return $results;
    }

    /**
     * @param Client $client
     * @return array
     */
    public function getTagTransferItemRequestsWithLastErrorResponses(Client $client, Location $location)
    {
        //Get only the declareTagTransfers of the given client
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('relationNumberKeeper', $client->getRelationNumberKeeper()))
            ->andWhere(Criteria::expr()->eq('location', $location))
            ->orderBy(['logDate' => Criteria::ASC]);

        $declareTagsTransfers = $this->getManager()->getRepository(DeclareTagsTransfer::class)
            ->matching($criteria);

        $results = array();

        /** @var DeclareTagsTransfer $declareTagsTransfer */
        foreach ($declareTagsTransfers as $declareTagsTransfer) {
            foreach ($declareTagsTransfer->getTagTransferRequests() as $tagTransferItemRequest) {

                if($tagTransferItemRequest->getRequestState() == RequestStateType::FAILED) {
                    $lastTagTransferItemResponse = $this->getLastResponse($tagTransferItemRequest);

                    if($lastTagTransferItemResponse != null) {
                        if ($lastTagTransferItemResponse->getIsRemovedByUser() != true) {
                            $results[] = DeclareTagsTransferResponseOutput::createErrorResponse($tagTransferItemRequest, $lastTagTransferItemResponse);
                        }
                    }
                }

            }
        }

        return $results;
    }


    /**
     * @param TagTransferItemRequest $tagTransferItemRequest
     * @return TagTransferItemResponse|null
     */
    public function getLastResponse(TagTransferItemRequest $tagTransferItemRequest)
    {
        //get the last response
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('id', $tagTransferItemRequest->getId()))
            ->orderBy(['logDate' => Criteria::DESC])
            ->setMaxResults(1);

        $tagsTransferItemResponse = $this->getManager()->getRepository(TagTransferItemResponse::class)
            ->matching($criteria);

        if($tagsTransferItemResponse->isEmpty()) {
            return null;
        } else {
            return $tagsTransferItemResponse->get(0);
        }
    }

}