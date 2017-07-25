<?php

namespace AppBundle\Service\Migration;

use AppBundle\Util\CommandUtil;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class VsmMigratorService
 */
class VsmMigratorService extends Migrator2017JunServiceBase
{
    /** @var AnimalTableImporter */
    private $animalTableImporter;
    /** @var AnimalTableMigrator */
    private $animalTableMigrator;
    /** @var TagReplaceMigrator */
    private $tagReplaceMigrator;
    /** @var WormResistanceMigrator */
    private $wormResistanceMigrator;


    public function __construct(ObjectManager $em, $rootDir,
                                AnimalTableImporter $animalTableImporter,
                                AnimalTableMigrator $animalTableMigrator,
                                TagReplaceMigrator $tagReplaceMigrator,
                                WormResistanceMigrator $wormResistanceMigrator
    )
    {
        parent::__construct($em, $rootDir);

        $this->animalTableImporter = $animalTableImporter;
        $this->animalTableMigrator = $animalTableMigrator;
        $this->tagReplaceMigrator = $tagReplaceMigrator;
        $this->wormResistanceMigrator = $wormResistanceMigrator;
    }


    /**
     * @param CommandUtil $cmdUtil
     */
    public function run(CommandUtil $cmdUtil)
    {
        parent::run($cmdUtil);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            ' ', "\n",
            'Choose option: ', "\n",
            '1: AnimalTableImporter options ...', "\n",
            '2: Migrate Animals from animal_migration_table to animal table ...', "\n",
//            '3: Migrate TagReplaces', "\n",
            '----------------------------------------------------', "\n",
            '10: Migrate TagReplaces (WARNING make sure no other declareBases are inserted during this)', "\n",
//            '4: Migrate AnimalTable data', "\n",
//            '13: Migrate Performance Measurements', "\n",
            '----------------------------------------------------', "\n",
            '20: Migrate WormResistance records', "\n",
//            '16: Import animal_migration_table from exported csv', "\n",
//            '17: Export vsm_id_group to csv', "\n",
//            '18: Import vsm_id_group from exported csv', "\n",
//            '21: Export uln by animalId to csv', "\n",
//            '22: Import uln by animalId to csv', "\n",
//            '----------------------------------------------------', "\n",
//            '23: Fix animal table after animalTable migration', "\n",
//            '24: Fix missing ulns by data in declares and migrationTable', "\n",
//            '25: Add missing animals to migrationTable', "\n",
//            '26: Fix duplicateDeclareTagTransfers', "\n",
//            '27: Fix vsmIds part1', "\n",
//            '28: Fix vsmIds part2', "\n",
//            '29: Migrate dateOfDeath & isAlive status', "\n",
//            '----------------------------------------------------', "\n",
//            '31: Migrate BirthWeights into weight and birthProgress into animal', "\n",
//            '39: Fill missing british ulnNumbers in AnimalMigrationTable', "\n",
//            '----------------------------------------------------', "\n",
//            '40: Fill missing ulnNumbers in AnimalMigrationTable', "\n",
//            '41: Fix animalIds in AnimalMigrationTable (likely incorrect due to duplicate fix)', "\n",
//            '42: Fix genderInDatabase values in AnimalMigrationTable (likely incorrect due to genderChange)', "\n",
//            '43: Fix parentId values in AnimalMigrationTable', "\n",
//            '44: Fix inverted primary and secondary vsmIds in the vsmIdGroup table', "\n",
//            '----------------------------------------------------', "\n",
//            '45: Migrate AnimalTable data V2', "\n",
//            '46: Migrate AnimalTable data: UPDATE Synced Animals', "\n",
//            '47: Fix missing pedigreeNumbers', "\n",
//            '48: Set missing parents on animal', "\n",
            '----------------------------------------------------', "\n",
            'other: Exit VsmMigrator', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {
            case 1: $this->animalTableImporter->run($this->cmdUtil); break;
            case 2: $this->animalTableMigrator->run($this->cmdUtil); break;
//            case 3:
//                break;
//            case 4:
//                break;
//            case 5:
//                break;
//            case 6:
//                break;
//            case 7:
//                break;
//            case 8:
//                break;
//            case 9:
//                break;
            case 10: $this->tagReplaceMigrator->run($this->cmdUtil); break;
//            case 11:
//                break;
//            case 12:
//                break;
//            case 13:
//                break;
//            case 14:
//                break;
//            case 15:
//                break;
//            case 16:
//                break;
//            case 17:
//                break;
            case 20: $this->wormResistanceMigrator->run($this->cmdUtil); break;
            default: return;
        }
        $this->run($this->cmdUtil);
    }


}