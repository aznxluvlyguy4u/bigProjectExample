<?php

namespace AppBundle\Controller;

use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Validation\AdminValidator;
use AppBundle\Entity\Location;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * @Route("/api/v1/location")
 */
class LocationAPIController extends APIController
{
    /**
     * @param Request $request the request object
     *
     * @return JsonResponse
     * @Route("/generate-ids")
     * @Method("POST")
     */
    public function generateNewLocationIds(Request $request)
    {
        // Validation if user is an admin
        $admin = $this->getAuthenticatedEmployee($request);
        $adminValidator = new AdminValidator($admin);

        if (!$adminValidator->getIsAccessGranted()) {
            return $adminValidator->createJsonErrorResponse();
        }

        $locations = $this->getDoctrine()->getRepository(Constant::LOCATION_REPOSITORY)->findAll();

        foreach ($locations as $location) {
            /**
             * @var Location $location
             */
            if ($location->getLocationId() == null || $location->getLocationId() == '') {
                $location->setLocationId(Utils::generateTokenCode());
                $this->getDoctrine()->getEntityManager()->persist($location);
                $this->getDoctrine()->getEntityManager()->flush();
            }
        }

        return new JsonResponse('ok', 200);
    }
}