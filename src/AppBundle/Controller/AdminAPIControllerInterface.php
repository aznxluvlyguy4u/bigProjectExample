<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

interface AdminAPIControllerInterface {
    function getAdmins(Request $request);
    function createAdmins(Request $request);
    function editAdmins(Request $request);
    function deactivateAdmins(Request $request);
    function getTemporaryGhostToken(Request $request);
    function verifyGhostToken(Request $request);
    function getAccessLevelTypes(Request $request);
}