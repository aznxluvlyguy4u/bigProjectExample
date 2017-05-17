<?php


namespace AppBundle\Util;



use AppBundle\Component\Builder\CsvOptions;
use Symfony\Component\Filesystem\Filesystem;

class FilesystemUtil
{
    /**
     * @param string $rootFolder
     * @param CsvOptions $csvOptions
     * @return bool
     */
    public static function csvFileExists($rootFolder, CsvOptions $csvOptions)
    {
        return self::filesExist($rootFolder.ltrim($csvOptions->getInputFolder(),'app'), [$csvOptions->getFileName()]);
    }

    /**
     * @param string $rootFolder
     * @param array|string $fileNames
     * @return bool
     */
    public static function filesExist($rootFolder, $fileNames)
    {
        $fullPathFileNames = [];
        if(is_array($fileNames)) {
            foreach ($fileNames as $fileName) {
                $fullPathFileNames[] = rtrim($rootFolder,'/').'/'.$fileName;
            }

        } elseif (is_string($fileNames)) {
            $fullPathFileNames[] = rtrim($rootFolder,'/').'/'.$fileNames;

        } else {
            return false;
        }
        $fs = new Filesystem();
        return $fs->exists($fullPathFileNames);
    }


    /**
     * @param array $folderPaths
     */
    public static function deleteAllFilesInFolders(array $folderPaths)
    {
        foreach ($folderPaths as $folderPath) {
            array_map('unlink', glob(rtrim($folderPath,'/')."/*.*"));
        }
    }


    /**
     * @param string $folderPath
     */
    public static function createFolderPathIfNull($folderPath)
    {
        $fs = new Filesystem();
        if(!$fs->exists($folderPath)) {
            $fs->mkdir($folderPath);
        }
    }


    /**
     * @param string $rootDir
     * @param CsvOptions $csvOptions
     */
    public static function createFolderPathsFromCsvOptionsIfNull($rootDir, $csvOptions)
    {
        if(!is_string($rootDir) OR !($csvOptions instanceof CsvOptions)) { return; }

        //removing the app at the end
        $pathStart = self::rtrimRootDir($rootDir);

        /* Setup folders */
        FilesystemUtil::createFolderPathIfNull($pathStart.$csvOptions->getInputFolder());
        FilesystemUtil::createFolderPathIfNull($pathStart.$csvOptions->getOutputFolder());
    }


    /**
     * @param string $rootDir
     * @return string
     */
    public static function rtrimRootDir($rootDir)
    {
        return substr($rootDir, 0, strlen($rootDir)-3);
    }


    /**
     * @param string $rootDir
     * @return string
     */
    public static function getWebDirectory($rootDir) { return realpath($rootDir . '/../web'); }

    /**
     * @param string $rootDir
     * @return string
     */
    public static function getAssetsDirectory($rootDir) { return self::getWebDirectory($rootDir).'/assets'; }

    /**
     * @param string $rootDir
     * @return string
     */
    public static function getImagesDirectory($rootDir) { return self::getAssetsDirectory($rootDir).'/images'; }
}