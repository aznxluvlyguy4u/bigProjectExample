<?php

namespace AppBundle\Service\Report;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\Location;
use AppBundle\Entity\MedicationSelection;
use AppBundle\Entity\Treatment;
use AppBundle\Entity\TreatmentMedication;
use DateTime;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CombiFormTransportDocumentService extends ReportServiceBase
{

    const TITLE = 'combi_form_vki_transport_document';
    const FILENAME = self::TITLE;
    const TWIG_FILE = 'Report/combi_form_transport_document.html.twig';
    const MAX_AGE = 30;
    const OTHER_ANIMALS_PER_PAGE = 40;
    const ANIMAL_LIMIT = 20;
    const EXPIRED_WAITING_TIME_ANIMAL_LIMIT = 10;

    private $result = [];

    /**
     * @inheritDoc
     * @throws Exception
     */
    function getReport($transportDate, $exportUBN, ?Location $location = null)
    {
        $PDFData = $this->getPDFData($transportDate, $exportUBN, $location);
        $this->filename = $this->translate(self::FILENAME);

        if ($PDFData instanceof JsonResponse) {
            return $PDFData;
        }

        return $this->getPdfReportBase(self::TWIG_FILE, $PDFData, false);
    }

    /**
     * @param $transportDate
     * @param $exportUbn
     * @param Location|null $location
     * @return JsonResponse|array
     * @throws Exception
     */
    private function getPDFData($transportDate, $exportUbn, ?Location $location)
    {
        /** @var Location $exportLocation */
        $exportLocation = $this->em->getRepository(Location::class)
            ->findOneBy(['ubn' => $exportUbn]);

        $this->result = [
            'animals' => [],
            'can_be_exported' => false,
            'transport_date' => $transportDate,
            ReportLabel::IMAGES_DIRECTORY => $this->getImagesDirectory()
        ];

        $currentDateTime =  new DateTime();

        $transportDateObject = new DateTime($transportDate);

        $transportDateSickTime = clone $transportDateObject;
        $transportDateWaitingTimeExpired = clone $transportDateObject;

        $transportDateSickTime->modify('-35 days');
        $transportDateWaitingTimeExpired->modify('-7 days');

        $this->result['waitingTimeAnswer'] = 'Nee/Ja';
        $this->result['sickTimeAnswer'] = 'Nee/Ja';
        $this->result['waitingTimeExpiredAnswer'] = 'Nee/Ja';
        $this->result['waiting_time_expired_animals'] = [];
        $this->result['total_animal_pages'] = 0;
        $this->result['total_waiting_time_expired_animal_pages'] = 0;

        $animalsForTransportOptions = $this->em->getRepository(DeclareDepart::class)
            ->getDepartAnimals($transportDateObject, $exportUbn, $location, false);

        /** @var Animal $animal */
        foreach ($animalsForTransportOptions as $animal) {

            $diff = date_diff($animal->getDateOfBirth(), $currentDateTime);

            if ($diff->days < self::MAX_AGE) {
                if ($animal->getLocationOfBirth() === $location) {
                    $this->result['can_be_exported'] = true;
                }
            }

            $iterator = $animal->getTreatments()->getIterator();

            $iterator->uasort(function($first, $second) {
                return $first->getLastDate() > $second->getLastDate();
            });

            $lastTreatmentEndDate = '';

            if (isset($iterator->getArrayCopy()[0])) {
                $lastTreatmentEndDate = $iterator->getArrayCopy()[0]->getLastDate()->format('d-m-Y');
            }

            /** @var Treatment $treatment */
            foreach ($animal->getTreatments() as $treatment) {
                if ($treatment->getStartDate() >= $transportDateSickTime && $treatment->getLastDate() <= $transportDateSickTime) {
                    $this->result['sickTimeAnswer'] = 'Ja';
                }

                /** @var MedicationSelection $medicationSelection */
                foreach ($treatment->getMedicationSelections() as $medicationSelection) {
                    $lastDate = clone $treatment->getLastDate();

                    /** @var TreatmentMedication $treatmentMedication */
                    $treatmentMedication = $medicationSelection->getTreatmentMedication();

                    $lastDate->modify('+'.$treatmentMedication->getWaitingDays().' days');

                    if ($lastDate >= $transportDateWaitingTimeExpired && $treatment->getLastDate()->format('d-m-Y') == $lastTreatmentEndDate) {
                        $this->result['waitingTimeExpiredAnswer'] = 'Ja';
                        $this->result['waiting_time_expired_animals'][] = [
                            'uln'                   => $animal->getUln(),
                            'reg_nl'                => $treatmentMedication->getRegNl(),
                            'medication_name'       => $treatmentMedication->getName(),
                            'latest_treatment_date' => $lastTreatmentEndDate,
                            'waiting_term_end_date' => $medicationSelection->getWaitingTimeEnd()->format('d-m-Y')
                        ];
                    }

                    if ($medicationSelection->getWaitingTimeEnd() >= $transportDateObject) {
                        $this->result['waitingTimeAnswer'] = 'Ja';
                    }
                }
            }

            $this->result['animals'][] = $animal;
        }

        $totalAnimalCount = count($this->result['animals']);
        $totalWaitingTimeExpiredAnimals = count($this->result['waiting_time_expired_animals']);

        if ($totalAnimalCount === 0) {
            $errorMessage = $this->trans("NO ANIMALS FOUND FOR TRANSPORT DATE %transportDate% AND UBN OF ARRIVAL %ubnOfArrival%",
                [
                   '%transportDate%' => $transportDate,
                   '%ubnOfArrival%' => $exportUbn
                ]);
            throw new NotFoundHttpException($errorMessage, null, Response::HTTP_NOT_FOUND);
        }

        if ($totalAnimalCount >= self::ANIMAL_LIMIT) {
            $firstAnimals = array_slice($this->result['animals'], 0, self::ANIMAL_LIMIT);

            if ($totalAnimalCount === self::ANIMAL_LIMIT) {
                $totalAnimalCount = $totalAnimalCount+1;
            }

            $otherAnimals = array_slice($this->result['animals'], self::ANIMAL_LIMIT, $totalAnimalCount-self::ANIMAL_LIMIT);
            $this->result['total_other_animals'] = count($otherAnimals);
            $this->result['total_animal_pages'] = ceil(count($otherAnimals)/self::OTHER_ANIMALS_PER_PAGE);

            $this->result['paginated_animals'] = array_chunk($otherAnimals, self::OTHER_ANIMALS_PER_PAGE, true);

            $chunkedAnimals = array_chunk($firstAnimals, self::ANIMAL_LIMIT/2, true);
        }
        else {
            $this->result['total_other_animals'] = 0;

            if ($totalAnimalCount === 1) {
                $size = $totalAnimalCount;
            } else {
                $size = (int) ceil($totalAnimalCount/2);
            }

            $chunkedAnimals = array_chunk($this->result['animals'], $size, true);
        }

        if ($totalWaitingTimeExpiredAnimals >= self::EXPIRED_WAITING_TIME_ANIMAL_LIMIT) {
            $this->result['first_animals_waiting_time_expired'] = array_slice(
                $this->result['waiting_time_expired_animals'],
                0,
                self::EXPIRED_WAITING_TIME_ANIMAL_LIMIT
            );

            if ($totalWaitingTimeExpiredAnimals === self::EXPIRED_WAITING_TIME_ANIMAL_LIMIT) {
                $totalWaitingTimeExpiredAnimals = $totalWaitingTimeExpiredAnimals+1;
            }

            $otherWaitingTimeExpireAnimals = array_slice(
                $this->result['waiting_time_expired_animals'],
                self::EXPIRED_WAITING_TIME_ANIMAL_LIMIT,
                $totalWaitingTimeExpiredAnimals-self::EXPIRED_WAITING_TIME_ANIMAL_LIMIT
            );

            $this->result['total_paginated_waiting_time_expired_animals'] = count($otherWaitingTimeExpireAnimals);
            $this->result['total_waiting_time_expired_animal_pages'] = ceil(count($otherWaitingTimeExpireAnimals)/self::OTHER_ANIMALS_PER_PAGE);

            $this->result['paginated_waiting_time_expired_animals'] = array_chunk($otherWaitingTimeExpireAnimals, self::OTHER_ANIMALS_PER_PAGE, true);
        }
        else {
            $this->result['total_paginated_waiting_time_expired_animals'] = 0;
            $this->result['first_animals_waiting_time_expired'] = $this->result['waiting_time_expired_animals'];
        }

        $this->result['animals_left'] = $chunkedAnimals[0];
        if (count($chunkedAnimals) > 1) {
            $this->result['animals_right'] = $chunkedAnimals[1];
        } else {
            $this->result['animals_right'] = [];
        }

        $this->result['location'] = $location;
        $this->result['export_location'] = $exportLocation;

        return $this->result;
    }
}
