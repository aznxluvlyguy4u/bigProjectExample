<?php


namespace AppBundle\Service\Invoice;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\ActionLog;
use AppBundle\Entity\Address;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\Invoice;
use AppBundle\Entity\InvoiceRule;
use AppBundle\Entity\InvoiceRuleSelection;
use AppBundle\Entity\InvoiceSenderDetails;
use AppBundle\Entity\Location;
use AppBundle\Entity\PedigreeRegister;
use AppBundle\Enumerator\EmailPrefix;
use AppBundle\Enumerator\InvoiceAction;
use AppBundle\Enumerator\InvoiceRuleType;
use AppBundle\Enumerator\InvoiceStatus;
use AppBundle\Service\ControllerServiceBase;
use AppBundle\Service\Twinfield\TwinfieldInvoiceService;
use AppBundle\Util\ResultUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\HttpFoundation\Request;


class BatchInvoiceService extends ControllerServiceBase
{
    private $invoiceNumber;

    private $twinfieldInvoiceService;


    public function initializeServices(TwinfieldInvoiceService $invoiceService) {
        $this->twinfieldInvoiceService = $invoiceService;
    }

    /**
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
        $invoices = $this->setupInvoices($registerCounts, $companies, $newRules, $date);
        $log = new ActionLog($this->getUser(), $this->getUser(), InvoiceAction::BATCH_INVOICES_SEND);
        $this->getManager()->persist($log);
        $this->getManager()->flush();
        return ResultUtil::successResult($invoices);
    }

    private function sendInvoicesToTwinfield(ArrayCollection $invoices) {
        $unpaidInvoices = $this->getUnpaidInvoices($invoices);
        $this->twinfieldInvoiceService->sendInvoices($unpaidInvoices);
    }

    private function getUnpaidInvoices(ArrayCollection $invoices) {
        $criteria = Criteria::create()->where(
            Criteria::expr()->eq("status", InvoiceStatus::UNPAID)
        );
        return $invoices->matching($criteria);
    }

    /**
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
                    $registerDescription = $registerRule->getDescription()." - ".$register->getAbbreviation()." - ".$controlDate->format("d-m-Y");
                    $registerRule->setDescription($registerDescription);
                    $this->getManager()->persist($registerRule);
                    $newRules->add($registerRule);
                }
                continue;
            }
            if ($newRule->getType() == InvoiceRuleType::SUBSCRIPTION_NSFO_ONLINE || $newRule->getType() == InvoiceRuleType::SUBSCRIPTION_ANIMAL_HEALTH) {
                $newRule->setDescription($newRule->getDescription()." - ".$controlDate->format("Y"));
                $this->getManager()->persist($newRule);
                $newRules->add($newRule);
                continue;
            }
        }
        return $newRules;
    }

    /**
     * @param array $animalData
     * @param array $companies
     * @param ArrayCollection $batchRules
     * @param \DateTime $date
     * @return array
     */
    private function setupInvoices(array $animalData, array $companies, ArrayCollection $batchRules, \DateTime $date){
        $this->createInvoiceNumber();
        $invoices = array();
        /** @var Company $company */
        foreach ($companies as $company) {
            if (array_key_exists($company->getId(), $animalData)) {
                $invoiceSet = $this->SetupInvoicesForCompanyLocation($company, $batchRules, $date, $animalData[$company->getId()]);
            } else {
                $invoiceSet = $this->SetupInvoicesForCompanyLocation($company, $batchRules, $date);
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
     * @param Company $company
     * @param ArrayCollection $batchRules
     * @param \DateTime $date
     * @param array|null $data
     * @return array
     */
    private function SetupInvoicesForCompanyLocation(Company $company, ArrayCollection $batchRules, \DateTime $date, array $data = null) {
        $invoiceSet = array();

        $details = $this->getManager()->getRepository(InvoiceSenderDetails::class)->findBy(array(), array("id" => "DESC"),1);
        $details = $details[0];

        /** @var Location $location */
        foreach ($company->getLocations() as $location) {
            $invoice = new Invoice();
            $invoice->setSenderDetails($details);
            $invoice->setIsBatch(true);
            $this->setAddressProperties($invoice, $company->getBillingAddress());
            if ($company->getTwinfieldCode()) {
                $invoice->setStatus("UNPAID");
                $invoice->setInvoiceDate(new \DateTime());
            } else {
                $invoice->setStatus(InvoiceStatus::NOT_SEND);
            }
            $invoice->setCompanyDebtorNumber($company->getDebtorNumber());
            $invoice->setCompanyLocalId($company->getCompanyId());
            $invoice->setCompanyName($company->getCompanyName());
            $invoice->setUbn($location->getUbn());
            $invoice->setCompany($company);
            $invoice->setCompanyVatNumber($company->getVatNumber());
            $invoice->setInvoiceNumber($this->invoiceNumber);
            if ($data && array_key_exists($location->getId(), $data)) {
                $dataSet = $data[$location->getId()];
                $this->addAnimalDataInvoiceRulesToInvoice($invoice, $company, $dataSet, $batchRules, $date);
            }
            if ($company->getAnimalHealthSubscription() != null && $company->getAnimalHealthSubscription()) {
                $this->addAnimalHealthSubscriptionInvoiceRule($invoice, $batchRules, $date);
            }
            $breakdown = $invoice->getVatBreakdownRecords();
            if ($breakdown->getTotalExclVat() != 0) {
                $invoiceSet[] = $invoice;
                $this->invoiceNumber++;
                $this->getManager()->persist($invoice);
            }
        }
        return $invoiceSet;
    }

    /**
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

    private function createInvoiceNumber() {
        $year = new \DateTime();
        $year = $year->format('Y');
        /** @var Invoice $previousInvoice */
        $previousInvoice = $this->getManager()->getRepository(Invoice::class)->getInvoiceOfCurrentYearWithLastInvoiceNumber($year);
        $number = $previousInvoice === null ?
            (int)$year * 10000 :
            (int)$previousInvoice->getInvoiceNumber() + 1
        ;
       $this->invoiceNumber = $number;
    }

    /**
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
            $abbreviationDate = $animalRegisterAbbreviation." - ".$date->format("d-m-Y");
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
     * @param \DateTime $controlDate
     * @return mixed
     */
    private function getAllAnimalsSortedByPedigreeRegisterAndLocationOnControlDate(\DateTime $controlDate) {
        $dateString = $controlDate->format('d-m-Y H:i:s');
        return $this->getManager()->getRepository(Animal::class)
            ->getAnimalCountsByCompanyLocationPedigreeRegisterOnControlDate($dateString);
    }
}