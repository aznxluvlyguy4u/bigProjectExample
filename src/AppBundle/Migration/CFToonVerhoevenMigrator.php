<?php


namespace AppBundle\Migration;


use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Employee;
use AppBundle\Enumerator\Specie;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\OutputInterface;

class CFToonVerhoevenMigrator extends MigratorBase
{
    /** @var Employee */
    private $developer;

    /**
     * RacesMigrator constructor.
     * @param CommandUtil $cmdUtil
     * @param ObjectManager $em
     * @param OutputInterface $outputInterface
     * @param array $data
     */
    public function __construct(CommandUtil $cmdUtil, ObjectManager $em, OutputInterface $outputInterface, array $data)
    {
        parent::__construct($cmdUtil, $em, $outputInterface, $data);
    }
    
    public function migrate()
    {
        $this->cmdUtil->setStartTimeAndPrintIt(count($this->data), 1);

        $allAnimalIdByVsmIds = $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray();

        //NOTE THAT CURRENTLY THE PARENTS IN THE CSV FILE ARE NOT IN THE MIGRATION TABLE, AND THUS WILL NOT BE CHECKED

        $sql = "SELECT id, vsm_id, animal_id, uln_origin, stn_origin, animal_order_number, uln_country_code, uln_number,
                pedigree_country_code, pedigree_number, father_id, mother_id FROM animal_migration_table
                WHERE vsm_id = '628826102' OR vsm_id = '628826162' OR vsm_id = '628826182' OR vsm_id = '628826202' 
                OR vsm_id = '632427942' OR vsm_id = '641282382' OR vsm_id = '643220482' OR vsm_id = '643234962' 
                OR vsm_id = '643234982' OR vsm_id = '643235002' OR vsm_id = '643235022' OR vsm_id = '643235042' 
                OR vsm_id = '643235062' OR vsm_id = '643235082' OR vsm_id = '643235102' OR vsm_id = '643235122' 
                OR vsm_id = '643235142' OR vsm_id = '656535542' ORDER BY vsm_id ASC ";
        $results = $this->conn->query($sql)->fetchAll();

        $animalIdByVsmIds = [];
        $ulnOriginByVsmIds = [];
        $stnOriginByVsmIds = [];
        $animalOrderNumberByVsmIds = [];
        $ulnCountryCodeByVsmIds = [];
        $ulnNumberByVsmIds = [];
        $pedigreeCountryCodeByVsmIds = [];
        $pedigreeNumberByVsmIds = [];
        $migrationTableIdByVsmIds = [];

        foreach ($results as $result) {
            $vsmId = $result['vsm_id'];

            $migrationTableIdByVsmIds[$vsmId] = $result['id'];
            $animalIdByVsmIds[$vsmId] = $result['animal_id'];
            $ulnOriginByVsmIds[$vsmId] = $result['uln_origin'];
            $stnOriginByVsmIds[$vsmId] = $result['stn_origin'];
            $animalOrderNumberByVsmIds[$vsmId] = $result['animal_order_number'];
            $ulnCountryCodeByVsmIds[$vsmId] = $result['uln_country_code'];
            $ulnNumberByVsmIds[$vsmId] = $result['uln_number'];
            $pedigreeCountryCodeByVsmIds[$vsmId] = $result['pedigree_country_code'];
            $pedigreeNumberByVsmIds[$vsmId] = $result['pedigree_number'];
        }

        $updateCount = 0;
        $alreadyUpdatedCount = 0;
        foreach ($this->data as $record) {

            $vsmId = $record[0];

            $uln = $record[3];
            $ulnParts = $this->parseUln($uln);
            $ulnCountryCode = $ulnParts[JsonInputConstant::ULN_COUNTRY_CODE];
            $ulnNumber =$ulnParts[JsonInputConstant::ULN_NUMBER];

            $animalOrderNumber = StringUtil::getLast5CharactersFromString($ulnNumber);

            $stnImport = $record[1];
            $stnParts = $this->parseStn($stnImport);
            $pedigreeCountryCode = $stnParts[JsonInputConstant::PEDIGREE_COUNTRY_CODE];
            $pedigreeNumber = $stnParts[JsonInputConstant::PEDIGREE_NUMBER];

            $animalId = null;
            $animalIdIsUpToDate = false;
            if(array_key_exists($vsmId, $animalIdByVsmIds)) {
                $animalId = $animalIdByVsmIds[$vsmId];
                $animalIdIsUpToDate = true;
            } else if(array_key_exists($vsmId, $allAnimalIdByVsmIds)) {
                $animalId = $allAnimalIdByVsmIds[$vsmId];
            }
            $animalId = StringUtil::getNullAsStringOrWrapInQuotes($animalId);

            $id = $migrationTableIdByVsmIds[$vsmId];

            $updateRecord =
                $uln != $ulnOriginByVsmIds[$vsmId] || $ulnCountryCode != $ulnCountryCodeByVsmIds[$vsmId]
                || $ulnNumber != $ulnNumberByVsmIds[$vsmId] || $animalOrderNumber != $animalOrderNumberByVsmIds[$vsmId]
                || $stnImport != $stnOriginByVsmIds[$vsmId] || $pedigreeCountryCode != $pedigreeCountryCodeByVsmIds[$vsmId]
                || $pedigreeNumber != $pedigreeNumberByVsmIds[$vsmId] || $animalIdIsUpToDate;

            if($updateRecord) {
                $sql = "UPDATE animal_migration_table SET uln_origin = '".$uln."', stn_origin = '".$stnImport."', animal_id = ".$animalId.",
                      uln_country_code = '".$ulnCountryCode."', uln_number = '".$ulnNumber."', animal_order_number = '".$animalOrderNumber."',
                      pedigree_country_code = '".$pedigreeCountryCode."', pedigree_number = '".$pedigreeNumber."'
                    WHERE id = ".$id;
                $this->conn->exec($sql);
                $updateCount++;
            } else {
                $alreadyUpdatedCount++;
            }

            $this->cmdUtil->advanceProgressBar(1, 'Updated|Skipped(alreadyUpdated): '.$updateCount.'|'.$alreadyUpdatedCount);
        }
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    /**
     * @param string $vsmSpecieData
     * @return string
     */
    public function convertSpecieData($vsmSpecieData){
        return strtr($vsmSpecieData, [ 'SC' => Specie::SHEEP, 'GE' => Specie::GOAT]);
    }

    /**
     *
     * @param string $ulnString
     * @return array
     */
    private function parseUln($ulnString)
    {
        if(Validator::verifyUlnFormat($ulnString, true)) {
            $parts = explode(' ', $ulnString);
            $parts[0] = str_replace('GB', 'UK', $parts[0]);
        } else {
            $parts = [null, null];
        }

        return [
            JsonInputConstant::ULN_COUNTRY_CODE => $parts[0],
            JsonInputConstant::ULN_NUMBER => StringUtil::padUlnNumberWithZeroes($parts[1]),
        ];

    }

    /**
     * @param string $stnString
     * @return array
     */
    private function parseStn($stnString)
    {
        if(Validator::verifyPedigreeCountryCodeAndNumberFormat($stnString, true)) {
            $parts = explode(' ', $stnString);
            $parts[0] = str_replace('GB', 'UK', $parts[0]);
        } else {
            $parts = [null, null];
        }

        return [
            JsonInputConstant::PEDIGREE_COUNTRY_CODE => $parts[0],
            JsonInputConstant::PEDIGREE_NUMBER => $parts[1],
        ];
    }
}