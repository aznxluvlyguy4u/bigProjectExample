<?php


namespace AppBundle\Migration;


use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\PedigreeRegister;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\Specie;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\GenderChanger;
use AppBundle\Util\NullChecker;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\Translation;
use AppBundle\Util\Validator;
use ClassesWithParents\G;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\Time;

class AnimalTableMigrator extends MigratorBase
{
    const PRINT_OUT_INVALID_UBNS_OF_BIRTH = true;
    const UBNS_PER_ROW = 8;
    const FILENAME_INCORRECT_ULNS = 'incorrect_ulns.csv';

    const VALUE = 'VALUE';
    const ABBREVIATION = 'ABBREVIATION';

    //Column name
    const VSM_ID = 'VSM_ID';
    const STN = 'STN';
    const ANIMAL_ORDER_NUMBER = 'ANIMAL_ORDER_NUMBER';
    const ULN = 'ULN';
    const NICKNAME = 'NICKNAME';
    const VSM_ID_FATHER = 'VSM_ID_FATHER';
    const VSM_ID_MOTHER = 'VSM_ID_MOTHER';
    const GENDER = 'GENDER';
    const DATE_OF_BIRTH = 'DATE_OF_BIRTH';
    const BREED_CODE = 'BREED_CODE';
    const UBN_OF_BIRTH = 'UBN_OF_BIRTH';
    const PEDIGREE_REGISTER = 'PEDIGREE_REGISTER';
    const BREED_TYPE = 'BREED_TYPE';
    const SCRAPIE_GENOTYPE = 'SCRAPIE_GENOTYPE';

    //PedigreeRegister
    const PR_UNKNOWN = '* Onbekend stamboek Schaap';
    const PR_EN_MANAGEMENT = 'EN-Management';
    const PR_EN_BASIS = 'EN-Basis';
    const PR_EN_MANAGEMENT_ABBREVIATION = null; //Set here or edit in db later
    const PR_EN_BASIS_ABBREVIATION = null; //Set here or edit in db later

    /**
     * MyoMaxMigrator constructor.
     * @param CommandUtil $cmdUtil
     * @param ObjectManager $em
     * @param OutputInterface $outputInterface
     * @param array $data
     * @param string $rootDir
     */
    public function __construct(CommandUtil $cmdUtil, ObjectManager $em, OutputInterface $outputInterface, array $data, $rootDir)
    {
        parent::__construct($cmdUtil, $em, $outputInterface, $data, $rootDir);
    }

    public function migrate()
    {
        //TODO Migrate PedigreeRegisterData NsfoMigratePedigreeregistersCommand

        //TODO Regender animals to Ewe/Ram before setting parents-children

        //TODO SEARCH ARRAY CURRENT ANIMAL DATA
//        $sql = "SELECT myo_max, name FROM animal WHERE myo_max NOTNULL";
//        $results = $this->em->getConnection()->query($sql)->fetchAll();
//        $searchArray = [];
//        foreach ($results as $result) {
//            $searchArray[$result['name']] = $result['myo_max'];
//        }

//        $this->cmdUtil->setStartTimeAndPrintIt(count($this->data)+1, 1);
        
        
        $newCount = 0;
        foreach ($this->data as $record) {

            $vsmId = $record[0];
            $stnImport = $record[1];
            $animalOrderNumberImport = $record[2];

            $uln = $this->parseUln($record[3]);
            $ulnCountryCode = $uln[JsonInputConstant::ULN_COUNTRY_CODE];
            $ulnNumber = $uln[JsonInputConstant::ULN_NUMBER];
            
            $nickName = $record[4];
            $vsmIdFather = $record[5];
            $vsmIdMother = $record[6];
            $gender = $this->parseGender($record[7]);
            $dateOfBirthString = $record[8];
            $breedCode = $record[9];
            $ubnOfBirth = $record[10]; //ubnOfBreeder

            $pedigreeRegister = self::parsePedigreeRegister($record[11]);
            $pedigreeRegisterFullname = $pedigreeRegister[self::VALUE];
            $pedigreeRegisterAbbreviation = $pedigreeRegister[self::ABBREVIATION];

            $breedType = Translation::getEnglish(strtoupper($record[12]));
            $scrapieGenotype = $record[13];

        }


    }


