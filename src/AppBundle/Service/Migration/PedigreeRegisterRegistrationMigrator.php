<?php


namespace AppBundle\Service\Migration;


use AppBundle\Criteria\LocationCriteria;
use AppBundle\Criteria\PedigreeRegisterCriteria;
use AppBundle\Criteria\PedigreeRegisterRegistrationCriteria;
use AppBundle\Entity\Location;
use AppBundle\Entity\PedigreeRegister;
use AppBundle\Entity\PedigreeRegisterRegistration;
use AppBundle\Enumerator\ImportFileName;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\CsvParser;
use AppBundle\Util\FilesystemUtil;
use AppBundle\Util\MigrationUtil;
use AppBundle\Util\StringUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;

class PedigreeRegisterRegistrationMigrator extends MigratorServiceBase implements IMigratorService
{
    /** @var ArrayCollection|PedigreeRegisterRegistration[] */
    private $registrations;
    /** @var ArrayCollection|PedigreeRegister[] */
    private $registers;
    /** @var ArrayCollection|Location[] */
    private $locations;

    /** @var array */
    private $missingLocations;
    /** @var array */
    private $locationsFoundWithoutMatchingOwner;

    /**
     * @param EntityManagerInterface|ObjectManager $em
     * @param string $rootDir
     */
    public function __construct(ObjectManager $em, $rootDir)
    {
        parent::__construct($em, self::BATCH_SIZE, self::IMPORT_SUB_FOLDER, $rootDir);
    }


    /**
     * @param CommandUtil $cmdUtil
     * @return bool
     */
    public function run(CommandUtil $cmdUtil)
    {
        parent::run($cmdUtil);

        if(!$this->parse()) { return false; }

        $this->initializeSearchArrays();

        if(!$this->validateInputFile()) { return false; }

        $this->cmdUtil->setStartTimeAndPrintIt(count($this->data)+1, 1);

        $this->writeLn('Only missing registrations will be added');

        $newCount = 0;
        $skippedCount = 0;
        $alreadyExistsCount = 0;
        $errors = 0;

        $newlyInsertedBreederNumbers = [];
        $duplicateBreederNumbersInFileOnSameUbn = [];
        $duplicateBreederNumbersInFileOnDifferentUbn = [];

        foreach ($this->data as $record) {

            $breederNumber = $record[0];
            $companyName = $record[1];
            $ubn = $record[2];
            $pedigreeRegisterAbbreviation = $record[3];

            if ($this->skipRecord($record)) {
                $skippedCount++;
                continue;
            }


            if ($this->isBreederNumberAlreadyUsedForRegistration($breederNumber)) {
                $alreadyExistsCount++;
                continue;
            }


            $location = $this->getLocation($ubn, $companyName);
            if (!$location) {
                $errors++;
                continue;
            }


            $register = $this->getPedigreeRegister($pedigreeRegisterAbbreviation);
            if (!$register) {
                $this->writeLn('PEDIGREE REGISTER NULL CHECK FAILED": '.$pedigreeRegisterAbbreviation);
                $errors++;
                continue;
            }

            if (key_exists($breederNumber, $newlyInsertedBreederNumbers)) {
                if ($ubn === $newlyInsertedBreederNumbers[$breederNumber]) {
                    $duplicateBreederNumbersInFileOnSameUbn[$breederNumber] = $ubn;
                } else {
                    $duplicateBreederNumbersInFileOnDifferentUbn[$breederNumber] = $ubn;
                }
                $errors++;
                continue;
            }

            $newRegistration = new PedigreeRegisterRegistration();
            $newRegistration->setPedigreeRegister($register);
            $newRegistration->setLocation($location);
            $newRegistration->setBreederNumber($breederNumber);
            $this->em->persist($newRegistration);

            $newlyInsertedBreederNumbers[$breederNumber] = $ubn;

            $newCount++;
            $this->cmdUtil->advanceProgressBar(1);
        }

        $this->em->flush();
        $this->cmdUtil->setProgressBarMessage('Records persisted new|alreadyExists|skipped: '.$newCount.'|'.$alreadyExistsCount.'|'.$skippedCount
        .'  duplicate breederNumber input sameUbn|differentUbn: '.count($duplicateBreederNumbersInFileOnSameUbn).'|'.count($duplicateBreederNumbersInFileOnDifferentUbn));
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();

        $this->printMissingData();

        return true;
    }


