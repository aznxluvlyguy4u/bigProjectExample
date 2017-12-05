<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

interface PedigreeRegisterAPIControllerInterface {
    function getPedigreeRegisters(Request $request);
}