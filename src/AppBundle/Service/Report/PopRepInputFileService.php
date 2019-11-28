<?php


namespace AppBundle\Service\Report;


use AppBundle\Constant\TranslationKey;
use AppBundle\Entity\PedigreeRegister;
use AppBundle\Enumerator\FileType;
use AppBundle\Exception\InvalidPedigreeRegisterAbbreviationHttpException;
use AppBundle\Util\ReportUtil;
use AppBundle\Util\ResultUtil;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PopRepInputFileService extends ReportServiceBase
{
    const TITLE = 'poprep_input_file';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;
    const FILE_NAME_REPORT_TYPE = 'POPREP INPUT FILE';

    /**
     * @param $pedigreeRegisterAbbreviation
     * @return \AppBundle\Component\HttpFoundation\JsonResponse
     */
    function getReport($pedigreeRegisterAbbreviation)
    {


        try {
            $pedigreeRegister = $this->validatePedigreeRegisterAbbreviationAndGetPedigreeRegister($pedigreeRegisterAbbreviation);
            $this->filename = $this->getPopRepInputFileFileName($pedigreeRegisterAbbreviation);
            $this->extension = FileType::TXT;

            return $this->generateCsvFileBySqlQuery(
                $this->getFilename(),
                $this->getSqlQuery($pedigreeRegister->getId()),
                [],
                false
            );

        } catch (\Exception $exception) {
            return ResultUtil::errorResult($exception->getMessage(), $exception->getCode());
        }
    }

    private function validatePedigreeRegisterAbbreviationAndGetPedigreeRegister($pedigreeRegisterAbbreviation)
    {
        if (empty($pedigreeRegisterAbbreviation)) {
            throw new BadRequestHttpException('Missing pedigree register');
        }

        $pedigreeRegisterAbbreviation = strtoupper($pedigreeRegisterAbbreviation);
        $pedigreeRegister = $this->em->getRepository(PedigreeRegister::class)
            ->findOneByAbbreviation($pedigreeRegisterAbbreviation);

        if (!$pedigreeRegister) {
            throw new InvalidPedigreeRegisterAbbreviationHttpException($this->translator,strval($pedigreeRegisterAbbreviation));
        }

        return $pedigreeRegister;
    }

    private function getPopRepInputFileFileName($pedigreeRegisterAbbreviation): string {
        return ReportUtil::translateFileName($this->translator, self::FILE_NAME_REPORT_TYPE)
            . '_'. $pedigreeRegisterAbbreviation . '_'.
            ReportUtil::translateFileName($this->translator, TranslationKey::GENERATED_ON);
    }


    /**
     * @param int $pedigreeRegisterId
     * @return string
     */
    private function getSqlQuery(int $pedigreeRegisterId)
    {
        return "SELECT
            CONCAT(
                CONCAT(a.uln_country_code,a.uln_number),'|',
                COALESCE(NULLIF(CONCAT(father.uln_country_code,father.uln_number),''),'unknown_sire'),'|',
                COALESCE(NULLIF(CONCAT(mother.uln_country_code,mother.uln_number),''),'unknown_dam'),'|',
                to_char(a.date_of_birth, 'YYYY-MM-DD'),'|',
                gender.english_letter
            )
            as \"uln|uln_vader|uln_moeder|geboortedatum|geslacht\"
        FROM animal a
            LEFT JOIN animal father ON father.id = a.parent_father_id
            LEFT JOIN animal mother ON mother.id = a.parent_mother_id
            LEFT JOIN (VALUES ('MALE','M'), ('FEMALE','F')) AS gender(english_full, english_letter) ON a.gender = gender.english_full
        WHERE a.gender NOTNULL AND (a.gender = 'MALE' OR a.gender = 'FEMALE')
            AND a.is_alive
            AND a.location_id NOTNULL
            AND a.pedigree_register_id = $pedigreeRegisterId
        ";
    }
}
