<?php


namespace AppBundle\Service;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\ReportWorker;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Enumerator\ReportType;
use AppBundle\Enumerator\WorkerAction;
use AppBundle\Enumerator\WorkerType;
use AppBundle\Service\Report\PedigreeCertificateReportService;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\DateUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\Validator;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\UlnValidatorInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Enqueue\Client\ProducerInterface;
use Enqueue\Util\JSON;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\Translation\TranslatorInterface;

class ReportService
{
    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @var BaseSerializer
     */
    private $serializer;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Logger
     */
    private $logger;

    /** @var UlnValidatorInterface */
    private $ulnValidator;

    /** @var PedigreeCertificateReportService */
    private $pedigreeCertificateReportService;

    /**
     * ReportService constructor.
     * @param ProducerInterface $producer
     * @param BaseSerializer $serializer
     * @param EntityManager $em
     * @param UserService $userService
     * @param TranslatorInterface $translator
     * @param Logger $logger
     * @param UlnValidatorInterface $ulnValidator
     * @param PedigreeCertificateReportService $pedigreeCertificateReportService
     */
    public function __construct(
        ProducerInterface $producer,
        BaseSerializer $serializer,
        EntityManager $em,
        UserService $userService,
        TranslatorInterface $translator,
        Logger $logger,
        UlnValidatorInterface $ulnValidator,
        PedigreeCertificateReportService $pedigreeCertificateReportService
    )
    {
        $this->em = $em;
        $this->producer = $producer;
        $this->serializer = $serializer;
        $this->userService = $userService;
        $this->translator = $translator;
        $this->logger = $logger;
        $this->ulnValidator = $ulnValidator;
        $this->pedigreeCertificateReportService = $pedigreeCertificateReportService;
    }

