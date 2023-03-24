<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Order management.
 */
class OrderManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_order';

	/** @var Nette\Database\Context */
	public $OrderItemsManager;			
	/** @var Nette\Database\Context */
	public $RatesVatManager;				
	/** @var Nette\Database\Context */
	public $PartnersManager;				
	/** @var Nette\Database\Context */
	public $NumberSeriesManager;	
	/** @var Nette\Database\Context */
	public $StatusManager;
    /** @var Nette\Database\Context */
    public $CompanyBranchManager;
	/** @var Nette\Database\Context */
	public $PriceListManager;
	/** @var Nette\Database\Context */
	public $PriceListLimitsManager;
	/** @var App\Model\Store */
	public $StoreManager;
    /** @var App\Model\CommissionItemsManager */
    public $CommissionItemsManager;
    /** @var App\Model\CommissionItemsSelManager */
    public $CommissionItemsSelManager;

    public $settings;
	
	/**
	   * @param Nette\Database\Connection $db
	   * @throws Nette\InvalidStateException
	   */
	  public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \Nette\Http\Session $session, \DatabaseAccessor $accessor,
					OrderItemsManager $OrderItemsManager, RatesVatManager $RatesVatManager, CompaniesManager $CompaniesManager,
					PartnersManager $PartnersManager, NumberSeriesManager $NumberSeriesManager, StatusManager $StatusManager,
                    CompanyBranchManager $CompanyBranchManager, PriceListManager $PriceListManager, PriceListLimitsManager $PriceListLimitsManager,
		  			StoreManager $StoreManager, CommissionItemsSelManager $CommissionItemsSelManager, CommissionItemsManager $CommissionItemsManager)
	  {
	      parent::__construct($db, $userManager, $user, $session, $accessor);
	      $this->OrderItemsManager	    = $OrderItemsManager;
	      $this->RatesVatManager	    = $RatesVatManager;
	      $this->PartnersManager	    = $PartnersManager;
	      $this->NumberSeriesManager    = $NumberSeriesManager;
	      $this->StatusManager	    	= $StatusManager;
          $this->CompanyBranchManager	= $CompanyBranchManager;
		  $this->PriceListManager	    = $PriceListManager;
		  $this->PriceListLimitsManager = $PriceListLimitsManager;
		  $this->StoreManager			= $StoreManager;
          $this->CommissionItemsManager = $CommissionItemsManager;
          $this->CommissionItemsSelManager = $CommissionItemsSelManager;
	      $this->settings		    	= $CompaniesManager->getTable()->fetch();

	  }    	
			
	  public function updateSum($id)
	  {
	    $price_e2 = $this->OrderItemsManager->findBy(['cl_order_id' => $id])->sum('price_e2');
	    $price_e2_vat = $this->OrderItemsManager->findBy(['cl_order_id' => $id])->sum('price_e2_vat');
        $price_e2_rcv = $this->OrderItemsManager->findBy(['cl_order_id' => $id])->sum('price_e_rcv * quantity_rcv');
        $price_e2_vat_rcv = $this->OrderItemsManager->findBy(['cl_order_id' => $id])->sum('(price_e_rcv * quantity_rcv) * (1 + (vat / 100))');
	    $parentData = [];
	    $parentData['id'] = $id;
	    $parentData['price_e2'] = $price_e2;
	    $parentData['price_e2_vat'] = $price_e2_vat;
        $parentData['price_e2_rcv'] = $price_e2_rcv;
        $parentData['price_e2_vat_rcv'] = $price_e2_vat_rcv;

        //06.12.2020 - update status
          //count delivered
          $cntNotDelivered = $this->OrderItemsManager->findAll()->where('cl_order_id = ? AND rea_date IS NULL', $id)->count('id');
        // bdump($cntNotDelivered);
          if ($cntNotDelivered == 0) {
              if ($nStatus = $this->StatusManager->findAll()->where('status_use = ? AND (s_delivered = 1 OR s_fin = 1)', 'order')->order('s_delivered')->fetch()) {
                  $parentData['cl_status_id'] = $nStatus->id;
              }
          }else{
              if ($nStatus = $this->StatusManager->findAll()->where('status_use = ? AND (s_work = 1 OR s_new = 1)', 'order')->order('s_work')->fetch()) {
                  $parentData['cl_status_id'] = $nStatus->id;
              }
          }
        //11.07.20201 - on store flag
        $cntNotOnStore = $this->OrderItemsManager->findAll()->where('cl_order_id = ? AND cl_store_move_id IS NULL AND cl_pricelist_id IS NOT NULL', $id)->count('id');
        $parentData['s_on_store'] = ($cntNotOnStore > 0) ? 0 : 1;
        //bdump($parentData, 'updateSum');
	    $this->update($parentData);
	    //die;
	  }

	  public function createOrder($dataMove, $od_title, $order_id = NULL, $arr_partners_book_id = array(NULL), $cl_storage_id = NULL, $cl_users_id = NULL)
	  {
	      $arrDocId = array();
	      if ($order_id != NULL){
		    $docId = $order_id;
	      }else{
		    $docId = NULL;
	      }
	      $count = 1;
          $arrCommission = array();
          //bdump($arr_partners_book_id);
          $cl_partners_book_id = NULL;
          foreach($arr_partners_book_id as $partners_book_id) {
              foreach ($dataMove as $key => $one) {
                  //28.08.2020 - if we have given cl_storage_id then storage_id of item has to be equal
                  //in this case we need order only items which are at given cl_storage_id
                  //if cl_storage_id is null then we order every given items
                  if (is_null($cl_storage_id) || $one['cl_storage_id'] == $cl_storage_id){
                      //bdump($one);
                      if (is_null($one['cl_partners_book_id']) || is_null($partners_book_id) ||
                            $one['cl_partners_book_id'] == $partners_book_id || $one['cl_partners_book_id2'] == $partners_book_id ||
                            $one['cl_partners_book_id3'] == $partners_book_id || $one['cl_partners_book_id4'] == $partners_book_id)
                        {
                            //14.04.2019 - supplier is different than in previous record, we are going to create another cl_doc_order

                            if (($one['cl_partners_book_id'] != $cl_partners_book_id && $one['cl_partners_book_id2'] != $cl_partners_book_id &&
                                $one['cl_partners_book_id3'] != $cl_partners_book_id && $one['cl_partners_book_id4'] != $cl_partners_book_id ) &&
                                 $one['quantity'] > 0)
                            /*if ($cl_partners_book_id != $one['cl_partners_book_id'] && $one['quantity'] > 0) */
                            {

                              //06.03.2020 we have no cl_order_docs created yet
                              //at first we have to update sum of previous created cl_doc_order
                              //and insert items from previous orders which still are not delivered as reminder
                              if (!is_null($cl_partners_book_id) && $docId != NULL) {
                                  $this->createOrderItemReminder($cl_partners_book_id, $docId, $count, $one['cl_storage_id'], $cl_users_id);
                              }

                              $cl_partners_book_id = $one['cl_partners_book_id'];
                              if (!is_null($cl_partners_book_id) && $order_id == NULL) {
                                  $docId = $this->createOrderDoc($cl_partners_book_id, $od_title, $one['cl_storage_id'], $cl_users_id);

                                  $arrDocId[] = $docId;
                              } else {
                                  $docId = $order_id;
                              }
                              $count = 1;
                          } elseif ($cl_partners_book_id == NULL && $order_id == NULL && $docId == NULL) {
                              $cl_partners_book_id = $one['cl_partners_book_id'];
                              //solution for create order without supplier from commission
                              $docId = $this->createOrderDoc($cl_partners_book_id, $od_title, $one['cl_storage_id'], $cl_users_id);
                              $arrDocId[] = $docId;
                          }

                          if ($docId != NULL && $one['quantity'] > 0) {
                              $rowId = $this->createOrderItem($one, $docId, $count);
                              //bdump($one->cl_commission->cm_number);
                              if (is_object($one)){
                                  $tableName = $one->getTable()->getName();
                                  if (isset($one['cl_commission_id']) && !($tableName == 'cl_commission_items_sel')) {
                                      //cl_commission_items
                                      $arrCommission[$one->cl_commission->cm_number] = $one->cl_commission->cm_number;
                                      //24.07.2020 - update cl_commission_items_sel->cl_order_items_id
                                      //bdump($one->getTable()->getName());
                                      //die;
                                      $this->CommissionItemsManager->find($one['id'])->update(array('cl_order_items_id' => $rowId));
                                  }elseif (isset($one['cl_commission_id']) && $tableName == 'cl_commission_items_sel') {
                                      //cl_commission_sel_items
                                      $arrCommission[$one->cl_commission->cm_number] = $one->cl_commission->cm_number;
                                      //24.07.2020 - update cl_commission_items->cl_order_items_id
                                      $this->CommissionItemsSelManager->find($one['id'])->update(array('cl_order_items_id' => $rowId));
                                  }
                              }
                              //cl_commission_items
                              $count++;
                          }
                      }
                  }
              }

              if (!is_null($cl_partners_book_id)) {
                  $docId = $this->createOrderItemReminder($cl_partners_book_id, $docId, $count, $one['cl_storage_id'], $cl_users_id);
                  $arrDocId[] = $docId;
              }
              //update for last created order
              //bdump($arrDocId, 'arrDocId');
              //bdump($arrCommission);
              foreach($arrDocId as $docId) {
                  //bdump($docId);
                  //bdump($docId != NULL);
                  if ($docId != NULL) {

                      $this->updateSum($docId);
                      $this->update(array('id' => $docId, 'com_numbers' => implode(", ", $arrCommission)));
                  }
              }
          }
          //die;
	      return $arrDocId;
			
	  }
	  
	  
	  private function createOrderDoc($cl_partners_book_id, $od_title, $cl_storage_id = NULL, $cl_users_id = NULL)
	  {
            $arrOrder = array();
            //cl_company_branch and cl_storage_id
            if (!is_null($cl_storage_id)) {
                //30.09.2019 -  cl_store_docs.cl_company_branch_id according to destination cl_company_branch_id of selected cl_storage_id
                if ($tmpBranch = $this->CompanyBranchManager->findAll()->where('cl_storage_id = ?', $cl_storage_id)->limit(1)->fetch())
                    $cl_company_branch_id = $tmpBranch->id;
                else
                    $cl_company_branch_id = NULL;

                $arrOrder['cl_company_branch_id']   = $cl_company_branch_id;
                $arrOrder['cl_storage_id']          = $cl_storage_id;
            }else{

                $tmpCompanyBranchId = $this->user->getIdentity()->cl_company_branch_id;
                if (!is_null($tmpCompanyBranchId)) {
                    $arrOrder['cl_company_branch_id']   = $tmpCompanyBranchId;
                    if ($tmpBranch = $this->CompanyBranchManager->findAll()->where('id = ?', $tmpCompanyBranchId)->limit(1)->fetch())
                        $arrOrder['cl_storage_id']    = $tmpBranch->cl_storage_id;
                } else {
                    $arrOrder['cl_storage_id']    = $this->settings->cl_storage_id;
                }

               // $arrOrder['cl_storage_id']          = $this->settings->cl_storage_id;
            }
            $arrOrder['cl_users_id']    	    = $cl_users_id;
            $arrOrder['cl_currencies_id']	    = $this->settings->cl_currencies_id;
            $arrOrder['currency_rate']	        = $this->settings->cl_currencies->fix_rate;
            $arrOrder['cl_partners_book_id']    = $cl_partners_book_id;
            $arrOrder['od_date']		        = new \Nette\Utils\DateTime;
            $arrOrder['od_title']		        = $od_title;
            //new number
            $nSeries			                = $this->NumberSeriesManager->getNewNumberSeries(array('use' => 'order', 'table_key' => 'cl_number_series_id', 'table_number' => 'od_number'), NULL);
            $arrOrder['od_number']		        = $nSeries['od_number'];
            $arrOrder['cl_number_series_id']    = $nSeries['cl_number_series_id'];
            if (isset($nSeries['header_txt']) && !is_null($nSeries['header_txt']))
                $arrOrder['header_txt']             = $nSeries['header_txt'];
            if (isset($nSeries['footer_txt']) && !is_null($nSeries['footer_txt']))
                $arrOrder['footer_txt']             = $nSeries['footer_txt'];

            $tmpStatus = 'order';
            if ($nStatus= $this->StatusManager->findAll()->where('status_use = ? AND s_new = ?',$tmpStatus,1)->fetch())
                $arrOrder['cl_status_id'] = $nStatus->id;

            $row = $this->insert($arrOrder);

            $docId = $row->id;
            if (!is_null($arrOrder['cl_partners_book_id'])) {
                $this->PartnersManager->useHeaderFooter($docId, $arrOrder['cl_partners_book_id'], $this);
            }
            return($docId);
	  }

    /**
     * @param $dataMove
     * @param $docId
     * @param $count
     * @return int - inserted ID
     */
	  private function createOrderItem($dataMove, $docId, $count) : int
	  {
	      //bdump($dataMove);
	      $arrItem = array();
	      $arrItem['cl_order_id']	    = $docId;
	      $arrItem['item_order']	    = $count;
	      $tmpPriceList                 = $this->PriceListManager->find($dataMove['cl_pricelist_id']);
	      $arrItem['cl_pricelist_id']   = $dataMove['cl_pricelist_id'];
	      $arrItem['cl_storage_id']	    = (!is_null($dataMove['cl_storage_id']) ? $dataMove['cl_storage_id'] : $this->settings->cl_storage_id) ;
	      $arrItem['item_label']	    = $dataMove['item_label'];
	      if (isset($dataMove['note2'])) {
              $arrItem['note2'] = $dataMove['note2'];
          }
	      //if (!is_null($dataMove->cl_pricelist_id) && $dataMove->cl_pricelist->in_package > 0 && $this->settings->order_package == 0) {
		  if (!is_null($dataMove['cl_pricelist_id']) && $tmpPriceList->in_package > 0 && $this->settings->order_package == 0) {
              //$arrItem['quantity'] = round($dataMove->quantity / $dataMove->cl_pricelist->in_package,0);
			  $arrItem['quantity'] = round($dataMove['quantity'] / $tmpPriceList->in_package,0);
          }else{
              $arrItem['quantity'] = $dataMove['quantity'];
          }
	      if (!is_null($dataMove['cl_pricelist_id'])) {
	          //if ($dataMove->cl_pricelist->in_package > 1 && $this->settings->order_package == 0)
			  if ($tmpPriceList->in_package > 1 && $this->settings->order_package == 0) {
				  $arrItem['units'] = 'bal';
			  }else {
				  //$arrItem['units'] = $dataMove->cl_pricelist->unit;
				  $arrItem['units'] = $tmpPriceList->unit;
			  }
              //$arrItem['price_e'] = $dataMove->cl_pricelist->price_s;
			  $arrItem['price_e'] = $tmpPriceList->price_s;
              $arrItem['vat']	  = $tmpPriceList->vat;
          }else{
	          if (isset($dataMove['price_s'])) {
                  $arrItem['price_e'] = $dataMove['price_s'];
              }else{
                  $arrItem['price_e'] = $dataMove['price_e'];
              }
              $arrItem['units'] = $dataMove['units'];
	          /*13.04.2021 - set VAT for items without VAT on itself. e.g. cost items from commission without constraint on pricelist*/
	          if (isset($dataMove['cl_commission_id']) && !is_null($dataMove['cl_commission_id'])){
	              $arrItem['vat'] = $dataMove->cl_commission['vat'];
              }
          }

          $arrItem['price_e2'] = $arrItem['price_e'] * $arrItem['quantity'];

          //if (!is_null($dataMove->cl_pricelist_id)) {
		  if (!is_null($dataMove['cl_pricelist_id'])) {
              //$arrItem['price_e2_vat'] = $arrItem['price_e2'] * (1 + ($dataMove->cl_pricelist->vat / 100));
			  $arrItem['price_e2_vat'] = $arrItem['price_e2'] * (1 + ($tmpPriceList->vat / 100));
          }else{
              $arrItem['price_e2_vat'] = $arrItem['price_e2'];
          }
          //if (!is_null($dataMove->cl_pricelist_id) && $dataMove->cl_pricelist->price_s == 0 )
		  if (!is_null($dataMove['cl_pricelist_id']) && $tmpPriceList->price_s == 0 )
          {
              //if ($tmpPriceS = $dataMove->cl_pricelist->related('cl_store_move')->where('price_s > 0')->order('id DESC')->fetch())
			  if ($tmpPriceS = $tmpPriceList->related('cl_store_move')->where('price_s > 0')->order('id DESC')->fetch())
              {
                  $lastPriceS = $tmpPriceS->price_s;
              }else{
                  $lastPriceS = 0;
              }
              $arrItem['price_e_rcv'] = $lastPriceS;


          }else{
              //if (!is_null($dataMove->cl_pricelist_id)) {
			  if (!is_null($dataMove['cl_pricelist_id'])) {
                  //$arrItem['price_e_rcv'] = $dataMove->cl_pricelist->price_s;
				  $arrItem['price_e_rcv'] 	= $tmpPriceList->price_s;
                  //$arrItem['vat']		    = $dataMove->cl_pricelist->vat;
				  $arrItem['vat']		    = $tmpPriceList->vat;
              }else{
                  $arrItem['price_e_rcv'] = 0;

              }
          }

          //$arrItem['price_e_rcv']   = $dataMove->cl_pricelist->price_s;

	      
	      $arInserted = $this->OrderItemsManager->insert($arrItem);

		  return $arInserted->id;
	      
	  }
	  
	  /**
	   * Create addition to order with items which was not delivered yet
	   * @param type $cl_partners_book_id
	   * @param type $docId
	   * @param type $count
	   */
	  private function createOrderItemReminder($cl_partners_book_id, $docId, $count, $cl_storage_id, $cl_users_id = NULL)
      {
          if ($docId == NULL) {
              $docId = $this->createOrderDoc($cl_partners_book_id, 'Nedodáno z minulých objednávek', $cl_storage_id, $cl_users_id);
          }

          //14.04.2019 - find all items which are not delivered yet, not belong to current cl_order_id and their cl_order.cl_status.s_fin = 1
          $tmpData = $this->OrderItemsManager->findAll()->where('cl_order.cl_partners_book_id = ? AND quantity_rcv = 0 AND cl_order_id != ?', $cl_partners_book_id, $docId)->
                                where('cl_order.cl_status.s_fin = 1 AND reminder = 0');
          if (!is_null($cl_storage_id))
          {
              $tmpData->where('cl_order_items.cl_storage_id = ?', $cl_storage_id);
          }
	      //bdump($tmpData);

	      
	      if (count($tmpData->fetchAll()) > 0){
                $arrItem = array();
                $arrItem['cl_order_id']	    = $docId;
                $arrItem['item_order']	    = $count;
                $arrItem['item_label']	    = 'Nedodáno z minulých objednávek:';
                $this->OrderItemsManager->insert($arrItem);
                $count++;
	      }
	      foreach($tmpData as $key => $one)
	      {
	          $one2 = $one->toArray();
              $one2['note2'] = 'minule nedodáno';
              $this->createOrderItem($one2, $docId, $count);
              $one->update(array('reminder' => 1));
	      }
	      return $docId;
	  }
	
	
	/**Prepare data for order with take a look on cl_pricelist_limits especially quantity_req and quantity_min
	 * @param $dataMove
	 * @return mixed
	 */
	public function prepareDataMove($dataMove)
	{
	    $arrPartners = array();
        $cl_storage_id = 0;
		foreach($dataMove as $key => $one) {
            $tmpLimits = $this->StoreManager->findAll()->
                                            select('SUM(cl_store.quantity) AS quantity, cl_pricelist_limits.quantity_min, cl_pricelist_limits.quantity_req')->
                                            where('cl_pricelist_limits.cl_storage_id = ? AND cl_pricelist_limits.cl_pricelist_id = ?', $one['cl_storage_id'], $one['cl_pricelist_id'])->
                                            group('cl_store.cl_pricelist_id, cl_store.cl_storage_id')->
                                            fetch();
            $cl_storage_id = $one['cl_storage_id'];
            //bdump($one);
            //bdump($tmpLimits);
            if ($tmpLimits && ($tmpLimits->quantity <= $tmpLimits->quantity_min)) {
                $dataMove[$key]['quantity'] = $tmpLimits->quantity_req;
                $dataMove[$key]['old_quantity'] = 0;
            } elseif ($tmpLimits && (($tmpLimits->quantity > $tmpLimits->quantity_min) && $tmpLimits->quantity_min > 0)) {
                $dataMove[$key]['old_quantity'] = $dataMove[$key]['quantity'];
                $dataMove[$key]['quantity'] = 0;
            } else {
                $dataMove[$key]['old_quantity'] = $dataMove[$key]['quantity'];
            }
        }

        foreach($dataMove as $key => $one){
			//prepare partners array for next check
            if (array_key_exists($one['cl_partners_book_id'], $arrPartners )) {
                $tmpSum = $arrPartners[$one['cl_partners_book_id']]['sum'] + ($one['quantity'] * $one['price_s']) ;
            }else{
                $tmpSum = ($one['quantity'] * $one['price_s']);
            }
            $arrPartners[$one['cl_partners_book_id']]['id'] = $one['cl_partners_book_id'];
            $arrPartners[$one['cl_partners_book_id']]['sum'] = $tmpSum;
            $arrPartners[$one['cl_partners_book_id']][$key] = $one;
		}

		//bdump($dataMove, 'dataMove');
		//bdump($arrPartners, 'arrPartners');
		//die;
		//control if cl_partners_book.min_order limit is achieved
        foreach($dataMove as $key => $one){
           // echo($key . ' : cl_store_id: '. $one['cl_store_id']. '<br>');
            if (array_key_exists($one['cl_partners_book_id'], $arrPartners )) {
                $tmpPartner = $this->PartnersManager->find($one['cl_partners_book_id']);
                //echo($tmpPartner->min_order . ' > ' . $arrPartners[$one['cl_partners_book_id']]['sum'] . '<br>');
                if ($tmpPartner && $tmpPartner->min_order > $arrPartners[$one['cl_partners_book_id']]['sum']){
                    //echo('unset: '.$one['cl_store_id'].' -  '. $one['old_quantity'].'<br>');
                    //update cl_store.quantity_to_order for order in next run
                    $this->StoreManager->update(array('id' => $one['cl_store_id'], 'quantity_to_order' => new Nette\Database\SqlLiteral('quantity_to_order + ' . $one['old_quantity'])));
                    unset($dataMove[$key]);
                }
            }
        }
       // bdump($dataMove);
        //die;
        $this->StoreManager->updateSupplierSum($cl_storage_id);

		return $dataMove;
	}

    /** Fill missing price_s into cl_order_items
     * @return Exception
     */
    public function repairOrderPriceS()
    {
        try{
            session_write_close();
            //1. and 2.

            $this->database->beginTransaction();
            $data = $this->OrderItemsManager->findAll()->select('cl_order_items.id, cl_order_items.quantity, cl_order_items.vat, cl_pricelist_id, cl_pricelist.identification')->where('cl_order.cl_status.s_fin = 0 AND (cl_order_items.price_e = 0 OR cl_order_items.price_e_rcv = 0)');
            $count = 0;
            $maxCount = count($data);
            foreach($data as $key => $one) {
                $this->userManager->setProgressBar($count, $maxCount, $this->user->getId(), 'Objednávky');

                if (!is_null($one->cl_pricelist_id)){
                    //dump($key);
                    //dump($one);
                    $one->update(array('price_e' => $one->cl_pricelist->price_s,
                        'price_e_rcv' => $one->cl_pricelist->price_s,
                        'price_e2' => $one->cl_pricelist->price_s * $one->quantity,
                        'price_e2_vat' => ($one->cl_pricelist->price_s * $one->quantity) * (1 + ($one->vat / 100 ))
                    ));
                    //die;
                }
                $count++;

            }
            $this->database->commit(); // potvrzení
            $this->userManager->resetProgressBar($this->user->getId());

        }catch (\Exception $e){
            $this->database->rollback();
            //bdump($e);
            //$this->database->rollback(); // vrácení zpět
            return ($e);
            //$this->presenter->flashMessage($e->getMessage(),'danger');
        }
    }


    public function checkPrices($id)
    {
        $items = $this->OrderItemsManager->findAll()->where('cl_order_id = ? AND ROUND(price_e,0) < ROUND(price_e_rcv,0) AND quantity_rcv > 0 AND quantity > 0', $id)->count('id');
        if ($items > 0) {
            $strRet = '<p class="alert alert-danger" role="alert">Pozor! Rozdíl v cenách. Počet záznamů s chybou: '.$items.' </p>';
        }else{
            $strRet = "";
        }
        return($strRet);
    }

}

