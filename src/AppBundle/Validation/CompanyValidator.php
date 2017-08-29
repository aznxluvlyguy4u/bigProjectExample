<?php

namespace AppBundle\Validation;

use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Client;
use AppBundle\Entity\ClientRepository;
use AppBundle\Entity\Content;
use AppBundle\Entity\Employee;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Constant\Constant;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\ExpressionLanguage\Tests\Node\Obj;
use \Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class CompanyValidator
 * @package AppBundle\Validation
 */
class CompanyValidator extends BaseValidator
{
    /**
     * CompanyValidator constructor.
     * @param ArrayCollection $content
     * @param ObjectManager $em
     * @param boolean $isPost
     */
    public function __construct(ObjectManager $em, ArrayCollection $content, $isPost = true)
    {
        parent::__construct($em, $content);
        $this->isInputValid = $isPost ? $this->validatePost() : $this->validatePut();
    }

    /**
     * @return boolean
     */
    private function validatePost()
    {
        //First check if topLevel ArrayKeys exist
        $areTopLevelArrayKeysMissing = false;
        $topLevelArrayKeys = ['owner', 'unpaid_invoices', 'billing_address', 'locations', 'deleted_locations',
        'users', 'deleted_users', 'pedigrees', 'company_name', 'animal_health_subscription', 'subscription_date'];
        foreach ($topLevelArrayKeys as $topLevelArrayKey) {
            if(!$this->content->containsKey($topLevelArrayKey)) {
                $this->errors[] = "The arrayKey '".$topLevelArrayKey."' is missing";
                $areTopLevelArrayKeysMissing = true;
            }
        }
        if($areTopLevelArrayKeysMissing) { return false; }

        //Validate users
        $ownerArray = $this->content->get('owner');
        $usersArray = $this->content->get('users');

        $isValid = true;
        if($this->containsPersonValues($ownerArray, 'owner') == false) { $isValid = false; };
        foreach ($usersArray as $userArray) {
            if($this->containsPersonValues($userArray, 'users') == false) { $isValid = false; };
        }

        return $isValid;
    }

    /**
     * @param array $array
     * @param string $arrayName
     * @return bool
     */
    private function containsPersonValues(array $array, $arrayName)
    {
        $keysToCheck = ['last_name', 'first_name', 'email_address', 'primary_contactperson'];
        $areAllKeysPresent = true;
        foreach ($keysToCheck as $keyToCheck) {
            if(!array_key_exists($keyToCheck, $array)) {
                $this->errors[] = "Key '".$keyToCheck."' is missing from array '".$arrayName."'";
                $areAllKeysPresent = false;
            }
        }
        return $areAllKeysPresent;
    }

    /**
     * @param ObjectManager $em
     * @param string $emailAddress
     * @return bool
     */
    public static function doesClientAlreadyExist(ObjectManager $em, $emailAddress)
    {
        /** @var ClientRepository $repository */
        $repository = $em->getRepository(Client::class);
        $user = $repository->findOneBy(array('emailAddress' => $emailAddress, 'isActive' => true));
        return $user != null;
    }


    /**
     * @param string $emailAddress
     * @return JsonResponse|\AppBundle\Component\HttpFoundation\JsonResponse
     */
    public static function emailAddressIsInUseErrorMessage($emailAddress)
    {
        return new JsonResponse(
            array(
                Constant::CODE_NAMESPACE => 400,
                Constant::MESSAGE_NAMESPACE => 'THIS EMAIL IS ALREADY REGISTERED FOR ANOTHER USER. EMAIL HAS TO BE UNIQUE.',
                'data' => $emailAddress
            ),
            400
        );
    }
    

    private function validatePut()
    {
        //TODO
    }
}