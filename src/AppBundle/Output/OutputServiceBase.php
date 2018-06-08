<?php


namespace AppBundle\Output;


use AppBundle\Service\BaseSerializer;
use AppBundle\SqlView\SqlViewManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class OutputServiceBase
{
    /** @var BaseSerializer */
    private $serializer;
    /** @var EntityManagerInterface */
    private $em;
    /** @var SqlViewManagerInterface */
    private $sqlViewManager;
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(BaseSerializer $serializer,
                                EntityManagerInterface $em,
                                SqlViewManagerInterface $sqlViewManager,
                                TranslatorInterface $translator)
    {
        $this->serializer = $serializer;
        $this->em = $em;
        $this->sqlViewManager = $sqlViewManager;
        $this->translator = $translator;
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

    /**
     * @return TranslatorInterface
     */
    public function getTranslator()
    {
        return $this->translator;
    }



}