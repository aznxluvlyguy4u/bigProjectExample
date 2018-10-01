<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Controller\ProfileAPIControllerInterface;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\FormInput\CompanyProfile;
use AppBundle\Output\CompanyProfileOutput;
use AppBundle\Output\LoginOutput;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;


class ProfileService extends ControllerServiceBase implements ProfileAPIControllerInterface
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getCompanyProfile(Request $request)
    {
        $client = $this->getAccountOwner($request);
        $location = $this->getSelectedLocation($request);

        $this->nullCheckClient($client);
        $this->nullCheckLocation($location);

        $outputArray = CompanyProfileOutput::create($location->getCompany(), $location);

        return ResultUtil::successResult($outputArray);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getLoginData(Request $request)
    {
        $loggedInUser = $this->getUser();
        $client = $this->getAccountOwner($request);
        $this->nullCheckClient($client);
        $revealHistoricAnimals = $this->getSelectedLocation($request)->getCompany()->getIsRevealHistoricAnimals();
        $outputArray = LoginOutput::create($client, $loggedInUser, $revealHistoricAnimals);
        return ResultUtil::successResult($outputArray);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function editCompanyProfile(Request $request)
    {
        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();
        $content = RequestUtil::getContentAsArray($request);
        $location = $this->getSelectedLocation($request);

        $this->nullCheckClient($client);
        $this->nullCheckLocation($location);

        //TODO Phase 2: Give back a specific company and location of that company. The CompanyProfileOutput already can process a ($client, $company, $location) method signature.
        $company = $location->getCompany();

        //Persist updated changes and return the updated values
        $client = CompanyProfile::update($client, $content, $company);
        $this->getManager()->persist($client);
        $log = ActionLogWriter::updateProfile($this->getManager(), $client, $loggedInUser, $company);
        $this->flushClearAndGarbageCollect(); //Only flush after persisting both the client and ActionLogWriter

        $outputArray = CompanyProfileOutput::create($company, $location);

        return ResultUtil::successResult($outputArray);
    }
}