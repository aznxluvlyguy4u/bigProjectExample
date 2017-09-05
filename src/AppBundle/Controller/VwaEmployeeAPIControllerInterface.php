<?php


namespace AppBundle\Controller;


use Symfony\Component\HttpFoundation\Request;

interface VwaEmployeeAPIControllerInterface
{
    function getAll(Request $request);
    function getById(Request $request, $id);
    function create(Request $request);
    function edit(Request $request, $id);
    function deactivate(Request $request, $id);

    function invite(Request $request);

    function authorize(Request $request);
}