<?php


namespace AppBundle\Exception;


use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Translation\TranslatorInterface;

class FeatureNotAvailableHttpException extends AccessDeniedHttpException
{
    /**
     * DeclareToOtherCountryHttpException constructor.
     * @param TranslatorInterface $translator
     * @param string|null $featureName
     * @param \Exception|null $previous
     * @param int $code
     */
    public function __construct(TranslatorInterface $translator, ?string $featureName = null,
                                \Exception $previous = null, int $code = 0)
    {
        parent::__construct(
            $this->getMessageFeatureNotAvailable($translator, $featureName),
            $previous,
            $code
        );
    }

    /**
     * @param TranslatorInterface $translator
     * @param string|null $featureName
     * @return string
     */
    private function getMessageFeatureNotAvailable(TranslatorInterface $translator, ?string $featureName): string
    {
        return $translator->trans('THIS FEATURE IS NOT AVAILABLE YET')
            .(!empty($featureName) ? ': ' . $translator->trans($featureName) : '.');
    }
}