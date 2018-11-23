<?php


namespace AppBundle\Util;


use AppBundle\Component\Builder\CsvOptions;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class CsvParser
{
    /**
     * @param CsvOptions $csvOptions
     * @return SplFileInfo
     */
    private static function getSplFileInfo($csvOptions = null)
    {
        if(!($csvOptions instanceof CsvOptions)) {
            //create default options
            $csvOptions = new CsvOptions();
        }

        $finder = new Finder();
        $finder->files()
            ->in($csvOptions->getInputFolder())
            ->name($csvOptions->getFileName())
        ;
        foreach ($finder as $file) { return $file; }

        return null;
    }


    /**
     * @param CsvOptions $csvOptions
     * @return array
     */
    public static function parse($csvOptions = null)
    {
        $csv = self::getSplFileInfo($csvOptions);

        $rows = array();
        if (($handle = fopen($csv->getRealPath(), $csvOptions->getFopenMode())) !== FALSE) {
            $i = 0;
            while (($data = fgetcsv($handle, null, $csvOptions->getSeparatorSymbol())) !== FALSE) {
                $i++;
                if ($csvOptions->isIgnoreFirstLine() && $i == 1) { continue; }
                $rows[] = $data;
                gc_collect_cycles();
            }
            fclose($handle);
        }

        return $rows;
    }


    /**
     * @param CsvOptions $csvOptions
     * @return array
     */
    public static function parseHeaders($csvOptions = null)
    {
        $csv = self::getSplFileInfo($csvOptions);

        $data = [null];
        $handle = fopen($csv->getRealPath(), $csvOptions->getFopenMode());
        if ($handle !== FALSE) {
            $data = fgetcsv($handle, null, $csvOptions->getSeparatorSymbol());
            gc_collect_cycles();
            fclose($handle);
        }

        return $data;
    }


    /**
     * @param string $inputFolder
     * @param string $fileName
     * @return array
     */
    public static function parseSpaceSeparatedFile($inputFolder, $fileName)
    {
        $csvOption = (new CsvOptions())
            ->setPipeSeparator()
            ->includeFirstLine()
            ->setInputFolder($inputFolder)
            ->setFileName($fileName)
        ;

        $encapsulatedRows = self::parse($csvOption);

        $results = [];
        foreach ($encapsulatedRows as $encapsulatedRow) {
            $row = ArrayUtil::get(0, $encapsulatedRow);
            if($row) {
                $values = preg_split("/ +/", $row);
                $results[] = $values;
            }
        }

        return $results;
    }


    /**
     * @param string $filename
     * @param string $folderName
     * @param int $startPositionId
     * @param int $endPositionId
     * @return array
     * @throws \Exception
     */
    public static function extractIdsInColumnOfTextFile(string $filename,
                                                  string $folderName,
                                                  int $startPositionId = 0,
                                                  int $endPositionId = 9
    ): array
    {
        $csvOptions = new CsvOptions();
        $csvOptions
            ->ignoreFirstLine()
            ->setPipeSeparator()
            ->setFileName($filename)
            ->setInputFolder($folderName)
        ;

        $csvAsArray = CsvParser::parse($csvOptions);

        $idsInInput = [];
        foreach ($csvAsArray as $xRows)
        {
            $row = array_shift($xRows);
            if (empty($row)) {
                continue;
            }

            if (!is_string($row)) {
                throw new \Exception('NOT A STRING: '.
                    (is_array($row) ? implode(', ', $row) : $row)
                    , 400);
            }

            $animalId = trim(substr($row, $startPositionId, $endPositionId));

            if (!ctype_digit($animalId)) {
                throw new \Exception('NOT A NUMBER: '.$animalId, 400);
            }

            $animalId = intval($animalId);
            $idsInInput[$animalId] = $animalId;
        }

        return $idsInInput;
    }
}