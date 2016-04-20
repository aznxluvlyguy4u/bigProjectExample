<?php

namespace AppBundle\Component\Authentication;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

/**
 * Class TokenUserProvider
 * @package AppBundle\Component\Authentication
 */
class TokenUserProvider implements UserProviderInterface, AuthenticationFailureHandlerInterface
{
  public function getUsernameForApiKey($apiKey)
  {
    // Look up the username based on the token in the database, via
    // an API call, or do something entirely different
    $username = 'lll';

        return $username;
    }

  public function loadUserByUsername($username)
  {
    return new User(
      $username,
      null,
      // the roles for the user - you may choose to determine
      // these dynamically somehow based on the user
      array('ROLE_USER')
    );
  }

  public function refreshUser(UserInterface $user)
  {
    // this is used for storing authentication in the session
    // but in this example, the token is sent in each request,
    // so authentication can be stateless. Throwing this exception
    // is proper to make things stateless
    throw new UnsupportedUserException();
  }

  public function supportsClass($class)
  {
    return 'Symfony\Component\Security\Core\User\User' === $class;
  }

  /**
   * This is called when an interactive authentication attempt fails. This is
   * called by authentication listeners inheriting from
   * AbstractAuthenticationListener.
   *
   * @param Request $request
   * @param AuthenticationException $exception
   *
   * @return Response The response to return, never null
   */
  public function onAuthenticationFailure(Request $request, AuthenticationException $exception) {
    return new Response(
    // this contains information about *why* authentication failed
    // use it, or return your own message
      strtr($exception->getMessageKey(), $exception->getMessageData()),
      403
    );
  }
}