<?php
namespace App\APIModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Tracy\Debugger;

class SyncPricelistPresenter  extends \App\APIModule\Presenters\SyncPresenter{
    

    /**
    * @inject
    * @var \App\Model\PriceListManager
    */
    public $DataManager;
    
    /**
    * @inject
    * @var \App\Model\CurrenciesManager
    */
    public $CurrenciesManager;	

    /**
    * @inject
    * @var \App\Model\PriceListGroupManager
    */
    public $PriceListGroupManager;

    /**
     * @inject
     * @var \App\Model\StorageManager
     */
    public $StorageManager;

    /**
     * @inject
     * @var \App\Model\PartnersManager
     */
    public $PartnersManager;

    /**
     * @inject
     * @var \App\Model\StoreManager
     */
    public $StoreManager;

    /**
     * @inject
     * @var \App\Model\PricesManager
     */
    public $PricesManager;

    /**
     * @inject
     * @var \App\Model\PricesGroupsManager
     */
    public $PricesGroupsManager;

    /**
     * @inject
     * @var \App\Model\CompanyBranchManager
     */
    public $CompanyBranchManager;

    public function actionSet()
	{
		parent::actionSet();

       //file_put_contents( __DIR__."/../../../log/logxmlpricelist".random_int(0,10000).".txt", $this->dataxml);
		$xml = simplexml_load_string($this->dataxml, "SimpleXMLElement",  LIBXML_NOCDATA);

				
		$json = json_encode($xml);
		//$json2 = urldecode($json);
		//file_put_contents('logjson.txt', $json);
		$array = json_decode($json,TRUE);
		$arrRet = array();
		
		if (!isset($array['cenik'][0]))
		{
		//	dump('neni pole');
			$array['cenik'] = array($array['cenik']);
		}
		//dump($array['partner']);
		foreach ($array['cenik'] as $key1 => $onePricelist)
		{
			foreach ($onePricelist as $key => $one)
			{
				if ( is_array($onePricelist[$key]))
				{
					$onePricelist[$key] = '';
				}
			}

			$tmpCis_record = $onePricelist['int_cis'];
			$onePricelist['cl_company_id'] = $this->cl_company_id;


            //find cl_pricelist_id if already exists
            if (!is_null($onePricelist['identification'])) {
                if ($tmpPricelist = $this->DataManager->findAllTotal()->where('cl_company_id = ? AND identification = ?', $this->cl_company_id, $onePricelist['identification'])->fetch()) {
                    $onePricelist['id'] = $tmpPricelist->id;
                }
            }

			//set currency
            if (isset($onePricelist['currency_code'])) {
               //file_put_contents(__DIR__.'/../../../log/logcurrency_code'.$onePricelist['identification'].'.txt', $onePricelist['currency_code']);
                if ($tmpCurrency = $this->CurrenciesManager->findAllTotal()->where('cl_company_id = ? AND currency_code = ?', $this->cl_company_id, $onePricelist['currency_code'])->fetch()) {
                    $onePricelist['cl_currencies_id'] = $tmpCurrency->id;
                } else {
                    $onePricelist['cl_currencies_id'] = $this->settings->cl_currencies_id;
                }
                //file_put_contents(__DIR__.'/../../../log/logcurrency_code'.$onePricelist['identification'].'.txt', $onePricelist['currency_code'] . ' ---- ' .   $onePricelist['cl_currencies_id'] );
            }else{
            //    file_put_contents( __DIR__."/../../../log/logxmlpricelist".random_int(0,10000).".txt", $this->dataxml);
            //    file_put_contents(__DIR__.'/../../../log/logcurrency_code'.$onePricelist['identification'] . '-----' . $onePricelist['identification'].'.txt', $onePricelist['currency_code'] . ' ---- ' .   $onePricelist['cl_currencies_id'] . '-------' . $json . '------' . $xml );

            }

			//set cl_pricelist_group_id
            if (isset($onePricelist['category'])) {
                //file_put_contents(__DIR__.'/../../../log/logcateg.txt', $onePricelist['category']);
                if ($tmpPriceListGroup = $this->PriceListGroupManager->findAllTotal()->
                where(array('cl_company_id' => $this->cl_company_id, 'name' => $onePricelist['category']))->fetch()) {
                    $onePricelist['cl_pricelist_group_id'] = $tmpPriceListGroup->id;
                } else {
                    //$onePricelist['cl_pricelist_group_id'] = NULL;
                }
                //file_put_contents(__DIR__.'/../../../log/logcateg2.txt', $onePricelist['cl_pricelist_group_id']);
            }

            if (isset($onePricelist['storage_name'])) {
                if ($tmpStorage = $this->StorageManager->findAllTotal()->
                where(array('cl_company_id' => $this->cl_company_id, 'name' => $onePricelist['storage_name']))->fetch()) {
                    $onePricelist['cl_storage_id'] = $tmpStorage->id;
                } else {
                    $tmpStorage = $this->StorageManager->findAllTotal()->
                    where(array('cl_company_id' => $this->cl_company_id))->fetch();
                    if ($tmpStorage)
                        $onePricelist['cl_storage_id'] = $tmpStorage->id;
                    //else
                    //  $onePricelist['cl_storage_id'] = NULL;
                }
            }

            if (isset($onePricelist['partner_name'])) {
                if ($tmpStorage = $this->PartnersManager->findAllTotal()->
                                        where(array('cl_company_id' => $this->cl_company_id, 'company' => $onePricelist['partner_name']))->fetch()) {
                    $onePricelist['cl_partners_book_id'] = $tmpStorage->id;
                } else {
                    //switched off because on salepoints (perrito) are not correct values for partner_name and in case of synchro its wrong
                    //$onePricelist['cl_partners_book_id'] = NULL;
                }
            }

            if (isset($onePricelist['quantity_min'])) {
                $tmpQuantity_min = $onePricelist['quantity_min'];
                $tmpQuantity_req = $onePricelist['quantity_req'];
                $tmpPrice_1 = $onePricelist['price_1'];
                $tmpPrice_2 = $onePricelist['price_2'];
                $tmpPrice_3 = $onePricelist['price_3'];
                $tmpCurrency_1 = $onePricelist['curr_1'];
                $tmpCurrency_2 = $onePricelist['curr_2'];
                $tmpCurrency_3 = $onePricelist['curr_3'];
                $tmpCena_1 = $onePricelist['cena_1'];
                $tmpCena_2 = $onePricelist['cena_2'];
                $tmpCena_3 = $onePricelist['cena_3'];
                $tmpCena_4 = $onePricelist['cena_4'];
                $tmpCena_5 = $onePricelist['cena_5'];
                $tmpCena_6 = $onePricelist['cena_6'];
                $tmpCena_7 = $onePricelist['cena_7'];
                $priceWithVAT = $onePricelist['cena_sdph'];
                $tmpCurrencyCode = $onePricelist['currency_code'];
                $tmpBranchName = $onePricelist['branch_name'];
            }
            //file_put_contents(__DIR__.'/../../../log/../log/log-' .$onePricelist['int_cis'] . '.txt', json_encode($onePricelist));

            unset($onePricelist['cena_sdph']);
            unset($onePricelist['price_1']);
            unset($onePricelist['price_2']);
            unset($onePricelist['price_3']);
            unset($onePricelist['curr_1']);
            unset($onePricelist['curr_2']);
            unset($onePricelist['curr_3']);
            unset($onePricelist['cena_1']);
            unset($onePricelist['cena_2']);
            unset($onePricelist['cena_3']);
            unset($onePricelist['cena_4']);
            unset($onePricelist['cena_5']);
            unset($onePricelist['cena_6']);
            unset($onePricelist['cena_7']);
            unset($onePricelist['quantity_min']);
            unset($onePricelist['quantity_req']);
            unset($onePricelist['partner_name']);
            unset($onePricelist['storage_name']);
			unset($onePricelist['category']);
			unset($onePricelist['currency_code']);
			unset($onePricelist['int_cis']);
            unset($onePricelist['branch_name']);
			
			/*main method for save new data*/

			$row = $this->syncData($onePricelist);

            //now related tables cl_store for quantity_min
            if (isset($tmpQuantity_min)){
                if ($tmpQuantity_min > 0) {
                    $tmpStorage_id = NULL;
                    if ($tmpBranchName != "")
                    {
                        //06.07.2019 - if is set branch cl_storage_id will be from this cl_company_branch
                        if ($tmpBranch = $this->CompanyBranchManager->findAllTotal()->where(array('cl_company_id ' => $this->cl_company_id, 'name' => $tmpBranchName))->fetch())
                        {
                            if (!is_null($tmpBranch->cl_storage_id)) {
                                $tmpStorage_id = $tmpBranch->cl_storage_id;
                            }
                        }
                        $tmpStore = $this->StoreManager->findAllTotal()->
                                where(array('cl_company_id' => $this->cl_company_id, 'cl_pricelist_id' => $row['id'], 'cl_storage_id' => $tmpStorage_id));
                    }else{
                        $tmpStore = $this->StoreManager->findAllTotal()->
                                where(array('cl_company_id' => $this->cl_company_id, 'cl_pricelist_id' => $row['id']));
                    }

                    if ($tmpStore->count() > 0) {
                        foreach ($tmpStore as $key => $one) {
                                    $one->update(array('quantity_min' => $tmpQuantity_min, 'quantity_req' => $tmpQuantity_req));
                        }
                    } else {
                        if (is_null($tmpStorage_id)) {
                            $tmpStorages = $this->StorageManager->findAllTotal()->where(array('cl_company_id' => $this->cl_company_id));
                        }else{
                            $tmpStorages = $this->StorageManager->findAllTotal()->where(array('cl_company_id' => $this->cl_company_id, 'id' => $tmpStorage_id));
                        }
                        foreach ($tmpStorages as $key => $one)
                                {
                                    $tmpStore = array();
                                    $tmpStore['cl_company_id'] = $this->cl_company_id;
                                    $tmpStore['cl_storage_id'] = $one['id'];
                                    $tmpStore['cl_pricelist_id'] = $row['id'];
                                    $tmpStore['quantity_min'] = $tmpQuantity_min;
                                    $tmpStore['quantity_req'] = $tmpQuantity_req;
                                    $tmpStore['created'] = new \Nette\Utils\DateTime();
                                    $tmpStore['create_by'] = 'import F4';
                                    $tmpStore['changed'] = new \Nette\Utils\DateTime();
                                    $tmpStore['change_by'] = 'import F4';

                                    $retStore = $this->StoreManager->insertForeign($tmpStore);
                                }
                    }
                }
            }

            //now related tables  cl_prices for price_1-7
            //
            if (isset($tmpPrice_1)) {
                if ($tmpPrice_1 > 0) {
                    $this->prices($tmpPrice_1, $tmpCurrency_1, $row['id'], 'Skupina ' . $tmpCurrency_1, $priceWithVAT, $onePricelist['vat']);
                }
                if ($tmpPrice_2 > 0) {
                    $this->prices($tmpPrice_2, $tmpCurrency_2, $row['id'], 'Skupina ' . $tmpCurrency_2, $priceWithVAT, $onePricelist['vat']);
                }
                if ($tmpPrice_3 > 0) {
                    $this->prices($tmpPrice_3, $tmpCurrency_3, $row['id'], 'Skupina ' . $tmpCurrency_3, $priceWithVAT, $onePricelist['vat']);
                }
                if ($tmpCena_1 > 0) {
                    $this->prices($tmpCena_1, $tmpCurrencyCode, $row['id'], 'Skupina 1 ' . $tmpCurrencyCode, $priceWithVAT, $onePricelist['vat']);
                }
                if ($tmpCena_2 > 0) {
                    $this->prices($tmpCena_2, $tmpCurrencyCode, $row['id'], 'Skupina 2 ' . $tmpCurrencyCode, $priceWithVAT, $onePricelist['vat']);
                }
                if ($tmpCena_3 > 0) {
                    $this->prices($tmpCena_3, $tmpCurrencyCode, $row['id'], 'Skupina 3 ' . $tmpCurrencyCode, $priceWithVAT, $onePricelist['vat']);
                }
                if ($tmpCena_4 > 0) {
                    $this->prices($tmpCena_4, $tmpCurrencyCode, $row['id'], 'Skupina 4 ' . $tmpCurrencyCode, $priceWithVAT, $onePricelist['vat']);
                }
                if ($tmpCena_5 > 0) {
                    $this->prices($tmpCena_5, $tmpCurrencyCode, $row['id'], 'Skupina 5 ' . $tmpCurrencyCode, $priceWithVAT, $onePricelist['vat']);
                }
                if ($tmpCena_6 > 0) {
                    $this->prices($tmpCena_6, $tmpCurrencyCode, $row['id'], 'Skupina 6 ' . $tmpCurrencyCode, $priceWithVAT, $onePricelist['vat']);
                }
                if ($tmpCena_7 > 0) {
                    $this->prices($tmpCena_7, $tmpCurrencyCode, $row['id'], 'Skupina 7 ' . $tmpCurrencyCode, $priceWithVAT, $onePricelist['vat']);
                }
            }
            $arrRet[$tmpCis_record] = array('id' => $row['id'], 'updated' => $row['updated']);

			
		}
		$strRet = "";
		foreach($arrRet as $key => $one)
		{
			$strRet .= $key.";".$one['id'].";".$one['updated'].PHP_EOL;
		}
		
		
		echo($strRet);
		$this->terminate();		
	}

