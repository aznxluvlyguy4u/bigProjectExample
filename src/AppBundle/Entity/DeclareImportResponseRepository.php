<?php

namespace AppBundle\Entity;
use AppBundle\Component\Utils;
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
     * @param Location $location
     * @param $messageNumber
     * @return DeclareImportResponse|null
     */
    public function getImportResponseByMessageNumber(Location $location, $messageNumber)
    {
        $retrievedImports = $this->_em->getRepository(Constant::DECLARE_IMPORT_REPOSITORY)->getImports($location);

        return $this->getResponseMessageFromRequestsByMessageNumber($retrievedImports, $messageNumber);
    }

    /**
     * @param Location $location
     * @return ArrayCollection
     */
    public function getImportsWithLastHistoryResponses(Location $location)
    {
        $retrievedImports = $this->_em->getRepository(Constant::DECLARE_IMPORT_REPOSITORY)->getImports($location);

        $results = new ArrayCollection();

        foreach($retrievedImports as $import) {

            $isHistoryRequestStateType = $import->getRequestState() == RequestStateType::OPEN ||
                $import->getRequestState() == RequestStateType::REVOKING ||
                $import->getRequestState() == RequestStateType::REVOKED ||
                $import->getRequestState() == RequestStateType::FINISHED ||
                $import->getRequestState() == RequestStateType::FINISHED_WITH_WARNING;

            if($isHistoryRequestStateType) {
                $results->add(DeclareImportResponseOutput::createHistoryResponse($import));
            }
        }

        return $results;
    }

    /**
     * @param Location $location
     * @return array
     */
    public function getImportsWithLastErrorResponses(Location $location)
    {
        $retrievedImports = $this->_em->getRepository(Constant::DECLARE_IMPORT_REPOSITORY)->getImports($location);

        $results = array();

        foreach($retrievedImports as $import) {
            if($import->getRequestState() == RequestStateType::FAILED) {

                $lastResponse = Utils::returnLastResponse($import->getResponses());
                if($lastResponse != false) {
                    $results[] = DeclareImportResponseOutput::createErrorResponse($import);
                }
            }
        }

        return $results;
    }
}