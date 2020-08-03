<?php


namespace AppBundle\Worker\Logic;


use AppBundle\Constant\RvoSetting;
use AppBundle\Service\BaseSerializer;
use AppBundle\Util\StringUtil;
use AppBundle\Util\XmlUtil;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

class InternalWorkerActionBase
{
    /** @var EntityManagerInterface */
    protected $em;
    /** @var LoggerInterface */
    protected $logger;
    /** @var BaseSerializer */
    protected $serializer;


    /**
     * @required
     *
     * @param EntityManagerInterface $em
     */
    public function setEntityManager(EntityManagerInterface $em)
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
     * @required
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
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