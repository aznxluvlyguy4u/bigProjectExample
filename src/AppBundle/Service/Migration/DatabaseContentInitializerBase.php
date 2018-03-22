<?php


namespace AppBundle\Service\Migration;


use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use Symfony\Component\Translation\TranslatorInterface;

class DatabaseContentInitializerBase
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var Connection */
    private $conn;
    /** @var Logger */
    private $logger;
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(EntityManagerInterface $em,
                                Logger $logger,
                                TranslatorInterface $translator
    )
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->logger = $logger;
        $this->translator = $translator;
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEm()
    {
        return $this->em;
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->conn;
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return TranslatorInterface
     */
    public function getTranslator()
    {
        return $this->translator;
    }


}