    /**
     * @param array $record
     * @return bool
     */
    private function skipRecord($record)
    {
        return ArrayUtil::get(4, $record, null) !== null;
    }


    /**
     * @return bool
     */
    private function parse()
    {
        $csvOptions = MigrationUtil::createInitialValuesFolderCsvImport(ImportFileName::PEDIGREE_REGISTER_REGISTRATIONS, $this->rootDir);

        if(!FilesystemUtil::csvFileExists($this->rootDir, $csvOptions)) {
            $this->cmdUtil->writeln($csvOptions->getFileName().' is missing. No '.$csvOptions->getFileName().' data is imported!');
            return false;
        }

        $this->writeLn('Parsing csv import file...');
        $csv = CsvParser::parse($csvOptions);
        if(!is_array($csv)) {
            $this->cmdUtil->writeln('Import file failed or import file is empty');
            return false;
        }

        $this->data = $csv;
        return true;
    }


    private function initializeSearchArrays()
    {
        $this->writeLn('Initializing search arrays...');

        $this->registrations = new ArrayCollection($this->em->getRepository(PedigreeRegisterRegistration::class)->findAll());
        $this->registers = new ArrayCollection($this->em->getRepository(PedigreeRegister::class)->findAll());
        $this->locations = new ArrayCollection($this->em->getRepository(Location::class)->findAll());

        $this->missingLocations = [];
        $this->locationsFoundWithoutMatchingOwner = [];
    }


    /**
     * @return bool
     */
    private function validateInputFile()
    {
        $ubnAbbreviationCombinations = [];
        $breederNumbers = [];

        $duplicateBreederNumbers = [];
        $duplicateUbnAbbreviationCombinations = [];
        $missingPedigreeRegisters = [];
        $incorrectBreederNumbers = [];

        foreach ($this->data as $record) {

            $breederNumber = $record[0];
            $companyName = $record[1];
            $ubn = $record[2];
            $pedigreeRegisterAbbreviation = $record[3];

            if ($this->skipRecord($record)) {
                continue;
            }

            $key = $breederNumber.' '.$companyName.' '.$ubn.' '.$pedigreeRegisterAbbreviation;

            if ($breederNumber === '' || $breederNumber == null) {
                $this->writeLn('MISSING BREEDER NUMBER: '.$key);
                return false;
            } elseif (strlen($breederNumber) !== 5) {
                $incorrectBreederNumbers[$key] = $breederNumber;
            }

            if ($companyName === '' || $companyName == null) {
                $this->writeLn('MISSING COMPANY NAME: '.$key);
                return false;
            }

            if ($ubn === '' || $ubn == null) {
                $this->writeLn('MISSING UBN: '.$key);
                return false;
            }

            if ($pedigreeRegisterAbbreviation === '' || $pedigreeRegisterAbbreviation == null) {
                $this->writeLn('MISSING REGISTER: '.$key);
                return false;
            }

            if (key_exists($breederNumber, $duplicateBreederNumbers)) {
                $duplicateBreederNumbers[$breederNumber] = $breederNumber;
            } else {
                $breederNumbers[$breederNumber] = $breederNumber;
            }

            $ubnAbbreviationCombination = $ubn.$pedigreeRegisterAbbreviation;
            if (key_exists($ubnAbbreviationCombination, $ubnAbbreviationCombinations)) {
                $duplicateUbnAbbreviationCombinations[$ubnAbbreviationCombination] = $ubnAbbreviationCombination;
            } else {
                $ubnAbbreviationCombinations[$ubnAbbreviationCombination] = $ubnAbbreviationCombination;
            }

            $register = $this->getPedigreeRegister($pedigreeRegisterAbbreviation);
            if (!$register) {
                $missingPedigreeRegisters[$pedigreeRegisterAbbreviation] = $pedigreeRegisterAbbreviation;
            }
        }

        $isValid = true;

        if (count($duplicateBreederNumbers) > 0) {
            $this->writeLn('There are duplicate breederNumbers in the csv import file: '
                . implode(', ', $duplicateBreederNumbers));
            $isValid = false;
        }

        if (count($incorrectBreederNumbers) > 0) {
            $this->writeLn('There are incorrect breederNumbers in the csv import file: '
                . implode(', ', $incorrectBreederNumbers));
            $isValid = false;
        }

        if (count($duplicateUbnAbbreviationCombinations) > 0) {
            $this->writeLn('There are duplicate ubn-pedigreeRegister combinations in the csv import file: '
                . implode(', ', $duplicateUbnAbbreviationCombinations));
            $isValid = true;
        }

        if (count($missingPedigreeRegisters) > 0) {
            $this->writeLn('There are missing pedigreeRegister in the database, based on the registers in the csv import file: '
                . implode(', ', $missingPedigreeRegisters));
            $isValid = false;
        }

        return $isValid;
    }


