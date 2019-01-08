<?php

namespace AppBundle\Service\Report;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Option\BirthListReportOptions;
use AppBundle\Entity\Client;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

class BirthListReportService extends ReportServiceBase
{
    const TITLE = 'birth_list_report';
    const TWIG_FILE = 'Report/birth_list.html.twig';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;

    const FILE_NAME_REPORT_TYPE = 'BIRTH_LIST';


    /**
     * @param Person $person
     * @param Location $location
     * @param BirthListReportOptions $options
     * @return JsonResponse
     */
    public function getReport(Person $person, Location $location, BirthListReportOptions $options)
    {
        self::validateUser($person, $location);
        $this->setLocale($options->getLanguage());

        $this->filename = $this->trans(self::FILE_NAME_REPORT_TYPE).'_'.$location->getUbn();
        $this->folderName = self::FOLDER_NAME;

        return $this->getPdfReport($location, $options);
    }


    /**
     * @param Person $person
     * @param Location $location
     */
    public static function validateUser(Person $person, Location $location)
    {
        if (AdminValidator::isAdmin($person, AccessLevelType::ADMIN)) {
            return;
        }

        /** Client */
        if ($person instanceof Client) {
            $companyId = $location->getCompany() ? $location->getCompany()->getId() : null;

            if (!$companyId) {
                throw new PreconditionFailedHttpException('Location has no company');
            }

            foreach ($person->getCompanies() as $company) {
                if ($company->getId() === $companyId) {
                    return;
                }
            }

            $companyIdOfOwner = $person->getEmployer() ? $person->getEmployer()->getId() : null;
            if ($companyIdOfOwner === $companyId) {
                return;
            }
        }

        throw AdminValidator::standardException();
    }


    /**
     * @param Location $location
     * @param BirthListReportOptions $options
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    private function getPdfReport(Location $location, BirthListReportOptions $options)
    {
        $data = $this->getReportData($location, $options);

        dump($data);die;

        return $this->getPdfReportBase(self::TWIG_FILE,
            $data,
            true,
            [],
            null,
            true
        );
    }


    /**
     * @param Location $location
     * @param BirthListReportOptions $options
     * @return array
     */
    private function getReportData(Location $location, BirthListReportOptions $options): array
    {
        return [
            'TEST',
            'TEST',
            'TEST',
            'TEST',
            'TEST',
            'TEST',
            'TEST',
        ];
    }
}