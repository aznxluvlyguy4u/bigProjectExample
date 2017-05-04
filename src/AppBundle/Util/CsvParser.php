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

}