<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

interface RevokeAPIControllerInterface {
  function createRevoke(Request $request);
  function revokeNsfoDeclaration(Request $request, $messageId);
}