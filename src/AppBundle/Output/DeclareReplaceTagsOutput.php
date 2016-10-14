<?php

namespace AppBundle\Output;


use Symfony\Component\Validator\Constraints\DateTime;

class DeclareReplaceTagsOutput
{

    /**
     * @param $tagReplaces
     * @return array
     */
    public static function createHistoryArray($tagReplaces)
    {
        $results = [];
        foreach ($tagReplaces as $tagReplace) {
            $result = [
                "request_id" => $tagReplace['request_id'],
                "log_date" => new \DateTime($tagReplace['replace_date']),
                "animal" => [
                    "uln_country_code" => $tagReplace['uln_country_code_to_replace'],
                    "uln_number" => $tagReplace['uln_number_to_replace'],
                    "work_number" => $tagReplace['animal_order_number_to_replace']
                ],
                "tag" => [
                    "uln_country_code" => $tagReplace['uln_country_code_replacement'],
                    "uln_number" => $tagReplace['uln_number_replacement'],
                    "work_number" => $tagReplace['animal_order_number_replacement']
                ],
                "request_state" => $tagReplace['request_state'],
                "message_number" => $tagReplace['message_number'],
            ];

            $results[] = $result;
        }

        return $results;
    }
}