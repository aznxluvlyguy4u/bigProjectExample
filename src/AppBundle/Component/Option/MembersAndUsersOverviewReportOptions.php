<?php


namespace AppBundle\Component\Option;

use JMS\Serializer\Annotation as JMS;

class MembersAndUsersOverviewReportOptions
{
    /**
     * @var \DateTime
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "BASIC"
     * })
     */
    private $referenceDate;

    /**
     * @var string
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "BASIC"
     * })
     */
    private $language;

    /**
     * @var bool
     * @JMS\Type("boolean")
     * @JMS\Groups({
     *     "BASIC"
     * })
     */
    private $mustHaveAnimalHealthSubscription;

    /**
     * @var string|null
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "BASIC"
     * })
     */
    private $pedigreeRegisterAbbreviation;

    /**
     * @return \DateTime
     */
    public function getReferenceDate(): \DateTime
    {
        return $this->referenceDate;
    }

    /**
     * @param \DateTime $referenceDate
     * @return MembersAndUsersOverviewReportOptions
     */
    public function setReferenceDate(\DateTime $referenceDate): MembersAndUsersOverviewReportOptions
    {
        $this->referenceDate = $referenceDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @param string $language
     * @return MembersAndUsersOverviewReportOptions
     */
    public function setLanguage(string $language): MembersAndUsersOverviewReportOptions
    {
        $this->language = $language;
        return $this;
    }

    /**
     * @return bool
     */
    public function isMustHaveAnimalHealthSubscription(): bool
    {
        return $this->mustHaveAnimalHealthSubscription;
    }

    /**
     * @param bool $mustHaveAnimalHealthSubscription
     * @return MembersAndUsersOverviewReportOptions
     */
    public function setMustHaveAnimalHealthSubscription(bool $mustHaveAnimalHealthSubscription): MembersAndUsersOverviewReportOptions
    {
        $this->mustHaveAnimalHealthSubscription = $mustHaveAnimalHealthSubscription;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPedigreeRegisterAbbreviation(): ?string
    {
        return $this->pedigreeRegisterAbbreviation;
    }

    /**
     * @param string|null $pedigreeRegisterAbbreviation
     * @return MembersAndUsersOverviewReportOptions
     */
    public function setPedigreeRegisterAbbreviation(?string $pedigreeRegisterAbbreviation): MembersAndUsersOverviewReportOptions
    {
        $this->pedigreeRegisterAbbreviation = $pedigreeRegisterAbbreviation;
        return $this;
    }



}