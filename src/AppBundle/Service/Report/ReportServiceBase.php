<?php


namespace AppBundle\Service\Report;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Client;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\FileType;
use AppBundle\Service\AWSSimpleStorageService;
use AppBundle\Service\CsvFromSqlResultsWriterService;
use AppBundle\Service\CsvFromSqlResultsWriterService as CsvWriter;
use AppBundle\Service\ExcelService;
use AppBundle\Service\UserService;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\FilesystemUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\TwigOutputUtil;
use AppBundle\Validation\UlnValidatorInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\GeneratorInterface;
use Knp\Snappy\Pdf;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class ReportServiceBase
 * @package AppBundle\Service\Report
 */
class ReportServiceBase
{
    const EXCEL_TYPE = 'Excel2007';
    const CREATOR = 'NSFO';
    const DEFAULT_EXTENSION = FileType::PDF;
    const FILENAME = 'NFSO_Report';
    const FOLDER_NAME = ReportServiceBase::FILENAME;

    /** @var EntityManagerInterface */
    protected $em;
    /** @var Connection */
    protected $conn;
    /** @var ExcelService */
    protected $excelService;
    /** @var AWSSimpleStorageService */
    protected $storageService;
    /** @var CsvWriter */
    protected $csvWriter;
    /** @var UserService */
    protected $userService;
    /** @var TwigEngine */
    protected $templating;
    /** @var TranslatorInterface */
    protected $translator;
		/** @var GeneratorInterface */
		protected $knpGeneratorV124;
		/** @var Pdf */
		protected $knpGeneratorV125;
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
    /** @var boolean */
    protected $outputReportsToCacheFolderForLocalTesting;
    /** @var boolean */
    protected $displayReportPdfOutputAsHtml;
    /** @var UlnValidatorInterface */
    protected $ulnValidator;
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

    /** @var boolean */
    private $isTranslateHeaderActive;
    /** @var array */
    private static $translationSet;

    public function __construct(EntityManagerInterface $em, ExcelService $excelService, Logger $logger,
                                AWSSimpleStorageService $storageService, CsvWriter $csvWriter,
                                UserService $userService, TwigEngine $templating,
                                TranslatorInterface $translator,
                                GeneratorInterface $knpGenerator,
                                UlnValidatorInterface $ulnValidator,
                                $cacheDir, $rootDir,
                                $outputReportsToCacheFolderForLocalTesting,
                                $displayReportPdfOutputAsHtml,
																$wkhtmltopdfV125Path
    )
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->logger = $logger;
        $this->storageService = $storageService;
        $this->csvWriter = $csvWriter;
        $this->userService = $userService;
        $this->templating = $templating;
        $this->translator = $translator;
        $this->knpGeneratorV124 = $knpGenerator;
        $this->knpGeneratorV125 = new Pdf($wkhtmltopdfV125Path);
        $this->knpGenerator = $this->knpGeneratorV124;
        $this->ulnValidator = $ulnValidator;
        $this->cacheDir = $cacheDir;
        $this->rootDir = $rootDir;

        $this->excelService = $excelService;
        $this->excelService
            ->setFolderName(self::FOLDER_NAME)
            ->setCreator(self::CREATOR)
            ->setExcelFileType(self::EXCEL_TYPE)
        ;

        $this->extension = self::DEFAULT_EXTENSION;
        $this->folderPath = self::FOLDER_NAME;
        $this->filename = self::FILENAME;

        $this->fs = new Filesystem();
        $this->inputErrors = [];

        $this->outputReportsToCacheFolderForLocalTesting = StringUtil::getStringAsBoolean($outputReportsToCacheFolderForLocalTesting, false);
        $this->displayReportPdfOutputAsHtml = StringUtil::getStringAsBoolean($displayReportPdfOutputAsHtml, false);

