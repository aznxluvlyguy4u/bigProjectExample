<?php


namespace AppBundle\model\metadata;


class YearMonthData
{
    /** @var int */
    private $year;

    /** @var int */
    private $month;

    /** @var int|null */
    private $count;

    /**
     * YearMonthData constructor.
     * @param  int  $year
     * @param  int  $month
     * @param  int|null  $count
     */
    public function __construct(int $year, int $month, ?int $count)
    {
        $this->year = $year;
        $this->month = $month;
        $this->count = $count;
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
     * @return YearMonthData
     */
    public function setYear(int $year): YearMonthData
    {
        $this->year = $year;
        return $this;
    }

    /**
     * @return int
     */
    public function getMonth(): int
    {
        return $this->month;
    }

    /**
     * @param  int  $month
     * @return YearMonthData
     */
    public function setMonth(int $month): YearMonthData
    {
        $this->month = $month;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getCount(): ?int
    {
        return $this->count;
    }

    /**
     * @param  int|null  $count
     * @return YearMonthData
     */
    public function setCount(?int $count): YearMonthData
    {
        $this->count = $count;
        return $this;
    }


}
