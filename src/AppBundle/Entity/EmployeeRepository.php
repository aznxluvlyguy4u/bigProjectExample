<?php

namespace AppBundle\Entity;

use AppBundle\Component\Utils;
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


    /**
     * @return Employee
     */
    public function getAutomatedProcess()
    {
        $firstName = 'AUTOMATED';
        $lastName = 'PROCESS';
        $accessLevel = AccessLevelType::DEVELOPER;
        $isActive = false;
        $emailAddress = 'automated.process@nsfo.nl';

        /** @var Employee $automatedProcess */
        $automatedProcess = $this->findOneBy([
            'firstName' => $firstName,
            'lastName' => $lastName,
            'accessLevel' => $accessLevel,
        ]);

        $areValuesChanged = false;
        if ($automatedProcess) {
            if ($automatedProcess->getIsActive() !== $isActive) {
                $automatedProcess->setIsActive($isActive);
                $areValuesChanged = true;
            }
        } else {
            //Initialize
            $automatedProcess = new Employee($accessLevel, $firstName, $lastName, $emailAddress,
                Utils::randomString(32));
            $automatedProcess->setIsActive($isActive);
            $areValuesChanged = true;
        }


        if ($areValuesChanged) {
            $this->getManager()->persist($automatedProcess);
            $this->getManager()->flush();
        }

        return $automatedProcess;
    }

}
