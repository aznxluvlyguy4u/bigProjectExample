<?php


namespace AppBundle\Service\Report;


use AppBundle\Enumerator\FileType;
use AppBundle\Util\ResultUtil;

class AnimalsOverviewReportService extends ReportServiceWithBreedValuesBase
{
    const TITLE = 'animals_overview_report';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;

    const CONCAT_BREED_VALUE_AND_ACCURACY_BY_DEFAULT = false;
    const MAX_CURRENT_ANIMAL_AGE_IN_YEARS = 15;

    /**
     * @inheritDoc
     */
    function getReport(
        $concatValueAndAccuracy,
        \DateTime $pedigreeActiveEndDateLimit,
        \DateTime $activeUbnReferenceDate,
        $locale
    )
    {
        try {

            $this->concatValueAndAccuracy = $concatValueAndAccuracy;
            $activeUbnReferenceDateString = $activeUbnReferenceDate->format('Y-m-d');

            $this->setLocale($locale);

            $sql = $this->breedValuesReportQueryGenerator->createAnimalsOverviewReportQuery(
                $this->concatValueAndAccuracy,
                true,
                true,
                self::MAX_CURRENT_ANIMAL_AGE_IN_YEARS,
                $activeUbnReferenceDateString,
                $pedigreeActiveEndDateLimit
            );
            $this->filename = $this->translate(self::FILENAME)
                .'__'.$this->translate('reference date').'_'.$activeUbnReferenceDateString
                .'__'.$this->translate('generated on');
            $this->extension = FileType::CSV;

            return $this->generateCsvFileBySqlQuery(
                $this->getFilename(),
                $sql,
                $this->breedValuesReportQueryGenerator->getAnimalsOverviewReportBooleanColumns()
            );

        } catch (\Exception $exception) {
            return ResultUtil::errorResult($exception->getMessage(), $exception->getCode());
        }
    }



}