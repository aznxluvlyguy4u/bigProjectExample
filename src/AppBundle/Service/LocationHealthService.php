<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Controller\HealthAPIControllerInterface;
use AppBundle\Entity\Company;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\FormInput\LocationHealthEditor;
use AppBundle\Output\HealthOutput;
use AppBundle\Util\AdminActionLogWriter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\HealthEditValidator;
use Symfony\Component\HttpFoundation\Request;

class LocationHealthService extends ControllerServiceBase implements HealthAPIControllerInterface
{

    /**
     * @param Request $request
     * @param $ubn
     * @return JsonResponse
     */
    public function getHealthByLocation(Request $request, $ubn)
    {
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::SUPER_ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        $location = $this->getManager()->getRepository(Location::class)->findOneByActiveUbn($ubn);

        if($location == null) {
            return ResultUtil::errorResult("No Location found with ubn: " . $ubn, 428);
        }

        $outputArray = HealthOutput::create($this->getManager(), $location);
        return ResultUtil::successResult($outputArray);
    }


    /**
     * @param Request $request
     * @param $ubn
     * @return JsonResponse
     */
    public function updateHealthStatus(Request $request, $ubn)
    {
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::SUPER_ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        $location = $this->getManager()->getRepository(Location::class)->findOneByActiveUbn($ubn);

        if($location == null) {
            return ResultUtil::errorResult("No Location found with ubn: " . $ubn, 428);
        }

        $company = $location->getCompany();
        if ($company === null) {
            return ResultUtil::errorResult("No Company found for location with ubn: " . $ubn, 428);
        }

        $content = RequestUtil::getContentAsArrayCollection($request);

        $updateHealthStatusValues = true;
        if ($content->containsKey(JsonInputConstant::ANIMAL_HEALTH_SUBSCRIPTION)) {
            $newAnimalHealthSubscription = $content->get(JsonInputConstant::ANIMAL_HEALTH_SUBSCRIPTION);

            if ($company->getAnimalHealthSubscription() != $newAnimalHealthSubscription) {
                $company->setAnimalHealthSubscription($newAnimalHealthSubscription);
                $this->getManager()->persist($company);
                $this->getManager()->flush();

                AdminActionLogWriter::updateAnimalHealthSubscription($this->getManager(), $admin, $company);
            }

            if (!$newAnimalHealthSubscription) {
                $updateHealthStatusValues = false;
            }
        }


        if ($updateHealthStatusValues) {
            //Status and check date validation
            $healthEditValidator = new HealthEditValidator($this->getManager(), $content);
            if(!$healthEditValidator->getIsValid()) {
                return $healthEditValidator->createJsonResponse();
            }

            $location = LocationHealthEditor::edit($this->getManager(), $location, $content); //includes persisting changes
            AdminActionLogWriter::updateHealthStatus($this->getManager(), $admin, $location, $content);
        }


        $outputArray = HealthOutput::createCompanyHealth($this->getManager(), $company);

        return ResultUtil::successResult($outputArray);
    }


    /**
     * @param Request $request
     * @param $companyId
     * @return JsonResponse
     */
    public function getHealthByCompany(Request $request, $companyId)
    {
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::SUPER_ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        /** @var Company $company */
        $company = $this->getManager()->getRepository(Company::class)->findOneByCompanyId($companyId);
        $outputArray = HealthOutput::createCompanyHealth($this->getManager(), $company);

        return ResultUtil::successResult($outputArray);
    }
}
