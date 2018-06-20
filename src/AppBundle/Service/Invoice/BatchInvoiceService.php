<?php


namespace AppBundle\Service\Invoice;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\ActionLog;
use AppBundle\Entity\Address;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\Invoice;
use AppBundle\Entity\InvoiceRule;
use AppBundle\Entity\InvoiceRuleSelection;
use AppBundle\Entity\InvoiceSenderDetails;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationRepository;
use AppBundle\Entity\Message;
use AppBundle\Entity\PedigreeRegister;
use AppBundle\Enumerator\DateTimeFormats;
use AppBundle\Enumerator\EmailPrefix;
use AppBundle\Enumerator\InvoiceAction;
use AppBundle\Enumerator\InvoiceMessages;
use AppBundle\Enumerator\InvoiceRuleType;
use AppBundle\Enumerator\InvoiceStatus;
use AppBundle\Service\ControllerServiceBase;
use AppBundle\Service\Twinfield\TwinfieldInvoiceService;
use AppBundle\Service\Google\FireBaseService;
use AppBundle\Util\ResultUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\HttpFoundation\Request;

/**
 * This service contains all functionality regarding the sending of a batch of invoices.
 * It's purpose is to take all active companies and create an invoice for each location of that company, based on
 * certain logic.
 *
 * Class BatchInvoiceService
 * @package AppBundle\Service\Invoice
 */
class BatchInvoiceService extends ControllerServiceBase
{
    /** @var TwinfieldInvoiceService */
    private $twinfieldInvoiceService;

    /** @var FireBaseService */
    private $fireBaseService;

    private $invoicesSendCounter = 0;

    public function initializeServices(TwinfieldInvoiceService $invoiceService)
    {
        $this->twinfieldInvoiceService = $invoiceService;
    }

    /**
     * @required
     *
     * @param FireBaseService $fireBaseService
     */
    public function setFireBaseService(FireBaseService $fireBaseService) {
        $this->fireBaseService = $fireBaseService;
    }

    /**
     * This is the only public function in the service, that will call all other functions to create invoices for all
     * active companies
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createBatchInvoices(Request $request) {
        $companies = $this->getManager()->getRepository(Company::class)->findBy(array("isActive" => true));
        $requestJson = $request->getContent();
        $requestJson = json_decode($requestJson, true);
        $date = $requestJson["controlDate"];
        $date = new \DateTime($date);
        $animalsByCompanyResult = $this->getAllAnimalsSortedByPedigreeRegisterAndLocationOnControlDate($date);
        $registerCounts = $this->setupAnimalDataByCompanyLocation($companies, $animalsByCompanyResult);
        $rules = new ArrayCollection($this->getManager()->getRepository(InvoiceRule::class)->findBy(array("isBatch" => true)));
        $newRules = $this->createRuleCopiesForBatch($rules, $date);
        $invoices = $this->setupInvoices($registerCounts, $companies, $newRules, $date, $request);
        $log = new ActionLog($this->getUser(), $this->getUser(), InvoiceAction::BATCH_INVOICES_SEND);
        $this->getManager()->persist($log);
        $this->getManager()->flush();
        return ResultUtil::successResult($invoices);
    }

    /**
     * To ensure that each batch of invoices has a unique set of rules, this function takes the base set of batch invoice rules
     * Along with the control date for the new batch, and persists a new set of rules, for the batch.
     *
     * @param ArrayCollection $originalRules
     * @param \DateTime $controlDate
     * @return ArrayCollection
     */
    private function createRuleCopiesForBatch(ArrayCollection $originalRules, $controlDate) {
        $registers = $this->getManager()->getRepository(PedigreeRegister::class)->findAll();
        $newRules = new ArrayCollection();
        foreach ($originalRules as $rule) {
            /** @var InvoiceRule $newRule */
            $newRule = clone $rule;
            if ($newRule->getType() == InvoiceRuleType::BASE_ADMINISTRATION) {
                $newRules->add($newRule);
                $this->getManager()->persist($newRule);
                continue;
            }

            if ($newRule->getType() == InvoiceRuleType::ADMINISTRATION_ONLINE_EWE || $newRule->getType() == InvoiceRuleType::ADMINISTRATION_OFFLINE_EWE) {
                /** @var PedigreeRegister $register */
                foreach ($registers as $register) {
                    $registerRule = clone $newRule;
                    $registerDescription = $registerRule->getDescription()." - ".$register->getAbbreviation()." - ".$controlDate->format(DateTimeFormats::DAY_MONTH_YEAR);
                    $registerRule->setDescription($registerDescription);
                    $registerRule->setSubArticleCode($register->getAbbreviation());
                    $this->getManager()->persist($registerRule);
                    $newRules->add($registerRule);
                }
                continue;
            }
            if ($newRule->getType() == InvoiceRuleType::SUBSCRIPTION_NSFO_ONLINE || $newRule->getType() == InvoiceRuleType::SUBSCRIPTION_ANIMAL_HEALTH) {
                $newRule->setDescription($newRule->getDescription()." - ".$controlDate->format(DateTimeFormats::YEAR));
                $this->getManager()->persist($newRule);
                $newRules->add($newRule);
                continue;
            }
        }
        return $newRules;
    }

