<?php


namespace AppBundle\Controller;


use Symfony\Component\HttpFoundation\Request;

interface ErrorMessageAPIControllerInterface
{
    function getErrors(Request $request);
    function getErrorDetails(Request $request, $messageId);
    function updateError(Request $request);
    function updateNsfoDeclarationError(Request $request, $messageId);
}