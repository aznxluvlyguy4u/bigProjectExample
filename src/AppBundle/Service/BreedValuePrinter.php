<?php


namespace AppBundle\Service;


use AppBundle\Util\FilesystemUtil;
use AppBundle\Util\LoggerUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Filesystem\Filesystem;

class BreedValuePrinter
{
    const OUTPUT_DIRECTORY = "breedvalues";
    const DEFAULT_COLUMN_SEPARATOR = ';';
    const DEFAULT_ROW_SEPARATOR = "\n";
    const DEFAULT_SKIP_EXISTING_FILES = true;

    /** @var ObjectManager */
    private $em;
    /** @var Connection */
    private $conn;
    /** @var Logger */
    private $logger;
    /** @var String */
    private $cacheDir;
    /** @var Filesystem */
    private $fs;

    /** @var string */
    private $columnSeparator;
    /** @var string */
    private $rowSeparator;
    /** @var string */
    private $outputDir;
    /** @var boolean */
    private $skipExistingFiles;

    /** @var array */
    private $ubns;

    public function __construct(ObjectManager $em, $logger, $cacheDir)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->logger = $logger;
        $this->cacheDir = $cacheDir;
        $this->fs = new Filesystem();

        $this->columnSeparator = self::DEFAULT_COLUMN_SEPARATOR;
        $this->rowSeparator = self::DEFAULT_ROW_SEPARATOR;
        $this->outputDir = $cacheDir.'/'.self::OUTPUT_DIRECTORY.'/';
        $this->skipExistingFiles = self::DEFAULT_SKIP_EXISTING_FILES;

