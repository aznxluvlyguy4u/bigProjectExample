<?php


namespace AppBundle\Enumerator;


use AppBundle\Traits\EnumInfo;

class ReportType
{
    use EnumInfo;

    /**
     * Note that these enum values should match the ReportType enum class in the frontend.
     * The enum values do not have to match the report numbers as listed in the JVT-NSFO overview
     */
    const ANNUAL_ACTIVE_LIVE_STOCK = 1;
    const ANNUAL_TE_100 = 2; # Report number 5
    const FERTILIZER_ACCOUNTING = 3; # Report number 7
    const INBREEDING_COEFFICIENT = 4;
    const PEDIGREE_CERTIFICATE = 5; # Report number 1
    const ANIMALS_OVERVIEW = 6;
    const ANNUAL_ACTIVE_LIVE_STOCK_RAM_MATES = 7;
    const OFFSPRING = 8; # Report number 6
    const PEDIGREE_REGISTER_OVERVIEW = 9;
    const LIVE_STOCK = 10;
    const BIRTH_LIST = 11; # Report number 23
    const MEMBERS_AND_USERS_OVERVIEW = 12; # Report number 8
    const ANIMAL_HEALTH_STATUSES = 13; # Report number 11
    const QUICK_VIEW_KPIS = 14; # Report number 21
    const COMPANY_REGISTER = 15; # Report number 22
    const ANIMAL_FEATURES_PER_YEAR_OF_BIRTH = 16; # Report number 16
    const CLIENT_NOTES_OVERVIEW = 17; # Report number 18
    const STAR_EWES = 18; # Report number 13
    const COMBI_FORMS_VKI_AND_TRANSPORT_DOCUMENTS = 19; # Report number 24
    const EWE_CARD = 20; # Report number 9
    const BLOOD_AND_TISSUE_INVESTIGATIONS = 21; # Report number 10
    const I_AND_R = 22; # Report number 12
    const POPREP_INPUT_FILE = 23; # Report number 14
    const REASONS_DEPART_AND_LOSS = 24; # Report number 15
    const WEIGHTS_PER_YEAR_OF_BIRTH = 25; # Report number 17
    const TREATMENTS = 26; # Report number 19
    const MODEL_EXPORT_CERTIFICATE = 27; # Report number 20
    const ACTION_LOG = 28;
}
