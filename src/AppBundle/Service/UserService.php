<?php


namespace AppBundle\Service;


use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use AppBundle\Entity\PersonRepository;
use AppBundle\Entity\Token;
use AppBundle\Entity\TokenRepository;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Util\Finder;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\HeaderValidation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserService
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var PersonRepository */
    private $personRepository;
    /** @var TokenRepository */
    private $tokenRepository;
    /** @var TokenStorageInterface */
    private $tokenStorage;

    public function __construct(EntityManagerInterface $em, TokenStorageInterface $tokenStorage)
    {
        $this->em = $em;
        $this->personRepository = $em->getRepository(Person::class);
        $this->tokenRepository = $em->getRepository(Token::class);
        $this->tokenStorage = $tokenStorage;
    }


    /**
     * @param string $accessLevelType
     * @return bool
     */
    public function isAdminUser($accessLevelType = AccessLevelType::ADMIN)
    {
        return AdminValidator::isAdmin($this->getUser(), $accessLevelType);
    }


    /**
     * Get a user from the Security Token Storage.
     *
     * @param string|null $tokenCode
     *
     * @return Person|Employee|Client
     *
     * @throws \LogicException If SecurityBundle is not available
     *
     * @see TokenInterface::getUser()
     */
    public function getUser($tokenCode = null)
    {
        if ($tokenCode) {
            return $this->personRepository->findOneByAccessTokenCode($tokenCode);
        }

        if (!$this->tokenStorage) {
            throw new \LogicException('The SecurityBundle is not registered in your application.');
        }

        if (null === $token = $this->tokenStorage->getToken()) {
            return;
        }

        if (!is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return;
        }

        return $user;
    }


    /**
     * @param Request $request
     * @param string|null $tokenCode
     * @return Client|null
     * @throws \Exception
     */
    public function getAccountOwner(Request $request = null, $tokenCode = null)
    {
        $loggedInUser = $this->getUser($tokenCode);

        /* Clients */
        if($loggedInUser instanceof Client) {
            return $loggedInUser;

            /* Admins with a GhostToken */
        } else if ($loggedInUser instanceof Employee) {

            if ($request === null) {
                throw new \Exception('Request cannot be empty for getAccountOwner if (loggedIn)User is not a Client');
            }

            if($request->headers->has(Constant::GHOST_TOKEN_HEADER_NAMESPACE) && $tokenCode === null) {
                $ghostTokenCode = $request->headers->get(Constant::GHOST_TOKEN_HEADER_NAMESPACE);
                $ghostToken = $this->tokenRepository->findOneBy(array("code" => $ghostTokenCode));

                if($ghostToken != null) {
                    if($ghostToken->getIsVerified()) {
                        return $ghostToken->getOwner(); //client
                    }
                }
                //Admins without a GhostToken
                return null;
            }

        } else {
            return null;
            /* Note that returning null will break a lot of code in the controllers. That is why it is essential that both the AccessToken and _verified_ GhostToken
             are validated in the TokenAuthenticator Prehook.
             At this point only Clients and Employees can login to the system. Not Inspectors.
            */
        }
    }


    /**
     * @param string|null $tokenCode
     * @return Client|Employee|Person|null
     */
    public function getEmployee($tokenCode = null)
    {
        $user = $this->getUser($tokenCode);

        if ($user instanceof Employee) {
            return $user;
        }

        return null;
        /* Note that returning null will break a lot of code in the controllers. That is why it is essential that both the AccessToken and _verified_ GhostToken
          are validated in the TokenAuthenticator Prehook.
        */

    }


    /**
     * @param Request $request
     * @return Location|null
     */
    public function getSelectedLocation(Request $request)
    {
        $client = $this->getAccountOwner($request);
        $headerValidation = null;

        if($client) {
            $headerValidation = new HeaderValidation($this->em, $request, $client);
        }

        if($headerValidation) {
            if($headerValidation->isInputValid()) {
                return $headerValidation->getLocation();
            } else {
                $locations = Finder::findLocationsOfClient($client);
                if($locations->count() > 0) {
                    //pick the first available Location as default
                    return $locations->get(0);
                } else {
                    return null;
                }
            }
        }

        return null;
    }

}