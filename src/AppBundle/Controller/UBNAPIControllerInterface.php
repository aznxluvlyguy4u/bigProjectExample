<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface UBNAPIControllerInterface
 * @package AppBundle\Controller
 */
interface UBNAPIControllerInterface
{
    function getUBNDetails(Request $request);
}