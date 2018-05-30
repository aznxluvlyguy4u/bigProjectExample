<?php


namespace AppBundle\Output;


use AppBundle\Service\BaseSerializer;
use AppBundle\SqlView\SqlViewManagerInterface;
use Doctrine\ORM\EntityManagerInterface;

class OutputServiceBase
{
    /** @var BaseSerializer */
    private $serializer;
    /** @var EntityManagerInterface */
    private $em;
    /** @var SqlViewManagerInterface */
    private $sqlViewManager;

    public function __construct(BaseSerializer $serializer,
                                EntityManagerInterface $em,
                                SqlViewManagerInterface $sqlViewManager)
    {
        $this->serializer = $serializer;
        $this->em = $em;
        $this->sqlViewManager = $sqlViewManager;
    }

    /**
     * @return BaseSerializer
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * @return EntityManagerInterface
     */
    public function getManager()
    {
        return $this->em;
    }

    /**
     * @return SqlViewManagerInterface
     */
    public function getSqlViewManager()
    {
        return $this->sqlViewManager;
    }



}