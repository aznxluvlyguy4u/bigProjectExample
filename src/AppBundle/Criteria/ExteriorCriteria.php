<?php


namespace AppBundle\Criteria;


use AppBundle\Enumerator\ExteriorKind;
use Doctrine\Common\Collections\Criteria;

class ExteriorCriteria
{
    const ONE_YEAR_AGE_IN_DAYS_LIMIT = 480;

    /**
     * @param int $parentAgeAtDateOfBirthChildInDays
     * @return Criteria
     */
    public static function pureBredParentExterior($parentAgeAtDateOfBirthChildInDays)
    {
        if ($parentAgeAtDateOfBirthChildInDays <= self::ONE_YEAR_AGE_IN_DAYS_LIMIT) {
            $kindsCriteria = Criteria::expr()->orX(
                Criteria::expr()->eq('kind', ExteriorKind::VG_),
                Criteria::expr()->eq('kind', ExteriorKind::DO_)
                );
        } else {
            $kindsCriteria = Criteria::expr()->orX(
                Criteria::expr()->eq('kind', ExteriorKind::DO_),
                Criteria::expr()->eq('kind', ExteriorKind::DF_),
                Criteria::expr()->eq('kind', ExteriorKind::DD_),
                Criteria::expr()->eq('kind', ExteriorKind::HK_),
                Criteria::expr()->eq('kind', ExteriorKind::HH_)
            );
        }

        return Criteria::create()
            ->where($kindsCriteria)
            ->andWhere(Criteria::expr()->gte('generalAppearance', 75))
        ;
    }
}