    /** Check if exists cl_prices_groups if not, create
     * @param $tmpPrice
     * @param $tmpCurrency
     * @param $cl_pricelist_id
     * @param $priceGroup
     */
	private function prices($tmpPrice, $tmpCurrencyCode, $cl_pricelist_id, $priceGroup, $priceWithVAT, $tmpVAT)
    {
        $tmpPrice = str_replace(',','.', $tmpPrice);
        $tmpVAT = str_replace(',','.', $tmpVAT);
        if ($priceWithVAT == 1) {
            $tmpPriceBase = $tmpPrice / (1 + ($tmpVAT / 100));
            $tmpPriceVAT = $tmpPrice;
        }else {
            $tmpPriceBase = $tmpPrice;
            $tmpPriceVAT = $tmpPrice *  (1 + ($tmpVAT / 100));
        }

        if ($tmpCurrency = $this->CurrenciesManager->findAllTotal()->where('cl_company_id = ? AND currency_code = ?', $this->cl_company_id, $tmpCurrencyCode)->fetch())
        {
            $tmpCurrencyId = $tmpCurrency->id;
        }else{
            $tmpCurrencyId = NULL;
        }

        $tmpPriceGroup = $this->PricesGroupsManager->findAllTotal()->where('cl_company_id = ? AND name = ?', $this->cl_company_id, $priceGroup)->fetch();
        if (!$tmpPriceGroup){
            $tmpPriceGroup                  = array();
            $tmpPriceGroup['cl_company_id'] = $this->cl_company_id;
            $tmpPriceGroup['name']          = $priceGroup;
            $tmpPriceGroup['created']       = new \Nette\Utils\DateTime();
            $tmpPriceGroup['create_by']     = 'import F4';
            $tmpPriceGroup['changed']       = new \Nette\Utils\DateTime();
            $tmpPriceGroup['change_by']     = 'import F4';
            $rowPriceGroupId                = $this->PricesGroupsManager->insertForeign($tmpPriceGroup);
        }else{
            $rowPriceGroupId = $tmpPriceGroup->id;
        }

        $tmpPrices = $this->PricesManager->findAllTotal()->
                                    where(array('cl_company_id'         => $this->cl_company_id,
                                                'cl_pricelist_id'       => $cl_pricelist_id,
                                                'cl_prices_groups_id'   => $rowPriceGroupId,
                                                'cl_currencies_id'      => $tmpCurrencyId));
        if ($tmpPrices->count() > 0)
        {
            foreach($tmpPrices as $key => $one) {
                $one->update(array('price' => $tmpPriceBase, 'price_vat' => $tmpPriceVAT));
            }
        }else{

            $tmpIns                         = array();
            $tmpIns['cl_company_id']        = $this->cl_company_id;
            $tmpIns['cl_prices_groups_id']  = $rowPriceGroupId;
            $tmpIns['cl_pricelist_id']      = $cl_pricelist_id;
            $tmpIns['price']                = $tmpPriceBase;
            $tmpIns['price_vat']            = $tmpPriceVAT;
            $tmpIns['cl_currencies_id']     = $tmpCurrencyId;
            $tmpIns['created']              =  new \Nette\Utils\DateTime();
            $tmpIns['create_by']            = 'import F4';
            $tmpIns['changed']              = new \Nette\Utils\DateTime();
            $tmpIns['change_by']            = 'import F4';

            $retStore = $this->PricesManager->insertForeign($tmpIns);

        }
    }


