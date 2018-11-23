<?php


namespace AppBundle\Exception;


use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\Translation\TranslatorInterface;

class InvalidStnHttpException extends PreconditionFailedHttpException
{
    /**
     * @param TranslatorInterface $translator
     * @param string|null $stn
     * @param \Exception|null $previous
     * @param int $code
     */
    public function __construct(TranslatorInterface $translator,
                                ?string $stn,
                                \Exception $previous = null, int $code = 0)
    {
        parent::__construct(
            $this->getMessageFromStn($translator, $stn),
            $previous,
            $code
        );
    }

    /**
     * @param TranslatorInterface $translator
     * @param string|null $stn
     * @return string
     */
    private function getMessageFromStn(TranslatorInterface $translator, ?string $stn): string
    {
        return $translator->trans('THE STN HAS AN INVALID FORMAT'). '.'
            . (empty($stn) ? '' : ' '.$translator->trans('STN').': '.$stn);
    }
}