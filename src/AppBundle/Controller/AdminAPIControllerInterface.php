<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

interface AdminAPIControllerInterface {
    function getAdmins(Request $request);
    function createAdmin(Request $request);
    function editAdmin(Request $request);
    function deactivateAdmin(Request $request);
    function getTemporaryGhostToken(Request $request);
    function verifyGhostToken(Request $request);
    function getAccessLevelTypes(Request $request);
}