<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Number series management.
 */
class NumberSeriesManager extends Base
{
	const COLUMN_ID		= 'id';
	const COMMISSION_TABLE	= 'cl_commission';
	const COMMISSION_COLUMN	= 'cm_number';	
	const KDB_TABLE		= 'cl_kdb';		
	const KDB_COLUMN	= 'kdb_number';		
	const STORE_DOCS_TABLE	= 'cl_store_docs';	
	const STORE_DOCS_COLUMN	= 'doc_number';		
	const PRICELIST_TABLE	= 'cl_pricelist';
	const PRICELIST_COLUMN	= 'identification';		
	const ORDER_TABLE	= 'cl_order';
	const ORDER_COLUMN	= 'od_number';		
	const INVOICE_TABLE	= 'cl_invoice';
	const INVOICE_COLUMN	= 'inv_number';		
	const INVOICE_ARRIVED_TABLE	= 'cl_invoice_arrived';		
	const INVOICE_ARRIVED_COLUMN	= 'inv_number';			
	public $tableName	= 'cl_number_series';

    /** @var \App\Model\StatusManager */
    public $StatusManager;
    /** @var \App\Model\HeadersFootersManager */
    public $HeadersFootersManager;
    /** @var \App\Model\InvoiceTypesManager */
    public $InvoiceTypesManager;

    /** @var \App\Model\CompaniesManager */
    public $CompaniesManager;

	//oproti beznemu modelu tu bude metoda, ktera vraci primo cislo dokladu podle predaneho parametru


