<?php


namespace AppBundle\Exception;


use AppBundle\Entity\Location;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\Translation\TranslatorInterface;

class MissingRelationNumberKeeperOfLocationHttpException extends PreconditionFailedHttpException
{
    /**
     * MissingRelationNumberKeeperOfLocationHttpException constructor.
     * @param TranslatorInterface $translator
     * @param Location $location
     * @param \Exception|null $previous
     * @param int $code
     */
    public function __construct(TranslatorInterface $translator,
                                Location $location,
                                \Exception $previous = null, int $code = 0)
    {
        parent::__construct(
            $this->getMessageFromLocation($translator, $location),
            $previous,
            $code
        );
    }

    /**
     * @param TranslatorInterface $translator
     * @param Location $location
     * @return string
     */
    private function getMessageFromLocation(TranslatorInterface $translator, Location $location): string
    {
        $companyName = $location->getCompanyName(true);
        $companyNameString = empty($companyName) ? '' : strtolower($translator->trans('COMPANY NAME')).': '.$companyName.', ';
        return $translator->trans('THE LOCATION HAS NO RELATION NUMBER KEEPER IN THE NSFO SYSTEM'). '. '
            . $companyNameString . $translator->trans('UBN').': '.$location->getUbn();
    }
}