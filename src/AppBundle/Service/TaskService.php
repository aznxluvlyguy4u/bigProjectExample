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
use AppBundle\Entity\UpdateAnimalDataWorker;
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

class TaskService
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

    /**
     * ReportService constructor.
     * @param ProducerInterface $producer
     * @param BaseSerializer $serializer
     * @param EntityManager $em
     * @param UserService $userService
     * @param TranslatorInterface $translator
     * @param Logger $logger
     * @param UlnValidatorInterface $ulnValidator
     */
    public function __construct(
        ProducerInterface $producer,
        BaseSerializer $serializer,
        EntityManager $em,
        UserService $userService,
        TranslatorInterface $translator,
        Logger $logger,
        UlnValidatorInterface $ulnValidator
    )
    {
        $this->em = $em;
        $this->producer = $producer;
        $this->serializer = $serializer;
        $this->userService = $userService;
        $this->translator = $translator;
        $this->logger = $logger;
        $this->ulnValidator = $ulnValidator;
    }

    /**
     * @param Request $request
     * @return array|null
     * @throws \Exception
     */
    public function getTasks(Request $request): ?array
    {
        $user = $this->userService->getUser();
        $accountOwner = $this->userService->getAccountOwner($request);

        $workers = $this->em->getRepository(UpdateAnimalDataWorker::class)->getTasks($user, $accountOwner);
        return $this->serializer->getDecodedJson($workers,[JmsGroup::BASIC],true);
    }
}
