<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * StoreDocs management.
 */
class StoreDocsManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_store_docs';

	/** @var App\Model\Base */
	public $NumberSeriesManager;			
	
	/** @var App\Model\Base */
	public $StatusManager;				
	
	/** @var App\Model\Base */
	public $PartnersManager;					
	
	/** @var App\Model\Base */
	public $CurrenciesManager;						
	
	/** @var App\Model\Base */
	public $StorageManager;							

	/** @var App\Model\Base */
	public $PairedDocsManager;	
	
	/**
	   * @param Nette\Database\Connection $db
	   * @throws Nette\InvalidStateException
	   */
	  public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \DatabaseAccessor $accessor,
                                  \Nette\Http\Session $session, NumberSeriesManager $NumberSeriesManager,
                                  StatusManager $StatusManager, PartnersManager $PartnersManager, CurrenciesManager $CurrenciesManager, StorageManager $StorageManager,
                                    PairedDocsManager $PairedDocsManager)
	  {
	      parent::__construct($db, $userManager, $user, $session, $accessor);
		  $this->NumberSeriesManager	= $NumberSeriesManager;
		  $this->StatusManager			= $StatusManager;
		  $this->PartnersManager		= $PartnersManager;
		  $this->CurrenciesManager		= $CurrenciesManager;
		  $this->StorageManager			= $StorageManager;
		  $this->PairedDocsManager		= $PairedDocsManager;
	  }    		
	

	/**
	 * Called from api
	 * @param type $data - array(	'cl_company_id',
	 *				'doc_date',
	 *				'cl_partners_name',
	 *				'currency_code',
	 *				'doc_title',
	 *				'storage_name',
	 *				'doc_type' => array('store_in','store_out'),
	 *				'cl_partners_book_id',
	 *				'cl_storage_id'
	 *							)
	 */
	public function ApiCreateDoc($dataApi)
	{	
		$data = new Nette\Utils\ArrayHash;
		$data['cl_company_id']	= $dataApi['cl_company_id'];
		$data['doc_date']	= $dataApi['doc_date'];
		$data['doc_title']	= $dataApi['doc_title'];
		if (!isset($dataApi['create_by']))
		{
		    $data['create_by']	= 'automat';
		}
        if (isset($dataApi['cl_company_branch_id']))
        {
            $data['cl_company_branch_id']	= $dataApi['cl_company_branch_id'];
        }
		
		if (isset($dataApi['cl_partners_book_id'])){
		    $tmpPartners = $this->PartnersManager->findAllTotal()->
				    where('cl_company_id = ?', $dataApi['cl_company_id'])->
				    where('id = ?', $dataApi['cl_partners_book_id'])->fetch();
		}else{
		    $tmpPartners = $this->PartnersManager->findAllTotal()->
				    where('cl_company_id = ?', $dataApi['cl_company_id'])->
				    where('company LIKE ?', $dataApi['cl_partners_name'].'%')->fetch();		    
		}
		
		if ($tmpPartners)
		{
			$data['cl_partners_book_id'] = $tmpPartners['id'];

			if ($tmpCurrencies = $this->CurrenciesManager->findAllTotal()->
						where('cl_company_id = ?', $dataApi['cl_company_id'])->
						where('currency_code LIKE ?', $dataApi['currency_code'])->fetch())
			{
				$data['cl_currencies_id']	 = $tmpCurrencies['id'];		
			}
			
			if (isset($dataApi['cl_storage_id'])){
			    $tmpStorage = $this->StorageManager->findAllTotal()->
						where('cl_company_id = ?', $dataApi['cl_company_id'])->
						where('id = ?', $dataApi['cl_storage_id'])->fetch();
			}elseif (isset($dataApi['storage_name'])){
			    $tmpStorage = $this->StorageManager->findAllTotal()->
						where('cl_company_id = ?', $dataApi['cl_company_id'])->
						where(array('name' => $dataApi['storage_name']))->fetch();    
			}else{
			    $tmpStorage = FALSE;
			}
			
			if ($tmpStorage)
			{
				$data['cl_storage_id']	 = $tmpStorage['id'];					
			}
			

			$nSeries = $this->NumberSeriesManager->getNewNumber($dataApi['doc_type'],NULL,NUll,$dataApi['cl_company_id']);
			$data['cl_number_series_id'] = $nSeries['id'];
			$data['doc_number']			 = $nSeries['number'];

			$tmpStatus = $dataApi['doc_type'];
			if ($nStatus = $this->StatusManager->findAllTotal()->
						where('cl_company_id = ?', $dataApi['cl_company_id'])->
						where('status_use = ? AND s_new = ?',$tmpStatus,1)->fetch())
			{
				$data['cl_status_id']	= $nStatus->id;		
			}
		
			if ($dataApi['doc_type'] == 'store_out'){
				$data['doc_type'] = 1;
			}elseif ($dataApi['doc_type'] == 'store_in'){
				$data['doc_type'] = 0;
			}
		
			return $this->insertForeign($data);
		}
		
	}
	
	
    /**06.03.2019 - moved from invoicepresenter because we need make invoice from commission presenter
     * @return type - integer or NULL  - return ID of created cl_store_docs
     * 
     */
    public function createStoreDoc($type, $id, $parentManager, $newDoc = FALSE)
    {
        $tmpData = $parentManager->find($id);
        if ($tmpData)
        {
            $arrStore = new \Nette\Utils\ArrayHash;
            //08.09.2020 - repair of use cl_company_branch_id
            //$arrStore['cl_company_branch_id']   = $this->user->getIdentity()->cl_company_branch_id;
            $arrStore['cl_company_branch_id']   = $tmpData->cl_company_branch_id;
            $arrStore['cl_partners_book_id']    = $tmpData->cl_partners_book_id;
            $arrStore['cl_currencies_id']       = $tmpData->cl_currencies_id;
            $arrStore['cl_users_id']            = $tmpData->cl_users_id; //21.04.2020 - doplneno

            if ($tmpData->currency_rate == 0)
                $rate = 1;
            else
                $rate = $tmpData->currency_rate;

            $arrStore['currency_rate']	        = $rate;
            $arrStore['doc_date']	            = new \Nette\Utils\DateTime;
            $arrStore['doc_type']	            = $type; //0 - income, 1 - giveout
            if ($parentManager->tableName == 'cl_invoice'){
                $parentKey                      = 'cl_invoice_id';
                $arrStore['doc_date']           = $tmpData['inv_date'];
            }elseif ($parentManager->tableName == 'cl_commission'){
                $parentKey                      = 'cl_commission_id';
                $arrStore['doc_date']           = $tmpData['cm_date'];
            }elseif ($parentManager->tableName == 'cl_offer'){
                $parentKey                      = 'cl_offer_id';
                $arrStore['doc_date']           = $tmpData['offer_date'];
            }elseif ($parentManager->tableName == 'cl_order'){
                $parentKey                      = 'cl_order_id';
                $arrStore['invoice_number']     = $tmpData['inv_numbers'];
                $arrStore['delivery_number']    = $tmpData['dln_numbers'];
                $arrStore['doc_date']           = $tmpData['od_date'];
            }elseif ($parentManager->tableName == 'cl_sale'){
                $parentKey                      = 'cl_sale_id';
                $arrStore['doc_date']           = $tmpData['inv_date'];
            }elseif ($parentManager->tableName == 'cl_delivery_note_in'){
                $parentKey                      = 'cl_delivery_note_in_id';
                $arrStore['delivery_number']    = $tmpData['rdn_number'];
                $arrStore['invoice_number']     = $tmpData['rinv_number'];
                $arrStore['doc_date']           = $tmpData['delivery_date'];
            }elseif ($parentManager->tableName == 'cl_delivery_note'){
                $parentKey                      = 'cl_delivery_note_id';
                $arrStore['doc_date']           = $tmpData['issue_date'];
            }
            //bdump($parentKey, 'parentKey');
            $arrStore[$parentKey]   = $tmpData->id;

            //bdump($arrStore);
            //die;

            if ($type == 0){
                //income to store
                //create or update store_doc
                //always create new store_doc when parent is cl_order
                $tmpTest = $this->find($tmpData->cl_store_docs_id_in);
                if ($tmpData->cl_store_docs_id_in == NULL || $parentKey == 'cl_order_id' || $newDoc || !$tmpTest)
                {
                    //new number
                    $nSeries				            = $this->NumberSeriesManager->getNewNumber('store_in');
                    $arrStore['doc_number']		        = $nSeries['number'];
                    $arrStore['cl_number_series_id']	= $nSeries['id'];
                    $tmpStatus				            = 'store_in';
                    if ($nStatus= $this->StatusManager->findAll()->where('status_use = ? AND s_new = ?',$tmpStatus,1)->fetch())
                              $arrStore['cl_status_id'] = $nStatus->id;

                    $row = $this->insert($arrStore);
                    $parentManager->update(['id' => $id, 'cl_store_docs_id_in' => $row->id]);
                    $docId = $row->id;
                }else
                {
                    $arrStore['id'] = $tmpData->cl_store_docs_id_in;
                    $row	        = $this->update($arrStore);
                    $docId	        = $tmpData->cl_store_docs_id_in;

                }

                //create pairedocs record
              //  bdump($parentKey, 'parentKey2');
                $this->PairedDocsManager->insertOrUpdate([$parentKey => $id, 'cl_store_docs_id' => $docId]);


            }elseif ($type == 1){
                //giveout from store
                //create or update store_doc
                if ($tmpData->cl_store_docs_id == NULL || $newDoc)
                {
                    //new number
                    $nSeries				          = $this->NumberSeriesManager->getNewNumber('store_out');
                    $arrStore['doc_number']		      = $nSeries['number'];
                    $arrStore['cl_number_series_id']  = $nSeries['id'];
                    $tmpStatus				          = 'store_out';
                    if ($nStatus= $this->StatusManager->findAll()->where('status_use = ? AND s_new = ?',$tmpStatus,1)->fetch())
                            $arrStore['cl_status_id'] = $nStatus->id;

                    $row = $this->insert($arrStore);
                    $parentManager->update(['id' => $id, 'cl_store_docs_id' => $row->id]);
                    $docId = $row->id;
                }else
                {
                    //unset($arrStore['doc_date']);
                    $arrStore['id'] = $tmpData->cl_store_docs_id;
                    $row	    = $this->update($arrStore);
                    $docId	    = $tmpData->cl_store_docs_id;
                }
                //create pairedocs record
                $this->PairedDocsManager->insertOrUpdate([$parentKey => $id, 'cl_store_docs_id' => $docId]);
            }
            //29.12.2017 - if there is commission for this invoice, we must update cl_store_docs_id in cl_commission
            if ($parentManager->tableName == 'cl_invoice' ){
                if (!is_null($tmpData->cl_commission_id))
                {
                    $tmpCommission = $tmpData->cl_commission;
                    $tmpCommission->update(array('id' => $tmpData->cl_commission_id, 'cl_store_docs_id' => $docId));
                }
            }
        }else{
            $docId = NULL;
        }
        return $docId;
    }	
	
	
}

