<?php

namespace AppBundle\Output;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareBirthResponse;
use AppBundle\Entity\DeclareNsfoBase;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Litter;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Entity\TailLength;
use AppBundle\Entity\Weight;
use Doctrine\Common\Collections\Collection;

class DeclareBirthResponseOutput extends Output
{

    /**
     * @param Litter $litter
     * @param Collection $declarations
     * @return array
     */
    public static function createBirth($litter, $declarations)
    {
        $res = array();

        // GENERAL
        $res["log_date"] = Utils::fillNull($litter->getLogDate());
        $res["date_of_birth"] = Utils::fillNull($litter->getLitterDate());
        $res["stillborn_count"] = Utils::fillNull($litter->getStillbornCount());
        $res["born_alive_count"] = Utils::fillNull($litter->getBornAliveCount());
        $res["is_abortion"] = Utils::fillNull($litter->getAbortion());
        $res["is_pseudo_pregnancy"] = Utils::fillNull($litter->getPseudoPregnancy());
        $res["status"] = Utils::fillNull($litter->getStatus());
        $res["request_state"] = Utils::fillNull($litter->getRequestState());

        // MOTHER
        $mother = $litter->getAnimalMother();
        $res["mother_uln_country_code"] = $mother->getUlnCountryCode();
        $res["mother_uln_number"] = $mother->getUlnNumber();

        // FATHER
        $father = $litter->getAnimalFather();
        if($father != null) {
            $res["father_uln_country_code"] = $father->getUlnCountryCode();
            $res["father_uln_number"] = $father->getUlnNumber();
        } else {
            $res["father_uln_country_code"] = "";
            $res["father_uln_number"] = "";
        }

        // CHILDREN
        $children = $litter->getChildren();
        $childrenTemp = array();
        if(sizeof($children) > 0) {
            foreach ($children as $child) {
                $childTemp = array();

                /** @var Animal $child */
                $childTemp['is_alive'] = $child->getIsAlive();
                $childTemp['uln_country_code'] = $child->getUlnCountryCode();
                $childTemp['uln_number'] = $child->getUlnNumber();

                if ($child instanceof Ewe) {
                    $childTemp['gender'] = "FEMALE";
                }

                if ($child instanceof Ram) {
                    $childTemp['gender'] = "MALE";
                }

                if ($child instanceof Neuter) {
                    $childTemp['gender'] = "NEUTER";
                }

                $childTemp['birth_progress'] = $child->getBirthProgress();
                $childTemp['lambar'] = $child->getLambar();

                $surrogate = $child->getSurrogate();
                if ($surrogate != null) {
                    $childTemp["surrogate_uln_country_code"] = $surrogate->getUlnCountryCode();
                    $childTemp["surrogate_uln_number"] = $surrogate->getUlnNumber();
                } else {
                    $childTemp["surrogate_uln_country_code"] = "";
                    $childTemp["surrogate_uln_number"] = "";
                }

                $weights = $child->getWeightMeasurements();
                foreach ($weights as $weight) {
                    /** @var Weight $weight */
                    if ($weight->isIsBirthWeight()) {
                        $childTemp['birth_weight'] = $weight->getWeight();
                    }
                }


                /** @var TailLength $tailLength */
                if($child->getTailLengthMeasurements()->count() > 0) {
                    $tailLength = $child->getTailLengthMeasurements()->first();
                    $childTemp['tail_length'] = $tailLength->getLength();
                }

                $childTemp['is_successful'] = true;

                $childrenTemp[] = $childTemp;
            }
        }

        /** @var DeclareBirth $declaration */
        if(sizeof($declarations) > 0) {
            foreach ($declarations as $declaration) {

                /** @var DeclareBirthResponse $response */
                $response = $declaration->getResponses()->last();
                if($response != null) {
                    $failedChild = array();
                    $failedChild['uln_country_code'] = $declaration->getUlnCountryCode();
                    $failedChild['uln_number'] = $declaration->getUlnNumber();
                    $failedChild['gender'] = $declaration->getGender();
                    $failedChild['birth_weight'] = $declaration->getBirthWeight();
                    $failedChild['tail_length'] = $declaration->getBirthTailLength();
                    $failedChild['birth_progress'] = $declaration->getBirthType();
                    $failedChild['lambar'] = $declaration->getHasLambar();
                    $failedChild['surrogate_uln_country_code'] = $declaration->getUlnCountryCodeSurrogate();
                    $failedChild['surrogate_uln_number'] = $declaration->getUlnSurrogate();

                    $failedChild['is_successful'] = ($response->getSuccessIndicator() == 'J');
                    $failedChild['error_kind'] = $response->getErrorKindIndicator();
                    $failedChild['error_code'] = $response->getErrorCode();
                    $failedChild['error_message'] = $response->getErrorMessage();

                    $childrenTemp[] = $failedChild;
                }
            }
        }

        $res["children"] = $childrenTemp;

        return $res;
    }

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