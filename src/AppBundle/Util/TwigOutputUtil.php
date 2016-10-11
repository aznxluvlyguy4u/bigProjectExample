<?php

namespace AppBundle\Util;


use AppBundle\Constant\TwigCode;

class TwigOutputUtil
{    
    /**
     * @param float $starScore
     * @param int $totalStars
     * @return string
     */
    public static function createStarsIndex($starScore, $totalStars = 5)
    {
        $spacer = ' ';
        
        if($totalStars < 0) { $totalStars = 0; }

        //Star score limit check
        if($starScore < 0) {
            $starScore = 0;
        } elseif ($starScore > $totalStars) {
            $starScore = $totalStars;
        }
        
        //Initialize star counts
        $starScore = round($starScore * 2) / 2; //round to nearest 0.5
        $wholeStars = intval($starScore);
        $hasHalfStar = !NumberUtil::isFloatZero($starScore - $wholeStars);
        $halfStarCount = $hasHalfStar ? 1 : 0;
        $emptyStarsCount = $totalStars - $wholeStars - $halfStarCount;
        if($emptyStarsCount < 0) { $emptyStarsCount = 0; }

        //Generate star index code for three different possibilities

        //All stars are empty
        if($starScore == 0) {
            $stars = self::createIconString($emptyStarsCount, TwigCode::STAR_WHITE, $spacer);
            
        //All stars are black    
        } elseif ($starScore == $totalStars) {
            $stars = self::createIconString($starScore, TwigCode::STAR_BLACK, $spacer);

        // number of full black stars + possibly one half star + number of empty white stars
        } else {
            $stars = self::createIconString($wholeStars, TwigCode::STAR_BLACK, $spacer).$spacer.
                self::createIconString($halfStarCount, TwigCode::STAR_HALF, $spacer).$spacer.
                self::createIconString($emptyStarsCount, TwigCode::STAR_WHITE, $spacer);
        }

        return TwigCode::AUTO_ESCAPE_START.' '.$stars.' '.TwigCode::AUTO_ESCAPE_END;
    }


    /**
     * Creates a string of icons (subStrings) with spacers between them
     *
     * @param int $iconCount
     * @param string $iconCode
     * @param string $spacer
     * @return string
     */
    private static function createIconString($iconCount, $iconCode, $spacer = ' ')
    {
        if($iconCount <= 0) {
            return ''; 
        
        } elseif($iconCount == 1) {
            return $iconCode;
        
        } else { //starCount > 1
            $result = '';
            for($i = 0; $i < $iconCount - 1; $i++) {
                $result = $result.$iconCode.$spacer;
            }
            return $result.$iconCode;
        }
    }


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
            'margin-top'    => 6,
            'margin-right'  => 8,
            'margin-bottom' => 4,
            'margin-left'   => 8,
        ];
    }
}