    /**
     * @param $ubn
     * @param $companyName
     * @return Location|null
     */
    private function getLocation($ubn, $companyName)
    {
        /** @var ArrayCollection|Location[] $locations */
        $locations = $this->locations->matching(LocationCriteria::byUbn($ubn, true));

        if ($locations->count() === 0) {
            $this->missingLocations[$ubn . ': ' . $companyName] = $ubn;
            return null;
        }


        foreach ($locations as $location) {
            if ($this->locationMatchesCompanyName($location, $companyName)) {
                return $location;
            }
        }

        return null;
    }


    /**
     * @param Location $location
     * @param string $importedCompanyName
     * @return Location|null
     */
    private function locationMatchesCompanyName(Location $location, $importedCompanyName)
    {
        $importedCompanyName = $this->lowercaseNameWithoutPrepositions($importedCompanyName);
        $extractedLastName = $this->lowercaseNameWithoutPrepositions($this->extractedCompanyLastName($importedCompanyName));

        $dbCompanyName = '';
        if ($location->getCompany()) {
            $dbCompanyName = $this->lowercaseNameWithoutPrepositions($location->getCompany()->getCompanyName());
            if ($dbCompanyName === $importedCompanyName || StringUtil::containsSubstring($importedCompanyName, $dbCompanyName)) {
                return $location;
            }
        }

        $dbLocationHolder = $this->lowercaseNameWithoutPrepositions($location->getLocationHolder());
        if ($dbLocationHolder === $importedCompanyName || StringUtil::containsSubstring($importedCompanyName, $dbLocationHolder)) {
            return $location;
        }

        $foundOwnerName = '';
        if ($location->getOwner()) {
            $foundOwnerName = $this->lowercaseNameWithoutPrepositions(
                $location->getOwner()->getLastName() . ', ' . $location->getOwner()->getFirstName()
            );
            if ($foundOwnerName === $importedCompanyName || StringUtil::containsSubstring($importedCompanyName, $foundOwnerName)) {
                return $location;
            }

            $dbFullName = $this->lowercaseNameWithoutPrepositions($location->getOwner()->getFullName());

            if ($dbFullName || StringUtil::containsSubstring($importedCompanyName, $dbFullName)) {
                return $location;
            }

            $dbLastName = $this->lowercaseNameWithoutPrepositions($location->getOwner()->getLastName());
            if ($dbLastName === $importedCompanyName || StringUtil::containsSubstring($importedCompanyName, $dbLastName)) {
                return $location;
            }

            $flippedCompanyName = $this->lowercaseNameWithoutPrepositions($this->flipCompanyName($importedCompanyName));
            if ($dbFullName === $flippedCompanyName || StringUtil::containsSubstring($flippedCompanyName, $dbFullName)) {
                return $location;
            }

            if ($dbLastName === $flippedCompanyName || StringUtil::containsSubstring($flippedCompanyName, $dbLastName)) {
                return $location;
            }

            $dbFirstName = $this->lowercaseNameWithoutPrepositions($location->getOwner()->getFirstName());
            if ($extractedLastName === $dbLastName || StringUtil::containsSubstring($extractedLastName, $dbLastName)
            || $extractedLastName === $dbFirstName || StringUtil::containsSubstring($extractedLastName, $dbFirstName)) {
                return $location;
            }
        }

        if ($location->getCompany() && $dbCompanyName !== ''
            && ($dbCompanyName === $extractedLastName || StringUtil::containsSubstring($extractedLastName, $dbCompanyName))) {
            return $location;
        }


        $this->locationsFoundWithoutMatchingOwner[$location->getUbn() . ': ' . $foundOwnerName.'<='.$importedCompanyName] = $location->getUbn();

        return null;
    }