    public function verifyData()
    {
        $neutersByVsmId = $this->getNeutersByVsmId();
        
        $vsmIdCollection = [];
        $stnImportCollection = [];
        $animalOrderNumberImportCollection = [];
        $ulnImportCollection = [];
        $nickNameCollection = [];
        $vsmIdFatherCollection = [];
        $vsmIdMotherCollection = [];
        $genderCollection = [];
        $dateOfBirthStringCollection = [];
        $breedCodeCollection = [];
        $ubnOfBirthCollection = []; //ubnOfBreeder
        $pedigreeRegisterCollection = [];
        $breedTypeCollection = [];
        $scrapieGenotypeCollection = [];

        $newCount = 0;
        foreach ($this->data as $record) {

            $vsmId = $record[0];
            $stnImport = $record[1];
            $animalOrderNumberImport = $record[2];
            $ulnImport = $record[3];
            $nickName = $record[4];
            $vsmIdFather = $record[5];
            $vsmIdMother = $record[6];
            $gender = $record[7];
            $dateOfBirthString = $record[8];
            $breedCode = $record[9];
            $ubnOfBirth = $record[10]; //ubnOfBreeder
            $pedigreeRegister = trim($record[11]);
            $breedType = $record[12];
            $scrapieGenotype = $record[13];

            $vsmIdCollection[] = $vsmId;
            $stnImportCollection[] = $stnImport;
            $animalOrderNumberImportCollection[] = $animalOrderNumberImport;
            $ulnImportCollection[] = $ulnImport;
            $nickNameCollection[] = $nickName;
            $vsmIdFatherCollection[] = $vsmIdFather;
            $vsmIdMotherCollection[] = $vsmIdMother;
            $genderCollection[$gender] = $gender;
            $dateOfBirthStringCollection[] = $dateOfBirthString;
            $breedCodeCollection[] = $breedCode;
            $ubnOfBirthCollection[] = $ubnOfBirth; //ubnOfBreeder
            $pedigreeRegisterCollection[$pedigreeRegister] = $pedigreeRegister;
            $breedTypeCollection[$breedType] = $breedType;
            $scrapieGenotypeCollection[$scrapieGenotype] = $scrapieGenotype;
        }
        
        
        $sql = "SELECT DISTINCT(ubn) as ubn FROM location";
        $results = $this->em->getConnection()->query($sql)->fetchAll();
        $ubnsOfLocationsInDatabase = [];
        foreach ($results as $result) {
            $ubn = $result['ubn'];
            $ubnsOfLocationsInDatabase[$ubn] = $ubn;
        }

        $vsmIdsMissing = 0;
        $invalidVsmId = [];
        $neuterVsmIdsCount = 0;
        foreach ($vsmIdCollection as $vsmId) {
            //Check if vsmId only contains digits.
            if($vsmId == null || $vsmId == '') { $vsmIdsMissing++; }
            else if(!ctype_digit($vsmId)) { $invalidVsmId[] = $vsmId; }
            else if(array_key_exists($vsmId, $neutersByVsmId)) { $neuterVsmIdsCount++; }
        }
        $this->output->writeln('vsmIds missing: '.$vsmIdsMissing.' | '.'invalidVsmIds: '.count($invalidVsmId).' | VsmIds without gender: '.$neuterVsmIdsCount);

        $stnMissing = 0;
        $invalidStn = [];
        foreach ($stnImportCollection as $stn) {
            if($stn == null || $stn == '') { $stnMissing++; }
            else if(!Validator::verifyPedigreeCountryCodeAndNumberFormat($stn, true)) { $invalidStn[] = $stn; }
        }
        $this->output->writeln('stns missing: '.$stnMissing.' | '.'invalidStns: '.count($invalidStn));

//        foreach ($animalOrderNumberImportCollection as $animalOrderNumber) {
//
//        }

        $ulnsMissing = 0;
        $invalidUlns = [];
        foreach ($ulnImportCollection as $uln) {
            if($uln == null || $uln == '') { $ulnsMissing++; }
            else if(!Validator::verifyUlnFormat($uln, true)) { $invalidUlns[] = $uln; }
        }
        $this->output->writeln('ulns missing: '.$ulnsMissing.' | '.'invalidUlns: '.count($invalidUlns));


        if(self::PRINT_OUT_INVALID_UBNS_OF_BIRTH) {
            $invalidUlnCount = 0;
            foreach ($invalidUlns as $uln) {
                file_put_contents($this->outputFolder.'/'.self::FILENAME_INCORRECT_ULNS,
                    $uln
                    ."\n", FILE_APPEND);
            }
        }

        $nickNames = [];
        foreach ($nickNameCollection as $nickName) {
            if($nickName != null && $nickName != '') { $nickNames[] = $nickName; }
        }
        $this->output->writeln('nickNames: '.count($nickNames));

        $vsmIdFathersIncorrect = [];
        foreach ($vsmIdFatherCollection as $vsmIdFather) {
            if($vsmIdFather != null && $vsmIdFather != '') {
                if(!ctype_digit($vsmIdFather)) { $vsmIdFathersIncorrect[] = $vsmIdFather; }
            }
        }
        $this->output->writeln('Incorrect FatherIds: '.count($vsmIdFathersIncorrect));

        $vsmIdMothersIncorrect = [];
        foreach ($vsmIdMotherCollection as $vsmIdMother) {
            if($vsmIdMother != null && $vsmIdMother != '') {
                if(!ctype_digit($vsmIdMother)) { $vsmIdMothersIncorrect[] = $vsmIdMother; }
            }
        }
        $this->output->writeln('Incorrect MotherIds: '.count($vsmIdMothersIncorrect));

        $genders = '';
        foreach ($genderCollection as $gender) {
            $genders = $genders.'|'.$gender;
        }
        $this->output->writeln('Genders: '.$genders);

        $dateOfBirthMissing = 0;
        $incorrectDateOfBirths = [];
        foreach ($dateOfBirthStringCollection as $dateOfBirthString) {
            if($dateOfBirthString == null || $dateOfBirthString == '') { $dateOfBirthMissing++; }
            else if(!TimeUtil::isFormatYYYYMMDD($dateOfBirthString)) {  }
        }
        $this->output->writeln('dateOfBirths missing: '.$dateOfBirthMissing.' | '.'invalid dateOfBirths: '.count($incorrectDateOfBirths));

//        foreach ($breedCodeCollection as $breedCode) {
//
//        }

        $ubnsOfBirthMissing = 0;
        $invalidUbnOfBirths = [];
        $ubnsOfBirthNotInDatabase = [];
        foreach ($ubnOfBirthCollection as $ubnOfBirth) {
            if($ubnOfBirth == null || $ubnOfBirth == '') { $ubnsOfBirthMissing++; }
            else if(!ctype_digit($ubnOfBirth)) { $invalidUbnOfBirths[] = $ubnOfBirth; }
            else if(array_key_exists($ubnOfBirth, $ubnsOfLocationsInDatabase)) { $ubnsOfBirthNotInDatabase[] = $ubnOfBirth; }
        }
        $this->output->writeln('ubnsOfBirthMissing missing: '.$ubnsOfBirthMissing.' | '.'invalid ubnsOfBirth: '.count($invalidUbnOfBirths).' | ubnOfBirths not in Database: '.count($ubnsOfBirthNotInDatabase));


        $pedigreeRegisters = '';
        foreach ($pedigreeRegisterCollection as $pedigreeRegister) {
            $pedigreeRegisters = $pedigreeRegisters.'|'.$pedigreeRegister;
        }
        $this->output->writeln('PedigreeRegisters: '.$pedigreeRegisters);

        $breedTypes = '';
        foreach ($breedTypeCollection as $breedType) {
            $breedTypes = $breedTypes.'|'.$breedType;
        }
        $this->output->writeln('BreedTypes: '.$breedTypes);

        $scrapieGenotypes = '';
        foreach ($scrapieGenotypeCollection as $scrapieGenotype) {
            $scrapieGenotypes = $scrapieGenotypes.'|'.$scrapieGenotype;
        }
        $this->output->writeln('ScrapieGenotypes: '.$scrapieGenotypes);
    }


