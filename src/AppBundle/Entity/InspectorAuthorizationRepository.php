<?php

namespace AppBundle\Entity;

use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Enumerator\InspectorMeasurementType;
use AppBundle\Util\ArrayUtil;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;

/**
 * Class InspectorAuthorizationRepository
 * @package AppBundle\Entity
 */
class InspectorAuthorizationRepository extends PersonRepository {

    const NON_NSFO_INSPECTOR_NAME = 'Niet NSFO';

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

        $pedigreeRegisterId = null;
        if($result) {
            $pedigreeRegisterId = ArrayUtil::get('pedigree_register_id', $result);
        }

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
            if(ctype_digit($pedigreeRegistersId) || is_int($pedigreeRegistersId)) {
                $filterMiddle = $filterMiddle.'pedigree_register_id = '.$pedigreeRegistersId.' OR ';
            }
        }

        $inspectors = [];
        if($filterMiddle != '') {
            $filterMiddle = rtrim($filterMiddle, ' OR ');
            $filter = $filterStart.$filterMiddle.$filterEnd;

            $sql = "SELECT p.person_id, p.first_name, p.last_name
                FROM inspector_authorization a
                  INNER JOIN person p ON p.id = a.inspector_id
                WHERE measurement_type = '".$measurementType."' ".$filter."
                GROUP BY first_name, last_name, person_id
                UNION
                SELECT p.person_id, p.first_name, p.last_name
                FROM inspector i
                  INNER JOIN person p ON p.id = i.id
                WHERE p.last_name = '".self::NON_NSFO_INSPECTOR_NAME."'
                ORDER BY first_name, last_name";
            $inspectors = $this->getConnection()->query($sql)->fetchAll();

        } else {
            $sql = "SELECT p.person_id, p.first_name, p.last_name
                FROM inspector i
                  INNER JOIN person p ON p.id = i.id
                WHERE p.last_name = '".self::NON_NSFO_INSPECTOR_NAME."'
                ORDER BY first_name, last_name";
            $inspectors = $this->getConnection()->query($sql)->fetchAll();
        }

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


    /**
     * @return array
     * @throws \Exception
     */
    public function getInspectorAuthorizationsOfNsfoInspectorsByInspectorId(): array
    {
        $qb = $this->getManager()->createQueryBuilder();

        $queryBuilder =
            $qb
                ->select('a')
                ->from(InspectorAuthorization::class, 'a')
                ->innerJoin('a.inspector', 'i', Join::WITH, $qb->expr()->eq('a.inspector', 'i.id'))
                ->innerJoin('a.pedigreeRegister', 'r', Join::WITH, $qb->expr()->eq('a.pedigreeRegister', 'r.id'))
                ->where($qb->expr()->eq('i.isAuthorizedNsfoInspector', 'true'));

        $query = $queryBuilder->getQuery();
        $query->setFetchMode(Inspector::class, 'i', ClassMetadata::FETCH_EAGER);
        $query->setFetchMode(PedigreeRegister::class, 'r', ClassMetadata::FETCH_EAGER);
        $result = $query->getResult();

        if (empty($result)) {
            return [];
        }

        $mappedResult = [];

        /** @var InspectorAuthorization $inspectorAuthorization */
        foreach ($result as $inspectorAuthorization) {
            if (!$inspectorAuthorization->getInspector() || !$inspectorAuthorization->getPedigreeRegister()) {
                throw new \Exception('InspectorAuthorization is missing inspector or pedigreeRegister');
            }

            $inspectorId = $inspectorAuthorization->getInspector()->getId();
            $mappedResult[$inspectorId][$inspectorAuthorization->getId()] = $inspectorAuthorization;
        }

        return $mappedResult;
    }


    /**
     * @param InspectorAuthorization[] $inspectorAuthorizations
     * @param string $inspectorMeasurementType
     * @param int $pedigreeRegisterId
     * @return bool
     */
    public static function hasInspectorAuthorization(array $inspectorAuthorizations, $inspectorMeasurementType, $pedigreeRegisterId)
    {
        if (empty($inspectorMeasurementType) || empty($pedigreeRegisterId)) {
            return false;
        }

        foreach ($inspectorAuthorizations as $inspectorAuthorization) {
            if ($inspectorAuthorization->getMeasurementType() === $inspectorMeasurementType
             && $inspectorAuthorization->getPedigreeRegister()
             && $inspectorAuthorization->getPedigreeRegister()->getId() === $pedigreeRegisterId) {
                return true;
            }
        }
        return false;
    }
}