        $this->activateColumnHeaderTranslation();
    }


    /**
     * @param string $value
     * @param array $parameters
     * @return string
     */
    protected function trans($value, $parameters = [])
    {
        return $this->translator->trans($value, $parameters);
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
     * @param TranslatorInterface $translator
     * @param string $columnHeader
     * @return string
     */
    public static function staticTranslateColumnHeader(TranslatorInterface $translator, $columnHeader)
    {
        $prefix = mb_substr($columnHeader, 0, 2);
        $upperSuffix = strtoupper(mb_substr($columnHeader, 2, strlen($columnHeader)-2));

        switch ($prefix) {
            case 'a_': $translatedColumnHeader = $translator->trans('A') . '_' . $translator->trans($upperSuffix); break;
            case 'f_': $translatedColumnHeader = $translator->trans('F') . '_' . $translator->trans($upperSuffix); break;
            case 'm_': $translatedColumnHeader = $translator->trans('M') . '_' . $translator->trans($upperSuffix); break;
            default: $translatedColumnHeader = $translator->trans(strtoupper($columnHeader)); break;
        }

        return strtr(strtolower($translatedColumnHeader), self::getTranslationSet());
    }


    protected function activateColumnHeaderTranslation()
    {
        $this->isTranslateHeaderActive = true;
    }


    protected function deactivateColumnHeaderTranslation()
    {
        $this->isTranslateHeaderActive = false;
    }


    /**
     * @param string $columnHeader
     * @return string
     */
    protected function translateColumnHeader($columnHeader)
    {
        if ($this->isTranslateHeaderActive) {
            return ReportServiceBase::staticTranslateColumnHeader($this->translator, $columnHeader);
        }
        return $columnHeader;
    }


    /**
     * @return array
     */
    private static function getTranslationSet()
    {
        if (self::$translationSet === null || count(self::$translationSet) === 0) {
            self::$translationSet = StringUtil::capitalizationSet();
            self::$translationSet[' '] = '_';
        }
        return self::$translationSet;
    }


    public static function closeColumnHeaderTranslation()
    {
        self::$translationSet = null;
    }


    protected function getImagesDirectory()
    {
        return FilesystemUtil::getImagesDirectory($this->rootDir);
    }


    /**
     * @param array $csvData
     * @return array
     */
    protected function translateColumnHeaders($csvData)
    {
        foreach ($csvData as $item => $records) {
            foreach ($records as $columnHeader => $value) {

                $translatedColumnHeader = ReportServiceBase::staticTranslateColumnHeader($this->translator, $columnHeader);

                if ($columnHeader !== $translatedColumnHeader) {
                    $csvData[$item][$translatedColumnHeader] = $value;
                    unset($csvData[$item][$columnHeader]);
                }
            }
        }

        self::closeColumnHeaderTranslation();

        return $csvData;
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


    /**
     * @param $locale
     */
    protected function setLocale($locale)
    {
        $this->translator->setLocale($locale);
    }


    public function getS3Key()
    {
        $path = FilesystemUtil::concatDirAndFilename('reports', $this->folderName);
        return FilesystemUtil::concatDirAndFilename($path, $this->getFilename());
    }


    public function getContentType()
    {
        return $this->excelService->getContentMimeType();
    }


    public function getCacheSubFolder()
    {
        return FilesystemUtil::concatDirAndFilename($this->cacheDir, $this->folderName);
    }


    /**
     * @param string $filenameWithoutExtension
     * @param array $data
     * @param string $title
     * @param string $fileExtension
     * @param boolean $uploadToS3
     * @return JsonResponse
     * @throws \Exception
     */
    protected function generateFile($filenameWithoutExtension, $data, $title, $fileExtension, $uploadToS3)
    {
        $recordCount = count($data);
        if($recordCount === 0) {
            $code = 428;
            $message = "Data is empty";
            return new JsonResponse(['code' => $code, "message" => $message], $code);
        }

        $this->logger->notice('Retrieved '.$recordCount.' records');
        $this->logger->notice('Generate data from sql results ... ');

        //These values are also used for the filename on the S3 bucket
        $this->filename = $filenameWithoutExtension;
        $this->extension = $fileExtension;

        $this->excelService->setFilename($this->filename);
        $this->excelService->setExtension($this->extension);
        $this->excelService->setTitle($title);

        switch ($fileExtension) {

            case FileType::XLS:
                $this->excelService->generateFromSqlResults($data);
                $localFilePath = $this->excelService->getFullFilepath();
                break;

            case FileType::CSV:
                $localFilePath = $this->csvWriter->write($data,$this->getFilename());
                break;

            default:
                throw new \Exception('Incorrect FileType given');
        }

        if($localFilePath instanceof JsonResponse) { return $localFilePath; }

        if ($uploadToS3) {
            return $this->uploadReportFileToS3($localFilePath);
        }

        return ResultUtil::successResult($localFilePath);
    }


    /**
     * @param string $filenameWithExtension
     * @param string $selectQuery
     * @param array $booleanColumns
     * @return JsonResponse
     * @throws \Exception
     */
    protected function generateCsvFileBySqlQuery($filenameWithExtension, $selectQuery, $booleanColumns = [])
    {
        $dir = CsvFromSqlResultsWriterService::csvCacheDir($this->cacheDir);

        $localFilePath = FilesystemUtil::concatDirAndFilename($dir, $filenameWithExtension);

        $this->csvWriter->writeToFileFromSqlQuery($selectQuery, $localFilePath, $booleanColumns);

        return $this->uploadReportFileToS3($localFilePath);
    }


    /**
     * @param string $filePath
     * @return JsonResponse
     */
    protected function uploadReportFileToS3($filePath)
    {
        if($this->outputReportsToCacheFolderForLocalTesting) {
            return ResultUtil::successResult($filePath);
        }

        $s3Service = $this->storageService;
        $url = $s3Service->uploadFromFilePath(
            $filePath,
            $this->getS3Key(),
            $this->getContentType()
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
		 * @param array $additionalData
		 * @param null $customPdfOptions
		 * @param bool $useWkhtmltopdfV125
		 * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
		 */
    protected function getPdfReportBase($twigFile, $data, $isLandscape = true, $additionalData = [], $customPdfOptions = null, $useWkhtmltopdfV125 = false)
    {
    	  $twigInput = ArrayUtil::concatArrayValues(
    	  	[
			      [
			      	'variables' => $data,
				      'displayReportPdfOutputAsHtml' => $this->displayReportPdfOutputAsHtml
			      ],
			      $additionalData
		      ],
		      false
	      );

        $html = $this->renderView($twigFile, $twigInput);

        if ($this->displayReportPdfOutputAsHtml) {
            $response = new Response($html);
            $response->headers->set('Content-Type', 'text/html');
            return $response;
        }

        $this->extension = FileType::PDF;

        $pdfOptions = $isLandscape ? TwigOutputUtil::pdfLandscapeOptions() : TwigOutputUtil::pdfPortraitOptions();
        $pdfOptions = $customPdfOptions ? $customPdfOptions : $pdfOptions;

        // Switch to wkhtmltopdf v0.12.5
        if ($useWkhtmltopdfV125) {
	          $this->knpGenerator = $this->knpGeneratorV125;
        }

        if($this->outputReportsToCacheFolderForLocalTesting) {
            //Save pdf in local cache
            return ResultUtil::successResult($this->saveFileLocally($this->getCacheDirFilename(), $html, $pdfOptions));
        }

        $pdfOutput = $this->knpGenerator->getOutputFromHtml($html, $pdfOptions);

        $url = $this->storageService->uploadPdf($pdfOutput, $this->getS3Key());

	      // Switch back to wkhtmltopdf v0.12.4
        if ($useWkhtmltopdfV125) {
        	  $this->knpGenerator = $this->knpGeneratorV124;
        }

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
     * @param array $arraySets
     * @param array $keysToIgnore
     * @param array $customKeyTranslation
     * @param string $keyPrefix
     * @return array
     */
    protected function convertNestedArraySetsToSqlResultFormat(array $arraySets, $keysToIgnore = [],
                                                               array $customKeyTranslation = [], $keyPrefix = '')
    {
        $result = [];
        foreach ($arraySets as $arraySet) {
            $result[] = $this->convertNestedArrayToSqlResultFormat($arraySet, $keysToIgnore, $keyPrefix, $customKeyTranslation);
        }
        $this->purgeConvertedResult();

        return $result;
    }


    /**
     * @param array $data
     * @param array $keysToIgnore
     * @return mixed
     */
    protected function unsetNestedKeys(array $data, array $keysToIgnore = [])
    {
        $rows = array_keys($data);
        foreach ($rows as $row) {
            foreach ($keysToIgnore as $keyToIgnore)
            {
                unset($data[$row][$keyToIgnore]);
            }
        }
        return $data;
    }


    /**
     * @param array $array
     * @return array
     */
    protected function translateKeysInFlatArray(array $array)
    {
        foreach ($array as $key => $value) {
            $translatedKey = $this->translateKey($key);
            if ($translatedKey != $key) {
                $array[$translatedKey] = $value;
                unset($array[$key]);
            }
        }
        return $array;
    }



    /**
     * @param array $array
     * @param string $keyPrefix
     * @param array $customKeyTranslation
     * @param array $keysToIgnore
     * @param string $semiColonReplacement
     * @param boolean $purgeResult
     * @return array
     * @throws \Exception
     */
    protected function convertNestedArrayToSqlResultFormat(array $array, $keysToIgnore = [], $keyPrefix = '', array $customKeyTranslation = [], $semiColonReplacement = ',', $purgeResult = true)
    {
        $keySeparator = '_';

        if ($purgeResult) { $this->purgeConvertedResult(); }

        foreach ($array as $key => $item) {

            if (in_array($key, $keysToIgnore)) {
                continue;
            }

            if (key_exists($key, $customKeyTranslation)) {
                $key = $customKeyTranslation[$key];
            }

            $key = $this->translateKey($key);

            $keyForResult = $keyPrefix !== '' ? $keyPrefix.$keySeparator.$key : $key;

            if (is_array($item)) {
                self::convertNestedArrayToSqlResultFormat($item, $keysToIgnore, $keyForResult, $customKeyTranslation, $semiColonReplacement,false);

            } elseif (is_string($item)) {

                if (key_exists($keyForResult, $this->convertedResult)) {
                    throw new \Exception('Duplicate key: '.$keyForResult);
                }
                $this->convertedResult[$keyForResult] = str_replace(';', $semiColonReplacement, $item);
            }
        }

        return $this->convertedResult;
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



    protected function purgeConvertedResult()
    {
        $this->convertedResult = [];
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


    /**
     * @param Request $request
     * @param boolean $nullCheck
     * @return Location|null
     * @throws \Exception
     */
    protected function getSelectedLocation(Request $request, $nullCheck = false)
    {
        $location = $this->userService->getSelectedLocation($request);
        if ($nullCheck) {
            if (!$location || !$location->getId()) {
                throw new \Exception('No location given', Response::HTTP_PRECONDITION_REQUIRED);
            }
            if (!$location->getUbn()) {
                throw new \Exception('UBN of location is missing', Response::HTTP_PRECONDITION_REQUIRED);
            }
        }
        return $location;
    }


    /**
     * @param array $animalsArray
     * @return JsonResponse|array
     * @throws \Exception
     */
    protected function getAnyAnimalIdsFromBody($animalsArray)
    {
        $results = $this->em->getRepository(Animal::class)->getAnimalIdsFromAnimalsArray($animalsArray);

        if (count($results) === 0) {
            throw new \Exception($this->translateErrorMessages('NO ANIMALS FOUND FOR GIVEN INPUT'), Response::HTTP_PRECONDITION_REQUIRED);
        }

        return $results;
    }


    /**
     * @param array $animalsArray
     * @param Location $location
     * @return array
     * @throws \Exception
     */
    protected function getCurrentAndHistoricAnimalIdsFromBody($animalsArray, Location $location)
    {
        $results = $this->em->getRepository(Animal::class)
            ->getCurrentAndHistoricAnimalIdsFromAnimalsArray(
                $animalsArray,
                $location->getId()
            );

        if (count($results) === 0) {
            throw new \Exception($this->translateErrorMessages('NO ANIMALS FOUND FOR GIVEN INPUT'), Response::HTTP_PRECONDITION_REQUIRED);
        }

        $nonHistoricAnimalUlns = [];
        $animalIds = [];
        foreach ($results as $result)
        {
            $animalIds[] = $result['id'];

            if (!$result['is_historic_livestock_animal']) {
                $nonHistoricAnimalUlns[] = $result['uln'];
            }
        }

        if (count($nonHistoricAnimalUlns) > 0) {
            throw new \Exception($this->translateErrorMessages('THE FOLLOWING ANIMALS ARE NOT CURRENT LIVESTOCK OR HISTORIC LIVESTOCK ANIMALS OF THIS UBN').', '.$location->getUbn().': '.implode(',',$nonHistoricAnimalUlns), Response::HTTP_PRECONDITION_REQUIRED);
        }

        return $animalIds;
    }


    /**
     * @return string
     */
    protected function getGenderLetterTranslationValues()
    {
        return SqlUtil::getGenderLetterTranslationValues($this->translator);
    }
}