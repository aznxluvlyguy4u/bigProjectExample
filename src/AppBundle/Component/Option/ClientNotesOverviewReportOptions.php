<?php


namespace AppBundle\Component\Option;

use AppBundle\Util\SqlUtil;
use JMS\Serializer\Annotation as JMS;

class ClientNotesOverviewReportOptions
{
    /**
     * @var string
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "BASIC"
     * })
     */
    private $fileType;

    /**
     * @var string
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "BASIC"
     * })
     */
    private $companyId;

    /**
     * @var \DateTime
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "BASIC"
     * })
     */
    private $startDate;

    /**
     * @var \DateTime
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "BASIC"
     * })
     */
    private $endDate;

    /**
     * @return string
     */
    public function getFileType(): string
    {
        return $this->fileType;
    }

    /**
     * @param string $fileType
     * @return ClientNotesOverviewReportOptions
     */
    public function setFileType(string $fileType): ClientNotesOverviewReportOptions
    {
        $this->fileType = $fileType;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCompanyId(): string
    {
        return $this->companyId;
    }

    /**
     * @param string|null $companyId
     * @return ClientNotesOverviewReportOptions
     */
    public function setCompanyId(string $companyId): ClientNotesOverviewReportOptions
    {
        $this->companyId = $companyId;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }

    /**
     * @param \DateTime $startDate
     * @return ClientNotesOverviewReportOptions
     */
    public function setStartDate(\DateTime $startDate): ClientNotesOverviewReportOptions
    {
        $this->startDate = $startDate;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getEndDate(): \DateTime
    {
        return $this->endDate;
    }

    /**
     * @param \DateTime $endDate
     * @return ClientNotesOverviewReportOptions
     */
    public function setEndDate(\DateTime $endDate): ClientNotesOverviewReportOptions
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getStartDateString(): string {
        return $this->startDate->format(SqlUtil::DATE_FORMAT);
    }

    public function getEndDateString(): string {
        return $this->endDate->format(SqlUtil::DATE_FORMAT);
    }

    public static function getCompanyIdEmptyValue(): string {
        return "";
    }
}
