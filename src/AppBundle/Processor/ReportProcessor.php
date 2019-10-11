<?php

namespace AppBundle\Processor;

use AppBundle\Component\Option\BirthListReportOptions;
use AppBundle\Component\Option\ClientNotesOverviewReportOptions;
use AppBundle\Component\Option\CompanyRegisterReportOptions;
use AppBundle\Component\Option\MembersAndUsersOverviewReportOptions;
use AppBundle\Entity\ReportWorker;
use AppBundle\Enumerator\ReportType;
use AppBundle\Enumerator\WorkerAction;
use AppBundle\Service\BaseSerializer;
use AppBundle\Service\Report\AnimalHealthStatusesReportService;
use AppBundle\Service\Report\AnimalsOverviewReportService;
use AppBundle\Service\Report\AnnualActiveLivestockRamMatesReportService;
use AppBundle\Service\Report\AnnualActiveLivestockReportService;
use AppBundle\Service\Report\AnnualTe100UbnProductionReportService;
use AppBundle\Service\Report\BirthListReportService;
use AppBundle\Service\Report\ClientNotesOverviewReportService;
use AppBundle\Service\Report\CompanyRegisterReportService;
use AppBundle\Service\Report\FertilizerAccountingReport;
use AppBundle\Service\Report\InbreedingCoefficientReportService;
use AppBundle\Service\Report\LiveStockReportService;
use AppBundle\Service\Report\MembersAndUsersOverviewReportService;
use AppBundle\Service\Report\OffspringReportService;
use AppBundle\Service\Report\PedigreeCertificateReportService;
use AppBundle\Service\Report\PedigreeRegisterOverviewReportService;
use AppBundle\Service\Report\PopRepInputFileService;
use AppBundle\Service\Report\WeightsPerYearOfBirthReportService;
use AppBundle\Util\ResultUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Util\JSON;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;

class ReportProcessor implements PsrProcessor, CommandSubscriberInterface
{
    const ERROR_LOG_HEADER = '===== SYMFONY WORKER =====';

    /**
     * @var AnnualActiveLivestockReportService
     */
    private $livestockReport;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var AnnualTe100UbnProductionReportService
     */
    private $annualTe;

    /**
     * @var FertilizerAccountingReport
     */
    private $fertilizerAccounting;

    /**
     * @var InbreedingCoefficientReportService
     */
    private $coefficientReportService;

    /**
     * @var AnimalHealthStatusesReportService
     */
    private $animalHealthStatusesReportService;

    /**
     * @var AnimalsOverviewReportService
     */
    private $animalsOverviewReportService;

    /**
     * @var AnnualActiveLivestockRamMatesReportService
     */
    private $annualActiveLivestockRamMatesReportService;

    /**
     * @var OffspringReportService
     */
    private $offspringReportService;

    /**
     * @var PedigreeRegisterOverviewReportService
     */
    private $pedigreeRegisterOverviewReportService;

    /**
     * @var PedigreeCertificateReportService
     */
    private $pedigreeCertificateReportService;

    /**
     * @var LiveStockReportService
     */
    private $liveStockReportService;

    /** @var BirthListReportService */
    private $birthListReportService;

    /** @var MembersAndUsersOverviewReportService */
    private $membersAndUsersOverviewReport;

    /** @var CompanyRegisterReportService */
    private $companyRegisterReportService;

    /** @var ClientNotesOverviewReportService */
    private $clientNotesOverviewReportService;

    /** @var WeightsPerYearOfBirthReportService */
    private $weightsPerYearOfBirthReportService;

    /** @var PopRepInputFileService */
    private $popRepInputFileService;

