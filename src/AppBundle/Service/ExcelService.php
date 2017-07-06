<?php


namespace AppBundle\Service;

use AppBundle\Util\FilesystemUtil;
use Liuggio\ExcelBundle\Factory as ExcelFactory;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Class ExcelService
 * @package AppBundle\Service
 */
class ExcelService
{
    const DEFAULT_FILENAME = 'nsfo_excel_file';
    const DEFAULT_EXTENSION = 'xls';
    const DEFAULT_EXCEL_TYPE = 'Excel2007';
    const DEFAULT_CONTENT_MIME_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    const DEFAULT_CATEGORY = '';
    const DEFAULT_CREATOR = 'NSFO';
    const DEFAULT_DESCRIPTION = '';
    const DEFAULT_KEYWORDS = 'NSFO schapen fokken';
    const DEFAULT_LAST_MODIFIED_BY = 'NSFO';
    const DEFAULT_SUBJECT = 'Schapenfokken';
    const DEFAULT_TITLE = 'Excel File';

    /** @var ExcelFactory */
    private $excelBundle;
    /** @var string */
    private $cacheDir;

    /** @var string */
    private $contentMimeType;
    /** @var string */
    private $excelFileType;
    /** @var string */
    private $extension;
    /** @var string */
    private $filename;
    /** @var string */
    private $folder;
    /** @var string */
    private $fullFilepath;


    /* FileSettings */

    /** @var string */
    private $category;
    /** @var string */
    private $creator;
    /** @var string */
    private $description;
    /** @var string */
    private $keywords;
    /** @var string */
    private $lastModifiedBy;
    /** @var string */
    private $subject;
    /** @var string */
    private $title;


    /**
     * ReportService constructor.
     * @param ExcelFactory $excelBundle
     */
    public function __construct(ExcelFactory $excelBundle, $cacheDir)
    {
        $this->excelBundle = $excelBundle;
        $this->cacheDir = $cacheDir;

        $this->contentMimeType = self::DEFAULT_CONTENT_MIME_TYPE;
        $this->excelFileType = self::DEFAULT_EXCEL_TYPE;
        $this->extension = self::DEFAULT_EXTENSION;
        $this->setFolderName($this->folder);
        $this->setFilename(self::DEFAULT_FILENAME);

        $this->category = self::DEFAULT_CATEGORY;
        $this->creator = self::DEFAULT_CREATOR;
        $this->description = self::DEFAULT_DESCRIPTION;
        $this->lastModifiedBy = self::DEFAULT_LAST_MODIFIED_BY;
        $this->keywords = self::DEFAULT_KEYWORDS;
        $this->subject = self::DEFAULT_SUBJECT;
        $this->title = self::DEFAULT_TITLE;
    }


    /**
     * @param $folderName
     * @return ExcelService
     */
    public function setFolderName($folderName)
    {
        $folderName = '/'.ltrim($folderName, '/');
        $this->folder = rtrim($folderName, '/').'/';
        FilesystemUtil::createFolderPathIfNull($this->getCacheSubFolder());
        return $this;
    }


    public function getFileNameWithExtension()
    {
        return $this->filename . '.' . $this->extension;
    }


