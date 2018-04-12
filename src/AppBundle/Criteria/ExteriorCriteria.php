<?php


namespace AppBundle\Criteria;


use AppBundle\Enumerator\ExteriorKind;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;

class ExteriorCriteria
{
    const ONE_YEAR_AGE_IN_DAYS_LIMIT = 480;


    /**
     * @param CompositeExpression|Comparison $criteria
     * @param boolean $ignoreDOKind
     * @return CompositeExpression
     */
    private static function addDoKindCriteria($criteria, $ignoreDOKind)
    {
        if ($ignoreDOKind) {
            return $criteria;
        }

        return Criteria::expr()->orX(
            $criteria,
            Criteria::expr()->eq('kind', ExteriorKind::DO_)
        );
    }


    /**
     * @param bool $ignoreDOKind
     * @return CompositeExpression
     */
    private static function olderThanOneYearKindsCriteria($ignoreDOKind = false)
    {
        $criteria = Criteria::expr()->orX(
            Criteria::expr()->eq('kind', ExteriorKind::DF_),
            Criteria::expr()->eq('kind', ExteriorKind::DD_),
            Criteria::expr()->eq('kind', ExteriorKind::HK_),
            Criteria::expr()->eq('kind', ExteriorKind::HH_)
        );
        return self::addDoKindCriteria($criteria, $ignoreDOKind);
    }


    /**
     * @param bool $ignoreDOKind
     * @return Comparison|CompositeExpression
     */
    private static function oneYearOldOrYoungerKindsCriteria($ignoreDOKind = false)
    {
        $criteria = Criteria::expr()->eq('kind', ExteriorKind::VG_);
        return self::addDoKindCriteria($criteria, $ignoreDOKind);
    }


    /**
     * @param $parentAgeAtDateOfBirthChildInDays
     * @param bool $ignoreDOKind
     * @return \Doctrine\Common\Collections\Expr\CompositeExpression
     */
    private static function kindsCriteria($parentAgeAtDateOfBirthChildInDays, $ignoreDOKind = false)
    {
        if ($parentAgeAtDateOfBirthChildInDays <= self::ONE_YEAR_AGE_IN_DAYS_LIMIT) {
            return self::oneYearOldOrYoungerKindsCriteria($ignoreDOKind);
        }

        return self::olderThanOneYearKindsCriteria($ignoreDOKind);
    }


    /**
     * @param int $parentAgeAtDateOfBirthChildInDays
     * @return Criteria
     */
    public static function pureBredBMParentExterior($parentAgeAtDateOfBirthChildInDays)
    {
        return Criteria::create()
            ->where(self::kindsCriteria($parentAgeAtDateOfBirthChildInDays))
            ->andWhere(Criteria::expr()->gte('generalAppearance', 75))
        ;
    }


    /**
     * @param int $parentAgeAtDateOfBirthChildInDays
     * @return Criteria
     */
    public static function pureBredTEFatherExterior($parentAgeAtDateOfBirthChildInDays)
    {
        return Criteria::create()
            ->where(self::kindsCriteria($parentAgeAtDateOfBirthChildInDays))
            ->andWhere(Criteria::expr()->gte('generalAppearance', 70))
            ;
    }


    /**
     * @param int $parentAgeAtDateOfBirthChildInDays
     * @return Criteria
     */
    public static function pureBredTEMotherOfRamExterior($parentAgeAtDateOfBirthChildInDays)
    {
        if ($parentAgeAtDateOfBirthChildInDays <= self::ONE_YEAR_AGE_IN_DAYS_LIMIT) {

            return Criteria::create()
                ->where(self::oneYearOldOrYoungerKindsCriteria(true))
                ->andWhere(Criteria::expr()->gte('generalAppearance', 70))
                ;
        }

        return Criteria::create()
            ->where(self::olderThanOneYearKindsCriteria(true))
            ->andWhere(Criteria::expr()->gte('generalAppearance', 75))
            ->andWhere(Criteria::expr()->gte('muscularity', 77))
            ;
    }


    /**
     * @return Criteria
     */
    public static function pureBredTEMotherOfEweExterior()
    {
        return Criteria::create()
            ->where(self::olderThanOneYearKindsCriteria())
            ->andWhere(Criteria::expr()->gte('generalAppearance', 80))
            ;
    }
}