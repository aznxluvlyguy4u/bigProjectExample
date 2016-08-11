<?php

namespace AppBundle\Validation;

use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Person;
use AppBundle\Output\AccessLevelOverviewOutput;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Constant\Constant;
use Doctrine\ORM\EntityManager;
use \Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class CreateAdminValidator
 * @package AppBundle\Validation
 */
class EditAdminValidator extends CreateAdminValidator
{
    const RESPONSE_INVALID_INPUT_PERSON_ID = "NO ADMIN FOUND FOR GIVEN PERSON ID";
    const EMPTY_PERSON_ID = 'EMPTY PERSON ID';

    /** @var ArrayCollection */
    private $admins;

    /**
     * PasswordValidator constructor.
     * @param array $profileEditContent
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em, $profileEditContent)
    {
        parent::__construct($em, $profileEditContent, false);

        $this->admins = new ArrayCollection();

        $this->validate($profileEditContent);
    }

    /**
     * @param array $adminsContent
     */
    private function validate($adminsContent)
    {
        foreach ($adminsContent as $adminContent) {

            $personId = Utils::getNullCheckedArrayValue(JsonInputConstant::PERSON_ID, $adminContent);
            $this->validatePersonId($personId);

            $firstName = Utils::getNullCheckedArrayValue(JsonInputConstant::FIRST_NAME, $adminContent);
            $this->validateFirstName($firstName);

            $lastName = Utils::getNullCheckedArrayValue(JsonInputConstant::LAST_NAME, $adminContent);
            $this->validateLastName($lastName);

            $emailAddress = Utils::getNullCheckedArrayValue(JsonInputConstant::EMAIL_ADDRESS, $adminContent);
            $this->validateEmailAddress($emailAddress, $personId);

            $accessLevel = Utils::getNullCheckedArrayValue(JsonInputConstant::ACCESS_LEVEL, $adminContent);
            $this->validateAccessLevelType($accessLevel);
        }
    }

    /**
     * @param string $personId
     */
    protected function validatePersonId($personId)
    {
        if($personId == null || $personId == "" || $personId == " ") {
            $this->isValid = false;
            $this->errors[self::EMPTY_FIRST_NAME] = self::RESPONSE_INVALID_INPUT_PERSON_ID;

        } else {

            $foundAdmin = $this->em->getRepository(Employee::class)->findOneBy(['personId' => $personId]);

            if($foundAdmin == null) {
                $this->isValid = false;
                $this->errors[$personId] = self::RESPONSE_INVALID_INPUT_PERSON_ID;
            } else {
                $this->admins->set($personId, $foundAdmin);
            }
        }
    }

    /**
     * @return ArrayCollection
     */
    public function getAdmins()
    {
        return $this->admins;
    }



}