<?php

namespace AppBundle\Service;


use AppBundle\Constant\Environment;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Aws\Credentials\Credentials;

class AWSSimpleStorageService
{
    /** @var string */
    protected $region;
    /** @var string */
    private $version;
    /** @var string */
    private $accessKeyId;
    /** @var string */
    private $secretKey;
    /** @var S3Client; */
    private $s3Service;
    /** @var string */
    private $bucket;
    /** @var string */
    private $pathApppendage;

    /**
     * @var Credentials
     */
    private $awsCredentials;
    

    public function __construct($acccessKeyId, $secretKey, $region, $version, $bucket, $selectedEnvironment, $systemEnvironment)
    {
        $this->accessKeyId = $acccessKeyId;
        $this->secretKey = $secretKey;
        
        $this->awsCredentials =  new Credentials($this->accessKeyId, $this->secretKey);
        $this->region = $region;
        $this->version = $version;
        $this->bucket = $bucket;
        
        $s3Config = array(
            'region'  => $this->region,
            'version' => $this->version,
            'credentials' => $this->awsCredentials
        );
        
        $this->s3Service = new S3Client($s3Config);


        /**
         * Get current environment, set separate files based on environment
         */
        if ($systemEnvironment = 'test') {
            $this->pathApppendage = "test/";

        } else {
            switch($selectedEnvironment) {
                case Environment::PROD:
                    $this->pathApppendage = "production/";
                    break;
                case Environment::STAGE:
                    $this->pathApppendage = 'staging/';
                    break;
                case Environment::DEV:
                    $this->pathApppendage = 'dev/';
                    break;
                case Environment::TEST:
                    $this->pathApppendage = 'test/';
                    break;
                case Environment::LOCAL:
                    $this->pathApppendage = 'dev/';
                    break;
                default;
                    $this->pathApppendage = 'dev/';
                    break;
            }
        }
    }

    /**
     * @return S3Client
     */
    public function getS3Client()
    {
        return $this->s3Service;
    }


    /**
     * Upload a file without a filepath to the location in the S3 Bucket specified by the key.
     * And return the download url.
     *
     * @param $file
     * @param $key
     * @return string
     */
    public function uploadPdf($file, $key)
    {
        return $this->upload($file, $key, 'application/pdf');
    }

    /**
     * Upload a file with the given filepath to the location in the S3 Bucket specified by the key.
     * And return the download url.
     *
     * @param $filepath
     * @param $key
     * @return string
     */
    public function uploadPdfFromFilePath($filepath, $key)
    {
        return $this->uploadFromFilePath($filepath, $key, 'application/pdf');
    }

    /**
     * Upload a file directly to the location in the S3 Bucket specified by the key.
     * And return the download url.
     *
     * @param $file
     * @param $key
     * @return string
     */
    public function upload($file, $key, $contentType)
    {
        $key = $this->pathApppendage.$key;

        $result = $this->s3Service->putObject(array(
            'Bucket' => $this->bucket,
            'Key'    => $key,
            'Body'   => $file,
            'ACL'    => 'private', //protect access to the uploaded file
            'ContentType' => $contentType
        ));

        $command = $this->s3Service->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key' => $key,
        ]);

        $request = $this->s3Service->createPresignedRequest($command, '+20 minutes');
        $url = (string) $request->getUri(); //The S3 download link including the accesstoken

        return $url;
    }

    /**
     * Upload a file with the given filepath to the location in the S3 Bucket specified by the key.
     * And return the download url.
     *
     * @param $filepath
     * @param $key
     * @return string
     */
    public function uploadFromFilePath($filepath, $key, $contentType)
    {
        return $this->upload(file_get_contents($filepath), $key, $contentType);
    }


    /**
     * Download a file from the S3 Bucket specified by the filepath,
     * and return the download file.
     *
     * @param $filepath
     * @return \Aws\Result|S3Exception|\Exception
     */
    public function downloadFile($filepath) {
        $result = null;
        $key = $this->pathApppendage . $filepath;
        // Get the object
        $result = $this->getS3Client()->getObject(array(
            'Bucket' => $this->bucket,
            'Key'    => $key
        ));

        return $result;
    }


    /**
     * Download a file from the S3 Bucket specified by the filepath,
     * and return the downloaded file contents.
     *
     * @param $filepath
     * @return mixed
     */
    public function downloadFileContents($filepath) {
        $result = $this->downloadFile($filepath);
        $stream = $result['Body'];
        return $stream->getContents();
    }

    /**
     * @return string
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * @return string
     */
    public function getPathApppendage()
    {
        return $this->pathApppendage;
    }



}