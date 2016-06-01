<?php

namespace AppBundle\Entity;
use AppBundle\Constant\Constant;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Output\DeclareImportResponseOutput;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclareImportResponseRepository
 * @package AppBundle\Entity
 */
class DeclareImportResponseRepository extends BaseRepository {
    /**
     * @param Client $client
     * @param $messageNumber
     * @return DeclareImportResponse|null
     */
    public function getImportResponseByMessageNumber(Client $client, $messageNumber)
    {
        $retrievedImports = $this->_em->getRepository(Constant::DECLARE_IMPORT_REPOSITORY)->getImports($client);

        return $this->getResponseMessageFromRequestsByMessageNumber($retrievedImports, $messageNumber);
    }

    public function getImportsWithLastHistoryResponses(Client $client)
    {
        $retrievedImports = $this->_em->getRepository(Constant::DECLARE_IMPORT_REPOSITORY)->getImports($client);

        $results = new ArrayCollection();

        foreach($retrievedImports as $import) {

            $isHistoryRequestStateType = $import->getRequestState() == RequestStateType::OPEN ||
                $import->getRequestState() == RequestStateType::REVOKING ||
                $import->getRequestState() == RequestStateType::REVOKED ||
                $import->getRequestState() == RequestStateType::FINISHED;

            if($isHistoryRequestStateType) {
                $results->add(DeclareImportResponseOutput::createHistoryResponse($import));
            }
        }

        return $results;
    }

    public function getImportsWithLastErrorResponses(Client $client)
    {
        $retrievedImports = $this->_em->getRepository(Constant::DECLARE_IMPORT_REPOSITORY)->getImports($client);

        $results = new ArrayCollection();

        foreach($retrievedImports as $import) {
            if($import->getRequestState() == RequestStateType::FAILED) {
                $results->add(DeclareImportResponseOutput::createErrorResponse($import));
            }
        }

        return $results;
    }
}