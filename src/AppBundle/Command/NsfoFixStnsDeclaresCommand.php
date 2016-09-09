<?php

namespace AppBundle\Command;

use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareArrivalRepository;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareDepartRepository;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareExportRepository;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoFixStnsDeclaresCommand extends ContainerAwareCommand
{
    const TITLE = 'FIX STNs IN DECLARES (STN)';

    /** @var ObjectManager */
    private $em;

    /** @var DeclareArrivalRepository */
    private $arrivalRepository;

    /** @var DeclareDepartRepository */
    private $departRepository;

    /** @var DeclareExportRepository */
    private $exportRepository;
    
    /** @var OutputInterface */
    private $output;
    
    /** @var CommandUtil */
    private $cmdUtil;

    protected function configure()
    {
        $this
            ->setName('nsfo:fix:stns:declares')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;
        $this->output = $output;
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $this->arrivalRepository = $this->em->getRepository(DeclareArrival::class);
        $this->departRepository = $this->em->getRepository(DeclareDepart::class);
        $this->exportRepository = $this->em->getRepository(DeclareExport::class);
        
        $helper = $this->getHelper('question');
        $this->cmdUtil = new CommandUtil($input, $output, $helper);

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));

        $arrivals = $this->arrivalRepository->findAll();
        $departs = $this->departRepository->findAll();
        $exports = $this->exportRepository->findAll();

        $totalDeclares = count($arrivals) + count($departs) + count($exports);

        $this->cmdUtil->setStartTimeAndPrintIt($totalDeclares, 1, 'Fixing PedigreeCodes in arrival, depart and export...');
        
        $this->fixPedigreeCodesInDeclares($arrivals);
        $this->fixPedigreeCodesInDeclares($departs);
        $this->fixPedigreeCodesInDeclares($exports);
        
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }
    
    
    private function fixPedigreeCodesInDeclares($declares)
    {
        /** @var DeclareArrival|DeclareDepart|DeclareExport $declare */
        foreach ($declares as $declare) {
            $animal = $declare->getAnimal();
            if($animal != null) {
                $pedigreeNumber = $animal->getPedigreeNumber();
                
                if(self::isValidPedigreeNumber($pedigreeNumber)) {
                    $this->setPedigreeCode($declare, $animal);
                    
                } else {
                    $this->clearPedigreeCode($declare);
                }
            } else {
                $this->clearPedigreeCode($declare);
            }
            $this->em->persist($declare);
            $this->cmdUtil->advanceProgressBar(1);
        }
    }


    /**
     * @param string $pedigreeNumber
     * @return bool
     */
    public static function isValidPedigreeNumber($pedigreeNumber)
    {
        if($pedigreeNumber != null) {
            return strpos($pedigreeNumber, '-') == 5 && strlen($pedigreeNumber) == 11;
        } else {
            return false;
        }
    }
    
    
    /**
     * @param DeclareDepart|DeclareArrival|DeclareExport $declare
     */
    private function clearPedigreeCode($declare)
    {
        $this->setPedigreeCode($declare, null);
    }


    /**
     * @param DeclareDepart|DeclareArrival|DeclareExport $declare
     * @param Animal $animal
     */
    private function setPedigreeCode($declare, $animal)
    {
        $id = $declare->getId();

        $tableFound = true;
        if($declare instanceof DeclareArrival) {
            $table = 'declare_arrival';
        } elseif ($declare instanceof DeclareDepart) {
            $table = 'declare_depart';
        } elseif ($declare instanceof DeclareExport) {
            $table = 'declare_export';
        } else {
            $table = '';
            $tableFound = false;
        }

        if($tableFound) {
            if($animal instanceof Animal) {
                $pedigreeCountryCode = $animal->getPedigreeCountryCode();
                $pedigreeNumber = $animal->getPedigreeNumber();
            } else {
                $pedigreeCountryCode = null;
                $pedigreeNumber = null;
            }

            $sql = "UPDATE ".$table." SET pedigree_country_code = '". $pedigreeCountryCode ."', pedigree_number = '". $pedigreeNumber ."' WHERE id = '". $id ."'";
            $this->em->getConnection()->exec($sql);
        }
    }
}
