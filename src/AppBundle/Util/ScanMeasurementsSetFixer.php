<?php


namespace AppBundle\Util;


use Doctrine\DBAL\Connection;
use Monolog\Logger;

class ScanMeasurementsSetFixer
{
    public static function fixSubMeasurementsIsActiveStatus(Connection $connection, Logger $logger)
    {
        self::fixWeightIsActiveAndIsRevokedStatus($connection, $logger);
        self::fixBodyFatIsActiveStatus($connection, $logger);
        self::fixFat1IsActiveStatus($connection, $logger);
        self::fixFat2IsActiveStatus($connection, $logger);
        self::fixFat3IsActiveStatus($connection, $logger);
        self::fixMuscleThicknessIsActiveStatus($connection, $logger);
    }


    private static function updateAndLog(Connection $connection, Logger $logger, string $tableName, string $sql,
        string $columnName = 'is_active', string $source = 'set.is_active')
    {
        $updateCount = SqlUtil::updateWithCount($connection, $sql);
        $logger->notice("$tableName $columnName statuses matched with $source: ".$updateCount);
    }


    private static function fixWeightIsActiveAndIsRevokedStatus(Connection $connection, Logger $logger)
    {
        $sql = "UPDATE measurement SET is_active = v.set_is_active
FROM (
         SELECT
             mset.is_active as set_is_active,
             mw.is_active as sub_measurement_is_active,
             mw.id as sub_measurement_id
         FROM scan_measurement_set s
                  INNER JOIN measurement mset ON mset.id = s.id
                  INNER JOIN weight w ON w.id = s.scan_weight_id
                  INNER JOIN measurement mw on w.id = mw.id
         WHERE
                 mset.is_active <> mw.is_active
)v(set_is_active,measurement_is_active,measurement_id) WHERE measurement.id = v.measurement_id";
        self::updateAndLog($connection, $logger, 'weight', $sql);

        self::fixWeightIsRevokedStatus($connection, $logger);
    }


    public static function fixWeightIsRevokedStatus(Connection $connection, Logger $logger)
    {
        $sql = "UPDATE weight SET is_revoked = v.new_is_revoked
FROM (
         SELECT
             w.id,
             w.is_revoked,
             CASE WHEN w.is_revoked THEN
                FALSE
             ELSE
                TRUE
            END as new_is_revoked
         FROM weight w
                  INNER JOIN measurement m on w.id = m.id
         WHERE w.is_revoked = m.is_active
     ) v(weight_id, old_is_revoked, new_is_revoked) WHERE weight.id = v.weight_id AND is_revoked <> new_is_revoked";

        self::updateAndLog($connection, $logger, 'weight', $sql, 'is_revoked', 'measurement.is_active');
    }


    private static function fixBodyFatIsActiveStatus(Connection $connection, Logger $logger)
    {
        $sql = "UPDATE measurement SET is_active = v.set_is_active
FROM (
         SELECT
             mset.is_active as set_is_active,
             mbf.is_active as body_fat_is_active,
             mbf.id as sub_measurement_id
         FROM scan_measurement_set s
                  INNER JOIN measurement mset ON mset.id = s.id
                  INNER JOIN body_fat bf on s.body_fat_id = bf.id
                  INNER JOIN measurement mbf on bf.id = mbf.id
         WHERE
                 mset.is_active <> mbf.is_active
)v(set_is_active,measurement_is_active,measurement_id) WHERE measurement.id = v.measurement_id";
        self::updateAndLog($connection, $logger, 'body_fat', $sql);
    }


    private static function fixFat1IsActiveStatus(Connection $connection, Logger $logger)
    {
        $sql = "UPDATE measurement SET is_active = v.set_is_active
FROM (
         SELECT
             mset.is_active as set_is_active,
             mfat1.is_active as fat1_is_active,
             mfat1.id as sub_measurement_id
         FROM scan_measurement_set s
                  INNER JOIN measurement mset ON mset.id = s.id
                  INNER JOIN body_fat bf on s.body_fat_id = bf.id
                  INNER JOIN fat1 on fat1.id = bf.fat1_id
                  INNER JOIN measurement mfat1 on fat1.id = mfat1.id
         WHERE
                 mset.is_active <> mfat1.is_active
)v(set_is_active,measurement_is_active,measurement_id) WHERE measurement.id = v.measurement_id";
        self::updateAndLog($connection, $logger, 'fat1', $sql);
    }


    private static function fixFat2IsActiveStatus(Connection $connection, Logger $logger)
    {
        $sql = "UPDATE measurement SET is_active = v.set_is_active
FROM (
         SELECT
             mset.is_active as set_is_active,
             mfat2.is_active as fat2_is_active,
             mfat2.id as sub_measurement_id
         FROM scan_measurement_set s
                  INNER JOIN measurement mset ON mset.id = s.id
                  INNER JOIN body_fat bf on s.body_fat_id = bf.id
                  INNER JOIN fat2 on fat2.id = bf.fat2_id
                  INNER JOIN measurement mfat2 on fat2.id = mfat2.id
         WHERE
                 mset.is_active <> mfat2.is_active
)v(set_is_active,measurement_is_active,measurement_id) WHERE measurement.id = v.measurement_id";
        self::updateAndLog($connection, $logger, 'fat2', $sql);
    }


    private static function fixFat3IsActiveStatus(Connection $connection, Logger $logger)
    {
        $sql = "UPDATE measurement SET is_active = v.set_is_active
FROM (
         SELECT
             mset.is_active as set_is_active,
             mfat3.is_active as fat3_is_active,
             mfat3.id as sub_measurement_id
         FROM scan_measurement_set s
                  INNER JOIN measurement mset ON mset.id = s.id
                  INNER JOIN body_fat bf on s.body_fat_id = bf.id
                  INNER JOIN fat3 on fat3.id = bf.fat3_id
                  INNER JOIN measurement mfat3 on fat3.id = mfat3.id
         WHERE
                 mset.is_active <> mfat3.is_active
)v(set_is_active,measurement_is_active,measurement_id) WHERE measurement.id = v.measurement_id";
        self::updateAndLog($connection, $logger, 'fat3', $sql);
    }


    private static function fixMuscleThicknessIsActiveStatus(Connection $connection, Logger $logger)
    {
        $sql = "UPDATE measurement SET is_active = v.set_is_active
FROM (
         SELECT
             mset.is_active as set_is_active,
             mmt.is_active as muscle_thickness_is_active,
             mmt.id as sub_measurement_id
         FROM scan_measurement_set s
                  INNER JOIN measurement mset ON mset.id = s.id
                  INNER JOIN muscle_thickness mt on s.muscle_thickness_id = mt.id
                  INNER JOIN measurement mmt on mt.id = mmt.id
         WHERE
                 mset.is_active <> mmt.is_active
)v(set_is_active,measurement_is_active,measurement_id) WHERE measurement.id = v.measurement_id";
        self::updateAndLog($connection, $logger, 'muscle_thickness', $sql);
    }

}
