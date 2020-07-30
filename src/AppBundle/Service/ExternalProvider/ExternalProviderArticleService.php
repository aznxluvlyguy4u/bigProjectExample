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

        $headerData = ["SessionID" => $sessionID];

        $header = new SoapHeader('http://www.twinfield.com/','Header',
            $headerData);

        $finderClient->__setSoapHeaders($header);

        $finderParams = [
            "type" => "ART",
            "pattern" => '*',
            "field" => 0,
            "firstRow" => 1,
            "maxRows" => 9001
        ];

        $res = $finderClient->Search($finderParams);

        $array = json_decode(json_encode($res), true);

        $result = [];

        $office = new Office();

        $office ->setCode('TESTNSFO');
        $office->setName("NSFO testadm.");
        $office->setCountryCode("NL");

        $processXMLClient = new SoapClient("https://accounting2.twinfield.com/webservices/processxml.asmx?wsdl", ["trace" => 1]);

        $processXMLClient->__setSoapHeaders($header);

        foreach ($array["data"]["Items"]["ArrayOfString"] as $item) {
            $request_article = new Article();
            $request_article
                ->setOffice($office->getCode())
                ->setCode($item["string"][0]);

            $processXMLParams = [
                "xmlRequest" => $request_article->saveXML()
            ];

            $array = json_decode(json_encode($processXMLClient->ProcessXmlString($processXMLParams)), true);

            $res = $this->xmlToArray($array["ProcessXmlStringResult"]);
            if (key_exists("header", $res)) {
                $result[] = $res["header"];
            }
//            else {
//                dump($res);
//            }
        }

        return $result;
    }

    private function xmlToArray($xml)
    {
        return json_decode(json_encode(simplexml_load_string($xml)), true);
    }
}