    /**
     * This function takes the animal data, all active companies, the set of invoice rules for the batch, and the control date,
     * and start executing logic to setup an invoice every location of each company.
     * @param array $animalData
     * @param array $companies
     * @param ArrayCollection $batchRules
     * @param \DateTime $date
     * @return array
     */
    private function setupInvoices(array $animalData, array $companies, ArrayCollection $batchRules, \DateTime $date, Request $request){
        $invoices = array();
        /** @var Company $company */
        foreach ($companies as $company) {
            if (array_key_exists($company->getId(), $animalData)) {
                $invoiceSet = $this->SetupInvoicesForCompanyLocation($company, $batchRules, $date, $request, $animalData[$company->getId()]);
            } else {
                $invoiceSet = $this->SetupInvoicesForCompanyLocation($company, $batchRules, $date, $request);
            }
            if (sizeof($invoiceSet) > 0) {
                foreach ($invoiceSet as $invoice) {
                    $invoices[] = $invoice;
                }
            }
        }

        return $invoices;
    }

    /**
     * This function takes the original animal data and all active companies, and puts the animal counts
     * sorted by company, and then company location, and then each different pedigree register.
     * @param array $companies
     * @param array $animalData
     * @return array
     */
    private function setupAnimalDataByCompanyLocation(array $companies,array $animalData){
        $animalCountsByRegister = array();
        /** @var Company $company */
        foreach ($companies as $company) {
            if (!array_key_exists($company->getId(), $animalData)) {
                continue;
            }
            $administration = $animalData[$company->getId()];
            foreach ($administration as $locationRegister) {
                if (isset($animalCountsByRegister[$company->getId()][$locationRegister["location_id"]][$locationRegister["abbreviation"]])) {
                    $animalCountsByRegister[$company->getId()][$locationRegister["location_id"]][$locationRegister["abbreviation"]] += $locationRegister["animal_count"];
                    continue;
                }
                $animalCountsByRegister[$company->getId()][$locationRegister["location_id"]][$locationRegister["abbreviation"]] = $locationRegister["animal_count"];
            }
        }
        return $animalCountsByRegister;
    }

    /**
     * This function sets up an invoice for each location of a company, and adds invoice rules, based on the data array
     * and if the company has certain subscriptions.
     * This function also sets the company properties on the invoice.
     *
     * @param Company $company
     * @param ArrayCollection $batchRules
     * @param \DateTime $date
     * @param array|null $data
     * @param Request $request
     * @return array
     */
    private function SetupInvoicesForCompanyLocation(Company $company, ArrayCollection $batchRules, \DateTime $date, Request $request, array $data = null) {
        $invoiceSet = array();

        $details = $this->getManager()->getRepository(InvoiceSenderDetails::class)->findBy(array(), array("id" => "DESC"),1);
        $details = $details[0];

        /** @var Location $location */
        foreach ($company->getLocations() as $location) {
            $invoice = new Invoice();
            $invoice->setSenderDetails($details);
            $invoice->setIsBatch(true);
            $this->setAddressProperties($invoice, $company->getBillingAddress());
            if ($company->getDebtorNumber()) {
                $invoice->setCompanyTwinfieldAdministrationCode($company->getTwinfieldOfficeCode());
                $invoice->setStatus(InvoiceStatus::UNPAID);
                $invoice->setCompanyDebtorNumber($company->getDebtorNumber());
                $invoice->setInvoiceDate(new \DateTime());
            } else {
                $invoice->setStatus(InvoiceStatus::NOT_SEND);
            }
            $invoice->setCompanyLocalId($company->getCompanyId());
            $invoice->setCompanyName($company->getCompanyName());
            $invoice->setUbn($location->getUbn());
            $invoice->setCompany($company);
            $invoice->setCompanyVatNumber($company->getVatNumber());
            $message = new Message();
            if (
                !$company->getDebtorNumber()
                || $company->getDebtorNumber() == ""
                || !$company->getTwinfieldOfficeCode()
                || $company->getTwinfieldOfficeCode() == "")
            {
                $invoice->setStatus(InvoiceStatus::NOT_SEND);
            } else {
                $invoice->setStatus(InvoiceStatus::UNPAID);
                $invoice->setInvoiceDate(new \DateTime());
                $client = $this->getAccountOwner($request);
                $message->setSender($client);
                $message->setType(InvoiceMessages::NEW_INVOICE_TYPE);
                $message->setSubject(InvoiceMessages::NEW_INVOICE_SUBJECT);
                $message->setMessage(InvoiceMessages::NEW_INVOICE_MESSAGE);
                $message->setReceiver($invoice->getCompany()->getOwner());
                $message->setReceiverLocation($location);
            }
            if ($data && array_key_exists($location->getId(), $data)) {
                $dataSet = $data[$location->getId()];
                $this->addAnimalDataInvoiceRulesToInvoice($invoice, $company, $dataSet, $batchRules, $date);
            }
            if ($company->getAnimalHealthSubscription() != null && $company->getAnimalHealthSubscription()) {
                $this->addAnimalHealthSubscriptionInvoiceRule($invoice, $batchRules, $date);
            }
            $breakdown = $invoice->getVatBreakdownRecords();
            if ($breakdown->getTotalExclVat() == 0) {
                continue;
            }
            $invoice->setTotal($invoice->getVatBreakdownRecords()->getTotalInclVat());
            if ($invoice->getStatus() == InvoiceStatus::UNPAID) {
                $this->persist($message);
                foreach($location->getOwner()->getMobileDevices() as $mobileDevice) {
                    $title = $this->translator->trans($message->getType());
                    $this->fireBaseService->sendMessageToDevice($mobileDevice->getRegistrationToken(), $title, $message->getData());
                }
            }
            $invoiceSet[] = $invoice;
            $this->getManager()->persist($invoice);
        }
        return $invoiceSet;
    }

