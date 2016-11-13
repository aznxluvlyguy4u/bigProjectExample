<?php


namespace AppBundle\Migration;


use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\NullChecker;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\OutputInterface;

class MigratorBase
{
    const MIGRATION_OUTPUT_FOLDER = '/Resources/outputs/migration';

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

    /** @var string */
    protected $outputFolder;

    /** @var string */
    protected $rootDir;

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
        $this->output = $outputInterface;
        $this->data = $data;
        /** @var AnimalRepository animalRepository */
        $this->animalRepository = $em->getRepository(Animal::class);
        
        $this->rootDir = null;
        if(is_string($rootDir)) {
            $this->rootDir = $rootDir;
            $this->outputFolder = $rootDir.self::MIGRATION_OUTPUT_FOLDER;
            NullChecker::createFolderPathIfNull($this->outputFolder);
        }
    }
}