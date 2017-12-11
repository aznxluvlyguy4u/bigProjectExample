<?php


namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Service\AWSSimpleStorageService;
use AppBundle\Service\CsvFromSqlResultsWriterService as CsvWriter;
use AppBundle\Service\ExcelService;
use AppBundle\Service\UserService;
use AppBundle\Util\RequestUtil;
use AppBundle\Validation\AdminValidator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\GeneratorInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;

class BreedValuesOverviewReportService extends ReportServiceWithBreedValuesBase
{
    const TITLE = 'Fokwaardenoverzicht alle huidige dieren';
    const FILENAME = 'Fokwaardenoverzicht_alle_huidige_dieren';
    const KEYWORDS = "nsfo fokwaarden dieren overzicht";
    const DESCRIPTION = "Fokwaardenoverzicht van alle dieren op huidige stallijsten met minstens 1 fokwaarde";
    const FOLDER = '/pedigree_register_reports/';

    /**
     * PedigreeRegisterOverviewReportService constructor.
     * @param ObjectManager|EntityManagerInterface $em
     * @param ExcelService $excelService
     * @param Logger $logger
     * @param AWSSimpleStorageService $storageService
     * @param CsvWriter $csvWriter
     * @param UserService $userService
     * @param TwigEngine $templating
     * @param TranslatorInterface $translator
     * @param GeneratorInterface $knpGenerator
     * @param string $cacheDir
     * @param string $rootDir
     */
    public function __construct(ObjectManager $em, ExcelService $excelService, Logger $logger,
                                AWSSimpleStorageService $storageService, CsvWriter $csvWriter, UserService $userService, TwigEngine $templating,
                                TranslatorInterface $translator,
                                GeneratorInterface $knpGenerator,
                                BreedValuesReportQueryGenerator $breedValuesReportQueryGenerator,
                                $cacheDir, $rootDir)
    {
        parent::__construct($em, $excelService, $logger, $storageService, $csvWriter, $userService, $templating, $translator,
            $knpGenerator, $breedValuesReportQueryGenerator, $cacheDir, $rootDir, self::FOLDER, self::FILENAME);

        $this->excelService
            ->setKeywords(self::KEYWORDS)
            ->setDescription(self::DESCRIPTION)
        ;
    }


    /**
     * @param Request $request
     * @param $user
     * @return JsonResponse
     */
    public function request(Request $request, $user)
    {
        if(!AdminValidator::isAdmin($user, AccessLevelType::SUPER_ADMIN)) { //validate if user is at least a SUPER_ADMIN
            return AdminValidator::getStandardErrorResponse();
        }

        $fileType = $request->query->get(QueryParameter::FILE_TYPE_QUERY, FileType::XLS);
        $uploadToS3 = RequestUtil::getBooleanQuery($request,QueryParameter::S3_UPLOAD, true);
        $concatBreedValuesAndAccuracies = RequestUtil::getBooleanQuery($request,QueryParameter::CONCAT_VALUE_AND_ACCURACY, false);
        $includeAllLiveStockAnimals = RequestUtil::getBooleanQuery($request,QueryParameter::INCLUDE_ALL_LIVESTOCK_ANIMALS, false);

        $this->setLocaleFromQueryParameter($request);

        return $this->generate($fileType, $concatBreedValuesAndAccuracies, $includeAllLiveStockAnimals, $uploadToS3);
    }


    /**
     * @param string $fileType
     * @param boolean $concatBreedValuesAndAccuracies
     * @param boolean $includeAllLiveStockAnimals
     * @param boolean $uploadToS3
     * @return JsonResponse
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function generate($fileType, $concatBreedValuesAndAccuracies, $includeAllLiveStockAnimals, $uploadToS3)
    {
        return $this->generateFile($this->getFilenameWithoutExtension(), $this->getData($concatBreedValuesAndAccuracies, $includeAllLiveStockAnimals), self::TITLE, $fileType, $uploadToS3);
    }


    /**
     * @param bool $concatBreedValuesAndAccuracies
     * @param bool $includeAnimalsWithoutAnyBreedValues
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getData($concatBreedValuesAndAccuracies = true, $includeAnimalsWithoutAnyBreedValues = false)
    {
        return $this->conn->query(
            $this->breedValuesReportQueryGenerator->getFullBreedValuesReportOverviewQuery(
                $concatBreedValuesAndAccuracies,
                $includeAnimalsWithoutAnyBreedValues
            )
        )->fetchAll();
    }
}