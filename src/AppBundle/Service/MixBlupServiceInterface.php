<?php


namespace AppBundle\Service;

/**
 * Interface MixBlupServiceInterface
 * @package AppBundle\Service
 */
interface MixBlupServiceInterface
{
    /**
     * Generates the data for all the files,
     * writes the data to the text input files,
     * uploads the text files to the S3-Bucket,
     * and sends a message to sqs queue with the overview data of the uploaded files.
     */
    public function run();
}