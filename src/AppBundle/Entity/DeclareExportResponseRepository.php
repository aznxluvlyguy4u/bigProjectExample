<?php

namespace AppBundle\Entity;
use AppBundle\Component\Utils;
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
     * @param Location $location
     * @param $messageNumber
     * @return DeclareExportResponse|null
     */
    public function getExportResponseByMessageNumber(Location $location, $messageNumber)
    {
        $retrievedExports = $this->_em->getRepository(Constant::DECLARE_EXPORT_REPOSITORY)->getExports($location);

        return $this->getResponseMessageFromRequestsByMessageNumber($retrievedExports, $messageNumber);
    }

    /**
     * @param Location $location
     * @return ArrayCollection
     */
    public function getExportsWithLastHistoryResponses(Location $location)
    {
        $retrievedExports = $this->_em->getRepository(Constant::DECLARE_EXPORT_REPOSITORY)->getExports($location);

        $results = new ArrayCollection();

        foreach($retrievedExports as $export) {

            $isHistoryRequestStateType = $export->getRequestState() == RequestStateType::OPEN ||
                $export->getRequestState() == RequestStateType::REVOKING ||
                $export->getRequestState() == RequestStateType::REVOKED ||
                $export->getRequestState() == RequestStateType::FINISHED ||
                $export->getRequestState() == RequestStateType::FINISHED_WITH_WARNING;

            if($isHistoryRequestStateType) {
                $results->add(DeclareExportResponseOutput::createHistoryResponse($export));
            }
        }

        return $results;
    }

    /**
     * @param Location $location
     * @return array
     */
    public function getExportsWithLastErrorResponses(Location $location)
    {
        $retrievedExports = $this->_em->getRepository(Constant::DECLARE_EXPORT_REPOSITORY)->getExports($location);

        $results = array();

        foreach($retrievedExports as $export) {
            if($export->getRequestState() == RequestStateType::FAILED) {

                $lastResponse = Utils::returnLastResponse($export->getResponses());
                if($lastResponse != false) {
                    $results[] = DeclareExportResponseOutput::createErrorResponse($export);
                }
            }
        }

        return $results;
    }
}