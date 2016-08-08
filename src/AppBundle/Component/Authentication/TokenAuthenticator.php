<?php

namespace AppBundle\Component\Authentication;

use AppBundle\Entity\Token;
use AppBundle\Enumerator\TokenType;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use AppBundle\Component\HttpFoundation\JsonResponse;

/**
 * Class TokenAuthenticator
 * @package AppBundle\Component\Authentication
 */
class TokenAuthenticator extends AbstractGuardAuthenticator  {

  const ACCESS_TOKEN_HEADER_NAMESPACE = 'AccessToken';
  const GHOST_TOKEN_HEADER_NAMESPACE = 'GhostToken';
  const PERSON_SHORT_ENTITY_PATH = 'AppBundle:Person';

  /**
   * @var HttpUtils
   */
  protected $httpUtils;

  /**
   * @var array
   */
  private $unAuthedPaths;

  /**
   * @var EntityManager
   */
  private $entityManager;

  public function __construct(EntityManager $entityManager, HttpUtils $httpUtils, $unAuthedPaths = array())
  {
    $this->entityManager = $entityManager;
    $this->httpUtils = $httpUtils;
    $this->unAuthedPaths = $unAuthedPaths;
  }

  /**
   * Returns a response that directs the user to authenticate.
   *
   * This is called when an anonymous request accesses a resource that
   * requires authentication. The job of this method is to return some
   * response that "helps" the user start into the authentication process.
   *
   * Examples:
   *  A) For a form login, you might redirect to the login page
   *      return new RedirectResponse('/login');
   *  B) For an API token authentication system, you return a 401 response
   *      return new Response('Auth header required', 401);
   *
   * @param Request $request The request that resulted in an AuthenticationException
   * @param AuthenticationException $authException The exception that started the authentication process
   *
   * @return Response
   */
  public function start(Request $request, AuthenticationException $authException = null) {
    $response = null;

    if(!$request->headers->has($this::ACCESS_TOKEN_HEADER_NAMESPACE)) {
      $response = array(
        'errorCode' => 401,
        'errorMessage' => 'Unauthorized, no AccessToken provided'
      );
    } else {
      $response = array(
        'errorCode' => 401,
        'errorMessage' => 'Unauthorized, AccessToken invalid'
      );
    }

    return new JsonResponse($response, 401);
  }

  /**
   * Get the authentication credentials from the request and return them
   * as any type (e.g. an associate array). If you return null, authentication
   * will be skipped.
   *
   * Whatever value you return here will be passed to getUser() and checkCredentials()
   *
   * For example, for a form login, you might:
   *
   *      return array(
   *          'username' => $request->request->get('_username'),
   *          'password' => $request->request->get('_password'),
   *      );
   *
   * Or for an API token that's on a header, you might use:
   *
   *      return array('api_key' => $request->headers->get('X-API-TOKEN'));
   *
   * @param Request $request
   *
   * @return mixed|null
   */
  public function getCredentials(Request $request) {
    //Get auth header to read token
    if($request->headers->has($this::ACCESS_TOKEN_HEADER_NAMESPACE)) {
      $token = $request->headers->get($this::ACCESS_TOKEN_HEADER_NAMESPACE);

      if($request->headers->has($this::GHOST_TOKEN_HEADER_NAMESPACE)) {
        $ghostToken = $request->headers->get($this::GHOST_TOKEN_HEADER_NAMESPACE);

        return array(
            'accessToken' => $token,
            'ghostToken' => $ghostToken
        );
      }

      // What you return here will be passed to getUser() as $credentials
      return array(
        'accessToken' => $token
      );
    }

    return null;
  }

  /**
   * Return a UserInterface object based on the credentials.
   *
   * The *credentials* are the return value from getCredentials()
   *
   * You may throw an AuthenticationException if you wish. If you return
   * null, then a UsernameNotFoundException is thrown for you.
   *
   * @param mixed $credentials
   * @param UserProviderInterface $userProvider
   *
   * @throws AuthenticationException
   *
   * @return UserInterface|null
   */
  public function getUser($credentials, UserProviderInterface $userProvider) {
    $accessTokenCode = $credentials['accessToken'];

    // if null, authentication will fail
    // if a User object, checkCredentials() is called
    $accessToken = $this->entityManager->getRepository(Token::class)
        ->findOneBy(array('code' => $accessTokenCode));

    $user = null;
    if ($accessToken != null) {
      $user = $accessToken->getOwner();
    }
    //Also verify the ghostToken and return null, if ghostToken is not valid
    if (array_key_exists('ghostToken', $credentials) && $accessToken != null) {
      $ghostTokenCode = $credentials['ghostToken'];
      $ghostToken = $this->entityManager->getRepository(Token::class)
          ->findOneBy(array('code' => $ghostTokenCode));

      if ($ghostToken == null) {
        $user = null; //deny access
      } else {
        if ($ghostToken->getType() != TokenType::GHOST || !$ghostToken->getIsVerified()) {
          $user = null; //deny access
        }
      }
    }

    return $user;
  }

  /**
   * Returns true if the credentials are valid.
   *
   * If any value other than true is returned, authentication will
   * fail. You may also throw an AuthenticationException if you wish
   * to cause authentication to fail.
   *
   * The *credentials* are the return value from getCredentials()
   *
   * @param mixed $credentials
   * @param UserInterface $user
   *
   * @return bool
   *
   * @throws AuthenticationException
   */
  public function checkCredentials($credentials, UserInterface $user) {
    return true;
  }

  /**
   * Called when authentication executed, but failed (e.g. wrong username password).
   *
   * This should return the Response sent back to the user, like a
   * RedirectResponse to the login page or a 403 response.
   *
   * If you return null, the request will continue, but the user will
   * not be authenticated. This is probably not what you want to do.
   *
   * @param Request $request
   * @param AuthenticationException $exception
   *
   * @return Response|null
   */
  public function onAuthenticationFailure(Request $request, AuthenticationException $exception) {
    $response = array(
      'errorCode'=> 403,
      'errorMessage' => 'Forbidden, invalid AccessToken provided'
    );

    return new JsonResponse($response, 403);
  }

  /**
   * Called when authentication executed and was successful!
   *
   * This should return the Response sent back to the user, like a
   * RedirectResponse to the last page they visited.
   *
   * If you return null, the current request will continue, and the user
   * will be authenticated. This makes sense, for example, with an API.
   *
   * @param Request $request
   * @param TokenInterface $token
   * @param string $providerKey The provider (i.e. firewall) key
   *
   * @return Response|null
   */
  public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey) {
    return null;
  }

  /**
   * Does this method support remember me cookies?
   *
   * Remember me cookie will be set if *all* of the following are met:
   *  A) This method returns true
   *  B) The remember_me key under your firewall is configured
   *  C) The "remember me" functionality is activated. This is usually
   *      done by having a _remember_me checkbox in your form, but
   *      can be configured by the "always_remember_me" and "remember_me_parameter"
   *      parameters under the "remember_me" firewall key
   *
   * @return bool
   */
  public function supportsRememberMe() {
    return false;
  }
}