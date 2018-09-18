<?php


namespace AppBundle\Service\Migration;


use AppBundle\Entity\PedigreeCode;
use AppBundle\Entity\PedigreeRegister;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use Doctrine\Common\Persistence\ObjectManager;

class PedigreeCodeInitializer extends MigratorServiceBase implements IMigratorService
{
    const IMPORT_SUB_FOLDER = 'initial_values/';

    const PEDIGREE_CODE_DETAILS = 'pedigree_codes.csv';
    const PEDIGREE_CODES = 'pedigree_register_pedigree_codes.csv';

    /** @var array */
    private $pedigreeRegisters;

    /** @var array */
    private $pedigreeCodes;

    public function __construct(ObjectManager $em, $rootDir)
    {
        parent::__construct($em, self::BATCH_SIZE, self::IMPORT_SUB_FOLDER, $rootDir);

        $this->filenames = array(
            self::PEDIGREE_CODE_DETAILS => self::PEDIGREE_CODE_DETAILS,
            self::PEDIGREE_CODES => self::PEDIGREE_CODES,
        );

        $this->getCsvOptions()->setPipeSeparator()->includeFirstLine();
        $this->pedigreeCodes = [];
        $this->pedigreeRegisters = [];
    }


    public function run(CommandUtil $cmdUtil)
    {
        $this->initializePedigreeCodes();
        $this->initializePedigreeCodesFromPedigreeRegisters();
        $this->initializePedigreeRegisterPedigreeCodesRelationships();
        $this->pedigreeCodes = null;
        $this->pedigreeRegisters = null;
    }


    private function initializePedigreeCodes()
    {
        $this->writeLn('PedigreeCodes: Processing '.self::PEDIGREE_CODE_DETAILS.' ... ');

        $csv = $this->parseCSV(self::PEDIGREE_CODE_DETAILS);

        $insertCount = 0;
        $updateCount = 0;

        foreach ($csv as $record) {
            $code = $record[0];
            $fullname = $record[1];

            $pedigreeCode = $this->getPedigreeCode($code);
            if ($pedigreeCode) {
                $updatePedigreeCode = false;
                if ($pedigreeCode->getFullName() !== $fullname) {
                    $pedigreeCode->setFullName($fullname);
                    $updatePedigreeCode = true;
                }

                if (!$pedigreeCode->isValidated()) {
                    $pedigreeCode->setIsValidated(true);
                    $updatePedigreeCode = true;
                }

                if (!$updatePedigreeCode) {
                    continue;
                }

                $this->em->persist($pedigreeCode);
                $this->em->flush();
                $this->pedigreeCodes[$code] = $pedigreeCode;
                $updateCount++;
                continue;
            }

            $pedigreeCode = new PedigreeCode($code, $fullname, true);
            $this->pedigreeCodes[$code] = $pedigreeCode;
            $this->em->persist($pedigreeCode);
            $this->em->flush();
            $insertCount++;
        }

        if ($insertCount > 0) {
            $this->em->flush();
        }

        $this->writeLn(($insertCount > 0 ? $insertCount : 'No') . ' new PedigreeCodes inserted');
        $this->writeLn(($updateCount > 0 ? $updateCount : 'No') . ' existing PedigreeCodes updated');
    }


    private function initializePedigreeCodesFromPedigreeRegisters()
    {
        $this->writeLn('PedigreeCodes: Processing '.self::PEDIGREE_CODES.' ... ');

        $csv = $this->parseCSV(self::PEDIGREE_CODES);

        $insertCount = 0;

        foreach ($csv as $record) {
            $code = $record[1];

            $pedigreeCode = $this->getPedigreeCode($code);
            if ($pedigreeCode) {
                continue;
            }

            $pedigreeCode = new PedigreeCode($code, null, false);
            $this->pedigreeCodes[$code] = $pedigreeCode;
            $this->em->persist($pedigreeCode);
            $this->em->flush();
            $insertCount++;
        }

        if ($insertCount > 0) {
            $this->em->flush();
        }

        $this->writeLn(($insertCount > 0 ? $insertCount : 'No') . ' new PedigreeCodes inserted');
    }


    private function initializePedigreeRegisterPedigreeCodesRelationships()
    {
        $this->writeLn('PedigreeRegister-PedigreeCode relationships: Processing '.self::PEDIGREE_CODES.' ... ');

        $csv = $this->parseCSV(self::PEDIGREE_CODES);

        $updateCount = 0;

        foreach ($csv as $record) {
            $pedigreeRegisterAbbreviation = $record[0];
            $code = $record[1];

            $pedigreeRegister = $this->getPedigreeRegister($pedigreeRegisterAbbreviation);
            if (!$pedigreeRegister) {
                continue;
            }

            if ($pedigreeRegister->hasPedigreeCode($code)) {
                continue;
            }

            $pedigreeCode = $this->getPedigreeCode($code);
            $pedigreeRegister->addPedigreeCode($pedigreeCode);
            $this->pedigreeRegisters[$pedigreeRegisterAbbreviation] = $pedigreeRegister;

            $this->em->persist($pedigreeRegister);
            $updateCount++;
        }

        if ($updateCount > 0) {
            $this->em->flush();
        }

        $this->writeLn(($updateCount > 0 ? $updateCount : 'No') . ' PedigreeCodes added to PedigreeRegisters');
    }


    /**
     * @param string $abbreviation
     * @return PedigreeRegister|null
     */
    private function getPedigreeRegister($abbreviation)
    {
        $pedigreeRegister = ArrayUtil::get($abbreviation, $this->getPedigreeRegisters());
        if (!$pedigreeRegister) {
            $this->writeLn('Invalid PedigreeRegister abbreviation: '.$abbreviation);
            return null;
        }

        return $pedigreeRegister;
    }


    /**
     * @return array|PedigreeRegister[]
     */
    private function getPedigreeRegisters()
    {
        if (count($this->pedigreeRegisters) === 0) {
            $this->refreshPedigreeRegisters();
        }
        return $this->pedigreeRegisters;
    }


    private function refreshPedigreeRegisters()
    {
        foreach ($this->em->getRepository(PedigreeRegister::class)->findAll() as $pedigreeRegister) {
            $this->pedigreeRegisters[$pedigreeRegister->getAbbreviation()] = $pedigreeRegister;
        }
    }


    /**
     * @param string $code
     * @return PedigreeCode|null
     */
    private function getPedigreeCode($code)
    {
        $pedigreeCode = ArrayUtil::get($code, $this->getPedigreeCodes());
        if (!$pedigreeCode) {
            return null;
        }

        return $pedigreeCode;
    }


    /**
     * @return array|PedigreeCode[]
     */
    private function getPedigreeCodes()
    {
        if (count($this->pedigreeCodes) === 0) {
            $this->refreshPedigreeCodes();
        }
        return $this->pedigreeCodes;
    }


    private function refreshPedigreeCodes()
    {
        foreach ($this->em->getRepository(PedigreeCode::class)->findAll() as $pedigreeCode) {
            $this->pedigreeCodes[$pedigreeCode->getCode()] = $pedigreeCode;
        }
    }
}