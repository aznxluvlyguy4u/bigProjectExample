<?php


namespace AppBundle\Exception;


use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\Translation\TranslatorInterface;

class InvalidBreedCodeHttpException extends PreconditionFailedHttpException
{
    /**
     * @param TranslatorInterface $translator
     * @param string|null $breedCode
     * @param \Exception|null $previous
     * @param int $code
     */
    public function __construct(TranslatorInterface $translator,
                                ?string $breedCode,
                                \Exception $previous = null, int $code = 0)
    {
        parent::__construct(
            $this->getMessageFromUln($translator, $breedCode),
            $previous,
            $code
        );
    }

    /**
     * @param TranslatorInterface $translator
     * @param string|null $breedCode
     * @return string
     */
    private function getMessageFromUln(TranslatorInterface $translator, ?string $breedCode): string
    {
        return $translator->trans('THE BREED CODE HAS AN INVALID FORMAT'). '.'
            . (empty($breedCode) ? '' : ' '.$translator->trans('BREED_CODE').': '.$breedCode);
    }
}