    /**
     * @param string $pedigreeRegister
     * @return array
     */
    public static function parsePedigreeRegister($pedigreeRegister)
    {
        $pedigreeRegisterFullName = null;
        $pedigreeRegisterAbbreviation = null;

        switch ($pedigreeRegister) {
            case self::PR_UNKNOWN: break;
            case ' ':              break;
            case '';               break;
            
            case self::PR_EN_BASIS:
                $pedigreeRegisterFullName = Translation::getEnglish(trim(strtoupper($pedigreeRegister)));
                $pedigreeRegisterAbbreviation = null;
                break;

            case self::PR_EN_MANAGEMENT:
                $pedigreeRegisterFullName = Translation::getEnglish(trim(strtoupper($pedigreeRegister)));
                $pedigreeRegisterAbbreviation = null;
                break;
            
            default:
                $parts = explode(' : ', $pedigreeRegister);
                if(count($parts) == 2) {
                    $pedigreeRegisterAbbreviation = $parts[0];
                    $pedigreeRegisterFullName = Translation::getEnglish(trim(strtoupper($parts[1])));
                }
                break;
        }

        return [
          self::VALUE => $pedigreeRegisterFullName,
          self::ABBREVIATION => $pedigreeRegisterAbbreviation,
        ];
    }


    /**
     * @param string $gender
     * @return string
     */
    private function parseGender($gender)
    {
        //The only genders in the file are 'M' and 'V'
        switch ($gender) {
            case GenderType::M: return GenderType::MALE;
            case GenderType::V: return GenderType::FEMALE;
            default: return GenderType::NEUTER;
        }
    }


