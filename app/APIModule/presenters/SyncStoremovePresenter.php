<?php
namespace App\APIModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Nette\Utils\DateTime;
use Tracy\Debugger;

class SyncStoremovePresenter  extends \App\APIModule\Presenters\SyncPresenter{
    

    /**
    * @inject
    * @var \App\Model\StoreMoveManager
    */
    public $DataManager;
    
    /**
    * @inject
    * @var \App\Model\CurrenciesManager
    */
    public $CurrenciesManager;

    /**
     * @inject
     * @var \App\Model\StorageManager
     */
    public $StorageManager;

    /**
     * @inject
     * @var \App\Model\StoreDocsManager
     */
    public $StoreDocsManager;

    /**
     * @inject
     * @var \App\Model\StoreManager
     */
    public $StoreManager;

    /**
     * @inject
     * @var \App\Model\PriceListManager
     */
    public $PricelistManager;

    /**
     * @inject
     * @var \App\Model\PartnersManager
     */
    public $PartnersManager;

    /**
     * @inject
     * @var \App\Model\CountriesManager
     */
    public $CountriesManager;


    /**
    * @inject
    * @var \App\Model\NumberSeriesManager
    */
    public $NumberSeriesManager;

    /**
     * @inject
     * @var \App\Model\CompanyBranchManager
     */
    public $CompanyBranchManager;


    /**
     * @inject
     * @var \App\Model\StatusManager
     */
    public $StatusManager;

