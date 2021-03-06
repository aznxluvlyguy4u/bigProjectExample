<?php

namespace AppBundle\Constant;


class JsonInputConstant
{
    const ID = 'id';
    const DASHBOARD_TYPE = 'dashboard_type';
    const PLAIN_TEXT_INPUT = 'plain_text_input';
    const UPDATED = 'updated';
    const NOT_UPDATED = 'not_updated';
    const NORMALIZED = 'normalized';
    const SUCCESSFUL_UPDATE_SECONDARY_VALUES = 'successful_update_secondary_values';
    const SUCCESSFUL_UPDATE_RESULT_TABLE_VALUES = 'successful_update_result_table_values';
    const FULL_OUTPUT = 'full_output';
    const SELECTED = 'selected';
    const ARRAY = 'array';
    const JSON = 'json';
    const IS_VALID = 'is_valid';
    const KEY = 'key';
    const TRANSLATION_KEY = 'translation_key';
    const FORMATTED = 'formatted';
    const LABEL = 'label';
    const DESCRIPTION = 'description';

    //Admins
    const ADMINS = 'admins';
    const ACCESS_LEVEL = 'access_level';
    const IS_ADMIN_ENV = 'is_admin_env';

    //Animal
    const ANIMAL_ORDER_NUMBER = 'animal_order_number';
    const WORK_NUMBER = 'work_number';
    const IS_ALIVE = 'is_alive';
    const ANIMAL = 'animal';
    const ANIMALS = 'animals';
    const ANIMAL_ID = 'animal_id';
    const ANIMAL_ID_AND_DATE = 'animal_id_and_date';
    const BIRTH_PROGRESS = 'birth_progress';
    const BIRTH = 'birth';
    const RAM = 'ram';
    const EWE = 'ewe';
    const NEUTER = 'neuter';
    const EWES = 'ewes';
    const RAMS = 'rams';
    const CHILDREN = 'children';
    const COUNTRY_OF_BIRTH = 'country_of_birth';

    const COLLAR = 'collar';
    const COLOR = 'color';
    const NUMBER = 'number';
    const COLLAR_COLOR = self::COLLAR.'_'.self::COLOR;
    const COLLAR_NUMBER = self::COLLAR.'_'.self::NUMBER;

    const LITTERS = 'litters';
    const COUNTRY = 'country';
    const COUNTRY_CODE = 'country_code';
    const ULN_COUNTRY_CODE = 'uln_country_code';
    const ULN_NUMBER = 'uln_number';
    const PEDIGREE_COUNTRY_CODE = 'pedigree_country_code';
    const PEDIGREE_NUMBER = 'pedigree_number';
    const DATE_OF_BIRTH = 'date_of_birth';
    const UBN_OF_BIRTH = 'ubn_of_birth';
    const GENDER = 'gender';
    const INBREEDING_COEFFICIENT = 'inbreeding_coefficient';
    const IS_REVEAL_HISTORIC_ANIMALS = 'is_reveal_historic_animals';
    const IS_HISTORIC_ANIMAL = 'is_historic_animal';
    const IS_PUBLIC = 'is_public';
    const IS_OWN_HISTORIC_ANIMAL = 'is_own_historic_animal';
    const IS_OWN_ANIMAL = 'is_own_animal';
    const ULN = 'uln';
    const ULN_FATHER = 'uln_mother';
    const ULN_MOTHER = 'uln_father';
    const MOTHER_ID = 'mother_id';
    const MOTHER = 'mother';
    const FATHER_ID = 'father_id';
    const FATHER = 'father';
    const PARENTS = 'parents';
    const STN = 'stn';
    const SCRAPIE_GENOTYPE = 'scrapie_genotype';
    const BREED = 'breed';
    const BREED_CODE = 'breed_code';
    const BREED_CODES = 'breed_codes';
    const BREED_CODE_LETTERS = 'breed_code_letters';
    const BREED_CODE_FULLNAME = 'breed_code_fullname';
    const BREED_CODE_MOTHER = 'breed_code_mother';
    const BREED_TYPE = 'breed_type';
    const BLINDNESS_FACTOR = 'blindness_factor';
    const PREDICATE = 'predicate';
    const PREDICATE_DETAILS = 'predicate_details';
    const PREDICATE_SCORE = 'predicate_score';
    const SCORE = 'score';
    const ANIMAL_TYPE = 'animal_type';
    const NICKNAME = 'nickname';
    const ANIMAL_RESIDENCE_HISTORY = 'animal_residence_history';
    const HETEROSIS = 'heterosis';
    const RECOMBINATION = 'recombination';
    const HETEROSIS_LAMB = 'heterosis_lamb';
    const RECOMBINATION_LAMB = 'recombination_lamb';
    const AGE = 'age';
    const PARENT_IDS = 'parent_ids';
    const PARENT_TYPES = 'parent_types';
    const EXCLUDE_MOTHER = 'exclude_mother';
    const EXCLUDE_FATHER = 'exclude_father';
    const SURROGATE_MOTHER = 'surrogate_mother';
    const GENERATIONS = 'generations';
    const SEPARATOR = 'separator';
    const ULNS_WITHOUT_FOUND_ANIMALS = 'ulns_without_found_animals';
    const STNS_WITHOUT_FOUND_ANIMALS = 'stns_without_found_animals';
    const FOUND_ULNS = 'found_ulns';
    const FOUND_STNS = 'found_stns';
    const BREED_CODE_UPDATED = 'breed_code_updated';
    const REARING = 'rearing';
    const LAMBAR = 'lambar';
    const SURROGATE = 'surrogate';

