<?php


namespace AppBundle\Exception;


use AppBundle\Constant\JsonInputConstant;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\Translation\TranslatorInterface;

class AnimalNotOnLocationHttpException extends PreconditionFailedHttpException
{
    /**
     * InvalidUlnHttpException constructor.
     * @param TranslatorInterface $translator
     * @param null|string $ubn
     * @param null|string $ulnOrStnKey
     * @param null|string $ulnOrStnValue
     * @param \Exception|null $previous
     * @param int $code
     */
    public function __construct(TranslatorInterface $translator,
                                ?string $ubn,
                                ?string $ulnOrStnKey,
                                ?string $ulnOrStnValue,
                                \Exception $previous = null, int $code = 0)
    {
        parent::__construct(
            $this->getMessageFromKeyAndValue($translator, $ubn, $ulnOrStnKey, $ulnOrStnValue),
            $previous,
            $code
        );
    }

    /**
     * @param TranslatorInterface $translator
     * @param null|string $ubn
     * @param null|string $ulnOrStnKey
     * @param null|string $value
     * @return string
     */
    private function getMessageFromKeyAndValue(TranslatorInterface $translator,
                                       ?string $ubn,
                                       ?string $ulnOrStnKey,
                                       ?string $value): string
    {
        $translationKey = $this->getKey($ulnOrStnKey);
        $displayIdentifier = !empty($translationKey) && !empty($value);
        $ubnData = (empty($ubn) ? '' : ' '.$translator->trans('UBN').': '.$ubn.'.');
        return $translator->trans('THE ANIMAL IS NOT ON THE GIVEN LOCATION'). '.'
            . $ubnData
            . ($displayIdentifier ? ' '.$translator->trans($translationKey).': '.$value.'.' : '');
    }


    /**
     * @param $ulnOrStnKey
     * @return null|string
     */
    private function getKey($ulnOrStnKey): ?string
    {
        if (!is_string($ulnOrStnKey) || empty($ulnOrStnKey)) {
            return null;
        }

        $ulnOrStnKey = strtoupper($ulnOrStnKey);

        if ($ulnOrStnKey === strtoupper(JsonInputConstant::ULN) ||
            $ulnOrStnKey === strtoupper(JsonInputConstant::STN)) {
            return $ulnOrStnKey;
        }

        return null;
    }
}