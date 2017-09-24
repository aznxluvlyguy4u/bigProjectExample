<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

interface QueueDashboardAPIControllerInterface
{
    function getQueueSizes(Request $request);
    function moveErrorQueueMessages(Request $request);
}