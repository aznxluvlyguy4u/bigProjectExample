<?php


namespace AppBundle\Service\Container;


use AppBundle\Entity\Address;
use AppBundle\Entity\AddressRepository;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\BodyFat;
use AppBundle\Entity\BodyFatRepository;
use AppBundle\Entity\CaseousLymphadenitis;
use AppBundle\Entity\CaseousLymphadenitisRepository;
use AppBundle\Entity\Client;
use AppBundle\Entity\ClientRepository;
use AppBundle\Entity\Collar;
use AppBundle\Entity\CollarRepository;
use AppBundle\Entity\Company;
use AppBundle\Entity\CompanyNote;
use AppBundle\Entity\CompanyNoteRepository;
use AppBundle\Entity\CompanyRepository;
use AppBundle\Entity\ContactFormMenu;
use AppBundle\Entity\ContactFormMenuRepository;
use AppBundle\Entity\Content;
use AppBundle\Entity\ContentRepository;
use AppBundle\Entity\Country;
use AppBundle\Entity\CountryRepository;
use AppBundle\Entity\DeclarationDetail;
use AppBundle\Entity\DeclarationDetailRepository;
use AppBundle\Entity\DeclareAnimalFlag;
use AppBundle\Entity\DeclareAnimalFlagRepository;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareArrivalRepository;
use AppBundle\Entity\DeclareArrivalResponse;
use AppBundle\Entity\DeclareArrivalResponseRepository;
use AppBundle\Entity\DeclareBase;
use AppBundle\Entity\DeclareBaseRepository;
use AppBundle\Entity\DeclareBaseResponse;
use AppBundle\Entity\DeclareBaseResponseRepository;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareBirthRepository;
use AppBundle\Entity\DeclareBirthResponse;
use AppBundle\Entity\DeclareBirthResponseRepository;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareDepartRepository;
use AppBundle\Entity\DeclareDepartResponse;
use AppBundle\Entity\DeclareDepartResponseRepository;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareExportRepository;
use AppBundle\Entity\DeclareExportResponse;
use AppBundle\Entity\DeclareExportResponseRepository;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\DeclareImportRepository;
use AppBundle\Entity\DeclareImportResponse;
use AppBundle\Entity\DeclareImportResponseRepository;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\DeclareLossRepository;
use AppBundle\Entity\DeclareLossResponse;
use AppBundle\Entity\DeclareLossResponseRepository;
use AppBundle\Entity\DeclareNsfoBase;
use AppBundle\Entity\DeclareNsfoBaseRepository;
use AppBundle\Entity\DeclareTagReplace;
use AppBundle\Entity\DeclareTagReplaceRepository;
use AppBundle\Entity\DeclareTagReplaceResponse;
use AppBundle\Entity\DeclareTagReplaceResponseRepository;
use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Entity\DeclareTagsTransferRepository;
use AppBundle\Entity\DeclareTagsTransferResponse;
use AppBundle\Entity\DeclareTagsTransferResponseRepository;
use AppBundle\Entity\DeclareWeight;
use AppBundle\Entity\DeclareWeightRepository;
use AppBundle\Entity\EditType;
use AppBundle\Entity\EditTypeRepository;
use AppBundle\Entity\Employee;
use AppBundle\Entity\EmployeeRepository;
use AppBundle\Entity\ErrorLogAnimalPedigree;
use AppBundle\Entity\ErrorLogAnimalPedigreeRepository;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\EweRepository;
use AppBundle\Entity\Exterior;
use AppBundle\Entity\ExteriorRepository;
use AppBundle\Entity\Fat1;
use AppBundle\Entity\Fat1Repository;
use AppBundle\Entity\Fat2;
use AppBundle\Entity\Fat2Repository;
use AppBundle\Entity\Fat3;
use AppBundle\Entity\Fat3Repository;
use AppBundle\Entity\FootRot;
use AppBundle\Entity\FootRotRepository;
use AppBundle\Entity\FTPFailedImport;
use AppBundle\Entity\FTPFailedImportRepository;
use AppBundle\Entity\GenderHistoryItem;
use AppBundle\Entity\GenderHistoryItemRepository;
use AppBundle\Entity\Inspector;
use AppBundle\Entity\InspectorAuthorization;
use AppBundle\Entity\InspectorAuthorizationRepository;
use AppBundle\Entity\InspectorRepository;
use AppBundle\Entity\Invoice;
use AppBundle\Entity\InvoiceRepository;
use AppBundle\Entity\InvoiceRule;
use AppBundle\Entity\InvoiceRuleRepository;
use AppBundle\Entity\Litter;
use AppBundle\Entity\LitterRepository;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationAddress;
use AppBundle\Entity\LocationAddressRepository;
use AppBundle\Entity\LocationHealth;
use AppBundle\Entity\LocationHealthInspection;
use AppBundle\Entity\LocationHealthInspectionDirection;
use AppBundle\Entity\LocationHealthInspectionDirectionRepository;
use AppBundle\Entity\LocationHealthInspectionRepository;
use AppBundle\Entity\LocationHealthInspectionResult;
use AppBundle\Entity\LocationHealthInspectionResultRepository;
use AppBundle\Entity\LocationHealthLetter;
use AppBundle\Entity\LocationHealthLetterRepository;
use AppBundle\Entity\LocationHealthMessage;
use AppBundle\Entity\LocationHealthMessageRepository;
use AppBundle\Entity\LocationHealthQueue;
use AppBundle\Entity\LocationHealthQueueRepository;
use AppBundle\Entity\LocationHealthRepository;
use AppBundle\Entity\LocationRepository;
use AppBundle\Entity\MaediVisna;
use AppBundle\Entity\MaediVisnaRepository;
use AppBundle\Entity\Mate;
use AppBundle\Entity\MateRepository;
use AppBundle\Entity\Measurement;
use AppBundle\Entity\MeasurementRepository;
use AppBundle\Entity\Message;
use AppBundle\Entity\MessageRepository;
use AppBundle\Entity\MuscleThickness;
use AppBundle\Entity\MuscleThicknessRepository;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\NeuterRepository;
use AppBundle\Entity\NormalDistribution;
use AppBundle\Entity\NormalDistributionRepository;
use AppBundle\Entity\Pedigree;
use AppBundle\Entity\PedigreeCode;
use AppBundle\Entity\PedigreeCodeRepository;
use AppBundle\Entity\PedigreeRegister;
use AppBundle\Entity\PedigreeRegisterRepository;
use AppBundle\Entity\PedigreeRepository;
use AppBundle\Entity\PerformanceMeasurement;
use AppBundle\Entity\PerformanceMeasurementRepository;
use AppBundle\Entity\Person;
use AppBundle\Entity\PersonRepository;
use AppBundle\Entity\Predicate;
use AppBundle\Entity\PredicateRepository;
use AppBundle\Entity\Processor;
use AppBundle\Entity\ProcessorRepository;
use AppBundle\Entity\Province;
use AppBundle\Entity\ProvinceRepository;
use AppBundle\Entity\Race;
use AppBundle\Entity\RaceRepository;
use AppBundle\Entity\Ram;
use AppBundle\Entity\RamRepository;
use AppBundle\Entity\ResultTableBreedGrades;
use AppBundle\Entity\ResultTableBreedGradesRepository;
use AppBundle\Entity\RetrieveAnimalDetails;
use AppBundle\Entity\RetrieveAnimalDetailsRepository;
use AppBundle\Entity\RetrieveAnimalDetailsResponse;
use AppBundle\Entity\RetrieveAnimalDetailsResponseRepository;
use AppBundle\Entity\RetrieveAnimals;
use AppBundle\Entity\RetrieveAnimalsRepository;
use AppBundle\Entity\RetrieveAnimalsResponse;
use AppBundle\Entity\RetrieveAnimalsResponseRepository;
use AppBundle\Entity\RetrieveCountries;
use AppBundle\Entity\RetrieveCountriesRepository;
use AppBundle\Entity\RetrieveCountriesResponse;
use AppBundle\Entity\RetrieveCountriesResponseRepository;
use AppBundle\Entity\RetrieveTags;
use AppBundle\Entity\RetrieveTagsRepository;
use AppBundle\Entity\RetrieveTagsResponse;
use AppBundle\Entity\RetrieveTagsResponseRepository;
use AppBundle\Entity\RetrieveUbnDetails;
use AppBundle\Entity\RetrieveUbnDetailsRepository;
use AppBundle\Entity\RetrieveUbnDetailsResponse;
use AppBundle\Entity\RetrieveUbnDetailsResponseRepository;
use AppBundle\Entity\RevokeDeclaration;
use AppBundle\Entity\RevokeDeclarationResponse;
use AppBundle\Entity\RevokeDeclarationResponseRepository;
use AppBundle\Entity\Scrapie;
use AppBundle\Entity\ScrapieRepository;
use AppBundle\Entity\Stillborn;
use AppBundle\Entity\StillbornRepository;
use AppBundle\Entity\Tag;
use AppBundle\Entity\TagRepository;
use AppBundle\Entity\TagSyncErrorLog;
use AppBundle\Entity\TagSyncErrorLogRepository;
use AppBundle\Entity\TagTransferItemRequest;
use AppBundle\Entity\TagTransferItemRequestRepository;
use AppBundle\Entity\TagTransferItemResponse;
use AppBundle\Entity\TagTransferItemResponseRepository;
use AppBundle\Entity\TailLength;
use AppBundle\Entity\TailLengthRepository;
use AppBundle\Entity\Token;
use AppBundle\Entity\TokenRepository;
use AppBundle\Entity\VsmIdGroup;
use AppBundle\Entity\VsmIdGroupRepository;
use AppBundle\Entity\Weight;
use AppBundle\Entity\WeightRepository;
use AppBundle\Entity\WormResistance;
use AppBundle\Entity\WormResistanceRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;

