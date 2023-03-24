<?php

namespace App\Model;

use Nette,
    Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Nette\Utils\Json;
use Exception;
use Tracy\Debugger;

/**
 * EET management.
 */
class EETManager extends Base
{
    const COLUMN_ID = 'id';
    public $tableName = 'cl_eet';


    /**Insert new record to cl_eet table
     * @param $arrData data returned from EETService
     * @param $testMode - testmode = 1, normal mode = 0
     * @return mixed|Nette\Database\Table\ActiveRow
     */
    public function insertNewEET($arrData, $testMode)
    {
        $tmpNew = array();
        if (count($arrData) > 0 && !($arrData['FIK'] == "" && $arrData['BKP'] == "" && $arrData['PKP'] == ""))
        {

            //$tmpNew['cl_company_id']   = $tmpData['cl_company_id'];
            //$tmpNew['cl_users_id']     = $tmpData['cl_users_id'];

            $tmpNew['fik']             = $arrData['FIK'];
            $tmpNew['bkp']             = $arrData['BKP'];
            $tmpNew['pkp']             = $arrData['PKP'];
            $tmpNew['error']           = $arrData['Error'];
            $tmpNew['warnings']        = Json::encode($arrData['Warnings']);
            $tmpNew['eet_test']        = $testMode;
            $tmpNew['eet_id']          = $arrData['eet_id'];
            $tmpNew['eet_idpokl']      = $arrData['eet_idpokl'];
            if ($arrData['Error'] != "") {
                $tmpNew['eet_status'] = 1;
            }else {
                //Debugger::log('EET warnings ' . count($arrData). " : ". dump($arrData), 'eet');
                if (count($arrData['Warnings']) == 0) {
                    $tmpNew['eet_status'] = ($testMode == 1) ? 0 : 3;
                } else {
                    $tmpNew['eet_status'] = 2;
                }
            }

            if (is_null($arrData['id'])) {
                $tmpNew['dat_eet']  = $arrData['dat_trzby'];
                $tmpRow             = $this->insert($tmpNew);
                $tmpRowId           = $tmpRow->id;
            }else{
                $tmpNew['id']           = $arrData['id'];
                $tmpNew['first_send']   = 0;
                $this->update($tmpNew);
                $tmpRowId               = $tmpNew['id'];
            }
            return $tmpRowId;
        }else {
            $tmpNew['error']           = $arrData['Error'];
            $tmpNew['warnings']        = Json::encode($arrData['Warnings']);
            if (is_null($arrData['id'])) {
                $tmpRow             = $this->insert($tmpNew);
                $tmpRowId           = $tmpRow->id;
            }else{
                $tmpNew['id']           = $arrData['id'];
                $this->update($tmpNew);
                $tmpRowId               = $tmpNew['id'];
            }
            return $tmpRowId;
        }


    }
}