    private function validateAndSendToTwinfield(Invoice $invoice) {
        $message = "Company debtor number and/or twinfield administration code are not filled out";
        $log = new ActionLog($this->getUser(), $this->getUser(), InvoiceAction::TWINFIELD_ERROR, false, $message);
        if ($invoice->getCompanyTwinfieldAdministrationCode() && $invoice->getCompanyDebtorNumber()) {
            if ($this->invoicesSendCounter >= 100) {
                $this->twinfieldInvoiceService->reAuthenticate();
                $this->invoicesSendCounter = 0;
            }
            $result = $this->twinfieldInvoiceService->sendInvoiceToTwinfield($invoice);
            $this->invoicesSendCounter++;
            if (is_a($result, \PhpTwinfield\Invoice::class)) {
                $invoice->setInvoiceNumber($result->getInvoiceNumber());
                $invoice->setInvoiceDate(new \DateTime());
                $this->persist($invoice);
                return;
            }
       } else {
            $invoice->setStatus(InvoiceStatus::NOT_SEND);
            $invoice->setInvoiceDate(null);
            $this->persist($log);
            $this->persist($invoice);
        }
    }

    /**
     * This function sets the company address properties on the invoice.
     *
     * @param Invoice $invoice
     * @param Address $address
     */
    private function setAddressProperties(Invoice $invoice, Address $address) {
        $address->getState() != null && $address->getState() != "" ?
            $invoice->setCompanyAddressState($address->getState()) : null;

        $address->getAddressNumberSuffix() != null && $address->getAddressNumberSuffix() != "" ?
            $invoice->setCompanyAddressStreetNumberSuffix($address->getAddressNumberSuffix()) : null;

        $invoice->setCompanyAddressStreetNumber($address->getAddressNumber());
        $invoice->setCompanyAddressStreetName($address->getStreetName());
        $invoice->setCompanyAddressPostalCode($address->getPostalCode());
    }

    /**
     * This function adds the nsfo online subscription invoice rule to the given input invoice.
     *
     * @param Invoice $invoice
     * @param array $dataSet
     * @param ArrayCollection $batchRules
     * @param \DateTime $date
     */
    private function addNSFOOnlineSubscriptionInvoiceRule(Invoice $invoice, array $dataSet, ArrayCollection $batchRules, \DateTime $date) {
        $selection = new InvoiceRuleSelection();
        /** @var InvoiceRule $newRule */
        $newRule = $this->getRulesByType($batchRules, InvoiceRuleType::SUBSCRIPTION_NSFO_ONLINE)->first();
        $selection->setInvoice($invoice);
        $selection->setInvoiceRule($newRule);
        $selection->setAmount(1);
        $selection->setDate($date);
    }

    /**
     * This function adds the animal health subscription invoice rule to the given input invoice
     *
     * @param Invoice $invoice
     * @param ArrayCollection $batchRules
     * @param \DateTime $date
     */
    private function addAnimalHealthSubscriptionInvoiceRule(Invoice $invoice, ArrayCollection $batchRules, \DateTime $date){
        $selection = new InvoiceRuleSelection();
        /** @var InvoiceRule $newRule */
        $newRule = $this->getRulesByType($batchRules, InvoiceRuleType::SUBSCRIPTION_ANIMAL_HEALTH)->first();
        $selection->setInvoice($invoice);
        $selection->setInvoiceRule($newRule);
        $selection->setDate($date);
        $selection->setAmount(1);
        $invoice->addInvoiceRuleSelection($selection);
    }

