<?php

namespace AppBundle\Command;

use AppBundle\Component\Utils;
use AppBundle\Entity\Company;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use AppBundle\Util\CommandUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoGenerateApikeysCommand extends ContainerAwareCommand
{
    const TITLE = 'Generate ';
    const INPUT_PATH = '/path/to/file.txt';

    /** @var ObjectManager $em */
    private $em;
    
    protected function configure()
    {
        $this
            ->setName('nsfo:generate:apikeys')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;
        $helper = $this->getHelper('question');
        $cmdUtil = new CommandUtil($input, $output, $helper);

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));

        $companies = $em->getRepository(Company::class)->findAll();
        $locations = $em->getRepository(Location::class)->findAll();
        $persons = $em->getRepository(Person::class)->findAll();

        $totalCount = count($companies) + count($locations) + count($persons);
        
        $cmdUtil->setStartTimeAndPrintIt($totalCount, 0);
        $cmdUtil->setProgressBarMessage('Generating Company ids');

        /** @var Company $company */
        foreach ($companies as $company) {
            /*
             * @var Company
             */
            if ($company->getCompanyId() == null || $company->getCompanyId() == '') {
                $company->setCompanyId(Utils::generateTokenCode());
                $em->persist($company);
                $em->flush();
            }
            $cmdUtil->advanceProgressBar(1);
        }

        $cmdUtil->setProgressBarMessage('Generating Location ids');

        

        foreach ($locations as $location) {
            /**
             * @var Location $location
             */
            if ($location->getLocationId() == null || $location->getLocationId() == '') {
                $location->setLocationId(Utils::generateTokenCode());
                $em->persist($location);
                $em->flush();
            }
            $cmdUtil->advanceProgressBar(1);
        }


        $cmdUtil->setProgressBarMessage('Generating Person ids');

        

        foreach ($persons as $person) {
            /** @var Person $person */
            if($person->getPersonId() == null || $person->getPersonId() == "") {
                $person->setPersonId(Utils::generatePersonId());
                $em->persist($person);
                $em->flush();
            }
            $cmdUtil->advanceProgressBar(1);
        }

        $cmdUtil->setProgressBarMessage('*finished*');

        $cmdUtil->setEndTimeAndPrintFinalOverview();
    }
}
