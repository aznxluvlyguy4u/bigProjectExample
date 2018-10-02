<?php


namespace AppBundle\Exception;


use AppBundle\Entity\Location;
use AppBundle\Util\StringUtil;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\Translation\TranslatorInterface;

class DeclareToOtherCountryHttpException extends PreconditionFailedHttpException
{
    /**
     * DeclareToOtherCountryHttpException constructor.
     * @param TranslatorInterface $translator
     * @param string $declareClazz
     * @param Location $destination
     * @param Location $origin
     * @param \Exception|null $previous
     * @param int $code
     */
    public function __construct(TranslatorInterface $translator,
                                string $declareClazz,
                                Location $destination, Location $origin,
                                \Exception $previous = null, int $code = 0)
    {
        parent::__construct(
            $this->getMessageFromLocations($translator, $declareClazz, $destination, $origin),
            $previous,
            $code
        );
    }

    /**
     * @param TranslatorInterface $translator
     * @param string $declareClazz
     * @param Location $destination
     * @param Location $origin
     * @return string
     */
    private function getMessageFromLocations(TranslatorInterface $translator, string $declareClazz, Location $destination, Location $origin)
    {
        $declareTranslationKey = StringUtil::getDeclareTranslationKey($declareClazz, true);
        $declareText = ucfirst($translator->trans($declareTranslationKey));

        return $declareText . ' ' . $translator->trans('ARE ONLY ALLOWED BETWEEN UBNS FROM THE SAME COUNTRY')
        .'. '.$origin->getUbn(). '['.$origin->getCountryCode().']'
        .' => '.$destination->getUbn().' ['.$destination->getCountryCode().']';
    }
}