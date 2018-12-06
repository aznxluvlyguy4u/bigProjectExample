<?php


namespace AppBundle\Validation;


use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Person;
use AppBundle\SqlView\Repository\ViewMinimalParentDetailsRepository;
use AppBundle\SqlView\View\ViewMinimalParentDetails;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\Translation\TranslatorInterface;

class UlnValidator implements UlnValidatorInterface
{
    const MISSING_INPUT_MESSAGE = 'NO ULN GIVEN';

    const MAX_ANIMALS = 50;
    const ERROR_MESSAGE_MAX_ANIMALS_EXCEEDED = 'NO MORE THAN 50 ANIMALS CAN BE SELECTED AT A TIME';

    /** @var EntityManagerInterface */
    private $em;

    /** @var ViewMinimalParentDetailsRepository */
    private $viewMinimalParentDetailsRepository;

    /** @var TranslatorInterface */
    private $translator;

    /** @var array */
    private $ulns;
    /** @var array */
    private $ulnsData;
    /** @var array */
    private $blockedUlns;
    /** @var array */
    private $missingUlns;
    /** @var array */
    private $invalidUlns;

    public function __construct(EntityManagerInterface $em,
                                ViewMinimalParentDetailsRepository $viewMinimalParentDetailsRepository,
                                TranslatorInterface $translator)
    {
        $this->em = $em;
        $this->viewMinimalParentDetailsRepository = $viewMinimalParentDetailsRepository;
        $this->translator = $translator;
    }


    function validateUln(array $ulnSet)
    {
        $this->validateUlns([$ulnSet]);
    }


    function validateUlns(array $ulnSets)
    {
        if (empty($ulnSets)) {
            throw new PreconditionFailedHttpException(self::MISSING_INPUT_MESSAGE);
        }

        if (count($ulnSets) > self::MAX_ANIMALS) {
            throw new PreconditionFailedHttpException(self::ERROR_MESSAGE_MAX_ANIMALS_EXCEEDED);
        }

        $this->ulns = [];
        foreach ($ulnSets as $ulnSet) {
            $uln = !empty($ulnSet) && is_array($ulnSet) ?
                   ArrayUtil::get(JsonInputConstant::ULN_COUNTRY_CODE, $ulnSet, '')
                  .ArrayUtil::get(JsonInputConstant::ULN_NUMBER, $ulnSet, '')
                : '';
            if  (!Validator::verifyUlnFormat($uln, false)) {
                $this->getInvalidUlns()[] = $uln;
            }
            $this->ulns[] = $uln;
        }

        $this->throwIfInvalidUlnsFound();

        $this->getUlnsData();

        $this->throwIfUlnsMissing();
    }


    /**
     * @param array $ulnSets
     * @param Person $person
     * @param Company|null $company
     * @throws \Exception
     */
    function validateUlnsWithUserAccessPermission(array $ulnSets, Person $person, $company)
    {
        if (!($person instanceof Client) && !($person instanceof Employee)) {
            throw Validator::unauthorizedException();
        }

        $this->validateUlns($ulnSets);

        if ($person instanceof Employee) {
            return;
        }

        if (!$company) {
            throw Validator::unauthorizedException();
        }

        $currentUbnsOfUser = $company->getUbns(true);
        if (empty($currentUbnsOfUser)) {
            throw Validator::unauthorizedException();
        }

        $this->blockedUlns = [];
        $animals = $this->viewMinimalParentDetailsRepository->findByUlns($this->ulns);
        foreach ($animals as $animal) {
            if (!Validator::isUserAllowedToAccessAnimalDetails($animal, $company, $currentUbnsOfUser)) {
                $this->blockedUlns[] = $animal->getUln();
            }
        }

        if (!empty($this->blockedUlns)) {
            throw new PreconditionFailedHttpException($this->translator->trans('THE FOLLOWING ULNS ARE BLOCKED FOR YOU').': '. implode(', ', $this->blockedUlns));
        }
    }


    /**
     * @param ArrayCollection $collection
     * @param Person $person
     * @param Company|null $company
     * @throws \Exception
     */
    public function pedigreeCertificateUlnsInputValidation(ArrayCollection $collection, Person $person, $company)
    {
        $ulnSets = $collection->get(Constant::ANIMALS_NAMESPACE);
        if (!is_array($ulnSets)) {
            throw new PreconditionFailedHttpException(Constant::ANIMALS_NAMESPACE.'must contain ulnSets array');
        }

        $includeAccessPermission = false; // If true will add significantly (3x) more process time at the moment.
        if ($includeAccessPermission) {
            $this->validateUlnsWithUserAccessPermission($ulnSets, $person, $company);
        } else {
            $this->validateUlns($ulnSets);
        }
    }

    /**
     * @param ViewMinimalParentDetails $animal
     * @param Person $person
     * @param Company|null $company
     * @return bool
     */
    public static function isUserAllowedToAccessAnimalDetails(ViewMinimalParentDetails $animal, Person $person, ?Company $company)
    {
        if ($person instanceof Employee) {
            return true;
        }

        if (!($person instanceof Client) || !$company) {
            return false;
        }

        $currentUbnsOfUser = $company->getUbns(true);
        if (empty($currentUbnsOfUser)) {
            return false;
        }

        return Validator::isUserAllowedToAccessAnimalDetails($animal, $company, $currentUbnsOfUser);
    }


    private function getInvalidUlns(): array
    {
        if ($this->invalidUlns === null) {
            $this->invalidUlns = [];
        }
        return $this->invalidUlns;
    }


    private function throwIfInvalidUlnsFound()
    {
        if (!empty($this->getInvalidUlns())) {
            throw new PreconditionFailedHttpException($this->translator->trans('THE FOLLOWING ULNS HAVE AN INCORRECT FORMAT').': '. implode(', ', $this->getInvalidUlns()));
        }
    }

    private function throwIfUlnsMissing()
    {
        $this->missingUlns = [];
        foreach ($this->ulnsData as $ulnDatum) {
            if (!$ulnDatum['uln_exists']) {
                $this->missingUlns[] = $ulnDatum['uln'];
            }
        }

        if (!empty($this->missingUlns)) {
            throw new PreconditionFailedHttpException($this->translator->trans('THE FOLLOWING ULNS ARE DO NOT EXIST IN THE DATABASE').': '. implode(', ', $this->missingUlns));
        }
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getUlnsData()
    {
        if (empty($this->ulns)) {
            $this->ulnsData = [];
        }

        $ulnSearchString = "('" . implode("'),('", $this->ulns) . "')";

        $sql = "SELECT
                  uln_sets.uln,
                  w.id NOTNULL as uln_exists
                FROM (VALUES $ulnSearchString) AS uln_sets(uln)
                  LEFT JOIN (
                              SELECT
                                id,
                                uln_country_code,
                                uln_number
                              FROM animal
            )w ON CONCAT(w.uln_country_code, w.uln_number) = uln_sets.uln";

        $this->ulnsData = $this->em->getConnection()->query($sql)->fetchAll();
    }
}