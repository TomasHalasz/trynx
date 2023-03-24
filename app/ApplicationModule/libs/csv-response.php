<?php

namespace CsvResponse;
/**
 * CSV download response.
 *
 * @author     Petr 'PePa' Pavel
 *
 * @property-read array  $data
 * @property-read string $name
 * @property-read bool   $addHeading
 * @property-read string $glue
 * @property-read string $contentType
 * @package Nette\Application\Responses
 */
 
//class NCsvResponse extends Nette\Object  implements  Nette\Application\IResponse {
/*use Nette\Object,
    Nette\Application\IPresenterResponse,
    Nette\Callback,
    Nette\Environment,
    Nette\String;
*/
  //class NCsvResponse extends NObject implements IPresenterResponse {
/*  class NCsvResponse extends Object implements Nette\Application\IPresenterResponse {*/

use Nette;
use Nette\Utils\Strings;
//use Nette\Object;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Utils\Callback;

//use Nette\Object, Nette\Application\IResponse, Nette\Callback, Nette\Environment, Nette\Utils\Strings, Nette\Templating\Itemplate;

class NCsvResponse implements \Nette\Application\IResponse
{


    /** @var array */
    private $data;

    /** @var string */
    private $name;

    /** @var array */
    private $nameColumns;

    /** @var bool */
    public $addHeading;

    /** @var bool */
    public $formatFirstRow;

    /** @var string */
    public $glue;

    /** @var string */
    private $charset;

    /** @var string */
    private $separator;

    /** @var string */
    private $contentType;

    /**
     * @param string  data (array of arrays - rows/columns)
     * @param string  imposed file name
     * @param bool    return array keys as the first row (column headings)
     * @param string  glue between columns (comma or a semi-colon)
     * @param string  MIME content type
     */
    public function __construct($data, $name = NULL, $addHeading = TRUE, $glue = ';', $charset = NULL, $contentType = NULL, $arrNameColumns = [], $formatFirstRow = TRUE, $separator = '"')
    {
        // ----------------------------------------------------
        $this->data = $data;
        $this->name = $name;
        $this->addHeading = $addHeading;
        $this->glue = $glue;
        $this->charset = $charset;
//     $this->charset = $charset ? $charset : 'UTF-8';
        $this->contentType = $contentType ? $contentType : 'text/csv';
        $this->nameColumns  = $arrNameColumns;
        $this->formatFirstRow = $formatFirstRow;
        $this->separator = $separator;
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
        $httpResponse->setContentType($this->contentType, $this->charset);

        if (empty($this->name)) {
            $httpResponse->setHeader('Content-Disposition', 'attachment');
        } else {
            $httpResponse->setHeader('Content-Disposition', 'attachment; filename="' . $this->name . '"');
        }

        $data = $this->formatCsv();

        $httpResponse->setHeader('Content-Length', strlen($data));
        print $data;
    }

    public function getCsv()
    {
        $data = $this->formatCsv();
        return $data;
    }
  
  
  
  public function formatCsv() {
    // ----------------------------------------------------
    if (empty($this->data)) {
      return '';
    }
  
    $csv = array();
    
    if (!is_array($this->data)) {
      $this->data = iterator_to_array($this->data);
    }
    $firstRow = reset($this->data);
  
    if ($this->addHeading) {
      if (!is_array($firstRow)) {
        $firstRow = iterator_to_array($firstRow);
      }

        if (count($this->nameColumns) > 0) {
            $firstRow = $this->nameColumns;
        }

      $labels = array();
      foreach (array_keys($firstRow) as $key) {
        //$labels[] = ucwords(str_replace("_", ' ', $key));
          if ($this->formatFirstRow)
            $labels[] = ucfirst(str_replace("_", ' ', $key));
          else
            $labels[] = $key;
      } 
      $csv[] = $this->separator . join($this->separator . $this->glue . $this->separator, $labels) . $this->separator;
    }
  
    foreach ($this->data as $row) {
      if (!is_array($row)) {
        $row = iterator_to_array($row);
      }
      foreach ($row as $key => $value) {
        $value = preg_replace('/[\r\n]+/', ' ', $value);  // remove line endings
        $value = str_replace('"', '""', $value);          // escape double quotes
        $value = preg_replace('#<br\s*/?>#i', "\n ", $value);
        $value = strip_tags($value);
      }
      $csv[] = $this->separator . join($this->separator . $this->glue . $this->separator , $row) . $this->separator;
    }
  
    return join(PHP_EOL, $csv);
  }
}
