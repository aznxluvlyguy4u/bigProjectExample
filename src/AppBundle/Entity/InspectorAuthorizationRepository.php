<?php

namespace AppBundle\Entity;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Enumerator\InspectorMeasurementType;
use AppBundle\Util\TimeUtil;

/**
 * Class InspectorAuthorizationRepository
 * @package AppBundle\Entity
 */
class InspectorAuthorizationRepository extends PersonRepository {


    /**
     * @param string $ulnString
     * @return array
     */
    public function getAuthorizedInspectorIdsExteriorByUln($ulnString)
    {
        $output = $this->getAuthorizedInspectorsExteriorByUln($ulnString);

        $result = [];
        foreach ($output as $item) {
            $personId = $item[JsonInputConstant::PERSON_ID];
            $result[$personId] = $personId;
        }
        return $result;
    }


    /**
     * @param string $ulnString
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getAuthorizedInspectorsExteriorByUln($ulnString)
    {
        $ulnParts = Utils::getUlnFromString($ulnString);
        if($ulnParts == null) {
            return [];
        }
        $ulnCountryCode = $ulnParts[Constant::ULN_COUNTRY_CODE_NAMESPACE];
        $ulnNumber = $ulnParts[Constant::ULN_NUMBER_NAMESPACE];

        $sql = "SELECT pedigree_register_id FROM animal WHERE uln_country_code = '".$ulnCountryCode."' AND uln_number = '".$ulnNumber."'";
        $result = $this->getConnection()->query($sql)->fetch();
        $pedigreeRegisterId = Utils::getNullCheckedArrayValue('pedigree_register_id', $result);

        return $this->getAuthorizedInspectors(InspectorMeasurementType::EXTERIOR, [$pedigreeRegisterId]);
    }
    
    /**
     * @param $measurementType
     * @param array $pedigreeRegistersIds
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getAuthorizedInspectors($measurementType, array $pedigreeRegistersIds = [])
    {
        $filterStart = '';
        $filterEnd = '';
        $filterMiddle = '';
        if(count($pedigreeRegistersIds) > 0) {
            $filterStart = ' AND (';
            $filterEnd = ')';
        }

        foreach ($pedigreeRegistersIds as $pedigreeRegistersId) {
            $filterMiddle = $filterMiddle.'pedigree_register_id = '.$pedigreeRegistersId.' OR ';
        }
        $filterMiddle = rtrim($filterMiddle, ' OR ');
        $filter = $filterStart.$filterMiddle.$filterEnd;

        $sql = "SELECT p.person_id, p.first_name, p.last_name
                FROM inspector_authorization a
                  INNER JOIN person p ON p.id = a.inspector_id
                WHERE measurement_type = '".$measurementType."' ".$filter."
                GROUP BY first_name, last_name, person_id";
        $inspectors = $this->getConnection()->query($sql)->fetchAll();

        $result = [];
        foreach ($inspectors as $inspector) {
            $result[] = [
                JsonInputConstant::PERSON_ID => $inspector['person_id'],  
                JsonInputConstant::FIRST_NAME => $inspector['first_name'],  
                JsonInputConstant::LAST_NAME => $inspector['last_name'],  
            ];
        }

        return $result;
    }

}