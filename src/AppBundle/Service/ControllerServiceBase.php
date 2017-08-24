<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Client;
use AppBundle\Entity\ClientRepository;
use AppBundle\Entity\DeclarationDetail;
use AppBundle\Entity\DeclareAnimalFlag;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareArrivalRepository;
use AppBundle\Entity\DeclareArrivalResponse;
use AppBundle\Entity\DeclareArrivalResponseRepository;
use AppBundle\Entity\DeclareBase;
use AppBundle\Entity\DeclareBaseRepository;
use AppBundle\Entity\DeclareBaseResponse;
use AppBundle\Entity\DeclareBaseResponseRepository;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareDepartRepository;
use AppBundle\Entity\DeclareDepartResponse;
use AppBundle\Entity\DeclareDepartResponseRepository;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareExportRepository;
use AppBundle\Entity\DeclareExportResponse;
use AppBundle\Entity\DeclareExportResponseRepository;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\DeclareImportRepository;
use AppBundle\Entity\DeclareImportResponse;
use AppBundle\Entity\DeclareImportResponseRepository;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\DeclareLossRepository;
use AppBundle\Entity\DeclareLossResponse;
use AppBundle\Entity\DeclareLossResponseRepository;
use AppBundle\Entity\DeclareNsfoBase;
use AppBundle\Entity\DeclareNsfoBaseRepository;
use AppBundle\Entity\DeclareTagReplace;
use AppBundle\Entity\DeclareTagReplaceRepository;
use AppBundle\Entity\DeclareTagReplaceResponse;
use AppBundle\Entity\DeclareTagReplaceResponseRepository;
use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Entity\DeclareTagsTransferRepository;
use AppBundle\Entity\DeclareTagsTransferResponse;
use AppBundle\Entity\DeclareTagsTransferResponseRepository;
use AppBundle\Entity\Employee;
use AppBundle\Entity\EmployeeRepository;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationRepository;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Entity\RetrieveAnimals;
use AppBundle\Entity\RetrieveCountries;
use AppBundle\Entity\RetrieveTags;
use AppBundle\Entity\RetrieveUbnDetails;
use AppBundle\Entity\RevokeDeclaration;
use AppBundle\Entity\Tag;
use AppBundle\Entity\TagRepository;
use AppBundle\Entity\Token;
use AppBundle\Entity\TokenRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ControllerServiceBase
 */
abstract class ControllerServiceBase
{
    /** @var EntityManagerInterface|ObjectManager */
    protected $em;
    /** @var IRSerializer */
    protected $serializer;
    /** @var CacheService */
    protected $cacheService;
    /** @var UserService */
    protected $userService;

    /** @var Connection */
    protected $conn;

    /** @var AnimalRepository */
    protected $animalRepository;
    /** @var ClientRepository */
    protected $clientRepository;

    /** @var DeclareBaseRepository */
    protected $declareBaseRepository;
    /** @var DeclareBaseResponseRepository */
    protected $declareBaseResponseRepository;
    /** @var DeclareNsfoBaseRepository */
    protected $declareNsfoBaseRepository;

    /** @var DeclareArrivalRepository */
    protected $declareArrivalRepository;
    /** @var DeclareArrivalResponseRepository */
    protected $declareArrivalResponseRepository;
    /** @var DeclareImportRepository */
    protected $declareImportRepository;
    /** @var DeclareImportResponseRepository */
    protected $declareImportResponseRepository;
    /** @var DeclareDepartRepository */
    protected $declareDepartRepository;
    /** @var DeclareDepartResponseRepository */
    protected $declareDepartResponseRepository;
    /** @var DeclareExportRepository */
    protected $declareExportRepository;
    /** @var DeclareExportResponseRepository */
    protected $declareExportResponseRepository;
    /** @var DeclareLossRepository */
    protected $declareLossRepository;
    /** @var DeclareLossResponseRepository */
    protected $declareLossResponseRepository;
    /** @var DeclareTagsTransferRepository */
    protected $declareTagsTransferRepository;
    /** @var DeclareTagsTransferResponseRepository */
    protected $declareTagsTransferResponseRepository;
    /** @var DeclareTagReplaceRepository */
    protected $declareTagReplaceRepository;
    /** @var DeclareTagReplaceResponseRepository */
    protected $declareTagReplaceResponseRepository;