    /**
     * @param $name
     * @return string
     */
    private function lowercaseNameWithoutPrepositions($name)
    {
        $lowercaseName = trim(strtolower($name));
        $middleReplaced =
            strtr($lowercaseName, [
                ' van ' => ' ',
                ' de ' => ' ',
                ' der ' => ' ',
                ' het ' => ' ',
                ' vd ' => ' ',
                ' den ' => ' ',
            ]);

        $lastPartReplaced =
            strtr($middleReplaced, [
                ' van' => '',
                ' de' => '',
                ' der' => '',
                ' het' => '',
                ' vd' => '',
                ' den' => '',
            ]);

        return rtrim(StringUtil::removeSpaces($lastPartReplaced),',');
    }


    /**
     * @param string $companyName
     * @return string
     */
    private function flipCompanyName($companyName)
    {
        $companyNameV2 = explode(',',$companyName);
        if (count($companyNameV2) > 1) {
            $part1 = array_shift($companyNameV2);
            return trim(implode(',', $companyNameV2)) .' '. ltrim($part1);
        }
        return $companyName;
    }


    private function extractedCompanyLastName($companyName)
    {
        $companyNameV2 = explode(',',$companyName);
        if (count($companyNameV2) > 1) {
            return $this->lowercaseNameWithoutPrepositions(array_shift($companyNameV2));
        }
        return $companyName;
    }


    /**
     * @param string $abbreviation
     * @return PedigreeRegister|null
     */
    private function getPedigreeRegister($abbreviation)
    {
        $abbreviation = trim($abbreviation);

        if ($abbreviation === 'SOAY') {
            $abbreviation = 'Soay';
        }

        if ($abbreviation === 'BM') {
            $abbreviation = 'BdM';
        }

        /** @var ArrayCollection|PedigreeRegister[] $registers */
        $registers = $this->registers->matching(PedigreeRegisterCriteria::byAbbreviation($abbreviation));
        if ($registers->count() > 0) {
            return $registers->first();
        }

        return null;
    }


    /**
     * @param string $breederNumber
     * @return bool
     */
    private function isBreederNumberAlreadyUsedForRegistration($breederNumber)
    {
        /** @var ArrayCollection|PedigreeRegisterRegistration[] $registrations */
        return $this->registrations->matching(PedigreeRegisterRegistrationCriteria::byBreederNumber($breederNumber))->count() > 0;
    }


    private function printMissingData()
    {
        $isDataMissing = false;
        $orderOnNewLine = true;

        if (count($this->missingLocations) > 0) {
            $errorMessage = count($this->missingLocations). ' Missing locations: ';
            if  ($orderOnNewLine) {
                $this->writeLn($errorMessage);
                foreach ($this->missingLocations as $key => $value) {
                    $this->writeLn($key);
                }
            } else {
                $this->writeLn($errorMessage.implode(',', $this->missingLocations));
            }

            $isDataMissing = true;
        }

        if (count($this->locationsFoundWithoutMatchingOwner) > 0) {
            $errorMessage = count($this->locationsFoundWithoutMatchingOwner).' Locations without a matching owner: ';
            if  ($orderOnNewLine) {
                $this->writeLn($errorMessage);
                foreach ($this->locationsFoundWithoutMatchingOwner as $key => $value) {
                    $this->writeLn($value);
                }
            } else {
                $this->writeLn($errorMessage.ArrayUtil::implode($this->locationsFoundWithoutMatchingOwner));
            }

            $isDataMissing = true;
        }

        if (!$isDataMissing) {
            $this->writeLn('No data was missing');
        }
    }
}