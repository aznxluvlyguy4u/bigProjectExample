<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Client;
use AppBundle\Entity\Country;
use AppBundle\Entity\DeclareAnimalFlag;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Entity\EditType;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Person;
use AppBundle\Entity\Ram;
use AppBundle\Entity\RetrieveAnimals;
use AppBundle\Entity\RetrieveCountries;
use AppBundle\Entity\RetrieveTags;
use AppBundle\Entity\RetrieveUbnDetails;
use AppBundle\Entity\RevokeDeclaration;
use AppBundle\Entity\Token;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Enumerator\TokenType;
use AppBundle\SqlView\SqlViewManagerInterface;
use AppBundle\Util\NullChecker;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Util\Json;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
    /** @var TranslatorInterface */
    protected $translator;
    /** @var Logger */
    private $logger;
    /** @var SqlViewManagerInterface */
    private $sqlViewManager;
    /** @var RegistryInterface */
    private $doctrine;
    /** @var ValidatorInterface */
    private $validator;

    /** @var string */
    private $actionLogEditMessage;

    public function __construct(BaseSerializer $baseSerializer,
                                CacheService $cacheService,
                                RegistryInterface $doctrine,
                                UserService $userService,
                                TranslatorInterface $translator,
                                Logger $logger,
                                SqlViewManagerInterface $sqlViewManager,
                                ValidatorInterface $validator
    )
    {
        $this->baseSerializer = $baseSerializer;
        $this->cacheService = $cacheService;
        $this->doctrine = $doctrine;
        $this->manager = $doctrine->getManager();
        $this->userService = $userService;
        $this->translator = $translator;
        $this->logger = $logger;
        $this->sqlViewManager = $sqlViewManager;
        $this->validator = $validator;
    }


    /**
     * @return ObjectManager|EntityManagerInterface
     */
    public function getManager()
    {
        return $this->manager;
    }


    public function resetManager(): void
    {
        $this->doctrine->resetManager();
    }

    /**
     * @return SqlViewManagerInterface
     */
    public function getSqlViewManager(): SqlViewManagerInterface
    {
        return $this->sqlViewManager;
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
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }


    /**
     * @return ValidatorInterface
     */
    public function getValidator(): ValidatorInterface
    {
        return $this->validator;
    }


    /**
     * @param \Exception $exception
     */
    public function logExceptionAsError($exception)
    {
        $this->getLogger()->error($exception->getMessage());
        $this->getLogger()->error($exception->getTraceAsString());
    }


    /**
     * Clears the redis cache for the Livestock of a given location , to reflect changes of animals on Livestock.
     *
     * @param Location $location
     * @param Animal | Ewe | Ram | Neuter $animal
     */
    protected function clearLivestockCacheForLocation(?Location $location = null, $animal = null) {
        if ($location || $animal) {
            $this->cacheService->clearLivestockCacheForLocation($location, $animal);
        }
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
     * @return null|DeclareArrival|DeclareImport|DeclareExport|DeclareDepart|DeclareBirth|DeclareLoss|DeclareAnimalFlag|DeclareTagsTransfer|RetrieveTags|RevokeDeclaration|RetrieveAnimals|RetrieveAnimals|RetrieveCountries|RetrieveUbnDetails
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
        $content = RequestUtil::getContentAsArrayCollection($request);

        //Get token header to read token value
        if($request->headers->has(Constant::ACCESS_TOKEN_HEADER_NAMESPACE)) {

            $environment = $content->get('env');
            $tokenCode = $request->headers->get(Constant::ACCESS_TOKEN_HEADER_NAMESPACE);
            $token = $this->getManager()->getRepository(Token::class)
                ->findOneBy(array("code" => $tokenCode, "type" => TokenType::ACCESS));

            $response = $this->getUnauthorizedResponse();

            if ($token != null) {

                switch ($environment) {
                    case 'USER':
                        $response = $this->getUserEnvironmentAccessTokenValidationResponse($request, $token->getOwner(), $tokenCode);
                        break;

                    case 'ADMIN':
                        $response = $this->getAdminEnvironmentAccessTokenValidationResponse($token->getOwner(), $tokenCode);
                        break;

                    default:
                        break;
                }
            }

            return $response;
        }

        return new JsonResponse([
            'error'=> Response::HTTP_UNAUTHORIZED,
            'errorMessage'=> 'Mandatory AccessToken header was not provided'
        ], Response::HTTP_UNAUTHORIZED);
    }


    /**
     * @param Request $request
     * @param Person $owner
     * @param string $tokenCode
     * @return JsonResponse
     */
    private function getUserEnvironmentAccessTokenValidationResponse(Request $request, $owner, $tokenCode): JsonResponse
    {
        if ($owner instanceof Client) {
            return $this->getTokenResponse($tokenCode);

        } elseif ($owner instanceof Employee ) {
            $ghostTokenCode = $request->headers->get(Constant::GHOST_TOKEN_HEADER_NAMESPACE);
            $ghostToken = $this->getManager()->getRepository(Token::class)
                ->findOneBy(array("code" => $ghostTokenCode, "type" => TokenType::GHOST));

            if ($ghostToken != null) {
                return $this->getTokenResponse($tokenCode);
            }
        }
        return $this->getUnauthorizedResponse();
    }


    /**
     * @param Person $owner
     * @param string $tokenCode
     * @return JsonResponse
     */
    private function getAdminEnvironmentAccessTokenValidationResponse($owner, $tokenCode): JsonResponse
    {
        if ($owner instanceof Employee) {
            return $this->getTokenResponse($tokenCode);
        }
        return $this->getUnauthorizedResponse();
    }


    private function getUnauthorizedResponse(): JsonResponse
    {
        return new JsonResponse([
            'error'=> Response::HTTP_UNAUTHORIZED,
            'errorMessage'=> 'No AccessToken provided'
        ], Response::HTTP_UNAUTHORIZED);
    }


    /**
     * @param string $tokenCode
     * @param string $tokenStatus
     * @return JsonResponse
     */
    private function getTokenResponse($tokenCode, $tokenStatus = 'valid'): JsonResponse
    {
        return new JsonResponse([
            'token_status' => $tokenStatus,
            'token' => $tokenCode
        ], Response::HTTP_OK);
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


    /**
     * @param string $string
     * @return string
     */
    protected function translateUcFirstLower($string)
    {
        return self::translateWithUcFirstLower($this->translator, $string);
    }


    public static function translateWithUcFirstLower(TranslatorInterface $translator, $string)
    {
        return ucfirst(
            strtr(
                strtolower($translator->trans($string)),
                StringUtil::capitalizationSet()
            )
        );
    }


    /**
     * @param Animal $animal
     * @param array $jmsGroups
     * @param boolean $enableMaxDepthChecks
     * @param boolean $includeParentsInLitter
     * @return array
     */
    public function getDecodedJsonOfAnimalWithParents(Animal $animal, array $jmsGroups = [], $enableMaxDepthChecks, $includeParentsInLitter = false)
    {
        $serializedAnimal = $this->getBaseSerializer()->getDecodedJson($animal, $jmsGroups, $enableMaxDepthChecks);

        if ($animal->getParentFather() !== null) {
            $serializedAnimal['parent_father'] = $this->getBaseSerializer()->getDecodedJson($animal->getParentFather(), [JmsGroup::PARENT_DATA]);
        }
        if ($animal->getParentMother() !== null) {
            $serializedAnimal['parent_mother'] = $this->getBaseSerializer()->getDecodedJson($animal->getParentMother(), [JmsGroup::PARENT_DATA]);
        }
        if ($animal->getSurrogate() !== null) {
            $serializedAnimal['surrogate'] = $this->getBaseSerializer()->getDecodedJson($animal->getSurrogate(), [JmsGroup::PARENT_DATA]);
        }

        $litter = $animal->getLitter();
        if ($includeParentsInLitter && $litter !== null) {
            if ($litter->getAnimalFather() !== null) {
                $serializedAnimal['litter']['animal_father'] = $this->getBaseSerializer()->getDecodedJson($litter->getAnimalFather(), [JmsGroup::PARENT_DATA]);
            }
            if ($litter->getAnimalMother() !== null) {
                $serializedAnimal['litter']['animal_mother'] = $this->getBaseSerializer()->getDecodedJson($litter->getAnimalMother(), [JmsGroup::PARENT_DATA]);
            }
        }

        return $serializedAnimal;
    }


    protected function activateFilter($filterName)
    {
        if (!$this->getManager()->getFilters()->isEnabled($filterName)) {
            $this->getManager()->getFilters()->enable($filterName);
        }
    }


    protected function deactivateFilter($filterName)
    {
        if ($this->getManager()->getFilters()->isEnabled($filterName)) {
            $this->getManager()->getFilters()->disable($filterName);
        }
    }


    /**
     * @param int $editTypeEnum
     * @return EditType|null
     */
    protected function getEditTypeByEnum(int $editTypeEnum): ?EditType
    {
        return $this->getManager()->getRepository(EditType::class)->getEditType($editTypeEnum);
    }


    /**
     * @param string $countryName
     * @return Country
     */
    protected function getCountryByName($countryName): Country
    {
        $country = $this->getManager()->getRepository(Country::class)->getCountryByName($countryName);
        if (!$country) {
            throw new PreconditionFailedHttpException(
                $this->translator->trans('COUNTRY DOES NOT EXIST IN THE DATABASE').
                ': '.strval($country)
            );
        }
        return $country;
    }


    /**
     * @param Client|null $client
     */
    protected function nullCheckClient(?Client $client)
    {
        NullChecker::checkClient($client);
    }


    /**
     * @param Location|null $location
     */
    protected function nullCheckLocation(?Location $location)
    {
        NullChecker::checkLocation($location);
    }
}
