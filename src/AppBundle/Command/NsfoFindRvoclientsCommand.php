<?php

namespace AppBundle\Command;

use AppBundle\Entity\Address;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\CompanyAddress;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationAddress;
use AppBundle\Util\CommandUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoFindRvoclientsCommand extends ContainerAwareCommand
{
    const TITLE = 'Fix machtigingenRVO.csv file';
    const INPUT_PATH = '/home/data/JVT/projects/NSFO/Migratie/fixed_machtigingenRVO_(edited_v1).csv';
    const FOUND_OUTPUT_PATH = '/home/data/JVT/projects/NSFO/testing/rvoClientsFound.csv';
    const MISSING_OUTPUT_PATH = '/home/data/JVT/projects/NSFO/testing/rvoClientsMissing.csv';
    const UPDATED_OUTPUT_PATH = '/home/data/JVT/projects/NSFO/testing/rvoClientsUpdated.csv';

    /** @var int $relationNumberFound */
    private $relationNumberFound;

    /** @var int $ubnFound */
    private $ubnFound;

    /** @var int $companyWithoutUbnFound */
    private $companyWithoutUbnFound;

    /** @var int $nameFound */
    private $nameFound;

    /** @var int $skipped */
    private $skipped;

    /** @var int $relationNumberKeepersUpdated */
    private $relationNumberKeepersUpdated;

    /** @var int $totalFound */
    private $totalFoundInDatabase;

    /** @var int $totalFound */
    private $totalFoundInDatabaseWithUbn;

    /** @var int $totalFound */
    private $totalFoundInDatabaseWithoutUbn;

    /** @var int $notFoundInDatabase */
    private $notFoundInDatabase;

    /** @var ArrayCollection $foundNames */
    private $foundNames;

    /** @var ArrayCollection $clients */
    private $clients;

    /** @var ArrayCollection $foundOutputs */
    private $foundOutputs;

    /** @var  ArrayCollection $missingOutputs */
    private $missingOutputs;

    /** @var ObjectManager $em */
    private $em;

    /** @var OutputInterface $output */
    private $output;

    protected function configure()
    {
        $this
            ->setName('nsfo:find:rvoclients')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $cmdUtil = new CommandUtil($input, $output, $helper);
        $this->output = $output;

        /* Initialize values */
        $this->relationNumberFound = 0;
        $this->ubnFound = 0;
        $this->companyWithoutUbnFound = 0;
        $this->nameFound = 0;
        $this->skipped = 0;
        $this->relationNumberKeepersUpdated = 0;
        $this->totalFoundInDatabase = 0;
        $this->totalFoundInDatabaseWithoutUbn = 0;
        $this->totalFoundInDatabaseWithUbn = 0;
        $this->notFoundInDatabase = 0;

        $this->foundNames = new ArrayCollection();

        $this->foundOutputs = new ArrayCollection();
        $this->missingOutputs = new ArrayCollection();


        
        //Print intro
        $output->writeln(CommandUtil::generateTitle('TESTING'));

        /** @var ObjectManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;

        $fileContents = file_get_contents(self::INPUT_PATH);

        $data = explode(PHP_EOL, $fileContents);

        //parse strings
        foreach ($data as $inputRow) {
            $results = $this->readRow($inputRow);
        }

        //output data
        $headerRow = 'searchType,result,relationNumberKeeperRVO,relationNumberKeeperDB,ubn,ubns,fullName,companyName,streetName,addressNumber,addressNumberSuffix,postalCode,city,adres';
        file_put_contents(self::FOUND_OUTPUT_PATH, $headerRow."\n", FILE_APPEND);
        file_put_contents(self::MISSING_OUTPUT_PATH, $headerRow."\n", FILE_APPEND);

        foreach ($this->foundOutputs as $outputRow) {
//            $output->writeln($outputRow);
            file_put_contents(self::FOUND_OUTPUT_PATH, $outputRow."\n", FILE_APPEND);
        }

        foreach ($this->missingOutputs as $outputRow) {
//            $output->writeln($outputRow);
            file_put_contents(self::MISSING_OUTPUT_PATH, $outputRow."\n", FILE_APPEND);
        }

        $output->writeln([
            '=== PROCESS FINISHED ===',
            'relationNumberKeeper count: '.$this->relationNumberFound,
            'ubn count: '.$this->ubnFound,
            'company without ubn count: '.$this->companyWithoutUbnFound,
            'name count: '.$this->nameFound,
            'skipped count: '.$this->skipped,
            '--------------',
            'TOTAL FOUND count: '.$this->totalFoundInDatabase,
            'TOTAL FOUND with UBN count: '.$this->totalFoundInDatabaseWithUbn,
            'TOTAL FOUND without UBN count: '.$this->totalFoundInDatabaseWithoutUbn,
            'TOTAL MISSING count: '.$this->notFoundInDatabase,
            '--------------',
            'RelationNumberKeepers updated: '.$this->relationNumberKeepersUpdated,
            '']);
    }

    /**
     * @param string $row
     * @return ArrayCollection
     */
    protected function readRow($row)
    {
        $items = explode(',', $row);

        $relationNumberKeeper = $items[0];

        if($relationNumberKeeper == 'relationNumberKeeper') { //Skip the header row
            return $this->foundOutputs;
        }

        if(sizeof($items) < 13) {
            return $this->foundOutputs; //Skip the blank end row
        }

        $companyName   = $items[1];
        $fullName      = $items[2];
        $streetName    = $items[3];
        $addressNumber = intval($items[4]);
        $addressNumberSuffix = $items[5];
        if($addressNumberSuffix == '') { $addressNumberSuffix = null; }

        $postalCode    = str_replace(' ','' , $items[6]);
        $city          = $items[7];
        $soortMachtiging = $items[8];
        $startDate     = $items[9];
        $endDate       = $items[10];
        $agreementCode = $items[11];
        $agreementDescription = $items[12];
        $isRemoveFromOutput = $items[13];

        $fullAddress = $streetName.','.$addressNumber.','.$addressNumberSuffix.','.$postalCode.','.$city;
        $streetNameAndNumber = $streetName.' '.$addressNumber.' '.$addressNumberSuffix;

        $isSearchForRelationNumberKeeper = false;
        $isSearchForAddress = false;
        $isSearchForName = false;
        $results = array();
        $ubn = '';
        $ubns = '';
        $relNrInDb = '';


        if($this->foundNames->contains($fullName)) {
            return $this->foundOutputs; //Skip the clients already searched for
        } else {
            $this->foundNames->add($fullName);
        }

        if($isRemoveFromOutput) {
            $outputString = 'SKIPPED,SKIPPED,'.$relationNumberKeeper.','.$relNrInDb.','.$ubn.','.$ubns.','.$fullName.','.$companyName.','.$fullAddress.','.$streetNameAndNumber;
            $this->foundOutputs->add($outputString);
            $this->skipped++;
            return $this->foundOutputs;
        }


        if($relationNumberKeeper != null && $relationNumberKeeper != '') {
            $isSearchForRelationNumberKeeper = true;
        } else {
            $isSearchForAddress = true;
        }


        /* RELATION NUMBER KEEPER */
        if($isSearchForRelationNumberKeeper) {
            $criteria = Criteria::create()
                ->where(Criteria::expr()->eq('relationNumberKeeper', $relationNumberKeeper))
            ;

            $results = $this->em->getRepository(Client::class)
                ->matching($criteria);

            /** @var Client $client */
            foreach ($results as $client) {
                $ubn = self::getClientUBN($client);
                $ubns = self::getClientUBNsAsString($client);
                $relNrInDb = $client->getRelationNumberKeeper();
                $outputString = 'RELNR,FOUND,'.$relationNumberKeeper.','.$relNrInDb.','.$ubn.','.$ubns.','.$fullName.','.$companyName.','.$fullAddress.','.$streetNameAndNumber;
                $this->foundOutputs->add($outputString);
                $this->relationNumberFound++;
                $this->totalFoundInDatabase++;

                $this->countFoundUsersUbns($ubn);

                // UPDATE DATABASE DATA
                $isNewRelationNumberKeeperPersisted = $this->setNewRelationNumberKeeperIfMissing($client, $relationNumberKeeper);
            }

            if (sizeof($results) == 0) {
                $isSearchForAddress = true;
            }
        }

        /* ADDRESS */
        if($isSearchForAddress) {
            $criteria = Criteria::create()
                ->where(Criteria::expr()->eq('streetName', $streetName))
                ->andWhere(Criteria::expr()->eq('addressNumber', $addressNumber))
                ->andWhere(Criteria::expr()->eq('addressNumberSuffix', $addressNumberSuffix))
                ->andWhere(Criteria::expr()->eq('postalCode', $postalCode))
                ->andWhere(Criteria::expr()->eq('city', $city))
            ;

            //FIRST SEARCH UBN LOCATIONS
            $results = $this->em->getRepository(LocationAddress::class)
                ->matching($criteria);

            /** @var LocationAddress $locationAddress */
            foreach ($results as $locationAddress) {
                $addressId = $locationAddress->getId();

                /** @var Location $location */
                $location = $this->em->getRepository(Location::class)->findOneBy(['address' => $addressId]);
                $ubn = $location->getUbn();
                $client = $location->getCompany()->getOwner();
                $relNrInDb = $client->getRelationNumberKeeper();

                $outputString = 'UBN,FOUND,'.$relationNumberKeeper.','.$relNrInDb.','.$ubn.','.$ubns.','.$fullName.','.$companyName.','.$fullAddress.','.$streetNameAndNumber;
                $this->foundOutputs->add($outputString);
                $this->ubnFound++;
                $this->totalFoundInDatabase++;
                $this->countFoundUsersUbns($ubn);

                // UPDATE DATABASE DATA
                $isNewRelationNumberKeeperPersisted = $this->setNewRelationNumberKeeperIfMissing($client, $relationNumberKeeper);
            }

            //THEN SEARCH FOR COMPANY LOCATIONS WITHOUT UBN
            if (sizeof($results) == 0) {
                $results = $this->em->getRepository(CompanyAddress::class)
                    ->matching($criteria);

                /** @var CompanyAddress $companyAddress */
                foreach ($results as $companyAddress) {

                    /** @var Company $company */
                    $company = $this->em->getRepository(Company::class)->findOneByAddress($companyAddress);
                    $client = $company->getOwner();
                    $relNrInDb = $client->getRelationNumberKeeper();
                    $outputString = 'COMPANY,FOUND,'.$relationNumberKeeper.','.$relNrInDb.','.$ubn.','.$ubns.','.$fullName.','.$companyName.','.$fullAddress.','.$streetNameAndNumber;
                    $this->foundOutputs->add($outputString);
                    $this->totalFoundInDatabase++;
                    $this->companyWithoutUbnFound++;
                    $this->countFoundUsersUbns($ubn);

                    // UPDATE DATABASE DATA
                    $isNewRelationNumberKeeperPersisted = $this->setNewRelationNumberKeeperIfMissing($client, $relationNumberKeeper);
                }
            }


            if (sizeof($results) == 0) {
                $isSearchForName = true;
            }
        }

        /* NAME */
        if($isSearchForName) {
            /** @var Client $client */
            $client = $this->searchForNames($fullName);
            if($client != null) {
                $results[] = $client;
            }

            /** @var Client $client */
            foreach ($results as $client) {
                $ubn = self::getClientUBN($client);
                $ubns = self::getClientUBNsAsString($client);
                $relNrInDb = $client->getRelationNumberKeeper();
                $outputString = 'NAME,FOUND,'.$relationNumberKeeper.','.$relNrInDb.','.$ubn.','.$ubns.','.$fullName.','.$companyName.','.$fullAddress.','.$streetNameAndNumber;
                $this->foundOutputs->add($outputString);
                $this->nameFound++;
                $this->totalFoundInDatabase++;

                $this->countFoundUsersUbns($ubn);

                // UPDATE DATABASE DATA
                $isNewRelationNumberKeeperPersisted = $this->setNewRelationNumberKeeperIfMissing($client, $relationNumberKeeper);
            }
        }


        if(sizeof($results) == 0) {
            $outputString = 'ALL,MISSING,'.$relationNumberKeeper.','.$relNrInDb.','.$ubn.','.$ubns.','.$fullName.','.$companyName.','.$fullAddress.','.$streetNameAndNumber;
            $this->missingOutputs->add($outputString);
            $this->notFoundInDatabase++;
        }

        return $this->foundOutputs;
    }

    /**
     * @param string $fullName
     * @return Client
     */
    public function searchForNames($fullName)
    {
        if($this->clients == null) {
            $this->clients = $this->em->getRepository(Client::class)->findAll();
        }

        $clients = $this->clients;
        /** @var Client $client */
        foreach ($clients as $client) {
            if($client->getFullName() == $fullName) {
                return $client;
            }

            if($client->getFirstName() == $fullName) {
                return $client;
            }

            if($client->getLastName() == $fullName) {
                return $client;
            }

            if($client->getFirstName().','.$client->getLastName() == $fullName) {
                return $client;
            }

            if($client->getFirstName().', '.$client->getLastName() == $fullName) {
                return $client;
            }
        }
        return null;
    }


    /**
     * @param Client $client
     * @return string
     */
    public static function getClientUBN(Client $client)
    {
        $companies = $client->getCompanies();
        if($companies->count() > 0) {
            foreach($companies as $company) {
                /** @var ArrayCollection $locations */
                $locations = $company->getLocations();
                if($locations->count() > 0) {
                    return $locations->first()->getUbn();
                }
            }
        }
        return null;
    }

    /**
     * @param Client $client
     * @return string
     */
    public static function getClientUBNsAsString(Client $client)
    {
        $ubns = '';
        $count = 0;

        $companies = $client->getCompanies();
        if($companies->count() > 0) {
            foreach($companies as $company) {
                /** @var ArrayCollection $locations */
                $locations = $company->getLocations();
                if($locations->count() > 0) {
                    foreach ($locations as $location) {
                        $ubns = $ubns.$location->getUbn().' - ';
                        $count++;
                    }
                }
            }
        }
        if($count > 1) {
            return $ubns;
        } else {
            return '';
        }
    }

    private function countFoundUsersUbns($ubn)
    {
        if($ubn != null || $ubn != '') {
            $this->totalFoundInDatabaseWithUbn++;
        } else {
            $this->totalFoundInDatabaseWithoutUbn++;
        }
    }


    /**
     * @param Client $client
     * @param $relationNumberKeeper
     * @return bool|string
     */
    private function setNewRelationNumberKeeperIfMissing(Client $client, $relationNumberKeeper)
    {
        $oldRelationNumberKeeper = $client->getRelationNumberKeeper();

        if($relationNumberKeeper == "" || $relationNumberKeeper == null) {
            //do nothing
            return false;

        } else if($oldRelationNumberKeeper == "" || $oldRelationNumberKeeper == null) {
            if($this->relationNumberKeepersUpdated == 0) { $this->writeUpdatedClientsHeader(); }

            $client->setRelationNumberKeeper($relationNumberKeeper);
            $this->em->persist($client);
            $this->em->flush();
            $this->relationNumberKeepersUpdated++;
            $this->writeUpdatedClientsRow($client);

            return true;
        } else if($relationNumberKeeper != $oldRelationNumberKeeper) {
            if($this->relationNumberKeepersUpdated == 0) { $this->writeUpdatedClientsHeader(); }

            $client->setRelationNumberKeeper($relationNumberKeeper);
            $this->em->persist($client);
            $this->em->flush();
            $this->relationNumberKeepersUpdated++;
            $this->writeUpdatedClientsRow($client);

            return true;
        } else {
            //If number is identical, do nothing
            return false;
        }
    }

    /**
     * @param Client $client
     * @return string
     */
    private function writeUpdatedClientsRow(Client $client)
    {
        $id = $client->getId();
        $number = $client->getRelationNumberKeeper();
        $firstName = $client->getFirstName();
        $lastName = $client->getLastName();
        $email = $client->getEmailAddress();

        $outputRow = $id.','.$number.','.$firstName.','.$lastName.','.$email;
        file_put_contents(self::UPDATED_OUTPUT_PATH, $outputRow."\n", FILE_APPEND);

        return $outputRow;
    }

    /**
     * @return string
     */
    private function writeUpdatedClientsHeader()
    {
        $outputRow = 'id,relationNumberKeeper,firstName,lastName,emailAddress';
        file_put_contents(self::UPDATED_OUTPUT_PATH, $outputRow."\n", FILE_APPEND);

        return $outputRow;
    }
}
