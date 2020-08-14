<?php


namespace AppBundle\Worker\Logic;


use AppBundle\Constant\RvoSetting;
use AppBundle\Service\BaseSerializer;
use AppBundle\Util\StringUtil;
use AppBundle\Util\XmlUtil;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

abstract class RawInternalWorkerActionBase
{
    /** @var EntityManagerInterface */
    protected $em;
    /** @var LoggerInterface */
    protected $logger;
    /** @var BaseSerializer */
    protected $serializer;

    public function __construct(
        BaseSerializer $serializer,
        EntityManagerInterface $em,
        LoggerInterface $logger
    )
    {
        $this->serializer = $serializer;
        $this->em = $em;
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