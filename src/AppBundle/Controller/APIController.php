<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Employee;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class APIController
 * @package AppBundle\Controller
 */
class APIController extends Controller implements APIControllerInterface
{
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
   * @param string $tokenCode
   * @return Employee|null
   */
  public function getEmployee($tokenCode = null)
  {
    return $this->get('app.user')->getEmployee($tokenCode);
  }


}