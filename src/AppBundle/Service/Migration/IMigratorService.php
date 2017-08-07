<?php


namespace AppBundle\Service\Migration;

use AppBundle\Util\CommandUtil;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Interface IMigratorService
 * @package AppBundle\Service
 */
interface IMigratorService
{
    /**
     * @param ObjectManager $em
     * @param string $rootDir
     */
    function __construct(ObjectManager $em, $rootDir);

    /**
     * @param CommandUtil $cmdUtil
     */
    function run(CommandUtil $cmdUtil);
}