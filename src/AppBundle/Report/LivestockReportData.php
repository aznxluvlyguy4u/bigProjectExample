<?php

namespace AppBundle\Report;


use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Client;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\EweRepository;
use AppBundle\Entity\Location;
use AppBundle\Entity\Ram;
use AppBundle\Entity\RamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

class LivestockReportData extends ReportBase
{
    const FILE_NAME_REPORT_TYPE = 'stallijst';
    const PEDIGREE_NULL_FILLER = '-';
    const ULN_NULL_FILLER = '-';

    /** @var array */
    private $data;

    /** @var EweRepository */
    private $eweRepository;

    /** @var RamRepository */
    private $ramRepository;

    /** @var Location */
    private $location;

    /**
     * InbreedingCoefficientReportData constructor.
     * @param ObjectManager $em
     * @param ArrayCollection $content
     * @param Client $client
     * @param Location $location
     */
    public function __construct(ObjectManager $em, ArrayCollection $content, Client $client, Location $location)
    {
        $this->client = $client;
        $this->location = $location;

        parent::__construct($em, $client, $this->parseFilename());

        $this->data = [];

        /** @var RamRepository $ramRepository */
        $this->ramRepository = $this->em->getRepository(Ram::class);
        /** @var EweRepository $eweRepository */
        $this->eweRepository = $this->em->getRepository(Ewe::class);
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }


    /**
     * @return string
     */
    private function parseFilename()
    {
        return self::FILE_NAME_REPORT_TYPE.'_'.$this->location->getUbn().'_'.$this->client->getLastName();
    }
}