<?php

namespace AppBundle\Report;


use AppBundle\Component\Count;
use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Constant\UnicodeSymbol;
use AppBundle\Entity\Client;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\EweRepository;
use AppBundle\Entity\Location;
use AppBundle\Entity\Ram;
use AppBundle\Entity\RamRepository;
use AppBundle\Enumerator\GenderType;
use AppBundle\Util\DisplayUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\Translation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

class LivestockReportData extends ReportBase
{
    //Options
    const SHOW_PREDICATE_IN_REPORT = false;

    const FILE_NAME_REPORT_TYPE = 'stallijst';
    const PEDIGREE_NULL_FILLER = '-';
    const ULN_NULL_FILLER = '-';
    const NEUTER_STRING = '-';
    const EWE_LETTER = 'O';
    const RAM_LETTER = 'R';

    /** @var array */
    private $data;

    /** @var Location */
    private $location;

    /**
     * InbreedingCoefficientReportData constructor.
     * @param ObjectManager $em
     * @param ArrayCollection $content
     * @param Client $client
     * @param Location $location
     */
    public function __construct(ObjectManager $em, ArrayCollection $content, Client $client, Location $location)
    {
        $this->client = $client;
        $this->location = $location;

        parent::__construct($em, $client, $this->parseFilename());

        $this->data = [];
        $this->data[ReportLabel::DATE] = TimeUtil::getTimeStampToday('d-m-Y');
        $this->data[ReportLabel::BREEDER_NUMBER] = '-'; //TODO
        $this->data[ReportLabel::UBN] = $this->location->getUbn();
        $this->data[ReportLabel::NAME.'_and_'.ReportLabel::ADDRESS] = $this->parseNameAddressString();
        $this->data[ReportLabel::LIVESTOCK] = Count::getLiveStockCountLocation($em, $this->location, true);
        $this->data[ReportLabel::ANIMALS] = $this->retrieveLiveStockData();
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }


    /**
     * @return string
     */
    private function parseFilename()
    {
        return self::FILE_NAME_REPORT_TYPE.'_'.$this->location->getUbn().'_'.$this->client->getLastName();
    }

    /**
     * @return string
     */
    private function parseNameAddressString()
    {
        $address = $this->location->getAddress();
        $streetNameAndNumber = $address->getFullStreetNameAndNumber();
        $streetNameAndNumber = $streetNameAndNumber != null ? $streetNameAndNumber.', ' : '';
        return $this->location->getUbn().', '.$this->client->getFullName().', '.$streetNameAndNumber.$address->getPostalCode().', '.$address->getCity();
    }