class RepositoryContainerBase
{
    /** @var EntityManagerInterface|ObjectManager */
    private $manager;

    /* Repository */

    /** @var AddressRepository */
    protected $addressRepository;
    /** @var AnimalRepository */
    protected $animalRepository;
    /** @var BodyFatRepository */
    protected $bodyFatRepository;
    /** @var CaseousLymphadenitisRepository */
    protected $caseousLymphadenitis;
    /** @var ClientRepository */
    protected $clientRepository;
    /** @var CollarRepository */
    protected $collarRepository;
    /** @var CompanyRepository */
    protected $companyRepository;
    /** @var CompanyNoteRepository */
    protected $companyNoteRepository;
    /** @var ContactFormMenuRepository */
    protected $contactFormMenuRepository;
    /** @var ContentRepository */
    protected $contentRepository;
    /** @var CountryRepository */
    protected $countryRepository;
    /** @var DeclarationDetailRepository */
    protected $declarationDetailRepository;
    /** @var DeclareBaseRepository */
    protected $declareBaseRepository;
    /** @var DeclareBaseResponseRepository */
    protected $declareBaseResponseRepository;
    /** @var DeclareNsfoBaseRepository */
    protected $declareNsfoBaseRepository;
    /** @var DeclareArrivalRepository */
    protected $declareArrivalRepository;
    /** @var DeclareArrivalResponseRepository */
    protected $declareArrivalResponseRepository;
    /** @var DeclareAnimalFlagRepository */
    protected $declareAnimalFlagRepository;
    /** @var DeclareBirthRepository */
    protected $declareBirthRepository;
    /** @var DeclareBirthResponseRepository */
    protected $declareBirthResponseRepository;
    /** @var DeclareImportRepository */
    protected $declareImportRepository;
    /** @var DeclareImportResponseRepository */
    protected $declareImportResponseRepository;
    /** @var DeclareDepartRepository */
    protected $declareDepartRepository;
    /** @var DeclareDepartResponseRepository */
    protected $declareDepartResponseRepository;
    /** @var DeclareExportRepository */
    protected $declareExportRepository;
    /** @var DeclareExportResponseRepository */
    protected $declareExportResponseRepository;
    /** @var DeclareLossRepository */
    protected $declareLossRepository;
    /** @var DeclareLossResponseRepository */
    protected $declareLossResponseRepository;
    /** @var DeclareTagsTransferRepository */
    protected $declareTagsTransferRepository;
    /** @var DeclareTagsTransferResponseRepository */
    protected $declareTagsTransferResponseRepository;
    /** @var DeclareTagReplaceRepository */
    protected $declareTagReplaceRepository;
    /** @var DeclareTagReplaceResponseRepository */
    protected $declareTagReplaceResponseRepository;
    /** @var DeclareWeightRepository */
    protected $declareWeightRepository;
    /** @var EditTypeRepository */
    protected $editTypeRepository;
    /** @var EmployeeRepository */
    protected $employeeRepository;
    /** @var ErrorLogAnimalPedigreeRepository */
    protected $errorLogAnimalPedigreeRepository;
    /** @var EweRepository */
    protected $eweRepository;
    /** @var ExteriorRepository */
    protected $exteriorRepository;
    /** @var Fat1Repository */
    protected $fat1Repository;
    /** @var Fat2Repository */
    protected $fat2Repository;
    /** @var Fat3Repository */
    protected $fat3Repository;
    /** @var FootRotRepository */
    protected $footRotRepository;
    /** @var FTPFailedImportRepository */
    protected $ftpFailedImportRepository;
    /** @var GenderHistoryItemRepository */
    protected $genderHistoryItemRepository;
    /** @var InspectorRepository */
    protected $inspectorRepository;
    /** @var InspectorAuthorizationRepository */
    protected $inspectorAuthorizationRepository;
    /** @var InvoiceRepository */
    protected $invoiceRepository;
    /** @var InvoiceRuleRepository */
    protected $invoiceRuleRepository;
    /** @var LitterRepository */
    protected $litterRepository;
    /** @var LocationRepository */
    protected $locationRepository;
    /** @var LocationAddressRepository */
    protected $locationAddressRepository;
    /** @var LocationHealthRepository */
    protected $locationHealthRepository;
    /** @var LocationHealthInspectionRepository */
    protected $locationHealthInspectionRepository;
    /** @var LocationHealthInspectionDirectionRepository */
    protected $locationHealthInspectionDirectionRepository;
    /** @var LocationHealthInspectionResultRepository */
    protected $locationHealthInspectionResultRepository;
    /** @var LocationHealthLetterRepository */
    protected $locationHealthLetterRepository;
    /** @var LocationHealthMessageRepository */
    protected $locationHealthMessageRepository;
    /** @var LocationHealthQueueRepository */
    protected $locationHealthQueueRepository;
    /** @var MaediVisnaRepository */
    protected $maediVisnaRepository;
    /** @var MateRepository */
    protected $mateRepository;
    /** @var MeasurementRepository */
    protected $measurementRepository;
    /** @var MessageRepository */
    protected $messageRepository;
    /** @var MuscleThicknessRepository */
    protected $muscleThicknessRepository;
    /** @var NeuterRepository */
    protected $neuterRepository;
    /** @var NormalDistributionRepository */
    protected $normalDistributionRepository;
    /** @var PedigreeRepository */
    protected $pedigreeRepository;
    /** @var PedigreeCodeRepository */
    protected $pedigreeCodeRepository;
    /** @var PedigreeRegisterRepository */
    protected $pedigreeRegisterRepository;
    /** @var PerformanceMeasurementRepository */
    protected $performanceMeasurementRepository;
    /** @var PersonRepository */
    protected $personRepository;
    /** @var PredicateRepository */
    protected $predicateRepository;
    /** @var ProcessorRepository */
    protected $processorRepository;
    /** @var ProvinceRepository */
    protected $provinceRepository;
    /** @var RaceRepository */
    protected $raceRepository;
    /** @var RamRepository */
    protected $ramRepository;
    /** @var ResultTableBreedGradesRepository */
    protected $resultTableBreedGradesRepository;
    /** @var RetrieveAnimalDetailsRepository */
    protected $retrieveAnimalDetailsRepository;
    /** @var RetrieveAnimalDetailsResponseRepository */
    protected $retrieveAnimalDetailsResponseRepository;
    /** @var RetrieveAnimalsRepository */
    protected $retrieveAnimalsRepository;
    /** @var RetrieveAnimalsResponseRepository */
    protected $retrieveAnimalsResponseRepository;
    /** @var RetrieveCountriesRepository */
    protected $retrieveCountriesRepository;
    /** @var RetrieveCountriesResponseRepository */
    protected $retrieveCountriesResponseRepository;
    /** @var RetrieveTagsRepository */
    protected $retrieveTagsRepository;
    /** @var RetrieveTagsResponseRepository */
    protected $retrieveTagsResponseRepository;
    /** @var RetrieveUbnDetailsRepository */
    protected $retrieveUbnDetailsRepository;
    /** @var RetrieveUbnDetailsResponseRepository */
    protected $retrieveUbnDetailsResponseRepository;
    /** @var RevokeDeclarationResponse */
    protected $revokeDeclarationRepository;
    /** @var RevokeDeclarationResponseRepository */
    protected $revokeDeclarationResponseRepository;
    /** @var ScrapieRepository */
    protected $scrapieRepository;
    /** @var StillbornRepository */
    protected $stillbornRepository;
    /** @var TagRepository */
    protected $tagRepository;
    /** @var TagSyncErrorLogRepository */
    protected $tagSyncErrorLogRepository;
    /** @var TagTransferItemRequestRepository */
    protected $tagTransferItemRequestRepository;
    /** @var TagTransferItemResponseRepository */
    protected $tagTransferItemResponseRepository;
    /** @var TailLengthRepository */
    protected $tailLengthRepository;
    /** @var TokenRepository */
    protected $tokenRepository;
    /** @var VsmIdGroupRepository */
    protected $vsmIdGroupRepository;
    /** @var WeightRepository */
    protected $weightRepository;
    /** @var WormResistanceRepository */
    protected $wormResistanceRepository;


    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;

//        $this->addressRepository = $this->manager->getRepository(Address::class);
//        $this->animalRepository = $this->manager->getRepository(Animal::class);
//
//        $this->bodyFatRepository = $this->manager->getRepository(BodyFat::class);
//
//        $this->caseousLymphadenitis = $this->manager->getRepository(CaseousLymphadenitis::class);
        $this->clientRepository = $this->manager->getRepository(Client::class);
//        $this->collarRepository = $this->manager->getRepository(Collar::class);
//        $this->companyRepository = $this->manager->getRepository(Company::class);
//        $this->companyNoteRepository = $this->manager->getRepository(CompanyNote::class);
//        $this->contactFormMenuRepository = $this->manager->getRepository(ContactFormMenu::class);
//        $this->contentRepository = $this->manager->getRepository(Content::class);
//        $this->countryRepository = $this->manager->getRepository(Country::class);
//
//        $this->declareBaseRepository = $this->manager->getRepository(DeclareBase::class);
//        $this->declareBaseResponseRepository = $this->manager->getRepository(DeclareBaseResponse::class);
//        $this->declareNsfoBaseRepository = $this->manager->getRepository(DeclareNsfoBase::class);
//
//        $this->declarationDetailRepository = $this->manager->getRepository(DeclarationDetail::class);
//        $this->declareAnimalFlagRepository = $this->manager->getRepository(DeclareAnimalFlag::class);
//        $this->declareArrivalRepository = $this->manager->getRepository(DeclareArrival::class);
//        $this->declareArrivalResponseRepository = $this->manager->getRepository(DeclareArrivalResponse::class);
//        $this->declareBirthRepository = $this->manager->getRepository(DeclareBirth::class);
//        $this->declareBirthResponseRepository = $this->manager->getRepository(DeclareBirthResponse::class);
//        $this->declareImportRepository = $this->manager->getRepository(DeclareImport::class);
//        $this->declareImportResponseRepository = $this->manager->getRepository(DeclareImportResponse::class);
//        $this->declareDepartRepository = $this->manager->getRepository(DeclareDepart::class);
//        $this->declareDepartResponseRepository = $this->manager->getRepository(DeclareDepartResponse::class);
//        $this->declareExportRepository = $this->manager->getRepository(DeclareExport::class);
//        $this->declareExportResponseRepository = $this->manager->getRepository(DeclareExportResponse::class);
//        $this->declareLossRepository = $this->manager->getRepository(DeclareLoss::class);
//        $this->declareLossResponseRepository = $this->manager->getRepository(DeclareLossResponse::class);
//        $this->declareTagsTransferRepository = $this->manager->getRepository(DeclareTagsTransfer::class);
//        $this->declareTagsTransferResponseRepository = $this->manager->getRepository(DeclareTagsTransferResponse::class);
//        $this->declareTagReplaceRepository = $this->manager->getRepository(DeclareTagReplace::class);
//        $this->declareTagReplaceResponseRepository = $this->manager->getRepository(DeclareTagReplaceResponse::class);
//
//        $this->declareWeightRepository = $this->manager->getRepository(DeclareWeight::class);
//
//        $this->editTypeRepository = $this->manager->getRepository(EditType::class);
//        $this->employeeRepository = $this->manager->getRepository(Employee::class);
//        $this->errorLogAnimalPedigreeRepository = $this->manager->getRepository(ErrorLogAnimalPedigree::class);
//        $this->eweRepository = $this->manager->getRepository(Ewe::class);
//        $this->exteriorRepository = $this->manager->getRepository(Exterior::class);
//
//        $this->fat1Repository = $this->manager->getRepository(Fat1::class);
//        $this->fat2Repository = $this->manager->getRepository(Fat2::class);
//        $this->fat3Repository = $this->manager->getRepository(Fat3::class);
//        $this->footRotRepository = $this->manager->getRepository(FootRot::class);
//        $this->ftpFailedImportRepository = $this->manager->getRepository(FTPFailedImport::class);
//
//        $this->genderHistoryItemRepository = $this->manager->getRepository(GenderHistoryItem::class);
//
//        $this->inspectorRepository = $this->manager->getRepository(Inspector::class);
//        $this->inspectorAuthorizationRepository = $this->manager->getRepository(InspectorAuthorization::class);
//        $this->invoiceRepository = $this->manager->getRepository(Invoice::class);
//        $this->invoiceRuleRepository = $this->manager->getRepository(InvoiceRule::class);
//
//        $this->litterRepository = $this->manager->getRepository(Litter::class);
//        $this->locationRepository = $this->manager->getRepository(Location::class);
//        $this->locationAddressRepository = $this->manager->getRepository(LocationAddress::class);
//        $this->locationHealthRepository = $this->manager->getRepository(LocationHealth::class);
//        $this->locationHealthInspectionRepository = $this->manager->getRepository(LocationHealthInspection::class);
//        $this->locationHealthInspectionDirectionRepository = $this->manager->getRepository(LocationHealthInspectionDirection::class);
//        $this->locationHealthInspectionResultRepository = $this->manager->getRepository(LocationHealthInspectionResult::class);
//        $this->locationHealthLetterRepository = $this->manager->getRepository(LocationHealthLetter::class);
//        $this->locationHealthMessageRepository = $this->manager->getRepository(LocationHealthMessage::class);
//        $this->locationHealthQueueRepository = $this->manager->getRepository(LocationHealthQueue::class);
//
//        $this->maediVisnaRepository = $this->manager->getRepository(MaediVisna::class);
//        $this->mateRepository = $this->manager->getRepository(Mate::class);
//        $this->measurementRepository = $this->manager->getRepository(Measurement::class);
//        $this->messageRepository = $this->manager->getRepository(Message::class);
//        $this->muscleThicknessRepository = $this->manager->getRepository(MuscleThickness::class);
//
//        $this->neuterRepository = $this->manager->getRepository(Neuter::class);
//        $this->normalDistributionRepository = $this->manager->getRepository(NormalDistribution::class);
//
//        $this->pedigreeRepository = $this->manager->getRepository(Pedigree::class);
//        $this->pedigreeCodeRepository = $this->manager->getRepository(PedigreeCode::class);
//        $this->pedigreeRegisterRepository = $this->manager->getRepository(PedigreeRegister::class);
//        $this->performanceMeasurementRepository = $this->manager->getRepository(PerformanceMeasurement::class);
//        $this->personRepository = $this->manager->getRepository(Person::class);
//        $this->predicateRepository = $this->manager->getRepository(Predicate::class);
//        $this->processorRepository = $this->manager->getRepository(Processor::class);
//        $this->provinceRepository = $this->manager->getRepository(Province::class);
//
//        $this->raceRepository = $this->manager->getRepository(Race::class);
//        $this->ramRepository = $this->manager->getRepository(Ram::class);
//        $this->resultTableBreedGradesRepository = $this->manager->getRepository(ResultTableBreedGrades::class);
//        $this->retrieveAnimalDetailsRepository = $this->manager->getRepository(RetrieveAnimalDetails::class);
//        $this->retrieveAnimalDetailsResponseRepository = $this->manager->getRepository(RetrieveAnimalDetailsResponse::class);
//        $this->retrieveAnimalsRepository = $this->manager->getRepository(RetrieveAnimals::class);
//        $this->retrieveAnimalsResponseRepository = $this->manager->getRepository(RetrieveAnimalsResponse::class);
//        $this->retrieveCountriesRepository = $this->manager->getRepository(RetrieveCountries::class);
//        $this->retrieveCountriesResponseRepository = $this->manager->getRepository(RetrieveCountriesResponse::class);
//        $this->retrieveTagsRepository = $this->manager->getRepository(RetrieveTags::class);
//        $this->retrieveTagsResponseRepository = $this->manager->getRepository(RetrieveTagsResponse::class);
//        $this->retrieveUbnDetailsRepository = $this->manager->getRepository(RetrieveUbnDetails::class);
//        $this->retrieveUbnDetailsResponseRepository = $this->manager->getRepository(RetrieveUbnDetailsResponse::class);
//        $this->revokeDeclarationRepository = $this->manager->getRepository(RevokeDeclaration::class);
//        $this->revokeDeclarationResponseRepository = $this->manager->getRepository(RevokeDeclarationResponse::class);
//
//        $this->scrapieRepository = $this->manager->getRepository(Scrapie::class);
//        $this->stillbornRepository = $this->manager->getRepository(Stillborn::class);
//
//        $this->tagRepository = $this->manager->getRepository(Tag::class);
//        $this->tagSyncErrorLogRepository = $this->manager->getRepository(TagSyncErrorLog::class);
//        $this->tagTransferItemRequestRepository = $this->manager->getRepository(TagTransferItemRequest::class);
//        $this->tagTransferItemResponseRepository = $this->manager->getRepository(TagTransferItemResponse::class);
//        $this->tailLengthRepository = $this->manager->getRepository(TailLength::class);
//        $this->tokenRepository = $this->manager->getRepository(Token::class);
//
//        $this->vsmIdGroupRepository = $this->manager->getRepository(VsmIdGroup::class);
//
//        $this->weightRepository = $this->manager->getRepository(Weight::class);
//        $this->wormResistanceRepository = $this->manager->getRepository(WormResistance::class);
    }


    /**
     * @return AddressRepository
     */
    public function getAddressRepository()
    {
        return $this->addressRepository;
    }


    /**
     * @return AnimalRepository
     */
    public function getAnimalRepository()
    {
        return $this->animalRepository;
    }

    /**
     * @return BodyFatRepository
     */
    public function getBodyFatRepository()
    {
        return $this->bodyFatRepository;
    }

    /**
     * @return CaseousLymphadenitisRepository
     */
    public function getCaseousLymphadenitis()
    {
        return $this->caseousLymphadenitis;
    }

    /**
     * @return ClientRepository
     */
    public function getClientRepository()
    {
        return $this->clientRepository;
    }

    /**
     * @return CollarRepository
     */
    public function getCollarRepository()
    {
        return $this->collarRepository;
    }

    /**
     * @return CompanyRepository
     */
    public function getCompanyRepository()
    {
        return $this->companyRepository;
    }

    /**
     * @return CompanyNoteRepository
     */
    public function getCompanyNoteRepository()
    {
        return $this->companyNoteRepository;
    }

    /**
     * @return ContactFormMenuRepository
     */
    public function getContactFormMenuRepository()
    {
        return $this->contactFormMenuRepository;
    }

    /**
     * @return ContentRepository
     */
    public function getContentRepository()
    {
        return $this->contentRepository;
    }

    /**
     * @return CountryRepository
     */
    public function getCountryRepository()
    {
        return $this->countryRepository;
    }

    /**
     * @return DeclarationDetailRepository
     */
    public function getDeclarationDetailRepository()
    {
        return $this->declarationDetailRepository;
    }

    /**
     * @return DeclareBaseRepository
     */
    public function getDeclareBaseRepository()
    {
        return $this->declareBaseRepository;
    }

    /**
     * @return DeclareBaseResponseRepository
     */
    public function getDeclareBaseResponseRepository()
    {
        return $this->declareBaseResponseRepository;
    }

    /**
     * @return DeclareNsfoBaseRepository
     */
    public function getDeclareNsfoBaseRepository()
    {
        return $this->declareNsfoBaseRepository;
    }

    /**
     * @return DeclareAnimalFlagRepository
     */
    public function getDeclareAnimalFlagRepository()
    {
        return $this->declareAnimalFlagRepository;
    }

    /**
     * @return DeclareArrivalRepository
     */
    public function getDeclareArrivalRepository()
    {
        return $this->declareArrivalRepository;
    }

    /**
     * @return DeclareArrivalResponseRepository
     */
    public function getDeclareArrivalResponseRepository()
    {
        return $this->declareArrivalResponseRepository;
    }

    /**
     * @return DeclareBirthRepository
     */
    public function getDeclareBirthRepository()
    {
        return $this->declareBirthRepository;
    }

    /**
     * @return DeclareBirthResponseRepository
     */
    public function getDeclareBirthResponseRepository()
    {
        return $this->declareBirthResponseRepository;
    }

    /**
     * @return DeclareImportRepository
     */
    public function getDeclareImportRepository()
    {
        return $this->declareImportRepository;
    }

    /**
     * @return DeclareImportResponseRepository
     */
    public function getDeclareImportResponseRepository()
    {
        return $this->declareImportResponseRepository;
    }

    /**
     * @return DeclareDepartRepository
     */
    public function getDeclareDepartRepository()
    {
        return $this->declareDepartRepository;
    }

    /**
     * @return DeclareDepartResponseRepository
     */
    public function getDeclareDepartResponseRepository()
    {
        return $this->declareDepartResponseRepository;
    }

    /**
     * @return DeclareExportRepository
     */
    public function getDeclareExportRepository()
    {
        return $this->declareExportRepository;
    }

    /**
     * @return DeclareExportResponseRepository
     */
    public function getDeclareExportResponseRepository()
    {
        return $this->declareExportResponseRepository;
    }

    /**
     * @return DeclareLossRepository
     */
    public function getDeclareLossRepository()
    {
        return $this->declareLossRepository;
    }

    /**
     * @return DeclareLossResponseRepository
     */
    public function getDeclareLossResponseRepository()
    {
        return $this->declareLossResponseRepository;
    }

    /**
     * @return DeclareTagsTransferRepository
     */
    public function getDeclareTagsTransferRepository()
    {
        return $this->declareTagsTransferRepository;
    }

    /**
     * @return DeclareTagsTransferResponseRepository
     */
    public function getDeclareTagsTransferResponseRepository()
    {
        return $this->declareTagsTransferResponseRepository;
    }

    /**
     * @return DeclareTagReplaceRepository
     */
    public function getDeclareTagReplaceRepository()
    {
        return $this->declareTagReplaceRepository;
    }

    /**
     * @return DeclareTagReplaceResponseRepository
     */
    public function getDeclareTagReplaceResponseRepository()
    {
        return $this->declareTagReplaceResponseRepository;
    }

    /**
     * @return DeclareWeightRepository
     */
    public function getDeclareWeightRepository()
    {
        return $this->declareWeightRepository;
    }

    /**
     * @return EditTypeRepository
     */
    public function getEditTypeRepository()
    {
        return $this->editTypeRepository;
    }

    /**
     * @return EmployeeRepository
     */
    public function getEmployeeRepository()
    {
        return $this->employeeRepository;
    }

    /**
     * @return ErrorLogAnimalPedigreeRepository
     */
    public function getErrorLogAnimalPedigreeRepository()
    {
        return $this->errorLogAnimalPedigreeRepository;
    }

    /**
     * @return EweRepository
     */
    public function getEweRepository()
    {
        return $this->eweRepository;
    }

    /**
     * @return ExteriorRepository
     */
    public function getExteriorRepository()
    {
        return $this->exteriorRepository;
    }

    /**
     * @return Fat1Repository
     */
    public function getFat1Repository()
    {
        return $this->fat1Repository;
    }

    /**
     * @return Fat2Repository
     */
    public function getFat2Repository()
    {
        return $this->fat2Repository;
    }

    /**
     * @return Fat3Repository
     */
    public function getFat3Repository()
    {
        return $this->fat3Repository;
    }

    /**
     * @return FootRotRepository
     */
    public function getFootRotRepository()
    {
        return $this->footRotRepository;
    }

    /**
     * @return FTPFailedImportRepository
     */
    public function getFtpFailedImportRepository()
    {
        return $this->ftpFailedImportRepository;
    }

    /**
     * @return GenderHistoryItemRepository
     */
    public function getGenderHistoryItemRepository()
    {
        return $this->genderHistoryItemRepository;
    }

    /**
     * @return InspectorRepository
     */
    public function getInspectorRepository()
    {
        return $this->inspectorRepository;
    }

    /**
     * @return InspectorAuthorizationRepository
     */
    public function getInspectorAuthorizationRepository()
    {
        return $this->inspectorAuthorizationRepository;
    }

    /**
     * @return InvoiceRepository
     */
    public function getInvoiceRepository()
    {
        return $this->invoiceRepository;
    }

    /**
     * @return InvoiceRuleRepository
     */
    public function getInvoiceRuleRepository()
    {
        return $this->invoiceRuleRepository;
    }

    /**
     * @return LitterRepository
     */
    public function getLitterRepository()
    {
        return $this->litterRepository;
    }

    /**
     * @return LocationRepository
     */
    public function getLocationRepository()
    {
        return $this->locationRepository;
    }

    /**
     * @return LocationAddressRepository
     */
    public function getLocationAddressRepository()
    {
        return $this->locationAddressRepository;
    }

    /**
     * @return LocationHealthRepository
     */
    public function getLocationHealthRepository()
    {
        return $this->locationHealthRepository;
    }

    /**
     * @return LocationHealthInspectionRepository
     */
    public function getLocationHealthInspectionRepository()
    {
        return $this->locationHealthInspectionRepository;
    }

    /**
     * @return LocationHealthInspectionDirectionRepository
     */
    public function getLocationHealthInspectionDirectionRepository()
    {
        return $this->locationHealthInspectionDirectionRepository;
    }

    /**
     * @return LocationHealthInspectionResultRepository
     */
    public function getLocationHealthInspectionResultRepository()
    {
        return $this->locationHealthInspectionResultRepository;
    }

    /**
     * @return LocationHealthLetterRepository
     */
    public function getLocationHealthLetterRepository()
    {
        return $this->locationHealthLetterRepository;
    }

    /**
     * @return LocationHealthMessageRepository
     */
    public function getLocationHealthMessageRepository()
    {
        return $this->locationHealthMessageRepository;
    }

    /**
     * @return LocationHealthQueueRepository
     */
    public function getLocationHealthQueueRepository()
    {
        return $this->locationHealthQueueRepository;
    }

    /**
     * @return MaediVisnaRepository
     */
    public function getMaediVisnaRepository()
    {
        return $this->maediVisnaRepository;
    }

    /**
     * @return MateRepository
     */
    public function getMateRepository()
    {
        return $this->mateRepository;
    }

    /**
     * @return MeasurementRepository
     */
    public function getMeasurementRepository()
    {
        return $this->measurementRepository;
    }

    /**
     * @return MessageRepository
     */
    public function getMessageRepository()
    {
        return $this->messageRepository;
    }

    /**
     * @return MuscleThicknessRepository
     */
    public function getMuscleThicknessRepository()
    {
        return $this->muscleThicknessRepository;
    }

    /**
     * @return NeuterRepository
     */
    public function getNeuterRepository()
    {
        return $this->neuterRepository;
    }

    /**
     * @return NormalDistributionRepository
     */
    public function getNormalDistributionRepository()
    {
        return $this->normalDistributionRepository;
    }

    /**
     * @return PedigreeRepository
     */
    public function getPedigreeRepository()
    {
        return $this->pedigreeRepository;
    }

    /**
     * @return PedigreeCodeRepository
     */
    public function getPedigreeCodeRepository()
    {
        return $this->pedigreeCodeRepository;
    }

    /**
     * @return PedigreeRegisterRepository
     */
    public function getPedigreeRegisterRepository()
    {
        return $this->pedigreeRegisterRepository;
    }

    /**
     * @return PerformanceMeasurementRepository
     */
    public function getPerformanceMeasurementRepository()
    {
        return $this->performanceMeasurementRepository;
    }

    /**
     * @return PersonRepository
     */
    public function getPersonRepository()
    {
        return $this->personRepository;
    }

    /**
     * @return PredicateRepository
     */
    public function getPredicateRepository()
    {
        return $this->predicateRepository;
    }

    /**
     * @return ProcessorRepository
     */
    public function getProcessorRepository()
    {
        return $this->processorRepository;
    }

    /**
     * @return ProvinceRepository
     */
    public function getProvinceRepository()
    {
        return $this->provinceRepository;
    }

    /**
     * @return RaceRepository
     */
    public function getRaceRepository()
    {
        return $this->raceRepository;
    }

    /**
     * @return RamRepository
     */
    public function getRamRepository()
    {
        return $this->ramRepository;
    }

    /**
     * @return ResultTableBreedGradesRepository
     */
    public function getResultTableBreedGradesRepository()
    {
        return $this->resultTableBreedGradesRepository;
    }

    /**
     * @return RetrieveAnimalDetailsRepository
     */
    public function getRetrieveAnimalDetailsRepository()
    {
        return $this->retrieveAnimalDetailsRepository;
    }

    /**
     * @return RetrieveAnimalDetailsResponseRepository
     */
    public function getRetrieveAnimalDetailsResponseRepository()
    {
        return $this->retrieveAnimalDetailsResponseRepository;
    }

    /**
     * @return RetrieveAnimalsRepository
     */
    public function getRetrieveAnimalsRepository()
    {
        return $this->retrieveAnimalsRepository;
    }

    /**
     * @return RetrieveAnimalsResponseRepository
     */
    public function getRetrieveAnimalsResponseRepository()
    {
        return $this->retrieveAnimalsResponseRepository;
    }

    /**
     * @return RetrieveCountriesRepository
     */
    public function getRetrieveCountriesRepository()
    {
        return $this->retrieveCountriesRepository;
    }

    /**
     * @return RetrieveCountriesResponseRepository
     */
    public function getRetrieveCountriesResponseRepository()
    {
        return $this->retrieveCountriesResponseRepository;
    }

    /**
     * @return RetrieveTagsRepository
     */
    public function getRetrieveTagsRepository()
    {
        return $this->retrieveTagsRepository;
    }

    /**
     * @return RetrieveTagsResponseRepository
     */
    public function getRetrieveTagsResponseRepository()
    {
        return $this->retrieveTagsResponseRepository;
    }

    /**
     * @return RetrieveUbnDetailsRepository
     */
    public function getRetrieveUbnDetailsRepository()
    {
        return $this->retrieveUbnDetailsRepository;
    }

    /**
     * @return RetrieveUbnDetailsResponseRepository
     */
    public function getRetrieveUbnDetailsResponseRepository()
    {
        return $this->retrieveUbnDetailsResponseRepository;
    }

    /**
     * @return RevokeDeclarationResponse
     */
    public function getRevokeDeclarationRepository()
    {
        return $this->revokeDeclarationRepository;
    }

    /**
     * @return RevokeDeclarationResponseRepository
     */
    public function getRevokeDeclarationResponseRepository()
    {
        return $this->revokeDeclarationResponseRepository;
    }

    /**
     * @return ScrapieRepository
     */
    public function getScrapieRepository()
    {
        return $this->scrapieRepository;
    }

    /**
     * @return StillbornRepository
     */
    public function getStillbornRepository()
    {
        return $this->stillbornRepository;
    }

    /**
     * @return TagRepository
     */
    public function getTagRepository()
    {
        return $this->tagRepository;
    }

    /**
     * @return TagSyncErrorLogRepository
     */
    public function getTagSyncErrorLogRepository()
    {
        return $this->tagSyncErrorLogRepository;
    }

    /**
     * @return TagTransferItemRequestRepository
     */
    public function getTagTransferItemRequestRepository()
    {
        return $this->tagTransferItemRequestRepository;
    }

    /**
     * @return TagTransferItemResponseRepository
     */
    public function getTagTransferItemResponseRepository()
    {
        return $this->tagTransferItemResponseRepository;
    }

    /**
     * @return TailLengthRepository
     */
    public function getTailLengthRepository()
    {
        return $this->tailLengthRepository;
    }

    /**
     * @return TokenRepository
     */
    public function getTokenRepository()
    {
        return $this->tokenRepository;
    }

    /**
     * @return VsmIdGroupRepository
     */
    public function getVsmIdGroupRepository()
    {
        return $this->vsmIdGroupRepository;
    }

    /**
     * @return WeightRepository
     */
    public function getWeightRepository()
    {
        return $this->weightRepository;
    }

    /**
     * @return WormResistanceRepository
     */
    public function getWormResistanceRepository()
    {
        return $this->wormResistanceRepository;
    }

}