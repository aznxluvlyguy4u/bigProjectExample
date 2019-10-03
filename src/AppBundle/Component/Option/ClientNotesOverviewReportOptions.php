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
}
