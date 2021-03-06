<?php

namespace AppBundle\Validation;

use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Employee;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class CreateAdminValidator
 * @package AppBundle\Validation
 */
class EditAdminProfileValidator extends CreateAdminValidator
{
    const RESPONSE_INVALID_INPUT_PERSON_ID = "NO ADMIN FOUND FOR GIVEN PERSON ID";
    const EMPTY_PERSON_ID = 'EMPTY PERSON ID';

    /** @var ArrayCollection */
    private $admins;

    /** @var string */
    private $personId;

    /**
     * PasswordValidator constructor.
     * @param ArrayCollection $profileEditContent
     * @param ObjectManager $em
     * @param Employee $admin
     */
    public function __construct(ObjectManager $em, $profileEditContent, Employee $admin)
    {
        parent::__construct($em, $profileEditContent->toArray(), false);

        $this->admins = new ArrayCollection();
        $this->personId = $admin->getPersonId();

        $this->validate($profileEditContent->toArray());
    }

    /**
     * @param array $profileEditContent
     */
    private function validate($profileEditContent)
    {
        $firstName = Utils::getNullCheckedArrayValue(JsonInputConstant::FIRST_NAME, $profileEditContent);
        $this->validateFirstName($firstName);

        $lastName = Utils::getNullCheckedArrayValue(JsonInputConstant::LAST_NAME, $profileEditContent);
        $this->validateLastName($lastName);

        $emailAddress = Utils::getNullCheckedArrayValue(JsonInputConstant::EMAIL_ADDRESS, $profileEditContent);
        $this->validateEmailAddress($emailAddress, $this->personId);
    }

    /**
     * @return ArrayCollection
     */
    public function getAdmins()
    {
        return $this->admins;
    }



}