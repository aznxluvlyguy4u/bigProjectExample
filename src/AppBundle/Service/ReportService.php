<?php


namespace AppBundle\Service;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Option\BirthListReportOptions;
use AppBundle\Component\Option\ClientNotesOverviewReportOptions;
use AppBundle\Component\Option\CompanyRegisterReportOptions;
use AppBundle\Component\Option\MembersAndUsersOverviewReportOptions;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\TranslationKey;
use AppBundle\Entity\Company;
use AppBundle\Entity\Location;
use AppBundle\Entity\PedigreeRegister;
use AppBundle\Entity\ReportWorker;
use AppBundle\Entity\Token;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Enumerator\ReportType;
use AppBundle\Enumerator\WorkerAction;
use AppBundle\Enumerator\WorkerType;
use AppBundle\Exception\InvalidBreedCodeHttpException;
use AppBundle\Exception\InvalidPedigreeRegisterAbbreviationHttpException;
use AppBundle\Service\Report\BirthListReportService;
use AppBundle\Service\Report\ClientNotesOverviewReportService;
use AppBundle\Service\Report\CompanyRegisterReportService;
use AppBundle\Service\Report\FertilizerAccountingReport;
use AppBundle\Service\Report\InbreedingCoefficientReportService;
use AppBundle\Service\Report\LiveStockReportService;
use AppBundle\Service\Report\MembersAndUsersOverviewReportService;
use AppBundle\Service\Report\PedigreeCertificateReportService;
use AppBundle\Service\Report\PopRepInputFileService;
use AppBundle\Service\Report\WeightsPerYearOfBirthReportService;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\BreedCodeUtil;
use AppBundle\Util\DateUtil;
use AppBundle\Util\NullChecker;
use AppBundle\Util\ReportUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\Validator;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\UlnValidatorInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Enqueue\Client\ProducerInterface;
use Enqueue\Util\JSON;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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

    /** @var LiveStockReportService */
    private $livestockReportService;

    /** @var BirthListReportService */
    private $birthListReportService;

    /** @var MembersAndUsersOverviewReportService */
    private $membersAndUsersOverviewReport;

    /** @var CompanyRegisterReportService */
    private $companyRegisterReportService;

    /** @var ClientNotesOverviewReportService */
    private $clientNotesOverviewReportService;

    /** @var InbreedingCoefficientReportService */
    private $inbreedingCoefficientReportService;

    /** @var WeightsPerYearOfBirthReportService */
    private $weightsPerYearOfBirthReportService;

    /** @var PopRepInputFileService */
    private $popRepInputFileService;

    /** @var FertilizerAccountingReport */
    private $fertilizerAccountingReport;

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
     * @param LiveStockReportService $livestockReportService
     * @param BirthListReportService $birthListReportService
     * @param MembersAndUsersOverviewReportService $membersAndUsersOverviewReport
     * @param CompanyRegisterReportService $companyRegisterReportService
     * @param ClientNotesOverviewReportService $clientNotesOverviewReportService
     * @param InbreedingCoefficientReportService $inbreedingCoefficientReportService
     * @param WeightsPerYearOfBirthReportService $weightsPerYearOfBirthReportService
     * @param PopRepInputFileService $popRepInputFileService
     * @param FertilizerAccountingReport $fertilizerAccountingReport
     */
    public function __construct(
        ProducerInterface $producer,
        BaseSerializer $serializer,
        EntityManager $em,
        UserService $userService,
        TranslatorInterface $translator,
        Logger $logger,
        UlnValidatorInterface $ulnValidator,
        PedigreeCertificateReportService $pedigreeCertificateReportService,
        LiveStockReportService $livestockReportService,
        BirthListReportService $birthListReportService,
        MembersAndUsersOverviewReportService $membersAndUsersOverviewReport,
        CompanyRegisterReportService $companyRegisterReportService,
        ClientNotesOverviewReportService $clientNotesOverviewReportService,
        InbreedingCoefficientReportService $inbreedingCoefficientReportService,
        WeightsPerYearOfBirthReportService $weightsPerYearOfBirthReportService,
        PopRepInputFileService $popRepInputFileService,
        FertilizerAccountingReport $fertilizerAccountingReport
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
        $this->livestockReportService = $livestockReportService;
        $this->birthListReportService = $birthListReportService;
        $this->membersAndUsersOverviewReport = $membersAndUsersOverviewReport;
        $this->companyRegisterReportService = $companyRegisterReportService;
        $this->clientNotesOverviewReportService = $clientNotesOverviewReportService;
        $this->inbreedingCoefficientReportService = $inbreedingCoefficientReportService;
        $this->weightsPerYearOfBirthReportService = $weightsPerYearOfBirthReportService;
        $this->popRepInputFileService = $popRepInputFileService;
        $this->fertilizerAccountingReport = $fertilizerAccountingReport;
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
     * @throws \Exception
     */
    public function createLiveStockReport(Request $request)
    {
        $processAsWorkerTask = RequestUtil::getBooleanQuery($request,QueryParameter::PROCESS_AS_WORKER_TASK,true);

        if ($processAsWorkerTask) {
            return $this->createLiveStockReportAsWorkerTask($request);
        }

        return $this->createLiveStockReportWithoutWorker($request);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    private function createLiveStockReportWithoutWorker(Request $request)
    {
        $person = $this->userService->getUser();

        if(!AdminValidator::isAdmin($person, AccessLevelType::ADMIN)) {
            $person = $this->userService->getAccountOwner($request);
        }

        $location = $this->userService->getSelectedLocation($request);
        $fileType = $request->query->get(QueryParameter::FILE_TYPE_QUERY, self::getDefaultFileType());
        $language = $request->query->get(QueryParameter::LANGUAGE, $this->translator->getLocale());
        $content = RequestUtil::getContentAsArray($request);
        $concatValueAndAccuracy = RequestUtil::getBooleanQuery($request,QueryParameter::CONCAT_VALUE_AND_ACCURACY, false);

        $report = $this->livestockReportService->getReport($person, $location, $fileType, $concatValueAndAccuracy, $content,$language);
        if ($report instanceof Response) {
            return $report;
        }
        return ResultUtil::successResult($report);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    private function createLiveStockReportAsWorkerTask(Request $request): JsonResponse
    {
        $concatValueAndAccuracy = RequestUtil::getBooleanQuery($request,QueryParameter::CONCAT_VALUE_AND_ACCURACY, false);
        $content = RequestUtil::getContentAsArray($request);
        $contentAsJson = JSON::encode($content->toArray());

        $inputForHash = $contentAsJson . StringUtil::getBooleanAsString($concatValueAndAccuracy);

        return $this->processReportAsWorkerTask(
            [
                'content' => $contentAsJson,
                'concat_value_and_accuracy' => $concatValueAndAccuracy,
            ],
            $request,ReportType::LIVE_STOCK, $inputForHash
        );
    }


    /**
     * @param \Exception $e
     * @param int|null $workerId
     */
    private function processWorkerError(\Exception $e, int $workerId = null)
    {
        $this->logExceptionAsError($e);
        if ($workerId) {
            $workerRecord = $this->em->getRepository(ReportWorker::class)->find($workerId);
            if ($workerRecord) {
                try {
                    $this->em->remove($workerRecord);
                    $this->em->flush();
                } catch (ORMException $ORMException) {
                    $this->logExceptionAsError($ORMException);
                }
            }
        }
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

            $contentAsJson = JSON::encode($content->toArray());
            $inputForHash = $contentAsJson;

            return $this->processReportAsWorkerTask(
                [
                    'content' => $contentAsJson,
                ],
                $request,ReportType::PEDIGREE_CERTIFICATE, $inputForHash
            );
        }

        return $this->createPedigreeCertificatesWithoutWorker($request);
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

        return $this->processReportAsWorkerTask(
            [
                'type' => $type,
                'upload_to_s3' => $uploadToS3,
            ],
            $request,ReportType::PEDIGREE_REGISTER_OVERVIEW, $inputForHash
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function createPopRepReport(Request $request)
    {
        $actionBy = $this->userService->getUser();

        AdminValidator::isAdmin($actionBy, AccessLevelType::ADMIN, true);

        $pedigreeRegisterAbbreviation = $request->query->get(QueryParameter::PEDIGREE_REGISTER);

        if ($pedigreeRegisterAbbreviation == null) {
            throw new BadRequestHttpException('Missing pedigree register');
        }

        $pedigreeRegisterAbbreviation = strtoupper($pedigreeRegisterAbbreviation);
        $pedigreeRegister = $this->em->getRepository(PedigreeRegister::class)
            ->findOneByAbbreviation($pedigreeRegisterAbbreviation);

        if (!$pedigreeRegister) {
            throw new InvalidPedigreeRegisterAbbreviationHttpException($this->translator,strval($pedigreeRegisterAbbreviation));
        }

        // Set file type as TXT. This value will be saved in the ReportWorker table
        $request->query->set(QueryParameter::FILE_TYPE_QUERY, FileType::TXT);

        $processAsWorkerTask = RequestUtil::getBooleanQuery($request,QueryParameter::PROCESS_AS_WORKER_TASK,true);

        $inputForHash = $pedigreeRegisterAbbreviation;

        if ($processAsWorkerTask) {
            return $this->processReportAsWorkerTask(
                [
                    'pedigree_register_abbreviation' => $pedigreeRegisterAbbreviation
                ],
                $request,ReportType::POPREP_INPUT_FILE, $inputForHash
            );
        }

        return $this->popRepInputFileService->getReport($pedigreeRegisterAbbreviation);
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

        return $this->processReportAsWorkerTask(
            [
                'content' => $contentAsJson,
                'concat_value_and_accuracy' => $concatValueAndAccuracy,
            ],
            $request,ReportType::OFFSPRING, $inputForHash
        );
    }

    /**
     * @param Request $request
     * @return \AppBundle\Component\HttpFoundation\JsonResponse
     */
    public function createAnimalHealthStatusReport(Request $request)
    {
        $inputForHash = '';

        return $this->processReportAsWorkerTask(
            [],
            $request,ReportType::ANIMAL_HEALTH_STATUSES, $inputForHash
        );
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

        return $this->processReportAsWorkerTask(
            [
                'year' => $referenceYear
            ],
            $request,ReportType::ANNUAL_ACTIVE_LIVE_STOCK_RAM_MATES, $inputForHash
        );
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

        return $this->processReportAsWorkerTask(
            [
                'concat_value_and_accuracy' => $concatValueAndAccuracy,
                'pedigree_active_end_date_limit' => $pedigreeActiveEndDateLimitString,
                'active_ubn_reference_date_string' => $activeUbnReferenceDateString,
            ],
            $request,ReportType::ANIMALS_OVERVIEW, $inputForHash
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function createInbreedingCoefficientsReport(Request $request)
    {
        $content = RequestUtil::getContentAsArray($request);
        $contentAsJson = JSON::encode($content->toArray());
        $inputForHash = $contentAsJson;

        $processAsWorkerTask = RequestUtil::getBooleanQuery($request,QueryParameter::PROCESS_AS_WORKER_TASK,true);

        if ($processAsWorkerTask) {
            return $this->processReportAsWorkerTask(
                [
                    'content' => $contentAsJson,
                ],
                $request,ReportType::INBREEDING_COEFFICIENT, $inputForHash
            );
        }

        $actionBy = $this->userService->getUser();
        $fileType = $request->query->get(QueryParameter::FILE_TYPE_QUERY, self::getDefaultFileType());
        $language = $request->query->get(QueryParameter::LANGUAGE, $this->translator->getLocale());

        return $this->inbreedingCoefficientReportService->getReport(
            $actionBy, $content, $fileType, $language
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function createFertilizerAccountingReport(Request $request)
    {
        $referenceDate = RequestUtil::getDateQuery($request,QueryParameter::REFERENCE_DATE, new \DateTime());
        $referenceDateString = $referenceDate->format('y-m-d H:i:s');
        $inputForHash = $referenceDateString;

        $processAsWorkerTask = RequestUtil::getBooleanQuery($request,QueryParameter::PROCESS_AS_WORKER_TASK,true);

        $this->fertilizerAccountingReport->validateReferenceDate($referenceDate);
        
        if ($processAsWorkerTask) {
            return $this->processReportAsWorkerTask(
                [
                    'reference_date' => $referenceDateString,
                ],
                $request,ReportType::FERTILIZER_ACCOUNTING, $inputForHash
            );
        }

        $location = $this->userService->getSelectedLocation($request);
        $fileType = $request->query->get(QueryParameter::FILE_TYPE_QUERY, self::getDefaultFileType());

        return $this->fertilizerAccountingReport->getReport($location, $referenceDate, $fileType);
    }

    /**
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
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

        return $this->processReportAsWorkerTask(
            [
                'year' => $year,
                'pedigree_active_end_date' => $pedigreeActiveEndDateLimitString,
            ],
            $request,ReportType::ANNUAL_TE_100, $inputForHash
        );
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

        return $this->processReportAsWorkerTask(
            [
                'year' => $referenceYear
            ],
            $request,ReportType::ANNUAL_ACTIVE_LIVE_STOCK, $referenceYear
        );
    }


    /**
     * @param Request $request
     * @return \AppBundle\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createBirthListReport(Request $request)
    {
        $actionBy = $this->userService->getUser();
        $location = $this->userService->getSelectedLocation($request);

        NullChecker::checkLocation($location);
        BirthListReportService::validateUser($actionBy, $location);

        $breedCode = $request->query->get(QueryParameter::BREED_CODE);
        if ($breedCode !== null) {
            $breedCode = strtoupper($breedCode);
            if (!BreedCodeUtil::isValidBreedCodeString($breedCode)) {
                throw new InvalidBreedCodeHttpException($this->translator, strval($breedCode));
            }
        }

        $pedigreeRegisterAbbreviation = $request->query->get(QueryParameter::PEDIGREE_REGISTER);
        if ($pedigreeRegisterAbbreviation !== null) {
            $pedigreeRegisterAbbreviation = strtoupper($pedigreeRegisterAbbreviation);
            $pedigreeRegister = $this->em->getRepository(PedigreeRegister::class)
                ->findOneByAbbreviation($pedigreeRegisterAbbreviation);
            if (!$pedigreeRegister) {
                throw new InvalidPedigreeRegisterAbbreviationHttpException($this->translator,strval($pedigreeRegisterAbbreviation));
            }
        }

        $language = $request->query->get(QueryParameter::LANGUAGE, $this->translator->getLocale());

        // Set file type as PDF. This value will be saved in the ReportWorker table
        $request->query->set(QueryParameter::FILE_TYPE_QUERY, FileType::PDF);

        $options = (new BirthListReportOptions())
            ->setLanguage($language)
            ->setPedigreeRegisterAbbreviation($pedigreeRegisterAbbreviation)
            ->setBreedCode($breedCode)
        ;

        $optionsAsJson = $this->serializer->serializeToJSON($options);

        $processAsWorkerTask = RequestUtil::getBooleanQuery($request,QueryParameter::PROCESS_AS_WORKER_TASK,true);

        if ($processAsWorkerTask) {
            return $this->processReportAsWorkerTask(
                [
                    'options' => $optionsAsJson
                ],
                $request,ReportType::BIRTH_LIST, $optionsAsJson
            );
        }

        return $this->birthListReportService->getReport($actionBy, $location, $options);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function createCompanyRegisterReport(Request $request)
    {
        $actionBy = $this->userService->getUser();
        $location = $this->userService->getSelectedLocation($request);

        NullChecker::checkLocation($location);

        $sampleDateString = $request->query->get(QueryParameter::SAMPLE_DATE);
        $sampleDate = empty($sampleDateString) ? new \DateTime() : new \DateTime($sampleDateString);

        ReportUtil::validateDateIsNotOlderThanOldestAutomatedSync($sampleDate, TranslationKey::SAMPLE_DATE, $this->translator);
        ReportUtil::validateDateIsNotInTheFuture($sampleDate, TranslationKey::SAMPLE_DATE, $this->translator);

        $fileType = $request->query->get(QueryParameter::FILE_TYPE_QUERY, self::getDefaultFileType());
        $allowedFileTypes = [FileType::CSV, FileType::PDF];
        ReportUtil::validateFileType($fileType, $allowedFileTypes, $this->translator);

        $options = (new CompanyRegisterReportOptions())
            ->setFileType($fileType)
            ->setSampleDate($sampleDate)
        ;

        $optionsAsJson = $this->serializer->serializeToJSON($options);

        $processAsWorkerTask = RequestUtil::getBooleanQuery($request,QueryParameter::PROCESS_AS_WORKER_TASK,true);

        if ($processAsWorkerTask) {
            return $this->processReportAsWorkerTask(
                [
                    'options' => $optionsAsJson
                ],
                $request,ReportType::COMPANY_REGISTER, $optionsAsJson
            );
        }

        return $this->companyRegisterReportService->getReport($actionBy, $location, $options);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function createWeightsPerYearOfBirthReport(Request $request)
    {
        /** @var Location $location */
        $location = null;

        // not admin
        if ($this->userService->isRequestFromUserFrontend($request)) {
            $location = $this->userService->getSelectedLocation($request);
            NullChecker::checkLocation($location);
        }

        $yearOfBirth = RequestUtil::getIntegerQuery($request,QueryParameter::YEAR_OF_BIRTH, null);

        if (!$yearOfBirth) {
            return ResultUtil::errorResult('Invalid year of birth', Response::HTTP_PRECONDITION_REQUIRED);
        }

        $ubn = is_null($location) ? "" : $location->getUbn();
        $inputForHash = $yearOfBirth . $ubn;
        $processAsWorkerTask = RequestUtil::getBooleanQuery($request,QueryParameter::PROCESS_AS_WORKER_TASK,true);

        if ($processAsWorkerTask) {
            return $this->processReportAsWorkerTask(
                [
                    'year_of_birth' => $yearOfBirth
                ],
                $request,ReportType::WEIGHTS_PER_YEAR_OF_BIRTH, $inputForHash
            );
        }

        return $this->weightsPerYearOfBirthReportService->getReport($yearOfBirth, $location);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function createClientNotesOverviewReport(Request $request)
    {
        $actionBy = $this->userService->getUser();
        $companyId = $request->query->get(
            QueryParameter::COMPANY_ID,
            ClientNotesOverviewReportOptions::getCompanyIdEmptyValue()
        );

        if (!empty($companyId)) {
            $company = $this->em->getRepository(Company::class)
                ->findOneByCompanyId($companyId);
            if (empty($company)) {
                throw new BadRequestHttpException("No company was found for given companyId: ".$companyId);
            }
        }

        $startDate = RequestUtil::getDateQuery($request,QueryParameter::START_DATE, null, true, true);
        $endDate = RequestUtil::getDateQuery($request,QueryParameter::END_DATE, null, true, true);

        $fileType = $request->query->get(QueryParameter::FILE_TYPE_QUERY, self::getDefaultFileType());
        ReportUtil::validateFileType($fileType, ClientNotesOverviewReportService::allowedFileTypes(), $this->translator);

        $options = (new ClientNotesOverviewReportOptions())
            ->setFileType($fileType)
            ->setCompanyId($companyId)
            ->setStartDate($startDate)
            ->setEndDate($endDate)
        ;

        $optionsAsJson = $this->serializer->serializeToJSON($options);
        $processAsWorkerTask = RequestUtil::getBooleanQuery($request,QueryParameter::PROCESS_AS_WORKER_TASK,true);

        if ($processAsWorkerTask) {
            return $this->processReportAsWorkerTask(
                [
                    'options' => $optionsAsJson
                ],
                $request,ReportType::CLIENT_NOTES_OVERVIEW, $optionsAsJson
            );
        }

        return $this->clientNotesOverviewReportService->getReport($actionBy, $options);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function createMembersAndUsersOverviewReportService(Request $request)
    {
        $actionBy = $this->userService->getUser();

        AdminValidator::isAdmin($actionBy, AccessLevelType::ADMIN, true);

        $referenceDateString = $request->query->get(QueryParameter::REFERENCE_DATE);
        $referenceDate = empty($referenceDateString) ? new \DateTime() : new \DateTime($referenceDateString);

        if (TimeUtil::isDateInFuture($referenceDate)) {
            throw new PreconditionFailedHttpException(ucfirst(strtolower(
                $this->translator->trans('REFERENCE DATE CANNOT BE IN THE FUTURE')
            )));
        }

        $mustHaveAnimalHealthSubscription = RequestUtil::getBooleanQuery($request,QueryParameter::MUST_HAVE_ANIMAL_HEALTH_SUBSCRIPTION,false);

        $pedigreeRegisterAbbreviation = $request->query->get(QueryParameter::PEDIGREE_REGISTER);
        if ($pedigreeRegisterAbbreviation !== null) {
            $pedigreeRegisterAbbreviation = strtoupper($pedigreeRegisterAbbreviation);
            $pedigreeRegister = $this->em->getRepository(PedigreeRegister::class)
                ->findOneByAbbreviation($pedigreeRegisterAbbreviation);
            if (!$pedigreeRegister) {
                throw new InvalidPedigreeRegisterAbbreviationHttpException($this->translator,strval($pedigreeRegisterAbbreviation));
            }
        }

        $language = $request->query->get(QueryParameter::LANGUAGE, $this->translator->getLocale());

        // Set file type as CSV. This value will be saved in the ReportWorker table
        $request->query->set(QueryParameter::FILE_TYPE_QUERY, FileType::CSV);

        $options = (new MembersAndUsersOverviewReportOptions())
            ->setReferenceDate($referenceDate)
            ->setMustHaveAnimalHealthSubscription($mustHaveAnimalHealthSubscription)
            ->setPedigreeRegisterAbbreviation($pedigreeRegisterAbbreviation)
            ->setLanguage($language)
        ;

        $optionsAsJson = $this->serializer->serializeToJSON($options);

        $processAsWorkerTask = RequestUtil::getBooleanQuery($request,QueryParameter::PROCESS_AS_WORKER_TASK,true);

        if ($processAsWorkerTask) {
            return $this->processReportAsWorkerTask(
                [
                    'options' => $optionsAsJson
                ],
                $request,ReportType::MEMBERS_AND_USERS_OVERVIEW, $optionsAsJson
            );
        }

        return $this->membersAndUsersOverviewReport->getReport($options);
    }


    private function processReportAsWorkerTask(array $messageBodyAsArray, Request $request, string $reportType, string $inputForHash)
    {
        $workerId = null;
        try {

            if ($this->isSimilarNonExpiredReportAlreadyInProgress($request, $reportType, $inputForHash)) {
                return $this->reportWorkerInProgressAlreadyExistErrorResponse();
            }

            $workerId = $this->createWorker($request, $reportType, $inputForHash);
            if (!$workerId) {
                return ResultUtil::errorResult('Could not create worker.', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $messageBodyAsArray['worker_id'] = $workerId;
            $this->producer->sendCommand(WorkerAction::GENERATE_REPORT, $messageBodyAsArray);
        }
        catch(\Exception $e) {
            $this->processWorkerError($e, $workerId);
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
        $request = $this->setLocationAndClientHeadersByLocationIdQueryParameter($request);

        $body = empty($base64encodedBody) ? null :base64_decode($base64encodedBody);
        $isEmptyBody = empty($body) || $body === '{}';

        $content = new ArrayCollection(($isEmptyBody ? json_decode($body, true) : null));
        $request->query->set(QueryParameter::PROCESS_AS_WORKER_TASK, 'false');

        switch ($reportType) {
            case ReportType::PEDIGREE_CERTIFICATE:
                return $this->createPedigreeCertificatesWithoutWorker($request, $content);
            case ReportType::BIRTH_LIST:
                return $this->createBirthListReport($request);
            default:
                throw new PreconditionFailedHttpException('INVALID REPORT TYPE'
                    .'. '.$this->getValidTestReportTypesErrorMessage());
        }
    }


    /**
     * @param Request $request
     * @return Request
     */
    private function setLocationAndClientHeadersByLocationIdQueryParameter(Request $request)
    {
        $locationId = $request->query->get(QueryParameter::LOCATION_ID);
        if (is_int($locationId) || ctype_digit($locationId)) {
            $locationId = intval($locationId);
            $location = $this->em->getRepository(Location::class)->find($locationId);
            if ($location) {
                $request->headers->set('ubn', $location->getUbn());
                $owner = $location->getOwner();

                $token = $this->em->getRepository(Token::class)->findByClientIdPrioritizedByGhostTokenType($owner);
                if (!$token) {
                    throw new PreconditionFailedHttpException('Owner for location ' . $locationId
                        . '/ubn ' . $location->getUbn() . ' has no token');
                }

                $request->headers->set('GhostToken', $token->getCode());
            }
        }
        return $request;
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
