<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclarationDetail;
use AppBundle\Entity\DeclareAnimalFlag;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Entity\RetrieveAnimals;
use AppBundle\Entity\RetrieveCountries;
use AppBundle\Entity\RetrieveTags;
use AppBundle\Entity\RetrieveUbnDetails;
use AppBundle\Entity\RevokeDeclaration;
use AppBundle\Service\Container\NonControllerServiceContainer;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ControllerServiceBase
 */
abstract class ControllerServiceBase
{
    /** @var NonControllerServiceContainer */
    protected $container;

    public function __construct(NonControllerServiceContainer $container)
    {
        $this->container = $container;
    }


    /**
     * @return ObjectManager|EntityManagerInterface
     */
    public function getManager()
    {
        return $this->container->getManager();
    }


    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->container->getConnection();
    }


    /**
     * Clears the redis cache for the Livestock of a given location , to reflect changes of animals on Livestock.
     *
     * @param Location $location
     * @param Animal | Ewe | Ram | Neuter $animal
     */
    protected function clearLivestockCacheForLocation(Location $location = null, $animal = null) {
        $this->container->getCacheService()->clearLivestockCacheForLocation($location, $animal);
    }


    /**
     * @param Request $request
     * @return JsonResponse|array|string
     */
    public function getToken(Request $request)
    {
        //Get auth header to read token
        if(!$request->headers->has(Constant::AUTHORIZATION_HEADER_NAMESPACE)) {
            return new JsonResponse(array("errorCode" => 401, "errorMessage"=>"Unauthorized"), 401);
        }

        return $request->headers->get('AccessToken');
    }


    /**
     * @param $messageObject
     * @return null|DeclareArrival|DeclareImport|DeclareExport|DeclareDepart|DeclareBirth|DeclareLoss|DeclareAnimalFlag|DeclarationDetail|DeclareTagsTransfer|RetrieveTags|RevokeDeclaration|RetrieveAnimals|RetrieveAnimals|RetrieveCountries|RetrieveUbnDetails
     */
    public function persist($messageObject)
    {
        //Set the string values
        $repositoryEntityNameSpace = Utils::getRepositoryNameSpace($messageObject);

        //Persist to database
        $this->getManager()->getRepository($repositoryEntityNameSpace)->persist($messageObject);

        return $messageObject;
    }


    /**
     * @param $object
     * @return mixed
     */
    protected function persistAndFlush($object)
    {
        $this->getManager()->persist($object);
        $this->getManager()->flush();
        return $object;
    }


    /**
     */
    protected function flushClearAndGarbageCollect()
    {
        $this->getManager()->flush();
        $this->getManager()->clear();
        gc_collect_cycles();
    }


    /**
     * @return Client|Employee|\AppBundle\Entity\Person
     */
    public function getUser()
    {
        return $this->container->getUserService()->getUser();
    }


    /**
     * @param Request $request
     * @return Client|null
     */
    public function getAccountOwner(Request $request = null)
    {
        return $this->container->getUserService()->getAccountOwner($request);
    }


    /**
     * @param string $tokenCode
     * @return Employee|null
     */
    public function getEmployee($tokenCode = null)
    {
        return $this->container->getUserService()->getEmployee($tokenCode);
    }


    /**
     * @param Request $request
     * @return Location|null
     */
    public function getSelectedLocation(Request $request)
    {
        return $this->container->getUserService()->getSelectedLocation($request);
    }


    /**
     * @param Request $request
     * @return string|null
     */
    public function getSelectedUbn(Request $request)
    {
        return $this->container->getUserService()->getSelectedUbn($request);
    }
}