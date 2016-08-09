<?php

namespace AppBundle\Command;

use AppBundle\Component\Utils;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Util\CommandUtil;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoDumpClientsCommand extends ContainerAwareCommand
{
    const TITLE = 'Generate NSFO MiXBLUP files';
    const FILE_NAME = 'clients.txt';
    const FILE_NAME_WO_COMPANIES = 'clients_without_companies.txt';
    const FILE_NAME_WO_UBNS = 'clients_without_ubns.txt';
    const DEFAULT_OUTPUT_FOLDER_PATH = '/home/data/JVT/projects/NSFO';

    protected function configure()
    {
        $this
            ->setName('nsfo:dump:clients')
            ->setDescription('...')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $helper = $this->getHelper('question');
        $cmdUtil = new CommandUtil($input, $output, $helper);

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));

        //Output folder input
        $outputFolderPath = $cmdUtil->generateQuestion('Please enter output folder path', self::DEFAULT_OUTPUT_FOLDER_PATH);


        $clients = $em->getRepository(Client::class)
            ->matching(Criteria::create());

        $clientsWithoutCompany = new ArrayCollection();
        $clientsWithoutUbn = new ArrayCollection();

        //Initialize headers
        $this->formatColumnHeaders($outputFolderPath, self::FILE_NAME_WO_COMPANIES);
        $this->formatColumnHeaders($outputFolderPath, self::FILE_NAME_WO_UBNS);

        /** @var Client $client */
        foreach($clients as $client) {
            /** @var Collection $companies */
            $companies = $client->getCompanies();
            if($companies->count() <= 0) {
                $clientsWithoutCompany->add($client);

                $row = $this->formatClientRowData($client);
                file_put_contents($outputFolderPath.'/'.self::FILE_NAME_WO_COMPANIES, $row."\n", FILE_APPEND);
            } else {
                $ubnCount = 0;

                /** @var Company $company */
                foreach ($companies as $company) {
                    $ubnCount += $company->getLocations()->count();
                }

                if($ubnCount <= 0) {
                    $clientsWithoutUbn->add($client);

                    $row = $this->formatClientRowData($client);
                    file_put_contents($outputFolderPath.'/'.self::FILE_NAME_WO_UBNS, $row."\n", FILE_APPEND);
                }
            }
        }

        $output->writeln('Clients without a company: '.$clientsWithoutCompany->count());
        $output->writeln('Clients with a company but without an ubn: '.$clientsWithoutUbn->count());

        $output->writeln('=== FINISHED ===');

    }


    /**
     * @param Client $client
     * @return string
     */
    private function formatClientRowData(Client $client){

        $row = '|'.
            Utils::addPaddingToStringForColumnFormatCenter($client->getAccessToken(), 46).'|'.
            Utils::addPaddingToStringForColumnFormatCenter($client->getId(), 8).'|'.
            Utils::addPaddingToStringForColumnFormatCenter($client->getRelationNumberKeeper(),14).'|'.
            Utils::addPaddingToStringForColumnFormatCenter($client->getEmailAddress(), 30).'|'.
            Utils::addPaddingToStringForColumnFormatCenter($client->getFirstName(), 16).'|'.
            Utils::addPaddingToStringForColumnFormatCenter($client->getLastName(), 30).'|'

        ;

        return $row;
    }


    /**
     * @param $outputFolderPath
     * @param $filename
     */
    private function formatColumnHeaders($outputFolderPath, $filename){

        $row = '|'.
            Utils::addPaddingToStringForColumnFormatCenter('access token', 46).'|'.
            Utils::addPaddingToStringForColumnFormatCenter('id', 8).'|'.
            Utils::addPaddingToStringForColumnFormatCenter('rel.nr',14).'|'.
            Utils::addPaddingToStringForColumnFormatCenter('email address', 30).'|'.
            Utils::addPaddingToStringForColumnFormatCenter('first name', 16).'|'.
            Utils::addPaddingToStringForColumnFormatCenter('last name', 30).'|'
        ;

        file_put_contents($outputFolderPath.'/'. $filename, $row."\n", FILE_APPEND);
    }

}
