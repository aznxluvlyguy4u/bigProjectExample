<?php

namespace AppBundle\Service\Migration;

use AppBundle\Service\DataFix\DuplicateAnimalsFixer;
use AppBundle\Service\DataFix\DuplicateLitterFixer;
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
    /** @var BirthDataMigrator */
    private $birthDataMigrator;
    /** @var ExteriorMigrator */
    private $exteriorMigrator;
    /** @var LitterMigrator */
    private $litterMigrator;
    /** @var TagReplaceMigrator */
    private $tagReplaceMigrator;
    /** @var WormResistanceMigrator */
    private $wormResistanceMigrator;

    /** @var DuplicateAnimalsFixer */
    private $duplicateAnimalsFixer;
    /** @var DuplicateLitterFixer */
    private $duplicateLitterFixer;

    public function __construct(ObjectManager $em, $rootDir,
                                AnimalTableImporter $animalTableImporter,
                                AnimalTableMigrator $animalTableMigrator,
                                BirthDataMigrator $birthDataMigrator,
                                ExteriorMigrator $exteriorMigrator,
                                LitterMigrator $litterMigrator,
                                TagReplaceMigrator $tagReplaceMigrator,
                                WormResistanceMigrator $wormResistanceMigrator,
                                DuplicateAnimalsFixer $duplicateAnimalsFixer,
                                DuplicateLitterFixer $duplicateLitterFixer
    )
    {
        parent::__construct($em, $rootDir);

        $this->animalTableImporter = $animalTableImporter;
        $this->animalTableMigrator = $animalTableMigrator;
        $this->birthDataMigrator = $birthDataMigrator;
        $this->exteriorMigrator = $exteriorMigrator;
        $this->litterMigrator = $litterMigrator;
        $this->tagReplaceMigrator = $tagReplaceMigrator;
        $this->wormResistanceMigrator = $wormResistanceMigrator;

        $this->duplicateAnimalsFixer = $duplicateAnimalsFixer;
        $this->duplicateLitterFixer = $duplicateLitterFixer;
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
            '----------------------------------------------------', "\n",
            '2: Migrate Animals from animal_migration_table to animal table ...', "\n",
            '3: Migrate TagReplaces (WARNING make sure no other declareBases are inserted during this)', "\n",
            '4: Fix duplicate animals by ulnNumber, using tagReplaces', "\n",
            '5: Fix Migrated Animals in animal table ...', "\n",
            '----------------------------------------------------', "\n",
            '6: Merge duplicate imported litters', "\n",
            '7: Merge litters with only one stillborn', "\n",
            '8: Migrate Litter data', "\n",
            '9: Update parent values in animal and litter tables', "\n",
            '10: Merge duplicate animals', "\n",
            '----------------------------------------------------', "\n",
            '20: Migrate WormResistance records', "\n",
            '21: Migrate Exterior records', "\n",
            '22: Migrate BirthWeight, TailLength, BirthProgress records', "\n",
            '----------------------------------------------------', "\n",
            'other: Exit VsmMigrator', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {

            case 1: $this->animalTableImporter->run($this->cmdUtil); break;

            case 2: $this->animalTableMigrator->run($this->cmdUtil); break;
            case 3: $this->tagReplaceMigrator->run($this->cmdUtil); break;
            case 4: $this->animalTableMigrator->mergeDuplicateAnimalsByVsmIdAndTagReplaces($this->cmdUtil); break;
            case 5: $this->animalTableMigrator->fix($this->cmdUtil); break;

            case 6: $this->duplicateLitterFixer->mergeDuplicateImportedLittersInSetOf2($this->cmdUtil); break;
            case 7: $this->duplicateLitterFixer->mergeDuplicateLittersWithOnlySingleStillborn($this->cmdUtil); break;
            case 8: $this->litterMigrator->run($this->cmdUtil); break;
            case 9: $this->litterMigrator->update($this->cmdUtil); break;
            case 10: $this->duplicateAnimalsFixer->fixMultipleDuplicateAnimalsAfterMigration($this->cmdUtil); break;

            case 20: $this->wormResistanceMigrator->run($this->cmdUtil); break;
            case 21: $this->exteriorMigrator->run($this->cmdUtil); break;
            case 22: $this->birthDataMigrator->run($this->cmdUtil); break;
            default: return;
        }
        $this->run($this->cmdUtil);
    }


}