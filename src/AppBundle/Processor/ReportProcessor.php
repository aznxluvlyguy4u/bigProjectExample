<?php
/**
 * Created by PhpStorm.
 * User: johnnieho
 * Date: 19/06/2018
 * Time: 11:18
 */

namespace AppBundle\Processor;

use AppBundle\Entity\ReportWorker;
use AppBundle\Entity\Worker;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\ReportType;
use AppBundle\Service\Report\AnimalsOverviewReportService;
use AppBundle\Service\Report\AnnualActiveLivestockRamMatesReportService;
use AppBundle\Service\Report\AnnualActiveLivestockReportService;
use AppBundle\Service\Report\AnnualTe100UbnProductionReportService;
use AppBundle\Service\Report\FertilizerAccountingReport;
use AppBundle\Service\Report\InbreedingCoefficientReportService;
use AppBundle\Service\Report\OffspringReportService;
use AppBundle\Service\Report\PedigreeCertificateReportService;
use AppBundle\Service\Report\PedigreeRegisterOverviewReportService;
use AppBundle\Util\RequestUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Util\JSON;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
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

    public function __construct(
        AnnualActiveLivestockReportService $livestockReportService,
        AnnualTe100UbnProductionReportService $annualTe100UbnProductionReportService,
        FertilizerAccountingReport $accountingReport,
        InbreedingCoefficientReportService $coefficientReportService,
        AnimalsOverviewReportService $animalsOverviewReportService,
        AnnualActiveLivestockRamMatesReportService $annualActiveLivestockRamMatesReportService,
        OffspringReportService $offspringReportService,
        PedigreeRegisterOverviewReportService $pedigreeRegisterOverviewReportService,
        PedigreeCertificateReportService $pedigreeCertificateReportService,
        EntityManager $em)
    {
        $this->em = $em;
        $this->livestockReport = $livestockReportService;
        $this->annualTe = $annualTe100UbnProductionReportService;
        $this->fertilizerAccounting = $accountingReport;
        $this->coefficientReportService = $coefficientReportService;
        $this->animalsOverviewReportService = $animalsOverviewReportService;
        $this->annualActiveLivestockRamMatesReportService = $annualActiveLivestockRamMatesReportService;
        $this->offspringReportService = $offspringReportService;
        $this->pedigreeRegisterOverviewReportService = $pedigreeRegisterOverviewReportService;
        $this->pedigreeCertificateReportService = $pedigreeCertificateReportService;
    }

    public function process(PsrMessage $message, PsrContext $context)
    {
        $worker = null;
        try {
            $data = JSON::decode($message->getBody());

            $workerId = $data['worker_id'];

            /**
             * @var Worker $worker
             */
            $worker = $this->em->getRepository(Worker::class)->find($workerId);
            if (!$worker)
                return self::REJECT;

            /**
             * @var ReportWorker $pdfWorker
             */
            $pdfWorker = $worker->getReportWorker();
            $reportType = $pdfWorker->getReportType();

            switch($reportType) {
                case ReportType::PEDIGREE_CERTIFICATE:
                    {
                        $fileType = $data['extension'];
                        $content = new ArrayCollection(json_decode($data['content'], true));
                        $data = $this->pedigreeCertificateReportService->getReport($worker->getOwner(), $worker->getLocation(), $fileType, $content);
                        break;
                    }
                case ReportType::PEDIGREE_REGISTER_OVERVIEW:
                    {
                        $type = $data['type'];
                        $fileType = $data['extension'];
                        $uploadToS3 = $data['upload_to_s3'];
                        $data = $this->pedigreeRegisterOverviewReportService->request($type, $fileType, $uploadToS3);
                        break;
                    }
                case ReportType::OFF_SPRING:
                    {

                        $content = new ArrayCollection(json_decode($data['content'], true));
                        $concatValueAndAccuracy = $data['concat_value_and_accuracy'];
                        $data = $this->offspringReportService->getReport($worker->getOwner(), $worker->getLocation(), $content, $concatValueAndAccuracy);
                        break;
                    }
                case ReportType::ANIMALS_OVERVIEW:
                    {
                        $concatValueAndAccuracy = $data['concat_value_and_accuracy'];
                        $pedigreeActiveEndDateLimit = new \DateTime($data['pedigree_active_end_date_limit']);
                        $activeUbnReferenceDate = new \DateTime($data['active_ubn_reference_date_string']);
                        $data = $this->animalsOverviewReportService->getReport($concatValueAndAccuracy, $pedigreeActiveEndDateLimit, $activeUbnReferenceDate);
                        break;
                    }
                case ReportType::INBREEDING_COEFFICIENT:
                    {
                        $content = json_decode($data['content'], true);
                        $content = new ArrayCollection($content);
                        $data = $this->coefficientReportService->getReport($worker->getOwner(), $content, $data['extension']);
                        break;
                    }
                case ReportType::FERTILIZER_ACCOUNTING:
                    {
                        $date = new \DateTime($data['reference_date']);
                        $data = $this->fertilizerAccounting->getReport($worker->getLocation(), $date, $data['extension']);
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
                        $data = $this->annualTe->getReport($year, $pedigreeActiveEndDateLimit);
                        break;
                    }
            }
            $arrayData = JSON::decode($data->getContent());

            if($data->getStatusCode() === Response::HTTP_OK) {
                $pdfWorker->setDownloadUrl($arrayData['result']);
            }
            else {
                $worker->setErrorCode($data->getStatusCode());
                $worker->setErrorMessage($arrayData['result']['message']);
            }
            $this->em->persist($pdfWorker);
        }
        catch(\Exception $e) {
            if($worker) {
                $worker->setErrorCode($e->getCode());
                $worker->setErrorMessage($e->getMessage());
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
        return 'generate_pdf';
    }
}