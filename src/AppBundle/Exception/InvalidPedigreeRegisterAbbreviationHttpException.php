<?php


namespace AppBundle\Exception;


use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\Translation\TranslatorInterface;

class InvalidPedigreeRegisterAbbreviationHttpException extends PreconditionFailedHttpException
{
    /**
     * @param TranslatorInterface $translator
     * @param string|null $pedigreeRegisterAbbreviation
     * @param \Exception|null $previous
     * @param int $code
     */
    public function __construct(TranslatorInterface $translator,
                                ?string $pedigreeRegisterAbbreviation,
                                \Exception $previous = null, int $code = 0)
    {
        parent::__construct(
            $this->getMessageFromUln($translator, $pedigreeRegisterAbbreviation),
            $previous,
            $code
        );
    }

    /**
     * @param TranslatorInterface $translator
     * @param string|null $abbreviation
     * @return string
     */
    private function getMessageFromUln(TranslatorInterface $translator, ?string $abbreviation): string
    {
        return $translator->trans('THE PEDIGREE REGISTER ABBREVIATION HAS AN INVALID FORMAT'). '.'
            . (empty($abbreviation) ? '' : ' '.$translator->trans('PEDIGREE_REGISTER').': '.$abbreviation);
    }
}