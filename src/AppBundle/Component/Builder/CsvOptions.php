<?php

namespace AppBundle\Component\Builder;

/**
 * Class CsvOptions
 * @package AppBundle\Builder
 */
class CsvOptions
{
    const DEFAULT_INPUT_FOLDER = 'app/Resources/imports/';
    const DEFAULT_OUTPUT_FOLDER = 'app/Resources/outputs/';
    const DEFAULT_FILE_NAME = 'filename.csv';
    const DEFAULT_FOPEN_MODE = 'r';
    const DEFAULT_SEPARATOR = ';';
    const DEFAULT_IGNORE_FIRST_LINE = true;

    /** @var boolean */
    private $ignoreFirstLine;

    /** @var string */
    private $inputFolder;

    /** @var string */
    private $outputFolder;

    /** @var string */
    private $fileName;
    
    /** @var string */
    private $fopenMode;

    /** @var string */
    private $separatorSymbol;

    /**
     * CsvOptions constructor.
     */
    public function __construct()
    {
        $this->includeFirstLine();
        $this->setInputFolder(self::DEFAULT_INPUT_FOLDER);
        $this->setOutputFolder(self::DEFAULT_OUTPUT_FOLDER);
        $this->setFileName(self::DEFAULT_FILE_NAME);
        $this->setFopenMode(self::DEFAULT_FOPEN_MODE);
        $this->setSeparatorSymbol(self::DEFAULT_SEPARATOR);
        if(self::DEFAULT_IGNORE_FIRST_LINE) {
            $this->ignoreFirstLine();
        } else {
            $this->includeFirstLine();
        }
    }

    public function __clone()
    {
    }

    /**
     * @return boolean
     */
    public function isIgnoreFirstLine()
    {
        return $this->ignoreFirstLine;
    }

    /**
     * @return CsvOptions
     */
    public function ignoreFirstLine()
    {
        $this->ignoreFirstLine = true;
        return $this;
    }


    /**
     * @return CsvOptions
     */
    public function includeFirstLine()
    {
        $this->ignoreFirstLine = false;
        return $this;
    }


    /**
     * @return string
     */
    public function getInputFolder()
    {
        return $this->inputFolder;
    }

    /**
     * @param string $inputFolder
     * @return CsvOptions
     */
    public function setInputFolder($inputFolder)
    {
        $this->inputFolder = $inputFolder;
        return $this;
    }

    /**
     * @param string $append
     * @return CsvOptions
     */
    public function appendDefaultInputFolder($append)
    {
        $this->inputFolder = self::DEFAULT_INPUT_FOLDER.$append;
        return $this;
    }

    /**
     * @return string
     */
    public function getOutputFolder()
    {
        return $this->outputFolder;
    }

    /**
     * @param string $outputFolder
     * @return CsvOptions
     */
    public function setOutputFolder($outputFolder)
    {
        $this->outputFolder = $outputFolder;
        return $this;
    }

    /**
     * @param string $append
     * @return CsvOptions
     */
    public function appendDefaultOutputFolder($append)
    {
        $this->outputFolder = self::DEFAULT_OUTPUT_FOLDER.$append;
        return $this; 
    }
    

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     * @return CsvOptions
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
        return $this;
    }

    /**
     * @return string
     */
    public function getFopenMode()
    {
        return $this->fopenMode;
    }

    /**
     * @param string $fopenMode
     * @return CsvOptions
     */
    public function setFopenMode($fopenMode)
    {
        $this->fopenMode = $fopenMode;
        return $this;
    }

    /**
     * @return string
     */
    public function getSeparatorSymbol()
    {
        return $this->separatorSymbol;
    }

    /**
     * @param string $separatorSymbol
     * @return CsvOptions
     */
    public function setSeparatorSymbol($separatorSymbol)
    {
        $this->separatorSymbol = $separatorSymbol;
        return $this;
    }


    /**
     * @return CsvOptions
     */
    public function setCommaSeparator()
    {
        $this->separatorSymbol = ',';
        return $this;
    }

    /**
     * @return CsvOptions
     */
    public function setSemicolonSeparator()
    {
        $this->separatorSymbol = ';';
        return $this;
    }

    /**
     * @return CsvOptions
     */
    public function setPipeSeparator()
    {
        $this->separatorSymbol = '|';
        return $this;
    }







}