	public function actionSet()
	{
		parent::actionSet();

        //file_put_contents( __DIR__."/../../../log/logxml.txt", $this->dataxml);
		$xml = simplexml_load_string($this->dataxml, "SimpleXMLElement",  LIBXML_NOCDATA);
		$json = json_encode($xml);
		//$json2 = urldecode($json);
		//file_put_contents('logjson.txt', $json);
		$array = json_decode($json,TRUE);
		$arrRet = array();
		
		if (!isset($array['item'][0]))
		{
		//	dump('neni pole');
			$array['item'] = array($array['item']);
		}
		//1. make cl_store_docs global parent record
        $tmpStorage = $this->StorageManager->findAllTotal()->
                                            where(array('cl_company_id' => $this->cl_company_id))->fetch();
        if ($tmpStorage)
            $tmpStorageId = $tmpStorage->id;
        else
            $tmpStorageId = NULL;




        $tmpClPartnersBookImport = $this->PartnersManager->findAllTotal()->
                                                            where('cl_company_id = ? AND company = ?', $this->cl_company_id, 'Inventura')->fetch();
        if ($tmpClPartnersBookImport){
            $tmpClPartnersBookImportId = $tmpClPartnersBookImport->id;
        }else{
            $tmpClPartnersBookImportId = $this->PartnersManager->insertForeign(array('cl_company_id' => $this->cl_company_id,
                                                                                    'company'       => 'Inventura',
                                                                                    'created' => new \Nette\Utils\DateTime(),
                                                                                    'create_by' => 'import F4',
                                                                                    'changed' => new \Nette\Utils\DateTime(),
                                                                                    'change_by' => 'import F4'));
        }

        //find branch and cl_storage_id
        $tmpBrancheStorageId = NULL;
        $tmpBranchId = NULL;
        foreach ($array['item'] as $key1 => $oneRecordB)
        {
            if ($oneRecordB['branch_name'] != "") {
                if ($tmpBranch = $this->CompanyBranchManager->findAllTotal()->where(array('cl_company_id' => $this->cl_company_id, 'name' => $oneRecordB['branch_name']))->fetch()) {
                    if (!is_null($tmpBranch->cl_storage_id)) {
                        $tmpBrancheStorageId = $tmpBranch->cl_storage_id;
                    }
                    $tmpBranchId = $tmpBranch->id;
                }
            }
            if (!is_null($tmpBrancheStorageId))
                break;
        }

        $tmpNow = new DateTime();
        if (!is_null($tmpBrancheStorageId))
        {
            $tmpDoc = $this->StoreDocsManager->findAllTotal()->where(array('cl_store_docs.cl_company_id' => $this->cl_company_id,
                                'cl_partners_book.company' => 'Inventura',
                                'cl_store_docs.create_by' => 'automat',
                                'cl_store_docs.cl_storage_id' => $tmpBrancheStorageId,
                                'doc_date'  => $tmpNow->format('Y-m-d'),
                                'doc_title' => 'import stavu zásob'))->fetch();
            $tmpStorageId = $tmpBrancheStorageId;
        }else {
            $tmpDoc = $this->StoreDocsManager->findAllTotal()->where(array('cl_store_docs.cl_company_id' => $this->cl_company_id,
                                'cl_partners_book.company' => 'Inventura',
                                'cl_store_docs.create_by' => 'automat',
                                'doc_date'  => $tmpNow->format('Y-m-d'),
                                'doc_title' => 'import stavu zásob'))->fetch();
        }


        if ($tmpDoc) {
            $tmpDoc = $tmpDoc->id;
        }else{

            $data = array('cl_company_id' => $this->cl_company_id,
                'doc_date' => $tmpNow,
                'cl_partners_name' => '',
                'currency_code' => $this->settings->cl_currencies->currency_code,
                'doc_title' => 'import stavu zásob',
                'storage_name' => '',
                'doc_type' => 'store_in',
                'cl_company_branch_id' => $tmpBranchId,
                'cl_partners_book_id' => $tmpClPartnersBookImportId,
                'cl_storage_id' => $tmpStorageId);


            $tmpDoc = $this->StoreDocsManager->ApiCreateDoc($data);

            if ($tmpStatus = $this->StatusManager->findAllTotal()->where('cl_company_id = ? AND status_use = ? AND status_name = ?', $this->cl_company_id, 'store_in', 'Importováno')->fetch())
            {
                $oneSync['cl_status_id'] = $tmpStatus->id;
                $this->StoreDocsManager->updateForeign(array('cl_company_id' => $this->cl_company_id, 'id' => $tmpDoc, 'cl_status_id' => $tmpStatus->id));
            }else{
                $arrStatus = array('cl_company_id' => $this->cl_company_id,
                    'status_name' => 'Importováno',
                    's_new' => 0,
                    's_fin' => 0,
                    's_storno' => 0,
                    'status_use' => 'store_in',
                    'color_hex' => '#0B61E9');
                $newStatus = $this->StatusManager->insertForeign($arrStatus);
                $this->StoreDocsManager->updateForeign(array('cl_company_id' => $this->cl_company_id, 'id' => $tmpDoc, 'cl_status_id' => $newStatus->id));
            }


        }

		//dump($array['partner']);
        $item_order = 1;
		foreach ($array['item'] as $key1 => $oneRecord)
		{
			foreach ($oneRecord as $key => $one)
			{
				if ( is_array($oneRecord[$key]))
				{
					$oneRecord[$key] = '';
				}
			}

			$tmpCis_record = $oneRecord['cis_oint'];
			$oneRecord['cl_company_id'] = $this->cl_company_id;
            $oneRecord['cl_store_docs_id'] = $tmpDoc;
            $oneRecord['item_order'] = $item_order;
            if ($oneRecord['exp_date'] == '    /  /  '){
                $oneRecord['exp_date'] = NULL;
            }else{
                $oneRecord['exp_date'] =  new \Nette\Utils\DateTime($oneRecord['exp_date']);
            }
            if ($oneRecord['batch'] == ''){
                $oneRecord['batch'] == NULL;
            }



			//2. find cl_store according to cl_pricelist_id, cl_storage_id, exp_date, batch,

            if ($tmpStorage = $this->StorageManager->findAllTotal()->
                                                            where(array('cl_company_id' => $this->cl_company_id, 'name' => $oneRecord['storage_name']))->fetch())
            {
                $oneRecord['cl_storage_id'] = $tmpStorage->id;
            }else{
                $tmpStorage = $this->StorageManager->findAllTotal()->
                                                            where(array('cl_company_id' => $this->cl_company_id))->fetch();
                if ($tmpStorage)
                    $oneRecord['cl_storage_id'] = $tmpStorage->id;
                else
                    $oneRecord['cl_storage_id'] = NULL;
            }

            //06.07.2019 - if is set branch cl_storage_id will be from this cl_company_branch
            if ($oneRecord['branch_name'] != "")
            {
                if ($tmpBranch = $this->CompanyBranchManager->findAllTotal()->where(array('cl_company_id' => $this->cl_company_id, 'name' => $oneRecord['branch_name']))->fetch())
                {
                    if (!is_null($tmpBranch->cl_storage_id)) {
                        $oneRecord['cl_storage_id'] = $tmpBranch->cl_storage_id;
                    }
                }

            }


            //find cl_pricelist_id
            if ($tmpPricelist = $this->PricelistManager->findAllTotal()->where('cl_company_id = ? AND identification = ?', $this->cl_company_id, $oneRecord['ident_ci'])->fetch())
            {
                $oneRecord['cl_pricelist_id'] = $tmpPricelist->id;
            }else{
                //wasn't found, create new in cl_pricelist_id

                if ($oneRecord['cre_date'] == "    /  /  ") {
                    $tmpCreated = new \Nette\Utils\DateTime;
                }else{
                    $tmpCreated = new \Nette\Utils\DateTime($oneRecord['cre_date'] . ' ' . $oneRecord['cre_time']);
                }

                if ($oneRecord['lm_date'] == "    /  /  ") {
                    $tmpChanged = new \Nette\Utils\DateTime;
                }else{
                    $tmpChanged = new \Nette\Utils\DateTime($oneRecord['lm_date'] . ' ' . $oneRecord['lm_time']);
                }

                $tmpPriceListNew = array();
                $tmpPriceListNew['cl_company_id']  = $this->cl_company_id;
                $tmpPriceListNew['identification'] = $oneRecord['ident_ci'];
                $tmpPriceListNew['item_label']     = $oneRecord['item_label'];
                $tmpPriceListNew['vat']            = $oneRecord['vat'];
                $tmpPriceListNew['cl_currencies_id'] = $this->settings->cl_currencies_id;
                $tmpPriceListNew['cl_storage_id']  = $oneRecord['cl_storage_id'];
                $tmpPriceListNew['created']        = $tmpCreated;
                $tmpPriceListNew['create_by']      = ( !empty($onePartner['cre_user']) ? $oneRecord['cre_user'] : 'import F4');
                $tmpPriceListNew['changed']        = $tmpChanged;
                $tmpPriceListNew['change_by']      = ( !empty($onePartner['lm_user']) ? $oneRecord['lm_user'] : 'import F4');

                $tmpPricelistId = $this->PricelistManager->insertForeign($tmpPriceListNew);
                $oneRecord['cl_pricelist_id']   = $tmpPricelistId;

            }


            $tmpStore = $this->StoreManager->findAllTotal()
                                            ->where(array('cl_company_id'   => $this->cl_company_id,
                                                          'cl_pricelist_id' => $oneRecord['cl_pricelist_id'],
                                                          'cl_storage_id'   => $oneRecord['cl_storage_id'],
                                                          'batch'         => $oneRecord['batch'],
                                                          'exp_date'      => $oneRecord['exp_date']
                                                            ))
                                            ->fetch();
            if ($tmpStore){
                $oneRecord['cl_store_id']   = $tmpStore->id;
                if (!is_null($oneRecord['s_total'] && !is_null($tmpStore->quantity))) {
                    $oneRecord['s_total'] = $oneRecord['s_total'] + $tmpStore->quantity;
                }else{
                    $oneRecord['s_total'] = 0;
                }
            }else{
                $tmpStoreNew = $this->StoreManager->insertForeign(array(
                                                                        'cl_company_id'     => $this->cl_company_id,
                                                                        'cl_pricelist_id'   => $oneRecord['cl_pricelist_id'],
                                                                        'cl_storage_id'     => $oneRecord['cl_storage_id'],
                                                                        'batch'             => $oneRecord['batch'],
                                                                        'exp_date'          => $oneRecord['exp_date']
                                                                    ));
                $oneRecord['cl_store_id'] = $tmpStoreNew;
            }
            //3. find cl_countries_id according to  country_of_origin
            $tmpCountry = $this->CountriesManager->findAllTotal()->where(array('cl_company_id' => $this->cl_company_id,
                                                                               'acronym'       => $oneRecord['country_of_origin']))->fetch();
            if ($tmpCountry){
                $oneRecord['cl_countries_id'] = $tmpCountry->id;
            }else{
                $oneRecord['cl_countries_id'] = NULL;
            }


            //$tmpOneRecord = json_encode($oneRecord);
            //file_put_contents( __DIR__."/../../../log/logdata-".$oneRecord['cl_pricelist_id'].".txt", $tmpOneRecord);

            unset($oneRecord['item_label']);
            unset($oneRecord['ident_ci']);
            unset($oneRecord['batch']);
            unset($oneRecord['exp_date']);
            unset($oneRecord['country_of_origin']);
            unset($oneRecord['storage_name']);
			unset($oneRecord['cis_oint']);
            unset($oneRecord['branch_name']);



			//unset($onePricelist['int_cis']);

            //if (is_null($oneRecord['s_total'])){
               // $oneRecord['s_total'] = 0;
            //}

			/*main method for save new data*/
           // bdump($oneRecord);



			$row = $this->syncData($oneRecord);
			//update sum on cl_store
            $this->StoreManager->updateStore(array('cl_store_id'      => $oneRecord['cl_store_id'],
                                                    'cl_pricelist_id' => $oneRecord['cl_pricelist_id'],
                                                    'cl_company_id'   => $this->cl_company_id));
			
			$arrRet[$tmpCis_record] = array('id' => $row['id'], 'updated' => $row['updated']);			
			$item_order++;
		}
		//at the and update sum on cl_store_doc
		$this->StoreManager->UpdateSum($tmpDoc);

		$strRet = "";
		foreach($arrRet as $key => $one)
		{
			$strRet .= $key.";".$one['id'].";".$one['updated'].PHP_EOL;
		}
		
		
		echo($strRet);
		$this->terminate();		
	}

