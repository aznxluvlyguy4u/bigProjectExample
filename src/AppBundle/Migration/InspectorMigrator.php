<?php


namespace AppBundle\Migration;


use AppBundle\Constant\JsonInputConstant;

class InspectorMigrator
{
    /**
     * @param string $fullName
     * @return array
     */
    public static function convertImportedInspectorName($fullName)
    {
        $newFirstName = null;
        $newLastName = null;

        if($fullName == "A. Bakker" || $fullName == "Bakker" || $fullName == "Bakker a.") {
            $newFirstName = 'A.';
            $newLastName = 'Bakker';

        } elseif ($fullName == "A. van Zandwijk") {
            $newFirstName = 'A.';
            $newLastName = 'van Zandwijk';

        } elseif ($fullName == "Bennie Feije" || $fullName == "Feije") {
            $newFirstName = 'Bennie';
            $newLastName = 'Feije';

        } elseif ($fullName == "Ben Thys") {
            $newFirstName = 'Ben';
            $newLastName = 'Thys';

        } elseif ($fullName == "C.J.J. van der Pijl") {
            $newFirstName = 'C.J.J.';
            $newLastName = 'van der Pijl';

        } elseif ($fullName == "C. pauw" || $fullName == "C. Pauw") {
            $newFirstName = 'C.';
            $newLastName = 'Pauw';

        } elseif ($fullName == "D. Reijne" || $fullName == "Reijne") {
            $newFirstName = 'D.';
            $newLastName = 'Reijne';

        } elseif ($fullName == "F.Palmen") {
            $newFirstName = 'F.';
            $newLastName = 'Palmen';

        } elseif ($fullName == "H. Blok") {
            $newFirstName = 'H.';
            $newLastName = 'Blok';

        } elseif ($fullName == "H.J.G. te Mebel" || $fullName == "H.J. Te Mebel" ||
            $fullName == "H. te Mebel" || $fullName == "H.J.G. te Mebel" || $fullName == "H.J.G. te Mebel") {
            $newFirstName = 'Hans';
            $newLastName = 'te Mebel';

        } elseif ($fullName == "H. Mulder") {
            $newFirstName = 'H.';
            $newLastName = 'Mulder';

        } elseif ($fullName == "H. Verheul") {
            $newFirstName = 'H.';
            $newLastName = 'Verheul';

        } elseif ($fullName == "J.A.M. Schilder") {
            $newFirstName = 'J.A.M.';
            $newLastName = 'Schilder';

        } elseif ($fullName == "J.D.M. Schreurs") {
            $newFirstName = 'J.D.M.';
            $newLastName = 'Schreurs';

        } elseif ($fullName == "J.M.J. Vaessen") {
            $newFirstName = 'J.M.J.';
            $newLastName = 'Vaessen';

        } elseif ($fullName == "J.T. Hooge") {
            $newFirstName = 'J.T.';
            $newLastName = 'Hooge';

        } elseif ($fullName == "U. Zwaga") {
            $newFirstName = 'U.';
            $newLastName = 'Zwaga';

        } elseif ($fullName == "J. Worp") {
            $newFirstName = 'J.';
            $newLastName = 'Worp';

        } elseif ($fullName == "L.F. de Reuver") {
            $newFirstName = 'L.F.';
            $newLastName = 'de Reuver';

        } elseif ($fullName == "M. Mulder") {
            $newFirstName = 'M.';
            $newLastName = 'Mulder';

        } elseif ($fullName == "M. van Bergen" || $fullName == "Bergen" || $fullName == "Marjo van Bergen") {
            $newFirstName = 'Marjo';
            $newLastName = 'van Bergen';

        } elseif ($fullName == "M. van Wijnen") {
            $newFirstName = 'M.';
            $newLastName = 'van Wijnen';

        } elseif ($fullName == "M.W.P. Nijssen") {
            $newFirstName = 'M.W.P.';
            $newLastName = 'Nijssen';

        } elseif ($fullName == "R. Kuipers") {
            $newFirstName = 'R.';
            $newLastName = 'Kuipers';

        } elseif ($fullName == "Rodenburg w" || $fullName == "Rodenburg, W."
            || $fullName == "W. Rodenburg" || $fullName == "W.S.M. Rodenburg") {
            $newFirstName = 'Wout';
            $newLastName = 'Rodenburg';

        } elseif ($fullName == "Stijn Thijs" || $fullName == "Thijs") {
            $newFirstName = 'Stijn';
            $newLastName = 'Thijs';

        } elseif ($fullName == "Th. reintjes") {
            $newFirstName = 'Th.';
            $newLastName = 'Reintjes';

        } elseif ($fullName == "Johan Knaap") {
            $newFirstName = 'Johan';
            $newLastName = 'Knaap';

        } elseif ($fullName == "Hans te Mebel") {
            $newFirstName = 'Hans';
            $newLastName = 'te Mebel';

        }

        return [
          JsonInputConstant::FIRST_NAME => $newFirstName,
          JsonInputConstant::LAST_NAME => $newLastName,
        ];
    }
    
}