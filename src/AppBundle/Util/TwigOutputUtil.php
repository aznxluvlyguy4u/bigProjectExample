<?php

namespace AppBundle\Util;


class TwigOutputUtil
{
    /**
     * @return array
     */
    public static function pdfLandscapeOptions()
    {
        return [
            'orientation'=>'Landscape',
            'default-header'=>false,
            'disable-smart-shrinking'=>true,
            'print-media-type' => true,
            'page-size' => 'A4',
            'margin-top'    => 6,
            'margin-right'  => 8,
            'margin-bottom' => 4,
            'margin-left'   => 8,
        ];
    }


    /**
     * @return array
     */
    public static function pdfPortraitOptions()
    {
        return [
            'orientation'=>'Portrait',
            'default-header'=>false,
            'disable-smart-shrinking'=>true,
            'print-media-type' => true,
            'page-size' => 'A4',
            'margin-top'    => 6,
            'margin-right'  => 8,
            'margin-bottom' => 4,
            'margin-left'   => 8,
        ];
    }
}