    /**
     *
     * @param $ulnString
     * @return array
     */
    private function parseUln($ulnString)
    {
        if(Validator::verifyUlnFormat($ulnString)) {
            $parts = explode(' ', $ulnString);
            $parts[0] = str_replace('GB', 'UK', $parts[0]);
        } //TODO ELSE


        return [
          JsonInputConstant::ULN_COUNTRY_CODE => $parts[0],
          JsonInputConstant::ULN_NUMBER => $parts[1],
        ];
    }

    private function parseStn($stnString)
    {

    }


    /**
     * These values are based on the pedigreeRegister data in the import file
     * compared to the values in the database on 2016
     */
    public function updatePedigreeRegister()
    {
        $clunForestNewFullName = 'Clun Forest Schapenvereniging';
        $clunForestAbbr = 'CF';
        $nfsFullName = 'Nederlands Flevolander Schapenstamboek';
        $nfsAbbr = 'NFS';
        $tsnhFullName = 'Texels Schapenstamboek in Noord Holland';
        $tsnhAbbr = 'TSNH';
        $enManagementFullName = 'EN-Management';
        $enManagementAbbr = 'ENM';
        $enBasisFullName = 'EN-Basis';
        $enBasisAbbr = 'ENB';

        $sql = "SELECT * FROM pedigree_register";
        $results = $this->em->getConnection()->query($sql)->fetchAll();
        
        $allSpeciesAreSheep = true;
        $nfsExists = false;
        $tsnhExists = false;
        $enManagementExists = false;
        $enBasisExists = false;
        foreach ($results as $result) {
            $id = $result['id'];
            $abbreviation = $result['abbreviation'];
            $fullName = $result['full_name'];
            $specie = $result['specie'];

            //Update full_name
            if($abbreviation == $clunForestAbbr && $fullName != $clunForestNewFullName) {
                $sql = "UPDATE pedigree_register SET full_name = '".$clunForestNewFullName."' WHERE id = ".$id;
                $this->em->getConnection()->exec($sql);
            }
            
            if($specie != Specie::SHEEP) { $allSpeciesAreSheep = false; }
            if($abbreviation == $nfsAbbr) { $nfsExists = true; }
            if($abbreviation == $tsnhAbbr) { $tsnhExists = true; }
            if($fullName == self::PR_EN_MANAGEMENT) { $enManagementExists = true; }
            if($fullName == self::PR_EN_BASIS) { $enBasisExists = true; }
        }
        
        if(!$allSpeciesAreSheep) {
            $sql = "UPDATE pedigree_register SET specie = '".Specie::SHEEP."'";
            $this->em->getConnection()->exec($sql);
        }

        if(!$nfsExists) {
            $nfs = new PedigreeRegister();
            $nfs->setAbbreviation($nfsAbbr);
            $nfs->setFullName($nfsFullName);
            $nfs->setSpecie(Specie::SHEEP);
            $nfs->setCreationDate(new \DateTime());
            $this->em->persist($nfs);
        }

        if(!$tsnhExists) {
            $tsnh = new PedigreeRegister();
            $tsnh->setAbbreviation($tsnhAbbr);
            $tsnh->setFullName($tsnhFullName);
            $tsnh->setSpecie(Specie::SHEEP);
            $tsnh->setCreationDate(new \DateTime());
            $this->em->persist($tsnh);
        }

        if(!$enManagementExists) {
            $enManagement = new PedigreeRegister();
            $enManagement->setAbbreviation($enManagementAbbr);
            $enManagement->setFullName($enManagementFullName);
            $enManagement->setSpecie(Specie::SHEEP);
            $enManagement->setCreationDate(new \DateTime());
            $this->em->persist($enManagement);
        }

        if(!$enBasisExists) {
            $enBasis = new PedigreeRegister();
            $enBasis->setAbbreviation($enBasisAbbr);
            $enBasis->setFullName($enBasisFullName);
            $enBasis->setSpecie(Specie::SHEEP);
            $enBasis->setCreationDate(new \DateTime());
            $this->em->persist($enBasis);
        }

        $this->em->flush();
    }