    //Request & Response
    const ERROR_CODE = 'error_code';
    const ERROR_MESSAGE = 'error_message';
    const ERROR_KIND_INDICATOR = 'error_kind_indicator';
    const SUCCESS_INDICATOR = 'success_indicator';
    const ACTION = 'action';
    const RECOVERY_INDICATOR = 'recovery_indicator';
    const ACTION_BY = 'action_by';
    const IS_REMOVED_BY_USER = 'is_removed_by_user';
    const IS_DUTCH = 'is_dutch';
    const IS_MULTI_EDIT = 'is_multi_edit';
    const EDITS = 'edits';
    const SUCCESSFUL_EDITS = 'successful_edits';
    const SUCCESS_COUNT = 'success_count';
    const ERROR_COUNT = 'error_count';

    // Breeder & Holder
    const BREEDER = 'breeder';
    const HOLDER = 'holder';

    //Sync
    const IS_RVO_LEADING = 'is_rvo_leading';

    //Arrival & Import
    const IS_IMPORT_ANIMAL = 'is_import_animal';
    const UBN_PREVIOUS_OWNER = 'ubn_previous_owner';
    const ARRIVAL_DATE = 'arrival_date';
    const IS_ARRIVED_FROM_OTHER_NSFO_CLIENT = 'is_arrived_from_other_nsfo_client';
    const COUNTRY_ORIGIN = 'country_origin';

    //Depart & Export
    const IS_EXPORT_ANIMAL = 'is_export_animal';
    const UBN_NEW_OWNER = 'ubn_new_owner';
    const DEPART_DATE = 'depart_date';
    const REASON_OF_DEPARTURE = 'reason_of_departure';
    const REASON_OF_DEPART = 'reason_of_depart';

    //Tags
    const RELATION_NUMBER_ACCEPTANT = "relation_number_acceptant";
    const TAGS = "tags";
    const TAG = "tag";

    //Mate
    const PMSG = 'pmsg';
    const KI = 'ki';
    const START_DATE = 'start_date';
    const END_DATE = 'end_date';

    //Authentication
    const NEW_PASSWORD = 'new_password';

    //Clients
    const FIRST_NAME = 'first_name';
    const LAST_NAME = "last_name";
    const EMAIL_ADDRESS = "email_address";
    const IS_PRIMARY_CONTACT_PERSON = "is_primary_contact_person";
    const PREFIX = "prefix";

    const PASSWORD = "password";
    const USERNAME = "username";
    const CELLPHONE_NUMBER = "cellphone_number";
    const RELATION_NUMBER_KEEPER = "relation_number_keeper";

    //Mobile
    const REGISTRATION_TOKEN = 'registration_token';
    const DEVICE_ID = 'uuid';

    //Content Management System (cms)
    const DASHBOARD = 'dashboard';
    const CONTACT_INFO = 'contact_info';