    /** @var BaseSerializer */
    private $serializer;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * ReportProcessor constructor.
     * @param PopRepInputFileService $popRepInputFileService
     * @param WeightsPerYearOfBirthReportService $weightsPerYearOfBirthReportService
     * @param ClientNotesOverviewReportService $clientNotesOverviewReportService
     * @param AnimalHealthStatusesReportService $animalHealthStatusesReportService
     * @param AnnualActiveLivestockReportService $annualActiveLivestockReportService
     * @param AnnualTe100UbnProductionReportService $annualTe100UbnProductionReportService
     * @param CompanyRegisterReportService $companyRegisterReportService
     * @param FertilizerAccountingReport $accountingReport
     * @param InbreedingCoefficientReportService $coefficientReportService
     * @param AnimalsOverviewReportService $animalsOverviewReportService
     * @param AnnualActiveLivestockRamMatesReportService $annualActiveLivestockRamMatesReportService
     * @param OffspringReportService $offspringReportService
     * @param PedigreeRegisterOverviewReportService $pedigreeRegisterOverviewReportService
     * @param PedigreeCertificateReportService $pedigreeCertificateReportService
     * @param LiveStockReportService $liveStockReportService
     * @param BirthListReportService $birthListReportService
     * @param MembersAndUsersOverviewReportService $membersAndUsersOverviewReport
     * @param EntityManager $em
     * @param Logger $logger
     * @param BaseSerializer $serializer
     */
    public function __construct(
        PopRepInputFileService $popRepInputFileService,
        WeightsPerYearOfBirthReportService $weightsPerYearOfBirthReportService,
        ClientNotesOverviewReportService $clientNotesOverviewReportService,
        AnimalHealthStatusesReportService $animalHealthStatusesReportService,
        AnnualActiveLivestockReportService $annualActiveLivestockReportService,
        AnnualTe100UbnProductionReportService $annualTe100UbnProductionReportService,
        CompanyRegisterReportService $companyRegisterReportService,
        FertilizerAccountingReport $accountingReport,
        InbreedingCoefficientReportService $coefficientReportService,
        AnimalsOverviewReportService $animalsOverviewReportService,
        AnnualActiveLivestockRamMatesReportService $annualActiveLivestockRamMatesReportService,
        OffspringReportService $offspringReportService,
        PedigreeRegisterOverviewReportService $pedigreeRegisterOverviewReportService,
        PedigreeCertificateReportService $pedigreeCertificateReportService,
        LiveStockReportService $liveStockReportService,
        BirthListReportService $birthListReportService,
        MembersAndUsersOverviewReportService $membersAndUsersOverviewReport,
        EntityManager $em,
        Logger $logger,
        BaseSerializer $serializer
    )
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->livestockReport = $annualActiveLivestockReportService;
        $this->annualTe = $annualTe100UbnProductionReportService;
        $this->fertilizerAccounting = $accountingReport;
        $this->coefficientReportService = $coefficientReportService;
        $this->animalsOverviewReportService = $animalsOverviewReportService;
        $this->annualActiveLivestockRamMatesReportService = $annualActiveLivestockRamMatesReportService;
        $this->offspringReportService = $offspringReportService;
        $this->pedigreeRegisterOverviewReportService = $pedigreeRegisterOverviewReportService;
        $this->pedigreeCertificateReportService = $pedigreeCertificateReportService;
        $this->liveStockReportService = $liveStockReportService;
        $this->birthListReportService = $birthListReportService;
        $this->membersAndUsersOverviewReport = $membersAndUsersOverviewReport;
        $this->companyRegisterReportService = $companyRegisterReportService;
        $this->animalHealthStatusesReportService = $animalHealthStatusesReportService;
        $this->clientNotesOverviewReportService = $clientNotesOverviewReportService;
        $this->weightsPerYearOfBirthReportService = $weightsPerYearOfBirthReportService;
        $this->popRepInputFileService = $popRepInputFileService;
    }

    public function process(PsrMessage $message, PsrContext $context)
    {
        $worker = null;
        try {
            $data = JSON::decode($message->getBody());

            $workerId = $data['worker_id'];

            /**
             * @var ReportWorker $worker
             */
            $worker = $this->em->getRepository(ReportWorker::class)->find($workerId);
            if (!$worker)
                return self::REJECT;

            $reportType = $worker->getReportType();
            $fileType = $worker->getFileType();
            $locale = $worker->getLocale();

            switch ($reportType) {
                case ReportType::PEDIGREE_CERTIFICATE:
                    {
                        $content = new ArrayCollection(json_decode($data['content'], true));
                        $data = $this->pedigreeCertificateReportService->getReport($worker->getActionBy(), $worker->getLocation(), $fileType, $content, $locale);
                        break;
                    }
                case ReportType::PEDIGREE_REGISTER_OVERVIEW:
                    {
                        $type = $data['type'];
                        $uploadToS3 = $data['upload_to_s3'];
                        $data = $this->pedigreeRegisterOverviewReportService->request($type, $fileType, $uploadToS3);
                        break;
                    }
                case ReportType::OFFSPRING:
                    {
                        $content = new ArrayCollection(json_decode($data['content'], true));
                        $concatValueAndAccuracy = $data['concat_value_and_accuracy'];
                        $data = $this->offspringReportService->getReport($worker->getActionBy(), $worker->getLocation(), $content, $concatValueAndAccuracy, $locale);
                        break;
                    }
                case ReportType::ANIMALS_OVERVIEW:
                    {
                        $concatValueAndAccuracy = $data['concat_value_and_accuracy'];
                        $pedigreeActiveEndDateLimit = new \DateTime($data['pedigree_active_end_date_limit']);
                        $activeUbnReferenceDate = new \DateTime($data['active_ubn_reference_date_string']);
                        $data = $this->animalsOverviewReportService->getReport($concatValueAndAccuracy, $pedigreeActiveEndDateLimit, $activeUbnReferenceDate, $locale);
                        break;
                    }
                case ReportType::INBREEDING_COEFFICIENT:
                    {
                        $content = json_decode($data['content'], true);
                        $content = new ArrayCollection($content);
                        $data = $this->coefficientReportService->getReport($worker->getActionBy(), $content, $fileType, $locale);
                        break;
                    }
                case ReportType::FERTILIZER_ACCOUNTING:
                    {
                        $date = new \DateTime($data['reference_date']);
                        $data = $this->fertilizerAccounting->getReport($worker->getLocation(), $date, $fileType);
                        break;
                    }
                case ReportType::ANNUAL_ACTIVE_LIVE_STOCK_RAM_MATES:
                    {
                        $year = $data['year'];
                        $data = $this->annualActiveLivestockRamMatesReportService->getReport($year);
                        break;
                    }
                case ReportType::ANNUAL_ACTIVE_LIVE_STOCK:
                    {
                        $year = $data['year'];
                        $data = $this->livestockReport->getReport($year);
                        break;
                    }
                case ReportType::ANNUAL_TE_100:
                    {
                        $year = $data['year'];
                        $pedigreeActiveEndDateLimit = new \DateTime($data['pedigree_active_end_date']);
                        $data = $this->annualTe->getReport($year, $pedigreeActiveEndDateLimit, $locale);
                        break;
                    }
                case ReportType::LIVE_STOCK:
                    {
                        $content = new ArrayCollection(json_decode($data['content'], true));
                        $concatValueAndAccuracy = $data['concat_value_and_accuracy'];
                        $data = $this->liveStockReportService->getReport($worker->getLocation()->getOwner(),
                            $worker->getLocation(), $fileType, $concatValueAndAccuracy, $content, $locale);
                        break;
                    }
                case ReportType::BIRTH_LIST:
                    {
                        $options = $this->serializer->deserializeToObject($data['options'],
                            BirthListReportOptions::class, null);
                        $data = $this->birthListReportService->getReport($worker->getActionBy(),
                            $worker->getLocation(), $options);
                        break;
                    }
                case ReportType::MEMBERS_AND_USERS_OVERVIEW:
                    {
                        $options = $this->serializer->deserializeToObject($data['options'],
                            MembersAndUsersOverviewReportOptions::class, null);
                        $data = $this->membersAndUsersOverviewReport->getReport($options);
                        break;
                    }
                case ReportType::COMPANY_REGISTER:
                    {
                        $options = $this->serializer->deserializeToObject($data['options'],
                            CompanyRegisterReportOptions::class, null);
                        $data = $this->companyRegisterReportService->getReport($worker->getActionBy(), $worker->getLocation(), $options);
                        break;
                    }
                case ReportType::ANIMAL_HEALTH_STATUSES:
                    {
                        $data = $this->animalHealthStatusesReportService->getReport();
                        break;
                    }
                case ReportType::CLIENT_NOTES_OVERVIEW:
                    {
                        $options = $this->serializer->deserializeToObject($data['options'],
                            ClientNotesOverviewReportOptions::class, null);
                        $data = $this->clientNotesOverviewReportService->getReport($worker->getActionBy(), $options);
                        break;
                    }
                case ReportType::WEIGHTS_PER_YEAR_OF_BIRTH:
                    {
                        $yearOfBirth = $data['year_of_birth'];
                        $data = $this->weightsPerYearOfBirthReportService->getReport($yearOfBirth, $worker->getLocation());
                        break;
                    }
                case ReportType::POPREP_INPUT_FILE:
                    {
                        $pedigreeRegister = $data['pedigree_register'];
                        $data = $this->popRepInputFileService->getReport($pedigreeRegister);
                        break;
                    }
            }
            $arrayData = JSON::decode($data->getContent());

            if($data->getStatusCode() === Response::HTTP_OK) {
                $worker->setDownloadUrl($arrayData['result']);
            }
            else {
                $worker->setErrorCode($data->getStatusCode());
                $worker->setErrorMessage(ResultUtil::getMessageStringFromErrorResult($arrayData));
            }
            $this->em->persist($worker);
        }
        catch(\Throwable $e){
            if($worker) {
                $worker->setDebugErrorCode($e->getCode());
                $worker->setDebugErrorMessage($e->getMessage());
                if ($this->publiclyDisplayErrorMessage($e->getCode())) {
                    $worker->setErrorCode($e->getCode());
                    $worker->setErrorMessage($e->getMessage());
                } else {
                    $worker->setErrorCode(Response::HTTP_INTERNAL_SERVER_ERROR);
                    $worker->setErrorMessage('SOMETHING WENT WRONG');
                }
            }
            $this->logException($e);
        }

        try {
            if ($worker) {
                $worker->setFinishedAt(new \DateTime());
                $this->em->persist($worker);
            }
            $this->em->flush();
        }
        catch (\Exception $e) {
            //Database Exception
            $this->logException($e);
        }
        return self::ACK;
    }

    public static function getSubscribedCommand()
    {
        return WorkerAction::GENERATE_REPORT;
    }


    private function logException(\Throwable $exception)
    {
        $this->logger->error(self::ERROR_LOG_HEADER);
        $this->logger->error($exception->getMessage());
        $this->logger->error($exception->getTraceAsString());
    }


    /**
     * @param int|null $errorCode
     * @return bool
     */
    private function publiclyDisplayErrorMessage($errorCode): bool
    {
        return is_int($errorCode) && (
                $errorCode === Response::HTTP_NOT_FOUND
            );
    }
}
