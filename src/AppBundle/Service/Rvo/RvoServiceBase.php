<?php


namespace AppBundle\Service\Rvo;


use AppBundle\Enumerator\RvoPathEnum;
use AppBundle\Service\BaseSerializer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

abstract class RvoServiceBase
{
    /** @var string */
    protected $path;

    /** @var string */
    protected $rvoIrBaseUrl;
    /** @var string */
    protected $rvoIrUserName;
    /** @var string */
    protected $rvoIrPassword;
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
    protected function getUrl(): string
    {
        return $this->rvoIrBaseUrl . $this->path;
    }
}