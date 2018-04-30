<?php


namespace AppBundle\Service\Invoice;


use AppBundle\Entity\InvoiceRule;
use AppBundle\Entity\LedgerCategory;
use AppBundle\Service\Migration\MigratorServiceBase;
use Doctrine\ORM\EntityManagerInterface;

class BatchInvoiceRuleInitializer extends MigratorServiceBase
{

    /** @var  InvoiceRule */
    static private $baseCostAnimalAdministration;

    /** @var  InvoiceRule */
    static private $animalAdministrationOnlineEwe;

    /** @var  InvoiceRule */
    static private $animalAdministrationOfflineEwe;

    /** @var  InvoiceRule */
    static private $subscriptionNSFOOnline;

    /** @var  InvoiceRule */
    static private $subscriptionNSFOAnimalHealth;

    /**
     * @param EntityManagerInterface $em
     * @param string $rootDir
     */
    public function __construct(EntityManagerInterface $em, $rootDir)
    {
        parent::__construct($em, self::BATCH_SIZE, self::IMPORT_SUB_FOLDER, $rootDir);
    }

    /**
     * Load data fixtures of real Clients
     *
     */
    public function load() {
        /** @var LedgerCategory $baseAdministrationCategory */
        $baseAdministrationCategory = $this->em->getRepository(LedgerCategory::class)->findOneBy(array("code" => 8002));
        /** @var LedgerCategory $administrationCostsCategory */
        $administrationCostsCategory = $this->em->getRepository(LedgerCategory::class)->findOneBy(array("code" => 4756));
        /** @var LedgerCategory $NSFOSubscriptionsCategory */
        $NSFOSubscriptionsCategory = $this->em->getRepository(LedgerCategory::class)->findOneBy(array("code" => 8910));
        /** @var LedgerCategory $scrapieCategory */
        $scrapieCategory = $this->em->getRepository(LedgerCategory::class)->findOneBy(array("code" => 7850));
        /** @var LedgerCategory $manualDepositsCategory */
        $manualDepositsCategory = $this->em->getRepository(LedgerCategory::class)->findOneBy(array("code" => 8070));

        /**
         * Instantiate fixtures
         */
        self::$baseCostAnimalAdministration = new InvoiceRule();
        self::$animalAdministrationOnlineEwe = new InvoiceRule();
        self::$animalAdministrationOfflineEwe = new InvoiceRule();
        self::$subscriptionNSFOOnline = new InvoiceRule();
        self::$subscriptionNSFOAnimalHealth = new InvoiceRule();

        /**
         * Set is Batch true
         */
        self::$baseCostAnimalAdministration->setIsBatch(true);
        self::$animalAdministrationOnlineEwe->setIsBatch(true);
        self::$subscriptionNSFOOnline->setIsBatch(true);
        self::$subscriptionNSFOAnimalHealth->setIsBatch(true);
        self::$animalAdministrationOfflineEwe->setIsBatch(true);

        /**
         * Set the ledger categories
         */
        self::$baseCostAnimalAdministration->setLedgerCategory($baseAdministrationCategory);
        self::$animalAdministrationOnlineEwe->setLedgerCategory($administrationCostsCategory);
        self::$animalAdministrationOfflineEwe->setLedgerCategory($administrationCostsCategory);
        self::$subscriptionNSFOOnline->setLedgerCategory($NSFOSubscriptionsCategory);
        self::$subscriptionNSFOAnimalHealth->setLedgerCategory($NSFOSubscriptionsCategory);

        /**
         * Set the vat percentage rates
         */
        self::$baseCostAnimalAdministration->setVatPercentageRate(0);
        self::$animalAdministrationOnlineEwe->setVatPercentageRate(0);
        self::$subscriptionNSFOOnline->setVatPercentageRate(0);
        self::$subscriptionNSFOAnimalHealth->setVatPercentageRate(0);
        self::$animalAdministrationOfflineEwe->setVatPercentageRate(0);

        /**
         * Set the description
         */
        self::$baseCostAnimalAdministration->setDescription("Basiskosten Dieradministratie");
        self::$animalAdministrationOnlineEwe->setDescription("Dieradm.per ooi onl");
        self::$animalAdministrationOfflineEwe->setDescription("Dieradm.per ooi offl");
        self::$subscriptionNSFOOnline->setDescription("Lidmaatschap NSFO online");
        self::$subscriptionNSFOAnimalHealth->setDescription("Lidmaatschap NSFO Diergezondheid");

        /**
         * Set price excl vat
         */
        self::$baseCostAnimalAdministration->setPriceExclVat(50);
        self::$animalAdministrationOnlineEwe->setPriceExclVat(50);
        self::$animalAdministrationOfflineEwe->setPriceExclVat(50);
        self::$subscriptionNSFOOnline->setPriceExclVat(50);
        self::$subscriptionNSFOAnimalHealth->setPriceExclVat(50);

        /**
         * Set the type
         */
        self::$baseCostAnimalAdministration->setType("BaseAnimalAdministration");
        self::$animalAdministrationOnlineEwe->setType("AdministrationOnlineEwe");
        self::$animalAdministrationOfflineEwe->setType("AdministrationOfflineEwe");
        self::$subscriptionNSFOOnline->setType("SubscriptionNSFOOnline");
        self::$subscriptionNSFOAnimalHealth->setType("SubscriptionNSFOAnimalHealth");

        /**
         * Set sort order
         */
        self::$baseCostAnimalAdministration->setSortOrder(1);
        self::$animalAdministrationOnlineEwe->setSortOrder(1);
        self::$animalAdministrationOfflineEwe->setSortOrder(1);
        self::$subscriptionNSFOOnline->setSortOrder(1);
        self::$subscriptionNSFOAnimalHealth->setSortOrder(1);
        
        $this->em->persist(self::$baseCostAnimalAdministration);
        $this->em->persist(self::$animalAdministrationOnlineEwe);
        $this->em->persist(self::$animalAdministrationOfflineEwe);
        $this->em->persist(self::$subscriptionNSFOOnline);
        $this->em->persist(self::$subscriptionNSFOAnimalHealth);
        $this->em->flush();
    }
}