    /**
     * @param array $data
     * @return null|string
     */
    public function generateFromSqlResults($data)
    {
        if(count($data) === 0) { return null; }


        $phpExcelObject = $this->excelBundle->createPHPExcelObject();

        $phpExcelObject->getProperties()->setCreator($this->creator)
            ->setLastModifiedBy($this->lastModifiedBy)
            ->setTitle($this->title)
            ->setSubject($this->subject)
            ->setKeywords($this->keywords)
            ->setDescription($this->description)
            ->setCategory($this->category)
        ;


        //Insert headers
        foreach (array_keys($data[0]) as $key => $header)
        {
            $columnLetter = self::getColumnLetterByNumber($key, true);
            $phpExcelObject->setActiveSheetIndex(0)
                ->setCellValue($columnLetter.'1', $header);
        }


        foreach ($data as $recordNumber => $record)
        {
            $columnCounter = 0;
            foreach ($record as $columnHeaderName => $value) {
                $columnLetter = self::getColumnLetterByNumber($columnCounter, true);
                $columnCounter++;

                if($value) {
                    $phpExcelObject->setActiveSheetIndex(0)
                        ->setCellValue($columnLetter.($recordNumber+2), $value);
                }
            }
        }

        $phpExcelObject->getActiveSheet()->setTitle('records');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $phpExcelObject->setActiveSheetIndex(0);

        // create the writer
        $writer = $this->excelBundle->createWriter($phpExcelObject, $this->excelFileType);

        // create the response
        $response = $this->excelBundle->createStreamedResponse($writer);

        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $this->filename
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        $writer->save($this->fullFilepath);

        return $this->fullFilepath;
    }


    /**
     * @return string
     */
    public function getCacheSubFolder()
    {
        return $this->cacheDir . $this->folder;
    }

    /**
     * @return string
     */
    public function getFullFilepath()
    {
        return $this->fullFilepath;
    }


    /**
     * @param int $num
     * @param boolean $startIndexIsZero
     * @return string
     */
    public static function getColumnLetterByNumber($num, $startIndexIsZero = true)
    {
        if($startIndexIsZero) {
            $numeric = $num % 26;
            $letter = chr(65 + $numeric);
            $num2 = intval($num / 26);
            if ($num2 > 0) {
                return self::getColumnLetterByNumber($num2 - 1, $startIndexIsZero) . $letter;
            } else {
                return $letter;
            }

        } else {
            $numeric = ($num - 1) % 26;
            $letter = chr(65 + $numeric);
            $num2 = intval(($num - 1) / 26);
            if ($num2 > 0) {
                return self::getColumnLetterByNumber($num2, $startIndexIsZero) . $letter;
            } else {
                return $letter;
            }
        }
    }

    /**
     * @return string
     */
    public function getContentMimeType()
    {
        return $this->contentMimeType;
    }

    /**
     * @param $contentMimeType
     * @return ExcelService
     */
    public function setContentMimeType($contentMimeType)
    {
        $this->contentMimeType = $contentMimeType;
        return $this;
    }

    /**
     * @return string
     */
    public function getExcelFileType()
    {
        return $this->excelFileType;
    }

    /**
     * @param $excelFileType
     * @return ExcelService
     */
    public function setExcelFileType($excelFileType)
    {
        $this->excelFileType = $excelFileType;
        return $this;
    }

    /**
     * @return string
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * @param $extension
     * @return ExcelService
     */
    public function setExtension($extension)
    {
        $this->extension = $extension;
        $this->setFilePathWithExtension();
        return $this;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param $filename
     * @return ExcelService
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
        $this->setFilePathWithExtension();
        return $this;
    }


    private function setFilePathWithExtension()
    {
        $this->fullFilepath = $this->getCacheSubFolder().$this->filename. '.'.$this->extension;
    }


    /**
     * @return string
     */
    public function getFolder()
    {
        return $this->folder;
    }

    /**
     * @param $folder
     * @return ExcelService
     */
    public function setFolder($folder)
    {
        $this->folder = $folder;
        $this->setFilePathWithExtension();
        return $this;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param $category
     * @return ExcelService
     */
    public function setCategory($category)
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return string
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @param $creator
     * @return ExcelService
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param $description
     * @return ExcelService
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * @param $keywords
     * @return ExcelService
     */
    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastModifiedBy()
    {
        return $this->lastModifiedBy;
    }

    /**
     * @param $lastModifiedBy
     * @return ExcelService
     */
    public function setLastModifiedBy($lastModifiedBy)
    {
        $this->lastModifiedBy = $lastModifiedBy;
        return $this;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param $subject
     * @return ExcelService
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param $title
     * @return ExcelService
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }



}