<?php


namespace AppBundle\Service\Rvo;


use AppBundle\Constant\RvoSetting;
use AppBundle\Service\BaseSerializer;
use AppBundle\Util\StringUtil;
use AppBundle\Util\XmlUtil;
use Curl\Curl;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

abstract class RvoServiceBase
{
    /** @var string */
    protected $path;

    /** @var string */
    protected $rvoIrBaseUrl;
    /** @var string */
    private $rvoIrUserName;
    /** @var string */
    private $rvoIrPassword;
    /** @var LoggerInterface */
    protected $logger;
    /** @var EntityManagerInterface */
    protected $em;
    /** @var BaseSerializer */
    protected $serializer;

    /**
     * @required
     *
     * @param string $rvoIrBaseUrl
     */
    public function setRvoIrBaseUrl(string $rvoIrBaseUrl): void
    {
        $this->rvoIrBaseUrl = $rvoIrBaseUrl;
    }

    /**
     * @required
     *
     * @param string $rvoIrUserName
     */
    public function setRvoIrUserName(string $rvoIrUserName): void
    {
        $this->rvoIrUserName = $rvoIrUserName;
    }

    /**
     * @required
     *
     * @param string $rvoIrPassword
     */
    public function setRvoIrPassword(string $rvoIrPassword): void
    {
        $this->rvoIrPassword = $rvoIrPassword;
    }

    /**
     * @required
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @required
     *
     * @param EntityManagerInterface $em
     */
    public function setEntityManager(EntityManagerInterface $em): void
    {
        $this->em = $em;
    }

    /**
     * @required
     *
     * @param BaseSerializer $serializer
     */
    public function setSerializer(BaseSerializer $serializer): void
    {
        $this->serializer = $serializer;
    }

    /**
     * @return string
     */
    private function getUrl(): string
    {
        return $this->rvoIrBaseUrl . $this->path;
    }


    private function createCurl(): Curl
    {
        $curl = new Curl();
        $curl->setBasicAuthentication($this->rvoIrUserName, $this->rvoIrPassword);
        $curl->setHeader('SOAPAction', 'true');
        return $curl;
    }


    protected function post(string $xmlBody): Curl
    {
        $curl = $this->createCurl();
        $curl->post($this->getUrl(), $xmlBody,false);
        return $curl;
    }


    protected function parseRvoResponseObject(string $rvoXmlResponseContent, string $className)
    {
        $encoder = new XmlEncoder();
        $arrayOuter = $encoder->decode($rvoXmlResponseContent, 'xml', XmlUtil::rvoXmlOptions());

        $rvoClassKey = lcfirst(StringUtil::getEntityName($className));

        $arrayInner = $arrayOuter[RvoSetting::XML_SOAP_ENV_BODY][$rvoClassKey];
        XmlUtil::cleanXmlFormatting($arrayInner);
        $arrayInnerCleaned = $arrayInner[$rvoClassKey];

        $jsonInnerCleaned = json_encode($arrayInnerCleaned);

        return $this->serializer->deserializeToObject(
            $jsonInnerCleaned,
            $className,
            null
        );
    }
}