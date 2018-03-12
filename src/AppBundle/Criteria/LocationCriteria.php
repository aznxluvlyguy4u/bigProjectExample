<?php


namespace AppBundle\Criteria;


use Doctrine\Common\Collections\Criteria;

class LocationCriteria
{
    /**
     * @param boolean $prioritizeByIsActive
     * @param string $ubn
     * @return Criteria
     */
    public static function byUbn($ubn, $prioritizeByIsActive = true)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('ubn', $ubn))
        ;

        if ($prioritizeByIsActive) {
            $criteria->orderBy(['ubn' => Criteria::ASC, 'isActive' => Criteria::DESC]);
        }

        return $criteria;
    }

    /**
     * @param Criteria $criteria
     * @return Criteria
     */
    private static function prioritizeByIsActive(Criteria $criteria)
    {
        return $criteria->orderBy(['ubn' => Criteria::ASC, 'isActive' => Criteria::DESC]);
    }
}