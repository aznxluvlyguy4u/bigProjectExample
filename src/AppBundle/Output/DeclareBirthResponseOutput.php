<?php

namespace AppBundle\Output;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareBirthResponse;
use Doctrine\Common\Collections\Collection;

class DeclareBirthResponseOutput extends Output
{
    /**
     * @param Collection $declarations
     * @return array
     */
    public static function createHistoryResponse($declarations)
    {
        $res = array();

        foreach ($declarations as $declaration) {
            $res[] = array(
                "log_date" => Utils::fillNull($declaration['log_date']),
                "date_of_birth" => Utils::fillNull($declaration['date_of_birth']),
                "mother_uln_country_code" => Utils::fillNull($declaration['mother_uln_country_code']),
                "mother_uln_number" => Utils::fillNull($declaration['mother_uln_number']),
                "father_uln_country_code" => Utils::fillNull($declaration['father_uln_country_code']),
                "father_uln_number" => Utils::fillNull($declaration['father_uln_number']),
                "stillborn_count" => Utils::fillNull($declaration['stillborn_count']),
                "born_alive_count" => Utils::fillNull($declaration['born_alive_count']),
                "is_abortion" => Utils::fillNull($declaration['is_abortion']),
                "is_pseudo_pregnancy" => Utils::fillNull($declaration['is_pseudo_pregnancy']),
                "status" => Utils::fillNull($declaration['status']),
                "request_state" => Utils::fillNull($declaration['request_state']),
                "message_number" => Utils::fillNull($declaration['message_id'])
            );
        }

        return $res;
    }

    /**
     * @param Collection $declarations
     * @return array
     */
    public static function createErrorResponse($declarations)
    {
        $res = array();

        foreach ($declarations as $declaration) {
            $res[] = array(
                "log_date" => Utils::fillNull($declaration['log_date']),
                "date_of_birth" => Utils::fillNull($declaration['date_of_birth']),
                "mother_uln_country_code" => Utils::fillNull($declaration['mother_uln_country_code']),
                "mother_uln_number" => Utils::fillNull($declaration['mother_uln_number']),
                "father_uln_country_code" => Utils::fillNull($declaration['father_uln_country_code']),
                "father_uln_number" => Utils::fillNull($declaration['father_uln_number']),
                "stillborn_count" => Utils::fillNull($declaration['stillborn_count']),
                "born_alive_count" => Utils::fillNull($declaration['born_alive_count']),
                "is_abortion" => Utils::fillNull($declaration['is_abortion']),
                "is_pseudo_pregnancy" => Utils::fillNull($declaration['is_pseudo_pregnancy']),
                "status" => Utils::fillNull($declaration['status']),
                "request_state" => Utils::fillNull($declaration['request_state']),
                "is_removed_by_user" => Utils::fillNull($declaration['is_removed_by_user']),
                "message_number" => Utils::fillNull($declaration['message_id'])
            );
        }

        return $res;
    }


}