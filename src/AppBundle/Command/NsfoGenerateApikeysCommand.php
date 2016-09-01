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

        $cmdUtil->setStartTimeAndPrintIt();

        $companies = $em->getRepository(Company::class)->findAll();

        $output->writeln('Generating Company ids');

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
        }

        $output->writeln('Generating Location ids');

        $locations = $em->getRepository(Location::class)->findAll();

        foreach ($locations as $location) {
            /**
             * @var Location $location
             */
            if ($location->getLocationId() == null || $location->getLocationId() == '') {
                $location->setLocationId(Utils::generateTokenCode());
                $em->persist($location);
                $em->flush();
            }
        }


        $output->writeln('Generating Person ids');

        $persons = $em->getRepository(Person::class)->findAll();

        foreach ($persons as $person) {
            /** @var Person $person */
            if($person->getPersonId() == null || $person->getPersonId() == "") {
                $person->setPersonId(Utils::generatePersonId());
                $em->persist($person);
                $em->flush();
            }
        }


        $output->writeln([
            '=== DONE ===',
            '',
            '']);

        $cmdUtil->setEndTimeAndPrintFinalOverview();
    }
}
