<?php

namespace App\Model;


use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Tracy\Debugger;
use Exception;

/**
 * PriceList management.
 */
class PriceListManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_pricelist';

	private $db;

    /** @var App\Model\Base */
    public $CompaniesManager;

    /** @var App\Model\Base */
    public $ArraysManager;

    /** @var App\Model\NumberSeriesManager */
    public $NumberSeriesManager;

    public $settings;

    private $showNotActive = TRUE;

    /**
     * @param Nette\Database\Connection $db
     * @throws Nette\InvalidStateException
     */
    public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \Nette\Http\Session $session,
                                CompaniesManager $CompaniesManager, ArraysManager $ArraysManager, NumberSeriesManager $NumberSeriesManager, \DatabaseAccessor $accessor)
    {
        parent::__construct($db, $userManager, $user, $session, $accessor);

        $this->CompaniesManager = $CompaniesManager;
        $this->ArraysManager = $ArraysManager;
        $this->NumberSeriesManager = $NumberSeriesManager;
        $this->db = $db;
    }

    protected function getTable() {
        $data = parent::getTable();
        if ($this->showNotActive == FALSE) {
            $data = $data->where('not_active = 0');
        }
        return $data;
    }

	public function setShowNotActive($val){
        $this->showNotActive = $val;
    }

	public function update($data, $mark = FALSE)
	{
	    parent::update($data, $mark);
	    //dump($data['id']);
	    //die;
        //29.05.2019-prozatím funkcionalitu partnerských dat vypínám
        //TODO: dořešit později
	    //$this->updateCoopData($data['id']);
	}
	
	//public function insert($data)
	//{
	  //  $returnData = parent::insert($data);
	  //  $this->updateCoopData($returnData->id);
	//}

	
	/*
	 * !!only called from update in pricelist and pricelistpartner manager!!
	 * update cooperated data from master company to child company, called by master company when update cl_pricelist, cl_pricelist_partner
	 */
	public function updateCoopData($id)
	{
	    //Debugger::fireLog($id);
	    //dump($id);
	    //die;
	    //0. prepare data cl_pricelist
	    $onePriceList = $this->find($id);
	    //dump($onePriceList);
	    //die;
	    //$arrRow = new \Nette\Utils\ArrayHash;
	    $arrRow = array();
	    foreach($onePriceList as $key => $oneColumn)
	    {
		//each column from cl_pricelist
		$arrRow[$key] = $oneColumn;
	    }	
	    
	    //1. search across enabled partners coop and insert or update to their cl_pricelist record
	    $coopPartners = $this->database->table('cl_partners_coop')->where('master_cl_company_id = ?',$this->userManager->getCompany($this->user->getId()));
	    foreach ($coopPartners as $one)
	    {
		$arrRow2 = $arrRow; //copy of data for next work
		//dump($one);
		//dump($arrRow2);
		//die;
		//search child cl_partners_book in master
		$tmpChildPartner = $this->database->table('cl_partners_book')->where('id = ?',$one->child_cl_partners_book_id)->fetch();
		if($tmpChildPartner->pricelist_partner)
		{
		    //if there is active cl_pricelist_partner we must set some values
		    if ($masterData = $this->database->table('cl_pricelist_partner')->where('cl_partners_book_id=? AND cl_pricelist_id = ?',$one->child_cl_partners_book_id,$id)->fetch())
		    {
			$is_pricelist_partner = TRUE;
			$arrRow2['price']	      = $masterData['price'];
			$arrRow2['price_vat']	      = $masterData['price_vat'];
			$arrRow2['vat']		      = $masterData['vat'];
			$arrRow2['cl_currencies_id']  = $masterData['cl_currencies_id'];
		    }else
		    {
			$is_pricelist_partner = FALSE;
		    }

		    //if there is active cl_pricelist_partner we must unset some values, because they are set by cl_pricelist_partner
		    //unset($arrRow2['price']);
		    //unset($arrRow2['price_s']);
		    //unset($arrRow2['price_vat']);
		    //unset($arrRow2['vat']);
		    //unset($arrRow2['cl_currencies_id']);
		}
		else
		{
		    $is_pricelist_partner = FALSE;
		}
		
		//work with common cl_pricelist
		if ($this->database->table('cl_company')->where('id = ',$one->cl_company_id)->fetch()->platce_dph)
		{
		    $arrRow2['price_s'] = $onePriceList['price']; //stock price of master mustbe set to price	
		}
		else
		{
		    $arrRow2['price_s'] = $onePriceList['price_vat']; //stock price of master mustbe set to price	
		}		    		
		$arrRow2['master_id']		    = $id;
		$arrRow2['master_cl_company_id']    = $one->master_cl_company_id;		
		$arrRow2['cl_company_id']	    = $one->cl_company_id;
		   //Debugger::fireLog($arrRow2);
		
		//search if cl_pricelist for child company exist
		if ($tmpSearch = $this->findAllTotal()->where('cl_company_id = ? AND master_id = ?',$one->cl_company_id,$id)->fetch())
		{//exist, update
		    //Debugger::fireLog($tmpSearch);
		    $arrRow2['id'] = $tmpSearch['id'];
		    unset($arrRow2['price']); //for case of update, we must not overwrite prices
		    unset($arrRow2['price_vat']);		    
		    unset($arrRow2['cl_currencies_id']); //these dependecies we must let because they can have another values by child user
		    unset($arrRow2['cl_pricelist_group_id']);
		    unset($arrRow2['cl_currencies_id']);
		    $this->updateForeign($arrRow2);
		    unset($arrRow2);
		}  else {//not exist, insert new one
		    if (($tmpChildPartner->pricelist_partner && $is_pricelist_partner) || !$tmpChildPartner->pricelist_partner)
		    {//pricelist partner is active and item is now in cl_pricelist_partner, we can insert to child cl_pricelist
			//or pricelist partner is not enable, in this case we insert always

			    //we must solve dependency on other tables before inserting
			    //cl_currencies_id
			    //cl_pricelist_group_id
			    //vat
			    //BUT! if we dissable selectboxes, radio groups in edit form, we don't need this....
			
			//23.10.2015 - we don't need those dependencies, because cl_currencies and cl_rates_vat are shared 
			//and cl_pricelist_group is only for master
			/*
			    if (!$tmpCurrencies = $this->database->table('cl_currencies')->
						where('cl_company_id = ? AND currency_code = ?',$one->cl_company_id,$onePriceList->cl_currencies->currency_code)->fetch())
			    {//value doesn't exist, we must create new one
				$arrCurrency = new Nette\Utils\ArrayHash;
				foreach($onePriceList->cl_currencies as $key => $oneColumn)
				{
				    //each column from cl_pricelist
				    $arrCurrency[$key] = $oneColumn;
				}					
				unset($arrCurrency['id']);
				$arrCurrency['cl_company_id'] = $one->cl_company_id;
				$retCurrency = $this->database->table('cl_currencies')->insert($arrCurrency);
				$tmpCurrencyId = $retCurrency->id;
			    }else
			    {
				$tmpCurrencyId = $tmpCurrencies->id;
			    }
			    
			    if (!$tmpGroup = $this->database->table('cl_pricelist_group')->
						where('cl_company_id = ? AND name = ?',$one->cl_company_id,$onePriceList->cl_pricelist_group->name)->fetch())
			    {//value doesn't exist, we must create new one
				$arrGroup = new Nette\Utils\ArrayHash;
				foreach($onePriceList->cl_pricelist_group as $key => $oneColumn)
				{
				    //each column from cl_pricelist
				    $arrGroup[$key] = $oneColumn;
				}					
				unset($arrGroup['id']);
				$arrGroup['cl_company_id'] = $one->cl_company_id;
				$retGroup = $this->database->table('cl_pricelist_group')->insert($arrGroup);
				$tmpGroupId = $retGroup->id;
			    }else
			    {
				$tmpGroupId = $tmpGroup->id;
			    }			    
			    
			    if (!$tmpVat = $this->database->table('cl_rates_vat')->
						where('cl_company_id = ? AND rates = ?',$one->cl_company_id,$onePriceList->vat)->fetch())
			    {//value doesn't exist, we must create new one
				if ($tmpVatMaster = $this->database->table('cl_rates_vat')->
						where('cl_company_id = ? AND rates = ?',$one->master_cl_company_id,$onePriceList->vat)->fetch())
				{
				    $arrVat = new Nette\Utils\ArrayHash;
				    foreach($tmpVatMaster as $key => $oneColumn)
				    {
					//each column from cl_pricelist
					$arrVat[$key] = $oneColumn;
				    }					
				    unset($arrVat['id']);
				    $arrVat['cl_company_id'] = $one->cl_company_id;
				    $arrVat['cl_countries_id'] = NULL;
				    $retVat = $this->database->table('cl_rates_vat')->insert($arrVat);
				    //$tmpVatId = $retGroup->id;
				}
			    }else
			    {
				//$tmpGroupId = $tmpGroup->id;
			    }			    			    
			    */
			    
			//    $arrRow2['cl_pricelist_group_id'] = $tmpGroupId;
			//    $arrRow2['cl_currencies_id'] = $tmpCurrencyId;			

			unset($arrRow2['cl_pricelist_group_id']);			
			unset($arrRow2['id']);
			//dump($arrRow2);			
			//die;
			$this->insertForeign($arrRow2);
			unset($arrRow2);
		    }
		}
		

		
	    }

	    
	}
	
	/*21.01.2018 - return storePrice
	 * 
	 */
	public function getStorePrice($id, $storage_id = NULL){
	   $data = $this->find($id);
	   if ($data){
		$storePrice = $data->price_s;
	   }else{
		$storePrice = 0;
	   }
	    return $storePrice;
	}



	public function repairEANDuplicity()
    {
        $this->settings = $this->CompaniesManager->getTable()->fetch();
        //1. find duplicity on EAN
        //2. for each duplicity record we must set one of records as MAIN and replace cl_pricelist_id in child records of other tables connect to this record
        //3. save notmain cl_pricelist.identification to cl_pricelist.search_tag  and cl_pricelist.ean_code to cl_pricelist.ean_old
        // Rules for determinate MAIN record: cl_pricelist_group_id IS NOT NULL, cl_partners_book_id IS NOT NULL, price > 0, price_vat > 0
        // tables which will be affected
        // cl_commission_items, commission_items_sel, cl_delivery_note_items, cl_delivery_note_items_back
        // cl_files, cl_invoice_items, cl_invoice_items_back, cl_offer_items, cl_order_items,
        // cl_sale_items, cl_store_move
        //4. at the end we have to recalculate stores on each affected cl_pricelist record

        $arrTables = array('cl_commission_items', 'cl_commission_items_sel', 'cl_delivery_note_items', 'cl_delivery_note_items_back',
                           'cl_files', 'cl_invoice_items', 'cl_invoice_items_back', 'cl_offer_items', 'cl_order_items',
                            'cl_sale_items', 'cl_store_move', 'cl_store');

        //(SELECT COUNT(*) FROM cl_pricelist AS xxx WHERE ean_code = cl_pricelist.ean_code) AS count

        //1.
        $duplicity = $this->findAll()->
                            select('DISTINCT cl_pricelist.ean_code')->
                            where('(SELECT COUNT(*) FROM cl_pricelist AS xxx WHERE ean_code = cl_pricelist.ean_code) > 1 AND ean_code != ""')->order('ean_code');

        $arrMainRec = array();
        foreach($duplicity as $one)
        {
            $mainRec = $this->findAll()->
                                where('ean_code = ? AND cl_pricelist_group_id IS NOT NULL AND cl_partners_book_id IS NOT NULL AND price > 0 AND price_vat > 0', $one->ean_code)->fetch();
            if (!$mainRec){
                $mainRec = $this->findAll()->
                                where('ean_code = ? AND price > 0 AND price_vat > 0', $one->ean_code)->fetch();
            }
            if ($mainRec){
                $arrOthers = $this->findAll()->
                                    where('ean_code = ? AND id != ?', $one->ean_code, $mainRec->id)->fetchPairs('id', 'id');

                $arrMainRec[$mainRec->ean_code] = array('id' => $mainRec->id, 'others' => $arrOthers);
            }

        }
        //bdump($arrMainRec);

        //2.
        $this->database->beginTransaction(); // zahájení transakce
        foreach($arrMainRec as $ean_code => $one)
        {
            foreach($arrTables as $oneTable){
                $this->database->table($oneTable)->where('cl_pricelist_id IN ? ', $one['others'])->update(array('cl_pricelist_id' => $one['id']));
            }
        }
        $this->database->commit();

        //3. 4.
        $this->database->beginTransaction(); // zahájení transakce
        foreach($arrMainRec as $ean_code => $one)
        {
            //'search_tag' => new Nette\Database\SqlLiteral('CONCAT(search_tag," ",identification'),
            $tmpStag = $this->findAll()->where('id IN ?', $one['others']);
            $strTag = "";
            $i = 0;
            foreach ($tmpStag as $oneRec)
            {
                if ($i > 0) $strTag .= ";";

                $strTag .= $oneRec->identification;
                $i++;
            }
            $this->findAll()->where('id = ?', $one['id'])->update(array('search_tag' => $strTag));


            $this->findAll()->where('id IN ?', $one['others'])->
                                update(array('ean_old' => new Nette\Database\SqlLiteral('ean_code')));

            $this->findAll()->where('id IN ?', $one['others'])->
                                update(array('ean_code' => '', 'not_active' => 1));
            //4.
            //update quantity on cl_pricelist
            $quantity = $this->database->table('cl_store_move')->where(array('cl_pricelist_id' => $one['id']))->sum('s_in - s_out');
            $arrUpdate = new \Nette\Utils\ArrayHash;
            $arrUpdate['id'] = $one['id'];
            $arrUpdate['quantity'] = $quantity;
            $this->update($arrUpdate);

        }
        $this->database->commit();

        $debug_export = var_export($arrMainRec, true);
        $dataFolder = $this->CompaniesManager->getDataFolder($this->settings->id);
        $subFolder = $this->ArraysManager->getSubFolder('log');
        $destFolder = $dataFolder . '/' . $subFolder;
        if (!is_dir($destFolder)) {
            mkdir($destFolder);
        }
        $f = new \FilesystemIterator($destFolder, \FilesystemIterator::SKIP_DOTS);
        $f++;
        $destFile   =  $destFolder . '/' . 'ean_duplicity_repair-' .iterator_count($f). '.log';
        file_put_contents($destFile, $debug_export);
    }


  /*  public function getB2BPricelist(){
	    $result = $this->db->query("
                    SELECT `cl_pricelist`.*, 
             (IF(`cl_pricelist_partner`.`price_vat` > 0,
            `cl_pricelist_partner`.`price_vat`,`cl_pricelist`.`price_vat`) * ( 1 - (
            IF(`cl_pricelist_partner`.`price_vat` IS NULL,
             IF( 10 > ABS(IFNULL(`cl_pricelist_partner_group`.`price_change`,0)), 
             10, 
             ABS(`cl_pricelist_partner_group`.`price_change`))
             ,0) / 100))) AS `price_dis`, 
             CASE WHEN SUM(`cl_store`.`quantity`) = 0 THEN 0
             WHEN SUM(`cl_store`.`quantity`) > 0 AND `cl_store`.`quantity` <= 10 THEN 2
             WHEN SUM(`cl_store`.`quantity`) > 10 THEN 3
             ELSE NULL
             END AS `availability`, 
             IF(`cl_pricelist_partner`.`price_vat` IS NULL,
             IF( 10 > ABS(IFNULL(`cl_pricelist_partner_group`.`price_change`,0)), 
             10, 
             ABS(`cl_pricelist_partner_group`.`price_change`)), ROUND((1 - `cl_pricelist_partner`.`price_vat` /
            `cl_pricelist`.`price_vat`) * 100, 0))
             AS `discount`,
             (
            SELECT SUM(`cl_b2b_order_items`.`quantity`) 
            FROM `cl_b2b_order_items`
            WHERE `cl_b2b_order_items`.`cl_pricelist_id` = `cl_pricelist`.`id` AND
             `cl_b2b_order_items`.`cl_b2b_order_id` = 45) AS `quantity_basket`,
            `cl_pricelist_partner_group`.`price_change` AS `price_change`,
             `cl_pricelist_partner`.`price_vat` AS `price_partner` 
            FROM `cl_pricelist` 
            LEFT JOIN `cl_pricelist_group` ON `cl_pricelist`.`cl_pricelist_group_id` = `cl_pricelist_group`.`id` OR `cl_pricelist`.`cl_pricelist_group2_id` = `cl_pricelist_group`.`id` 
            LEFT JOIN `cl_pricelist_partner_group` ON `cl_pricelist_group`.`id` =
            `cl_pricelist_partner_group`.`cl_pricelist_group_id` AND
            (`cl_pricelist_partner_group`.`cl_pricelist_group_id` IN(128, 153)
             AND `cl_pricelist_partner_group`.`cl_partners_book_id` = 3541) 
            LEFT JOIN `cl_pricelist_partner` ON `cl_pricelist`.`id` = `cl_pricelist_partner`.`cl_pricelist_id`
            AND (`cl_pricelist_partner`.`cl_pricelist_id` IN(17304, 17336, 22058, 25949, 17929)
             AND `cl_pricelist_partner`.`cl_partners_book_id` = 3541) 
            LEFT JOIN `cl_store` ON `cl_pricelist`.`id` = `cl_store`.`cl_pricelist_id` 
            LEFT JOIN `cl_storage` ON `cl_store`.`cl_storage_id` = `cl_storage`.`id` 
            LEFT JOIN `cl_b2b_order_items` ON `cl_pricelist`.`id` = `cl_b2b_order_items`.`cl_pricelist_id` 
            WHERE (`cl_pricelist`.`cl_company_id` = 2) AND (`cl_storage`.`b2b_store` = 1 AND 
             `cl_pricelist_group`.`b2b_show` = 1 AND 
             ((`cl_pricelist_partner_group`.`cl_pricelist_group_id` IN(128, 153) AND 
             `cl_pricelist_partner_group`.`cl_partners_book_id` = 3541 ) OR
             (`cl_pricelist_partner`.`cl_pricelist_id` IN(17304, 17336, 22058, 25949, 17929) AND 
             `cl_pricelist_partner`.`cl_partners_book_id` = 3541))) AND (`cl_pricelist`.`cl_pricelist_group_id` = '153'
            OR `cl_pricelist`.`cl_pricelist_group2_id` = '153') 
            GROUP BY `cl_pricelist`.`id`
	    ");

	    return $result;
    }*/

    public function replaceNew()
    {
        $tmpData = $this->findAll()->where('identification_new != ""');
        foreach($tmpData as $key => $one)
        {
            $one->update(array('identification' => $one['identification_new']));
            $one->update(array('identification_new' => ""));
        }
        return;
    }

    /**restore previous stored old prices  and set old prices to 0
     * @return int
     */
    public function restorePrice()
    {
        try {
            $this->database->beginTransaction();
            $tmpData = $this->findAll()->where('price_old > 0 AND price_vat_old > 0');
            $count = 0;
            foreach ($tmpData as $key => $one) {
                $one->update(array('price' => $one['price_old'], 'price_vat' => $one['price_vat_old'], 'price_old' => 0, 'price_vat_old' => 0, 'price_updated' => 1));
                $count++;
            }
            $this->database->commit();
            return $count;

        } catch (Exception $e){
            $this->database->rollBack();
            return 0;
        }
    }

    /**make price change and set old prices to previous values
     * @param $values price_change_per, price_change_abs, base[prices_s, price, price_vat], cl_pricelist_group, cl_partners_book
     * @return int
     */
    public function priceChange($values) : int
    {
        try {
            $this->database->beginTransaction();
            $tmpData = $this->findAll();
            if (count($values['cl_pricelist_group']) > 0) {
                $tmpData = $tmpData->where('cl_pricelist_group_id IN ?', $values['cl_pricelist_group']);
            }
            if (count($values['cl_partners_book']) > 0) {
                $tmpData = $tmpData->where('cl_partners_book_id IN ?', $values['cl_partners_book']);
            }

            $count = 0;
            foreach ($tmpData as $key => $one) {
                $new_price = 0;
                $new_price_vat = 0;
                if ($values['price_change_per'] <> 0) {
                    if ($values['base'] == 'price_s'){
                        //update by price_s
                        $ret = $this->updatePriceS($one['id'], FALSE);
                        $tmpPricelist = $this->find($one['id']);
                        if ($tmpPricelist){
                            $new_price      = $tmpPricelist['price_s'] * (1 + ($values['price_change_per'] / 100));
                            if ($values['round']){
                                $new_price = round($new_price,0);
                            }
                            $new_price_vat  = $new_price * (1 + ($one['vat'] / 100));
                        }

                    }elseif ($values['base'] == 'price' || $values['base'] == 'price_vat'){
                        //update by price or price_vat
                        if ($values['base'] == 'price') {
                            $new_price          = $one['price'] * (1 + ($values['price_change_per'] / 100));
                            if ($values['round']){
                                $new_price      = round($new_price,0);
                            }
                            $new_price_vat      = $new_price * (1 + ($one['vat'] / 100));

                        }elseif ($values['base'] == 'price_vat') {
                            $new_price_vat      = $one['price_vat'] * (1 + ($values['price_change_per'] / 100));
                            if ($values['round']){
                                $new_price_vat = round($new_price_vat,0);
                            }
                            $new_price          = $new_price_vat / (1 + ($one['vat'] / 100));
                        }

                    }

                }elseif ($values['price_change_abs'] > 0) {
                    if ($values['base'] == 'price') {
                        $new_price          = $one['price']  + $values['price_change_abs'];
                        if ($values['round']){
                            $new_price = round($new_price,0);
                        }
                        $new_price_vat      = $new_price * (1 + ($one['vat'] / 100));

                    }elseif ($values['base'] == 'price_vat'){
                        $new_price_vat      = $one['price_vat']  + $values['price_change_abs'];
                        if ($values['round']){
                            $new_price_vat = round($new_price_vat,0);
                        }
                        $new_price          = $new_price_vat / (1 + ($one['vat'] / 100));

                    }
                }
                if ($new_price > 0 || $new_price_vat > 0) {
                    $one->update(['price_old' => $one['price'], 'price_vat_old' => $one['price_vat'],
                        'price' => $new_price, 'price_vat' => $new_price_vat, 'price_updated' => 1]);

                    $count++;
                }else{

                }
            }
            $this->database->commit();
            return $count;

        } catch (Exception $e){
            $this->database->rollBack();
            return 0;
        }
    }


    /**make update of cl_pricelist.price_s if is 0
     * @param null $cl_pricelist_id
     * @return int
     */
    public function updatePriceS($cl_pricelist_id = NULL, $force = FALSE) : int
    {
        $tmpData = $this->findAll();
        if (!is_null($cl_pricelist_id)){
            $tmpData = $tmpData->where('id = ?', $cl_pricelist_id);
        }
        if (!$force){
            $tmpData = $tmpData->where('price_s = 0 ');
        }

        $count = 0;
        foreach ($tmpData as $key => $one)
        {
            $data = $one->related('cl_store_move')->where('s_in > 0 AND price_s > 0')->order('id DESC')->limit(1)->select('price_s')->fetch();
            if ($data['price_s'] > 0) {
                $one->update(['price_s' => $data['price_s']]);
            }
            $count++;
        }
        return $count;
    }

    /**restore supplier stored in previous supplier change
     * @return int
     */
    public function restoreSupplier() : int
    {
        try {
            $this->database->beginTransaction();
            $tmpData = $this->findAll()->where('cl_partners_book_id_old IS NOT NULL');
            $count = 0;
            foreach ($tmpData as $key => $one) {
                $one->update(['cl_partners_book_id' => $one['cl_partners_book_id_old'], 'cl_partners_book_id_old' => NULL]);
                $count++;
            }

            $tmpData = $this->findAll()->where('cl_partners_book_id2_old IS NOT NULL');
            foreach ($tmpData as $key => $one) {
                $one->update(['cl_partners_book_id2' => $one['cl_partners_book_id2_old'], 'cl_partners_book_id2_old' => NULL]);
                $count++;
            }

            $tmpData = $this->findAll()->where('cl_partners_book_id3_old IS NOT NULL');
            foreach ($tmpData as $key => $one) {
                $one->update(['cl_partners_book_id3' => $one['cl_partners_book_id3_old'], 'cl_partners_book_id3_old' => NULL]);
                $count++;
            }

            $tmpData = $this->findAll()->where('cl_partners_book_id4_old IS NOT NULL');
            foreach ($tmpData as $key => $one) {
                $one->update(['cl_partners_book_id4' => $one['cl_partners_book_id4_old'], 'cl_partners_book_id4_old' => NULL]);
                $count++;
            }

            $this->database->commit();

            return $count;

        } catch (Exception $e){
            $this->database->rollBack();
            return 0;
        }
    }

    /**
     * @param $values  cl_pricelist_group, cl_partners_book_old, cl_partners_book_new, partner_order_old [1,2,3,4], partner_order_new [1,2,3,4]
     * @return int
     */
    public function supplierChange($values) : int
    {
        try {
            $this->database->beginTransaction();
            $tmpData = $this->findAll();
            if (count($values['cl_pricelist_group']) > 0) {
                $tmpData = $tmpData->where('cl_pricelist_group_id IN ?', $values['cl_pricelist_group']);
            }

            if (count($values['cl_partners_book_old']) > 0) {
                if ($values['partner_order_old'] == 1) {
                    $tmpData = $tmpData->where('cl_partners_book_id IN ?', $values['cl_partners_book_old']);
                } elseif ($values['partner_order_old'] == 2) {
                    $tmpData = $tmpData->where('cl_partners_book_id2 IN ?', $values['cl_partners_book_old']);
                } elseif ($values['partner_order_old'] == 3) {
                    $tmpData = $tmpData->where('cl_partners_book_id3 IN ?', $values['cl_partners_book_old']);
                } elseif ($values['partner_order_old'] == 4) {
                    $tmpData = $tmpData->where('cl_partners_book_id4 IN ?', $values['cl_partners_book_old']);
                }
            }

            $count = 0;
            foreach ($tmpData as $key => $one) {
                if ($values['cl_partners_book_new'] != "") {
                    if ($values['partner_order_new'] == 1) {
                        $one->update(array('cl_partners_book_id' => $values['cl_partners_book_new'], 'cl_partners_book_id_old' => $one['cl_partners_book_id']));
                    } elseif ($values['partner_order_new'] == 2) {
                        $one->update(array('cl_partners_book_id2' => $values['cl_partners_book_new'], 'cl_partners_book_id2_old' => $one['cl_partners_book_id2']));
                    } elseif ($values['partner_order_new'] == 3) {
                        $one->update(array('cl_partners_book_id3' => $values['cl_partners_book_new'], 'cl_partners_book_id3_old' => $one['cl_partners_book_id3']));
                    } elseif ($values['partner_order_new'] == 4) {
                        $one->update(array('cl_partners_book_id4' => $values['cl_partners_book_new'], 'cl_partners_book_id4_old' => $one['cl_partners_book_id4']));
                    }
                    $count++;
                }

            }
            $this->database->commit();
            return $count;

        } catch (Exception $e){
            $this->database->rollBack();
            return 0;
        }
    }


    public function setNotActiveReset()
    {
        $tmpData = $this->findAll();
        $tmpData->update(array('not_active_prep' => 0));
    }

    public function setNotActivePrepare($values)
    {
        //try{
            //$this->database->beginTransaction();
            $tmpData = $this->findAll();
            $tmpData->update(array('not_active_prep' => 0));
            if ($values['cl_pricelist_group'] != '') {
                $tmpData = $tmpData->where('cl_pricelist_group_id = ?', $values['cl_pricelist_group']);
            }
            if ($values['cl_partners_book']  != '') {
                $tmpData = $tmpData->where('cl_partners_book_id = ?', $values['cl_partners_book']);
            }
            $tmpData = $tmpData->where('cl_pricelist.quantity = 0');

            /*if ($values['cl_producer'] != '') {
                $tmpData = $tmpData->where('cl_producer_id = ?', $values['cl_producer']);
            }*/

            //cl_store_move
            $tmpData2 = $tmpData->where('NOT EXISTS(SELECT cl_store_move.id FROM cl_store_move 
                                                                LEFT JOIN cl_store_docs ON cl_store_docs.id = cl_store_move.cl_store_docs_id 
                                                                WHERE cl_store_move.cl_pricelist_id = cl_pricelist.id AND cl_store_docs.doc_date >= ?)', $values['date_to']);
            //cl_offer
            $tmpData2 = $tmpData2->where('NOT EXISTS(SELECT cl_offer_items.id FROM cl_offer_items 
                                                                LEFT JOIN cl_offer ON cl_offer.id = cl_offer_items.cl_offer_id 
                                                                WHERE cl_offer_items.cl_pricelist_id = cl_pricelist.id AND cl_offer.offer_date >= ?)', $values['date_to']);
            //cl_commission
            $tmpData2 = $tmpData2->where('NOT EXISTS(SELECT cl_commission_items.id FROM cl_commission_items 
                                                                LEFT JOIN cl_commission ON cl_commission.id = cl_commission_items.cl_commission_id 
                                                                WHERE cl_commission_items.cl_pricelist_id = cl_pricelist.id AND cl_commission.cm_date >= ?)', $values['date_to']);
            //cl_order
            $tmpData2 = $tmpData2->where('NOT EXISTS(SELECT cl_order_items.id FROM cl_order_items 
                                                                LEFT JOIN cl_order ON cl_order.id = cl_order_items.cl_order_id 
                                                                WHERE cl_order_items.cl_pricelist_id = cl_pricelist.id AND cl_order.od_date >= ?)', $values['date_to']);
            //cl_delivery_note
            $tmpData2 = $tmpData2->where('NOT EXISTS(SELECT cl_delivery_note_items.id FROM cl_delivery_note_items 
                                                                LEFT JOIN cl_delivery_note ON cl_delivery_note.id = cl_delivery_note_items.cl_delivery_note_id 
                                                                WHERE cl_delivery_note_items.cl_pricelist_id = cl_pricelist.id AND cl_delivery_note.issue_date >= ?)', $values['date_to']);
            //cl_delivery_note_items_back
            $tmpData2 = $tmpData2->where('NOT EXISTS(SELECT cl_delivery_note_items_back.id FROM cl_delivery_note_items_back 
                                                                LEFT JOIN cl_delivery_note ON cl_delivery_note.id = cl_delivery_note_items_back.cl_delivery_note_id 
                                                                WHERE cl_delivery_note_items_back.cl_pricelist_id = cl_pricelist.id AND cl_delivery_note.issue_date >= ?)', $values['date_to']);
            //cl_invoice
            $tmpData2 = $tmpData2->where('NOT EXISTS(SELECT cl_invoice_items.id FROM cl_invoice_items 
                                                                LEFT JOIN cl_invoice ON cl_invoice.id = cl_invoice_items.cl_delivery_note_id 
                                                                WHERE cl_invoice_items.cl_pricelist_id = cl_pricelist.id AND cl_invoice.inv_date >= ?)', $values['date_to']);

            //cl_invoice_items_back
            $tmpData2 = $tmpData2->where('NOT EXISTS(SELECT cl_invoice_items_back.id FROM cl_invoice_items_back 
                                                                LEFT JOIN cl_invoice ON cl_invoice.id = cl_invoice_items_back.cl_delivery_note_id 
                                                                WHERE cl_invoice_items_back.cl_pricelist_id = cl_pricelist.id AND cl_invoice.inv_date >= ?)', $values['date_to']);


            $tmpData2->update(array('not_active_prep' => 1));
            //$this->database->commit();
            return;
        //} catch (Exception $e){
         //   $this->database->rollBack();
         //   return;
        //}

    }

    public function generateEAN($values){
        $tmpData = $this->findAll();
        $numResult = 0;
        try{
            //dump(count($values['cl_pricelist_group']));
            if (count($values['cl_pricelist_group']) > 0){
                $tmpData = $tmpData->where("cl_pricelist_group_id IN (?)", $values['cl_pricelist_group']);
            }
            if (count($values['cl_partners_book_id']) > 0){
                $tmpData = $tmpData->where("cl_partners_book_id IN (?)", $values['cl_partners_book_id']);
            }
            if (count($values['cl_producer_id']) > 0){
                $tmpData = $tmpData->where("cl_producer_id IN (?)", $values['cl_producer_id']);
            }
            if ($values['all_new'] == 0){
                $tmpData = $tmpData->where("ean_code = ?", "");
            }

            foreach($tmpData as $key => $one){
                $data = [];
                $data['id'] = $key;
                $newCode = $this->NumberSeriesManager->getNewNumber('pricelist_ean', $values['cl_number_series_id']);
                $tmpCode = $this->ean13_check_digit($newCode['number']);
                $data['ean_code'] = $tmpCode;
                $data['ean_old'] = $one['ean_code'];
                $this->update($data);
                $numResult++;
            }
        }
        catch (Exception $e) {
            Debugger::log('Chyba při generování EAN kódů. ' . $e->getMessage());
        }

        return $numResult;

    }


    public function restoreEAN($values){
        $tmpData = $this->findAll();
        $numResult = 0;
       try{
            if (count($values['cl_pricelist_group']) > 0){
                $tmpData = $tmpData->where("cl_pricelist_group_id IN (?)", $values['cl_pricelist_group']);
            }
            if (count($values['cl_partners_book_id']) > 0){
                $tmpData = $tmpData->where("cl_partners_book_id IN (?)", $values['cl_partners_book_id']);
            }
            if (count($values['cl_producer_id']) > 0){
                $tmpData = $tmpData->where("cl_producer_id IN (?)", $values['cl_producer_id']);
            }
            $tmpData = $tmpData->where("ean_old != ?", "");


            foreach($tmpData as $key => $one){
                $data = [];
                $data['id'] = $key;
                $data['ean_code'] = $one['ean_old'];
                $this->update($data);
                $numResult++;
            }
        }
         catch (Exception $e) {
             Debugger::log('Chyba při resetování EAN kódů. ' . $e->getMessage());
         }

        return $numResult;

    }


    function ean13_check_digit($digits){
//first change digits to a string so that we can access individual numbers
        $digits = str_split((string)$digits);
// 1. Add the values of the digits in the even-numbered positions: 2, 4, 6, etc.
        $even_sum = $digits[1] + $digits[3] + $digits[5] + $digits[7] + $digits[9] + $digits[11];
// 2. Multiply this result by 3.
        $even_sum_three = $even_sum * 3;
// 3. Add the values of the digits in the odd-numbered positions: 1, 3, 5, etc.
        $odd_sum = $digits[0] + $digits[2] + $digits[4] + $digits[6] + $digits[8] + $digits[10];
// 4. Sum the results of steps 2 and 3.
        $total_sum = $even_sum_three + $odd_sum;
// 5. The check character is the smallest number which, when added to the result in step 4,  produces a multiple of 10.
        $next_ten = (ceil($total_sum/10))*10;
        $check_digit = $next_ten - $total_sum;
        return $digits . $check_digit;
    }

    public function exciseDutyChange($values)
    {
        try {
            $this->database->beginTransaction();
            $tmpData = $this->findAll();
            if (count($values['cl_pricelist_group']) > 0) {
                $tmpData = $tmpData->where('cl_pricelist_group_id IN ?', $values['cl_pricelist_group']);
            }
            if (count($values['cl_partners_book']) > 0) {
                $tmpData = $tmpData->where('cl_partners_book_id IN ?', $values['cl_partners_book']);
            }
            if (count($values['cl_pricelist_categories']) > 0) {
                $tmpData = $tmpData->where('cl_pricelist_categories_id IN ?', $values['cl_pricelist_categories']);
            }

            $count = 0;
            $tmpRate = (float)$values['excise_rate'];
            foreach ($tmpData as $key => $one) {

                    $tmpUnit = $this->ArraysManager->getVolumeRatesToBase($one['volume_unit']);
                    $tmpVolume = $one['volume'] / $tmpUnit;
                    $new_excise_duty = $tmpRate * $tmpVolume * ($one['percent'] / 100);
                    $one->update(['excise_duty_old' => $one['excise_duty'],
                        'excise_duty' => $new_excise_duty]); // 'price_updated' => 1

                    $count++;
            }
            $this->database->commit();
            return $count;

        } catch (Exception $e){
            $this->database->rollBack();
            return 0;
        }

    }


    /**restore previous stored old prices  and set old prices to 0
     * @return int
     */
    public function restoreExciseDuty()
    {
        try {
            $this->database->beginTransaction();
            $tmpData = $this->findAll()->where('excise_duty_old > 0');
            $count = 0;
            foreach ($tmpData as $key => $one) {
                $one->update(['excise_duty' => $one['excise_duty_old']]);
                $count++;
            }
            $this->database->commit();
            return $count;

        } catch (Exception $e){
            $this->database->rollBack();
            return 0;
        }
    }



}

