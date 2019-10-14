<?php


namespace AppBundle\Component\Option;

use AppBundle\Util\SqlUtil;
use JMS\Serializer\Annotation as JMS;

class CompanyRegisterReportOptions
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
     * @var \DateTime
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "BASIC"
     * })
     */
    private $sampleDate;

    /**
     * @return string
     */
    public function getFileType(): string
    {
        return $this->fileType;
    }

    /**
     * @param string $fileType
     * @return CompanyRegisterReportOptions
     */
    public function setFileType(string $fileType): CompanyRegisterReportOptions
    {
        $this->fileType = $fileType;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getSampleDate(): \DateTime
    {
        return $this->sampleDate;
    }

    public function getSampleDateString(): string {
        return $this->sampleDate->format(SqlUtil::DATE_FORMAT);
    }

    /**
     * @param \DateTime|null $sampleDate
     * @return CompanyRegisterReportOptions
     */
    public function setSampleDate(?\DateTime $sampleDate): CompanyRegisterReportOptions
    {
        $this->sampleDate = $sampleDate;
        return $this;
    }
}
