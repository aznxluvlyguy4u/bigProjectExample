<?php


namespace AppBundle\Service;


use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Person;
use AppBundle\Entity\Token;
use AppBundle\Entity\TokenRepository;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Validation\AdminValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserService
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var TokenRepository */
    private $tokenRepository;
    /** @var TokenStorageInterface */
    private $tokenStorage;

    public function __construct(EntityManagerInterface $em, TokenStorageInterface $tokenStorage)
    {
        $this->em = $em;
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
     * @return Person|Employee|Client
     *
     * @throws \LogicException If SecurityBundle is not available
     *
     * @see TokenInterface::getUser()
     */
    public function getUser()
    {
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
     * @return Client|null
     */
    public function getAccountOwner(Request $request = null)
    {
        $loggedInUser = $this->getUser();

        /* Clients */
        if($loggedInUser instanceof Client) {
            return $loggedInUser;

            /* Admins with a GhostToken */
        } else if ($loggedInUser instanceof Employee) {

            if($request->headers->has(Constant::GHOST_TOKEN_HEADER_NAMESPACE)) {
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
     * @return Employee|null
     */
    public function getEmployee()
    {
        if ($this->getUser() instanceof Employee) {
            return $this->getUser();
        }

        return null;
        /* Note that returning null will break a lot of code in the controllers. That is why it is essential that both the AccessToken and _verified_ GhostToken
          are validated in the TokenAuthenticator Prehook.
        */

    }
}