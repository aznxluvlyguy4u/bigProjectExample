<?php

namespace AppBundle\Service;


use Aws\S3\S3Client;
use  Aws\Credentials\Credentials;

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
    

    public function __construct($credentials = array(), $region, $version, $bucket, $currentEnvironment = null)
    {
        $this->accessKeyId = $credentials[0];
        $this->secretKey = $credentials[1];
        
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
        switch($currentEnvironment) {
            case 'prod':
                $this->pathApppendage = "";
                break;
            case 'stage':
                $this->pathApppendage = 'stage/';
                break;
            case 'dev':
                $this->pathApppendage = 'dev/';
                break;
            case 'test':
                $this->pathApppendage = 'dev/';
                break;
            case 'local':
                $this->pathApppendage = 'dev/';
                break;
            default;
                $this->pathApppendage = 'dev/';
                break;
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
     * Upload a file with the given filepath to the location in the S3 Bucket specified by the key.
     * And return the download url.
     *
     * @param $filepath
     * @param $key
     * @return string
     */
    public function uploadPdf($filepath, $key)
    {
        return $this->upload($filepath, $key, 'application/pdf');
    }

    /**
     * Upload a file with the given filepath to the location in the S3 Bucket specified by the key.
     * And return the download url.
     *
     * @param $filepath
     * @param $key
     * @return string
     */
    public function upload($filepath, $key, $contentType)
    {
        $key = $this->pathApppendage.$key;

        $result = $this->s3Service->putObject(array(
            'Bucket' => $this->bucket,
            'Key'    => $key,
            'Body'   => file_get_contents($filepath),
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
}