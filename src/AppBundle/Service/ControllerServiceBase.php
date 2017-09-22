<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
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
use AppBundle\Entity\Token;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Enumerator\TokenType;
use AppBundle\Output\AnimalDetailsOutput;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ControllerServiceBase
 */
abstract class ControllerServiceBase
{
    private $baseSerializer;
    /** @var EntityManagerInterface */
    private $manager;
    /** @var CacheService */
    private $cacheService;
    /** @var UserService */
    private $userService;

    /** @var string */
    private $actionLogEditMessage;

    public function __construct(BaseSerializer $baseSerializer,
                                CacheService $cacheService,
                                EntityManagerInterface $manager,
                                UserService $userService)
    {
        $this->baseSerializer = $baseSerializer;
        $this->cacheService = $cacheService;
        $this->manager = $manager;
        $this->userService = $userService;
    }


    /**
     * @return ObjectManager|EntityManagerInterface
     */
    public function getManager()
    {
        return $this->manager;
    }


    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->manager->getConnection();
    }


    /**
     * @return BaseSerializer
     */
    public function getBaseSerializer()
    {
        return $this->baseSerializer;
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
     * @return CacheService
     */
    public function getCacheService()
    {
        return $this->cacheService;
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
     * @param Request $request
     * @return JsonResponse
     */
    public function isAccessTokenValid(Request $request)
    {
        $token = null;
        $response = null;
        $content = RequestUtil::getContentAsArray($request);

        //Get token header to read token value
        if($request->headers->has(Constant::ACCESS_TOKEN_HEADER_NAMESPACE)) {

            $environment = $content->get('env');
            $tokenCode = $request->headers->get(Constant::ACCESS_TOKEN_HEADER_NAMESPACE);
            $token = $this->getManager()->getRepository(Token::class)
                ->findOneBy(array("code" => $tokenCode, "type" => TokenType::ACCESS));

            if ($token != null) {
                if ($environment == 'USER') {
                    if ($token->getOwner() instanceof Client) {
                        $response = array(
                            'token_status' => 'valid',
                            'token' => $tokenCode
                        );
                        return new JsonResponse($response, 200);
                    } elseif ($token->getOwner() instanceof Employee ) {
                        $ghostTokenCode = $request->headers->get(Constant::GHOST_TOKEN_HEADER_NAMESPACE);
                        $ghostToken = $this->getManager()->getRepository(Token::class)
                            ->findOneBy(array("code" => $ghostTokenCode, "type" => TokenType::GHOST));

                        if($ghostToken != null) {
                            $response = array(
                                'token_status' => 'valid',
                                'token' => $tokenCode
                            );
                            return new JsonResponse($response, 200);
                        }
                    } else {
                        $response = array(
                            'error' => 401,
                            'errorMessage' => 'No AccessToken provided'
                        );
                    }
                }
            }

            if ($environment == 'ADMIN') {
                if ($token->getOwner() instanceof Employee) {
                    $response = array(
                        'token_status' => 'valid',
                        'token' => $tokenCode
                    );
                    return new JsonResponse($response, 200);
                } else {
                    $response = array(
                        'error' => 401,
                        'errorMessage' => 'No AccessToken provided'
                    );
                }
            }

            $response = array(
                'error'=> 401,
                'errorMessage'=> 'No AccessToken provided'
            );
        } else {
            //Mandatory AccessToken was not provided
            $response = array(
                'error'=> 401,
                'errorMessage'=> 'Mandatory AccessToken header was not provided'
            );
        }

        return new JsonResponse($response, 401);
    }


    protected function clearActionLogEditMessage()
    {
        $this->actionLogEditMessage = '';
    }


    /**
     * @param string $type
     * @param string $oldValue
     * @param string $newValue
     */
    protected function updateActionLogEditMessage($type, $oldValue, $newValue)
    {
        if ($oldValue !== $newValue) {
            $prefix = $this->actionLogEditMessage === '' ? '' : ', ';
            $this->actionLogEditMessage = $this->actionLogEditMessage . $prefix . $type . ': '.$oldValue.' => '.$newValue;
        }
    }


    /**
     * @return string
     */
    protected function getActionLogEditMessage()
    {
        return $this->actionLogEditMessage;
    }


    /**
     * @param Animal $animal
     * @return JsonResponse
     */
    protected function getAnimalDetailsOutputForUserEnvironment(Animal $animal)
    {
        $output = AnimalDetailsOutput::create($this->getManager(), $animal);
        return ResultUtil::successResult($output);
    }


    /**
     * @param Animal $animal
     * @return JsonResponse
     */
    protected function getAnimalDetailsOutputForAdminEnvironment($animal)
    {
        return ResultUtil::successResult($this->getDecodedJsonForAnimalDetailsOutputFromAdminEnvironment($animal));
    }


    /**
     * @param Animal|Ram|Ewe|Neuter $animal
     * @return array
     */
    private function getDecodedJsonForAnimalDetailsOutputFromAdminEnvironment($animal)
    {
        $decodedLitters = [];
        if($animal instanceof Ram || $animal instanceof Ewe) {
            $decodedLitters = $this->getBaseSerializer()->getDecodedJson($animal->getLitters(), [JmsGroup::BASIC, JmsGroup::PARENTS]);
        }

        return [
            JsonInputConstant::ANIMAL => $this->getBaseSerializer()->getDecodedJson($animal, [JmsGroup::ANIMAL_DETAILS, JmsGroup::PARENTS]),
            JsonInputConstant::CHILDREN => $this->getBaseSerializer()->getDecodedJson($animal->getChildren(), [JmsGroup::BASIC]),
            JsonInputConstant::ANIMAL_RESIDENCE_HISTORY => $this->getBaseSerializer()->getDecodedJson($animal->getAnimalResidenceHistory(), [JmsGroup::BASIC]),
            JsonInputConstant::LITTERS => $decodedLitters,
        ];
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