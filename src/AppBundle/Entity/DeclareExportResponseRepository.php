<?php

namespace AppBundle\Entity;
use AppBundle\Constant\Constant;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Output\DeclareExportResponseOutput;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclareExportResponseRepository
 * @package AppBundle\Entity
 */
class DeclareExportResponseRepository extends BaseRepository {
    /**
     * @param Client $client
     * @param $messageNumber
     * @return DeclareExportResponse|null
     */
    public function getExportResponseByMessageNumber(Client $client, $messageNumber)
    {
        $retrievedExports = $this->_em->getRepository(Constant::DECLARE_EXPORT_REPOSITORY)->getExports($client);

        return $this->getResponseMessageFromRequestsByMessageNumber($retrievedExports, $messageNumber);
    }

    public function getExportsWithLastHistoryResponses(Client $client)
    {
        $retrievedExports = $this->_em->getRepository(Constant::DECLARE_EXPORT_REPOSITORY)->getExports($client);

        $results = new ArrayCollection();

        foreach($retrievedExports as $export) {

            $isHistoryRequestStateType = $export->getRequestState() == RequestStateType::OPEN ||
                $export->getRequestState() == RequestStateType::REVOKING ||
                $export->getRequestState() == RequestStateType::FINISHED;

            if($isHistoryRequestStateType) {
                $results->add(DeclareExportResponseOutput::createHistoryResponse($export));
            }
        }

        return $results;
    }

    public function getExportsWithLastErrorResponses(Client $client)
    {
        $retrievedExports = $this->_em->getRepository(Constant::DECLARE_EXPORT_REPOSITORY)->getExports($client);

        $results = new ArrayCollection();

        foreach($retrievedExports as $export) {
            if($export->getRequestState() == RequestStateType::FAILED) {
                $results->add(DeclareExportResponseOutput::createErrorResponse($export));
            }
        }

        return $results;
    }
}