    //Company
    const COMPANY_ID = 'company_id';
    const COMPANY_NAME = 'company_name';
    const CITY = 'city';
    const VAT_NUMBER = 'vat_number';
    const CHAMBER_OF_COMMERCE_NUMBER = 'chamber_of_commerce_number';
    const COMPANY_RELATION_NUMBER = 'company_relation_number';

    //Health
    const MAEDI_VISNA_STATUS = 'maedi_visna_status';
    const MAEDI_VISNA_START_DATE = 'maedi_visna_start_date';
    const MAEDI_VISNA_CHECK_DATE = 'maedi_visna_check_date';
    const MAEDI_VISNA_END_DATE = 'maedi_visna_end_date';
    const SCRAPIE_STATUS = 'scrapie_status';
    const SCRAPIE_START_DATE = 'scrapie_start_date';
    const SCRAPIE_CHECK_DATE = 'scrapie_check_date';
    const SCRAPIE_END_DATE = 'scrapie_end_date';
    const MAEDI_VISNA_REASON_OF_EDIT = 'maedi_visna_reason_of_edit';
    const SCRAPIE_REASON_OF_EDIT = 'scrapie_reason_of_edit';
    const ANIMAL_HEALTH_SUBSCRIPTION = 'animal_health_subscription';

    //Locations
    const LOCATION = 'location';
    const LOCATION_OF_BIRTH = 'location_of_birth';
    const LOCATIONS = 'locations';
    const PROVINCES = 'provinces';
    const CODES = 'codes';

    //Loss
    const UBN_PROCESSOR = "ubn_processor";
    const DATE_OF_DEATH = "date_of_death";
    const REASON_OF_LOSS = "reason_of_loss";

    //Location / UBN
    const UBN = 'ubn';
    const UBNS = 'ubns';

    //Messages
    const MESSAGE_ID = 'message_id';
    const REQUEST_ID = 'request_id';
    const REQUEST_STATE = 'request_state';
    const MESSAGE_NUMBER = 'message_number';
    const LOG_DATE = 'log_date';
    const RESULT = 'result';
    const IS_HIDDEN = 'is_hidden';
    const IS_IR_MESSAGE = 'is_ir_message';
    const HIDE_FOR_ADMIN = 'hide_for_admin';
    const IS_OVERWRITTEN = 'is_overwritten';
    const REVOKED_BY = 'revoked_by';
    const REVOKE_DATE = 'revoke_date';
    const DATA = 'data';

    //Persons
    const PERSON_ID = 'person_id';
    const INSPECTOR_CODE = 'inspector_code';
    const LINEAR_INSPECTOR_CODE = 'linear_inspector_code';

    //Measurements
    const MEASUREMENT_ID = 'measurement_id';
    const DATE_OF_MEASUREMENT = 'date_of_measurement';
    const MEASUREMENT_DATE = 'measurement_date';
    const IS_EMPTY_MEASUREMENT = 'is_empty_measurement';
    const MEASUREMENT_ROW = 'measurement_row';
    const INSPECTOR = 'inspector';
    const INSPECTOR_ID = 'inspector_id';
    const INSPECTOR_FIRST_NAME = 'inspector_first_name';
    const INSPECTOR_LAST_NAME = 'inspector_last_name';
    const YEAR_AND_UBN_OF_BIRTH = 'year_and_ubn_of_birth';
    const YEAR_AND_UBN_OF_TREATMENT = 'year_and_ubn_of_treatment';
    const SCAN_MEASUREMENTS = 'scan_measurements';

    //Weight measurements
    const LAST_WEIGHT = 'last_weight';
    const LAST_WEIGHT_MEASUREMENT_DATE = 'last_weight_measurement_date';
    const WEIGHT_MEASUREMENTS = 'weight_measurements';
    const WEIGHT = 'weight';
    const IS_BIRTH_WEIGHT = 'is_birth_weight';
    const BIRTH_WEIGHT = 'birth_weight';
    const IS_VALID_20WEEK_WEIGHT_MEASUREMENT = 'is_valid_20_week_weight_measurement';
    const IS_REVOKED = 'is_revoked';
    const RESET_MEASUREMENT_DATE_USING_DATE_OF_BIRTH = 'reset_measurement_date_using_date_of_birth';

