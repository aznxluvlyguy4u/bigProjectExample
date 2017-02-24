<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\AccessLevelType;
use Doctrine\Common\Collections\Criteria;

class EmployeeRepository extends BaseRepository {


    /**
     * @param string $emailAddress
     * @return null|Employee
     */
    public function findActiveOneByEmailAddress($emailAddress)
    {
        $emailAddress = trim(strtolower($emailAddress));
        /** @var EmployeeRepository $employeeRepository */
        $employeeRepository = $this->getManager()->getRepository(Employee::class);
        return $employeeRepository->findOneBy(["emailAddress"=>$emailAddress, "isActive" => TRUE]);
    }


    /**
     * @param string $accessLevel
     * @param boolean $includeDevelopers
     * @return \Doctrine\Common\Collections\Collection
     */
    public function findByMinimumAccessLevel($accessLevel, $includeDevelopers = true)
    {
        if($includeDevelopers) {
            switch ($accessLevel) {
                case AccessLevelType::DEVELOPER:
                    $criteria = Criteria::create()
                        ->where(Criteria::expr()->eq('accessLevel', AccessLevelType::DEVELOPER))
                        ->orWhere(Criteria::expr()->eq('accessLevel', AccessLevelType::SUPER_ADMIN))
                        ->orWhere(Criteria::expr()->eq('accessLevel', AccessLevelType::ADMIN))
                        ->orderBy(['firstName' => Criteria::ASC]);
                    break;

                case AccessLevelType::SUPER_ADMIN:
                    $criteria = Criteria::create()
                        ->where(Criteria::expr()->eq('accessLevel', AccessLevelType::DEVELOPER))
                        ->orWhere(Criteria::expr()->eq('accessLevel', AccessLevelType::SUPER_ADMIN))
                        ->orderBy(['firstName' => Criteria::ASC]);
                    break;

                default:
                    $criteria = Criteria::create()
                        ->orderBy(['firstName' => Criteria::ASC]);
                    break;
            }           
            
        } else {
            switch ($accessLevel) {
                case AccessLevelType::SUPER_ADMIN:
                    $criteria = Criteria::create()
                        ->where(Criteria::expr()->eq('accessLevel', AccessLevelType::SUPER_ADMIN))
                        ->orderBy(['firstName' => Criteria::ASC]);
                    break;

                default:
                $criteria = Criteria::create()
                    ->where(Criteria::expr()->eq('accessLevel', AccessLevelType::SUPER_ADMIN))
                    ->orWhere(Criteria::expr()->eq('accessLevel', AccessLevelType::ADMIN))
                    ->orderBy(['firstName' => Criteria::ASC]);
                break;
            }
        }

        return $this->matching($criteria);
    }

    
}
