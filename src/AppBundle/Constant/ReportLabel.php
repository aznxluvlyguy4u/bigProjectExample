<?php

namespace AppBundle\Constant;


class ReportLabel
{
    //General
    const ENTITY = 'entity';

    //Person
    const OWNER_NAME = "ownerName";
    const OWNER = 'owner';

    //Breeder
    const BREEDER = 'breeder';
    const BREEDER_NAME = "breederName";
    const BREEDER_NAME_CROPPED = "breederNameCropped";
    const BREEDER_NUMBER = 'breederNumber';

    //Address
    const ADDRESS = 'address';
    const ADDRESS_BREEDER = 'addressBreeder';
    const UBN = 'ubn';

    //Animal
    const ANIMALS = 'animals';
    const GENDER = 'gender';
    const ULN_COUNTRY_CODE = 'ulnCountryCode';
    const ULN_NUMBER = 'ulnNumber';
    const ULN = 'uln';
    const PEDIGREE_COUNTRY_CODE = 'pedigreeCountryCode';
    const PEDIGREE_NUMBER = 'pedigreeNumber';
    const PEDIGREE = 'pedigree';
    const NAME = 'name';
    const DATE_OF_BIRTH = 'dateOfBirth';
    const LITTER_SIZE = 'litterSize'; //litter animal was born in
    const LITTER_GROUP = 'litterGroup'; //litter animal was born in
    const LITTER_COUNT = 'litterCount'; //number of offspring litters
    const OFFSPRING_COUNT = 'offspringCount'; //number of offspring
    const N_LING = 'nLing';
    const SCRAPIE = 'scrapie';
    const PRODUCTION = 'production';
    const BREED_TYPE = 'breedType';
    const BREED_CODE = 'breedCode';
    const INSPECTION_DATE = 'inspectionDate';
    const PREDICATE = 'predicate';
    const BLINDNESS_FACTOR = 'blindnessFactor';

    //Measurements
    const EXTERIOR = 'exterior';
    const MEASUREMENT_DATE = 'measurement_date';
    const SKULL = 'skull';
    const DEVELOPMENT = 'development';
    const MUSCULARITY = 'muscularity';
    const PROPORTION = 'proportion';
    const TYPE = 'type';
    const LEGWORK = 'legWork';
    const FUR = 'fur';
    const GENERAL_APPEARANCE = 'generalAppearance';
    const HEIGHT = 'height';
    const TORSO_LENGTH = 'torsoLength';
    const BREAST_DEPTH = 'breastDepth';
    const GROWTH = 'growth';
    const VL = 'vl';
    const SL = 'sl';
    const MARKINGS = 'markings';


    const MUSCLE_THICKNESS = 'muscleThickness';
    const BODY_FAT = 'bodyFat';
    const TAIL_LENGTH = 'tailLength';

    //Pedrigree / Bloodline
    const CHILD_KEY = 'c';
    const FATHER_KEY = 'f';
    const MOTHER_KEY = 'm';
    
}