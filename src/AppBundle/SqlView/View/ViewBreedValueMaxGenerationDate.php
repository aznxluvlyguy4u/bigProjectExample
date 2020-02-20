<?php


namespace AppBundle\SqlView\View;

use AppBundle\Util\SqlUtil;
use JMS\Serializer\Annotation as JMS;

class ViewBreedValueMaxGenerationDate implements SqlViewInterface
{
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $ddMmYyyy;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $date;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $dateTime;

    /**
     * @var integer
     * @JMS\Type("integer")
     */
    private $year;

    static function getPrimaryKeyName()
    {
        return 'year';
    }

    function getPrimaryKey()
    {
        return $this->getYear();
    }


    /**
     * @return string
     */
    public function getDdMmYyyy(): string
    {
        return $this->ddMmYyyy;
    }

    /**
     * @param  string  $ddMmYyyy
     * @return ViewBreedValueMaxGenerationDate
     */
    public function setDdMmYyyy(string $ddMmYyyy): ViewBreedValueMaxGenerationDate
    {
        $this->ddMmYyyy = $ddMmYyyy;
        return $this;
    }

    /**
     * @return string
     */
    public function getDate(): string
    {
        return $this->date;
    }

    /**
     * @param  string  $date
     * @return ViewBreedValueMaxGenerationDate
     */
    public function setDate(string $date): ViewBreedValueMaxGenerationDate
    {
        $this->date = $date;
        return $this;
    }

    /**
     * @return \DateTime|null
     * @throws \Exception
     */
    public function getDateTime(): ?\DateTime
    {
        return $this->dateTime ? new \DateTime($this->dateTime) : null;
    }

    /**
     * @param  \DateTime $dateTime
     * @return ViewBreedValueMaxGenerationDate
     */
    public function setDateTime(\DateTime $dateTime): ViewBreedValueMaxGenerationDate
    {
        $this->dateTime = $dateTime ? $dateTime->format(SqlUtil::DATE_TIME_FORMAT) : null;
        return $this;
    }

    /**
     * @return int
     */
    public function getYear(): int
    {
        return $this->year;
    }

    /**
     * @param  int  $year
     * @return ViewBreedValueMaxGenerationDate
     */
    public function setYear(int $year): ViewBreedValueMaxGenerationDate
    {
        $this->year = $year;
        return $this;
    }



}
