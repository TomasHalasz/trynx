<?php

namespace XlsResponse;
/**
 * CSV download response.
 *
 * @author   Tomas Halasz
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
use PhpOffice\PhpSpreadsheet\Spreadsheet;


class NXlsResponse implements \Nette\Application\IResponse
{


    /** @var array */
    private $data;

    /** @var array */
    private $nameColumns;

    /** @var string */
    private $name;

    /** @var string */
    private $contentType;

    /** @var string */
    private $charset = 'UTF-8';



    /**
     * @param string  data (array of arrays - rows/columns)
     * @param string  imposed file name
     * @param bool    return array keys as the first row (column headings)
     * @param string  glue between columns (comma or a semi-colon)
     * @param string  MIME content type
     */
    public function __construct($data, $name = NULL, $contentType = NULL, $arrNameColumns = [])
    {
        // ----------------------------------------------------
        $this->data         = $data;
        $this->name         = $name;
        $this->contentType  = !is_null($contentType) ? $contentType : 'application/application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        $this->nameColumns  = $arrNameColumns;
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
    public function send(Nette\Http\IRequest $httpRequest, Nette\Http\IResponse $httpResponse): void
    {
        //dump('ted');
        $httpResponse->setContentType($this->contentType, $this->charset);
        if (empty($this->name)) {
            $httpResponse->setHeader('Content-Disposition', 'attachment');
        } else {
            $httpResponse->setHeader('Content-Disposition', 'attachment; filename="' . $this->name . '"');
        }
        //dump('ted1');
        $data = $this->createXls();

        $httpResponse->setHeader('Content-Length', strlen($data));
        print $data;
    }

    public function getXls()
    {
        $data = $this->createXls();
//        dump($data);
        return $data;
    }

    private function createXls(){
        if (empty($this->data)) {
            return '';
        }
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        //$sheet->setCellValue('A1', 'Hello World !');

        if (!is_array($this->data)) {
            $this->data = iterator_to_array($this->data);
        }
        $firstRow = reset($this->data);

        if (!is_array($firstRow)) {
            $firstRow = iterator_to_array($firstRow);
        }
        if (count($this->nameColumns) > 0) {
            $firstRow = $this->nameColumns;
        }

        //name columns
        $row = 1;
        $col = 'A';
        $col1 = '';
        foreach (array_keys($firstRow) as $key) {
            $label = ucfirst(str_replace("_", ' ', $key));
            $sheet->setCellValue($col1.$col . $row, $label);
            if ($col == 'Z'){
                $col1 = 'A';
                $col = 'A';
            }else{
                $col = chr(ord($col) + 1);
            }
        }

        $rowy = 2;
        foreach ($this->data as $row) {
            if (!is_array($row)) {
                $row = iterator_to_array($row);
            }
            $col = 'A';
            $col1 = '';
            foreach ($row as $key => $value) {
                $value = preg_replace('/[\r\n]+/', ' ', $value);  // remove line endings
                $value = str_replace('"', '""', $value);          // escape double quotes
                $sheet->setCellValue($col1.$col . $rowy, $value);
                if ($col == 'Z'){
                    $col1 = 'A';
                    $col = 'A';
                }else{
                    $col = chr(ord($col) + 1);
                }

            }
            $rowy += 1;
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
        //$writer->save('hello world.xls');
        ob_start();
        $writer->save('php://output');
        $excelOutput = ob_get_clean();
        //print $excelOutput;
        //dump($excelOutput);
        return $excelOutput;
    }

}
