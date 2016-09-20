<?php

namespace AppBundle\Command;

use AppBundle\Component\Utils;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Util\CommandUtil;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoDumpClientsCommand extends ContainerAwareCommand
{
    const TITLE = 'Generate text files for Clients without a company and Clients without an ubn';
    const FILE_EXTENSION = 'csv';
    const FILE_NAME_WO_COMPANIES = 'clients_without_companies';
    const FILE_NAME_WO_UBNS = 'clients_without_ubns';
    const DEFAULT_OUTPUT_FOLDER_PATH = '/home/data/JVT/projects/NSFO';
    const SEPARATOR = ',';
    const SEPARATOR_START = '';
    const SEPARATOR_END = '';

    protected function configure()
    {
        $this
            ->setName('nsfo:dump:clients')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
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

        $fileNameWithoutCompanies = self::FILE_NAME_WO_COMPANIES.'.'.self::FILE_EXTENSION;
        $fileNameWithoutUbns = self::FILE_NAME_WO_UBNS.'.'.self::FILE_EXTENSION;

        //Initialize headers
        $this->formatColumnHeaders($outputFolderPath, $fileNameWithoutCompanies);
        $this->formatColumnHeaders($outputFolderPath, $fileNameWithoutUbns);

        /** @var Client $client */
        foreach($clients as $client) {
            /** @var Collection $companies */
            $companies = $client->getCompanies();
            if($companies->count() <= 0) {
                $clientsWithoutCompany->add($client);

                $row = $this->formatClientRowData($client);
                file_put_contents($outputFolderPath.'/'.$fileNameWithoutCompanies, $row."\n", FILE_APPEND);
            } else {
                $ubnCount = 0;

                /** @var Company $company */
                foreach ($companies as $company) {
                    $ubnCount += $company->getLocations()->count();
                }

                if($ubnCount <= 0) {
                    $clientsWithoutUbn->add($client);

                    $row = $this->formatClientRowData($client);
                    file_put_contents($outputFolderPath.'/'.$fileNameWithoutUbns, $row."\n", FILE_APPEND);
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
    private function formatClientRowDataWithPadding(Client $client){

        $row = self::SEPARATOR_START
            .Utils::addPaddingToStringForColumnFormatCenter($client->getAccessToken(), 46).self::SEPARATOR
            .Utils::addPaddingToStringForColumnFormatCenter($client->getId(), 8).self::SEPARATOR
            .Utils::addPaddingToStringForColumnFormatCenter($client->getRelationNumberKeeper(),14).self::SEPARATOR
            .Utils::addPaddingToStringForColumnFormatCenter($client->getEmailAddress(), 30).self::SEPARATOR
            .Utils::addPaddingToStringForColumnFormatCenter($client->getFirstName(), 16).self::SEPARATOR
            .Utils::addPaddingToStringForColumnFormatCenter($client->getLastName(), 30)
            .self::SEPARATOR_END
        ;

        return $row;
    }


    /**
     * @param $outputFolderPath
     * @param $filename
     */
    private function formatColumnHeadersWithPadding($outputFolderPath, $filename){

        $row = self::SEPARATOR_START
            .Utils::addPaddingToStringForColumnFormatCenter('access token', 46).self::SEPARATOR
            .Utils::addPaddingToStringForColumnFormatCenter('id', 8).self::SEPARATOR
            .Utils::addPaddingToStringForColumnFormatCenter('rel.nr',14).self::SEPARATOR
            .Utils::addPaddingToStringForColumnFormatCenter('email address', 30).self::SEPARATOR
            .Utils::addPaddingToStringForColumnFormatCenter('first name', 16).self::SEPARATOR
            .Utils::addPaddingToStringForColumnFormatCenter('last name', 30)
            .self::SEPARATOR_END
        ;

        file_put_contents($outputFolderPath.'/'. $filename, $row."\n", FILE_APPEND);
    }

    /**
     * @param Client $client
     * @return string
     */
    private function formatClientRowData(Client $client){

        $row = self::SEPARATOR_START
//            .$client->getId().self::SEPARATOR
            .$client->getRelationNumberKeeper().self::SEPARATOR
            .$client->getEmailAddress().self::SEPARATOR
            .$client->getFirstName().self::SEPARATOR
            .$client->getLastName().self::SEPARATOR
            .$client->getCellphoneNumber().self::SEPARATOR
            .self::SEPARATOR_END
        ;

        return $row;
    }


    /**
     * @param $outputFolderPath
     * @param $filename
     */
    private function formatColumnHeaders($outputFolderPath, $filename){

        $row = self::SEPARATOR_START
//            .'id'.self::SEPARATOR
            .'relatienummer'.self::SEPARATOR
            .'emailadres'.self::SEPARATOR
            .'voornaam'.self::SEPARATOR
            .'achternaam'.self::SEPARATOR
            .'telefoonnummer'
            .self::SEPARATOR_END
        ;

        file_put_contents($outputFolderPath.'/'. $filename, $row."\n", FILE_APPEND);
    }

}
