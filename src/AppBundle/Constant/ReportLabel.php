<?php

namespace AppBundle\Constant;


class ReportLabel
{
    //General
    const COLUMN_HEADERS = 'columnHeaders';
    const ENTITY = 'entity';
    const IS_PROD_ENV = 'isProdEnv';
    const IS_EMPTY = 'isEmpty';
    const IMAGES_DIRECTORY = 'imagesDirectory';
    const DATE = 'date';
    const FOOTNOTE = 'footnote';
    const REFERENCE_DATE = 'referenceDate';
    const INVALID = 'invalid';
    const MONTHS = 'months';
    const TOTALS = 'totals';
    const VALUE = 'value';
    const VALUES = 'values';
    const HAS_ANY_VALUES = 'hasAnyValues';
    const ACCURACY = 'accuracy';

    //Person
    const ACTION_BY_FULL_NAME = 'action_by_full_name';
    const ACTION_BY_IS_SUPER_ADMIN = 'action_by_is_super_admin';
    const OWNER_NAME = "ownerName";
    const OWNER = 'owner';
    const OWNER_EMAIL_ADDRESS = 'ownerEmailAddress';

    //Breeder
    const BREEDER = 'breeder';
    const BREEDER_NAME = "breederName";
    const BREEDER_NAME_CROPPED = "breederNameCropped";
    const BREEDER_NUMBER = 'breederNumber';
    const BREEDER_EMAIL_ADDRESS = 'breederEmailAddress';

    //Breed Index
    const INDEXES = 'indexes';
    const STARS_VALUE = 'stars_value';
    const STARS_OUTPUT = 'stars_output';
    const INDEX_FATHER = 'indexFather';
    const INDEX_MOTHER = 'indexMother';
    const INDEX_EXTERIOR = 'indexExterior';
    const INDEX_BREED = 'indexBreed';
    
    //Breed values
    const INBREEDING_COEFFICIENT = 'inbreedingCoefficient';
    const IS_RAM_MISSING = 'isRamMissing';

    //Address
    const ADDRESS = 'address';
    const POSTAL_CODE = 'postalCode';
    const POSTAL_CODE_BREEDER = 'postalCodeBreeder';
    const ADDRESS_BREEDER = 'addressBreeder';
    const UBN = 'ubn';
    const COUNTRY = 'country';

    //Animal
    const ANIMALS = 'animals';
    const ANIMAL_COUNT = 'animalCount';
    const ANIMAL_CATEGORY = 'animalCategory';
    const ANIMAL_TYPE = 'animalType';
    const ANIMAL_TYPE_IN_LATIN = 'animalTypeInLatin';
    const RAM = 'ram';
    const EWE = 'ewe';
    const EWES = 'ewes';
    const GENDER = 'gender';
    const ULN_COUNTRY_CODE = 'ulnCountryCode';
    const ULN_NUMBER = 'ulnNumber';
    const ULN = 'uln';
    const STN = 'stn';
    const PEDIGREE_COUNTRY_CODE = 'pedigreeCountryCode';
    const PEDIGREE_NUMBER = 'pedigreeNumber';
    const PEDIGREE = 'pedigree';
    const LAST_MATE = 'lastMate';
    const NAME = 'name';
    const COUNTRY_OF_BIRTH = 'countryOfBirth';
    const DATE_OF_BIRTH = 'dateOfBirth';
    const LITTER_SIZE = 'litterSize'; //litter animal was born in
    const LITTER_GROUP = 'litterGroup'; //litter animal was born in
    const LITTER_COUNT = 'litterCount'; //number of offspring litters
    const OFFSPRING_COUNT = 'offspringCount'; //number of offspring
    const N_LING = 'nLing';
    const NICKNAME = 'nickname';
    const SCRAPIE = 'scrapie';
    const SECTION_TYPE = 'section_type';
    const PRODUCTION = 'production';
    const BREED = 'breed';
    const BREED_TYPE = 'breedType';
    const BREED_CODE = 'breedCode';
    const BREED_CODES = 'breedCodes';
    const BREED_CODE_LETTERS = 'breedCodeLetters';
    const BREED_CODE_FULLNAME = 'breedCodeFullname';
    const BREED_VALUES = 'breedValues';
    const BREED_VALUES_EVALUATION_DATE = 'breedValuesEvaluationDate';
    const INSPECTION_DATE = 'inspectionDate';
    const PREDICATE = 'predicate';
    const BLINDNESS_FACTOR = 'blindnessFactor';
    const PEDIGREE_REGISTER = 'pedigreeRegister';
    const LIVESTOCK = 'livestock';
    const IS_USER_ALLOWED_TO_ACCESS_ANIMAL_DETAILS = 'is_user_allowed_to_access_animal_details';

    //Location
    const LOCATIONS = 'locations';
    const CASEOUS_LYMPHADENITIS_STATUS = 'caseousLymphadenitisStatus';
    const MAEDI_VISNA_STATUS = 'maediVisnaStatus';
    const SCRAPIE_STATUS = 'scrapieStatus';
    const CAE_STATUS = 'caeStatus';
    const IS_PENDING = 'is_pending';

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


    //Pedrigree / Bloodline
    const CHILD_KEY = 'c';
    const FATHER_KEY = 'f';
    const MOTHER_KEY = 'm';
    
    const MOTHER_ID = 'mother_id';
    const FATHER_ID = 'father_id';
    const MOTHER = 'mother';
    const FATHER = 'father';

    const HETEROSIS = 'heterosis';
    const RECOMBINATION = 'recombination';
    
    //Gender
    const FEMALE_SYMBOL = 'female_symbol';
    const MALE_SYMBOL = 'male_symbol';
    const MALE_AND_FEMALE_SYMBOL = 'male_and_female_symbol';
    const NEUTER_SYMBOL = 'neuter_symbol';

    //Position
    const START = 'START';
    const END = 'END';

    //Fertilizer
    const AVERAGE_YEARLY_ANIMAL_COUNT = 'average_yearly_animal_count';
    const NITROGEN = 'nitrogen';
    const PHOSPHATE = 'phosphate';

    const DISPLAY_ZOO_TECHNICAL_DATA = 'displayZooTechnicalData';
}