	public function actionGet()
	{
		parent::actionGet();
		
		$tmpData = $this->DataManager->findAllTotal()->where(array('cl_company_id' => $this->cl_company_id, 'id' => $this->id))->fetch();
		$tmpDataArr = $tmpData->toArray();

		
		$xml = Array2XML::createXML('cl_pricelist', $tmpDataArr);
		echo $xml->saveXML();
		$this->terminate();		
	}
	
	
	public function actionGetNew()
	{
		parent::actionGetNew();
		
		
		$tmpData = $this->DataManager->findAllTotal()->where(array('cl_pricelist.cl_company_id' => $this->cl_company_id))->
								where('cl_pricelist.changed >= ? OR cl_pricelist.created >= ?', $this->sync_last, $this->sync_last);
		if (!empty($this->dataxml))
		{
			$tmpData = $tmpData->where('cl_pricelist.id NOT IN(?)', $this->dataxml);
		}
		
		$tmpData = $tmpData->select('cl_pricelist.*');	
		
		$tmpDataArr = array();
		foreach($tmpData as $key => $one)
		{
			$cl_partners_book = $one->toArray();
			$tmpDataArr['cl_pricelist'][] = $cl_partners_book;

		}
		//dump($tmpDataArr);
		//die;
			$xml = Array2XML::createXML('pricelist', $tmpDataArr);					
			//dump($xml);
			echo $xml->saveXML();		

		$this->terminate();		
	}	
    
}
