<?php


namespace AppBundle\Worker\Task;


use AppBundle\Entity\DeclareArrivalResponse;
use AppBundle\Entity\DeclareBaseResponse;
use AppBundle\Entity\DeclareBirthResponse;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareDepartResponse;
use AppBundle\Entity\DeclareExportResponse;
use AppBundle\Entity\DeclareImportResponse;
use AppBundle\Entity\DeclareLossResponse;
use AppBundle\Entity\DeclareTagReplaceResponse;
use AppBundle\Entity\DeclareTagsTransferResponse;
use AppBundle\Entity\RevokeDeclarationResponse;
use AppBundle\Enumerator\WorkerLevelType;
use AppBundle\Enumerator\WorkerTaskScope;
use AppBundle\Enumerator\WorkerTaskType;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

class WorkerMessageBodyDeclareResponsePersistence extends WorkerMessageBody
{
    /** @var DeclareBaseResponse */
    private $response;

    /**
     * WorkerMessageBody constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTaskType(WorkerTaskType::DECLARE_RESPONSE_PERSISTENCE);
        $this->setLevelType(WorkerLevelType::ALL);
    }

    /**
     * @return DeclareBaseResponse
     */
    public function getResponse(): DeclareBaseResponse
    {
        return $this->response;
    }

    /**
     * @param DeclareBaseResponse $response
     * @return WorkerMessageBodyDeclareResponsePersistence
     */
    public function setResponseAndScope(DeclareBaseResponse $response): WorkerMessageBodyDeclareResponsePersistence
    {
        $this->response = $response;
        $this->setScopeByResponseType($response);
        return $this;
    }

    /**
     * @param $response
     */
    private function setScopeByResponseType($response)
    {
        switch (true) {
            case $response instanceof DeclareArrivalResponse:
                $this->setScope(WorkerTaskScope::ARRIVAL);
                break;

            case $response instanceof DeclareBirthResponse:
                $this->setScope(WorkerTaskScope::BIRTH);
                break;

            case $response instanceof DeclareDepartResponse:
                $this->setScope(WorkerTaskScope::DEPART);
                break;

            case $response instanceof DeclareExportResponse:
                $this->setScope(WorkerTaskScope::EXPORT);
                break;

            case $response instanceof DeclareImportResponse:
                $this->setScope(WorkerTaskScope::IMPORT);
                break;

            case $response instanceof DeclareLossResponse:
                $this->setScope(WorkerTaskScope::LOSS);
                break;

            case $response instanceof DeclareTagReplaceResponse:
                $this->setScope(WorkerTaskScope::TAG_REPLACE);
                break;

            case $response instanceof DeclareTagsTransferResponse:
                $this->setScope(WorkerTaskScope::TAG_TRANSFER);
                break;

            case $response instanceof RevokeDeclarationResponse:
                $this->setScope(WorkerTaskScope::REVOKE);
                break;

            default: throw new PreconditionFailedHttpException('INVALID RESPONSE TYPE');
        }
    }
}