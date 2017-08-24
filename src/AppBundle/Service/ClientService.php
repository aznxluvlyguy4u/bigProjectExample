<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\Entity\ClientRepository;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Output\ClientOverviewOutput;
use AppBundle\Service\Container\RepositoryContainerBase;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ClientService
 * @package AppBundle\Service
 */
class ClientService extends ControllerServiceBase
{
    /**
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getClients(Request $request)
    {
        if (!AdminValidator::isAdmin($this->getEmployee(), AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        $ubnExists = $request->query->has(Constant::UBN_NAMESPACE);

        if(!$ubnExists) {
            $clients = $this->getManager()->getRepository(Client::class)->findAll();
            $result = ClientOverviewOutput::createClientsOverview($clients);
        } else { //Get client by ubn
            $ubn = $request->query->get(Constant::UBN_NAMESPACE);
            $client = $this->getManager()->getRepository(Client::class)->getByUbn($ubn);
            if($client == null) {
                $result = 'NO CLIENT FOUND FOR GIVEN UBN';
            } else {
                $result = ClientOverviewOutput::createClientOverview($client);
            }
        }

        return ResultUtil::successResult($result);
    }


    /**
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createClient(Request $request)
    {
//        if (!AdminValidator::isAdmin($this->getEmployee(), AccessLevelType::ADMIN)) {
//            return AdminValidator::getStandardErrorResponse();
//        }

        return ResultUtil::successResult('ok');
    }
}