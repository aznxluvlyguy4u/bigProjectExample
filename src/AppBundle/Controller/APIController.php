<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Client;
use AppBundle\Entity\Employee;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class APIController
 * @package AppBundle\Controller
 */
class APIController extends Controller implements APIControllerInterface
{
  /** @var array */
  //TODO remove after merge and refactor open feature branches
  private $services = [
  ];


  /**
   * TODO remove after merge and refactor open feature branches
   * @param string $controller
   * @return mixed|null
   */
  private function getService($controller){
    if(!key_exists($controller, $this->services)) { return null;}

    if ($this->services[$controller] == null) {
      $this->services[$controller] = $this->get($controller);
    }
    return $this->services[$controller];
  }


  /**
   * Redirect to API docs when root is requested
   *
   * @Route("")
   * @Method("GET")
   */
  public function redirectRootToAPIDoc()
  {
    return new RedirectResponse('/api/v1/doc');
  }

  /**
   * TODO remove after merge and refactor open feature branches
   *
   * @param Request $request
   * @return Client|null
   */
  public function getAccountOwner(Request $request = null)
  {
    return $this->get('app.user')->getAccountOwner($request);
  }


  /**
   * TODO remove after merge and refactor open feature branches
   *
   * @param string $tokenCode
   * @return Employee|null
   */
  public function getEmployee($tokenCode = null)
  {
    return $this->get('app.user')->getEmployee($tokenCode);
  }


}