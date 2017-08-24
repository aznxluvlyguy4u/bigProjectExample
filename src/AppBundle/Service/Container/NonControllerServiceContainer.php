<?php

namespace AppBundle\Service\Container;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\RequestMessageBuilder;
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
use AppBundle\Entity\Person;
use AppBundle\Entity\Ram;
use AppBundle\Entity\RetrieveAnimals;
use AppBundle\Entity\RetrieveCountries;
use AppBundle\Entity\RetrieveTags;
use AppBundle\Entity\RetrieveUbnDetails;
use AppBundle\Entity\RevokeDeclaration;
use AppBundle\Entity\Token;
use AppBundle\Enumerator\TokenType;
use AppBundle\Service\AnimalLocationHistoryService;
use AppBundle\Service\AwsExternalQueueService;
use AppBundle\Service\AwsInternalQueueService;
use AppBundle\Service\AWSSimpleStorageService;
use AppBundle\Service\CacheService;
use AppBundle\Service\Container\RepositoryContainerBase;
use AppBundle\Service\CsvFromSqlResultsWriterService;
use AppBundle\Service\EmailService;
use AppBundle\Service\EntityGetter;
use AppBundle\Service\HealthService;
use AppBundle\Service\IRSerializer;
use AppBundle\Service\UserService;
use AppBundle\Util\RequestUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\GeneratorInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Templating\EngineInterface;

/**
 * Class NonControllerServiceContainer
 */
class NonControllerServiceContainer extends RepositoryContainerBase
{
    /* Injected Services */

    /** @var AnimalLocationHistoryService */
    protected $animalLocationHistoryService;
    /** @var CacheService */
    protected $cacheService;
    /** @var CsvFromSqlResultsWriterService */
    protected $csvWriter;
    /** @var EntityManagerInterface|ObjectManager */
    protected $manager;
    /** @var EmailService */
    protected $emailService;
    /** @var UserPasswordEncoderInterface */
    protected $encoder;
    /** @var EntityGetter */
    protected $entityGetter;
    /** @var AwsExternalQueueService */
    protected $externalQueueService;
    /** @var HealthService */
    protected $healthService;
    /** @var AwsInternalQueueService */
    protected $internalQueueService;
    /** @var GeneratorInterface */
    protected $knpSnappy;
    /** @var Logger */
    protected $logger;
    /** @var RequestMessageBuilder */
    protected $requestMessageBuilder;
    /** @var IRSerializer */
    protected $iRserializer;
    /** @var AWSSimpleStorageService */
    protected $storageService;
    /** @var EngineInterface */
    protected $templating;
    /** @var UserService */
    protected $userService;


    /* Injected Parameters */

    /** @var string */
    protected $cacheDir;
    /** @var string */
    protected $environment;
    /** @var string */
    protected $rootDir;


    /* Classes from Services */

    /** @var Connection */
    protected $connection;


    /* Constructor Initialized Classes */

    /** @var Finder */
    protected $finder;
    /** @var Filesystem */
    protected $fs;



    /*
     * WARNING! DO NOT INJECT SERVICES PRIMARILY RELATED TO CONTROLLERS HERE!
     */
    public function __construct(
        AnimalLocationHistoryService $animalLocationHistoryService,
        AwsExternalQueueService $externalQueueService,
        AwsInternalQueueService $internalQueueService,
        AWSSimpleStorageService $storageService,
        CacheService $cacheService,
        CsvFromSqlResultsWriterService $csvFromSqlResultsWriterService,
        EmailService $emailService,
        EngineInterface $templating,
        EntityGetter $entityGetter,
        EntityManagerInterface $manager,
        GeneratorInterface $knpSnappyGenerator,
        HealthService $healthService,
        IRSerializer $serializer,
        Logger $logger,
        RequestMessageBuilder $requestMessageBuilder,
        UserPasswordEncoderInterface $userPasswordEncoder,
        UserService $userService,
        $cacheDir,
        $environment,
        $rootDir
    )
    {
        parent::__construct($manager);

        /* Injected Services */
        $this->animalLocationHistoryService = $animalLocationHistoryService;
        $this->cacheService = $cacheService;
        $this->csvWriter = $csvFromSqlResultsWriterService;
        $this->manager = $manager;
        $this->emailService = $emailService;
        $this->encoder = $userPasswordEncoder;
        $this->entityGetter = $entityGetter;
        $this->externalQueueService = $externalQueueService;
        $this->healthService = $healthService;
        $this->internalQueueService = $internalQueueService;
        $this->knpSnappy = $knpSnappyGenerator;
        $this->logger = $logger;
        $this->requestMessageBuilder = $requestMessageBuilder;
        $this->iRserializer = $serializer;
        $this->storageService = $storageService;
        $this->templating = $templating;
        $this->userService = $userService;

        /* Injected Parameters */
        $this->cacheDir = $cacheDir;
        $this->environment = $environment;
        $this->rootDir = $rootDir;

        /* Classes from Services */
        $this->connection = $this->manager->getConnection();

        /* Constructor Initialized Classes */
        $this->finder = new Finder();
        $this->fs = new Filesystem();
    }


