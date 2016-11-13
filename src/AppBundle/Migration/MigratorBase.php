<?php


namespace AppBundle\Migration;


use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Util\CommandUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\OutputInterface;

class MigratorBase
{
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

    /**
     * MigratorBase constructor.
     * @param CommandUtil $cmdUtil
     * @param ObjectManager $em
     * @param OutputInterface $outputInterface
     * @param array $data
     */
    public function __construct(CommandUtil $cmdUtil, ObjectManager $em, OutputInterface $outputInterface, array $data = [])
    {
        $this->cmdUtil = $cmdUtil;
        $this->em = $em;
        $this->output = $outputInterface;
        $this->data = $data;
        /** @var AnimalRepository animalRepository */
        $this->animalRepository = $em->getRepository(Animal::class);
    }
}