    /**
     * @return array
     */
    private function getNeutersByVsmId()
    {
        $sql = "SELECT id, name, gender FROM animal WHERE gender <> 'FEMALE' AND gender <> 'MALE'";
        $results = $this->em->getConnection()->query($sql)->fetchAll();
        $neutersByVsmId = [];
        foreach ($results as $result) {
            $neutersByVsmId[$result['name']] = $result['name'];
        }
        return $neutersByVsmId;
    }


    public function fixGendersInDatabase()
    {
        $neutersByVsmId = $this->getNeutersByVsmId();
        $animalIdByVsmIds = $this->animalRepository->getAnimalPrimaryKeysByVsmId();

        $animalIdsOfNeutersToFemale = [];
        $animalIdsOfNeutersToMale = [];


        foreach ($this->data as $record) {

            $vsmId = $record[0];
            $gender = $this->parseGender($record[7]);

            if (array_key_exists($vsmId, $neutersByVsmId)) {
                //Neuter with this vsmId currently exists in the database
                $animalId = $animalIdByVsmIds[$vsmId];
                if (is_int($animalId)) {
                    if($gender == GenderType::MALE) { $animalIdsOfNeutersToMale[$animalId] = $animalId; }
                    else if($gender == GenderType::FEMALE) { $animalIdsOfNeutersToFemale[$animalId] = $animalId; }
                }
            }

            $vsmIdFather = $record[5];
            if (array_key_exists($vsmIdFather, $neutersByVsmId)) {
                $animalId = $animalIdByVsmIds[$vsmIdFather];
                if (is_int($animalId)) {
                    $animalIdsOfNeutersToMale[$animalId] = $animalId;
                }
            }

            $vsmIdMother = $record[6];
            if (array_key_exists($vsmIdMother, $neutersByVsmId)) {
                $animalId = $animalIdByVsmIds[$vsmIdMother];
                if (is_int($animalId)) {
                    $animalIdsOfNeutersToFemale[$animalId] = $animalId;
                }
            }
        }

        $count = 0;
        $totalCount = count($animalIdsOfNeutersToFemale) + count($animalIdsOfNeutersToMale);

        if($totalCount > 0) {
            $this->cmdUtil->setStartTimeAndPrintIt($totalCount + 1, 1, 'Fixing gender of neuters...');

            $animalIdsFemale = array_keys($animalIdsOfNeutersToFemale);
            foreach ($animalIdsFemale as $animalId) {
                GenderChanger::changeNeuterToFemaleBySql($this->em, $animalId);
                $this->cmdUtil->advanceProgressBar(1, 'id|gender : ' . $animalId . '|' . GenderType::FEMALE);
                $count++;
            }

            $animalIdsMale = array_keys($animalIdsOfNeutersToMale);
            foreach ($animalIdsMale as $animalId) {
                GenderChanger::changeNeuterToMaleBySql($this->em, $animalId);
                $this->cmdUtil->advanceProgressBar(1, 'id|gender : ' . $animalId . '|' . GenderType::MALE);
                $count++;
            }

            $this->cmdUtil->setProgressBarMessage('Genders Fixed: ' . $count);
            $this->cmdUtil->setEndTimeAndPrintFinalOverview();
        } else {
            $this->output->writeln('No genders to fix');
        }

    }
}