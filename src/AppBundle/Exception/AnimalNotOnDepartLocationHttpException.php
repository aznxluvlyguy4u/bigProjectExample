<?php


namespace AppBundle\Exception;


use AppBundle\Entity\Animal;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\Translation\TranslatorInterface;

class AnimalNotOnDepartLocationHttpException extends PreconditionFailedHttpException
{
    /**
     * AnimalNotOnDepartLocationHttpException constructor.
     * @param TranslatorInterface $translator
     * @param Animal|null $animal
     * @param \Exception|null $previous
     * @param int $code
     */
    public function __construct(TranslatorInterface $translator,
                                ?Animal $animal,
                                \Exception $previous = null, int $code = 0)
    {
        parent::__construct(
            $this->getMessageFromAnimal($translator, $animal),
            $previous,
            $code
        );
    }

    /**
     * @param TranslatorInterface $translator
     * @param Animal|null $animal
     * @return string
     */
    private function getMessageFromAnimal(TranslatorInterface $translator, ?Animal $animal): string
    {
        $ulnData = '';
        $currentUbnData = '';

        $separator = ' ';
        $separatorConstant = ', ';

        if ($animal) {
            if (!empty($animal->getUln())) {
                $ulnData = $separator.$translator->trans('ULN').': '.$animal->getUln();
                $separator = $separatorConstant;
            }

            $currentAnimalLocation = $animal->getLocation();
            if ($currentAnimalLocation) {
                $currentCountryCode = $currentAnimalLocation->getCountryCode();
                $currentUbnData = $separator.$translator->trans('CURRENT UBN').': ' . $currentAnimalLocation->getUbn(). ' ('. $currentCountryCode .')';
                $separator = $separatorConstant;
            }
        }

        return $translator->trans('ANIMAL IS NOT ON LOCATION OF DEPART'). '.'
        . $ulnData . $currentUbnData;
    }
}