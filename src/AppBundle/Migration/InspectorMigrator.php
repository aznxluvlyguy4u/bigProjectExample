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


    /**
     * @return array
     */
    public function inspectorImportNames()
    {
        return [
            "A. Bakker" => [JsonInputConstant::FIRST_NAME => 'A.',
                JsonInputConstant::LAST_NAME => 'Bakker',],
            "Bakker" => [JsonInputConstant::FIRST_NAME => 'A.',
                JsonInputConstant::LAST_NAME => 'Bakker',],
            "Bakker a." => [JsonInputConstant::FIRST_NAME => 'A.',
                JsonInputConstant::LAST_NAME => 'Bakker',],
            "A. van Zandwijk" => [JsonInputConstant::FIRST_NAME => 'A.',
                JsonInputConstant::LAST_NAME => 'van Zandwijk',],

            "Bennie Feije" => [JsonInputConstant::FIRST_NAME => 'Bennie',
                JsonInputConstant::LAST_NAME => 'Feije',],
            "Feije" => [JsonInputConstant::FIRST_NAME => 'Bennie',
                JsonInputConstant::LAST_NAME => 'Feije',],

            "Ben Thys" => [JsonInputConstant::FIRST_NAME => 'Ben',
                JsonInputConstant::LAST_NAME => 'Thys',],

            "C.J.J. van der Pijl" => [JsonInputConstant::FIRST_NAME => 'C.J.J.',
                JsonInputConstant::LAST_NAME => 'van der Pijl',],

            "C. pauw" => [JsonInputConstant::FIRST_NAME => 'C.',
                JsonInputConstant::LAST_NAME => 'Pauw',],
            "C. Pauw" => [JsonInputConstant::FIRST_NAME => 'C.',
                JsonInputConstant::LAST_NAME => 'Pauw',],

            "D. Reijne" => [JsonInputConstant::FIRST_NAME => 'D.',
                JsonInputConstant::LAST_NAME => 'Reijne',],
            "Reijne" => [JsonInputConstant::FIRST_NAME => 'D.',
                JsonInputConstant::LAST_NAME => 'Reijne',],

            "F.Palmen" => [JsonInputConstant::FIRST_NAME => 'F.',
                JsonInputConstant::LAST_NAME => 'Palmen',],

            "H. Blok" => [JsonInputConstant::FIRST_NAME => 'H.',
                JsonInputConstant::LAST_NAME => 'Blok',],

            "H.J.G. te Mebel" => [JsonInputConstant::FIRST_NAME => 'Hans',
                JsonInputConstant::LAST_NAME => 'te Mebel',],
            "H.J. Te Mebel" => [JsonInputConstant::FIRST_NAME => 'Hans',
                JsonInputConstant::LAST_NAME => 'te Mebel',],
            "H. te Mebel" => [JsonInputConstant::FIRST_NAME => 'Hans',
                JsonInputConstant::LAST_NAME => 'te Mebel',],
            "Hans te Mebel" => [JsonInputConstant::FIRST_NAME => 'Hans',
                JsonInputConstant::LAST_NAME => 'te Mebel',],

            "H. Mulder" => [JsonInputConstant::FIRST_NAME => 'H.',
                JsonInputConstant::LAST_NAME => 'Mulder',],

            "H. Verheul" => [JsonInputConstant::FIRST_NAME => 'H.',
                JsonInputConstant::LAST_NAME => 'Verheul',],

            "J.A.M. Schilder" => [JsonInputConstant::FIRST_NAME => 'J.A.M.',
                JsonInputConstant::LAST_NAME => 'Schilder',],

            "J.D.M. Schreurs" => [JsonInputConstant::FIRST_NAME => 'J.D.M.',
                JsonInputConstant::LAST_NAME => 'Schreurs',],

            "J.M.J. Vaessen" => [JsonInputConstant::FIRST_NAME => 'J.M.J.',
                JsonInputConstant::LAST_NAME => 'Vaessen',],

            "J.T. Hooge" => [JsonInputConstant::FIRST_NAME => 'J.T.',
                JsonInputConstant::LAST_NAME => 'Hooge',],

            "U. Zwaga" => [JsonInputConstant::FIRST_NAME => 'U.',
                JsonInputConstant::LAST_NAME => 'Zwaga',],

            "J. Worp" => [JsonInputConstant::FIRST_NAME => 'J.',
                JsonInputConstant::LAST_NAME => 'Worp',],

            "L.F. de Reuver" => [JsonInputConstant::FIRST_NAME => 'L.F.',
                JsonInputConstant::LAST_NAME => 'Reuver',],

            "M. Mulder" => [JsonInputConstant::FIRST_NAME => 'M.',
                JsonInputConstant::LAST_NAME => 'Mulder',],

            "M. van Bergen" => [JsonInputConstant::FIRST_NAME => 'Marjo',
                JsonInputConstant::LAST_NAME => 'van Bergen',],
            "Bergen" => [JsonInputConstant::FIRST_NAME => 'Marjo',
                JsonInputConstant::LAST_NAME => 'van Bergen',],
            "Marjo van Bergen" => [JsonInputConstant::FIRST_NAME => 'Marjo',
                JsonInputConstant::LAST_NAME => 'van Bergen',],

            "M. van Wijnen" => [JsonInputConstant::FIRST_NAME => 'M.',
                JsonInputConstant::LAST_NAME => 'van Wijnen',],

            "M.W.P. Nijssen" => [JsonInputConstant::FIRST_NAME => 'M.W.P.',
                JsonInputConstant::LAST_NAME => 'Nijssen',],

            "R. Kuipers" => [JsonInputConstant::FIRST_NAME => 'R.',
                JsonInputConstant::LAST_NAME => 'Kuipers',],

            "Rodenburg w" => [JsonInputConstant::FIRST_NAME => 'Wout',
                JsonInputConstant::LAST_NAME => 'Rodenburg',],
            "Rodenburg, W." => [JsonInputConstant::FIRST_NAME => 'Wout',
                JsonInputConstant::LAST_NAME => 'Rodenburg',],
            "W. Rodenburg" => [JsonInputConstant::FIRST_NAME => 'Wout',
                JsonInputConstant::LAST_NAME => 'Rodenburg',],
            "W.S.M. Rodenburg" => [JsonInputConstant::FIRST_NAME => 'Wout',
                JsonInputConstant::LAST_NAME => 'Rodenburg',],

            "Stijn Thijs" => [JsonInputConstant::FIRST_NAME => 'Stijn',
                JsonInputConstant::LAST_NAME => 'Thijs',],
            "Thijs" => [JsonInputConstant::FIRST_NAME => 'Stijn',
                JsonInputConstant::LAST_NAME => 'Thijs',],

            "Th. reintjes" => [JsonInputConstant::FIRST_NAME => 'Th.',
                JsonInputConstant::LAST_NAME => 'Reintjes',],

            "Johan Knaap" => [JsonInputConstant::FIRST_NAME => 'Johan',
                JsonInputConstant::LAST_NAME => 'Knaap',],

        ];
    }
}