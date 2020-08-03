<?php


namespace AppBundle\Service\ExternalProvider;


use PhpTwinfield\ApiConnectors\ArticleApiConnector;
use PhpTwinfield\Office;
use PhpTwinfield\Request\Read\Article;
use SoapClient;
use SoapFault;
use SoapHeader;

class ExternalProviderArticleService extends ExternalProviderBase implements ExternalProviderInterface
{

    /** @var ArticleApiConnector $officeConnection */
    private $articleConnection;

    /** @var string */
    private $office_code;

    public function setOfficeCode($office_code)
    {
        $this->office_code = $office_code;
    }

    /**
     * @required
     */
    public function reAuthenticate() {
        $this->getAuthenticator()->refreshConnection();
        $this->articleConnection = new ArticleApiConnector ($this->getAuthenticator()->getConnection());
    }

    /**
     * @throws SoapFault
     */
    public function getAllArticles()
    {
        $soapClient = new SoapClient("https://login.twinfield.com/webservices/session.asmx?wsdl", ["trace" => 1]);

        $params = [
            'user' => 'Test_NSFO',
            'password' => 'NSFO2020',
            'organisation' => 'weariv'
        ];

        $soapClient->Logon($params);
        $p = xml_parser_create();
        xml_parse_into_struct($p, $soapClient->__getLastResponse(), $vals, $index);

        $sessionID = $vals[3]['value'];

        $finderClient = new SoapClient('https://accounting2.twinfield.com/webservices/finder.asmx?wsdl', ["trace" => 1]);

        $headerData = ["SessionID" => $sessionID, "CompanyCode" => $this->office_code];

        $header = new SoapHeader('http://www.twinfield.com/','Header',
            $headerData);

        $finderClient->__setSoapHeaders($header);

        $finderParams = [
            "type" => "ART",
            "pattern" => '*',
            "field" => 1,
            "firstRow" => 1,
            "maxRows" => 10000000
        ];

        $res = $finderClient->Search($finderParams);

        $array = json_decode(json_encode($res), true);

        $processedResults = [];
        $results = [];

        $processXMLClient = new SoapClient("https://accounting2.twinfield.com/webservices/processxml.asmx?wsdl", ["trace" => 1]);

        $processXMLClient->__setSoapHeaders($header);

        foreach ($array["data"]["Items"]["ArrayOfString"] as $item) {
            $request_article = new Article();
            $request_article
                ->setOffice($this->office_code)
                ->setCode($item["string"][0]);

            $processXMLParams = [
                "xmlRequest" => $request_article->saveXML()
            ];

            $array = json_decode(json_encode($processXMLClient->ProcessXmlString($processXMLParams)), true);

            $processedResults[] = $this->xmlToArray($array["ProcessXmlStringResult"]);
        }

        foreach ($processedResults as $processedResult) {
            foreach ($processedResult['lines'] as $line) {
                if (key_exists('unitspriceexcl', $line)) {
                    $sub_code = '';
                    if (key_exists('subcode', $line) && !is_array($line['subcode'])) {
                        $sub_code = $line['subcode'];
                    }
                    $results[] = [
                        "description" => $processedResult['header']['name'],
                        "article_code" => $processedResult['header']['code'],
                        'sub_article_code' => $sub_code,
                        "price_excl_vat" => (float) $line['unitspriceexcl'],
                        "type" => $processedResult['header']['type']
                    ];
                }

                foreach ($line as $subLine) {
                    if(is_array($subLine) && key_exists('unitspriceexcl', $subLine)) {
                        $sub_code = '';
                        if (key_exists('subcode', $subLine) && !is_array($subLine['subcode'])) {
                            $sub_code = $subLine['subcode'];
                        }
                        $results[] = [
                            "description" => $subLine['name'],
                            "article_code" => $processedResult['header']['code'],
                            'sub_article_code' => $sub_code,
                            "price_excl_vat" => (float) $subLine['unitspriceexcl'],
                            "type" => $processedResult['header']['type']
                        ];
                    }
                }
            }
        }

        return $results;
    }

    private function xmlToArray($xml)
    {
        return json_decode(json_encode(simplexml_load_string($xml)), true);
    }
}