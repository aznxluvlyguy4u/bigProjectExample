<?php

namespace AppBundle\Command;

use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\BreedValueCoefficient;
use AppBundle\Entity\BreedValueCoefficientRepository;
use AppBundle\Enumerator\BreedTrait;
use AppBundle\Util\CommandUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoBreedvaluesSetvaluesCommand extends ContainerAwareCommand
{
    const TITLE = 'Set Breed Values';
    const DEFAULT_OPTION = 0;

    /** @var CommandUtil */
    private $cmdUtil;

    /** @var OutputInterface */
    private $output;

    /** @var ObjectManager */
    private $em;

    /** @var AnimalRepository $animalRepository */
    private $animalRepository;

    /** @var BreedValueCoefficientRepository $breedValueCoefficientRepository */
    private $breedValueCoefficientRepository;
    
    protected function configure()
    {
        $this
            ->setName('nsfo:breedvalues:setvalues')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $em */
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $helper = $this->getHelper('question');
        $this->cmdUtil = new CommandUtil($input, $output, $helper);
        $this->output = $output;
        $this->animalRepository = $this->em->getRepository(Animal::class);
        $this->breedValueCoefficientRepository = $this->em->getRepository(BreedValueCoefficient::class);


        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '1: Generate LambMeatIndexCoefficients (vleeslamindexcoëfficienten) from values set in Constant\BreedTraitCoefficient', "\n",
            'abort (other)', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {
            case 1:
                $this->breedValueCoefficientRepository->generateLambMeatIndexCoefficients();
                $output->writeln('Done!');
                break;

            default:
                $output->writeln('ABORTED');
                break;
        }
    }

}
