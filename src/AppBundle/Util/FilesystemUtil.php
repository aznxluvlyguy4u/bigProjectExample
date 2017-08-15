<?php


namespace AppBundle\Util;



use AppBundle\Component\Builder\CsvOptions;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Monolog\Logger;
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
     * Get the pathname from concatenated directory and filename,
     * regardless whether the directory has a trailing forward slash or not.
     *
     * @param string $dir
     * @param string $filename
     * @return string
     */
    public static function concatDirAndFilename($dir, $filename)
    {
        return rtrim($dir,'/').'/'. $filename;
    }


    /**
     * @param string $rootFolder
     * @param array|string $fileNames
     * @param Filesystem $fs
     * @return bool
     */
    public static function filesExist($rootFolder, $fileNames, $fs = null)
    {
        $fullPathFileNames = [];
        if(is_array($fileNames)) {
            foreach ($fileNames as $fileName) {
                $fullPathFileNames[] = self::concatDirAndFilename($rootFolder,$fileName);
            }

        } elseif (is_string($fileNames)) {
            $fullPathFileNames[] = self::concatDirAndFilename($rootFolder,$fileName);

        } else {
            return false;
        }

        if($fs instanceof Filesystem) {
            return $fs->exists($fullPathFileNames);
        }

        $fs = new Filesystem();
        $filesExist = $fs->exists($fullPathFileNames);
        $fs = null;

        return $filesExist;
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
     * Copy the contents of a directory to another directory
     * @param $src
     * @param $dst
     * @param $fs
     * @param $logger
     */
    public static function recurseCopy($src, $dst, Filesystem $fs = null, Logger $logger = null){
        if($logger != null) { $logger->notice('RECURSIVE COPY SRC = ' . $src . ' DEST = ' . $dst); }

        $useTempFs = $fs == null;
        if($useTempFs) { $fs = new Filesystem(); }

        $dir = opendir($src);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    self::recurseCopy($src . '/' . $file,$dst . '/' . $file, $fs, $logger);
                }
                else {
                    $fs->copy($src . '/' . $file,$dst . '/' . $file);
                }
            }
        }
        closedir($dir);

        if($useTempFs) { $fs = null; }
    }

    
    /**
     * @param $src
     * @param $fs
     * @param $logger
     */
    public static function purgeFolder($src, Filesystem $fs = null, Logger $logger = null) {
        $useTempFs = $fs == null;
        if($useTempFs) { $fs = new Filesystem(); }

        if($logger != null) { $logger->notice('PURGE SRC = ' . $src); }

        $dir = opendir($src);
        while(false !== ( $obj = readdir($dir)) ) {
            if($logger != null) { $logger->notice($obj); }
            if (( $obj != '.' ) && ( $obj != '..' )) {
                if ( is_dir($src . '/' . $obj) ) {
                    self::recursiveRemoveDirectory($src . '/' . $obj, $logger);
                }
                else {
                    $fs->remove($src . '/' . $obj);
                }
            }
        }
        closedir($dir);

        if($useTempFs) { $fs = null; }
    }


    /**
     * @param string|array $directory
     * @param Logger $logger
     */
    public static function recursiveRemoveDirectory($directory, $logger = null)
    {
        if(is_array($directory) || $directory instanceof ArrayCollection) {
            foreach ($directory as $dir) {
                self::recursiveRemoveSingleDirectory($dir, $logger);
            }
        } else {
            self::recursiveRemoveSingleDirectory($directory, $logger);
        }
    }


    /**
     * @param string $directory
     * @param Logger $logger
     */
    private static function recursiveRemoveSingleDirectory($directory, $logger = null)
    {
        if(is_dir($directory))
        {
            if($logger != null) { $logger->notice('PURGING DIR: '.$directory); }
            foreach(glob("{$directory}/*") as $file)
            {
                if(is_dir($file)) {
                    self::recursiveRemoveDirectory($file);
                } else {
                    if($logger != null) { $logger->notice('REMOVING FILE: '.basename($file)); }
                    unlink($file);
                }
            }
            rmdir($directory);
            if($logger != null) { $logger->notice('REMOVED DIR: '.$directory); }

        } else {
            if($logger != null) { $logger->notice('DIR DOES NOT EXIST: '.$directory); }
        }
    }


    /**
     * @param string|array $folderPath
     * @param Filesystem|null $fs
     * @param Logger|null $logger
     */
    public static function createFolderPathIfNull($folderPath, $fs = null, $logger = null)
    {
        $createNewFileSystem = $fs == null;
        if($createNewFileSystem) { $fs = new Filesystem(); }

        if(is_array($folderPath)) {
            foreach ($folderPath as $path) {
                self::createFolderIfNullByFolderPathString($path, $fs, $logger);
            }
        } else {
            self::createFolderIfNullByFolderPathString($folderPath, $fs, $logger);
        }

        if($createNewFileSystem) { $fs = null; }
    }


    /**
     * @param string $folderPath
     * @param Filesystem $fs
     * @param Logger|null $logger
     */
    private static function createFolderIfNullByFolderPathString($folderPath, Filesystem $fs, $logger = null)
    {
        if(!$fs->exists($folderPath)) {
            $fs->mkdir($folderPath);
            if($logger) {
                $logger->notice('CREATED DIR ' . $folderPath);
            }
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


    /**
     * Get filename without extension
     *
     * @param $path
     * @return mixed
     */
    public static function filename($path)
    {
        return pathinfo($path,PATHINFO_FILENAME);
    }


    /**
     * @param $path
     * @return mixed
     */
    public static function extension($path)
    {
        return pathinfo($path,PATHINFO_EXTENSION);
    }
}