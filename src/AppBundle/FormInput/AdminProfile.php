<?php

namespace AppBundle\FormInput;

use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Employee;
use Doctrine\Common\Collections\ArrayCollection;

class AdminProfile
{
    /**
     * @param Employee $admin
     * @param ArrayCollection $content
     * @return Employee
     */
    public static function update(Employee $admin, ArrayCollection $content)
    {
        $firstName = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::FIRST_NAME, $content);
        $lastName = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::LAST_NAME, $content);
        $emailAddress = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::EMAIL_ADDRESS, $content);
        $password = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::NEW_PASSWORD, $content);

        if($firstName != null) { $admin->setFirstName($firstName); }
        if($lastName != null) { $admin->setLastName($lastName); }
        if($emailAddress != null) { $admin->setEmailAddress($emailAddress); }
        if($password != null) { $admin->setPassword($password); }

        return $admin;
    }
}