        FilesystemUtil::createFolderPathIfNull($this->outputDir, $this->fs, $this->logger);
    }


    /**
     * @param null $lowerUbnLimit
     */
    private function getUbns($lowerUbnLimit = null)
    {
        $ubnFilter1 = $lowerUbnLimit != null ? "WHERE a.ubn_of_birth >= '$lowerUbnLimit'" : '';
        $ubnFilter2 = $lowerUbnLimit != null ? "WHERE l.ubn >= '$lowerUbnLimit'" : '';

        $this->notice('Get ubns ...');
        $sql = "SELECT
                  ubn_of_birth as ubn
                FROM breed_value b
                  INNER JOIN animal a ON b.animal_id = a.id
                $ubnFilter1
                GROUP BY ubn_of_birth
                UNION DISTINCT
                SELECT
                  l.ubn as ubn
                FROM breed_value b
                  INNER JOIN animal a ON b.animal_id = a.id
                  LEFT JOIN location l ON a.location_id = l.id
                $ubnFilter2
                GROUP BY l.ubn
                ORDER BY ubn";
        $results = $this->conn->query($sql)->fetchAll();

        foreach ($results as $result) {
            $ubn = $result['ubn'];
            $this->ubns[$ubn] = $ubn;
        }

        $this->overwriteNotice('Got ' . count($results) . ' ubns');
    }


    /**
     * @param bool $order
     * @param null $ubn
     * @return array
     */
    private function getBreedValues($order = false, $ubn = null)
    {
        if(ctype_digit($ubn) || is_int($ubn)) {
            $ubnString =  " AND (a.ubn_of_birth = '".$ubn."' OR l.ubn = '".$ubn."') ";
            $orderString = $order ? " ORDER BY a.animal_order_number, t.nl " : "";
        } else {
            $ubnString = '';
            $orderString = $order ? " ORDER BY l.ubn, a.animal_order_number, t.nl " : "";
        }

        $sql = "SELECT
                  c.company_name as bedrijfsnaam,
                  fc.company_name as fokkerbedrijfsnaam,
                  a.ubn_of_birth as fokkerubn,
                  l.ubn as huidig_ubn,
                  r.abbreviation as stamboek,
                  a.breed_code as rascode,
                  rastype.dutch as rastype,
                  a.scrapie_genotype as scrapiegenotype,
                  gender.dutch as geslacht,
                  CONCAT(a.uln_country_code, a.uln_number) as uln,
                  CONCAT(a.pedigree_country_code, a.pedigree_number) as stn,
                  a.animal_order_number as werknummer,
                  DATE(a.date_of_birth) as geboortedatum,
                  CONCAT(dad.uln_country_code, dad.uln_number) as uln_vader,
                  CONCAT(dad.pedigree_country_code, dad.pedigree_number) as stn_vader,
                  CONCAT(mom.uln_country_code, mom.uln_number) as uln_moeder,
                  CONCAT(mom.pedigree_country_code, mom.pedigree_number) as stn_moeder,
                  t.nl as fokwaarde_type,
                  ROUND(CAST(b.value - g.value AS NUMERIC),4) as gecorrigeerde_fokwaarde,
                  ROUND(CAST(SQRT(b.reliability) AS NUMERIC), 2) as nauwkeurigheid,
                  DATE(generation_date) as fokwaarde_berekening_datum,
                  a.name as aiind
                FROM breed_value b
                  INNER JOIN animal a ON b.animal_id = a.id
                  LEFT JOIN animal mom ON mom.id = a.parent_mother_id
                  LEFT JOIN animal dad ON dad.id = a.parent_father_id
                  INNER JOIN breed_value_type t ON b.type_id = t.id
                  INNER JOIN breed_value_genetic_base g ON g.year = DATE_PART('year', b.generation_date) AND g.breed_value_type_id = t.id
                  INNER JOIN (
                    SELECT animal_id, type_id, MAX(generation_date) as max_generation_date
                    FROM breed_value
                      INNER JOIN breed_value_type t ON type_id = t.id
                    WHERE breed_value.reliability >= t.min_reliability
                    GROUP BY animal_id, type_id
                    )z ON z.animal_id = b.animal_id AND z.type_id = b.type_id AND b.generation_date = z.max_generation_date
                  LEFT JOIN location l ON a.location_id = l.id
                  LEFT JOIN pedigree_register r ON a.pedigree_register_id = r.id
                  LEFT JOIN company c ON c.id = l.company_id
                  LEFT JOIN location fl ON fl.id = a.location_of_birth_id
                  LEFT JOIN company fc ON fl.company_id = fc.id
                  LEFT JOIN (VALUES ('Ram', 'Ram'),('Ewe', 'Ooi'),('Neuter', 'Onbekend')) AS gender(english, dutch) ON a.type = gender.english
                  LEFT JOIN (VALUES ('BLIND_FACTOR', 'Blindfactor'),('MEAT_LAMB_FATHER', 'Vleeslamvaderdier'),('MEAT_LAMB_MOTHER', 'Vleeslammoederdier'),
                    ('PARENT_ANIMAL', 'Ouderdier'),('PURE_BRED', 'Volbloed'),('REGISTER', 'Register'),
                    ('SECONDARY_REGISTER', 'Hulpboek'),('UNDETERMINED', 'Onbepaald')) AS rastype(english, dutch) ON a.breed_type = rastype.english
                WHERE b.reliability >= t.min_reliability AND (l.is_active = TRUE OR l.is_active ISNULL) $ubnString
                " . $orderString;
        return $this->conn->query($sql)->fetchAll();
    }


    /**
     * @param $ubn
     */
    public function printBreedValuesByUbn($ubn)
    {
        $filename = $this->getFilename($ubn);

        if($this->skipExistingFiles && $this->fileExists($filename)){
            return;
        }

        $results = $this->getBreedValues(true, $ubn);
        if(count($results) === 0) { return; }

        $this->clearFile($filename);
        $this->printColumnHeaders($results, $filename);

        foreach ($results as $values) {
            $this->printDataRecord($values, $filename);
        }
    }


    /**
     * @param string|int $ubn
     * @return string
     */
    private function getFilename($ubn = null)
    {
        $filename = 'nsfo_fokwaarden_'.TimeUtil::getTimeStampToday().'_';
        $extension = '.csv';
        return $ubn != null ? $filename . 'UBN' . $ubn . $extension : $filename . 'compleet' . $extension;
    }


    /**
     * @param $minimumUbn
     */
    public function printBreedValuesAllUbns($minimumUbn = null)
    {
        $this->getUbns($minimumUbn);
        $this->notice('Printing separate breedValue csv files for all ubns to '.$this->outputDir);

        $this->notice('Getting all sorted breedValues for searchArray ...');
        $results = $this->getBreedValues(true, null);

        $this->overwritePadding();

        $ubnCount = count($this->ubns);
        $loopCount = 0;
        foreach ($this->ubns as $ubn)
        {
            $ubn = strval($ubn);

            $filename = $this->getFilename($ubn);

            if($this->skipExistingFiles && $this->fileExists($filename)){
                continue;
            }

            $this->clearFile($filename);
            $this->printColumnHeaders($results, $filename);

            foreach ($results as $values) {
                $ubnOfBirth = strval($values['fokkerubn']);
                $currentUbn = strval($values['huidig_ubn']);
                if($ubnOfBirth === $ubn || $currentUbn === $ubn) {
                    $this->printDataRecord($values, $filename);
                }
            }

            $loopCount++;
            $this->overwriteNotice('BreedValue csv file ('.$loopCount.'/'.$ubnCount.')  last ubn: '.$ubn);
        }
        $this->notice('Done!');
    }


    /**
     * @param string $filename
     * @return bool
     */
    private function fileExists($filename)
    {
        return FilesystemUtil::filesExist($this->outputDir, $filename, $this->fs);
    }


    /**
     * @param string $filename
     */
    private function clearFile($filename)
    {
        file_put_contents($this->outputDir.$filename, '');
    }


    /**
     * @param array $results
     * @param string $filename
     */
    private function printColumnHeaders($results, $filename)
    {
        $columnHeaders = array_keys($results[0]);
        $headerRecord = implode($this->columnSeparator, $columnHeaders);
        file_put_contents($this->outputDir.$filename, $headerRecord.$this->rowSeparator, FILE_APPEND);
    }


    /**
     * @param array $values
     * @param string $filename
     */
    private function printDataRecord($values, $filename)
    {
        $record = implode($this->columnSeparator, $values);
        file_put_contents($this->outputDir.$filename, $record.$this->rowSeparator, FILE_APPEND);
    }


    /**
     * @param $string
     */
    private function notice($string)
    {
        $this->logger->notice($string);
    }


    private function overwritePadding()
    {
        $this->notice(' ... ');
        $this->notice(' ... ');
    }


    /**
     * @param $line
     */
    private function overwriteNotice($line)
    {
        LoggerUtil::overwriteNotice($this->logger, $line);
    }
}