    /**
     * @param Nette\Database\Connection $db
     * @throws Nette\InvalidStateException
     */
    public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \Nette\Http\Session $session,
                                    StatusManager $StatusManager, HeadersFootersManager $HeadersFootersManager, InvoiceTypesManager $InvoiceTypesManager, \DatabaseAccessor $accessor,
                                    CompaniesManager $CompaniesManager)
    {
        parent::__construct($db, $userManager, $user, $session, $accessor);
        $this->StatusManager	        = $StatusManager;
        $this->HeadersFootersManager    = $HeadersFootersManager;
        $this->InvoiceTypesManager      = $InvoiceTypesManager;
        $this->CompaniesManager = $CompaniesManager;

    }

    /**
     * Vrací cislo dokladu a id ciselne rady pro vychozi ciselnou radu podle predaneho typu pouziti
     * @return array(number,id)
     */
    public function getNewNumber($formUse,$id = NULL,$lastUse = NULL,$master_cl_company_id = NULL) {
	    if ($master_cl_company_id == NULL)
	    {
            $settings = $this->CompaniesManager->getTable()->fetch();
            if ($settings){
                $company_id = $settings->id;
                    //$this->userManager->getCompany($this->user->getId());
            }

	    }
	    else 
	    {
			$company_id = $master_cl_company_id;
	    }
	   // bdump($formUse, 'formuse');
	   //bdump($id,'id');
       //die;
	    $arr = [];
	    $arr['id'] = NULL;
	    $arr['number'] = 0;
	    switch ($formUse){
		case 'invoice_arrived':
		    $dataTable = self::INVOICE_ARRIVED_TABLE;
		    $dataColumn = self::INVOICE_ARRIVED_COLUMN;
		    break;		
		case 'invoice':
		    $dataTable = self::INVOICE_TABLE;
		    $dataColumn = self::INVOICE_COLUMN;
		    break;
        case 'invoice_tax':
            $dataTable = self::INVOICE_TABLE;
            $dataColumn = self::INVOICE_COLUMN;
            break;
		case 'commission':
		    $dataTable = self::COMMISSION_TABLE;
		    $dataColumn = self::COMMISSION_COLUMN;		    
		    break;
		case 'store_in':
		    $dataTable = self::STORE_DOCS_TABLE;
		    $dataColumn = self::STORE_DOCS_COLUMN;		    
		    break;		
		case 'store_out':
		    $dataTable = self::STORE_DOCS_TABLE;
		    $dataColumn = self::STORE_DOCS_COLUMN;
		    break;				
		case 'pricelist':
		    $dataTable = self::PRICELIST_TABLE;
		    $dataColumn = self::PRICELIST_COLUMN;		    
		    break;
		case 'order':
		    $dataTable = self::ORDER_TABLE;
		    $dataColumn = self::ORDER_COLUMN;
		    break;		
		case 'kdb':
		    $dataTable = self::KDB_TABLE;
		    $dataColumn = self::KDB_COLUMN;
		    break;				
		break;
	    }
		    if ($id != NULL)
			{
				$data = $this->findAllTotal()->where($this->tableName.'.id = ? AND '.$this->tableName.'.cl_company_id = ?', $id, $company_id);
			}else{
				$data = $this->findAllTotal()->where($this->tableName.'.form_default = 1 AND '.$this->tableName.'.cl_company_id = ?', $company_id);
			}

		    if ($data = $data->where($this->tableName.'.form_use = ?',$formUse)->fetch())
		    {
			/*if ($lastUse != NULL)
			{
			    $count = $this->database->table($dataTable)
				    ->where($dataTable.'.cl_company_id = ? AND '.
					    $dataTable.'.cl_number_series_id = ? AND '.
					    $dataTable.'.id != ? ',$company_id,$data->id, $lastUse)->count(1);
			    
			    if ($lastUsed = $this->database->table($dataTable)
				    ->where($dataTable.'.cl_company_id = ? AND '.
					    $dataTable.'.cl_number_series_id = ? AND '.
					    $dataTable.'.id != ? ',$company_id,$data->id, $lastUse)
				    ->order('id DESC')->limit(1)->fetch())
				$lastNumber = $lastUsed[$dataColumn];
			    else
				$lastNumber = $count;
			    
			}else{
			    $max = $this->database->table($dataTable)
				    ->where($dataTable.'.cl_company_id = ? AND '.
					    $dataTable.'.cl_number_series_id = ?',$company_id,$data->id)->count(1);
			    if ($lastUsed = $this->database->table($dataTable)
				    ->where($dataTable.'.cl_company_id = ? AND '.
					    $dataTable.'.cl_number_series_id = ?',$company_id,$data->id)
				    ->order('id DESC')->limit(1)->fetch())
				$lastNumber = $lastUsed[$dataColumn];
			    else
				$lastNumber = $count;			    
			}*/

			if ($lastUse == NULL){
			    $number = $data->last_number + 1;
			}else{
			    $number = $data->last_number;
			}
			
			if ($number == 0){
			    $number = 1;
			}
			
			$arrFormula = str_getcsv($data->formula,'(',')');
			$strFormula = $data->formula;

			//dump($arrFormula);
            $tmpNow = new \DateTime();
            //01.01.2021 - reset Z number if there is new year and year is used in formula tzn. 2R 4R
            if (( !is_null($data['last_use']) && $data['last_use']->format('Y') != $tmpNow->format('Y')) && (strpos($strFormula, "(2R)") || strpos($strFormula, "(4R)"))){
                $number = 1;
            }

			foreach($arrFormula as $one)
			{
			    $numStart = strpos($strFormula,"(");
			    $numEnd = strpos($strFormula,")")+1;
			    $strNumber = "";
			    $formulaTest = substr($one,0,strpos($one,")"));
			    //bdump($formulaTest);
			    //bdump($number,"number");
			    //bdump($strNumber != '','test');
			    if ($formulaTest == 'Z+')
			    {
					$strNumber = $number;
					$data->update(array('id' => $data->id, 'last_number' => $number, 'last_use' => $tmpNow));
			    }
			    if (substr($formulaTest,0,1) == 'Z' && $formulaTest != 'Z+')
			    {
					$counter = substr($formulaTest,1) - strlen($number);
					if ($counter <= 0)
					    $counter = 0;
					$strNumber = str_repeat("0",$counter) . ($number);
					$data->update(array('id' => $data->id, 'last_number' => $number, 'last_use' => $tmpNow));
			    }			    
			    //4 digits year 
			    if (substr($one,0,strpos($one,")")) == '4R')
			    {
					$now = new Nette\Utils\DateTime;
					$strNumber = $now->format('Y');
			    }		
			    //2 digits year			    
			    if (substr($one,0,strpos($one,")")) == '2R')
			    {
					$now = new Nette\Utils\DateTime;
					$strNumber = substr($now->format('Y'),-2);
			    }		
			    //2 digits month
			    if (substr($one,0,strpos($one,")")) == '2M')
			    {
					$now = new Nette\Utils\DateTime;
					$strNumber = $now->format('m');
			    }			    			
			    // 2digits day
			    if (substr($one,0,strpos($one,")")) == '2D')
			    {
					$now = new Nette\Utils\DateTime;
					$strNumber = $now->format('d');
			    }				    
			    // 2digits hours
			    if (substr($one,0,strpos($one,")")) == '2TH')
			    {
					$now = new Nette\Utils\DateTime;
					$strNumber = $now->format('H');
			    }
			    // 2digits minuts
			    if (substr($one,0,strpos($one,")")) == '2TM')
			    {
					$now = new Nette\Utils\DateTime;
					$strNumber = $now->format('i');
			    }			    
			    
			    if ($strNumber != '' )
					$strFormula = substr($strFormula, 0, $numStart) . $strNumber . substr($strFormula, $numEnd);			    
			    
			    //dump($strFormula);
			}
			$arr['id'] = $data->id;
			$arr['number'] = $strFormula;
			//bdump($arr,'arr');
		    }
		  //  break;
	    //}
	    return $arr;
    }
    

	public function lowerNumber($id)
	{
		$company_id = $this->userManager->getCompany($this->user->getId());
		//bdump($id);
		if ($data = $this->findAllTotal()->where($this->tableName.'.id = ? AND '.$this->tableName.'.cl_company_id = ?', $id, $company_id)->fetch())
		{
		    //bdump($data);
			$data->update(array('id' => $id, 'last_number' => $data->last_number - 1 ));		
		}
	}


	public function getNewNumberSeries($data = '', $idNs = NULL)
    {
            $nSeries = $this->getNewNumber($data['use'], $idNs);
            //bdump($data);
            $data[$data['table_key']] = $nSeries['id'];
            $data[$data['table_number']] = $nSeries['number'];
            //bdump($data);
            //die;
            //dump($data);
            if ($hfData = $this->HeadersFootersManager->findBy(array('cl_number_series_id' => $nSeries['id']))->fetch()){

                $data['header_txt'] = $hfData['header_txt'];
                $data['footer_txt'] = $hfData['footer_txt'];
            }else{
                //12.03.2020 - next line was there by mistake - when we didn't found hfData we have to unset header_txt and footer_txt
                //$data['header_txt'] = $hfData['header_txt'];
                //$data['footer_txt'] = $hfData['footer_txt'];
                unset($data['header_txt']);
                unset($data['footer_txt']);
            }
            //dump($data);

            if ($tmpTypes = $this->InvoiceTypesManager->findBy(array('cl_number_series_id' => $nSeries['id']))->fetch()){
                $data['cl_invoice_types_id'] = $tmpTypes['id'];
            }

            $tmpStatus = $data['use'];
            //bdump($tmpStatus,'newnumberseries status');
            if ($nStatus = $this->StatusManager->findAll()->where('status_use = ? AND s_new = ?', $tmpStatus, 1)->fetch()) {
                $data['cl_status_id'] = $nStatus->id;
            }else{
                $data['cl_status_id'] = null;
            }

        //} else {
        //}
        return $data;
    }

	
}