    /* Services */


    /**
     * @return AnimalLocationHistoryService
     */
    public function getAnimalLocationHistoryService()
    {
        return $this->animalLocationHistoryService;
    }

    /**
     * @return CsvFromSqlResultsWriterService
     */
    public function getCsvWriter()
    {
        return $this->csvWriter;
    }

    /**
     * @return EmailService
     */
    public function getEmailService()
    {
        return $this->emailService;
    }

    /**
     * @return UserPasswordEncoderInterface
     */
    public function getEncoder()
    {
        return $this->encoder;
    }

    /**
     * @return EntityGetter
     */
    public function getEntityGetter()
    {
        return $this->entityGetter;
    }

    /**
     * @return AwsExternalQueueService
     */
    public function getExternalQueueService()
    {
        return $this->externalQueueService;
    }

    /**
     * @return HealthService
     */
    public function getHealthService()
    {
        return $this->healthService;
    }

    /**
     * @return AwsInternalQueueService
     */
    public function getInternalQueueService()
    {
        return $this->internalQueueService;
    }

    /**
     * @return IRSerializer
     */
    public function getIRSerializer()
    {
        return $this->iRserializer;
    }

    /**
     * @return CacheService
     */
    public function getCacheService()
    {
        return $this->cacheService;
    }

    /**
     * @return GeneratorInterface
     */
    public function getKnpSnappy()
    {
        return $this->knpSnappy;
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return ObjectManager|EntityManagerInterface
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @return RequestMessageBuilder
     */
    public function getRequestMessageBuilder()
    {
        return $this->requestMessageBuilder;
    }

    /**
     * @return AWSSimpleStorageService
     */
    public function getStorageService()
    {
        return $this->storageService;
    }

    /**
     * @return UserService
     */
    public function getUserService()
    {
        return $this->userService;
    }

    /**
     * @return EngineInterface
     */
    public function getTemplating()
    {
        return $this->templating;
    }



    /* Classes from Services */

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }


    /* Constructor Initialized Classes */

    /**
     * @return Finder
     */
    public function getFinder()
    {
        return $this->finder;
    }

    /**
     * @return Filesystem
     */
    public function getFs()
    {
        return $this->fs;
    }



    /* Parameters */

    /**
     * @return string
     */
    public function getCacheDir()
    {
        return $this->cacheDir;
    }

    /**
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @return string
     */
    public function getRootDir()
    {
        return $this->rootDir;
    }



    /* Base Functions */

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
        $this->manager->getRepository($repositoryEntityNameSpace)->persist($messageObject);

        return $messageObject;
    }


    /**
     *
     * @param Person $person
     * @param int $passwordLength
     * @return string
     */
    public function persistNewPassword($person, $passwordLength = 9)
    {
        $newPassword = Utils::randomString($passwordLength);

        $encodedNewPassword = $this->getEncoder()->encodePassword($person, $newPassword);
        $person->setPassword($encodedNewPassword);

        $this->getManager()->persist($person);
        $this->getManager()->flush();

        return $newPassword;
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


    /**
     * @param $object
     * @return mixed
     */
    protected function persistAndFlush($object)
    {
        $this->manager->persist($object);
        $this->manager->flush();
        return $object;
    }


    /**
     */
    protected function flushClearAndGarbageCollect()
    {
        $this->manager->flush();
        $this->manager->clear();
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