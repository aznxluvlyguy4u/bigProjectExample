<?php


namespace AppBundle\Service;


interface QueueServiceInterface
{
    /**
     * @param string $queueId
     * @return string|null
     */
    public function createQueueUrl($queueId);


    /**
     * Send a request message to Queue.
     *
     * @param string $requestId
     * @param string $messageBody
     * @param string $requestType
     * @return array|null
     */
    public function send($messageBody, $requestType, $requestId);

    /**
     * Send a request message to error Queue.
     *
     * @param string $requestId
     * @param string $messageBody
     * @param string $requestType
     * @return array|null
     */
    public function sendToErrorQueue($messageBody, $requestType, $requestId);


    /**
     * Send a request message to given Queue.
     *
     * @param string $requestId
     * @param string $messageBody
     * @param string $requestType
     * @return array|null
     */
    public function sendDeclareResponse($messageBody, $requestType, $requestId);


    /**
     * @param $messageAttributeNames
     * @return \Aws\Result
     */
    public function getNextMessage(array $messageAttributeNames = []);


    /**
     * @param $messageAttributeNames
     * @return \Aws\Result
     */
    public function getNextErrorMessage(array $messageAttributeNames = []);


    /**
     * @return string
     */
    public function getQueueService();


    /**
     * @param string $receiptHandleOrAwsResult
     * @return \Aws\Result
     */
    public function deleteMessage($receiptHandleOrAwsResult);


    /**
     * @param string $receiptHandleOrAwsResult
     * @return \Aws\Result
     */
    public function deleteErrorMessage($receiptHandleOrAwsResult);


    /**
     * WARNING!
     * If this function is used in a while loop to continuously poll for messages in a queue
     * at least include a wait of 1 second within the loop.
     * Otherwise you will get a huge bill from Amazon.
     *
     * @return int
     */
    public function getSizeOfQueue();


    /**
     * WARNING!
     * If this function is used in a while loop to continuously poll for messages in a queue
     * at least include a wait of 1 second within the loop.
     * Otherwise you will get a huge bill from Amazon.
     *
     * @return int
     */
    public function getSizeOfErrorQueue();


    /**
     * @param \Aws\Result $response
     * @param bool $decodeJsonString
     * @return mixed
     */
    public static function getMessageBodyFromResponse($response, bool $decodeJsonString = true);


    /**
     * @param $response
     * @return array
     */
    public static function getMessageAttributes($response);


    /**
     * @param \Aws\Result $response
     * @param $key
     * @return mixed
     */
    public static function getResponseValue($response, $key);


    /**
     * @return string
     */
    public function getQueueId();

    /**
     * @return string
     */
    public function getErrorQueueId();


    /**
     * @param \Aws\Result $receiptHandleOrAwsResult
     * @return bool
     */
    public static function hasMessage($receiptHandleOrAwsResult);


    /**
     * @param array $messageAttributeNames first value = TaskType, second value = messageId
     * @return bool
     */
    public function moveErrorQueueMessagesToPrimaryQueue(array $messageAttributeNames);
}