    //BodyFat measurements
    const FAT1 = 'fat1';
    const FAT2 = 'fat2';
    const FAT3 = 'fat3';

    //ExteriorMeasurements
    const HEIGHT = 'height';
    const KIND = 'kind';
    const PROGRESS = 'progress';
    const SKULL = 'skull';
    const MUSCULARITY = 'muscularity';
    const PROPORTION = 'proportion';
    const EXTERIOR_TYPE = 'exterior_type';
    const LEG_WORK = 'leg_work';
    const FUR = 'fur';
    const GENERAL_APPEARANCE = 'general_appearance';
    const BREAST_DEPTH = 'breast_depth';
    const TORSO_LENGTH = 'torso_length';
    const MARKINGS = 'markings';
    const EXTERIOR_MEASUREMENT_DATE = 'exterior_measurement_date';
    const TYPE = 'type';

    //MuscleThickness
    const MUSCLE_THICKNESS = 'muscle_thickness';

    //TailLength
    const LENGTH = 'length';
    const TAIL_LENGTH = 'tail_length';

    //Litter
    const BIRTH_INTERVAL = 'birth_interval';
    const GESTATION_PERIOD = 'gestation_period';
    const SIZE = 'size';
    const N_LING = 'n_ling';
    const N_LING_VALUE = 'n_ling_max_value';
    const SUCKLE_COUNT = 'suckle_count';
    const LITTER_GROUP = 'litter_group';
    const LITTER_COUNT = 'litter_count';
    const LITTER_SIZE = 'litter_size';
    const TOTAL_BORN_ALIVE_COUNT = 'total_born_alive_count';
    const TOTAL_STILLBORN_COUNT = 'total_stillborn_count';
    const BORN_ALIVE_COUNT = 'born_alive_count';
    const STILLBORN_COUNT = 'stillborn_count';
    const EARLIEST_LITTER_DATE = 'earliest_litter_date';
    const LATEST_LITTER_DATE = 'latest_litter_date';
    const LITTER_DATE = 'litter_date';
    const LITTER_ORDINAL = 'litter_ordinal';
    const LITTER_ID = 'litter_id';
    const PRODUCTION = 'production';
    const GAVE_BIRTH_AS_ONE_YEAR_OLD = 'gave_birth_as_one_year_old';

    //BreedValues
    const BREED_VALUE_LITTER_SIZE = "breed_value_litter_size";
    const BREED_VALUE_GROWTH = "breed_value_growth";
    const BREED_VALUE_MUSCLE_THICKNESS = "breed_value_muscle_thickness";
    const BREED_VALUE_FAT = "breed_value_fat";
    const BREED_VALUE_MAX_GENERATION_DATE = "breed_value_max_generation_date";
    const LAMB_MEAT_INDEX = "lamb_meat_index";
    const LAMB_MEAT_INDEX_WITHOUT_ACCURACY = "lamb_meat_index_without_accuracy";

    //PedigreeRegisters
    const INCLUDE_NON_NSFO_REGISTERS = "include_non_nsfo_registers";

    //MiXBLUP
    const PERM_MIL = 'perm_mil';
    const RELANI_1 = 'relani_1';
    const RELANI_2 = 'relani_2';
    const RELANI_3 = 'relani_3';
    const SOLANI_1 = 'solani_1';
    const SOLANI_2 = 'solani_2';
    const SOLANI_3 = 'solani_3';

    //DataImport
    const ENTITY_ALREADY_EXISTS = 'entity_already_exists';

    //WormResistance
    const TREATED_FOR_SAMPLES = 'treated_for_samples';
    const EPG = 'epg';
    const S_IGA_GLASGOW = 's_iga_glasgow';
    const CARLA_IGA_NZ = 'carla_iga_nz';
    const CLASS_CARLA_IGA_NZ = 'class_carla_iga_nz';
    const SAMPLE_PERIOD = 'sample_period';
    const YEAR = 'year';

    // User
    const USE_RVO_LOGIC = 'use_rvo_logic';

    // Email
    const NSFO_DERDEN_LOGIN_URL = 'nsfo_derden_login_url';
}