    /**
     * @param Request $request
     * @return array|null
     * @throws \Exception
     */
    public function getReports(Request $request): ?array
    {
        $user = $this->userService->getUser();
        $accountOwner = $this->userService->getAccountOwner($request);

        $workers = $this->em->getRepository(ReportWorker::class)->getReports($user, $accountOwner);
        return $this->serializer->getDecodedJson($workers,[JmsGroup::BASIC],true);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createLiveStockReport(Request $request): JsonResponse
    {
        $concatValueAndAccuracy = RequestUtil::getBooleanQuery($request,QueryParameter::CONCAT_VALUE_AND_ACCURACY, false);
        $content = RequestUtil::getContentAsArray($request);
        $contentAsJson = JSON::encode($content->toArray());

        $inputForHash = $contentAsJson . StringUtil::getBooleanAsString($concatValueAndAccuracy);

        try {
            $reportType = ReportType::LIVE_STOCK;

            if ($this->isSimilarNonExpiredReportAlreadyInProgress($request, $reportType, $inputForHash)) {
                return $this->reportWorkerInProgressAlreadyExistErrorResponse();
            }

            $workerId = $this->createWorker($request, $reportType, $inputForHash);
            if(!$workerId)
                return ResultUtil::errorResult('Could not create worker.', Response::HTTP_INTERNAL_SERVER_ERROR);

            $this->producer->sendCommand(WorkerAction::GENERATE_REPORT,
                [
                    'worker_id' => $workerId,
                    'content' => $contentAsJson,
                    'concat_value_and_accuracy' => $concatValueAndAccuracy,
                ]
            );
        }
        catch(\Exception $e) {
            $this->logExceptionAsError($e);
            return ResultUtil::internalServerError();
        }
        return ResultUtil::successResult('OK');
    }

    /**
     * @param Request $request
     * @return \AppBundle\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function createPedigreeCertificates(Request $request)
    {
        $content = RequestUtil::getContentAsArray($request);

        $processAsWorkerTask = RequestUtil::getBooleanQuery($request,QueryParameter::PROCESS_AS_WORKER_TASK,true);

        if ($processAsWorkerTask) {
            $location = $this->userService->getSelectedLocation($request);
            $company = $location ? $location->getCompany() : null;
            $this->ulnValidator->pedigreeCertificateUlnsInputValidation($content, $this->userService->getUser(), $company);
            return $this->createPedigreeCertificatesAsWorkerTask($request);
        }

        return $this->createPedigreeCertificatesWithoutWorker($request);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    private function createPedigreeCertificatesAsWorkerTask(Request $request)
    {
        $content = RequestUtil::getContentAsArray($request);
        $contentAsJson = JSON::encode($content->toArray());
        $inputForHash = $contentAsJson;

        try {
            $reportType = ReportType::PEDIGREE_CERTIFICATE;

            if ($this->isSimilarNonExpiredReportAlreadyInProgress($request, $reportType, $inputForHash)) {
                return $this->reportWorkerInProgressAlreadyExistErrorResponse();
            }

            $workerId = $this->createWorker($request, $reportType, $inputForHash);
            if (!$workerId)
                return ResultUtil::errorResult('Could not create worker.', Response::HTTP_INTERNAL_SERVER_ERROR);

            $this->producer->sendCommand(WorkerAction::GENERATE_REPORT,
                [
                    'worker_id' => $workerId,
                    'content' => $contentAsJson,
                ]
            );
        }
        catch(\Exception $e) {
            $this->logExceptionAsError($e);
            return ResultUtil::internalServerError();
        }
        return ResultUtil::successResult('OK');
    }


    /**
     * @param Request $request
     * @param  $content
     * @return JsonResponse
     * @throws \Exception
     */
    private function createPedigreeCertificatesWithoutWorker(Request $request, $content = null)
    {
        $person = $this->userService->getUser();

        if(!AdminValidator::isAdmin($person, AccessLevelType::ADMIN)) {
            $person = $this->userService->getAccountOwner($request);
        }

        $location = $this->userService->getSelectedLocation($request);
        $fileType = $request->query->get(QueryParameter::FILE_TYPE_QUERY, self::getDefaultFileType());
        $language = $request->query->get(QueryParameter::LANGUAGE, $this->translator->getLocale());
        $content = empty($content) ? RequestUtil::getContentAsArray($request) : $content;

        $report = $this->pedigreeCertificateReportService->getReport($person, $location, $fileType, $content, $language);
        if ($report instanceof Response) {
            return $report;
        }
        return ResultUtil::successResult($report);
    }


    /**
     * @param Request $request
     * @return \AppBundle\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createPedigreeRegisterOverview(Request $request)
    {
        if(!AdminValidator::isAdmin($this->userService->getUser(), AccessLevelType::SUPER_ADMIN)) { //validate if user is at least a SUPER_ADMIN
            return AdminValidator::getStandardErrorResponse();
        }

        $type = $request->query->get(QueryParameter::TYPE_QUERY);
        $uploadToS3 = RequestUtil::getBooleanQuery($request,QueryParameter::S3_UPLOAD, !false);
        $inputForHash = $type . StringUtil::getBooleanAsString($uploadToS3);

        try {
            $reportType = ReportType::PEDIGREE_REGISTER_OVERVIEW;

            if ($this->isSimilarNonExpiredReportAlreadyInProgress($request, $reportType, $inputForHash)) {
                return $this->reportWorkerInProgressAlreadyExistErrorResponse();
            }

            $workerId = $this->createWorker($request, $reportType, $inputForHash);
            if(!$workerId)
                return ResultUtil::errorResult('Could not create worker.', Response::HTTP_INTERNAL_SERVER_ERROR);

            $this->producer->sendCommand(WorkerAction::GENERATE_REPORT,
                [
                    'worker_id' => $workerId,
                    'type' => $type,
                    'upload_to_s3' => $uploadToS3,
                ]
            );
        }
        catch(\Exception $e) {
            $this->logExceptionAsError($e);
            return ResultUtil::internalServerError();
        }
        return ResultUtil::successResult('OK');
    }

    /**
     * @param Request $request
     * @return \AppBundle\Component\HttpFoundation\JsonResponse
     */
    public function createOffspringReport(Request $request)
    {
        $content = RequestUtil::getContentAsArray($request);
        $animalsArray = $content->get(JsonInputConstant::PARENTS);
        if (!is_array($animalsArray)) {
            return ResultUtil::errorResult("'".JsonInputConstant::PARENTS."' key is missing in body", Response::HTTP_BAD_REQUEST);
        }

        if (count($animalsArray) === 0) {
            return ResultUtil::errorResult("Empty input", Response::HTTP_BAD_REQUEST);
        }

        $concatValueAndAccuracy = RequestUtil::getBooleanQuery($request,QueryParameter::CONCAT_VALUE_AND_ACCURACY, false);
        $contentAsJson = JSON::encode($content->toArray());
        $inputForHash = $contentAsJson . StringUtil::getBooleanAsString($concatValueAndAccuracy);

        try {
            $reportType = ReportType::OFFSPRING;

            if ($this->isSimilarNonExpiredReportAlreadyInProgress($request, $reportType, $inputForHash)) {
                return $this->reportWorkerInProgressAlreadyExistErrorResponse();
            }

            $workerId = $this->createWorker($request, $reportType, $inputForHash);
            if(!$workerId)
                return ResultUtil::errorResult('Could not create worker.', Response::HTTP_INTERNAL_SERVER_ERROR);

            $this->producer->sendCommand(WorkerAction::GENERATE_REPORT,
                [
                    'worker_id' => $workerId,
                    'content' => $contentAsJson,
                    'concat_value_and_accuracy' => $concatValueAndAccuracy,
                ]
            );
        }
        catch(\Exception $e) {
            $this->logExceptionAsError($e);
            return ResultUtil::internalServerError();
        }
        return ResultUtil::successResult('OK');
    }

    /**
     * @param Request $request
     * @return \AppBundle\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createAnnualActiveLivestockRamMatesReport(Request $request)
    {
        if(!AdminValidator::isAdmin($this->userService->getUser(), AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        $referenceYear = RequestUtil::getIntegerQuery($request,QueryParameter::YEAR, null);
        if (!$referenceYear) {
            $referenceYear = DateUtil::currentYear() - 1;
        }

        if (!Validator::isYear($referenceYear)) {
            return ResultUtil::errorResult('Invalid reference year', Response::HTTP_PRECONDITION_REQUIRED);
        }

        $inputForHash = $referenceYear;

        try {
            $reportType = ReportType::ANNUAL_ACTIVE_LIVE_STOCK_RAM_MATES;

            if ($this->isSimilarNonExpiredReportAlreadyInProgress($request, $reportType, $inputForHash)) {
                return $this->reportWorkerInProgressAlreadyExistErrorResponse();
            }

            $workerId = $this->createWorker($request, ReportType::ANNUAL_ACTIVE_LIVE_STOCK_RAM_MATES, $inputForHash);
            if(!$workerId)
                return ResultUtil::errorResult('Could not create worker.', Response::HTTP_INTERNAL_SERVER_ERROR);

            $this->producer->sendCommand(WorkerAction::GENERATE_REPORT,
                [
                    'worker_id' => $workerId,
                    'year' => $referenceYear
                ]
            );
        }
        catch(\Exception $e) {
            $this->logExceptionAsError($e);
            return ResultUtil::internalServerError();
        }
        return ResultUtil::successResult('OK');
    }

    /**
     * @param Request $request
     * @return \AppBundle\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function createAnimalsOverviewReport(Request $request)
    {
        if(!AdminValidator::isAdmin($this->userService->getUser(), AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        $concatValueAndAccuracy = RequestUtil::getBooleanQuery($request,QueryParameter::CONCAT_VALUE_AND_ACCURACY, false);
        $pedigreeActiveEndDateLimit = RequestUtil::getDateQuery($request,QueryParameter::PEDIGREE_ACTIVE_END_DATE, new \DateTime());
        $activeUbnReferenceDate = RequestUtil::getDateQuery($request,QueryParameter::REFERENCE_DATE, new \DateTime());

        if (TimeUtil::isDateInFuture($activeUbnReferenceDate)) {
            return ResultUtil::errorResult('Referentie datum kan niet in de toekomst liggen.', Response::HTTP_PRECONDITION_REQUIRED);
        }

        $pedigreeActiveEndDateLimitString = $pedigreeActiveEndDateLimit->format('y-m-d H:i:s');
        $activeUbnReferenceDateString = $activeUbnReferenceDate->format('y-m-d H:i:s');
        $inputForHash = StringUtil::getBooleanAsString($concatValueAndAccuracy) . $pedigreeActiveEndDateLimitString . $activeUbnReferenceDateString;

        try {
            $reportType = ReportType::ANIMALS_OVERVIEW;

            if ($this->isSimilarNonExpiredReportAlreadyInProgress($request, $reportType, $inputForHash)) {
                return $this->reportWorkerInProgressAlreadyExistErrorResponse();
            }

            $workerId = $this->createWorker($request, $reportType, $inputForHash);
            if(!$workerId)
                return ResultUtil::errorResult('Could not create worker.', Response::HTTP_INTERNAL_SERVER_ERROR);

            $this->producer->sendCommand(WorkerAction::GENERATE_REPORT,
                [
                    'worker_id' => $workerId,
                    'concat_value_and_accuracy' => $concatValueAndAccuracy,
                    'pedigree_active_end_date_limit' => $pedigreeActiveEndDateLimitString,
                    'active_ubn_reference_date_string' => $activeUbnReferenceDateString,
                ]
            );
        }
        catch(\Exception $e) {
            $this->logExceptionAsError($e);
            return ResultUtil::internalServerError();
        }
        return ResultUtil::successResult('OK');
    }

    /**
     * @param Request $request
     * @return \AppBundle\Component\HttpFoundation\JsonResponse
     */
    public function createInbreedingCoefficientsReport(Request $request)
    {
        $content = RequestUtil::getContentAsArray($request);
        $contentAsJson = JSON::encode($content->toArray());
        $inputForHash = $contentAsJson;

        try {
            $reportType = ReportType::INBREEDING_COEFFICIENT;

            if ($this->isSimilarNonExpiredReportAlreadyInProgress($request, $reportType, $inputForHash)) {
                return $this->reportWorkerInProgressAlreadyExistErrorResponse();
            }

            $workerId = $this->createWorker($request, $reportType, $inputForHash);
            if(!$workerId)
                return ResultUtil::errorResult('Could not create worker.', Response::HTTP_INTERNAL_SERVER_ERROR);

            $this->producer->sendCommand(WorkerAction::GENERATE_REPORT,
                [
                    'worker_id' => $workerId,
                    'content' => $contentAsJson,
                ]
            );
        }
        catch(\Exception $e) {
            $this->logExceptionAsError($e);
            return ResultUtil::internalServerError();
        }
        return ResultUtil::successResult('OK');
    }

    /**
     * @param Request $request
     * @return \AppBundle\Component\HttpFoundation\JsonResponse
     */
    public function createFertilizerAccountingReport(Request $request)
    {
        $referenceDate = RequestUtil::getDateQuery($request,QueryParameter::REFERENCE_DATE, new \DateTime());
        $referenceDateString = $referenceDate->format('y-m-d H:i:s');
        $inputForHash = $referenceDateString;

        try {
            $reportType = ReportType::FERTILIZER_ACCOUNTING;

            if ($this->isSimilarNonExpiredReportAlreadyInProgress($request, $reportType, $inputForHash)) {
                return $this->reportWorkerInProgressAlreadyExistErrorResponse();
            }

            $workerId = $this->createWorker($request, $reportType, $inputForHash);
            if(!$workerId)
                return ResultUtil::errorResult('Could not create worker.', Response::HTTP_INTERNAL_SERVER_ERROR);

            $this->producer->sendCommand(WorkerAction::GENERATE_REPORT,
                [
                    'worker_id' => $workerId,
                    'reference_date' => $referenceDateString,
                ]
            );
        }
        catch(\Exception $e) {
            $this->logExceptionAsError($e);
            return ResultUtil::internalServerError();
        }
        return ResultUtil::successResult('OK');
    }

    /**
     * @param Request $request
     * @return \AppBundle\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createAnnualTe100UbnProductionReport(Request $request)
    {
        if(!AdminValidator::isAdmin($this->userService->getUser(), AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        $year = RequestUtil::getIntegerQuery($request,QueryParameter::YEAR, null);
        if (!$year) {
            return ResultUtil::errorResult('Invalid reference year', Response::HTTP_PRECONDITION_REQUIRED);
        }

        $pedigreeActiveEndDateLimit = RequestUtil::getDateQuery($request,QueryParameter::END_DATE, new \DateTime());
        $pedigreeActiveEndDateLimitString = $pedigreeActiveEndDateLimit->format('y-m-d H:i:s');

        $inputForHash = $year . $pedigreeActiveEndDateLimitString;

        try {
            $reportType = ReportType::ANNUAL_TE_100;

            if ($this->isSimilarNonExpiredReportAlreadyInProgress($request, $reportType, $inputForHash)) {
                return $this->reportWorkerInProgressAlreadyExistErrorResponse();
            }

            $workerId = $this->createWorker($request, $reportType, $inputForHash);
            if(!$workerId)
                return ResultUtil::errorResult('Could not create worker.', Response::HTTP_INTERNAL_SERVER_ERROR);

            $this->producer->sendCommand(WorkerAction::GENERATE_REPORT,
                [
                    'worker_id' => $workerId,
                    'year' => $year,
                    'pedigree_active_end_date' => $pedigreeActiveEndDateLimitString,
                ]
            );
        }
        catch(\Exception $e) {
            $this->logExceptionAsError($e);
            return ResultUtil::internalServerError();
        }
        return ResultUtil::successResult('OK');
    }

    /**
     * @param Request $request
     * @return \AppBundle\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createAnnualActiveLivestockReport(Request $request)
    {
        if(!AdminValidator::isAdmin($this->userService->getUser(), AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        $referenceYear = RequestUtil::getIntegerQuery($request,QueryParameter::YEAR, null);
        if (!$referenceYear) {
            $referenceYear = DateUtil::currentYear() - 1;
        }

        if (!Validator::isYear($referenceYear)) {
            return ResultUtil::errorResult('Invalid reference year', Response::HTTP_PRECONDITION_REQUIRED);
        }

        $inputForHash = $referenceYear;

        try {
            $reportType = ReportType::ANNUAL_ACTIVE_LIVE_STOCK;

            if ($this->isSimilarNonExpiredReportAlreadyInProgress($request, $reportType, $inputForHash)) {
                return $this->reportWorkerInProgressAlreadyExistErrorResponse();
            }

            $workerId = $this->createWorker($request, $reportType, $inputForHash);
            if(!$workerId)
                return ResultUtil::errorResult('Could not create worker.', Response::HTTP_INTERNAL_SERVER_ERROR);

            $this->producer->sendCommand(WorkerAction::GENERATE_REPORT,
                [
                    'worker_id' => $workerId,
                    'year' => $referenceYear
                ]
            );
        }
        catch(\Exception $e) {
            $this->logExceptionAsError($e);
            return ResultUtil::internalServerError();
        }
        return ResultUtil::successResult('OK');
    }

    /**
     * @param Request $request
     * @param int $reportType
     * @param string $inputForHash
     * @return bool
     * @throws \Exception
     */
    private function isSimilarNonExpiredReportAlreadyInProgress(Request $request, int $reportType, string $inputForHash): bool
    {
        $reportWorkerHash = $this->getReportWorkerHash($request, $reportType, $inputForHash);

        return $this->em->getRepository(ReportWorker::class)->isSimilarNonExpiredReportAlreadyInProgress($reportWorkerHash);
    }

    private function createWorker(Request $request, int $reportType, $inputForHash = '') : ?int
    {
        try {
            $reportWorker = new ReportWorker();
            $reportWorker->setOwner($this->userService->getAccountOwner($request));
            $reportWorker->setActionBy($this->userService->getUser());
            $reportWorker->setLocation($this->userService->getSelectedLocation($request));

            $fileType = $request->query->get(QueryParameter::FILE_TYPE_QUERY, self::getDefaultFileType());
            $language = $request->query->get(QueryParameter::LANGUAGE, $this->translator->getLocale());

            $reportWorker->setReportType($reportType);
            $reportWorker->setLocale($language);
            $reportWorker->setFileType($fileType);
            $reportWorker->setHash($this->getReportWorkerHash($request, $reportType, $inputForHash));
            $this->em->persist($reportWorker);
            $this->em->flush();

            return $reportWorker->getId();
        }
        catch(\Exception $e) {
            $this->logExceptionAsError($e);
        }

        return null;
    }


    /**
     * @param Request $request
     * @param int $reportType
     * @param string $inputForHash
     * @return string
     * @throws \Exception
     */
    private function getReportWorkerHash(Request $request, int $reportType, string $inputForHash): string
    {
        $workerType = WorkerType::REPORT;

        $fileType = $request->query->get(QueryParameter::FILE_TYPE_QUERY, self::getDefaultFileType());
        $language = $request->query->get(QueryParameter::LANGUAGE, $this->translator->getLocale());

        $accountOwner = $this->userService->getAccountOwner($request);
        $user = $this->userService->getUser();
        $location = $this->userService->getSelectedLocation($request);

        $metaDataForHash =
            $workerType.'-'.
            $reportType.'-'.
            $user->getId().'-'.
            ($accountOwner ? $accountOwner->getId() : '0').'-'.
            ($location ? $location->getId() : '0').'-'.
            $fileType.'-'.
            $language
        ;

        return hash('sha256', $inputForHash . $metaDataForHash);
    }


    /**
     * @return string
     */
    private static function getDefaultFileType(): string
    {
        return FileType::CSV;
    }


    /**
     * @return JsonResponse
     */
    private function reportWorkerInProgressAlreadyExistErrorResponse(): JsonResponse
    {
        $message = $this->translator->trans('A SIMILAR REPORT IS ALREADY BEING GENERATED');
        return ResultUtil::errorResult($message, Response::HTTP_PRECONDITION_REQUIRED);
    }


    /**
     * @return \DateTime
     * @throws \Exception
     */
    public static function getMaxNonExpiredDate(): \DateTime
    {
        $date = new \DateTime();//now
        $interval = new \DateInterval('P1D');// P[eriod] 1 D[ay]
        $date->sub($interval);
        return $date;
    }

    /**
     * @param \Exception $exception
     */
    public function logExceptionAsError($exception)
    {
        $this->logger->error($exception->getMessage());
        $this->logger->error($exception->getTraceAsString());
    }


    /**
     * @param Request $request
     * @param $reportType
     * @param $base64encodedBody
     * @return JsonResponse
     * @throws \Exception
     */
    public function testReportTemplate(Request $request, $reportType, $base64encodedBody)
    {
        $this->userService->validateDevToken($request);
        $body = base64_decode($base64encodedBody);

        $content = new ArrayCollection(json_decode($body, true));

        switch ($reportType) {
            case ReportType::PEDIGREE_CERTIFICATE:
                return $this->createPedigreeCertificatesWithoutWorker($request, $content);
            default:
                throw new PreconditionFailedHttpException('INVALID REPORT TYPE'
                    .'. '.$this->getValidTestReportTypesErrorMessage());
        }
    }

    /**
     * @return string
     */
    private function getValidTestReportTypesErrorMessage(): string
    {

        $allReportTypes = ReportType::getConstants();
        $validReportTypePairs = array_filter($allReportTypes, function ($reportTypeEnum) {
                return in_array($reportTypeEnum, [
                        ReportType::PEDIGREE_CERTIFICATE,
                    ]
                );
        }, ARRAY_FILTER_USE_BOTH);

        return 'VALID REPORT TYPES: '. ArrayUtil::implode($validReportTypePairs);
    }
}