<?php


namespace AppBundle\MixBlup;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;

/**
 * Class MixBlupDataFileBase
 * @package AppBundle\MixBlup
 */
class MixBlupDataFileBase
{
    /** @var Connection */
    protected $conn;

    /** @var ObjectManager */
    protected $em;
    

    /**
     * MixBlupDataFileBase constructor.
     * @param ObjectManager $em
     */
    public function __construct(ObjectManager $em)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
    }
}