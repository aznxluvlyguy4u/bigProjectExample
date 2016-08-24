<?php

namespace AppBundle\Util;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Location;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Person;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Token;
use AppBundle\Enumerator\GenderType;
use AppBundle\Util\NullChecker;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

class Validator
{

    /**
     * @param Person $user
     * @param null $ghostToken
     * @return bool
     */
    public static function isUserLoginWithActiveCompany($user, $ghostToken = null)
    {
        if($user instanceof Client) {
            return self::isCompanyActiveOfClient($user);
        } else if($user instanceof Employee && $ghostToken instanceof Token) {
            return self::isCompanyActiveOfGhostToken($ghostToken);
        } else {
            //only Clients and Employees with GhostTokens are able to login
            return false;
        }
    }
    
    /**
     * TODO At the moment any Client(user) can only own one Company OR be an employee a one company. When this changes, this validation check has to be updated.
     * @param Client $client
     * @return bool
     */
    private static function isCompanyActiveOfClient($client)
    {
        //null check
        if(!($client instanceof Client)) { return false; }

        if($client->hasEmployer()) {

            //is user employee at the company
            $isActive = $client->getEmployer()->isActive();
            if($isActive) {
                return true;
            } else {
                return false;
            }

        } else {
            //is owner at at least one of owner's companies
            $companies = $client->getCompanies();
            $deactivatedCompanies = 0;
            /** @var Company $company */
            foreach ($companies as $company) {
                if(!$company->isActive()) {
                    $deactivatedCompanies++;
                }
            }

            if($deactivatedCompanies == $companies->count()) {
                //has no active companies
                return false;
            } else {
                return true;
            }
        }
    }


    /**
     * @param Token $ghostToken
     * @return bool
     */
    private static function isCompanyActiveOfGhostToken($ghostToken)
    {
        //null check
        if(!($ghostToken instanceof Token)) { return false; }
        
        $tokenOwner = $ghostToken->getOwner();
        if($tokenOwner instanceof Client) {
            return self::isCompanyActiveOfClient($tokenOwner);
        } else {
            //not a client, so cannot even have any companies
            return false;
        }
        
    }
}