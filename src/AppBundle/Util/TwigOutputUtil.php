<?php

namespace AppBundle\Util;


use AppBundle\Constant\TwigCode;

class TwigOutputUtil
{    
    /**
     * @param int $starScore
     * @param int $totalStars
     * @return string
     */
    public static function createStarsIndex($starScore, $totalStars = 5)
    {
        $spacer = ' ';

        if($totalStars < 0) { $totalStars = 0; }
        
        //Initialize star counts
        $whiteStarsCount = $totalStars - $starScore;
        if($whiteStarsCount < 0) { $whiteStarsCount = 0; }

        if($starScore > $totalStars) { $starScore = $totalStars; }
        if($starScore < 0) { $starScore = 0; }

        //Generate star index code for three different possibilities

        //All stars are empty
        if($starScore == 0) {
            return self::createIconString($whiteStarsCount, TwigCode::WHITE_STAR, $spacer);
            
        //All stars are black    
        } elseif ($starScore == $totalStars) {
            return self::createIconString($starScore, TwigCode::BLACK_STAR, $spacer);

        // number of empty white stars + number of black stars    
        } else {
            return self::createIconString($whiteStarsCount, TwigCode::WHITE_STAR).$spacer.
                   self::createIconString($starScore, TwigCode::BLACK_STAR);
        }
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
            for($i = 0; $iconCount - 1; $i++) {
                $result = $iconCode.$spacer;
            }
            return $result.$iconCode;
        }
    }



}