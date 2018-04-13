<?php


namespace AppBundle\Service\Invoice;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Client;
use AppBundle\Entity\Invoice;
use AppBundle\Entity\VatBreakdown;
use AppBundle\Enumerator\FileType;
use AppBundle\Service\AWSSimpleStorageService;
use AppBundle\Service\CsvFromSqlResultsWriterService as CsvWriter;
use AppBundle\Service\ExcelService;
use AppBundle\Service\UserService;
use AppBundle\Util\FilesystemUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\TwigOutputUtil;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\GeneratorInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;

class InvoicePdfGeneratorService
{
    const CREATOR = 'NSFO';
    const DEFAULT_EXTENSION = FileType::PDF;
    const FILENAME = 'NFSO_Invoice';
    const FOLDER_NAME = self::FILENAME;
    const BUCKET_DIRECTORY = "invoices";
    const CONTENT_TYPE = "application/pdf";

    /** @var AWSSimpleStorageService */
    protected $storageService;
    /** @var UserService */
    protected $userService;
    /** @var TwigEngine */
    protected $templating;
    /** @var TranslatorInterface */
    protected $translator;
    /** @var GeneratorInterface */
    protected $knpGenerator;
    /** @var Logger */
    protected $logger;
    /** @var Filesystem */
    protected $fs;
    /** @var string */
    protected $cacheDir;
    /** @var string */
    protected $rootDir;

    /** @var string */
    protected $folderPath;
    /** @var string */
    protected $filename;
    /** @var string */
    protected $folderName;
    /** @var string */
    protected $extension;
    /** @var string */
    protected $language;

    /** @var array */
    protected $inputErrors;

    /** @var array */
    protected $convertedResult;

    public function __construct(Logger $logger,
                                AWSSimpleStorageService $storageService,
                                UserService $userService, TwigEngine $templating,
                                TranslatorInterface $translator,
                                GeneratorInterface $knpGenerator, $cacheDir, $rootDir,
                                $outputReportsToCacheFolderForLocalTesting,
                                $displayReportPdfOutputAsHtml
    )
    {
        $this->logger = $logger;
        $this->storageService = $storageService;
        $this->userService = $userService;
        $this->templating = $templating;
        $this->translator = $translator;
        $this->knpGenerator = $knpGenerator;
        $this->cacheDir = $cacheDir;
        $this->rootDir = $rootDir;

        $this->extension = self::DEFAULT_EXTENSION;
        $this->folderName = self::FOLDER_NAME;
        $this->filename = self::FILENAME;

        $this->fs = new Filesystem();
        $this->inputErrors = [];

        $this->outputReportsToCacheFolderForLocalTesting = StringUtil::getStringAsBoolean($outputReportsToCacheFolderForLocalTesting, false);
        $this->displayReportPdfOutputAsHtml = StringUtil::getStringAsBoolean($displayReportPdfOutputAsHtml, false);
    }


    /**
     * @param string $value
     * @return string
     */
    protected function trans($value)
    {
        return $this->translator->trans($value);
    }


    /**
     * @param string $value
     * @param bool $replaceSpacesWithUnderScores
     * @param bool $capitalizeFirstLetter
     * @return string
     */
    protected function translate($value, $replaceSpacesWithUnderScores = true, $capitalizeFirstLetter = false)
    {
        $translated = mb_strtolower($this->translator->trans(strtoupper($value)));
        if ($capitalizeFirstLetter) {
            $translated = ucfirst($translated);
        }

        if ($replaceSpacesWithUnderScores) {
            return strtr($translated, [' ' => '_']);
        }

        return $translated;
    }


    /**
     * @param string $message
     * @return string
     */
    protected function translateErrorMessages($message)
    {
        if ($message == null) { return ''; }

        return $this->translate($message, false, true);
    }

    public function getS3Key()
    {
        $path = FilesystemUtil::concatDirAndFilename(self::BUCKET_DIRECTORY, $this->folderName);
        return FilesystemUtil::concatDirAndFilename($path, $this->getFilename());
    }

