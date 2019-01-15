<?php


namespace AppBundle\Exception;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\Translation\TranslatorInterface;

class ReportHasNoDataHttpException extends PreconditionFailedHttpException
{
    /**
     * @param TranslatorInterface $translator
     * @param \Exception|null $previous
     */
    public function __construct(TranslatorInterface $translator,
                                \Exception $previous = null)
    {
        parent::__construct(
            $this->getErrorMessage($translator),
            $previous,
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * @param TranslatorInterface $translator
     * @return string
     */
    private function getErrorMessage(TranslatorInterface $translator): string
    {
        return $translator->trans('REPORT HAS NO DATA'). '.';
    }
}