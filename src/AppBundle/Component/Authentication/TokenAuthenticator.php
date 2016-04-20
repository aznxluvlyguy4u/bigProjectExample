<?php

namespace AppBundle\Component\Authentication;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * Class TokenAuthenticator
 * @package AppBundle\Component\Authentication
 */
class TokenAuthenticator implements SimplePreAuthenticatorInterface {

  const ACCESS_TOKEN_HEADER_NAMESPACE = 'AccessToken';

  /**
   * @var HttpUtils
   */
  protected $httpUtils;

  /**
   * @var array
   */
  private $unAuthedPaths;

  public function __construct(HttpUtils $httpUtils, $unAuthedPaths)
  {
    $this->httpUtils = $httpUtils;
    $this->$unAuthedPaths = $unAuthedPaths;
  }

  /**
   * @param TokenInterface $token
   * @param UserProviderInterface $userProvider
   * @param $providerKey
   */
  public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey) {
    if (!$userProvider instanceof TokenUserProvider) {
      throw new \InvalidArgumentException(
        sprintf(
          'The user provider must be an instance of TokenUserProvider (%s was given).',
          get_class($userProvider)
        )
      );
    }

    $apiKey = $token->getCredentials();
    $username = $userProvider->getUsernameForApiKey($apiKey);

    if (!$username) {
      // CAUTION: this message will be returned to the client
      // (so don't put any un-trusted messages / error strings here)
      throw new CustomUserMessageAuthenticationException(
        sprintf('API Key "%s" does not exist.', $apiKey)
      );
    }

    $user = $userProvider->loadUserByUsername($username);

    return new PreAuthenticatedToken(
      $user,
      $apiKey,
      $providerKey,
      $user->getRoles()
    );  }


  /**
   * @param Request $request
   * @param $providerKey
   * @return PreAuthenticatedToken
   */
  public function createToken(Request $request, $providerKey) {

    foreach($this->unAuthedPaths as $unAuthedPath) {

      if($this->httpUtils->checkRequestPath($request, $unAuthedPath)) {
        return;
      }
    }

    //Get AccessToken header value
    if(!$request->headers->has($this::ACCESS_TOKEN_HEADER_NAMESPACE)) {
      throw new BadCredentialsException('AccessToken header was found');
    }

    $accessToken = $request->query->get($this::ACCESS_TOKEN_HEADER_NAMESPACE);

    return new PreAuthenticatedToken(
      'anon.',
      $accessToken,
      $providerKey
    );

  }

  /**
   * @param TokenInterface $token
   * @param $providerKey
   * @return bool
   */
  public function supportsToken(TokenInterface $token, $providerKey) {
    return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
  }
}