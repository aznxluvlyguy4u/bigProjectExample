<?php

namespace AppBundle\Processor;

use AppBundle\Entity\ReportWorker;
use AppBundle\Enumerator\ReportType;
use AppBundle\Enumerator\WorkerAction;
use AppBundle\Service\Report\AnimalsOverviewReportService;
use AppBundle\Service\Report\AnnualActiveLivestockRamMatesReportService;
use AppBundle\Service\Report\AnnualActiveLivestockReportService;
use AppBundle\Service\Report\AnnualTe100UbnProductionReportService;
use AppBundle\Service\Report\FertilizerAccountingReport;
use AppBundle\Service\Report\InbreedingCoefficientReportService;
use AppBundle\Service\Report\LiveStockReportService;
use AppBundle\Service\Report\OffspringReportService;
use AppBundle\Service\Report\PedigreeCertificateReportService;
use AppBundle\Service\Report\PedigreeRegisterOverviewReportService;
use AppBundle\Util\ResultUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Util\JSON;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Symfony\Component\HttpFoundation\Response;

class ReportProcessor implements PsrProcessor, CommandSubscriberInterface
{
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

    public function __construct(
        AnnualActiveLivestockReportService $annualActiveLivestockReportService,
        AnnualTe100UbnProductionReportService $annualTe100UbnProductionReportService,
        FertilizerAccountingReport $accountingReport,
        InbreedingCoefficientReportService $coefficientReportService,
        AnimalsOverviewReportService $animalsOverviewReportService,
        AnnualActiveLivestockRamMatesReportService $annualActiveLivestockRamMatesReportService,
        OffspringReportService $offspringReportService,
        PedigreeRegisterOverviewReportService $pedigreeRegisterOverviewReportService,
        PedigreeCertificateReportService $pedigreeCertificateReportService,
        LiveStockReportService $liveStockReportService,
        EntityManager $em)
    {
        $this->em = $em;
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

            switch($reportType) {
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
                        $data = $this->liveStockReportService->getReport($worker->getActionBy(), $worker->getLocation(), $fileType, $concatValueAndAccuracy, $content, $locale);
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
                $worker->setErrorCode(Response::HTTP_INTERNAL_SERVER_ERROR);
                $worker->setErrorMessage('SOMETHING WENT WRONG');
            }
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
        }
        return self::ACK;
    }

    public static function getSubscribedCommand()
    {
        return WorkerAction::GENERATE_REPORT;
    }
}