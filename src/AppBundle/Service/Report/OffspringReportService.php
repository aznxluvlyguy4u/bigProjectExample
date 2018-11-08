<?php


namespace AppBundle\Service\Report;


use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\FileType;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Response;

class OffspringReportService extends ReportServiceWithBreedValuesBase
{
    const TITLE = 'offspring_report';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;

    const CONCAT_BREED_VALUE_AND_ACCURACY_BY_DEFAULT = false;

    /**
     * @param Person $person
     * @param Location $location
     * @param ArrayCollection $content
     * @param $concatValueAndAccuracy
     * @param string $locale
     * @return \AppBundle\Component\HttpFoundation\JsonResponse
     */
    function getReport(Person $person, ?Location $location, ArrayCollection $content, $concatValueAndAccuracy, $locale)
    {
        try {
            $this->content = $content;
            $parentsArray = $this->content->get(JsonInputConstant::PARENTS);

            if(AdminValidator::isAdmin($person, AccessLevelType::ADMIN)) {
                $animalIds = $this->getAnyAnimalIdsFromBody($parentsArray);
                $location = null;

            } else {
                if (!$location || !$location->getId()) {
                    throw new \Exception('Location is missing', Response::HTTP_BAD_REQUEST);
                }
                $animalIds = $this->getCurrentAndHistoricAnimalIdsFromBody($parentsArray, $location);
            }

            $this->concatValueAndAccuracy = $concatValueAndAccuracy;

            $this->setLocale($locale);

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