<?php


namespace AppBundle\Enumerator;


class InvoiceRuleType
{
    const STANDARD = 'standard';
    const CUSTOM = 'custom';
    const SUBSCRIPTION_NSFO_ONLINE = "SubscriptionNSFOOnline";
    const SUBSCRIPTION_ANIMAL_HEALTH = "SubscriptionNSFOAnimalHealth";
    const BASE_ADMINISTRATION = "BaseAnimalAdministration";
    const ADMINISTRATION_ONLINE_EWE = "AdministrationOnlineEwe";
    const ADMINISTRATION_OFFLINE_EWE = "AdministrationOfflineEwe";
}