	public function actionGet()
	{
		parent::actionGet();
		
		$tmpData = $this->DataManager->findAllTotal()->where(array('cl_company_id' => $this->cl_company_id, 'id' => $this->id))->fetch();
		$tmpDataArr = $tmpData->toArray();

		
		$xml = Array2XML::createXML('store_move', $tmpDataArr);
		echo $xml->saveXML();
		$this->terminate();		
	}
	
	
	public function actionGetNew()
	{
		parent::actionGetNew();
		
		
		$tmpData = $this->DataManager->findAllTotal()->where(array('cl_store_move.cl_company_id' => $this->cl_company_id))->
								where('cl_store_move.changed >= ? OR cl_store_move.created >= ?', $this->sync_last, $this->sync_last);
		if (!empty($this->dataxml))
		{
			$tmpData = $tmpData->where('cl_store_move.id NOT IN(?)', $this->dataxml);
		}
		
		$tmpData = $tmpData->select('cl_store_move.*');
		
		$tmpDataArr = array();
		foreach($tmpData as $key => $one)
		{
			$cl_partners_book = $one->toArray();
			$tmpDataArr['cl_store_move'][] = $cl_partners_book;

		}
		//dump($tmpDataArr);
		//die;
			$xml = Array2XML::createXML('store_move', $tmpDataArr);
			//dump($xml);
			echo $xml->saveXML();		

		$this->terminate();		
	}	
    
}
