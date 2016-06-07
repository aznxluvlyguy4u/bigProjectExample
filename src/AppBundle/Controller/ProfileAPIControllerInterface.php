<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

interface ProfileAPIControllerInterface {
    function getCompanyProfile(Request $request);
    function getLoginData(Request $request);
    function editCompanyProfile(Request $request);
}