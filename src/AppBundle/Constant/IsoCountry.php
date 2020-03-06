<?php


namespace AppBundle\Constant;


use AppBundle\Traits\EnumInfo;
use function Aws\map;

/**
 * Source: https://www.iban.com/country-codes
 */
class IsoCountry
{
    use EnumInfo;

    const ALPHA_2_CODE = 'ALPHA_2_CODE';
    const ALPHA_3_CODE = 'ALPHA_3_CODE';
    const COUNTRY_NAME = 'COUNTRY_NAME';
    const NUMERIC = 'NUMERIC';

    const LIST = [
        'AF' => [self::ALPHA_2_CODE => 'AF', self::ALPHA_3_CODE => 'AFG', self::NUMERIC => '004', self::COUNTRY_NAME => "Afghanistan"],
        'AL' => [self::ALPHA_2_CODE => 'AL', self::ALPHA_3_CODE => 'ALB', self::NUMERIC => '008', self::COUNTRY_NAME => "Albania"],
        'DZ' => [self::ALPHA_2_CODE => 'DZ', self::ALPHA_3_CODE => 'DZA', self::NUMERIC => '012', self::COUNTRY_NAME => "Algeria"],
        'AS' => [self::ALPHA_2_CODE => 'AS', self::ALPHA_3_CODE => 'ASM', self::NUMERIC => '016', self::COUNTRY_NAME => "American Samoa"],
        'AD' => [self::ALPHA_2_CODE => 'AD', self::ALPHA_3_CODE => 'AND', self::NUMERIC => '020', self::COUNTRY_NAME => "Andorra"],
        'AO' => [self::ALPHA_2_CODE => 'AO', self::ALPHA_3_CODE => 'AGO', self::NUMERIC => '024', self::COUNTRY_NAME => "Angola"],
        'AI' => [self::ALPHA_2_CODE => 'AI', self::ALPHA_3_CODE => 'AIA', self::NUMERIC => '660', self::COUNTRY_NAME => "Anguilla"],
        'AQ' => [self::ALPHA_2_CODE => 'AQ', self::ALPHA_3_CODE => 'ATA', self::NUMERIC => '010', self::COUNTRY_NAME => "Antarctica"],
        'AG' => [self::ALPHA_2_CODE => 'AG', self::ALPHA_3_CODE => 'ATG', self::NUMERIC => '028', self::COUNTRY_NAME => "Antigua and Barbuda"],
        'AR' => [self::ALPHA_2_CODE => 'AR', self::ALPHA_3_CODE => 'ARG', self::NUMERIC => '032', self::COUNTRY_NAME => "Argentina"],
        'AM' => [self::ALPHA_2_CODE => 'AM', self::ALPHA_3_CODE => 'ARM', self::NUMERIC => '051', self::COUNTRY_NAME => "Armenia"],
        'AW' => [self::ALPHA_2_CODE => 'AW', self::ALPHA_3_CODE => 'ABW', self::NUMERIC => '533', self::COUNTRY_NAME => "Aruba"],
        'AU' => [self::ALPHA_2_CODE => 'AU', self::ALPHA_3_CODE => 'AUS', self::NUMERIC => '036', self::COUNTRY_NAME => "Australia"],
        'AT' => [self::ALPHA_2_CODE => 'AT', self::ALPHA_3_CODE => 'AUT', self::NUMERIC => '040', self::COUNTRY_NAME => "Austria"],
        'AZ' => [self::ALPHA_2_CODE => 'AZ', self::ALPHA_3_CODE => 'AZE', self::NUMERIC => '031', self::COUNTRY_NAME => "Azerbaijan"],
        'BS' => [self::ALPHA_2_CODE => 'BS', self::ALPHA_3_CODE => 'BHS', self::NUMERIC => '044', self::COUNTRY_NAME => "Bahamas (the)"],
        'BH' => [self::ALPHA_2_CODE => 'BH', self::ALPHA_3_CODE => 'BHR', self::NUMERIC => '048', self::COUNTRY_NAME => "Bahrain"],
        'BD' => [self::ALPHA_2_CODE => 'BD', self::ALPHA_3_CODE => 'BGD', self::NUMERIC => '050', self::COUNTRY_NAME => "Bangladesh"],
        'BB' => [self::ALPHA_2_CODE => 'BB', self::ALPHA_3_CODE => 'BRB', self::NUMERIC => '052', self::COUNTRY_NAME => "Barbados"],
        'BY' => [self::ALPHA_2_CODE => 'BY', self::ALPHA_3_CODE => 'BLR', self::NUMERIC => '112', self::COUNTRY_NAME => "Belarus"],
        'BE' => [self::ALPHA_2_CODE => 'BE', self::ALPHA_3_CODE => 'BEL', self::NUMERIC => '056', self::COUNTRY_NAME => "Belgium"],
        'BZ' => [self::ALPHA_2_CODE => 'BZ', self::ALPHA_3_CODE => 'BLZ', self::NUMERIC => '084', self::COUNTRY_NAME => "Belize"],
        'BJ' => [self::ALPHA_2_CODE => 'BJ', self::ALPHA_3_CODE => 'BEN', self::NUMERIC => '204', self::COUNTRY_NAME => "Benin"],
        'BM' => [self::ALPHA_2_CODE => 'BM', self::ALPHA_3_CODE => 'BMU', self::NUMERIC => '060', self::COUNTRY_NAME => "Bermuda"],
        'BT' => [self::ALPHA_2_CODE => 'BT', self::ALPHA_3_CODE => 'BTN', self::NUMERIC => '064', self::COUNTRY_NAME => "Bhutan"],
        'BO' => [self::ALPHA_2_CODE => 'BO', self::ALPHA_3_CODE => 'BOL', self::NUMERIC => '068', self::COUNTRY_NAME => "Bolivia (Plurinational State of)"],
        'BQ' => [self::ALPHA_2_CODE => 'BQ', self::ALPHA_3_CODE => 'BES', self::NUMERIC => '535', self::COUNTRY_NAME => "Bonaire, Sint Eustatius and Saba"],
        'BA' => [self::ALPHA_2_CODE => 'BA', self::ALPHA_3_CODE => 'BIH', self::NUMERIC => '070', self::COUNTRY_NAME => "Bosnia and Herzegovina"],
        'BW' => [self::ALPHA_2_CODE => 'BW', self::ALPHA_3_CODE => 'BWA', self::NUMERIC => '072', self::COUNTRY_NAME => "Botswana"],
        'BV' => [self::ALPHA_2_CODE => 'BV', self::ALPHA_3_CODE => 'BVT', self::NUMERIC => '074', self::COUNTRY_NAME => "Bouvet Island"],
        'BR' => [self::ALPHA_2_CODE => 'BR', self::ALPHA_3_CODE => 'BRA', self::NUMERIC => '076', self::COUNTRY_NAME => "Brazil"],
        'IO' => [self::ALPHA_2_CODE => 'IO', self::ALPHA_3_CODE => 'IOT', self::NUMERIC => '086', self::COUNTRY_NAME => "British Indian Ocean Territory (the)"],
        'BN' => [self::ALPHA_2_CODE => 'BN', self::ALPHA_3_CODE => 'BRN', self::NUMERIC => '096', self::COUNTRY_NAME => "Brunei Darussalam"],
        'BG' => [self::ALPHA_2_CODE => 'BG', self::ALPHA_3_CODE => 'BGR', self::NUMERIC => '100', self::COUNTRY_NAME => "Bulgaria"],
        'BF' => [self::ALPHA_2_CODE => 'BF', self::ALPHA_3_CODE => 'BFA', self::NUMERIC => '854', self::COUNTRY_NAME => "Burkina Faso"],
        'BI' => [self::ALPHA_2_CODE => 'BI', self::ALPHA_3_CODE => 'BDI', self::NUMERIC => '108', self::COUNTRY_NAME => "Burundi"],
        'CV' => [self::ALPHA_2_CODE => 'CV', self::ALPHA_3_CODE => 'CPV', self::NUMERIC => '132', self::COUNTRY_NAME => "Cabo Verde"],
        'KH' => [self::ALPHA_2_CODE => 'KH', self::ALPHA_3_CODE => 'KHM', self::NUMERIC => '116', self::COUNTRY_NAME => "Cambodia"],
        'CM' => [self::ALPHA_2_CODE => 'CM', self::ALPHA_3_CODE => 'CMR', self::NUMERIC => '120', self::COUNTRY_NAME => "Cameroon"],
        'CA' => [self::ALPHA_2_CODE => 'CA', self::ALPHA_3_CODE => 'CAN', self::NUMERIC => '124', self::COUNTRY_NAME => "Canada"],
        'KY' => [self::ALPHA_2_CODE => 'KY', self::ALPHA_3_CODE => 'CYM', self::NUMERIC => '136', self::COUNTRY_NAME => "Cayman Islands (the)"],
        'CF' => [self::ALPHA_2_CODE => 'CF', self::ALPHA_3_CODE => 'CAF', self::NUMERIC => '140', self::COUNTRY_NAME => "Central African Republic (the)"],
        'TD' => [self::ALPHA_2_CODE => 'TD', self::ALPHA_3_CODE => 'TCD', self::NUMERIC => '148', self::COUNTRY_NAME => "Chad"],
        'CL' => [self::ALPHA_2_CODE => 'CL', self::ALPHA_3_CODE => 'CHL', self::NUMERIC => '152', self::COUNTRY_NAME => "Chile"],
        'CN' => [self::ALPHA_2_CODE => 'CN', self::ALPHA_3_CODE => 'CHN', self::NUMERIC => '156', self::COUNTRY_NAME => "China"],
        'CX' => [self::ALPHA_2_CODE => 'CX', self::ALPHA_3_CODE => 'CXR', self::NUMERIC => '162', self::COUNTRY_NAME => "Christmas Island"],
        'CC' => [self::ALPHA_2_CODE => 'CC', self::ALPHA_3_CODE => 'CCK', self::NUMERIC => '166', self::COUNTRY_NAME => "Cocos (Keeling) Islands (the)"],
        'CO' => [self::ALPHA_2_CODE => 'CO', self::ALPHA_3_CODE => 'COL', self::NUMERIC => '170', self::COUNTRY_NAME => "Colombia"],
        'KM' => [self::ALPHA_2_CODE => 'KM', self::ALPHA_3_CODE => 'COM', self::NUMERIC => '174', self::COUNTRY_NAME => "Comoros (the)"],
        'CD' => [self::ALPHA_2_CODE => 'CD', self::ALPHA_3_CODE => 'COD', self::NUMERIC => '180', self::COUNTRY_NAME => "Congo (the Democratic Republic of the)"],
        'CG' => [self::ALPHA_2_CODE => 'CG', self::ALPHA_3_CODE => 'COG', self::NUMERIC => '178', self::COUNTRY_NAME => "Congo (the)"],
        'CK' => [self::ALPHA_2_CODE => 'CK', self::ALPHA_3_CODE => 'COK', self::NUMERIC => '184', self::COUNTRY_NAME => "Cook Islands (the)"],
        'CR' => [self::ALPHA_2_CODE => 'CR', self::ALPHA_3_CODE => 'CRI', self::NUMERIC => '188', self::COUNTRY_NAME => "Costa Rica"],
        'HR' => [self::ALPHA_2_CODE => 'HR', self::ALPHA_3_CODE => 'HRV', self::NUMERIC => '191', self::COUNTRY_NAME => "Croatia"],
        'CU' => [self::ALPHA_2_CODE => 'CU', self::ALPHA_3_CODE => 'CUB', self::NUMERIC => '192', self::COUNTRY_NAME => "Cuba"],
        'CW' => [self::ALPHA_2_CODE => 'CW', self::ALPHA_3_CODE => 'CUW', self::NUMERIC => '531', self::COUNTRY_NAME => "Curaçao"],
        'CY' => [self::ALPHA_2_CODE => 'CY', self::ALPHA_3_CODE => 'CYP', self::NUMERIC => '196', self::COUNTRY_NAME => "Cyprus"],
        'CZ' => [self::ALPHA_2_CODE => 'CZ', self::ALPHA_3_CODE => 'CZE', self::NUMERIC => '203', self::COUNTRY_NAME => "Czechia"],
        'CI' => [self::ALPHA_2_CODE => 'CI', self::ALPHA_3_CODE => 'CIV', self::NUMERIC => '384', self::COUNTRY_NAME => "Côte d'Ivoire"],
        'DK' => [self::ALPHA_2_CODE => 'DK', self::ALPHA_3_CODE => 'DNK', self::NUMERIC => '208', self::COUNTRY_NAME => "Denmark"],
        'DJ' => [self::ALPHA_2_CODE => 'DJ', self::ALPHA_3_CODE => 'DJI', self::NUMERIC => '262', self::COUNTRY_NAME => "Djibouti"],
        'DM' => [self::ALPHA_2_CODE => 'DM', self::ALPHA_3_CODE => 'DMA', self::NUMERIC => '212', self::COUNTRY_NAME => "Dominica"],
        'DO' => [self::ALPHA_2_CODE => 'DO', self::ALPHA_3_CODE => 'DOM', self::NUMERIC => '214', self::COUNTRY_NAME => "Dominican Republic (the)"],
        'EC' => [self::ALPHA_2_CODE => 'EC', self::ALPHA_3_CODE => 'ECU', self::NUMERIC => '218', self::COUNTRY_NAME => "Ecuador"],
        'EG' => [self::ALPHA_2_CODE => 'EG', self::ALPHA_3_CODE => 'EGY', self::NUMERIC => '818', self::COUNTRY_NAME => "Egypt"],
        'SV' => [self::ALPHA_2_CODE => 'SV', self::ALPHA_3_CODE => 'SLV', self::NUMERIC => '222', self::COUNTRY_NAME => "El Salvador"],
        'GQ' => [self::ALPHA_2_CODE => 'GQ', self::ALPHA_3_CODE => 'GNQ', self::NUMERIC => '226', self::COUNTRY_NAME => "Equatorial Guinea"],
        'ER' => [self::ALPHA_2_CODE => 'ER', self::ALPHA_3_CODE => 'ERI', self::NUMERIC => '232', self::COUNTRY_NAME => "Eritrea"],
        'EE' => [self::ALPHA_2_CODE => 'EE', self::ALPHA_3_CODE => 'EST', self::NUMERIC => '233', self::COUNTRY_NAME => "Estonia"],
        'SZ' => [self::ALPHA_2_CODE => 'SZ', self::ALPHA_3_CODE => 'SWZ', self::NUMERIC => '748', self::COUNTRY_NAME => "Eswatini"],
        'ET' => [self::ALPHA_2_CODE => 'ET', self::ALPHA_3_CODE => 'ETH', self::NUMERIC => '231', self::COUNTRY_NAME => "Ethiopia"],
        'FK' => [self::ALPHA_2_CODE => 'FK', self::ALPHA_3_CODE => 'FLK', self::NUMERIC => '238', self::COUNTRY_NAME => "Falkland Islands (the) [Malvinas]"],
        'FO' => [self::ALPHA_2_CODE => 'FO', self::ALPHA_3_CODE => 'FRO', self::NUMERIC => '234', self::COUNTRY_NAME => "Faroe Islands (the)"],
        'FJ' => [self::ALPHA_2_CODE => 'FJ', self::ALPHA_3_CODE => 'FJI', self::NUMERIC => '242', self::COUNTRY_NAME => "Fiji"],
        'FI' => [self::ALPHA_2_CODE => 'FI', self::ALPHA_3_CODE => 'FIN', self::NUMERIC => '246', self::COUNTRY_NAME => "Finland"],
        'FR' => [self::ALPHA_2_CODE => 'FR', self::ALPHA_3_CODE => 'FRA', self::NUMERIC => '250', self::COUNTRY_NAME => "France"],
        'GF' => [self::ALPHA_2_CODE => 'GF', self::ALPHA_3_CODE => 'GUF', self::NUMERIC => '254', self::COUNTRY_NAME => "French Guiana"],
        'PF' => [self::ALPHA_2_CODE => 'PF', self::ALPHA_3_CODE => 'PYF', self::NUMERIC => '258', self::COUNTRY_NAME => "French Polynesia"],
        'TF' => [self::ALPHA_2_CODE => 'TF', self::ALPHA_3_CODE => 'ATF', self::NUMERIC => '260', self::COUNTRY_NAME => "French Southern Territories (the)"],
        'GA' => [self::ALPHA_2_CODE => 'GA', self::ALPHA_3_CODE => 'GAB', self::NUMERIC => '266', self::COUNTRY_NAME => "Gabon"],
        'GM' => [self::ALPHA_2_CODE => 'GM', self::ALPHA_3_CODE => 'GMB', self::NUMERIC => '270', self::COUNTRY_NAME => "Gambia (the)"],
        'GE' => [self::ALPHA_2_CODE => 'GE', self::ALPHA_3_CODE => 'GEO', self::NUMERIC => '268', self::COUNTRY_NAME => "Georgia"],
        'DE' => [self::ALPHA_2_CODE => 'DE', self::ALPHA_3_CODE => 'DEU', self::NUMERIC => '276', self::COUNTRY_NAME => "Germany"],
        'GH' => [self::ALPHA_2_CODE => 'GH', self::ALPHA_3_CODE => 'GHA', self::NUMERIC => '288', self::COUNTRY_NAME => "Ghana"],
        'GI' => [self::ALPHA_2_CODE => 'GI', self::ALPHA_3_CODE => 'GIB', self::NUMERIC => '292', self::COUNTRY_NAME => "Gibraltar"],
        'GR' => [self::ALPHA_2_CODE => 'GR', self::ALPHA_3_CODE => 'GRC', self::NUMERIC => '300', self::COUNTRY_NAME => "Greece"],
        'GL' => [self::ALPHA_2_CODE => 'GL', self::ALPHA_3_CODE => 'GRL', self::NUMERIC => '304', self::COUNTRY_NAME => "Greenland"],
        'GD' => [self::ALPHA_2_CODE => 'GD', self::ALPHA_3_CODE => 'GRD', self::NUMERIC => '308', self::COUNTRY_NAME => "Grenada"],
        'GP' => [self::ALPHA_2_CODE => 'GP', self::ALPHA_3_CODE => 'GLP', self::NUMERIC => '312', self::COUNTRY_NAME => "Guadeloupe"],
        'GU' => [self::ALPHA_2_CODE => 'GU', self::ALPHA_3_CODE => 'GUM', self::NUMERIC => '316', self::COUNTRY_NAME => "Guam"],
        'GT' => [self::ALPHA_2_CODE => 'GT', self::ALPHA_3_CODE => 'GTM', self::NUMERIC => '320', self::COUNTRY_NAME => "Guatemala"],
        'GG' => [self::ALPHA_2_CODE => 'GG', self::ALPHA_3_CODE => 'GGY', self::NUMERIC => '831', self::COUNTRY_NAME => "Guernsey"],
        'GN' => [self::ALPHA_2_CODE => 'GN', self::ALPHA_3_CODE => 'GIN', self::NUMERIC => '324', self::COUNTRY_NAME => "Guinea"],
        'GW' => [self::ALPHA_2_CODE => 'GW', self::ALPHA_3_CODE => 'GNB', self::NUMERIC => '624', self::COUNTRY_NAME => "Guinea-Bissau"],
        'GY' => [self::ALPHA_2_CODE => 'GY', self::ALPHA_3_CODE => 'GUY', self::NUMERIC => '328', self::COUNTRY_NAME => "Guyana"],
        'HT' => [self::ALPHA_2_CODE => 'HT', self::ALPHA_3_CODE => 'HTI', self::NUMERIC => '332', self::COUNTRY_NAME => "Haiti"],
        'HM' => [self::ALPHA_2_CODE => 'HM', self::ALPHA_3_CODE => 'HMD', self::NUMERIC => '334', self::COUNTRY_NAME => "Heard Island and McDonald Islands"],
        'VA' => [self::ALPHA_2_CODE => 'VA', self::ALPHA_3_CODE => 'VAT', self::NUMERIC => '336', self::COUNTRY_NAME => "Holy See (the)"],
        'HN' => [self::ALPHA_2_CODE => 'HN', self::ALPHA_3_CODE => 'HND', self::NUMERIC => '340', self::COUNTRY_NAME => "Honduras"],
        'HK' => [self::ALPHA_2_CODE => 'HK', self::ALPHA_3_CODE => 'HKG', self::NUMERIC => '344', self::COUNTRY_NAME => "Hong Kong"],
        'HU' => [self::ALPHA_2_CODE => 'HU', self::ALPHA_3_CODE => 'HUN', self::NUMERIC => '348', self::COUNTRY_NAME => "Hungary"],
        'IS' => [self::ALPHA_2_CODE => 'IS', self::ALPHA_3_CODE => 'ISL', self::NUMERIC => '352', self::COUNTRY_NAME => "Iceland"],
        'IN' => [self::ALPHA_2_CODE => 'IN', self::ALPHA_3_CODE => 'IND', self::NUMERIC => '356', self::COUNTRY_NAME => "India"],
        'ID' => [self::ALPHA_2_CODE => 'ID', self::ALPHA_3_CODE => 'IDN', self::NUMERIC => '360', self::COUNTRY_NAME => "Indonesia"],
        'IR' => [self::ALPHA_2_CODE => 'IR', self::ALPHA_3_CODE => 'IRN', self::NUMERIC => '364', self::COUNTRY_NAME => "Iran (Islamic Republic of)"],
        'IQ' => [self::ALPHA_2_CODE => 'IQ', self::ALPHA_3_CODE => 'IRQ', self::NUMERIC => '368', self::COUNTRY_NAME => "Iraq"],
        'IE' => [self::ALPHA_2_CODE => 'IE', self::ALPHA_3_CODE => 'IRL', self::NUMERIC => '372', self::COUNTRY_NAME => "Ireland"],
        'IM' => [self::ALPHA_2_CODE => 'IM', self::ALPHA_3_CODE => 'IMN', self::NUMERIC => '833', self::COUNTRY_NAME => "Isle of Man"],
        'IL' => [self::ALPHA_2_CODE => 'IL', self::ALPHA_3_CODE => 'ISR', self::NUMERIC => '376', self::COUNTRY_NAME => "Israel"],
        'IT' => [self::ALPHA_2_CODE => 'IT', self::ALPHA_3_CODE => 'ITA', self::NUMERIC => '380', self::COUNTRY_NAME => "Italy"],
        'JM' => [self::ALPHA_2_CODE => 'JM', self::ALPHA_3_CODE => 'JAM', self::NUMERIC => '388', self::COUNTRY_NAME => "Jamaica"],
        'JP' => [self::ALPHA_2_CODE => 'JP', self::ALPHA_3_CODE => 'JPN', self::NUMERIC => '392', self::COUNTRY_NAME => "Japan"],
        'JE' => [self::ALPHA_2_CODE => 'JE', self::ALPHA_3_CODE => 'JEY', self::NUMERIC => '832', self::COUNTRY_NAME => "Jersey"],
        'JO' => [self::ALPHA_2_CODE => 'JO', self::ALPHA_3_CODE => 'JOR', self::NUMERIC => '400', self::COUNTRY_NAME => "Jordan"],
        'KZ' => [self::ALPHA_2_CODE => 'KZ', self::ALPHA_3_CODE => 'KAZ', self::NUMERIC => '398', self::COUNTRY_NAME => "Kazakhstan"],
        'KE' => [self::ALPHA_2_CODE => 'KE', self::ALPHA_3_CODE => 'KEN', self::NUMERIC => '404', self::COUNTRY_NAME => "Kenya"],
        'KI' => [self::ALPHA_2_CODE => 'KI', self::ALPHA_3_CODE => 'KIR', self::NUMERIC => '296', self::COUNTRY_NAME => "Kiribati"],
        'KP' => [self::ALPHA_2_CODE => 'KP', self::ALPHA_3_CODE => 'PRK', self::NUMERIC => '408', self::COUNTRY_NAME => "Korea (the Democratic People's Republic of)"],
        'KR' => [self::ALPHA_2_CODE => 'KR', self::ALPHA_3_CODE => 'KOR', self::NUMERIC => '410', self::COUNTRY_NAME => "Korea (the Republic of)"],
        'KW' => [self::ALPHA_2_CODE => 'KW', self::ALPHA_3_CODE => 'KWT', self::NUMERIC => '414', self::COUNTRY_NAME => "Kuwait"],
        'KG' => [self::ALPHA_2_CODE => 'KG', self::ALPHA_3_CODE => 'KGZ', self::NUMERIC => '417', self::COUNTRY_NAME => "Kyrgyzstan"],
        'LA' => [self::ALPHA_2_CODE => 'LA', self::ALPHA_3_CODE => 'LAO', self::NUMERIC => '418', self::COUNTRY_NAME => "Lao People's Democratic Republic (the)"],
        'LV' => [self::ALPHA_2_CODE => 'LV', self::ALPHA_3_CODE => 'LVA', self::NUMERIC => '428', self::COUNTRY_NAME => "Latvia"],
        'LB' => [self::ALPHA_2_CODE => 'LB', self::ALPHA_3_CODE => 'LBN', self::NUMERIC => '422', self::COUNTRY_NAME => "Lebanon"],
        'LS' => [self::ALPHA_2_CODE => 'LS', self::ALPHA_3_CODE => 'LSO', self::NUMERIC => '426', self::COUNTRY_NAME => "Lesotho"],
        'LR' => [self::ALPHA_2_CODE => 'LR', self::ALPHA_3_CODE => 'LBR', self::NUMERIC => '430', self::COUNTRY_NAME => "Liberia"],
        'LY' => [self::ALPHA_2_CODE => 'LY', self::ALPHA_3_CODE => 'LBY', self::NUMERIC => '434', self::COUNTRY_NAME => "Libya"],
        'LI' => [self::ALPHA_2_CODE => 'LI', self::ALPHA_3_CODE => 'LIE', self::NUMERIC => '438', self::COUNTRY_NAME => "Liechtenstein"],
        'LT' => [self::ALPHA_2_CODE => 'LT', self::ALPHA_3_CODE => 'LTU', self::NUMERIC => '440', self::COUNTRY_NAME => "Lithuania"],
        'LU' => [self::ALPHA_2_CODE => 'LU', self::ALPHA_3_CODE => 'LUX', self::NUMERIC => '442', self::COUNTRY_NAME => "Luxembourg"],
        'MO' => [self::ALPHA_2_CODE => 'MO', self::ALPHA_3_CODE => 'MAC', self::NUMERIC => '446', self::COUNTRY_NAME => "Macao"],
        'MG' => [self::ALPHA_2_CODE => 'MG', self::ALPHA_3_CODE => 'MDG', self::NUMERIC => '450', self::COUNTRY_NAME => "Madagascar"],
        'MW' => [self::ALPHA_2_CODE => 'MW', self::ALPHA_3_CODE => 'MWI', self::NUMERIC => '454', self::COUNTRY_NAME => "Malawi"],
        'MY' => [self::ALPHA_2_CODE => 'MY', self::ALPHA_3_CODE => 'MYS', self::NUMERIC => '458', self::COUNTRY_NAME => "Malaysia"],
        'MV' => [self::ALPHA_2_CODE => 'MV', self::ALPHA_3_CODE => 'MDV', self::NUMERIC => '462', self::COUNTRY_NAME => "Maldives"],
        'ML' => [self::ALPHA_2_CODE => 'ML', self::ALPHA_3_CODE => 'MLI', self::NUMERIC => '466', self::COUNTRY_NAME => "Mali"],
        'MT' => [self::ALPHA_2_CODE => 'MT', self::ALPHA_3_CODE => 'MLT', self::NUMERIC => '470', self::COUNTRY_NAME => "Malta"],
        'MH' => [self::ALPHA_2_CODE => 'MH', self::ALPHA_3_CODE => 'MHL', self::NUMERIC => '584', self::COUNTRY_NAME => "Marshall Islands (the)"],
        'MQ' => [self::ALPHA_2_CODE => 'MQ', self::ALPHA_3_CODE => 'MTQ', self::NUMERIC => '474', self::COUNTRY_NAME => "Martinique"],
        'MR' => [self::ALPHA_2_CODE => 'MR', self::ALPHA_3_CODE => 'MRT', self::NUMERIC => '478', self::COUNTRY_NAME => "Mauritania"],
        'MU' => [self::ALPHA_2_CODE => 'MU', self::ALPHA_3_CODE => 'MUS', self::NUMERIC => '480', self::COUNTRY_NAME => "Mauritius"],
        'YT' => [self::ALPHA_2_CODE => 'YT', self::ALPHA_3_CODE => 'MYT', self::NUMERIC => '175', self::COUNTRY_NAME => "Mayotte"],
        'MX' => [self::ALPHA_2_CODE => 'MX', self::ALPHA_3_CODE => 'MEX', self::NUMERIC => '484', self::COUNTRY_NAME => "Mexico"],
        'FM' => [self::ALPHA_2_CODE => 'FM', self::ALPHA_3_CODE => 'FSM', self::NUMERIC => '583', self::COUNTRY_NAME => "Micronesia (Federated States of)"],
        'MD' => [self::ALPHA_2_CODE => 'MD', self::ALPHA_3_CODE => 'MDA', self::NUMERIC => '498', self::COUNTRY_NAME => "Moldova (the Republic of)"],
        'MC' => [self::ALPHA_2_CODE => 'MC', self::ALPHA_3_CODE => 'MCO', self::NUMERIC => '492', self::COUNTRY_NAME => "Monaco"],
        'MN' => [self::ALPHA_2_CODE => 'MN', self::ALPHA_3_CODE => 'MNG', self::NUMERIC => '496', self::COUNTRY_NAME => "Mongolia"],
        'ME' => [self::ALPHA_2_CODE => 'ME', self::ALPHA_3_CODE => 'MNE', self::NUMERIC => '499', self::COUNTRY_NAME => "Montenegro"],
        'MS' => [self::ALPHA_2_CODE => 'MS', self::ALPHA_3_CODE => 'MSR', self::NUMERIC => '500', self::COUNTRY_NAME => "Montserrat"],
        'MA' => [self::ALPHA_2_CODE => 'MA', self::ALPHA_3_CODE => 'MAR', self::NUMERIC => '504', self::COUNTRY_NAME => "Morocco"],
        'MZ' => [self::ALPHA_2_CODE => 'MZ', self::ALPHA_3_CODE => 'MOZ', self::NUMERIC => '508', self::COUNTRY_NAME => "Mozambique"],
        'MM' => [self::ALPHA_2_CODE => 'MM', self::ALPHA_3_CODE => 'MMR', self::NUMERIC => '104', self::COUNTRY_NAME => "Myanmar"],
        'NA' => [self::ALPHA_2_CODE => 'NA', self::ALPHA_3_CODE => 'NAM', self::NUMERIC => '516', self::COUNTRY_NAME => "Namibia"],
        'NR' => [self::ALPHA_2_CODE => 'NR', self::ALPHA_3_CODE => 'NRU', self::NUMERIC => '520', self::COUNTRY_NAME => "Nauru"],
        'NP' => [self::ALPHA_2_CODE => 'NP', self::ALPHA_3_CODE => 'NPL', self::NUMERIC => '524', self::COUNTRY_NAME => "Nepal"],
        'NL' => [self::ALPHA_2_CODE => 'NL', self::ALPHA_3_CODE => 'NLD', self::NUMERIC => '528', self::COUNTRY_NAME => "Netherlands (the)"],
        'NC' => [self::ALPHA_2_CODE => 'NC', self::ALPHA_3_CODE => 'NCL', self::NUMERIC => '540', self::COUNTRY_NAME => "New Caledonia"],
        'NZ' => [self::ALPHA_2_CODE => 'NZ', self::ALPHA_3_CODE => 'NZL', self::NUMERIC => '554', self::COUNTRY_NAME => "New Zealand"],
        'NI' => [self::ALPHA_2_CODE => 'NI', self::ALPHA_3_CODE => 'NIC', self::NUMERIC => '558', self::COUNTRY_NAME => "Nicaragua"],
        'NE' => [self::ALPHA_2_CODE => 'NE', self::ALPHA_3_CODE => 'NER', self::NUMERIC => '562', self::COUNTRY_NAME => "Niger (the)"],
        'NG' => [self::ALPHA_2_CODE => 'NG', self::ALPHA_3_CODE => 'NGA', self::NUMERIC => '566', self::COUNTRY_NAME => "Nigeria"],
        'NU' => [self::ALPHA_2_CODE => 'NU', self::ALPHA_3_CODE => 'NIU', self::NUMERIC => '570', self::COUNTRY_NAME => "Niue"],
        'NF' => [self::ALPHA_2_CODE => 'NF', self::ALPHA_3_CODE => 'NFK', self::NUMERIC => '574', self::COUNTRY_NAME => "Norfolk Island"],
        'MP' => [self::ALPHA_2_CODE => 'MP', self::ALPHA_3_CODE => 'MNP', self::NUMERIC => '580', self::COUNTRY_NAME => "Northern Mariana Islands (the)"],
        'NO' => [self::ALPHA_2_CODE => 'NO', self::ALPHA_3_CODE => 'NOR', self::NUMERIC => '578', self::COUNTRY_NAME => "Norway"],
        'OM' => [self::ALPHA_2_CODE => 'OM', self::ALPHA_3_CODE => 'OMN', self::NUMERIC => '512', self::COUNTRY_NAME => "Oman"],
        'PK' => [self::ALPHA_2_CODE => 'PK', self::ALPHA_3_CODE => 'PAK', self::NUMERIC => '586', self::COUNTRY_NAME => "Pakistan"],
        'PW' => [self::ALPHA_2_CODE => 'PW', self::ALPHA_3_CODE => 'PLW', self::NUMERIC => '585', self::COUNTRY_NAME => "Palau"],
        'PS' => [self::ALPHA_2_CODE => 'PS', self::ALPHA_3_CODE => 'PSE', self::NUMERIC => '275', self::COUNTRY_NAME => "Palestine, State of"],
        'PA' => [self::ALPHA_2_CODE => 'PA', self::ALPHA_3_CODE => 'PAN', self::NUMERIC => '591', self::COUNTRY_NAME => "Panama"],
        'PG' => [self::ALPHA_2_CODE => 'PG', self::ALPHA_3_CODE => 'PNG', self::NUMERIC => '598', self::COUNTRY_NAME => "Papua New Guinea"],
        'PY' => [self::ALPHA_2_CODE => 'PY', self::ALPHA_3_CODE => 'PRY', self::NUMERIC => '600', self::COUNTRY_NAME => "Paraguay"],
        'PE' => [self::ALPHA_2_CODE => 'PE', self::ALPHA_3_CODE => 'PER', self::NUMERIC => '604', self::COUNTRY_NAME => "Peru"],
        'PH' => [self::ALPHA_2_CODE => 'PH', self::ALPHA_3_CODE => 'PHL', self::NUMERIC => '608', self::COUNTRY_NAME => "Philippines (the)"],
        'PN' => [self::ALPHA_2_CODE => 'PN', self::ALPHA_3_CODE => 'PCN', self::NUMERIC => '612', self::COUNTRY_NAME => "Pitcairn"],
        'PL' => [self::ALPHA_2_CODE => 'PL', self::ALPHA_3_CODE => 'POL', self::NUMERIC => '616', self::COUNTRY_NAME => "Poland"],
        'PT' => [self::ALPHA_2_CODE => 'PT', self::ALPHA_3_CODE => 'PRT', self::NUMERIC => '620', self::COUNTRY_NAME => "Portugal"],
        'PR' => [self::ALPHA_2_CODE => 'PR', self::ALPHA_3_CODE => 'PRI', self::NUMERIC => '630', self::COUNTRY_NAME => "Puerto Rico"],
        'QA' => [self::ALPHA_2_CODE => 'QA', self::ALPHA_3_CODE => 'QAT', self::NUMERIC => '634', self::COUNTRY_NAME => "Qatar"],
        'MK' => [self::ALPHA_2_CODE => 'MK', self::ALPHA_3_CODE => 'MKD', self::NUMERIC => '807', self::COUNTRY_NAME => "Republic of North Macedonia"],
        'RO' => [self::ALPHA_2_CODE => 'RO', self::ALPHA_3_CODE => 'ROU', self::NUMERIC => '642', self::COUNTRY_NAME => "Romania"],
        'RU' => [self::ALPHA_2_CODE => 'RU', self::ALPHA_3_CODE => 'RUS', self::NUMERIC => '643', self::COUNTRY_NAME => "Russian Federation (the)"],
        'RW' => [self::ALPHA_2_CODE => 'RW', self::ALPHA_3_CODE => 'RWA', self::NUMERIC => '646', self::COUNTRY_NAME => "Rwanda"],
        'RE' => [self::ALPHA_2_CODE => 'RE', self::ALPHA_3_CODE => 'REU', self::NUMERIC => '638', self::COUNTRY_NAME => "Réunion"],
        'BL' => [self::ALPHA_2_CODE => 'BL', self::ALPHA_3_CODE => 'BLM', self::NUMERIC => '652', self::COUNTRY_NAME => "Saint Barthélemy"],
        'SH' => [self::ALPHA_2_CODE => 'SH', self::ALPHA_3_CODE => 'SHN', self::NUMERIC => '654', self::COUNTRY_NAME => "Saint Helena, Ascension and Tristan da Cunha"],
        'KN' => [self::ALPHA_2_CODE => 'KN', self::ALPHA_3_CODE => 'KNA', self::NUMERIC => '659', self::COUNTRY_NAME => "Saint Kitts and Nevis"],
        'LC' => [self::ALPHA_2_CODE => 'LC', self::ALPHA_3_CODE => 'LCA', self::NUMERIC => '662', self::COUNTRY_NAME => "Saint Lucia"],
        'MF' => [self::ALPHA_2_CODE => 'MF', self::ALPHA_3_CODE => 'MAF', self::NUMERIC => '663', self::COUNTRY_NAME => "Saint Martin (French part)"],
        'PM' => [self::ALPHA_2_CODE => 'PM', self::ALPHA_3_CODE => 'SPM', self::NUMERIC => '666', self::COUNTRY_NAME => "Saint Pierre and Miquelon"],
        'VC' => [self::ALPHA_2_CODE => 'VC', self::ALPHA_3_CODE => 'VCT', self::NUMERIC => '670', self::COUNTRY_NAME => "Saint Vincent and the Grenadines"],
        'WS' => [self::ALPHA_2_CODE => 'WS', self::ALPHA_3_CODE => 'WSM', self::NUMERIC => '882', self::COUNTRY_NAME => "Samoa"],
        'SM' => [self::ALPHA_2_CODE => 'SM', self::ALPHA_3_CODE => 'SMR', self::NUMERIC => '674', self::COUNTRY_NAME => "San Marino"],
        'ST' => [self::ALPHA_2_CODE => 'ST', self::ALPHA_3_CODE => 'STP', self::NUMERIC => '678', self::COUNTRY_NAME => "Sao Tome and Principe"],
        'SA' => [self::ALPHA_2_CODE => 'SA', self::ALPHA_3_CODE => 'SAU', self::NUMERIC => '682', self::COUNTRY_NAME => "Saudi Arabia"],
        'SN' => [self::ALPHA_2_CODE => 'SN', self::ALPHA_3_CODE => 'SEN', self::NUMERIC => '686', self::COUNTRY_NAME => "Senegal"],
        'RS' => [self::ALPHA_2_CODE => 'RS', self::ALPHA_3_CODE => 'SRB', self::NUMERIC => '688', self::COUNTRY_NAME => "Serbia"],
        'SC' => [self::ALPHA_2_CODE => 'SC', self::ALPHA_3_CODE => 'SYC', self::NUMERIC => '690', self::COUNTRY_NAME => "Seychelles"],
        'SL' => [self::ALPHA_2_CODE => 'SL', self::ALPHA_3_CODE => 'SLE', self::NUMERIC => '694', self::COUNTRY_NAME => "Sierra Leone"],
        'SG' => [self::ALPHA_2_CODE => 'SG', self::ALPHA_3_CODE => 'SGP', self::NUMERIC => '702', self::COUNTRY_NAME => "Singapore"],
        'SX' => [self::ALPHA_2_CODE => 'SX', self::ALPHA_3_CODE => 'SXM', self::NUMERIC => '534', self::COUNTRY_NAME => "Sint Maarten (Dutch part)"],
        'SK' => [self::ALPHA_2_CODE => 'SK', self::ALPHA_3_CODE => 'SVK', self::NUMERIC => '703', self::COUNTRY_NAME => "Slovakia"],
        'SI' => [self::ALPHA_2_CODE => 'SI', self::ALPHA_3_CODE => 'SVN', self::NUMERIC => '705', self::COUNTRY_NAME => "Slovenia"],
        'SB' => [self::ALPHA_2_CODE => 'SB', self::ALPHA_3_CODE => 'SLB', self::NUMERIC => '090', self::COUNTRY_NAME => "Solomon Islands"],
        'SO' => [self::ALPHA_2_CODE => 'SO', self::ALPHA_3_CODE => 'SOM', self::NUMERIC => '706', self::COUNTRY_NAME => "Somalia"],
        'ZA' => [self::ALPHA_2_CODE => 'ZA', self::ALPHA_3_CODE => 'ZAF', self::NUMERIC => '710', self::COUNTRY_NAME => "South Africa"],
        'GS' => [self::ALPHA_2_CODE => 'GS', self::ALPHA_3_CODE => 'SGS', self::NUMERIC => '239', self::COUNTRY_NAME => "South Georgia and the South Sandwich Islands"],
        'SS' => [self::ALPHA_2_CODE => 'SS', self::ALPHA_3_CODE => 'SSD', self::NUMERIC => '728', self::COUNTRY_NAME => "South Sudan"],
        'ES' => [self::ALPHA_2_CODE => 'ES', self::ALPHA_3_CODE => 'ESP', self::NUMERIC => '724', self::COUNTRY_NAME => "Spain"],
        'LK' => [self::ALPHA_2_CODE => 'LK', self::ALPHA_3_CODE => 'LKA', self::NUMERIC => '144', self::COUNTRY_NAME => "Sri Lanka"],
        'SD' => [self::ALPHA_2_CODE => 'SD', self::ALPHA_3_CODE => 'SDN', self::NUMERIC => '729', self::COUNTRY_NAME => "Sudan (the)"],
        'SR' => [self::ALPHA_2_CODE => 'SR', self::ALPHA_3_CODE => 'SUR', self::NUMERIC => '740', self::COUNTRY_NAME => "Suriname"],
        'SJ' => [self::ALPHA_2_CODE => 'SJ', self::ALPHA_3_CODE => 'SJM', self::NUMERIC => '744', self::COUNTRY_NAME => "Svalbard and Jan Mayen"],
        'SE' => [self::ALPHA_2_CODE => 'SE', self::ALPHA_3_CODE => 'SWE', self::NUMERIC => '752', self::COUNTRY_NAME => "Sweden"],
        'CH' => [self::ALPHA_2_CODE => 'CH', self::ALPHA_3_CODE => 'CHE', self::NUMERIC => '756', self::COUNTRY_NAME => "Switzerland"],
        'SY' => [self::ALPHA_2_CODE => 'SY', self::ALPHA_3_CODE => 'SYR', self::NUMERIC => '760', self::COUNTRY_NAME => "Syrian Arab Republic"],
        'TW' => [self::ALPHA_2_CODE => 'TW', self::ALPHA_3_CODE => 'TWN', self::NUMERIC => '158', self::COUNTRY_NAME => "Taiwan (Province of China)"],
        'TJ' => [self::ALPHA_2_CODE => 'TJ', self::ALPHA_3_CODE => 'TJK', self::NUMERIC => '762', self::COUNTRY_NAME => "Tajikistan"],
        'TZ' => [self::ALPHA_2_CODE => 'TZ', self::ALPHA_3_CODE => 'TZA', self::NUMERIC => '834', self::COUNTRY_NAME => "Tanzania, United Republic of"],
        'TH' => [self::ALPHA_2_CODE => 'TH', self::ALPHA_3_CODE => 'THA', self::NUMERIC => '764', self::COUNTRY_NAME => "Thailand"],
        'TL' => [self::ALPHA_2_CODE => 'TL', self::ALPHA_3_CODE => 'TLS', self::NUMERIC => '626', self::COUNTRY_NAME => "Timor-Leste"],
        'TG' => [self::ALPHA_2_CODE => 'TG', self::ALPHA_3_CODE => 'TGO', self::NUMERIC => '768', self::COUNTRY_NAME => "Togo"],
        'TK' => [self::ALPHA_2_CODE => 'TK', self::ALPHA_3_CODE => 'TKL', self::NUMERIC => '772', self::COUNTRY_NAME => "Tokelau"],
        'TO' => [self::ALPHA_2_CODE => 'TO', self::ALPHA_3_CODE => 'TON', self::NUMERIC => '776', self::COUNTRY_NAME => "Tonga"],
        'TT' => [self::ALPHA_2_CODE => 'TT', self::ALPHA_3_CODE => 'TTO', self::NUMERIC => '780', self::COUNTRY_NAME => "Trinidad and Tobago"],
        'TN' => [self::ALPHA_2_CODE => 'TN', self::ALPHA_3_CODE => 'TUN', self::NUMERIC => '788', self::COUNTRY_NAME => "Tunisia"],
        'TR' => [self::ALPHA_2_CODE => 'TR', self::ALPHA_3_CODE => 'TUR', self::NUMERIC => '792', self::COUNTRY_NAME => "Turkey"],
        'TM' => [self::ALPHA_2_CODE => 'TM', self::ALPHA_3_CODE => 'TKM', self::NUMERIC => '795', self::COUNTRY_NAME => "Turkmenistan"],
        'TC' => [self::ALPHA_2_CODE => 'TC', self::ALPHA_3_CODE => 'TCA', self::NUMERIC => '796', self::COUNTRY_NAME => "Turks and Caicos Islands (the)"],
        'TV' => [self::ALPHA_2_CODE => 'TV', self::ALPHA_3_CODE => 'TUV', self::NUMERIC => '798', self::COUNTRY_NAME => "Tuvalu"],
        'UG' => [self::ALPHA_2_CODE => 'UG', self::ALPHA_3_CODE => 'UGA', self::NUMERIC => '800', self::COUNTRY_NAME => "Uganda"],
        'UA' => [self::ALPHA_2_CODE => 'UA', self::ALPHA_3_CODE => 'UKR', self::NUMERIC => '804', self::COUNTRY_NAME => "Ukraine"],
        'AE' => [self::ALPHA_2_CODE => 'AE', self::ALPHA_3_CODE => 'ARE', self::NUMERIC => '784', self::COUNTRY_NAME => "United Arab Emirates (the)"],
        'GB' => [self::ALPHA_2_CODE => 'GB', self::ALPHA_3_CODE => 'GBR', self::NUMERIC => '826', self::COUNTRY_NAME => "United Kingdom of Great Britain and Northern Ireland (the)"],
        'UM' => [self::ALPHA_2_CODE => 'UM', self::ALPHA_3_CODE => 'UMI', self::NUMERIC => '581', self::COUNTRY_NAME => "United States Minor Outlying Islands (the)"],
        'US' => [self::ALPHA_2_CODE => 'US', self::ALPHA_3_CODE => 'USA', self::NUMERIC => '840', self::COUNTRY_NAME => "United States of America (the)"],
        'UY' => [self::ALPHA_2_CODE => 'UY', self::ALPHA_3_CODE => 'URY', self::NUMERIC => '858', self::COUNTRY_NAME => "Uruguay"],
        'UZ' => [self::ALPHA_2_CODE => 'UZ', self::ALPHA_3_CODE => 'UZB', self::NUMERIC => '860', self::COUNTRY_NAME => "Uzbekistan"],
        'VU' => [self::ALPHA_2_CODE => 'VU', self::ALPHA_3_CODE => 'VUT', self::NUMERIC => '548', self::COUNTRY_NAME => "Vanuatu"],
        'VE' => [self::ALPHA_2_CODE => 'VE', self::ALPHA_3_CODE => 'VEN', self::NUMERIC => '862', self::COUNTRY_NAME => "Venezuela (Bolivarian Republic of)"],
        'VN' => [self::ALPHA_2_CODE => 'VN', self::ALPHA_3_CODE => 'VNM', self::NUMERIC => '704', self::COUNTRY_NAME => "Viet Nam"],
        'VG' => [self::ALPHA_2_CODE => 'VG', self::ALPHA_3_CODE => 'VGB', self::NUMERIC => '092', self::COUNTRY_NAME => "Virgin Islands (British)"],
        'VI' => [self::ALPHA_2_CODE => 'VI', self::ALPHA_3_CODE => 'VIR', self::NUMERIC => '850', self::COUNTRY_NAME => "Virgin Islands (U.S.)"],
        'WF' => [self::ALPHA_2_CODE => 'WF', self::ALPHA_3_CODE => 'WLF', self::NUMERIC => '876', self::COUNTRY_NAME => "Wallis and Futuna"],
        'EH' => [self::ALPHA_2_CODE => 'EH', self::ALPHA_3_CODE => 'ESH', self::NUMERIC => '732', self::COUNTRY_NAME => "Western Sahara"],
        'YE' => [self::ALPHA_2_CODE => 'YE', self::ALPHA_3_CODE => 'YEM', self::NUMERIC => '887', self::COUNTRY_NAME => "Yemen"],
        'ZM' => [self::ALPHA_2_CODE => 'ZM', self::ALPHA_3_CODE => 'ZMB', self::NUMERIC => '894', self::COUNTRY_NAME => "Zambia"],
        'ZW' => [self::ALPHA_2_CODE => 'ZW', self::ALPHA_3_CODE => 'ZWE', self::NUMERIC => '716', self::COUNTRY_NAME => "Zimbabwe"],
        'AX' => [self::ALPHA_2_CODE => 'AX', self::ALPHA_3_CODE => 'ALA', self::NUMERIC => '248', self::COUNTRY_NAME => "Åland Islands"],
    ];

    static function numericByAlpha2(): array {
        return array_map(
            function(array $values) {
                return $values[self::NUMERIC];
            },
            self::LIST
        );
    }
}