<?php


namespace AppBundle\Service;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\ReportWorker;
use AppBundle\Entity\Worker;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Enumerator\ReportType;
use AppBundle\Enumerator\WorkerType;
use AppBundle\Util\DateUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\Validator;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\UlnValidator;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Enqueue\Client\ProducerInterface;
use Enqueue\Util\JSON;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;

class ReportService
{
    /**
     * @var ProducerInterface
     */
    private $producer;

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
     * ReportService constructor.
     * @param ProducerInterface $producer
     * @param EntityManager $em
     * @param UserService $userService
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ProducerInterface $producer,
        EntityManager $em,
        UserService $userService,
        TranslatorInterface $translator
    )
    {
        $this->em = $em;
        $this->producer = $producer;
        $this->userService = $userService;
        $this->translator = $translator;
    }

    /**
     * @param Request $request
     * @return array|null
     * @throws \Exception
     */
    public function getReports(Request $request): ?array
    {
        $date = new \DateTime();//now
        $interval = new \DateInterval('P1M');// P[eriod] 1 M[onth]
        $date->sub($interval);
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('owner', $this->userService->getUser()))
            ->where(Criteria::expr()->gte('startedAt', $date))
            ->orderBy(['startedAt' => Criteria::DESC])
        ;
        $workers = $this->em->getRepository(Worker::class)->matching($criteria);
        return $workers->toArray();
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createLiveStockReport(Request $request): JsonResponse
    {
        $concatValueAndAccuracy = RequestUtil::getBooleanQuery($request,QueryParameter::CONCAT_VALUE_AND_ACCURACY, self::false);
        $content = RequestUtil::getContentAsArray($request);

        //Validate if given ULNs are correct AND there should at least be one ULN given
        $ulnValidator = new UlnValidator($this->em, $content, true, null, $this->userService->getSelectedLocation($request));
        if(!$ulnValidator->getIsUlnSetValid()) {
            return $ulnValidator->createArrivalJsonErrorResponse();
        }

        try {
            $workerId = $this->createWorker($request, WorkerType::REPORT, ReportType::LIVE_STOCK);
            if(!$workerId)
                return ResultUtil::errorResult('Could not create worker.', Response::HTTP_INTERNAL_SERVER_ERROR);

            $this->producer->sendCommand('generate_pdf',
                [
                    'worker_id' => $workerId,
                    'content' => JSON::encode($content->toArray()),
                    'concat_value_and_accuracy' => $concatValueAndAccuracy,
                ]
            );
        }
        catch(\Exception $e) {
            dump($e);
        }
        return ResultUtil::successResult('OK');
    }

    /**
     * @param Request $request
     * @return \AppBundle\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function createPedigreeCertificates(Request $request): JsonResponse
    {
        $content = RequestUtil::getContentAsArray($request);

        //Validate if given ULNs are correct AND there should at least be one ULN given
        $ulnValidator = new UlnValidator($this->em, $content, true, null, $this->userService->getSelectedLocation($request));
        if(!$ulnValidator->getIsUlnSetValid()) {
            return $ulnValidator->createArrivalJsonErrorResponse();
        }

        try {
            $workerId = $this->createWorker($request, WorkerType::REPORT, ReportType::PEDIGREE_CERTIFICATE);
            if(!$workerId)
                return ResultUtil::errorResult('Could not create worker.', Response::HTTP_INTERNAL_SERVER_ERROR);

            $this->producer->sendCommand('generate_pdf',
                [
                    'worker_id' => $workerId,
                    'content' => JSON::encode($content->toArray()),
                ]
            );
        }
        catch(\Exception $e) {
            dump($e);
        }
        return ResultUtil::successResult('OK');
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

        try {
            $workerId = $this->createWorker($request, WorkerType::REPORT, ReportType::PEDIGREE_REGISTER_OVERVIEW);
            if(!$workerId)
                return ResultUtil::errorResult('Could not create worker.', Response::HTTP_INTERNAL_SERVER_ERROR);

            $this->producer->sendCommand('generate_pdf',
                [
                    'worker_id' => $workerId,
                    'type' => $type,
                    'upload_to_s3' => $uploadToS3,
                ]
            );
        }
        catch(\Exception $e) {
            dump($e);
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

        try {
            $workerId = $this->createWorker($request, WorkerType::REPORT, ReportType::OFF_SPRING);
            if(!$workerId)
                return ResultUtil::errorResult('Could not create worker.', Response::HTTP_INTERNAL_SERVER_ERROR);

            $this->producer->sendCommand('generate_pdf',
                [
                    'worker_id' => $workerId,
                    'content' => JSON::encode($content->toArray()),
                    'concat_value_and_accuracy' => $concatValueAndAccuracy,
                ]
            );
        }
        catch(\Exception $e) {
            dump($e);
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

        try {
            $workerId = $this->createWorker($request, WorkerType::REPORT, ReportType::ANNUAL_ACTIVE_LIVE_STOCK_RAM_MATES);
            if(!$workerId)
                return ResultUtil::errorResult('Could not create worker.', Response::HTTP_INTERNAL_SERVER_ERROR);

            $this->producer->sendCommand('generate_pdf',
                [
                    'worker_id' => $workerId,
                    'year' => $referenceYear
                ]
            );
        }
        catch(\Exception $e) {
            dump($e);
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

        try {
            $workerId = $this->createWorker($request, WorkerType::REPORT, ReportType::ANIMALS_OVERVIEW);
            if(!$workerId)
                return ResultUtil::errorResult('Could not create worker.', Response::HTTP_INTERNAL_SERVER_ERROR);

            $this->producer->sendCommand('generate_pdf',
                [
                    'worker_id' => $workerId,
                    'concat_value_and_accuracy' => $concatValueAndAccuracy,
                    'pedigree_active_end_date_limit' => $pedigreeActiveEndDateLimit->format('y-m-d H:i:s'),
                    'active_ubn_reference_date_string' => $activeUbnReferenceDate->format('y-m-d H:i:s'),
                ]
            );
        }
        catch(\Exception $e) {
            dump($e);
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

        try {
            $workerId = $this->createWorker($request, WorkerType::REPORT, ReportType::INBREEDING_COEFFICIENT);
            if(!$workerId)
                return ResultUtil::errorResult('Could not create worker.', Response::HTTP_INTERNAL_SERVER_ERROR);

            $this->producer->sendCommand('generate_pdf',
                [
                    'worker_id' => $workerId,
                    'content' => JSON::encode($content->toArray()),
                ]
            );
        }
        catch(\Exception $e) {
            dump($e);
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

        try {
            $workerId = $this->createWorker($request, WorkerType::REPORT, ReportType::FERTILIZER_ACCOUNTING);
            if(!$workerId)
                return ResultUtil::errorResult('Could not create worker.', Response::HTTP_INTERNAL_SERVER_ERROR);

            $this->producer->sendCommand('generate_pdf',
                [
                    'worker_id' => $workerId,
                    'reference_date' => $referenceDate->format('y-m-d H:i:s'),
                ]
            );
        }
        catch(\Exception $e) {
            dump($e);
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

        try {
            $workerId = $this->createWorker($request, WorkerType::REPORT, ReportType::ANNUAL_TE_100);
            if(!$workerId)
                return ResultUtil::errorResult('Could not create worker.', Response::HTTP_INTERNAL_SERVER_ERROR);

            $this->producer->sendCommand('generate_pdf',
                [
                    'worker_id' => $workerId,
                    'year' => $year,
                    'pedigree_active_end_date' => $pedigreeActiveEndDateLimit->format('y-m-d H:i:s'),
                ]
            );
        }
        catch(\Exception $e) {
            dump($e);
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

        try {
            $workerId = $this->createWorker($request, WorkerType::REPORT, ReportType::ANNUAL_ACTIVE_LIVE_STOCK);
            if(!$workerId)
                return ResultUtil::errorResult('Could not create worker.', Response::HTTP_INTERNAL_SERVER_ERROR);

            $this->producer->sendCommand('generate_pdf',
                [
                    'worker_id' => $workerId,
                    'year' => $referenceYear
                ]
            );
        }
        catch(\Exception $e) {
            dump($e);
        }
        return ResultUtil::successResult('OK');
    }

    private function createWorker(Request $request, int $workerType, int $reportType) : ?int
    {
        try {
            $worker = new Worker();
            $worker->setWorkerType($workerType);
            $worker->setOwner($this->userService->getUser());
            $worker->setLocation($this->userService->getSelectedLocation($request));

            $fileType = $request->query->get(QueryParameter::FILE_TYPE_QUERY, FileType::CSV);

            $language = $request->query->get(QueryParameter::LANGUAGE, $this->translator->getLocale());

            $pdfWorker = new ReportWorker();
            $pdfWorker->setReportType($reportType);
            $pdfWorker->setWorker($worker);
            $pdfWorker->setLocale($language);
            $pdfWorker->setFileType($fileType);
            $pdfWorker->setHash(hash('sha256', $workerType.$reportType.$this->userService->getUser()->getId()));
            $this->em->persist($pdfWorker);
            $this->em->persist($worker);
            $this->em->flush();

            return $worker->getId();
        }
        catch(\Exception $e) {

        }

        return null;
    }
}