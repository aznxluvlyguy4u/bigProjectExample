<?php


namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Util\RequestUtil;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;

class BreedValuesOverviewReportService extends ReportServiceWithBreedValuesBase
{
    const TITLE = 'Fokwaardenoverzicht alle huidige dieren';
    const FILENAME_PART_1 = 'breed values overview';
    const FILENAME_PART_2 = 'all current animals';
    const FILENAME = 'breed values overview all current animals';
    const KEYWORDS = "nsfo fokwaarden dieren overzicht";
    const DESCRIPTION = "Fokwaardenoverzicht van alle dieren op huidige stallijsten met minstens 1 fokwaarde";
    const FOLDER_NAME = '/pedigree_register_reports/';


    /**
     * @param Request $request
     * @param $user
     * @return JsonResponse
     */
    public function request(Request $request, $user)
    {
        if(!AdminValidator::isAdmin($user, AccessLevelType::SUPER_ADMIN)) { //validate if user is at least a SUPER_ADMIN
            return AdminValidator::getStandardErrorResponse();
        }

        $fileType = $request->query->get(QueryParameter::FILE_TYPE_QUERY, FileType::XLS);
        $uploadToS3 = RequestUtil::getBooleanQuery($request,QueryParameter::S3_UPLOAD, !$this->outputReportsToCacheFolderForLocalTesting);
        $concatBreedValuesAndAccuracies = RequestUtil::getBooleanQuery($request,QueryParameter::CONCAT_VALUE_AND_ACCURACY, false);
        $includeAllLiveStockAnimals = RequestUtil::getBooleanQuery($request,QueryParameter::INCLUDE_ALL_LIVESTOCK_ANIMALS, false);

        $this->excelService
            ->setKeywords(self::KEYWORDS)
            ->setDescription(self::DESCRIPTION)
        ;

        $this->filename = $this->translate(self::FILENAME_PART_1).'_'.$this->translate(self::FILENAME_PART_2);
        $this->folderName = self::FOLDER_NAME;

//        $this->setLocaleFromQueryParameter($locale);

        return $this->generate($fileType, $concatBreedValuesAndAccuracies, $includeAllLiveStockAnimals, $uploadToS3);
    }


    /**
     * @param string $fileType
     * @param boolean $concatBreedValuesAndAccuracies
     * @param boolean $includeAllLiveStockAnimals
     * @param boolean $uploadToS3
     * @param boolean $ignoreHiddenBreedValueTypes
     * @return JsonResponse
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function generate($fileType, $concatBreedValuesAndAccuracies, $includeAllLiveStockAnimals, $uploadToS3,
                             $ignoreHiddenBreedValueTypes = false)
    {
        return $this->generateFile($this->getFilenameWithoutExtension(), $this->getData($concatBreedValuesAndAccuracies, $includeAllLiveStockAnimals, $ignoreHiddenBreedValueTypes), self::TITLE, $fileType, $uploadToS3);
    }


    /**
     * @param bool $concatBreedValuesAndAccuracies
     * @param bool $includeAnimalsWithoutAnyBreedValues
     * @param bool $ignoreHiddenBreedValueTypes
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getData($concatBreedValuesAndAccuracies = true, $includeAnimalsWithoutAnyBreedValues = false, $ignoreHiddenBreedValueTypes = false)
    {
        return $this->conn->query(
            $this->breedValuesReportQueryGenerator->getFullBreedValuesReportOverviewQuery(
                $concatBreedValuesAndAccuracies,
                $includeAnimalsWithoutAnyBreedValues,
                $ignoreHiddenBreedValueTypes
            )
        )->fetchAll();
    }
}