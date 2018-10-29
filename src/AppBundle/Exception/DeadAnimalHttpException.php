<?php


namespace AppBundle\Exception;


use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\Translation\TranslatorInterface;

class DeadAnimalHttpException extends PreconditionFailedHttpException
{
    /**
     * DeadAnimalHttpException constructor.
     * @param TranslatorInterface $translator
     * @param string|null $uln
     * @param \Exception|null $previous
     * @param int $code
     */
    public function __construct(TranslatorInterface $translator,
                                ?string $uln,
                                \Exception $previous = null, int $code = 0)
    {
        parent::__construct(
            $this->getMessageFromUln($translator, $uln),
            $previous,
            $code
        );
    }

    /**
     * @param TranslatorInterface $translator
     * @param string|null $uln
     * @return string
     */
    private function getMessageFromUln(TranslatorInterface $translator, ?string $uln): string
    {
        return $translator->trans('ANIMAL IS ALREADY DEAD'). '.' . (empty($uln) ? '' : ' ULN: '.$uln);
    }
}