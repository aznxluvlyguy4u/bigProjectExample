<?php


namespace AppBundle\Service\DataFix;


use AppBundle\Util\SqlUtil;

class MissingUbnOfBirthFillerService extends DataFixServiceBase
{
    public function run()
    {
        $sql = "UPDATE animal SET ubn_of_birth = v.found_ubn_of_birth
                FROM (
                       SELECT
                         a.id                                           AS animal_id,
                         CONCAT(uln_country_code, uln_number)           AS uln,
                         CONCAT(pedigree_country_code, pedigree_number) AS stn,
                         substr(pedigree_number, 1, 5)                  AS breeder_number_in_stn,
                         lr.ubn                                         AS found_ubn_of_birth,
                         l.ubn                                          AS huidig_ubn
                       FROM animal a
                         INNER JOIN pedigree_register_registration r ON r.breeder_number = substr(pedigree_number, 1, 5)
                         INNER JOIN location lr ON lr.id = r.location_id
                         LEFT JOIN location l ON a.location_id = l.id
                       WHERE pedigree_number NOTNULL AND a.ubn_of_birth ISNULL
                     ) AS v(animal_id, uln, stn, breeder_number_in_stn, found_ubn_of_birth, current_ubn)
                WHERE animal.id = v.animal_id AND animal.ubn_of_birth ISNULL";
        $fillCountByPedigreeRegisterRegistrations = SqlUtil::updateWithCount($this->getConnection(), $sql);

        $sql = "UPDATE animal SET ubn_of_birth = v.found_ubn_of_birth
                FROM (
                       SELECT
                         a.id AS animal_id,
                         CONCAT(uln_country_code, uln_number) AS uln,
                         CONCAT(pedigree_country_code, pedigree_number) AS stn,
                         substr(pedigree_number, 1 , 5) AS fokkernummer_in_stn,
                         b.ubn_of_birth AS gevonden_fokkerubn,
                         l.ubn AS huidig_ubn
                       FROM animal a
                         INNER JOIN breeder_number b ON b.breeder_number = substr(pedigree_number, 1 , 5)
                         LEFT JOIN location l ON a.location_id = l.id
                       WHERE pedigree_number NOTNULL AND a.ubn_of_birth ISNULL
                     ) AS v(animal_id, uln, stn, breeder_number_in_stn, found_ubn_of_birth, current_ubn)
                WHERE animal.id = v.animal_id AND animal.ubn_of_birth ISNULL";
        $fillCountByOldData = SqlUtil::updateWithCount($this->getConnection(), $sql);

        $fillCount = $fillCountByPedigreeRegisterRegistrations + $fillCountByOldData;

        if ($fillCount > 0) {
            $this->getLogger()->notice(($fillCountByPedigreeRegisterRegistrations > 0 ? $fillCountByPedigreeRegisterRegistrations : 'No') . ' empty UbnOfBirths were filled by the PedigreeRegisterRegistrations');

            $this->getLogger()->notice(($fillCountByOldData > 0 ? $fillCountByOldData : 'No') . ' empty UbnOfBirths were filled by the old breeder_number data');
        }

        $this->getLogger()->notice(($fillCount > 0 ? $fillCount : 'No') . ' empty UbnOfBirths were filled in total');
    }
}