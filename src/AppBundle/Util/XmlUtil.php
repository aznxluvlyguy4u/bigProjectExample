<?php


namespace AppBundle\Util;


use Symfony\Component\Serializer\Encoder\XmlEncoder;

class XmlUtil
{
    const NIL_KEY = '@xsi:nil';
    const FORMAT_OUTPUT_OPTION = true;
    const SOAPENV_ENVELOPE = 'soapenv:Envelope';


    public static function parseRvoXmlRequestBody(array $body, array $soapEnvelopeDetails = [])
    {
        $encoder = new XmlEncoder();

        $xml = $encoder->encode([
            'soapenv:Header' => [],
            'soapenv:Body' => $body,
        ], 'xml', self::rvoXmlOptions());

        return self::formatSoapEnvelope($xml, $soapEnvelopeDetails);
    }


    public static function rvoXmlOptions(): array
    {
        return [
            'xml_version' => '1.0',
            'xml_encoding' => 'utf-8',
            'xml_format_output' => self::FORMAT_OUTPUT_OPTION,
            'xml_root_node_name' => self::SOAPENV_ENVELOPE,
            'remove_empty_tags' => true
        ];
    }


    private static function formatSoapEnvelope(string $xml, array $soapEnvelopeDetails = []): string
    {
        $headerNewLine = self::FORMAT_OUTPUT_OPTION ? "\n  " : ' ';

        array_unshift(
            $soapEnvelopeDetails,
            'xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"'
        );

        $soapEnvelopePrefix = "<".self::SOAPENV_ENVELOPE;
        $envelopeReplacementString = implode($headerNewLine,$soapEnvelopeDetails);

        return str_replace(
            $soapEnvelopePrefix,
            $soapEnvelopePrefix.' '.$envelopeReplacementString,
            $xml
        );
    }


    public static function cleanXmlFormatting(array &$xmlArray)
    {
        foreach ($xmlArray as $key => $value) {
            $isNullValue = false;
            if (is_array($value)) {
                if (self::isNullValue($value)) {
                    $isNullValue = true;
                } else {
                    self::cleanXmlFormatting($value);
                }
            }

            if ($isNullValue) {
                unset($xmlArray[$key]);
            } else {
                $reformattedKey = self::reformatXmlKey($key);
                unset($xmlArray[$key]);
                $xmlArray[$reformattedKey] = $value;
            }
        }
    }


    private static function isNullValue(array $value): bool
    {
        $nilValue = $value[self::NIL_KEY] ?? null;
        return $nilValue === 'true' || $nilValue === true;
    }


    private static function reformatXmlKey(string $key): string
    {
        $keyWithoutPrefixBeforeNeedle = strstr($key, ':');
        $firstChar = substr($keyWithoutPrefixBeforeNeedle,0,1);
        return $firstChar === ':' ? substr($keyWithoutPrefixBeforeNeedle, 1) : $keyWithoutPrefixBeforeNeedle;
    }
}