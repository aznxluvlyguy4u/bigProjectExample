<?php


namespace AppBundle\Service\Report;


use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Client;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Util\ProcessUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OffspringReportService extends ReportServiceWithBreedValuesBase
{
    const TITLE = 'offspring_report';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;

    const CONCAT_BREED_VALUE_AND_ACCURACY_BY_DEFAULT = false;

    const ADMIN_PROCESS_TIME_LIMIT_IN_MINUTES = 3;

    /**
     * @param Person $person
     * @param Location $location
     * @param ArrayCollection $content
     * @param $concatValueAndAccuracy
     * @param string $locale
     * @return \AppBundle\Component\HttpFoundation\JsonResponse
     */
    function getReport(Person $person, Location $location, ArrayCollection $content, $concatValueAndAccuracy, $locale)
    {
        try {

            //$this->validateRequestBody($request);
            $this->content = $content;
            $parentsArray = $this->content->get(JsonInputConstant::PARENTS);

            if(AdminValidator::isAdmin($person, AccessLevelType::ADMIN)) {
                ProcessUtil::setTimeLimitInMinutes(self::ADMIN_PROCESS_TIME_LIMIT_IN_MINUTES);

                $animalIds = $this->getAnyAnimalIdsFromBody($parentsArray);
                $location = null;

            } else {
                if (!$location || !$location->getId()) {
                    throw new \Exception('Location is missing', Response::HTTP_BAD_REQUEST);
                }
                $animalIds = $this->getCurrentAndHistoricAnimalIdsFromBody($parentsArray, $location);
            }

            $this->concatValueAndAccuracy = $concatValueAndAccuracy;

            $this->setLocaleFromQueryParameter($locale);

            $sql = $this->breedValuesReportQueryGenerator->createOffspringReportQuery(
                $this->concatValueAndAccuracy,
                true,
                true,
                $animalIds
            );
            $this->filename = $this->translate(self::FILENAME)
                .'_'.$this->getFilenameParentPart($location, count($animalIds));
            $this->extension = FileType::CSV;

            return $this->generateCsvFileBySqlQuery(
                $this->getFilename(),
                $sql,
                $this->breedValuesReportQueryGenerator->getOffSpringReportBooleanColumns()
            );

        } catch (\Exception $exception) {
            return ResultUtil::errorResult($exception->getMessage(), $exception->getCode());
        }
    }


    /**
     * @param Request $request
     * @throws \Exception
     */
    private function validateRequestBody(Request $request)
    {
        $this->content = RequestUtil::getContentAsArray($request);
        $animalsArray = $this->content->get(JsonInputConstant::PARENTS);
        if (!is_array($animalsArray)) {
            throw new \Exception("'".JsonInputConstant::PARENTS."' key is missing in body", Response::HTTP_BAD_REQUEST);
        }

        if (count($animalsArray) === 0) {
            throw new \Exception("Empty input", Response::HTTP_BAD_REQUEST);
        }
    }


    /**
     * @param Location $location
     * @param $parentCount
     * @return string
     */
    private function getFilenameParentPart($location = null, $parentCount)
    {
        $name = '';
        if ($location) {
            $name .= 'UBN_'.$location->getUbn().'_';
        }

        if ($parentCount === 1) {
            $parentsArray = $this->content->get(JsonInputConstant::PARENTS);
            $parent = current($parentsArray);
            return $name.$parent[JsonInputConstant::ULN_COUNTRY_CODE].$parent[JsonInputConstant::ULN_NUMBER];
        }

        return $name . $parentCount.$this->translate('PARENTS');
    }
}