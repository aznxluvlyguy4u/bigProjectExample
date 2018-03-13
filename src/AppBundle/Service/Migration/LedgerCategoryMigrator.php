<?php


namespace AppBundle\Service\Migration;


use AppBundle\Entity\LedgerCategory;
use AppBundle\Entity\LedgerCategoryRepository;
use AppBundle\Enumerator\CommandTitle;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use Doctrine\Common\Persistence\ObjectManager;

class LedgerCategoryMigrator extends MigratorServiceBase implements IMigratorService
{
    const IMPORT_SUB_FOLDER = 'invoices/';
    const LEDGER_CATEGORY = 'finder_ledger_category';

    /** @var LedgerCategoryRepository */
    private $ledgerCategoryRepository;

    public function __construct(ObjectManager $em, $rootDir)
    {
        parent::__construct($em, self::BATCH_SIZE, self::IMPORT_SUB_FOLDER, $rootDir);
        $this->ledgerCategoryRepository = $this->em->getRepository(LedgerCategory::class);

        $this->filenames = array(
            self::LEDGER_CATEGORY => 'ledger_categories.csv',
        );

        $this->getCsvOptions()->setPipeSeparator();
    }

    public function run(CommandUtil $cmdUtil)
    {
        parent::run($cmdUtil);

        $this->writeLn(CommandTitle::LEDGER_CATEGORY);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '1: Initialize ledger categories', "\n",
            'other: EXIT ', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {
            case 1: $this->initializeLedgerCategories(); break;
            default: $this->writeln('EXIT'); return;
        }
        $this->run($cmdUtil);
    }

    public function initializeLedgerCategories()
    {
        $csv = $this->parseCSV(self::LEDGER_CATEGORY);
        $ledgerCategories = [];

        foreach($this->ledgerCategoryRepository->findAll() as $ledgerCategory) {
            $ledgerCategories[$ledgerCategory->getCode()] = $ledgerCategory;
        }

        $newCount = 0;
        foreach ($csv as $record) {
            $code = $record[0];
            $description = $record[1];

            /** @var LedgerCategory $ledgerCategory */
            $ledgerCategory = ArrayUtil::get($code, $ledgerCategories);

            if ($ledgerCategory === null) {
                $ledgerCategory = (new LedgerCategory())
                    ->setCode($code)
                    ->setDescription($description)
                    ->setCreationBy($this->getDeveloper())
                ;
                $this->em->persist($ledgerCategory);

                $newCount++;
            }
        }

        if ($newCount > 0) {
            $this->em->flush();
        }

        $result = $newCount == 0 ? 'No new ledger categories added' : $newCount.' new ledger categories added!' ;
        $this->writeln($result);

        return $newCount;
    }
}