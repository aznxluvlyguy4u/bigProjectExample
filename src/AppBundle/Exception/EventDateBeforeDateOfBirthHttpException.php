<?php


namespace AppBundle\Exception;


use AppBundle\Entity\DeclareLogInterface;
use AppBundle\Util\TimeUtil;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\Translation\TranslatorInterface;

class EventDateBeforeDateOfBirthHttpException extends PreconditionFailedHttpException
{
    /**
     * DeadAnimalHttpException constructor.
     * @param TranslatorInterface $translator
     * @param \DateTime|null $dateOfBirth
     * @param \DateTime|null $eventDate
     * @param \Exception|null $previous
     * @param int $code
     */
    public function __construct(TranslatorInterface $translator,
                                ?\DateTime $dateOfBirth,
                                $eventDate,
                                \Exception $previous = null, int $code = 0)
    {
        parent::__construct(
            $this->getMessageDates($translator, $dateOfBirth, $eventDate),
            $previous,
            $code
        );
    }

    /**
     * @param TranslatorInterface $translator
     * @param \DateTime|null $dateOfBirth
     * @param \DateTime|null $eventDate
     * @return string
     */
    private function getMessageDates(TranslatorInterface $translator,
                                     ?\DateTime $dateOfBirth,
                                     $eventDate): string
    {
        $dateFormat = DeclareLogInterface::EVENT_DATE_FORMAT;

        $dateStrings = '';
        if ($eventDate instanceof \DateTime && !empty($eventDate) &&
            DeclareLogInterface::EVENT_DATE_NULL_RESPONSE !== $eventDate &&
            TimeUtil::isDate1BeforeDate2($eventDate, $dateOfBirth)) {
            $dateStrings = ' '
                . strtolower($translator->trans('EVENT DATE')).': '.$eventDate->format($dateFormat) .', '
                . strtolower($translator->trans('DATE_OF_BIRTH')).': '.$dateOfBirth->format($dateFormat)
            ;
        }

        return $translator->trans('THE EVENT DATE CANNOT BE BEFORE THE DATE OF BIRTH'). '.'
            . $dateStrings;
    }
}