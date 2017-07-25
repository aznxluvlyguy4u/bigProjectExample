<?php

namespace AppBundle\Service\Migration;

use AppBundle\Util\CommandUtil;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class AnimalTableMigrator
 *
 * Migrating the data from the animal_migration_table to animal table.
 */
class AnimalTableMigrator extends Migrator2017JunServiceBase implements IMigratorService
{
    const BATCH_SIZE = 10000;

    /** @inheritdoc */
    public function __construct(ObjectManager $em, $rootDir)
    {
        parent::__construct($em, $rootDir, self::BATCH_SIZE);
    }


    /** @inheritdoc */
    public function run(CommandUtil $cmdUtil)
    {
        parent::run($cmdUtil);
    }


}