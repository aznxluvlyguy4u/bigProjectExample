<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

interface AnimalAnnotationAPIControllerInterface {
    function getAnnotations(Request $request, $idOrUlnString);
    function editAnnotation(Request $request, $idOrUlnString);
}