    /** @var EmployeeRepository */
    protected $employeeRepository;
    /** @var LocationRepository */
    protected $locationRepository;
    /** @var TagRepository */
    protected $tagRepository;
    /** @var TokenRepository */
    protected $tokenRepository;
    

    public function __construct(EntityManagerInterface $em, IRSerializer $serializer,
                                CacheService $cacheService, UserService $userService)
    {
        $this->em = $em;
        $this->serializer = $serializer;
        $this->cacheService = $cacheService;
        $this->userService = $userService;

        $this->conn = $this->em->getConnection();

        $this->animalRepository = $this->em->getRepository(Animal::class);
        $this->clientRepository = $this->em->getRepository(Client::class);

        $this->declareBaseRepository = $this->em->getRepository(DeclareBase::class);
        $this->declareBaseResponseRepository = $this->em->getRepository(DeclareBaseResponse::class);
        $this->declareNsfoBaseRepository = $this->em->getRepository(DeclareNsfoBase::class);

        $this->declareArrivalRepository = $this->em->getRepository(DeclareArrival::class);
        $this->declareArrivalResponseRepository = $this->em->getRepository(DeclareArrivalResponse::class);
        $this->declareImportRepository = $this->em->getRepository(DeclareImport::class);
        $this->declareImportResponseRepository = $this->em->getRepository(DeclareImportResponse::class);
        $this->declareDepartRepository = $this->em->getRepository(DeclareDepart::class);
        $this->declareDepartResponseRepository = $this->em->getRepository(DeclareDepartResponse::class);
        $this->declareExportRepository = $this->em->getRepository(DeclareExport::class);
        $this->declareExportResponseRepository = $this->em->getRepository(DeclareExportResponse::class);
        $this->declareLossRepository = $this->em->getRepository(DeclareLoss::class);
        $this->declareLossResponseRepository = $this->em->getRepository(DeclareLossResponse::class);
        $this->declareTagsTransferRepository = $this->em->getRepository(DeclareTagsTransfer::class);
        $this->declareTagsTransferResponseRepository = $this->em->getRepository(DeclareTagsTransferResponse::class);
        $this->declareTagReplaceRepository = $this->em->getRepository(DeclareTagReplace::class);
        $this->declareTagReplaceResponseRepository = $this->em->getRepository(DeclareTagReplaceResponse::class);

        $this->employeeRepository = $this->em->getRepository(Employee::class);
        $this->locationRepository = $this->em->getRepository(Location::class);
        $this->tagRepository = $this->em->getRepository(Tag::class);
        $this->tokenRepository = $this->em->getRepository(Token::class);
    }


    /**
     * Clears the redis cache for the Livestock of a given location , to reflect changes of animals on Livestock.
     *
     * @param Location $location
     * @param Animal | Ewe | Ram | Neuter $animal
     */
    protected function clearLivestockCacheForLocation(Location $location = null, $animal = null) {
        $this->cacheService->clearLivestockCacheForLocation($location, $animal);
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
        $this->em->getRepository($repositoryEntityNameSpace)->persist($messageObject);

        return $messageObject;
    }


    /**
     * @param $object
     * @return mixed
     */
    protected function persistAndFlush($object)
    {
        $this->em->persist($object);
        $this->em->flush();
        return $object;
    }


    /**
     */
    protected function flushClearAndGarbageCollect()
    {
        $this->em->flush();
        $this->em->clear();
        gc_collect_cycles();
    }


    /**
     * @return Client|Employee|\AppBundle\Entity\Person
     */
    public function getUser()
    {
        return $this->userService->getUser();
    }


    /**
     * @param Request $request
     * @return Client|null
     */
    public function getAccountOwner(Request $request = null)
    {
        return $this->userService->getAccountOwner($request);
    }


    /**
     * @param string $tokenCode
     * @return Employee|null
     */
    public function getEmployee($tokenCode = null)
    {
        return $this->userService->getEmployee($tokenCode);
    }


    /**
     * @param Request $request
     * @return Location|null
     */
    public function getSelectedLocation(Request $request)
    {
        return $this->userService->getSelectedLocation($request);
    }


    /**
     * @param Request $request
     * @return string|null
     */
    public function getSelectedUbn(Request $request)
    {
        return $this->userService->getSelectedUbn($request);
    }
}