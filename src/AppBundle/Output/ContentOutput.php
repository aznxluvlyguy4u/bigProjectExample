<?php

namespace AppBundle\Output;

use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Content;

class ContentOutput
{
    /**
     * @param Content $cms
     * @return array
     */
    public static function create($cms)
    {
        $res = array(
            JsonInputConstant::DASHBOARD => $cms->getDashBoardIntroductionText(),
            JsonInputConstant::CONTACT_INFO => $cms->getNsfoContactInformation()
        );

        return $res;
    }

}