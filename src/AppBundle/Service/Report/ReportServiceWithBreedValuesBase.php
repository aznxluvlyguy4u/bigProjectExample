<?php


namespace AppBundle\Service\Report;

use AppBundle\Service\AWSSimpleStorageService;
use AppBundle\Service\CsvFromSqlResultsWriterService as CsvWriter;
use AppBundle\Service\ExcelService;
use AppBundle\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\GeneratorInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\Translation\TranslatorInterface;

class ReportServiceWithBreedValuesBase extends ReportServiceBase
{
    /** @var BreedValuesReportQueryGenerator */
    protected $breedValuesReportQueryGenerator;

    public function __construct(EntityManagerInterface $em, ExcelService $excelService, Logger $logger,
                                AWSSimpleStorageService $storageService, CsvWriter $csvWriter, UserService $userService,
                                TwigEngine $templating,
                                TranslatorInterface $translator,
                                GeneratorInterface $knpGenerator,
                                BreedValuesReportQueryGenerator $breedValuesReportQueryGenerator,
                                $cacheDir, $rootDir, $outputReportsToCacheFolderForLocalTesting,
                                $displayReportPdfOutputAsHtml
    )
    {
        parent::__construct($em, $excelService, $logger, $storageService, $csvWriter, $userService, $templating, $translator,
            $knpGenerator,$cacheDir, $rootDir, $outputReportsToCacheFolderForLocalTesting, $displayReportPdfOutputAsHtml);

        $this->breedValuesReportQueryGenerator = $breedValuesReportQueryGenerator;
    }
}