    /**
     * @return array
     */
    private function retrieveLiveStockData()
    {
        $sql = "SELECT DISTINCT CONCAT(a.uln_country_code, a.uln_number) as a_uln, CONCAT(a.pedigree_country_code, a.pedigree_number) as a_stn,
                  CONCAT(m.uln_country_code, m.uln_number) as m_uln, CONCAT(m.pedigree_country_code, m.pedigree_number) as m_stn,
                  CONCAT(f.uln_country_code, f.uln_number) as f_uln, CONCAT(f.pedigree_country_code, f.pedigree_number) as f_stn,
                  a.gender,
                  a.animal_order_number as a_animal_order_number, f.animal_order_number as f_animal_order_number, m.animal_order_number as m_animal_order_number,
                  DATE(a.date_of_birth) as a_date_of_birth, DATE(m.date_of_birth) as m_date_of_birth, DATE(f.date_of_birth) as f_date_of_birth,
                  a.breed_code as a_breed_code, m.breed_code as m_breed_code, f.breed_code as f_breed_code,
                  a.scrapie_genotype as a_scrapie_genotype, m.scrapie_genotype as m_scrapie_genotype, f.scrapie_genotype as f_scrapie_genotype,
                  a.breed_code as a_breed_code, m.breed_code as m_breed_code, f.breed_code as f_breed_code,
                  ac.dutch_breed_status as a_dutch_breed_status, mc.dutch_breed_status as m_dutch_breed_status, fc.dutch_breed_status as f_dutch_breed_status,
                  ac.n_ling as a_n_ling, mc.n_ling as m_n_ling, fc.n_ling as f_n_ling,
                  a.predicate as a_predicate_value, m.predicate as m_predicate_value, f.predicate as f_predicate_value,
                  a.predicate_score as a_predicate_score, m.predicate_score as m_predicate_score, f.predicate_score as f_predicate_score,
                  ac.muscularity as a_muscularity, mc.muscularity as m_muscularity, fc.muscularity as f_muscularity,
                  ac.general_appearance as a_general_appearance, mc.general_appearance as m_general_appearance, fc.general_appearance as f_general_appearance,
                  ac.production as a_production, mc.production as m_production, fc.production as f_production,
                  ac.breed_value_litter_size as a_breed_value_litter_size, mc.breed_value_litter_size as m_breed_value_litter_size, fc.breed_value_litter_size as f_breed_value_litter_size,
                  ac.breed_value_growth as a_breed_value_growth, mc.breed_value_growth as m_breed_value_growth, fc.breed_value_growth as f_breed_value_growth,
                  ac.breed_value_muscle_thickness as a_breed_value_muscle_thickness, mc.breed_value_muscle_thickness as m_breed_value_muscle_thickness, fc.breed_value_muscle_thickness as f_breed_value_muscle_thickness,
                  ac.breed_value_fat as a_breed_value_fat, mc.breed_value_fat as m_breed_value_fat, fc.breed_value_fat as f_breed_value_fat,
                  ac.lamb_meat_index as a_lamb_meat_index, mc.lamb_meat_index as m_lamb_meat_index, fc.lamb_meat_index as f_lamb_meat_index
                FROM animal a
                  LEFT JOIN animal m ON a.parent_mother_id = m.id
                  LEFT JOIN animal f ON a.parent_father_id = f.id
                  LEFT JOIN animal_cache ac ON a.id = ac.animal_id
                  LEFT JOIN animal_cache mc ON m.id = mc.animal_id
                  LEFT JOIN animal_cache fc ON f.id = fc.animal_id
                WHERE a.is_alive = true AND a.location_id = ".$this->location->getId()."
                ORDER BY a.animal_order_number ASC"
        ;
        $results = $this->em->getConnection()->query($sql)->fetchAll();

        $keys = array_keys($results);
        foreach ($keys as $key) {
            $results[$key]['a_uln_without_order_number'] = StringUtil::getUlnWithoutOrderNumber($results[$key]['a_uln'], $results[$key]['a_animal_order_number']);
            $results[$key]['f_uln_without_order_number'] = StringUtil::getUlnWithoutOrderNumber($results[$key]['f_uln'], $results[$key]['f_animal_order_number']);
            $results[$key]['m_uln_without_order_number'] = StringUtil::getUlnWithoutOrderNumber($results[$key]['m_uln'], $results[$key]['m_animal_order_number']);

            $results[$key]['gender'] = $this->getGenderLetter($results[$key]['gender']);

            $results[$key]['a_date_of_birth'] = TimeUtil::flipDateStringOrder($results[$key]['a_date_of_birth']);
            $results[$key]['f_date_of_birth'] = TimeUtil::flipDateStringOrder($results[$key]['f_date_of_birth']);
            $results[$key]['m_date_of_birth'] = TimeUtil::flipDateStringOrder($results[$key]['m_date_of_birth']);

            $results[$key]['a_n_ling'] = str_replace('-ling', '', $results[$key]['a_n_ling']);
            $results[$key]['f_n_ling'] = str_replace('-ling', '', $results[$key]['f_n_ling']);
            $results[$key]['m_n_ling'] = str_replace('-ling', '', $results[$key]['m_n_ling']);

            if(self::SHOW_PREDICATE_IN_REPORT) {
                $results[$key]['a_predicate'] = DisplayUtil::parsePredicateString($results[$key]['a_predicate_value'], $results[$key]['a_predicate_score']);
                $results[$key]['f_predicate'] = DisplayUtil::parsePredicateString($results[$key]['f_predicate_value'], $results[$key]['f_predicate_score']);
                $results[$key]['m_predicate'] = DisplayUtil::parsePredicateString($results[$key]['m_predicate_value'], $results[$key]['m_predicate_score']);
            } else {
                $results[$key]['a_predicate'] = null;
                $results[$key]['f_predicate'] = null;
                $results[$key]['m_predicate'] = null;
            }
        }
        
        return $results;
    }


    /**
     * @param string $genderEnglish
     * @return string
     */
    private function getGenderLetter($genderEnglish)
    {
        /* variables translated to Dutch */
        if($genderEnglish == 'Ram' || $genderEnglish == GenderType::MALE || $genderEnglish == GenderType::M) {
            return self::RAM_LETTER;
        } elseif ($genderEnglish == 'Ewe' || $genderEnglish == GenderType::FEMALE || $genderEnglish == GenderType::V) {
            return self::EWE_LETTER;
        } else {
            return self::NEUTER_STRING;
        }
    }
}