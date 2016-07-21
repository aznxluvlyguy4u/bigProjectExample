<?php

namespace AppBundle\Constant;


class ReportLabel
{
    //General
    const DATE = 'date';
    const ENTITY = 'entity';

    //Person
    const FIRST_NAME = "first_name";
    const LAST_NAME = "last_name";
    const OWNER_NAME = "owner_name";
    const OWNER = 'owner';

    //Breeder
    const BREEDER = 'breeder';
    const BREEDER_NAME = "breeder_name";
    const BREEDER_NAME_CROPPED = "breeder_name_cropped";
    const BREEDER_NUMBER = 'breeder_number';

    //Address
    const ADDRESS = 'address';
    const ADDRESS_BREEDER = 'address_breeder';
    const UBN = 'ubn';

    //Animal
    const ANIMALS = 'animals';
    const GENDER = 'gender';
    const ULN_COUNTRY_CODE = 'uln_country_code';
    const ULN_NUMBER = 'uln_number';
    const ULN_CODE_FULL = 'uln_code_full';
    const PEDIGREE_COUNTRY_CODE = 'pedrigree_country_code';
    const PEDIGREE_NUMBER = 'pedrigree_number';
    const PEDIGREE_CODE_FULL = 'pedrigree_code_full';
    const DATE_OF_BIRTH = 'date_of_birth';
    const LITTER_SIZE = 'litter_size';
    const LITTER_GROUP = 'litter_group';
    const SCRAPIE_GENE = 'scrapie_gene';
    const PRODUCTION_CODE = 'production_code';
    const BREED_STATUS = 'breed_status';
    const INSPECTION_DATE = 'inspection_date';
    const PREDICATE = 'predicate';
    const LATEST_EXTERIOR = 'latest_exterior';
    const MEASUREMENT_DATE = 'measurement_date';
    const LATEST_MUSCLE_THICKNESS = 'latest_muscle_thickness';
    const LATEST_BODY_FAT = 'latest_body_fat';
    const LATEST_TAIL_LENGTH = 'latest_tail_length';

    //Pedrigree / Bloodline
    const GENERATION = 'generation';
    const CHILD = 'child';
    const FATHER = 'father';
    const MOTHER = 'mother';
    const _S_FATHER = '_s_father';
    const _S_MOTHER = '_s_mother';
    
    const FATHER_S_FATHER = 'father_s_father';
    const FATHER_S_FATHER_S_FATHER = 'father_s_father_s_father';
    const FATHER_S_FATHER_S_MOTHER = 'father_s_father_s_mother';

    const FATHER_S_MOTHER = 'father_s_mother';
    const FATHER_S_MOTHER_S_FATHER = 'father_s_mother_s_father';
    const FATHER_S_MOTHER_S_MOTHER = 'father_s_mother_s_mother';

    const MOTHER_S_FATHER = 'mother_s_father';
    const MOTHER_S_FATHER_S_FATHER = 'mother_s_father_s_father';
    const MOTHER_S_FATHER_S_MOTHER = 'mother_s_father_s_mother';

    const MOTHER_S_MOTHER = 'mother_s_mother';
    const MOTHER_S_MOTHER_S_FATHER = 'mother_s_mother_s_father';
    const MOTHER_S_MOTHER_S_MOTHER = 'mother_s_mother_s_mother';

    //TODO Animal Measurements. Wait until the animal import is complete to see the real fields that are used.
}