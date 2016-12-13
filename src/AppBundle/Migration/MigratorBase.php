<?php


namespace AppBundle\Migration;


use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\DeclareTagReplace;
use AppBundle\Entity\DeclareTagReplaceRepository;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\NullChecker;
use AppBundle\Util\SqlUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Output\OutputInterface;

class MigratorBase
{
    const MIGRATION_OUTPUT_FOLDER = '/Resources/outputs/migration';
    const BLANK_DATE_FILLER = '1899-01-01';

    /** @var ObjectManager */
    protected $em;

    /** @var CommandUtil */
    protected $cmdUtil;

    /** @var OutputInterface */
    protected $output;

    /** @var array */
    protected $data;

    /** @var AnimalRepository */
    protected $animalRepository;

    /** @var DeclareTagReplaceRepository */
    protected $declareTagReplaceRepository;

    /** @var string */
    protected $outputFolder;

    /** @var string */
    protected $rootDir;

    /** @var Connection $conn */
    protected $conn;

    /**
     * MigratorBase constructor.
     * @param CommandUtil $cmdUtil
     * @param ObjectManager $em
     * @param OutputInterface $outputInterface
     * @param array $data
     * @param string $rootDir
     */
    public function __construct(CommandUtil $cmdUtil, ObjectManager $em, OutputInterface $outputInterface, array $data = [], $rootDir = null)
    {
        $this->cmdUtil = $cmdUtil;
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->output = $outputInterface;
        $this->data = $data;
        /** @var AnimalRepository animalRepository */
        $this->animalRepository = $em->getRepository(Animal::class);
        $this->declareTagReplaceRepository = $em->getRepository(DeclareTagReplace::class);

        $this->rootDir = null;
        if(is_string($rootDir)) {
            $this->rootDir = $rootDir;
            $this->outputFolder = $rootDir.self::MIGRATION_OUTPUT_FOLDER;
            NullChecker::createFolderPathIfNull($this->outputFolder);
        }
    }


    /**
     * @return \DateTime
     */
    public static function getBlankDateFillerDateTime()
    {
        return new \DateTime(self::BLANK_DATE_FILLER);
    }


    /**
     * @return \DateTime
     */
    public static function getBlankDateFillerDateString()
    {
        return self::getBlankDateFillerDateTime()->format(SqlUtil::DATE_FORMAT);
    }
}