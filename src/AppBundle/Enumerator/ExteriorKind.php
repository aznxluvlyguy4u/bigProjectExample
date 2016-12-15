<?php


namespace AppBundle\Enumerator;


/**
 * Class ExteriorKind
 *
 * @ORM\Entity(repositoryClass="AppBundle\Enumerator")
 * @package AppBundle\Enumerator
 */
class ExteriorKind
{
    /*                       age (months)   condition
       VG voorlopig gekeurd: 5-14           -
       DD direct definitief: 14-26          als het nog geen VG heeft
       DF definitief:        14-26          als het al een VG heeft
       DO dood voor keuring: -              kan altijd voor een dier dat dood is
       HK herkeuring:        -              moet al een DD of DF of VG hebben
       HH herhaalde keuring: 26             moet al een DD of DF hebben
     */
    const VG_ = 'VG';
    const DD_ = 'DD';
    const DF_ = 'DF';
    const DO_ = 'DO';
    const HK_ = 'HK';
    const HH_ = 'HH';

    /**
     * @return array
     */
    public static function getAll()
    {
        $kinds = [
            self::VG_ => self::VG_,
            self::DD_ => self::DD_,
            self::DF_ => self::DF_,
            self::DO_ => self::DO_,
            self::HK_ => self::HK_,
            self::HH_ => self::HH_,
        ];

        //Use asort instead of sort to keep the keys!
        asort($kinds);

        return $kinds;
    }
}