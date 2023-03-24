<?php

namespace XMLResponse;
/**
 * XML download response.
 *
 * @author     Tom Halasz
 * @author     Petr 'PePa' Pavel
 *
 * @property-read array  $data
 * @property-read string $name
 * @property-read string $contentType
 * @package Nette\Application\Responses
 */
 

use Nette;
use Nette\Utils\Strings;
//use Nette\Object;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Utils\Callback;


class XMLResponse implements \Nette\Application\IResponse
{


    /** @var array */
    private $data;

    /** @var string */
    private $name;

    /** @var string */
    private $contentType;
	
	/** @var string */
	private $mainTag;

    /**
     * @param string  data (array of arrays - rows/columns)
     * @param string  imposed file name
     * @param string  MIME content type
	 * @param string  main tag name
     */
    public function __construct($data, $name = NULL, $contentType = NULL, $mainTag = "data")
    {
        // ----------------------------------------------------
        $this->data = $data;
        $this->name = $name;
        $this->mainTag = $mainTag;
        $this->contentType = $contentType ? $contentType : 'text/xml';
    }


    /**
     * Returns the file name.
     * @return string
     */
    final public function getName()
    {
        // ----------------------------------------------------
        return $this->name;
    }


    /**
     * Returns the MIME content type of a downloaded content.
     * @return string
     */
    final public function getContentType()
    {
        // ----------------------------------------------------
        return $this->contentType;
    }




    /**
     * Sends response to output.
     * @return void
     */
    //public function send(IHttpRequest $httpRequest, IHttpResponse $httpResponse) {
    //public function send(IRequest $httpRequest, IResponse $httpResponse){
    public function send(Nette\Http\IRequest $httpRequest, Nette\Http\IResponse $httpResponse): void
    {
        $httpResponse->setContentType($this->contentType);

        if (empty($this->name)) {
            $httpResponse->setHeader('Content-Disposition', 'attachment');
        } else {
            $httpResponse->setHeader('Content-Disposition', 'attachment; filename="' . $this->name . '"');
        }

        $data = $this->formatXml($this->mainTag);

        $httpResponse->setHeader('Content-Length', strlen($data));
        print $data;
    }

    public function getXml()
    {
        $data = $this->formatXml();
        return $data;
    }



    private function formatXml($mainTag)
    {
        $dataReport = $this->data;
        //creating object of SimpleXMLElement
        $xml_invoice = new \SimpleXMLElement("<?xml version=\"1.0\"?><$mainTag></$mainTag>");

        //function call to convert array to xml
        $this->array_to_xml($dataReport,$xml_invoice );

        //saving generated xml file
        return $xml_invoice->asXML();


    }


    //function defination to convert array to xml
    private function array_to_xml($array, &$xml_invoice ) {
        foreach($array as $key => $value) {
            // dump($key);
            // dump($value);
            if (is_object($value) || is_array($value)) {
                // dump(is_numeric($key2));
                if (!is_numeric($key)) {
                    $subnode = $xml_invoice ->addChild("$key");
                    $this->array_to_xml($value, $subnode);
                } else {
                    $subnode = $xml_invoice ->addChild("item$key");
                    $this->array_to_xml($value, $subnode);
                }
            } else {
                $xml_invoice ->addChild("$key", htmlspecialchars("$value"));
            }

        }
        //die;
    }

}