    /**
     * This function takes the input data set, along with the invoices and moves through the set, which contains the
     * animal counts for every animal pedigree register that is used on the given location, on the control date, and
     * adds administration invoice rules with amounts that are equal to the animal counts.
     *
     * @param Invoice $invoice
     * @param Company $company
     * @param array $dataSet
     * @param ArrayCollection $batchRules
     * @param \DateTime $date
     */
    private function addAnimalDataInvoiceRulesToInvoice(Invoice $invoice,  Company $company, array $dataSet, ArrayCollection $batchRules, \DateTime $date) {
        $selection = new InvoiceRuleSelection();
        $rule = $this->getRulesByType($batchRules, InvoiceRuleType::BASE_ADMINISTRATION);
        $selection->setInvoiceRule($rule->first());
        $selection->setInvoice($invoice);
        $selection->setAmount(sizeof($dataSet));
        $selection->setDate($date);
        $invoice->addInvoiceRuleSelection($selection);
        if ($this->hasLegitimateAccount($company)) {
            $this->addNSFOOnlineSubscriptionInvoiceRule($invoice, $dataSet, $batchRules, $date);
            $rules = $this->getRulesByType($batchRules, InvoiceRuleType::ADMINISTRATION_ONLINE_EWE);
        } else {
            $rules = $this->getRulesByType($batchRules, InvoiceRuleType::ADMINISTRATION_OFFLINE_EWE);
        }
        foreach ($dataSet as $animalRegisterAbbreviation => $animalRegisterCount) {
            /** @var InvoiceRule $registerRule */
            $abbreviationDate = $animalRegisterAbbreviation." - ".$date->format(DateTimeFormats::DAY_MONTH_YEAR);
            /** @var InvoiceRule $batchRule */
            $batchRule = $this->getRulesByDescription($rules, $abbreviationDate)->first();
            $selection = new InvoiceRuleSelection();
            $selection->setInvoice($invoice);
            $selection->setInvoiceRule($batchRule);
            $selection->setAmount($animalRegisterCount);
            $selection->setDate($date);
            $invoice->addInvoiceRuleSelection($selection);
        }
    }

    /**
     * This function checks if a company has a legitimate user account to login. This function decides if
     * the online or offline ewe invoice rule should be used for the invoice.
     *
     * @param Company $company
     * @return bool
     */
    private function hasLegitimateAccount(Company $company) {
        $clients = $this->getManager()->getRepository(Client::class)->findBy(array("employer" => $company));
        $validClients = new ArrayCollection();
        /** @var Client $client */
        foreach ($clients as $client) {
            if (substr($client->getEmailAddress(), 0, 8) != EmailPrefix::INVALID_PREFIX) {
                $validClients->add($client);
            }
        }
        if ($validClients->count() >= 1) {
            return true;
        }
        return false;
    }

    /**
     * This function takes an input array collection of invoice rules and returns every rule that has the same type,
     * as the given input type
     *
     * @param ArrayCollection $rules
     * @param string $type
     * @return ArrayCollection
     */
    private function getRulesByType(ArrayCollection $rules, $type) {
        $criteria = Criteria::create()->where(
            Criteria::expr()->eq("type", $type)
        );
        return $rules->matching($criteria);
    }

    /**
     * This function takes an input array collection of invoice rules and returns every rule that of which the description
     * contains the given input description
     *
     * @param ArrayCollection $rules
     * @param $abbreviationDate
     * @return ArrayCollection
     */
    private function getRulesByDescription(ArrayCollection $rules, $abbreviationDate) {
        $criteria = Criteria::create()->where(
            Criteria::expr()->contains("description", $abbreviationDate)
        );
        return $rules->matching($criteria);
    }

    /**
     * This function calls the animal repository to get all animal counts, sorted by company, the company location, and the
     * pedigree register.
     *
     * @param \DateTime $controlDate
     * @return mixed
     */
    private function getAllAnimalsSortedByPedigreeRegisterAndLocationOnControlDate(\DateTime $controlDate) {
        $dateString = $controlDate->format(DateTimeFormats::DAY_MONTH_YEAR_HOUR_MINUTE_SECOND);

        /** @var AnimalRepository $animalRepository */
        $animalRepository = $this->getManager()->getRepository(Animal::class);

        return $animalRepository->getAnimalCountsByCompanyLocationPedigreeRegisterOnControlDate($dateString);
    }
}