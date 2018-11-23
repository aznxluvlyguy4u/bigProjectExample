<?php


namespace AppBundle\Exception;


use AppBundle\Enumerator\RequestStateType;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\Translation\TranslatorInterface;

class DuplicateDeclareHttpException extends PreconditionFailedHttpException
{
    /**
     * DeadAnimalHttpException constructor.
     * @param TranslatorInterface $translator
     * @param string|null $clazz
     * @param bool $hasOpen
     * @param \Exception|null $previous
     * @param int $code
     */
    public function __construct(TranslatorInterface $translator,
                                ?string $clazz,
                                bool $hasOpen,
                                \Exception $previous = null, int $code = 0)
    {
        parent::__construct(
            $this->getMessageFromInput($translator, $clazz, $hasOpen),
            $previous,
            $code
        );
    }

    /**
     * @param TranslatorInterface $translator
     * @param string|null $clazz
     * @param bool $hasOpen
     * @return string
     */
    private function getMessageFromInput(TranslatorInterface $translator,
                                         ?string $clazz,
                                         bool $hasOpen): string
    {

        $requestState = $hasOpen ? RequestStateType::OPEN : RequestStateType::FINISHED;

        return $translator->trans('AN IDENTICAL %requestState% %declare% ALREADY EXISTS',
                [
                    '%requestState%' => $translator->trans($requestState),
                    '%declare%' => $this->getDeclareName($translator, $clazz),
                ]). '.';
    }


    /**
     * @param TranslatorInterface $translator
     * @param null|string $clazz
     * @return string
     */
    private function getDeclareName(TranslatorInterface $translator, ?string $clazz): string
    {
        if (!is_string($clazz)) {
            return $translator->trans('DECLARE');
        }
        return $translator->trans($clazz);
    }
}