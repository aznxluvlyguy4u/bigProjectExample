<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

interface AdminProfileAPIControllerInterface {
    function getAdminProfile(Request $request);
    function editAdminProfile(Request $request);
}