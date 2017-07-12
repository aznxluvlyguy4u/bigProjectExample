<?php


namespace AppBundle\Util;


use AppBundle\Component\Builder\CsvOptions;
use AppBundle\Enumerator\ImportFolderName;

class MigrationUtil
{
    /**
     * @param string $fileName
     * @param string $rootDir
     * @return CsvOptions
     */
    public static function createInitialValuesFolderCsvImport($fileName, $rootDir)
    {
        $csvOptions = (new CsvOptions())
            ->appendDefaultInputFolder(ImportFolderName::INITIAL_VALUES)
            ->setFileName($fileName)
            ->setPipeSeparator()
        ;
        FilesystemUtil::createFolderPathsFromCsvOptionsIfNull($rootDir, $csvOptions);

        return $csvOptions;
    }
}