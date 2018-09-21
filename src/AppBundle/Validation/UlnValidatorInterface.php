<?php


namespace AppBundle\Validation;


use AppBundle\Entity\Company;
use AppBundle\Entity\Person;
use Doctrine\Common\Collections\ArrayCollection;

interface UlnValidatorInterface
{
    function validateUln(array $ulnSet);

    function validateUlns(array $ulnSets);

    function validateUlnsWithUserAccessPermission(array $ulnSets, Person $person, $company);

    /**
     * Validate if given ULNs are correct AND there should at least be one ULN given
     *
     * @param ArrayCollection $collection
     * @param Person $person
     * @param Company|null $company
     */
    function pedigreeCertificateUlnsInputValidation(ArrayCollection $collection, Person $person, $company);
}