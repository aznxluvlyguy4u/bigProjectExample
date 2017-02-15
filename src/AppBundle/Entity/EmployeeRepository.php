<?php

namespace AppBundle\Entity;

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

}
