<?php


namespace AppBundle\Processor\Rvo;


use AppBundle\Enumerator\HttpMethod;
use AppBundle\Exception\Rvo\RvoExternalWorkerException;
use AppBundle\Processor\SqsProcessorInterface;
use AppBundle\Service\AwsRawExternalSqsService;
use AppBundle\Service\AwsRawInternalSqsService;
use AppBundle\Util\CurlUtil;
use AppBundle\Util\RvoUtil;
use Curl\Curl;
use Psr\Log\LoggerInterface;

class RawExternalWorker implements SqsProcessorInterface
{
    /** @var AwsRawExternalSqsService */
    private $rawExternalQueueService;
    /** @var AwsRawInternalSqsService */
    private $rawInternalQueueService;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    protected $rvoIrBaseUrl;
    /** @var string */
    private $rvoIrUserName;
    /** @var string */
    private $rvoIrPassword;

    public function __construct(
        AwsRawExternalSqsService $rawExternalQueueService,
        AwsRawInternalSqsService $rawInternalQueueService,
        LoggerInterface $logger,
        string $rvoIrBaseUrl,
        string $rvoIrUserName,
        string $rvoIrPassword
    )
    {
        $this->rawExternalQueueService = $rawExternalQueueService;
        $this->rawInternalQueueService = $rawInternalQueueService;

        $this->logger = $logger;

        $this->rvoIrBaseUrl = $rvoIrBaseUrl;
        $this->rvoIrUserName = $rvoIrUserName;
        $this->rvoIrPassword = $rvoIrPassword;
    }


    public function run()
    {
        try {
            // TODO Don't loop to prevent memory leaks
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            $this->logger->error($exception->getTraceAsString());

            // Move message to error queue
        }
    }


    private function sendRequestToRvo()
    {
        $xmlRequestBody = 'read it from the external queue';
        $requestType = 'get from TaskType key in message';

        $httpMethod = RvoUtil::REQUEST_TYPES[RvoUtil::HTTP_METHOD];
        $rvoPath = RvoUtil::REQUEST_TYPES[RvoUtil::RVO_PATH];
        $url = $this->rvoIrBaseUrl . $rvoPath;

        switch ($httpMethod) {
            case HttpMethod::GET:
                $curl = $this->get($url);
                break;
            case HttpMethod::POST:
                $curl = $this->post($url, $xmlRequestBody);
                break;
            default:
                throw new \Exception(
                    'Http Method not implemented yet '.$httpMethod. ' for requestType '. $requestType
                );
        }

        if (!CurlUtil::is200Response($curl)) {
            throw new RvoExternalWorkerException($curl->response, $curl->getHttpStatus());
        }

        $rvoXmlResponseContent = $curl->getResponse();
        // TODO send it to the INTERNAL QUEUE
    }


    private function createCurl(): Curl
    {
        $curl = new Curl();
        $curl->setBasicAuthentication($this->rvoIrUserName, $this->rvoIrPassword);
        $curl->setHeader('SOAPAction', 'true');
        return $curl;
    }


    private function post(string $url, string $xmlBody): Curl
    {
        $curl = $this->createCurl();
        $curl->post($url, $xmlBody,false);
        return $curl;
    }


    private function get(string $url, $queryParameters = []): Curl
    {
        $curl = $this->createCurl();
        $curl->get($url, $queryParameters);
        return $curl;
    }

}