    public function getCacheSubFolder()
    {
        return FilesystemUtil::concatDirAndFilename($this->cacheDir, $this->folderName);
    }

    /**
     * @param string $filePath
     * @return JsonResponse
     */
    protected function uploadPdfFileToS3($filePath)
    {
        $s3Service = $this->storageService;
        $url = $s3Service->uploadFromFilePath(
            $filePath,
            $this->getS3Key(),
            self::CONTENT_TYPE
        );

        $this->fs->remove($filePath);
        return ResultUtil::successResult($url);
    }


    /**
     * Returns a rendered view.
     *
     * @param string $view       The view name
     * @param array  $parameters An array of parameters to pass to the view
     *
     * @return string The rendered view
     */
    protected function renderView($view, array $parameters = array())
    {
        return $this->templating->render($view, $parameters);
    }


    /**
     * @param string $twigFile
     * @param array|object $data
     * @param boolean $isLandscape
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse|Response
     */
    public function getInvoicePdfBase($twigFile, $data, $isLandscape = true)
    {
        /**
         * @var Invoice $data
         * @var VatBreakdown $vatBreakdown
         */
        $vatBreakdown = $data->getVatBreakdownRecords();
        $html = $this->renderView($twigFile, ['invoice' => $data, 'rootDirectory' => $this->rootDir."/../web", 'vatBreakdown' => $vatBreakdown]);

        if ($this->displayReportPdfOutputAsHtml) {
            $response = new Response($html);
            $response->headers->set('Content-Type', 'text/html');
            return $response;
        }

        $this->extension = FileType::PDF;

        $pdfOptions = array(
            'footer-html' => $this->rootDir."/Resources/views/Invoice/_footer.html",
            'orientation'=>'Portrait',
            'default-header'=>false,
            'disable-smart-shrinking'=>false,
            'print-media-type' => true,
        );

        if($this->outputReportsToCacheFolderForLocalTesting) {
            //Save pdf in local cache
            return ResultUtil::successResult($this->saveFileLocally($this->getCacheDirFilename(), $html, $pdfOptions));
        }

        $pdfOutput = $this->knpGenerator->getOutputFromHtml($html, $pdfOptions);
        $url = $this->storageService->uploadPdf($pdfOutput, $this->getS3Key());

        return ResultUtil::successResult($url);
    }


    /**
     * @param string $generatedPdfPath
     * @param $html
     * @param array $pdfOptions
     * @return string
     */
    protected function saveFileLocally($generatedPdfPath, $html, $pdfOptions = null)
    {
        $this->knpGenerator->generateFromHtml($html, $generatedPdfPath, $pdfOptions);
        return $generatedPdfPath;
    }

    /**
     * @param string $key
     * @return string
     */
    protected function translateKey($key)
    {
        // Translate concatenated parent strings, like: fm, mm, fff, mfmfmfmf
        if (strlen($key) > 1 && StringUtil::onlyContainsChars(['f', 'm'], $key)) {
            $chars = str_split($key, 1);

            $result = '';
            $prefix = '';
            foreach ($chars as $char) {
                $result .= $prefix . $this->translateKey($char);
                $prefix = '_';
            }

            return $result;
        }

        return strtr(mb_strtolower($this->translator->trans(strtoupper($key))), [' ' => '_']);
    }

    /**
     * @return string
     */
    protected function getCacheDirFilename()
    {
        $path = FilesystemUtil::concatDirAndFilename($this->cacheDir, $this->folderName);
        return FilesystemUtil::concatDirAndFilename($path, $this->getFilename());
    }


    /**
     * @return string
     */
    protected function getFilename()
    {
        return $this->getFilenameWithoutExtension().'.'.$this->extension;
    }


    protected function getFilenameWithoutExtension()
    {
        return $this->filename.'_'.TimeUtil::getTimeStampNowForFiles();
    }


    /**
     * @return Client|\AppBundle\Entity\Employee|\AppBundle\Entity\Person
     */
    protected function getUser()
    {
        return $this->userService->getUser();
    }



}