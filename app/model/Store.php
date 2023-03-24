<?php
namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;
use Tracy\Debugger;
use Netpromotion\Profiler\Profiler;
use function GuzzleHttp\Psr7\parse_response;

/**
 * Store management.
 */
class StoreManager extends Base
{
    const COLUMN_ID = 'id';
    public $tableName = 'cl_store';

    /** @var App\Model\Base */
    public $PriceListManager;
	
	/** @var App\Model\PriceListLimitsManager */
	public $PriceListLimitsManager;

    /** @var App\Model\PriceListMacro */
    public $PriceListMacroManager;

    /** @var App\Model\Base */
    public $StoreMoveManager;

    /** @var App\Model\Base */
    public $StorageManager;

    /** @var App\Model\Base */
    public $StoreOutManager;

    /** @var App\Model\Base */
    public $StoreDocsManager;

    /** @var App\Model\Base */
    public $CompaniesManager;

    /** @var App\Model\Base */
    public $InvoiceItemsManager;

    /** @var App\Model\Base */
    public $InvoiceManager;

    /** @var App\Model\Base */
    public $InvoiceItemsBackManager;
	
	/** @var App\Model\Base */
	public $SaleItemsManager;
	
	/** @var App\Model\Base */
	public $DeliveryNoteItemsManager;

    /** @var App\Model\Base */
    public $DeliveryNoteItemsBackManager;

    /** @var App\Model\InvoiceArrivedManager */
    public $InvoiceArrivedManager;
    /** @var App\Model\PairedDocsManager */
    public $PairedDocsManager;
    /** @var App\Model\RatesVatManager */
    public $RatesVatManager;
    /** @var App\Model\PartnersManager */
    public $PartnersManager;
    /** @var App\Model\InvoiceTypesManager */
    public $InvoiceTypesManager;
    /** @var App\Model\StatusManager */
    public $StatusManager;
    /** @var App\Model\NumberSeriesManager */
    public $NumberSeriesManager;

    /** @var App\Model\DeliveryNoteInItemsBackManager */
    public $DeliveryNoteInItemsBackManager;

	public $settings;

    /**
     * @param Nette\Database\Connection $db
     * @throws Nette\InvalidStateException
     */
    public function __construct(\Nette\Database\Context $db, UserManager $userManager, \Nette\Security\User $user, \DatabaseAccessor $accessor,
                                StorageManager $StorageManager, \Nette\Http\Session $session,
                                StoreMoveManager $StoreMoveManager, StoreOutManager $StoreOutManager, StoreDocsManager $StoreDocsManager,
                                PriceListManager $PriceListManager, CompaniesManager $CompaniesManager, PriceListMacroManager $PriceListMacroManager,
                                InvoiceManager $InvoiceManager, InvoiceItemsManager $InvoiceItemsManager, InvoiceItemsBackManager $InvoiceItemsBackManager,
								SaleItemsManager $SaleItemsManager, DeliveryNoteItemsManager $DeliveryNoteItemsManager, PriceListLimitsManager $PriceListLimitsManager,
                                DeliveryNoteItemsBackManager $DeliveryNoteItemsBackManager, DeliveryNoteInItemsBackManager $DeliveryNoteInItemsBackManager,
                                InvoiceArrivedManager $InvoiceArrivedManager, PairedDocsManager $PairedDocsManager, RatesVatManager $RatesVatManager,
                                PartnersManager $PartnersManager, InvoiceTypesManager  $InvoiceTypesManager, StatusManager $StatusManager, NumberSeriesManager $NumberSeriesManager)
    {
        parent::__construct($db, $userManager, $user, $session, $accessor);

        $this->StoreMoveManager = $StoreMoveManager;
        $this->StoreDocsManager = $StoreDocsManager;
        $this->StoreOutManager = $StoreOutManager;
        $this->StorageManager = $StorageManager;
        $this->PriceListManager = $PriceListManager;
		$this->PriceListLimitsManager = $PriceListLimitsManager;
        $this->PriceListMacroManager = $PriceListMacroManager;
        $this->CompaniesManager = $CompaniesManager;
        $this->InvoiceManager = $InvoiceManager;
        $this->InvoiceItemsManager = $InvoiceItemsManager;
        $this->InvoiceItemsBackManager = $InvoiceItemsBackManager;
        $this->SaleItemsManager = $SaleItemsManager;
        //$this->DeliveryNoteManager = $DeliveryNoteManager;
        $this->DeliveryNoteItemsManager = $DeliveryNoteItemsManager;
        $this->DeliveryNoteItemsBackManager = $DeliveryNoteItemsBackManager;
        $this->DeliveryNoteInItemsBackManager = $DeliveryNoteInItemsBackManager;
        $this->InvoiceArrivedManager = $InvoiceArrivedManager;
        $this->PairedDocsManager = $PairedDocsManager;
        $this->RatesVatManager = $RatesVatManager;
        $this->PartnersManager = $PartnersManager;
        $this->InvoiceTypesManager = $InvoiceTypesManager;
        $this->StatusManager = $StatusManager;
        $this->NumberSeriesManager = $NumberSeriesManager;

        $this->settings = $CompaniesManager->getTable()->fetch();

    }


    /**
     *
     * @param array $line
     * @param array $tmpData
     * @param array $data
     * $line = array(cl_store_id, cl_pricelist_id)
     * $tmpData = used in case of calling from GiveInStore and GiveOutStoreCore
     * $data = used in case of calling from GiveInStore and GiveOutStoreCore
     */
    public function updateStore($line, $tmpData = NULL, $data = NULL, $usedMoves = array())
    {
        //update quantity on cl_store
	    //08.12.2019 - again update cl_store because method UpdateStore is not working with current data record
	   
		    if (!isset($line['cl_company_id'])) {
				$tmpQuantity = $this->StoreMoveManager->findBy(['cl_pricelist_id' => $line['cl_pricelist_id'],
														    'cl_store_id' => $line['cl_store_id']]);
			    $tmpSout = $this->StoreOutManager->findAll()->
													    where(['cl_store_id' => $line['cl_store_id']])->sum('s_out');
			    $tmpSoutMin = $this->StoreMoveManager->findAll()->
													    where(['cl_store_id' => $line['cl_store_id']]);
		    } else {
			    $tmpQuantity = $this->StoreMoveManager->findAllTotal()->
													    where('cl_store_move.cl_company_id = ?', $line['cl_company_id'])->
													    where(['cl_pricelist_id' => $line['cl_pricelist_id'],
														    'cl_store_id' => $line['cl_store_id']]);
			    $tmpSout = $this->StoreOutManager->findAllTotal()->
														    where('cl_store_out.cl_company_id = ?', $line['cl_company_id'])->
														    where(['cl_store_id' => $line['cl_store_id']])->sum('s_out');
			    $tmpSoutMin = $this->StoreMoveManager->findAllTotal()->
														    where('cl_store_move.cl_company_id = ?', $line['cl_company_id'])->
														    where(['cl_store_id' => $line['cl_store_id']]);
		    }
	        if (!is_null($tmpData)) {
	        	$tmpQuantity = $tmpQuantity->where('id != ?', $tmpData['id']);
		        $tmpSoutMin =  $tmpSoutMin->where('id != ?', $tmpData['id']);
	        }
		    $tmpQuantity =  $tmpQuantity->sum('s_in');
	        $tmpSoutMin =  $tmpSoutMin->sum('s_out - s_out_fin');
	        
		    if (isset($data['s_in']) && $data['cl_store_id'] == $line['cl_store_id']) {
			    $tmpQuantity = $tmpQuantity + $data['s_in'];
		    }

		    if (isset($data['s_out']) && $data['cl_store_id'] == $line['cl_store_id']) {
			    $tmpQuantity = $tmpQuantity - $tmpSout - ($data['s_out'] - $data['s_out_fin']) - $tmpSoutMin;
		    } else {
			    $tmpQuantity = $tmpQuantity - $tmpSout - $tmpSoutMin;
		    }
			$storeData = array();
		    $storeData['id'] = $line['cl_store_id'];
		    $storeData['quantity'] = $tmpQuantity;
		    $this->updateForeign($storeData);
     

            //update quantity on cl_pricelist
            if (!isset($line['cl_company_id']))
                $quantity = $this->StoreMoveManager->findBy(['cl_pricelist_id' => $line['cl_pricelist_id']])->sum('s_in - s_out');
            else
                $quantity = $this->StoreMoveManager->findAllTotal()->where(['cl_company_id' => $line['cl_company_id'],
                    'cl_pricelist_id' => $line['cl_pricelist_id']])->sum('s_in - s_out');

            $arrUpdate = [];
            $arrUpdate['id'] = $line['cl_pricelist_id'];
            $arrUpdate['quantity'] = $quantity;
            if (!isset($line['cl_company_id']))
                $this->PriceListManager->update($arrUpdate);
            else {
                $arrUpdate['cl_company_id'] = $line['cl_company_id'];
                $this->PriceListManager->updateForeign($arrUpdate);
            }


            //check all income moves and again sum their outcomes
            if (!isset($line['cl_company_id']))
                $tmpStoreMoveIn = $this->StoreMoveManager->findBy(['cl_store_id' => $line['cl_store_id'], 'cl_pricelist_id' => $line['cl_pricelist_id']])->where('s_in > 0')->
													order('cl_store_docs.doc_type ASC, cl_store_docs.doc_date ASC, cl_store_move.id');
            else
                $tmpStoreMoveIn = $this->StoreMoveManager->findAllTotal()->where(['cl_store_move.cl_company_id' => $line['cl_company_id'],
																						'cl_store_id' => $line['cl_store_id'],
																						'cl_pricelist_id' => $line['cl_pricelist_id']])->where('s_in > 0')->
																			order('cl_store_docs.doc_type ASC, cl_store_docs.doc_date ASC, cl_store_move.id');
	
			if (count($usedMoves) > 0){
			    $tmpStoreMoveIn->where('cl_store_move.id IN ?', $usedMoves);
            }

            $tmpTotal = 0;
            foreach ($tmpStoreMoveIn as $one) {
                if (!isset($line['cl_company_id']))
                    $quantity = $this->StoreOutManager->findBy(['cl_store_move_in_id' => $one->id])->sum('s_out');
                else
                    $quantity = $this->StoreOutManager->findAllTotal()->where(['cl_store_out.cl_company_id' => $line['cl_company_id'],
                        'cl_store_move_in_id' => $one->id])->sum('s_out');

                $arrUpdate = [];
                $arrUpdate['id'] = $one->id;
                $arrUpdate['s_end'] = $one->s_in - $quantity;
				$arrUpdate['s_total'] = $tmpTotal + $one->s_in;
                //Debugger::log('cl_store_move_id ' . $one['id'] );
			//	Debugger::log('s_end ' . $arrUpdate['s_end'] );
               // Debugger::log('s_total ' . $arrUpdate['s_total'] );
                if (!isset($line['cl_company_id']))
                    $this->StoreMoveManager->update($arrUpdate);
                else {
                    $arrUpdate['cl_company_id'] = $line['cl_company_id'];
                    $this->StoreMoveManager->updateForeign($arrUpdate);
                }
				$tmpTotal += $arrUpdate['s_end'];
            }
	
	



    }


    /**
     * Called from api
     * @param type $data - array('identification','quantity','price','storage_name','cl_store_docs_id','description','cl_company_id')
     */
    public function ApiGiveOut($dataApi)
    {
        $data = array();

        //find item in pricelist
        if ($tmpPricelist = $this->PriceListManager->findAllTotal()->
											where('cl_company_id = ?', $dataApi['cl_company_id'])->
											where(array('identification' => $dataApi['identification']))->fetch())
        {
            $data['cl_pricelist_id'] = $tmpPricelist->id;

            //find storage
            if ($tmpStorage = $this->StorageManager->findAllTotal()->
													where('cl_company_id = ?', $dataApi['cl_company_id'])->
													where(array('name' => $dataApi['storage_name']))->fetch())
            {
                $data['cl_storage_id'] = $tmpStorage->id;
            } else {
                $data['cl_storage_id'] = NULL;
            }
            $data['s_out'] = $dataApi['quantity'];
            $data['create_by'] = 'automat';

            //find cl_store_docs
            if ($tmpParentData = $this->StoreDocsManager->findAllTotal()->
															where('cl_company_id = ?', $dataApi['cl_company_id'])->
															where(array('id' => $dataApi['cl_store_docs_id']))->fetch())
            {
                //create record in cl_store_move
                $arrData = array();
                $arrData['create_by'] = 'automat';
                $arrData['cl_company_id'] = $dataApi['cl_company_id'];
                $arrData['cl_store_docs_id'] = $dataApi['cl_store_docs_id'];
                $arrData['cl_pricelist_id'] = $tmpPricelist->id;
                $arrData['item_order'] = $this->StoreMoveManager->findAllTotal()->
																	where('cl_company_id = ?', $dataApi['cl_company_id'])->
																	where('cl_store_docs_id = ?', $arrData['cl_store_docs_id'])->max('item_order') + 1;

                //outgoing
                $arrData['s_out'] = 0;
                if (is_null($dataApi['price'])) {
                    $arrData['price_e'] = $tmpPricelist['price'] * $tmpPricelist->cl_currencies->fix_rate / $tmpParentData->currency_rate;
                    $arrData['price_e2'] = $tmpPricelist['price'] * $tmpPricelist->cl_currencies->fix_rate / $tmpParentData->currency_rate;
                    $arrData['price_e2_vat'] = ($tmpPricelist['price'] * $tmpPricelist->cl_currencies->fix_rate / $tmpParentData->currency_rate) * (1 + ($tmpPricelist['vat'] / 100));
                    $arrData['price_s'] = $tmpPricelist->price_s * $tmpPricelist->cl_currencies->fix_rate / $tmpParentData->currency_rate;
                } else {
                    $arrData['price_e'] = $dataApi['price'];
                    $arrData['price_e2'] = $dataApi['price'];
                    $arrData['price_e2_vat'] = ($dataApi['price']) * (1 + ($tmpPricelist['vat'] / 100));
                    $arrData['price_s'] = $tmpPricelist->price_s * $tmpPricelist->cl_currencies->fix_rate / $tmpParentData->currency_rate;
                }
                $arrData['vat'] = $tmpPricelist['vat'];
                $arrData['cl_storage_id'] = $tmpParentData->cl_storage_id;
                $arrData['description'] = $dataApi['description'];

                $row = $this->StoreMoveManager->insertForeign($arrData);
                $data['id'] = $row->id;
                $data['price_e'] = $arrData['price_e'];

                //make giveout
                $dataMove = $this->GiveOutStore($data, $row, $tmpParentData, $dataApi['cl_company_id']);
                $row->update($dataMove);

                //update of parent record
                $this->UpdateSum($dataApi['cl_store_docs_id']);

                $retMin = 1;
                //check if total amount is not under minimum
                if ($tmpMin = $this->findAllTotal()->where(array('cl_company_id' => $dataApi['cl_company_id'],
                    'cl_storage_id' => $data['cl_storage_id'],
                    'cl_pricelist_id' => $data['cl_pricelist_id']))->fetch()) {
                    $quantity_min = (!is_null($tmpMin->cl_pricelist_limits_id) ? $tmpMin->cl_pricelist_limits->quantity_min : 0);
                    $retMin = $tmpMin->quantity - $quantity_min;
                    if ($retMin <= 0 && $quantity_min > 0)
                        $arrRet = array(-1, 'Zůstatek je pod požadovaným minimem: ' . $tmpMin->quantity, $row->id);
                    else
                        $arrRet = array(1, 'Odepsáno, nový zůstatek je: ' . $tmpMin->quantity, $row->id);
                } else {
                    $arrRet = array(1, 'Odepsáno.', $row->id);
                }


                return $arrRet;
            }
        } else {
            return array(9999, 'Neodepsáno, položka nebyla nalezena.', NULL);
        }
    }


    /**
     * GiveOut from store with processing macro items
     * @param type $data - StoreMove - actual data with cl_storage_id, s_out, id, price_e
     * @param type $tmpData - StoreMove - data previously stored in table
     * @param type $tmpParentData - StoreDocs
     * @return int
     */
    public function GiveOutStore($data, $tmpData, $tmpParentData, $cl_company_id = NULL)
    {
        try {
            //02.02.2019 - take a look at macro in pricelist
            //if there is macro content, we have to giveout maco items on their own docs and then main item
            //
            if (is_null($cl_company_id)) {
                $macroItems = $this->PriceListMacroManager->findAll()->where('cl_pricelist_macro_id = ?', $tmpData->cl_pricelist_id)->fetchAll();
            } else {
                $macroItems = $this->PriceListMacroManager->findAllTotal()->where('cl_pricelist_macro_id = ? AND cl_company_id = ?', $tmpData->cl_pricelist_id, $cl_company_id)->fetchAll();
            }

            if ($macroItems) {
                //10.03.2019 - new check if item is on store and in what quantity
                //if is enough on store don`t make new item, use the old one

                if (is_null($cl_company_id)) {
                    $tmpStore = $this->findOneBy(array('cl_pricelist_id' => $tmpData['cl_pricelist_id'],
                        'cl_storage_id' => $data['cl_storage_id']));

                    $tmpS_end = $this->StoreMoveManager->findBy(array('cl_store_id' => $tmpStore['id']))->
                    where('s_in > 0 AND s_end > 0')->
                    sum('s_end');
                } else {
                    $tmpStore = $this->findAllTotal()->
                    where('cl_company_id = ?', $cl_company_id)->
                    where(array('cl_pricelist_id' => $tmpData['cl_pricelist_id'],
                        'cl_storage_id' => $data['cl_storage_id']))->limit(1)->fetch();

                    $tmpS_end = $this->StoreMoveManager->findAllTotal()->
                    where('cl_store_move.cl_company_id = ?', $cl_company_id)->
                    where(array('cl_store_id' => $tmpStore['id']))->
                    where('s_in > 0 AND s_end > 0')->
                    sum('s_end');
                }

                //check if on storage is enough quantity - if no make them
                $toMake = $data['s_out'] - $tmpS_end;
                if ($toMake > 0) {


                    //$toOut = $data->s_out - $tmpData['s_out'] ; //we have to work with difference to previous stored value
                    //there are some items, we have to make cl_store_docs record or found previous used
                    if (!is_null($tmpData['cl_store_docs_macro_id'])) {
                        if (is_null($cl_company_id)) {
                            $macroStoreDocs = $this->StoreDocsManager->findAll()->where('id = ?', $tmpData['cl_store_docs_macro_id'])->fetch();
                        } else {
                            $macroStoreDocs = $this->StoreDocsManager->findAllTotal()->where('id = ? AND cl_company_id = ?', $tmpData['cl_store_docs_macro_id'], $cl_company_id)->fetch();
                        }
                    } else {
                        $macroStoreDocs = FALSE;
                    }

                    if (is_null($cl_company_id)) {
                        $tmpStorage = $this->StorageManager->find($this->settings->cl_storage_id_macro);
                    } else {
                        $tmpStorage = $this->StorageManager->findAllTotal()->where('id = ? AND cl_company_id = ?', $this->settings->cl_storage_id_macro, $cl_company_id)->fetch();
                    }

                    if ($tmpStorage) {
                        $tmpStorageName = $tmpStorage->name;
                    } else {
                        $tmpStorageName = "";
                    }


                    if (!$macroStoreDocs) {

                        //there is no storeDocs for macro items, let's create one
                        $dataApi = array('cl_company_id' => $tmpData['cl_company_id'],
                            'doc_date' => $tmpParentData['doc_date'],
                            'doc_title' => 'makro doklad: ' . $tmpParentData['doc_number'] . ', výrobek: ' . $tmpData->cl_pricelist->identification . ' ' . $tmpData->cl_pricelist->item_label,
                            'create_by' => 'macro',
                            'doc_type' => 'store_out',
                            'cl_partners_name' => NULL,
                            'currency_code' => $tmpParentData->cl_currencies->currency_code,
                            'storage_name' => $tmpStorageName);

                        $macroStoreDocs = $this->StoreDocsManager->ApiCreateDoc($dataApi);
                        $data['cl_store_docs_macro_id'] = $macroStoreDocs->id;
                    }
                    //bdump($macroStoreDocs);
                    //bdump($macroItems);
                    $totalPrice_s = 0;
                    foreach ($macroItems as $one) {
                        //each macroitem should be found in cl_store_move for actual cl_store_docs_macro
                        //if there is not, we have to made new item in cl_store_move and than procces GiveOutStoreCore
                        if (is_null($cl_company_id)) {
                            $tmpMacroMoves = $this->StoreMoveManager->findAll()->
                            where('cl_store_docs_id = ? AND cl_pricelist_id = ? AND cl_pricelist_macro_id = ?',
                                $macroStoreDocs->id, $one->cl_pricelist_id, $one->id)->fetch();
                        } else {
                            $tmpMacroMoves = $this->StoreMoveManager->findAllTotal()->
                            where('cl_store_docs_id = ? AND cl_pricelist_id = ? AND cl_pricelist_macro_id = ? AND cl_company_id = ?',
                                $macroStoreDocs->id, $one->cl_pricelist_id, $one->id, $cl_company_id)->fetch();
                        }
                        if (!$tmpMacroMoves) {
                            $dataApi = array('identification' => $this->PriceListManager->findAllTotal()->where('id = ?', $one->cl_pricelist_id)->fetch()->identification,
                                'quantity' => $this->PriceListMacroManager->getQuantity($one->id, $toMake),
                                'price' => 0,
                                'storage_name' => $tmpStorageName,
                                'cl_store_docs_id' => $macroStoreDocs->id,
                                'description' => '',
                                'cl_company_id' => $tmpData['cl_company_id']);
                            //'description'		=> 'makro složka '.$tmpParentData['doc_number'].' '.$tmpData->cl_pricelist->identification.' '.$tmpData->cl_pricelist->item_label,
                            $row = $this->ApiGiveOut($dataApi);
                            $this->StoreMoveManager->update(array('id' => $row[2], 'cl_pricelist_macro_id' => $one->id));
                        } else {
                            $dataMacro = $tmpMacroMoves->toArray();
                            $dataMacro['s_out'] = $this->PriceListMacroManager->getQuantity($one->id, $toMake);
                            $dataMacro['cl_pricelist_macro_id'] = $one->id;
                            //bdump($dataMacro,'dataMacro');
                            //bdump($tmpMacroMoves,'tmpMacroMoves');
                            $dataReturn = $this->GiveOutStoreCore($dataMacro, $tmpMacroMoves, $macroStoreDocs, $cl_company_id);
                            $this->StoreMoveManager->update($dataReturn);

                            //$totalPrice_s = $totalPrice_s + ($dataReturn['price_s']*$dataReturn['s_out']);
                        }
                    }
                    $totalPrice_s = $this->StoreMoveManager->findAll()->where('cl_store_docs_id = ? ',
                        $macroStoreDocs->id)->sum('price_s*s_out');
                    //now update $macroStoreDocsIn
                    $this->UpdateSum($macroStoreDocs['id']);


                    //04.02.2019 - income - find or create cl_store_docs for product
                    ////
                    if (is_null($cl_company_id)) {
                        $tmpStorage = $this->StorageManager->find($data['cl_storage_id']);
                    } else {
                        $tmpStorage = $this->StorageManager->findAllTotal()->where('id = ? AND cl_company_id = ?', $data['cl_storage_id'], $cl_company_id)->fetch();
                    }
                    if ($tmpStorage) {
                        $tmpStorageNameIn = $tmpStorage->name;
                    } else {
                        $tmpStorageNameIn = "";
                    }

                    if (!is_null($tmpData['cl_store_docs_macro_in_id'])) {
                        if (is_null($cl_company_id)) {
                            $macroStoreDocsIn = $this->StoreDocsManager->findAll()->where('id = ?', $tmpData['cl_store_docs_macro_in_id'])->fetch();
                        } else {
                            $macroStoreDocsIn = $this->StoreDocsManager->findAllTotal()->where('id = ? AND cl_company_id = ?', $tmpData['cl_store_docs_macro_in_id'], $cl_company_id)->fetch();
                        }
                    } else {
                        $macroStoreDocsIn = FALSE;
                    }
                    if (!$macroStoreDocsIn) {
                        //there is no storeDocs for macro items, let's create one
                        $dataApi = array('cl_company_id' => $tmpParentData['cl_company_id'],
                            'doc_date' => $tmpParentData['doc_date'],
                            'doc_title' => 'naskladnění výrobku z dokladu: ' . $macroStoreDocs['doc_number'],
                            'create_by' => 'macro',
                            'doc_type' => 'store_in',
                            'cl_partners_name' => NULL,
                            'currency_code' => $tmpParentData->cl_currencies->currency_code,
                            'storage_name' => $tmpStorageNameIn);

                        $macroStoreDocsIn = $this->StoreDocsManager->ApiCreateDoc($dataApi);
                        $data['cl_store_docs_macro_in_id'] = $macroStoreDocsIn->id;
                    }

                    //04.02.2019 - now we have to give in to store macro product
                    if (is_null($cl_company_id)) {
                        $tmpMacroInMoves = $this->StoreMoveManager->findAll()->
                        where('cl_store_docs_id = ? AND cl_pricelist_id = ? ',
                            $macroStoreDocsIn->id, $tmpData['cl_pricelist_id'])->fetch();
                    } else {
                        $tmpMacroInMoves = $this->StoreMoveManager->findAllTotal()->
                        where('cl_store_docs_id = ? AND cl_pricelist_id = ? AND cl_company_id = ?',
                            $macroStoreDocsIn->id, $tmpData['cl_pricelist_id'], $cl_company_id)->fetch();
                    }
                    //bdump($totalPrice_s,'totalPrice_s');
                    if (!$tmpMacroInMoves) {
                        //macroInMove doesnot exist yet
                        $dataApi = array('identification' => $this->PriceListManager->findAllTotal()->where('id = ?', $tmpData->cl_pricelist_id)->fetch()->identification,
                            'quantity' => $toMake,
                            'price_in' => $totalPrice_s,
                            'price' => $totalPrice_s,
                            'storage_name' => $tmpStorageNameIn,
                            'cl_store_docs_id' => $macroStoreDocsIn->id,
                            'description' => '',
                            'cl_company_id' => $tmpData['cl_company_id']);
                        //'description'		=> 'makro složka '.$tmpParentData['doc_number'].' '.$tmpData->cl_pricelist->identification.' '.$tmpData->cl_pricelist->item_label,
                        $row = $this->ApiGiveIn($dataApi);
                    } else {
                        //macroInMove exists, make update
                        $dataIn = $tmpMacroInMoves->toArray();
                        $dataIn['s_in'] = $toMake;
                        $dataIn['price_in'] = $totalPrice_s;
                        $dataIn['price'] = $totalPrice_s;

                        $dataReturn2 = $this->GiveInStore($dataIn, $tmpMacroInMoves, $macroStoreDocsIn, $cl_company_id);
                        $totalPrice_sVat = ($totalPrice_s * (1 + ($tmpData['vat'] / 100)));
                        $this->StoreMoveManager->update(array('id' => $tmpMacroInMoves['id'],
                            's_in' => $dataIn['s_in'],
                            'price_in' => $totalPrice_s,
                            'price_in_vat' => $totalPrice_sVat));
                        //now update $macroStoreDocsIn
                        $this->UpdateSum($macroStoreDocsIn['id']);
                    }


                } //$toMake>0 END
            }


            //at the end main item as usual
            $dataReturn = $this->GiveOutStoreCore($data, $tmpData, $tmpParentData, $cl_company_id);

            return $dataReturn;

        } catch (Exception $e) {
            Debugger::log($e, Debugger::ERROR);
        }

    }

    /**GiveOut of one item from store
     *
     */
    public function GiveOutStoreCore($data, $tmpData, $tmpParentData, $cl_company_id = NULL)
    {
        if (is_null($tmpData['cl_pricelist_id'])) {
            return $data;
        }

        try {
            //odpis ze skladu

            //1. najdeme zásobu, pokud ještě neexistuje, vytvoříme ji
            //if (!$tmpStore = $this->findOneBy(array('cl_pricelist_id' => $tmpData['cl_pricelist_id'],
            //
			//				 'cl_storage_id' => $data['cl_storage_id'])))
            $data['minus'] = 0;
			$usedStores = [];
            $usedMoves = [];
			//bdump($tmpData['cl_store_id'],'$tmpData[cl_store_id]');
            if (is_null($cl_company_id)) {
                $tmpStore = $this->findAll()->where('(cl_pricelist_id = ? AND cl_storage_id = ? AND quantity != 0) OR id = ?',
                                                            $tmpData['cl_pricelist_id'], $data['cl_storage_id'], (is_null($tmpData['cl_store_id']) ? 0 : $tmpData['cl_store_id']) )->
                                                order('exp_date ASC')->limit(1)->fetch();
            } else {
                $tmpStore = $this->findAllTotal()->
                                        where('cl_company_id = ?', $cl_company_id)->
                                        where('(cl_pricelist_id = ? AND cl_storage_id = ? AND quantity != 0) OR id = ?',
                                            $tmpData['cl_pricelist_id'], $data['cl_storage_id'], (is_null($tmpData['cl_store_id']) ? 0 : $tmpData['cl_store_id']) )->
                                        order('exp_date ASC')->limit(1)->fetch();
            }

            if (!$tmpStore) {
                //14.11.2019 - looking for cl_store without cl_store_out - this is used for allready existing cl_store_move to minus
                /*if (is_null($cl_company_id)) {
                    $tmpStore = $this->findAll()->where('cl_pricelist_id = ? AND cl_storage_id = ?',
                                                    $tmpData['cl_pricelist_id'], $data['cl_storage_id'])->
                                                    group('cl_store.id')->
                                                    having('COUNT(:cl_store_out.s_out) = 0 OR COUNT(:cl_store_out.s_out) IS NULL')->
                                                    order('exp_date ASC')->fetch();

                } else {
                    $tmpStore = $this->findAllTotal()->
                                                where('cl_company_id = ?', $cl_company_id)->
                                                where('cl_pricelist_id = ? AND cl_storage_id = ? ',
                                                    $tmpData['cl_pricelist_id'], $data['cl_storage_id'])->
                                                group('cl_store.id')->
                                                having('COUNT(:cl_store_out.s_out) = 0 OR COUNT(:cl_store_out.s_out) IS NULL')->
                                                order('exp_date ASC')->limit(1)->fetch();
                }*/
				if (is_null($cl_company_id)) {
					$tmpStore = $this->findAll()->where('cl_pricelist_id = ? AND cl_storage_id = ?',
															$tmpData['cl_pricelist_id'], $data['cl_storage_id'])->
														order('exp_date ASC')->fetch();
				} else {
					$tmpStore = $this->findAllTotal()->
													where('cl_company_id = ?', $cl_company_id)->
													where('cl_pricelist_id = ? AND cl_storage_id = ?',
														$tmpData['cl_pricelist_id'], $data['cl_storage_id'])->
													order('exp_date ASC')->limit(1)->fetch();
				}
	

                $data['minus'] = 1;
            }
            ($tmpData) ? $usedStores[] = $tmpData['cl_store_id'] : false;
            ($tmpStore) ? $usedStores[] = $tmpStore->id : false;

            //bdump($usedStores, 'usedStores on beginning');
            
            if (!$tmpStore) {

                $storeData = new \Nette\Utils\ArrayHash;
                $storeData['cl_pricelist_id'] = $tmpData['cl_pricelist_id'];
                $storeData['cl_storage_id'] = $data['cl_storage_id'];
				$storeData['cl_pricelist_limits_id'] = $this->PriceListLimitsManager->getLimit($storeData['cl_pricelist_id'], $storeData['cl_storage_id'], $cl_company_id);
                $storeData['quantity'] = -$data['s_out'];
                if (!is_null($cl_company_id)) {
					$storeData['create_by'] = 'automat';
				}

                if (is_null($cl_company_id)) {
					$tmpStore = $this->insert($storeData);
				} else {
                    $storeData['cl_company_id'] = $cl_company_id;
                    $tmpStore = $this->insertForeign($storeData);
                    //$tmpData->update(array('cl_store_id' => $tmpStore['id']));
                }
                //bdump($storeData);
                //die;
				$usedStores[] = $tmpStore->id;
            } else {

            }
            $data['cl_store_id'] = $tmpStore['id'];
            //bdump($data);
            //die;

            //$toOut = $data['s_out'] - $tmpData['s_out']; //musime vydavat rozdil oproti ulozene hodnote
            //07.11.2020
            $toOut = $data['s_out'] - $tmpData['s_out_fin']; //musime vydavat rozdil oproti ulozene hodnote skutecně vydaného množství

            //bdump($toOut,'toOut');
            // new val -10 old val 10
            //then $toOut = -10 and next made income -10
            // 10 old -10
            //

            $toOutLeft = $toOut;
            //dump($toOut);
            //die;
            //Debugger::firelog($toOut);

            if ($toOut > 0 && $tmpData['s_out'] >= 0) {//výdej ze skladu
                //2. najdeme zůstatky v cl_store_move
                //dump($tmpStore['id']);
                if (is_null($cl_company_id))
                    //$tmpStoreMove = $this->StoreMoveManager->findBy(array('cl_store_id' => $tmpStore['id']))->
                    $tmpStoreMove = $this->StoreMoveManager->findBy(array('cl_store_move.cl_storage_id' => $tmpStore['cl_storage_id']))->
														where('s_in > 0 AND s_end > 0 AND cl_store_move.cl_pricelist_id = ?', $tmpStore['cl_pricelist_id'])->
														order('exp_date ASC, cl_store_doc.doc_date ASC');
                else
                    //                    where(array('cl_store_id' => $tmpStore['id']))->
                    $tmpStoreMove = $this->StoreMoveManager->findAllTotal()->
														where('cl_store_move.cl_company_id = ?', $cl_company_id)->
														where(array('cl_store_move.cl_storage_id' => $tmpStore['cl_storage_id']))->
														where('s_in > 0 AND s_end > 0 AND cl_store_move.cl_pricelist_id = ?', $tmpStore['cl_pricelist_id'])->
														order('exp_date ASC, cl_store_doc.doc_date ASC');


              //  if (count($tmpStoreMove) == 0) {
                    //14.11.2019 - giveout to minus value

              //  }else {
					$data['minus'] = 1;
                    foreach ($tmpStoreMove as $one) {
                        // bdump($one->s_end, 'one->s_end');
                        // bdump($toOut, 'toOut');
                        // bdump($toOutLeft, 'toOutLeft');
                        //die;
                        //if ($one->s_end >= $toOut) 01.06.2017
                        $usedStores[] = $one->cl_store_id;
                        $usedMoves[$one->id] = $one->id;
                        //  bdump($usedStores);
                        if ($one->s_end >= $toOutLeft) {
                            //na zasobe je dostatecny zustatek, pouzijeme ji pro vydej
                            $arrOut = new \Nette\Utils\ArrayHash;
                            //01.06.2017
                            $toOut = $toOutLeft;
                            $arrOut['s_end'] = $one->s_end - $toOut;
                            $one->update($arrOut);
                            $toOutLeft = 0;

                        } else {
                            //na zasobe je nedostatecny zustatek pouzijeme ji celou pro vydej
                            $toOut = $one->s_end;
                            $arrOut = new \Nette\Utils\ArrayHash;
                            $arrOut['s_end'] = 0;
                            $one->update($arrOut);
                            $toOutLeft = $toOutLeft - $toOut;
                        }


                        //vyhledame a pripadne zapiseme do cl_store_out
                        if (is_null($cl_company_id))
                            $tmpStoreOut = $this->StoreOutManager->findOneBy(array('cl_store_move_id' => $tmpData['id'],
                                'cl_store_move_in_id' => $one->id));
                        else {
                            $tmpStoreOut = $this->StoreOutManager->findAllTotal()->
														where('cl_store_out.cl_company_id = ?', $cl_company_id)->
														where(array('cl_store_move_id' => $tmpData['id'],
															'cl_store_move_in_id' => $one->id))->limit(1)->fetch();
                        }
                        // bdump($tmpStoreOut, 'tmpStoreOut');
                        if (!$tmpStoreOut) {//not found insert
                            $arrStoreOut = new \Nette\Utils\ArrayHash;
							if (!is_null($cl_company_id)) {
								$arrStoreOut['cl_company_id'] = $cl_company_id;
							}
                            $arrStoreOut['cl_store_move_id'] = $tmpData['id'];
                            $arrStoreOut['cl_store_move_in_id'] = $one->id;
                            $arrStoreOut['cl_store_id'] = $one['cl_store_id'];
                            $arrStoreOut['s_out'] = $toOut;
                            $arrStoreOut['s_total'] = $arrOut['s_end'];
                            //31.05.2017 - VAT x FiFo solution
                            //find storage and determine method for store price
                            if ($one->cl_storage->price_method == 0) {
                                $arrStoreOut['price_s'] = $one->price_s; //price from cl_store_move = FiFo
                            } else {

                                $arrStoreOut['price_s'] = $one->cl_store->price_s; //price from cl_store = VAP
                            }

                            if (!is_null($cl_company_id))
                                $arrStoreOut['create_by'] = 'automat';

                            if (is_null($cl_company_id))
                                $tmpStoreOut = $this->StoreOutManager->insert($arrStoreOut);
                            else {
                                $arrStoreOut['cl_company_id'] = $cl_company_id;
                                $tmpStoreOut = $this->StoreOutManager->insertForeign($arrStoreOut);
                            }

                        } else {//found update
                            $arrStoreOut = new \Nette\Utils\ArrayHash;
                            $arrStoreOut['s_out'] = $tmpStoreOut->s_out + $toOut;
							$arrStoreOut['s_total'] = $arrOut['s_end'];
                            //31.05.2017 - VAT x FiFo solution
                            //find storage and determine method for store price
                            if ($one->cl_storage->price_method == 0) {
                                $arrStoreOut['price_s'] = $one->price_s; //price from cl_store_move = FiFo
                            } else {

                                $arrStoreOut['price_s'] = $one->cl_store->price_s; //price from cl_store = VAP
                            }
                            // $arrStoreOut['price_s'] =  $one->price_s; //tady bude v budoucnu reseni VAT


                            $tmpStoreOut->update($arrStoreOut);
                        }

                        if ($toOutLeft <= 0) {
							$data['minus'] = 0;
                            break;
                        }
                    }
              //  }
            } elseif ($toOut <= 0 && $tmpData['s_out'] >= 0) {//vracíme na sklad
                //08.10.2018 only if is saved value bigger then 0. In other case it means that we must write income to store

                $toOutLeft = abs($toOutLeft);
                if (is_null($cl_company_id))
                    $tmpStoreOut = $this->StoreOutManager->findBy(['cl_store_move_id' => $tmpData['id']])->order('id DESC');
                else
                    $tmpStoreOut = $this->StoreOutManager->findAllTotal()->
												where('cl_store_out.cl_company_id = ?', $cl_company_id)->
												where(['cl_store_move_id' => $tmpData['id']])->order('id DESC');

                foreach ($tmpStoreOut as $one) {

                    $usedStores[]           = $one->cl_store_id;
                    $usedMoves[$one->id]    = $one->id;
                    //bdump($usedStores,'$usedStores');
                    //dump($one->s_out);
                    //dump($toOutLeft);
                    if ($one->s_out >= $toOutLeft) {//na aktualnim vydeji je dostatek k vraceni, vratime vse co potrebujeme
                        $arrStoreOut = new \Nette\Utils\ArrayHash;
                        $arrStoreOut['s_out'] = $one->s_out - $toOutLeft;
                        $arrStoreOut['s_total'] = $one->s_total + $toOutLeft;
                        $toOutLeft = 0;
                    } else {//na aktualnim vydeji neni dostatek k vraceni, vratime vse co je dostupne
                        $arrStoreOut = new \Nette\Utils\ArrayHash;
                        $arrStoreOut['s_out'] = $one->s_out - $one->s_out;
                        $arrStoreOut['s_total'] = $one->s_total + $one->s_out;
                        $toOutLeft = $toOutLeft - $one->s_out;
                    }
                    $one->update($arrStoreOut);
                    //musime zaktualizovat naskladneni v cl_store_move
                    if (is_null($cl_company_id))
                        $tmpParentStoreMove = $this->StoreMoveManager->findOneBy(['id' => $one->cl_store_move_in_id]);
                    else {
                        $tmpParentStoreMove = $this->StoreMoveManager->findAllTotal()->
													where('cl_store_move.cl_company_id = ?', $cl_company_id)->
													where(['id' => $one->cl_store_move_in_id])->limit(1)->fetch();
                    }
                    if ($tmpParentStoreMove) {
                        $arrStoreIn = new \Nette\Utils\ArrayHash;
                        if (is_null($cl_company_id))
                            $tmpQuantity = $this->StoreOutManager->findBy(['cl_store_move_in_id' => $tmpParentStoreMove->id])->
                            sum('s_out');
                        else
                            $tmpQuantity = $this->StoreOutManager->findAllTotal()->
															where('cl_store_out.cl_company_id = ?', $cl_company_id)->
															where(['cl_store_move_in_id' => $tmpParentStoreMove->id])->
															sum('s_out');

                        $arrStoreIn['s_end'] = $tmpParentStoreMove['s_in'] - $tmpQuantity;
                        $tmpParentStoreMove->update($arrStoreIn);
                    }

                    if ($toOutLeft <= 0)
                        break;
                }
                //die;
            } elseif ($toOut < 0 && $tmpData['s_out'] < 0) {//08.10.2018 - saved value is less then 0 and new value is also less then 0
                //we have to create new income of $toOut or update existing


            } elseif ($toOut >= 0 && $tmpData['s_out'] < 0) {//08.10.2018 - saved value is less then 0 and new value is bigger then 0
                //we have to

            }
	
			if (is_null($cl_company_id)) {
				$data['s_out_fin'] = $this->StoreOutManager->findBy(['cl_store_move_id' => $data['id']])->sum('s_out');
			}else{
				$data['s_out_fin'] = $this->StoreOutManager->findAllTotal()->
															where((['cl_store_move_id' => $data['id'], 'cl_company_id' => $cl_company_id]))->
															sum('s_out');
			}

            //aktualizace skladove ceny pro vydej
            //22.09.2022 - oprava výpočtu price_s aby i při částečném výdeji do mínusu byla price_s kompletní podle dostupných cen
            if ($data['s_out'] > 0) {
                if (is_null($cl_company_id)) {
                    $tmpPriceS = ($this->StoreOutManager->findBy(['cl_store_move_id' => $data['id']])->select('AVG(price_s) AS price_s')->fetch());
                        											//sum('s_out * price_s')) ; /// $data['s_out']

                    //	    dump($data['id']);
                    //	    dump($data['price_s']);
                } else {
                    $tmpPriceS = ($this->StoreOutManager->findAllTotal()->
																	where('cl_store_out.cl_company_id = ?', $cl_company_id)->
																	where(['cl_store_move_id' => $data['id']])->select('AVG(price_s) AS price_s')->fetch());
																	//sum('s_out*price_s')) / $data['s_out'];

                }
                if ( $tmpPriceS )
                {
                    $data['price_s'] = $tmpPriceS['price_s'];
                }else{
                    $data['price_s'] = 0;
                }
                //01.03.2020 - use price_s from pricelist if on cl_Store_out is nothing
                if ($data['price_s'] == 0)
                {
                    $data['price_s'] = $tmpData->cl_pricelist->price_s;
                }
            } else
                $data['price_s'] = 0;

            //nová hodnota zisku $data['price_e2']
            if ($data['price_e'] > 0) {
                if ($tmpParentData['currency_rate'] == 0)
                    $rate = 1;
                else
                    $rate = $tmpParentData['currency_rate'];

                $data['profit'] = 100 - (($data['price_s'] / ($data['price_e'] * $rate)) * 100);
            }else {
                $data['profit'] = 100;
            }
			$this->deleteStoreWM($tmpData->cl_pricelist_id, $cl_company_id, $tmpStore->id);
			//die;
            //zaktualizujeme hodnotu na zasobach, které byly použity pro výdej
            //Debugger::log('Start updateStore2 runtime(sec): ' . Debugger::timer('StoreManager') , 'StoreManager');
            //Debugger::log('Memory used(MB): '. memory_get_usage('StoreManager')/1024/1000, 'StoreManager');
			$this->updateStore2($usedStores,$tmpData, $data, $cl_company_id, $usedMoves);
            //Debugger::log('Finished updateStore2 runtime(sec): ' . Debugger::timer() , 'StoreManager');
            //Debugger::log('Memory used(MB): '. memory_get_usage('StoreManager')/1024/1000, 'StoreManager');
			//die;
            //aktualizujeme hodnotu v ceniku
            $tmpPriceList = new \Nette\Utils\ArrayHash;
            $tmpPriceList['id'] = $tmpData->cl_pricelist_id;

            if (is_null($cl_company_id))
                $tmpQuantity = $this->StoreMoveManager->findBy(array('cl_pricelist_id' => $tmpData->cl_pricelist_id))->
												where('id != ?', $tmpData['id'])->
												sum('s_in-s_out');
            else
				$tmpQuantity = $this->StoreMoveManager->findAllTotal()->
												where('cl_store_move.cl_company_id = ?', $cl_company_id)->
												where(array('cl_pricelist_id' => $tmpData->cl_pricelist_id))->
												where('id != ?', $tmpData['id'])->
												sum('s_in-s_out');

            $tmpPriceList['quantity'] = $tmpQuantity - $data['s_out'];

            if (is_null($cl_company_id))
                $this->PriceListManager->update($tmpPriceList);
            else {
                $tmpPriceList['cl_company_id'] = $cl_company_id;
                $this->PriceListManager->updateForeign($tmpPriceList);
            }

            //dump($tmpStoreMove);
            //die;

            //$tmpBalance = $this->StoreMoveManager->findBy(array('cl_pricelist_id' => $tmpData->cl_pricelist_id,
            //						     'cl_storage_id' => $data->cl_storage_id))->
            //						where('id != ? AND ', $tmpData['id'])->
            //						sum('s_in-s_out');
			//bdump($data,'ted');
			//die;
            return $data;
       } catch (Exception $e) {
            Debugger::log($e, Debugger::ERROR);
        }
    }
    
    
    public function updateStore2($usedStores,$tmpData, $data, $cl_company_id, $usedMoves = array())
	{
		//bdump($usedStores);
		foreach($usedStores as $oneUsedClStoreId) {
			$storeData = new \Nette\Utils\ArrayHash;
			//$storeData['id'] = $tmpStore->id;
			$storeData['id'] = $oneUsedClStoreId;
			$storeData['cl_pricelist_id'] = $tmpData->cl_pricelist_id;
			$storeData['cl_storage_id'] = $data['cl_storage_id'];
			
			
			$this->UpdateStore(['cl_store_id' => $oneUsedClStoreId, 'cl_pricelist_id' => $tmpData->cl_pricelist_id, 'cl_company_id' => $cl_company_id], $tmpData, $data, $usedMoves);

			
		}
	}


    /**
     * Called from api
     * @param type $data - array('identification','quantity','price','storage_name','cl_store_docs_id','description','cl_company_id')
     */
    public function ApiGiveIn($dataApi)
    {
        $data = new Nette\Utils\ArrayHash;

        //find item in pricelist
        if ($tmpPricelist = $this->PriceListManager->findAllTotal()->
													where('cl_company_id = ?', $dataApi['cl_company_id'])->
													where(array('identification' => $dataApi['identification']))->fetch()) {
														$data['cl_pricelist_id'] = $tmpPricelist->id;

            //find storage
            if ($tmpStorage = $this->StorageManager->findAllTotal()->
													where('cl_company_id = ?', $dataApi['cl_company_id'])->
													where(array('name' => $dataApi['storage_name']))->fetch()) {
                $data['cl_storage_id'] = $tmpStorage->id;
            } else {
                $data['cl_storage_id'] = NULL;
            }
            $data['s_in'] = $dataApi['quantity'];
            $data['create_by'] = 'automat';

            //find cl_store_docs
            if ($tmpParentData = $this->StoreDocsManager->findAllTotal()->
														where('cl_company_id = ?', $dataApi['cl_company_id'])->
														where(array('id' => $dataApi['cl_store_docs_id']))->fetch()) {
                //create record in cl_store_move
                $arrData = new \Nette\Utils\ArrayHash;
                $arrData['create_by'] = 'automat';
                $arrData['cl_company_id'] = $dataApi['cl_company_id'];
                $arrData['cl_store_docs_id'] = $dataApi['cl_store_docs_id'];
                $arrData['cl_pricelist_id'] = $tmpPricelist->id;
                $arrData['item_order'] = $this->StoreMoveManager->findAllTotal()->
																	where('cl_company_id = ?', $dataApi['cl_company_id'])->
																	where('cl_store_docs_id = ?', $arrData['cl_store_docs_id'])->max('item_order') + 1;

                //income
                $arrData['s_in'] = 0;
                if (is_null($dataApi['price'])) {
                    $arrData['price_in'] = $tmpPricelist['price_s'] * $tmpPricelist->cl_currencies->fix_rate / $tmpParentData->currency_rate;
                    $arrData['price_in_vat'] = ($tmpPricelist['price_s'] * $tmpPricelist->cl_currencies->fix_rate / $tmpParentData->currency_rate) * (1 + ($tmpPricelist['vat'] / 100));
                    $arrData['price_s'] = $tmpPricelist['price_s'] * $tmpPricelist->cl_currencies->fix_rate / $tmpParentData->currency_rate;
                } else {
                    $arrData['price_in'] = $dataApi['price'];
                    $arrData['price_in_vat'] = ($dataApi['price']) * (1 + ($tmpPricelist['vat'] / 100));
                    $arrData['price_s'] = $dataApi['price'];
                }
                $arrData['vat'] = $tmpPricelist['vat'];
                if (is_null($data['cl_storage_id'])) {
                    $arrData['cl_storage_id'] = $tmpParentData->cl_storage_id;
                } else {
                    $arrData['cl_storage_id'] = $data['cl_storage_id'];
                }

                $arrData['description'] = $dataApi['description'];

                $row = $this->StoreMoveManager->insertForeign($arrData);
                $data['id'] = $row->id;
                $data['price_s'] = $dataApi['price'];
                $data['price_in'] = $dataApi['price_in'];
                ///??/// $data['price_in'] = $arrData['price_in'];

                //make givein
                $dataMove = $this->GiveInStore($data, $row, $tmpParentData, $dataApi['cl_company_id']);
                $row->update($dataMove);

                //update of parent record
                $this->UpdateSum($dataApi['cl_store_docs_id']);

                $retMin = 1;
                //check if total amount is not under minimum
                if ($tmpMin = $this->findAllTotal()->where(array('cl_company_id' => $dataApi['cl_company_id'],
														'cl_storage_id' => $data['cl_storage_id'],
														'cl_pricelist_id' => $data['cl_pricelist_id']))->fetch()) {
                    $arrRet = array(1, 'Naskladněno, nový zůstatek je: ' . $tmpMin->quantity, $row->id);
                } else {
                    $arrRet = array(1, 'Naskladněno.', $row->id);
                }


                return $arrRet;
            }
        } else {
            return array(9999, 'Nenaskladněno, položka nebyla nalezena.', NULL);
        }
    }


    /*
     * Give in to store
     * return data to save by component
     */
    public function GiveInStore($data, $tmpData, $tmpParentData, $cl_company_id = NULL)
    {
        if (is_null($tmpData['cl_pricelist_id'])) {
            return $data;
        }
        //try {
            //aditional processing of data from listgrid before update to database by listgrid
            if ($tmpData->cl_company->platce_dph == 1)
                $data['price_s'] = $data['price_in'] * $tmpParentData['currency_rate'];
            else
                $data['price_s'] = $data['price_in_vat'] * $tmpParentData['currency_rate'];

            $storeData = new \Nette\Utils\ArrayHash;

            //give in to store
            //find actual cl_store_id for current data
            $tmpArrFind = ['cl_pricelist_id' => $tmpData['cl_pricelist_id'],
                                    'cl_storage_id' => $data['cl_storage_id']];

           // bdump($tmpData['cl_store_id'],'cl_store_id');
            if (isset($data['exp_date']) && is_null($tmpData['cl_store_id'])) {
                $tmpArrFind['exp_date'] = $data['exp_date'];
            //}else{
			//	$tmpArrFind['exp_date'] = NULL;
			}elseif (!isset($data['exp_date']) && is_null($tmpData['cl_store_id']) && $this->settings['exp_on'] == 1) {
                $tmpArrFind['exp_date'] = NULL;
            }

            if (isset($data['batch']) && is_null($tmpData['cl_store_id'])) {
                $tmpArrFind['batch'] = $data['batch'];
            //}else{
			//	$tmpArrFind['batch'] = NULL;
            }elseif (!isset($data['batch']) && is_null($tmpData['cl_store_id']) && $this->settings['batch_on'] == 1) {
                $tmpArrFind['batch'] = NULL;
            }
            if (!is_null($tmpData['cl_store_id'])){
                $tmpArrFind['id'] = $tmpData['cl_store_id'];
            }
            bdump($tmpArrFind,'arrFind');
            if (!is_null($cl_company_id)){
                $tmpArrFind['cl_company_id'] = $cl_company_id;
                $tmpStore = $this->findAllTotal()->where($tmpArrFind)->fetch();
            }else {
                $tmpStore = $this->findOneBy($tmpArrFind);
            }


            if (!$tmpStore) {
                $storeData['cl_pricelist_id'] = $tmpData->cl_pricelist_id;
                $storeData['cl_storage_id'] = $data['cl_storage_id'];
				$storeData['cl_pricelist_limits_id'] = $this->PriceListLimitsManager->getLimit($storeData['cl_pricelist_id'], $storeData['cl_storage_id'], $cl_company_id);

                $storeData['quantity'] = $data['s_in'];

                $data['s_end'] = $data['s_in'];
                if (!is_null($cl_company_id)) {
                    $storeData['cl_company_id'] = $cl_company_id;
                }
                if (isset($data['exp_date'])) {
                    $storeData['exp_date'] = $data['exp_date'];
                }
                if (isset($data['batch'])) {
                    $storeData['batch'] = $data['batch'];
                }
                if (!is_null($cl_company_id)) {
                    $tmpStore = $this->insertForeign($storeData);
                    $tmpStore = $this->findAllTotal()->where('id = ?', $tmpStore->id)->fetch();
                }else {
                    $tmpStore = $this->insert($storeData);
                    $tmpStore = $this->find($tmpStore->id);
                }
            } else {
                //14.08.2022 - pointless
               // $newClStoreId[$tmpStore['id']] = $tmpStore['id'];

                $storeData['id'] = $tmpStore->id;
                $storeData['cl_pricelist_id'] = $tmpData->cl_pricelist_id;
                $storeData['cl_storage_id'] = $data['cl_storage_id'];
                if (isset($data['exp_date'])) {
                    $storeData['exp_date'] = $data['exp_date'];
                }
                if (isset($data['batch'])) {
                    $storeData['batch'] = $data['batch'];
                }

                if (!is_null($cl_company_id)){
                    $tmpQuantity = $this->StoreMoveManager->findAllTotal()->where(['cl_pricelist_id'   => $tmpData->cl_pricelist_id,
                                                                                        'cl_storage_id'     => $data['cl_storage_id'],
                                                                                        'cl_store_id'       => $tmpStore->id,
                                                                                        'cl_company_id'     => $cl_company_id])->
                                                                            where('id != ?', $tmpData['id'])->
                                                                            sum('s_in-s_out');
                    $storeData['quantity']      = $tmpQuantity + $data['s_in'];
                    $storeData['cl_company_id'] = $cl_company_id;
                    $this->updateForeign($storeData);
                    $data['s_end'] = $data['s_in'] - $this->StoreOutManager->findAllTotal()
                                                            ->where(['cl_store_move_in_id' => $tmpData->id, 'cl_company_id' => $cl_company_id])
                                                            ->sum('s_out');

                }else {
                    $tmpQuantity = $this->StoreMoveManager->findBy(['cl_pricelist_id' => $tmpData->cl_pricelist_id,
                                                                            'cl_storage_id' => $data['cl_storage_id'],
                                                                            'cl_store_id' => $tmpStore->id])->
                                                            where('id != ?', $tmpData['id'])->
                                                            sum('s_in-s_out');

                    $storeData['quantity'] = $tmpQuantity + $data['s_in'];
                    $this->update($storeData);
                    $data['s_end'] = $data['s_in'] - $this->StoreOutManager->findBy(['cl_store_move_in_id' => $tmpData->id])->
                        sum('s_out');

                }


                //16.10.2019 - delete cl_store records without cl_store_move for this cl_pricelist_id
                //bdump($tmpData->cl_pricelist_id,'cl_pricelist_id');
                $this->deleteStoreWM($tmpData->cl_pricelist_id, $cl_company_id);
            }

            $data['cl_store_id'] = $tmpStore['id'];
            // Debugger::log('update cl_store_id ' . $tmpStore['id']);
            //Debugger::log('update cl_store_move_id ' . $data['id']);
            if (!is_null($cl_company_id)) {
                $this->StoreMoveManager->updateForeign(['cl_company_id' => $cl_company_id, 'cl_store_id' => $tmpStore['id'], 'id' => $data['id'], 's_in' => $data['s_in']]);
            }else {
                $this->StoreMoveManager->update(['cl_store_id' => $tmpStore['id'], 'id' => $data['id'], 's_in' => $data['s_in']]);
            }
            //die;
            $usedMoves[$data['id']] = $data['id'];

            $newClStoreId = [];
            $newClStoreId[$tmpStore['id']] = $tmpStore['id'];

            //now update last price_s in cl_pricelist
            $tmpPriceList = new \Nette\Utils\ArrayHash;
            $tmpPriceList['id'] = $tmpData->cl_pricelist_id;
            if ($tmpData->cl_company->platce_dph == 1) {
				$tmpPriceList['price_s'] = $data['price_in'];
			}else {
				$tmpPriceList['price_s'] = $data['price_in_vat'];
			}
            //17.02.2020 - update profit
			$tmpPriceList['profit_abs'] = $tmpData->cl_pricelist->price - $tmpPriceList['price_s'];
            if ($tmpPriceList['price_s'] > 0){
			    //18.08.2020 - oprava výpočtu zisku. Byl chybně price_s / price
				$tmpPriceList['profit_per'] = (($tmpData->cl_pricelist->price / $tmpPriceList['price_s']) - 1) * 100;
			}elseif ($tmpData->cl_pricelist->price > 0) {
				$tmpPriceList['profit_per'] = 100;
			}else{
                $tmpPriceList['profit_per'] = 0;
            }
 
            $tmpQuantity = $this->StoreMoveManager->findBy(['cl_pricelist_id' => $tmpData->cl_pricelist_id])->
                                                    where('id != ?', $tmpData['id'])->
                                                    sum('s_in-s_out');
            $tmpPriceList['quantity'] = $tmpQuantity + $data['s_in'];
            if (!is_null($cl_company_id)) {
                $tmpPriceList['cl_company_id'] = $cl_company_id;
                $this->PriceListManager->update($tmpPriceList);
            }else{
                $this->PriceListManager->update($tmpPriceList);
            }

            //23.05.2016 - find moves out without record in cl_store_out
            //this means all giveouts which comes to minus
            //now we must create cl_store_out record for those giveouts
            $tmpOut = $this->StoreMoveManager->findBy(['cl_pricelist_id' => $tmpData->cl_pricelist_id])->
                                                where(['cl_storage_id' => $data['cl_storage_id']])->
                                                where('cl_store_move.s_out > 0 AND cl_store_move.s_out_fin < cl_store_move.s_out')->
                                                select('SUM(IFNULL(:cl_store_out.s_out,0)) AS store_out, cl_store_move.s_out,cl_store_move.id,cl_store_move.cl_store_id')->
                                                group('id,s_out');  //s_out,id

            //05.12.2020 - correction - we have to work with current balance
            $tmpDispo = $data['s_end'];
            foreach ($tmpOut as $one) {
                $arrStoreOut                        = new \Nette\Utils\ArrayHash;
                $arrStoreOut['cl_store_move_id']    = $one->id;            //id of outcome
                $arrStoreOut['cl_store_move_in_id'] = $data['id'];            //id of income
                $arrStoreOut['cl_store_id']         = $data['cl_store_id'];
                //14.08.2022 - correction of missing price_s in at some cases
                $arrStoreOut['price_s']             = $data['price_s'];

                $giveOut                            = $one->s_out - $one->store_out;
                $tmpDispo_before = $tmpDispo;
                if ($giveOut > 0 && $tmpDispo > 0) {
                    if ($tmpDispo >= $giveOut) {
                        $arrStoreOut['s_out'] = $giveOut;
						$arrStoreOut['s_total'] = $tmpDispo - $giveOut;
                        $tmpDispo = $tmpDispo - $giveOut;
                    } else {
                        $arrStoreOut['s_out'] = $tmpDispo;
						$arrStoreOut['s_total'] = 0;
                        $tmpDispo = 0;
                    }
                    $tmpStoreOut = $this->StoreOutManager->insert($arrStoreOut);

                    $dataOne = [];
                    if ($one->s_out > 0) {
                        $dataOne['price_s'] = $this->StoreOutManager->findBy(['cl_store_move_id' => $one['id']])->sum('price_s * s_out') / $one->s_out;
                    } else {
                        $dataOne['price_s'] = 0;
                    }
                    $dataOne['s_out_fin'] = $this->StoreOutManager->findBy(['cl_store_move_id' => $one['id']])->sum('s_out');
                    $dataOne['minus'] = ($dataOne['s_out_fin'] == $one['s_out']) ? 0 : 1;
                    $dataOne['cl_store_id'] = $data['cl_store_id'];
                    $newClStoreId[$one->cl_store_id] = $one->cl_store_id;
                    $one->update($dataOne);

                    //16.11.2019 - update quantity on cl_store
					$one->cl_store->update(['quantity' => $one->cl_store->quantity - $arrStoreOut['s_out']]);
					$data['s_end'] = $data['s_end'] - $arrStoreOut['s_out'];
                }
                if ($tmpDispo == 0 || $giveOut <= 0)
                    break;
            }

            //16.11.2019 - update cl_store
			//$this->deleteStoreWM($tmpData->cl_pricelist_id, $cl_company_id, $tmpStore->id);
			//foreach ($oldClStoreId as $one){
				/*if ($onClStore = $this->find($one)) {
					$onClStore->update(array('quantity' => $this->StoreMoveManager->findAll()->where('cl_store_id = ?',$one)->sum('s_end')));
				}*/
			//	$this->UpdateStore(array('cl_store_id' => $one, 'cl_pricelist_id' => $tmpData->cl_pricelist_id));
			//}
		
			$this->deleteStoreWM($tmpData->cl_pricelist_id, $cl_company_id, $tmpStore->id);
            //bdump($newClStoreId, 'newClStoreId is set');
          /*  bdump($oldClStoreId != $newClStoreId, '$oldClStoreId != $newClStoreId');
			if ($oldClStoreId != $newClStoreId) {
                //29.11.2020 - update previous used store record
                $this->updateStore2($oldClStoreId, $tmpData, $data, $cl_company_id);
            }*/
			//zaktualizujeme hodnotu na zasobach, které byly použity pro příjem
			$this->updateStore2($newClStoreId,$tmpData, $data, $cl_company_id, $usedMoves);
			
			//16.11.2019 - update cl_pricelist
            if (!is_null($cl_company_id)){
                $this->PriceListManager->findAllTotal()->where('id = ? AND cl_company_id = ?', $tmpData->cl_pricelist_id, $cl_company_id)->fetch()->
                        update(array('quantity' => $this->findAllTotal()->where(['cl_pricelist_id' => $tmpData->cl_pricelist_id, 'cl_company_id'=> $cl_company_id])->sum('quantity')));

            }else{
                $this->PriceListManager->find($tmpData->cl_pricelist_id)->update([
                            'quantity' => $this->findAll()->where(['cl_pricelist_id' => $tmpData->cl_pricelist_id])->sum('quantity')]);
            }

			//$one

            //update return data
            $data['s_end'] = $data['s_in'] - $this->StoreOutManager->findBy(['cl_store_move_in_id' => $tmpData->id])->
                sum('s_out');

            //update price in all existing moves out
            $arrMoveOut = new \Nette\Utils\ArrayHash;
            $arrMoveOut['price_s'] = $tmpPriceList['price_s'];
            //03.06.2017 - už není potřeba, řešíme v globální metodě updateVAP()
            //$this->StoreOutManager->findBy(array('cl_store_move_in_id'=> $tmpData['id']))->update($arrMoveOut);

            //$elapsed = Debugger::timer('start');
            //Debugger::fireLog('test');
            //Debugger::fireLog($data);
            //$this->updateVAP($data['cl_store_id']);
            //bdump($data,'return data');
            return $data;

        //} catch (Exception $e) {
            //$throw
            //dump($e);
            //die;
          //Debugger::log($e, Debugger::ERROR);
        //}

    }

    /**Delete all cl_store without cl_store_moves for given cl_pricelist
     * @param $cl_pricelist_id
     */
    private function deleteStoreWM($cl_pricelist_id, $cl_company_id, $idNoDelete = NULL)
    {
    	return;
    	//10.12.2019 - for now off, because of constraint errors on more users concurrent working
        /*$toDel = $this->findAll()->where('cl_store.cl_pricelist_id = ? AND NOT EXISTS(SELECT id FROM cl_store_move WHERE :cl_store_move.cl_store_id = cl_store.id)', $cl_pricelist_id);
        if (!is_null($idNoDelete)){
        	$toDel = $toDel->where('cl_store.id != ?', $idNoDelete);
		}
        //30.10.2019 - switched off because of constraints in cl_inventory_items - Kučinková
        //15.11.2019 - to cl_inventory_items is added ON DELETE SET NULL for cl_store_id
        foreach($toDel as $key => $one)
        {
            $one->delete();
        }
		*/
    }
	
	/**Delete all cl_store without cl_store_moves for given cl_pricelist  ONLY FOR repairBalance
	 * @param $cl_pricelist_id
	 */
	private function deleteStoreWMRB($cl_pricelist_id, $cl_company_id, $idNoDelete = NULL)
	{
		$toDel = $this->findAll()->where('cl_store.cl_pricelist_id = ? AND NOT EXISTS(SELECT id FROM cl_store_move WHERE :cl_store_move.cl_store_id = cl_store.id)', $cl_pricelist_id);
		if (!is_null($idNoDelete)){
			$toDel = $toDel->where('cl_store.id != ?', $idNoDelete);
		}
		//30.10.2019 - switched off because of constraints in cl_inventory_items - Kučinková
		//15.11.2019 - to cl_inventory_items is added ON DELETE SET NULL for cl_store_id
		foreach($toDel as $key => $one)
		{
			$one->delete();
		}
		
	}
 
	
	/*
	 * update of cl_store_docs sum
	 */
	public function UpdateSum($subId)
	{
	    $parentData = new \Nette\Utils\ArrayHash;
	    $parentData['id'] = $subId;	
		//we can work with total records, because security is done on higher level of calling procedures
	    $tmpParentData = $this->StoreDocsManager->findAllTotal()->where(['id' => $parentData['id']])->fetch();
	    if ($tmpParentData->doc_type == 0)
	    {
			//income
			$price_in = $this->StoreMoveManager->findAllTotal()->where(['cl_store_docs_id' => $parentData['id']])->sum('price_in*s_in');
			$price_in_vat = $this->StoreMoveManager->findAllTotal()->where(['cl_store_docs_id' => $parentData['id']])->sum('price_in_vat*s_in');

			$parentData['price_in'] = $price_in;
			$parentData['price_in_vat'] = $price_in_vat;	    



	    }elseif($tmpParentData->doc_type == 1)
	    {
			//outgoing
			$price_s = $this->StoreMoveManager->findAllTotal()->where(['cl_store_docs_id' => $parentData['id']])->sum('price_s*s_out');
			$price_e2 = $this->StoreMoveManager->findAllTotal()->where(['cl_store_docs_id' => $parentData['id']])->sum('price_e2');
			$price_e2_vat = $this->StoreMoveManager->findAllTotal()->where(['cl_store_docs_id' => $parentData['id']])->sum('price_e2_vat');
            $minus = $this->StoreMoveManager->findAllTotal()->where(['cl_store_docs_id' => $parentData['id']])->sum('minus');
            if ($tmpParentData['currency_rate'] == 0)
                $rate = 1;
            else
                $rate = $tmpParentData['currency_rate'];

			if ($price_e2 >0)
			{
				$parentData['profit'] = 100 - ($price_s / ($price_e2 * $rate) * 100);
				$parentData['profit_abs'] = ($price_e2 * $rate) - $price_s;
			}  else {
				$parentData['profit'] = 100;
				$parentData['profit_abs'] = ($price_e2 * $rate) - $price_s;
			}

		    $parentData['minus'] = ($minus > 0) ? 1 : 0;

			$parentData['price_s'] = $price_s;
			$parentData['price_e2'] = $price_e2;
			$parentData['price_e2_vat'] = $price_e2_vat;	    
	    }
	   // bdump($parentData);
	    $this->StoreDocsManager->updateForeign($parentData);	
	}       	
    	
	/**
	 * recalculate VAP price 
	 * @param type $cl_store_id
	 */
	public function updateVAP($cl_store_id, $dateFrom = NULL)
	{
		//return;
	    //30.05.2017 - VAP calculation
	    //go trough all incomes
	    //for everyone calc VAP
	    //
        ////profiler::start();
        //bdump('YES');
        Debugger::log('START ----> updateVAP for $cl_store_id = ' . $cl_store_id, 'repair_balance');
	    if (is_null($cl_store_id))
	    {
            throw new Exception('Nebylo předáno ID zásoby', 0);
        //	return;
	    }
	    $tmpStore = $this->StoreMoveManager->findBy(['cl_store_id' => $cl_store_id])
                                        ->where('cl_store_move.s_in > 0 AND cl_store_docs.doc_type = 0')
                                        ->order('cl_store_docs.doc_date ASC, cl_store_move.id ASC');
	    if (!is_null($dateFrom))
        {
            $tmpStore = $tmpStore->where('cl_store_docs.doc_date >= ?',$dateFrom);
        }

	    $totalOutgoing = 0;
	    $totalIncome = 0;
	    $lastPrice_s = 0;
		$prevEnd = 0;
        $row = 1;
        $arrWork = [];
        $rowTotal = count($tmpStore);
        Debugger::log('count($tmpStore) = ' . $rowTotal, 'repair_balance');
	    foreach ($tmpStore as $one)
	    {
            Debugger::log('$row = ' . $row . '/' . $rowTotal . '$cl_store_move.id = ' . $one['id'], 'repair_balance');
	        bdump($one['id']);
            if ($one->cl_storage->price_method == 0)
            { //FiFo
                $priceVap = $one->price_s;
                $totalIncome = $totalIncome + $one['s_in'];
            }else {
                //VAP
                //sum all outgoing before date of current processed income
                $tmpIncome = $this->StoreMoveManager->findBy(['cl_store_move.cl_pricelist_id' => $one->cl_pricelist_id,
                                                                'cl_store_move.cl_store_id' => $cl_store_id])->
                                                    where('(cl_store_docs.doc_date < ? OR (cl_store_docs.doc_date = ? AND cl_store_move.id != ?  )) AND cl_store_docs.doc_type = 0',
                                                        $one->cl_store_docs['doc_date'], $one->cl_store_docs['doc_date'], $one['id'])->
                                                    select('SUM(cl_store_move.s_in * cl_store_move.price_s) AS price_s_in, SUM(s_in) AS s_in')->fetch();

                $tmpOutgoing = $this->StoreMoveManager->findBy(['cl_store_move.cl_pricelist_id' => $one->cl_pricelist_id,
                                                                    'cl_store_move.cl_store_id' => $cl_store_id])->
                                                                where('(cl_store_docs.doc_date < ? OR (cl_store_docs.doc_date = ? AND cl_store_move.id != ? )) AND cl_store_docs.doc_type = 1',
                                                                    $one->cl_store_docs['doc_date'], $one->cl_store_docs['doc_date'], $one['id'])->
                                                                select('SUM(cl_store_move.s_out * cl_store_move.price_s) AS price_s, SUM(cl_store_move.s_out) AS s_out')->fetch();


                $priceVap = (($tmpIncome['price_s_in'] - $tmpOutgoing['price_s']) + ($one['s_in'] * $one['price_s'])) / (($tmpIncome['s_in'] - $tmpOutgoing['s_out']) + $one['s_in']);
               // Debugger::log('$priceVap = ' . $priceVap, 'repair_balance');

                $totalIncome = $totalIncome + $one['s_in'];
            }
            if ($one->cl_storage->price_method == 0)
            {
                //FiFo case
                //update all price_s on all outgoes cl_store_out from this income
                if (!array_key_exists($one->id, $arrWork)) {
                    $arrWork[$one->id] = $one->id;
                    $tmpOutgoing = $this->StoreOutManager->findBy(['cl_store_move_in_id' => $one->id]);
                    $tmpOutgoing->update(['price_s' => $one->price_s]);
                 //   Debugger::log('FiFo $tmpOutgoing->update([price_s => ' . $one->price_s . '])', 'repair_balance');
                }
            }else{
                //VAP case
                //update all price_s on all outgoes cl_store_out newest then this income
                //$tmpOutgoing = $this->StoreOutManager->findBy(array('cl_store_move_in_id' => $one->id));
                //$tmpOutgoing->update(array('price_s' => $priceVap));
                //bdump($priceVap);
                $tmpOutgoing = $this->StoreMoveManager->findAll()->where('cl_store_docs.doc_type = 1 AND cl_store_id = ? AND cl_store_docs.doc_date >= ?', $one->cl_store_id, $one->cl_store_docs['doc_date']);
                foreach ($tmpOutgoing as $key2 => $one2)
                {
                    bdump($priceVap);
                    bdump($one2['id']);
                    //$one2->cl_store_move->update(array('price_s' => $priceVap));
                    //$one3 = $this->StoreMoveManager->findAll()->where('id = ?', $one2['cl_store_move_id'])->fetch();
                    $this->StoreMoveManager->update(['id' => $key2, 'price_s' => $priceVap]);
                //    Debugger::log('$this->StoreMoveManager->update([id => ' . $key2 . ', price_s => ' . $priceVap . '])', 'repair_balance');

                    //$one2->update(array('id' => $key2, 'price_s' => $priceVap));
                    $one3 = $this->StoreOutManager->findAll()->where('cl_store_move_id = ?', $key2);
                    foreach($one3 as $key4 => $one4){
                        $this->StoreOutManager->update(['id' => $key4, 'price_s' => $priceVap]);
                    //    Debugger::log('$this->StoreOutManager->update([id => ' . $key4 . ', price_s => ' . $priceVap . '])', 'repair_balance');
                    }

                    //$one2->related('cl_store_out')->update(array('price_s' => $priceVap));
                }
            }
            //update price_vap on income cl_store_move record
            $one->update(['price_vap' => $priceVap,
                       			's_total' => $prevEnd + $one['s_in']]);

            if ($one->cl_storage->price_method == 0)
            {
                //FiFo
                //update price_s in all outgoing cl_store_move
                $tmpUpdateStoreMove = $this->StoreOutManager->findBy(['cl_store_move_in_id'=> $one->id]);
            }else{
                //VAP
                //$tmpUpdateStoreMove = $this->StoreOutManager->findAll()
                 //               				->where('cl_store_move.cl_store_docs.doc_date >= ? AND cl_store_move.cl_store_id = ?',$one->cl_store_docs['doc_date'], $one->cl_store_id );
                $tmpUpdateStoreMove = $this->StoreOutManager->findBy(['cl_store_move_in_id'=> $one->id]);
            }
			//$this->database->beginTransaction(); // zahájení transakce
            $arrMoveOut = new \Nette\Utils\ArrayHash;
            foreach($tmpUpdateStoreMove as $one2)
            {
                if ($one2->cl_store_move->s_out != 0)
                {
                	
                    $arrMoveOut['price_s'] = $this->StoreOutManager->findBy(['cl_store_move_id' => $one2['cl_store_move_id']])->sum('s_out*price_s') / $one2->cl_store_move->s_out;
					//$arrMoveOut['price_s'] = $one2->related('cl_store_out')->sum('s_out*price_s');
                }else{
                    $arrMoveOut['price_s'] = 0;
                }
                $arrMoveOut['price_vap'] = $priceVap;
                //nová hodnota zisku
                if ($one2->cl_store_move->price_e > 0)
                {
                    //$arrMoveOut['profit'] = 100 - (($one2->cl_store_move->price_s / ($one2->cl_store_move->price_e * $one2->cl_store_move->cl_store_docs->currency_rate)) * 100);
	                $arrMoveOut['profit'] = 100 - (($arrMoveOut['price_s'] / ($one2->cl_store_move->price_e * $one2->cl_store_move->cl_store_docs->currency_rate)) * 100);
                }else{
                    $arrMoveOut['profit'] = 100;
                }
                $this->StoreMoveManager->find($one2->cl_store_move_id)->update($arrMoveOut);
                //nová hodnota zisku pro celou výdejku
                //bdump($one2->cl_store_move->cl_store_docs->id,'cl_store_docs_id');
                $this->UpdateSum($one2->cl_store_move->cl_store_docs->id);
             //   Debugger::log('$this->UpdateSum(' . $one2->cl_store_move->cl_store_docs->id . ')', 'repair_balance');
                
                //update price_s and profit on related cl_invoice_items, cl_delivery_note_items, cl_sale_items
				unset($arrMoveOut['price_vap']);
				$tmpInvoiceItems = $this->InvoiceItemsManager->findAll()->where('cl_store_move_id = ?', $one2['cl_store_move_id']);
				//$tmpInvoiceItems->update($arrMoveOut);
				//bdump($tmpInvoiceItems, 'invoice items');
				foreach ($tmpInvoiceItems as $tmpInvoice)
				{
					$tmpInvoiceId = $tmpInvoice->cl_invoice_id;
					//bdump($tmpInvoice, 'update invoice item id');
					$tmpInvoice->update($arrMoveOut);
					$this->InvoiceManager->updateInvoiceProfit($tmpInvoiceId);
               //     Debugger::log('$this->InvoiceManager->updateInvoiceProfit(' . $tmpInvoiceId . ')', 'repair_balance');
				}
				
				unset($arrMoveOut['profit']);
				$tmpDeliveryNoteItems = $this->DeliveryNoteItemsManager->findAll()->where('cl_store_move_id = ?', $one2['cl_store_move_id']);
				$tmpDeliveryNoteItems->update($arrMoveOut);
            //    Debugger::log('$tmpDeliveryNoteItems->update($arrMoveOut);', 'repair_balance');
				$tmpSaleItems = $this->SaleItemsManager->findAll()->where('cl_store_move_id = ?', $one2['cl_store_move_id']);
				$tmpSaleItems->update($arrMoveOut);
             //   Debugger::log('$tmpSaleItems->update($arrMoveOut);', 'repair_balance');
                
            }
			//$this->database->commit();
            //save last price_s
            $lastPrice_s = $one->price_s;
            $row++;
	    }
	    //update VAP on cl_store according to last VAP
	    if ($tmpStore = $this->findBy(['id' => $cl_store_id])->fetch())
	    {
            if ($lastVap = $this->StoreMoveManager->findBy(['cl_store_move.cl_pricelist_id' => $tmpStore->cl_pricelist_id,
                                        'cl_store_move.cl_storage_id' => $tmpStore->cl_storage_id,
                                        'cl_store_docs.doc_type' => 0])
                                ->order('cl_store_docs.doc_date DESC, cl_store_move.id DESC')
                                ->limit(1)
                                ->fetch())
                $tmpStore->update(['price_s' => $lastVap->price_vap]);
        //    Debugger::log('$tmpStore->update([price_s => ' . $lastVap->price_vap . '])', 'repair_balance');
			//13.06.2017 - update cl_pricelist.price_s according to last income price
			$tmpPricelist = $tmpStore->cl_pricelist;
            $tmpPricelist->update(['price_s' => $lastVap->price_in]);
        //    Debugger::log('$tmpPricelist->update([price_s => ' . $lastVap->price_in . '])', 'repair_balance');
	    }

       // //profiler::finish('updateVAP');
        Debugger::log('END ----> updateVAP for $cl_store_id = ' . $cl_store_id, 'repair_balance');
	}
	
	/*25.11.2018
	 * return store tree with amount at storage in name
	 */
	public function getStoreTreeNotNestedAmount($cl_pricelist_id)
	{
		$arrStore = [];
		$tmpStorage = $this->StorageManager->findAll()->where('cl_storage_id IS NULL')->order('name');
		bdump($cl_pricelist_id,'cl_pricelist_id in getstoretreenotnestedamount');
		foreach($tmpStorage as $key=>$one)
		{
			//$arrStore[$key] = $one->name.' - '.$one->description;
			$arrStore[$key] = \Nette\Utils\Html::el()->
						setText($one->name.' - '.$one->description.' skladem: '.$this->getQuantOnStore($cl_pricelist_id, $one->id, TRUE))->
						setAttribute('class', 'l1');
				
			//102 => \Nette\Utils\Html::el()->setText('Czech republic')->data('lon', ...)->data('lat', ...)
			
			
			foreach($one->related('cl_storage') as $key2=>$one2)
			{
				//$arrStore[$key2] = $one2->name.' - '.$one2->description;
				$arrStore[$key2] = \Nette\Utils\Html::el()->
						setText($one2->name.' - '.$one2->description.' skladem: '.$this->getQuantOnStore($cl_pricelist_id, $one2->id, TRUE))->
						setAttribute('class', 'l2');				
			}

		}
		//dump($arrStore);
		//die;
		return $arrStore;
	}		
	
	private function getQuantOnStore($cl_pricelist_id, $cl_storage_id, $formatted)
	{
	    if ($found = $this->findAll()->where(['cl_storage_id' => $cl_storage_id, 'cl_pricelist_id' => $cl_pricelist_id])->
                                select('id, price_s, cl_storage_id,SUM(quantity) AS quantity,cl_pricelist_id')->group('cl_pricelist_id,cl_storage_id')->fetch())
	    {
	        //bdump($found,'found');
            //bdump($cl_storage_id, 'cl_storage_id');
            //bdump($cl_pricelist_id, 'cl_pricelist_id');
            //bdump($found->id, 'store_id');
            //bdump($found->quantity, 'quantity');

            if ($found->cl_storage->price_method == 0) {
                //FiFo
                $arrResponse = ['quantity' => $found->quantity, 'price_s' => $found->cl_pricelist->price_s, 'unit' => $found->cl_pricelist->unit];
            }else{
                //VAP
                $arrResponse = ['quantity' => $found->quantity, 'price_s' => $found->price_s, 'unit' => $found->cl_pricelist->unit];
            }

        }else{
            $arrResponse = ['quantity' => 0, 'price_s' =>  0, 'unit' => ""];
            //echo(0);
        }
        if ($formatted) {
            return (number_format($arrResponse['quantity'],$this->settings->des_mj,"."," ") . " ".$arrResponse['unit']);
        }else{
            return ($arrResponse['quantity']);
        }

	}



   
	/*06.03.2019 - moved here from invoice presenter, because we need make giveout from commission presenter
	 * 
	 */
	public function giveOutItem($docId, $dataId, $itemsManager)
	{
		//$tmpItem = $tmpData->related('cl_invoice_items');
        //$test = $itemsManager->findAll()->where('cl_pricelist_id IS NOT NULL AND cl_pricelist_id>0 AND id = ?', $dataId)->fetch();
        //dump($test == true);
        //die;
		if ($one = $itemsManager->findAll()->where('cl_pricelist_id IS NOT NULL AND cl_pricelist_id>0 AND id = ?', $dataId)->fetch())
		{
		//foreach ($tmpItems->where('cl_pricelist_id IS NOT NULL AND cl_pricelist_id>0') as $one)
		//{
		    if ($one->cl_storage_id != NULL)
		    {
			    $tmpStorage = $one->cl_storage_id;
		    }elseif ($this->settings->cl_storage_id != NULL) {
			    $tmpStorage = $this->settings->cl_storage_id;
		    }else{
                //find store with enough quantity
                if ($tmpStorage = $this->findBy(array( 'cl_pricelist_id' => $one->cl_pricelist_id))
                            ->where('quantity > 0')
                            ->order('quantity DESC')->limit(1)->fetch())
                {
                    $tmpStorage = $tmpStorage->cl_storage_id;
                }
		    }


		    //new record into cl_store_move
		    $arrData = new \Nette\Utils\ArrayHash;
		    $arrData['cl_store_docs_id']	= $docId;
		    $arrData['cl_pricelist_id']		= $one->cl_pricelist_id;
		    $arrData['item_order']		    = $one->item_order;
            //dump($one);
            //die;
            $arrData['description']		    = $one->description1 . ' ' . $one->description2;

		    $arrData['price_s']			    = $one->price_s;
		    $arrData['price_e']			    = $one->price_e;
		    $arrData['price_e2']		    = $one->price_e2;
		    $arrData['price_e2_vat']	    = $one->price_e2_vat;
		    $arrData['vat']			        = $one->vat;
		    $arrData['discount']		    = $one->discount;
		    $arrData['cl_storage_id']	    = $tmpStorage;
		    if ($itemsManager->tableName == 'cl_invoice_items') {
                $arrData['cl_invoice_items_id'] = $one->id;
            }
            //dump($itemsManager->tableName );
            //dump($one['order_number']);
            //die;
            if ($itemsManager->tableName == 'cl_commission_items_production') {
                $arrData['order_number'] = $one->order_number;
            }

		    //16.12.2018 - found cl_store_move row. 
		    if ($one->cl_store_move_id == NULL){
                //If there is no cl_store_move_id we have to insert new one
                $row = $this->StoreMoveManager->insert($arrData);
                $arrData['id']   = $row->id;
                //28.12.2017
                //we need tmpDataItemMove without quantity because in previous steps were storage movement deleted
                $tmpDataItemMove = $this->StoreMoveManager->find($row['id']);
		    }else{
                $arrData['id']   = $one->cl_store_move_id;
                $tmpDataItemMove = $this->StoreMoveManager->find($one->cl_store_move_id);
                //$tmpDataItemMove = $one->cl_store_move;
				//$this->StoreMoveManager->find($one->cl_store_move_id);			
		    }
		    //then update quantity
		    $arrData['s_out'] = $one->quantity;
            if ($itemsManager->tableName == 'cl_commission_items_production') {
                $arrData['s_out'] = $one->quantity_prod;
            }
		    $this->StoreMoveManager->update($arrData);

		    //dump($row->id);
		    //die;
		    //$toUpdate = $this->StoreMoveManager->find($row->id);
					
		    //
			//$this->StoreManager->UpdateStore($row);
		    $tmpParentData = $this->StoreDocsManager->find($docId);
		    //now cl_store, find record of store according to storage or batch, if doesn't exist, create it and add to return $data

		    //dump($arrData);
		    //dump($tmpDataItemMove);
            //die;
            //Debugger::log('giveOutStore start runtime(sec): ' . Debugger::timer() , 'StoreManager');
            //Debugger::log('Memory used(MB): '. memory_get_usage()/1024/1000, 'StoreManager');
		    $newData = $this->GiveOutStore($arrData,$tmpDataItemMove,$tmpParentData);
            //Debugger::log('giveOutStore finished runtime(sec): ' . Debugger::timer() , 'StoreManager');
            //Debugger::log('Memory used(MB): '. memory_get_usage()/1024/1000, 'StoreManager');
		    //dump($newData);
		    $this->StoreMoveManager->update($newData);
            //Debugger::log('StoreMoveUpdate finished runtime(sec): ' . Debugger::timer() , 'StoreManager');
            //Debugger::log('Memory used(MB): '. memory_get_usage()/1024/1000, 'StoreManager');
            $this->UpdateSum($docId);
            //Debugger::log('UpdateSum finished runtime(sec): ' . Debugger::timer() , 'StoreManager');
            //Debugger::log('Memory used(MB): '. memory_get_usage()/1024/1000, 'StoreManager');
		    //28.12.2017 - update price_s at invoice item
		    $one->update(array('price_s' => $newData['price_s'], 'cl_store_move_id' => $arrData['id']));

		}else{
		    $newData = FALSE;
        }
        return $newData;
	}

       /*16.12.2018 - give in one item
	 * 
	 */
	public function giveInItem($docId, $dataId, $itemsManager, $package = FALSE, $parentMoveBondId = NULL)
	{
	   // bdump($dataId);
		if ($one = $itemsManager->findAll()->where('cl_pricelist_id IS NOT NULL AND cl_pricelist_id>0 AND id = ?', $dataId)->fetch())
		{

		    if ($one->cl_storage_id != NULL)
		    {
    			$tmpStorage = $one->cl_storage_id;
		    }elseif ($this->settings->cl_storage_id != NULL) {
			    $tmpStorage = $this->settings->cl_storage_id;
		    }else{
                //find any storage
                if ($tmpStorage = $this->findBy(['cl_pricelist_id' => $one->cl_pricelist_id])
                            ->order('quantity DESC')->limit(1)->fetch())
                {
                    $tmpStorage = $tmpStorage->cl_storage_id;
                }
		    }

		    //new record into cl_store_move
		    $arrData = new \Nette\Utils\ArrayHash;
		    $arrData['cl_store_docs_id']	= $docId;
		    $arrData['cl_pricelist_id']		= $one->cl_pricelist_id;
		    //$arrData['item_order']		    = $one->item_order;
            $arrData['item_order']          = $this->StoreMoveManager->findAll()->
                                    where('cl_store_docs_id = ?', $docId)->
                                    max('item_order') + 1;



            if (!is_null($parentMoveBondId)){
                $arrData['cl_parent_bond_id'] = $parentMoveBondId;
            }

		    $tmpPrice = 0;
		    if (isset($one['price_s'])){
			    $tmpPrice = $one->price_s;
		    }elseif( isset($one['price_e_rcv'])){
			    $tmpPrice = $one->price_e_rcv;
		    }

            //then update quantity
            if (isset($one['quantity_rcv']))
            {
                $arrData['s_in'] = $one->quantity_rcv;
            }else{
                $arrData['s_in'] = abs($one->quantity);
            }
            $tmpPrice_e2 = $tmpPrice *  $arrData['s_in'];
            $tmpPrice_e2_vat = $tmpPrice *  $arrData['s_in'];
		    if (isset($one['price_e2'])) {
                $tmpPrice_e2 = $one->price_e2 ;
            }
            if (isset($one['price_e2_vat'])) {
                $tmpPrice_e2_vat = $one->price_e2_vat ;
            }

            $arrData['price_e2']		            = $tmpPrice_e2;
            $arrData['price_e2_vat']		        = $tmpPrice_e2_vat;


            //bdump($tmpPrice,'tmpPrice');
		    $arrData['price_s']			            = $tmpPrice;
		    $arrData['price_in']		            = $tmpPrice;
		    $arrData['price_in_vat']	            = $tmpPrice * (1+($one->vat/100));

		    $arrData['vat']			                = $one->vat;
		    $arrData['cl_storage_id']	            = $tmpStorage;
		    $arrData['cl_invoice_items_back_id']    = $one->id;

            if (isset($one['exp_date'])) {
                $arrData['exp_date'] = $one->exp_date;
            }

            if (isset($one['batch'])) {
                $arrData['batch'] = $one->batch;
            }
            if (isset($one['description'])) {
                $arrData['description'] = $one->description;
            }

            //bdump($one,'000');
		    //bdump($arrData,'prvni');
		    //16.12.2018 - found cl_store_move row. 
		    //(isset($one['cl_store_move_id']) && $one->cl_store_move_id == NULL) ||
		     //if ($one->cl_store_move_id == NULL){
		    if ( !isset($one['cl_store_move_id']) || is_null($one['cl_store_move_id'])) {
                //If there is no cl_store_move_id we have to insert new one
                $row = $this->StoreMoveManager->insert($arrData);
                $arrData['id']   = $row->id;
                //28.12.2017
                //we need tmpDataItemMove without quantity because in previous steps were storage movement deleted
                $tmpDataItemMove = $this->StoreMoveManager->find($row['id']);
		    }else{
                $arrData['id']   = $one->cl_store_move_id;
                $tmpDataItemMove = $one->cl_store_move;
                //$this->StoreMoveManager->find($one->cl_store_move_id);
		    }
		    


		    if ($package){
		        if ($one->cl_pricelist->in_package > 0 && $this->settings->order_package == 0)
		            $inPackage = $one->cl_pricelist->in_package;
		        else
		            $inPackage = 1;

                $arrData['s_in']            = $arrData['s_in'] * $inPackage;
                $arrData['price_s']         = $arrData['price_s'] / $inPackage;
                $arrData['price_in']        = $arrData['price_in'] / $inPackage;
                $arrData['price_in_vat']    = $arrData['price_in_vat'] / $inPackage;
                $arrData['price_e2']        = $arrData['price_in'] * $arrData['s_in'];
                $arrData['price_e2_vat']    = $arrData['price_in_vat'] * $arrData['s_in'];
            }

		    //bdump($arrData,'druha');
		    $this->StoreMoveManager->update($arrData);

		    $tmpParentData = $this->StoreDocsManager->find($docId);

		    $newData = $this->GiveInStore($arrData,$tmpDataItemMove,$tmpParentData);		
		    //dump($newData);
		    $this->StoreMoveManager->update($newData);

		    //16.12.2018 - update cl_Store_move_id 
		    if ($itemsManager->tableName != 'cl_order_items'){		    
			    $one->update(['cl_store_move_id' => $arrData['id']]);
		    }

		    //13.04.2019 - relation to cl_store_docs - typicaly for income from orders
		    if ($itemsManager->tableName == 'cl_order_items'){
			    $one->update(['cl_store_docs_id' => $docId, 'cl_store_move_id' => $newData['id']]);
		    }

		    return;

		}	
	}
	
	
    public function deleteItemStoreMove($tmpLine)
    {
	    //15.12.2018 - if line has connection with cl_store_move, we must delete store_move
	    if (!is_null($tmpLine->cl_store_move_id))
	    {
            //delete from cl_store_move
            if ($tmpStoreMove = $this->StoreMoveManager->find($tmpLine->cl_store_move_id))
            {
                try{
                    $arrStores = array();
                    $usedMoves = array();
                    $tmpStoreOut = $this->StoreOutManager->findAll()->where('cl_store_move_id = ? ', $tmpStoreMove['id']);
                    foreach($tmpStoreOut as $keyOut => $oneOut)
                    {
                        $arrStores[$oneOut->cl_store_id] = $oneOut->cl_store_id;
                        $usedMoves[$oneOut->cl_store_move_in_id] = $oneOut->cl_store_move_in_id;
                    }

                    $tmpLine->update(array('cl_store_move_id' => NULL));


                    $this->StoreMoveManager->find($tmpStoreMove->id)->delete();
                    //update cl_pricelist and cl_store
                    //bdump($tmpArrStoreMove);
                    $tmpArrStoreMove['cl_store_docs_id'] = null;
                    foreach($arrStores as $one)
                    {
                        $tmpArrStoreMove = array('cl_store_id'	    => $one,
                                                'cl_pricelist_id'   => $tmpStoreMove['cl_pricelist_id'],
                                                'cl_store_docs_id'  => $tmpStoreMove['cl_store_docs_id']);
                        $this->updateStore($tmpArrStoreMove, NULL, NULL, $usedMoves);
                    }


    //				$this->updateStore2(array($tmpStoreMove['cl_store_id'] => $tmpStoreMove['cl_store_id']), $tmpStoreMove, $tmpLine, $tmpLine->cl_company_id);
                    if (!is_null($tmpArrStoreMove['cl_store_docs_id'])) {
                        $this->updateSum($tmpArrStoreMove['cl_store_docs_id']);
                    }
                }catch (Exception $e) {
                    $errorMess = $e->getMessage();
                    //$this->flashMessage($errorMess,'danger');
                }

            }
	    }	
    }
    
    
    public function getUnderLimits($storages, $suppliers)
    {
        $data = $this->findAll();
        if (count($storages) > 0){
            $data = $data->where('cl_store.cl_storage_id IN ?', $storages);
        }
        if (count($suppliers) > 0){
            $data = $data->where('cl_pricelist.cl_partners_book_id IN ? OR cl_pricelist.cl_partners_book_id2 IN ? OR
                                            cl_pricelist.cl_partners_book_id3 IN ? OR cl_pricelist.cl_partners_book_id4 IN ?', $suppliers, $suppliers, $suppliers, $suppliers);
        }
        //19.02.2022 update cl_store.quantity
        foreach($data as $key => $one){
            $quantity = $this->StoreMoveManager->findAll()->where('cl_store_id = ? AND cl_pricelist_id = ?', $one['id'], $one['cl_pricelist_id'])->
                            sum('s_end');
            $one->update(['quantity' => $quantity]);
        }

        $data = $data->where('cl_pricelist.cl_partners_book_id IS NOT NULL')->
                        select('IF(cl_pricelist_limits.quantity_req > 0, cl_pricelist_limits.quantity_req,cl_pricelist_limits.quantity_min) - SUM(`cl_store`.`quantity`) AS quantity, cl_pricelist_limits.quantity_req, cl_pricelist_limits.quantity_min, cl_pricelist.id AS cl_pricelist_id, cl_pricelist.identification, cl_pricelist.item_label,'
                            . 'cl_pricelist.vat, cl_pricelist.price_s, cl_pricelist.unit AS units, '
                            . 'cl_pricelist.cl_partners_book_id,cl_pricelist.cl_partners_book_id2,cl_pricelist.cl_partners_book_id3,cl_pricelist.cl_partners_book_id4, cl_store.cl_storage_id AS cl_storage_id')->
                        group('cl_pricelist.id, cl_store.cl_storage_id')->
                        having('(SUM(cl_store.quantity) < cl_pricelist_limits.quantity_min)')->
                        order('cl_pricelist.cl_partners_book_id DESC, cl_pricelist.item_label ASC');

        return $data;
    }

    public function getUnderLimitsReq($storages, $suppliers)
    {
        $data = $this->findAll();
        if (count($storages) > 0){
            $data = $data->where('cl_store.cl_storage_id IN ?', $storages);
        }
        if (count($suppliers) > 0){
            $data = $data->where('cl_pricelist.cl_partners_book_id IN ? OR cl_pricelist.cl_partners_book_id2 IN ? OR
                                            cl_pricelist.cl_partners_book_id3 IN ? OR cl_pricelist.cl_partners_book_id4 IN ?', $suppliers, $suppliers, $suppliers, $suppliers);
        }
        //19.02.2022 update cl_store.quantity
        foreach($data as $key => $one){
            $quantity = $this->StoreMoveManager->findAll()->where('cl_store_id = ? AND cl_pricelist_id = ?', $one['id'], $one['cl_pricelist_id'])->
            sum('s_end');
            $one->update(['quantity' => $quantity]);
        }

        $data = $data->where('cl_pricelist.cl_partners_book_id IS NOT NULL')->
                            select('IF(cl_pricelist_limits.quantity_req > 0, cl_pricelist_limits.quantity_req,cl_pricelist_limits.quantity_min) - SUM(`cl_store`.`quantity`) AS quantity, cl_pricelist_limits.quantity_req, cl_pricelist_limits.quantity_min, cl_pricelist.id AS cl_pricelist_id, cl_pricelist.identification, cl_pricelist.item_label,'
                                . 'cl_pricelist.vat, cl_pricelist.price_s, cl_pricelist.unit AS units, '
                                . 'cl_pricelist.cl_partners_book_id,cl_pricelist.cl_partners_book_id2,cl_pricelist.cl_partners_book_id3,cl_pricelist.cl_partners_book_id4, cl_store.cl_storage_id AS cl_storage_id')->
                            group('cl_pricelist.id, cl_store.cl_storage_id')->
                            having('(SUM(cl_store.quantity) < cl_pricelist_limits.quantity_req)')->
                            order('cl_pricelist.cl_partners_book_id DESC, cl_pricelist.item_label ASC');

        return $data;
    }




    /**
     * @param $line(array(cl_company_id, cl_pricelist_id)
     */
    public function updateQuantity($line)
    {
        //update quantity on cl_pricelist
        if (!isset($line['cl_company_id']))
            $quantity = $this->StoreMoveManager->findBy(array('cl_pricelist_id' => $line['cl_pricelist_id']))->sum('s_in - s_out');
        else
            $quantity = $this->StoreMoveManager->findAllTotal()->where(array('cl_company_id' => $line['cl_company_id'],
                'cl_pricelist_id' => $line['cl_pricelist_id']))->sum('s_in - s_out');

        $arrUpdate = new \Nette\Utils\ArrayHash;
        $arrUpdate['id'] = $line['cl_pricelist_id'];
        $arrUpdate['quantity']  = $quantity;
        if (!isset($line['cl_company_id']))
            $this->PriceListManager->update($arrUpdate);
        else{
            $arrUpdate['cl_company_id'] = $line['cl_company_id'];
            $this->PriceListManager->updateForeign($arrUpdate);
        }
    }

    /**
     * method called from getMovesPeriod to update sums of waiting amount for suppliers
     */
    public function updateSupplierSum($cl_storage_id)
    {
        $sumPartners = $this->findAll()->where('cl_store.cl_storage_id IS NOT NULL AND cl_pricelist.cl_partners_book_id IS NOT NULL AND cl_store.cl_storage_id = ?', $cl_storage_id)->
                                        select('SUM(cl_store.quantity_to_order * cl_store.price_s) AS sumPartner, cl_pricelist.cl_partners_book.id AS cl_partners_book_id, cl_store.cl_storage_id, cl_store.id AS cl_store_id')->
                                        group('cl_pricelist.cl_partners_book_id,cl_store.cl_storage_id');
        //$total = $sumPartners->count('cl_store.cl_storage_id');
        //$i = 0;
        foreach ($sumPartners as $key => $one)
        {
            //  $this->UserManager->setProgressBar($i, $total, $this->user->getId());

            $tmpPartner = $this->PartnersManager->find($one->cl_partners_book_id);
            if ($tmpPartner){
                $tmpPartner->update(array('supplier_sum' => $one->sumPartner));
            }
            //$i++;
        }
    }


    public function getMovesPeriod($date_from, $date_to, $storages, $suppliers, $useLimits = FALSE)
    {
        ////profiler::start();
        //19.08.2019 - update cl_store_move.price_s  because it could be 0 in case of give out to minus values
        //
        foreach($storages as $one)
        {
            $this->updateSupplierSum($one);
        }

        $this->StoreMoveManager->updatePriceS($storages, $date_from);
        $data = $this->StoreMoveManager->findAll()->where('cl_store_docs.doc_type = 1 AND cl_store_docs.doc_date >= ? AND cl_store_docs.doc_date <= ?', $date_from, $date_to);
        //bdump($storages,'storages');
        //bdump(count($storages),'storages');

        if (count($storages) > 0){
            $data = $data->where('cl_store_move.cl_storage_id IN ?', $storages);
        }
        if (count($suppliers) > 0){
            $data = $data->where('cl_pricelist.cl_partners_book_id IN ?', $suppliers);
        }

        if ($useLimits) {
            $data = $data->where('cl_pricelist.cl_partners_book_id IS NOT NULL')->
                                    select( 'SUM((cl_store_move.s_out + cl_store.quantity_to_order) * cl_store_move.price_s) + cl_pricelist.cl_partners_book.supplier_sum AS test,'
                                        . 'SUM(cl_store_move.s_out + cl_store.quantity_to_order)  AS quantity, cl_pricelist.id AS cl_pricelist_id, cl_pricelist.identification, cl_pricelist.item_label,'
                                        . 'cl_pricelist.vat, cl_pricelist.price_s, cl_pricelist.unit AS units, cl_pricelist.cl_partners_book_id,cl_pricelist.cl_partners_book_id2,cl_pricelist.cl_partners_book_id3,cl_pricelist.cl_partners_book_id4, '
                                        . 'cl_store_move.cl_storage_id AS cl_storage_id,'
                                        . 'cl_pricelist.cl_partners_book.min_order, cl_store_id,cl_pricelist.in_package, cl_pricelist.cl_partners_book.supplier_sum ')->
                                    group('cl_partners_book_id')->
                                    having('(SUM((cl_store_move.s_out + cl_store.quantity_to_order) * cl_store_move.price_s) + cl_pricelist.cl_partners_book.supplier_sum  >= cl_pricelist.cl_partners_book.min_order) 
                                    AND SUM(cl_store_move.s_out + cl_store.quantity_to_order) / IF(cl_pricelist.in_package>0, cl_pricelist.in_package, 1) >= 1 ')->
                                    order('cl_pricelist.cl_partners_book_id ASC, cl_pricelist.identification ASC');
            $arrPartnersToOrder = $data->fetchPairs('cl_partners_book_id','cl_partners_book_id');
            //echo($data->getSql());

            $data = $this->StoreMoveManager->findAll()->where('cl_store_docs.doc_type = 1 AND cl_store_docs.doc_date >= ? AND cl_store_docs.doc_date <= ?', $date_from, $date_to);
            if (count($storages) > 0){
                $data = $data->where('cl_store_move.cl_storage_id IN ?', $storages);
            }
            $data = $data->where('cl_pricelist.cl_partners_book_id IN ?', $arrPartnersToOrder);
            $data = $data->where('cl_pricelist.cl_partners_book_id IS NOT NULL')->
                                    select('SUM(cl_store_move.s_out + cl_store.quantity_to_order) AS quantity, cl_pricelist.id AS cl_pricelist_id, cl_pricelist.identification, cl_pricelist.item_label,'
                                        . 'cl_pricelist.vat, cl_pricelist.price_s, cl_pricelist.unit AS units, cl_pricelist.cl_partners_book_id,cl_pricelist.cl_partners_book_id2,cl_pricelist.cl_partners_book_id3,cl_pricelist.cl_partners_book_id4, '
                                        . 'cl_store_move.cl_storage_id AS cl_storage_id,'
                                        . 'cl_pricelist.cl_partners_book.min_order, cl_store_id,cl_pricelist.in_package')->
                                    group('cl_pricelist_id')->
                                    having('SUM(cl_store_move.s_out + cl_store.quantity_to_order) / IF(cl_pricelist.in_package>0, cl_pricelist.in_package, 1) >= 1 ')->
                                    order('cl_pricelist.cl_partners_book_id ASC, cl_pricelist.identification ASC');

            $nodata = $this->StoreMoveManager->findAll()->where('cl_store_docs.doc_type = 1 AND cl_store_docs.doc_date >= ? AND cl_store_docs.doc_date <= ?', $date_from, $date_to);
            if (count($storages) > 0){
                $nodata = $nodata->where('cl_store_move.cl_storage_id IN (?)', $storages);
            }
            if (count($suppliers) > 0){
                $nodata = $nodata->where('cl_pricelist.cl_partners_book_id IN (?)', $suppliers);
            }
            $nodata2 = clone $nodata;

            $nodata = $nodata->where('cl_pricelist.cl_partners_book_id IS NOT NULL')->
                                    select( 'SUM((cl_store_move.s_out + cl_store.quantity_to_order) * cl_store_move.price_s) + cl_pricelist.cl_partners_book.supplier_sum AS test,'
                                        . 'SUM(cl_store_move.s_out + cl_store.quantity_to_order) AS quantity, cl_pricelist.id AS cl_pricelist_id, cl_pricelist.identification, cl_pricelist.item_label,'
                                        . 'cl_pricelist.vat, cl_pricelist.price_s, cl_pricelist.unit AS units, cl_pricelist.cl_partners_book_id,cl_pricelist.cl_partners_book_id2,cl_pricelist.cl_partners_book_id3,cl_pricelist.cl_partners_book_id4, '
                                        . 'cl_store_move.cl_storage_id AS cl_storage_id,'
                                        . 'cl_pricelist.cl_partners_book.min_order, cl_store_id,cl_pricelist.in_package, cl_pricelist.cl_partners_book.supplier_sum')->
                                    group('cl_partners_book_id')->
                                    having('(SUM((cl_store_move.s_out + cl_store.quantity_to_order) * cl_store_move.price_s) + cl_pricelist.cl_partners_book.supplier_sum < cl_pricelist.cl_partners_book.min_order)  
                                    OR SUM(cl_store_move.s_out + cl_store.quantity_to_order) / IF(cl_pricelist.in_package>0, cl_pricelist.in_package, 1) < 1 ')->
                                    order('cl_pricelist.cl_partners_book_id ASC, cl_pricelist.identification ASC');
            $arrPartnersToNotOrder = $nodata->fetchPairs('cl_partners_book_id','cl_partners_book_id');

            $nodata2 = $nodata2->where('cl_pricelist.cl_partners_book_id IS NOT NULL')->
                                    select('SUM(cl_store_move.s_out + cl_store.quantity_to_order) AS quantity, cl_pricelist.id AS cl_pricelist_id, cl_pricelist.identification, cl_pricelist.item_label,'
                                        . 'cl_pricelist.vat, cl_pricelist.price_s, cl_pricelist.unit AS units, cl_pricelist.cl_partners_book_id,cl_pricelist.cl_partners_book_id2,cl_pricelist.cl_partners_book_id3,cl_pricelist.cl_partners_book_id4, '
                                        . 'cl_store_move.cl_storage_id AS cl_storage_id,'
                                        . 'cl_pricelist.cl_partners_book.min_order, cl_store_id,cl_pricelist.in_package')->
                                    group('cl_partners_book_id')->
                                    having('SUM((cl_store_move.s_out + cl_store.quantity_to_order) / IF(cl_pricelist.in_package>0, cl_pricelist.in_package, 1)) < 1 ')->
                                    order('cl_pricelist.cl_partners_book_id ASC, cl_pricelist.identification ASC');
            $arrPartnersToNotOrder2 = $nodata2->fetchPairs('cl_partners_book_id','cl_partners_book_id');
            /*bdump($arrPartnersToOrder,'arrPartnersToOrder');
            bdump($arrPartnersToNotOrder,'arrPartnersToNotOrder');
            bdump($arrPartnersToNotOrder2,'arrPartnersToNotOrder2');*/

            $nodata = $this->StoreMoveManager->findAll()->where('cl_store_docs.doc_type = 1 AND cl_store_docs.doc_date >= ? AND cl_store_docs.doc_date <= ?', $date_from, $date_to);
            if (count($storages) > 0){
                $nodata = $nodata->where('cl_store_move.cl_storage_id IN (?)', $storages);
            }
            $nodata = $nodata->where('cl_pricelist.cl_partners_book_id IN (?) OR cl_pricelist.cl_partners_book_id IN (?)', $arrPartnersToNotOrder, $arrPartnersToNotOrder2);

            $nodata = $nodata->where('cl_pricelist.cl_partners_book_id IS NOT NULL')->
                            select('SUM((cl_store_move.s_out + cl_store.quantity_to_order) * cl_store_move.price_s) + cl_pricelist.cl_partners_book.supplier_sum AS test,
                                    SUM(cl_store_move.s_out + cl_store.quantity_to_order) AS quantity, cl_pricelist.id AS cl_pricelist_id, cl_pricelist.identification, cl_pricelist.item_label,'
                                . 'cl_pricelist.vat, cl_pricelist.price_s, cl_pricelist.unit AS units, cl_pricelist.cl_partners_book_id,cl_pricelist.cl_partners_book_id2,cl_pricelist.cl_partners_book_id3,cl_pricelist.cl_partners_book_id4, '
                                . 'cl_store_move.cl_storage_id AS cl_storage_id,'
                                . 'cl_pricelist.cl_partners_book.min_order, cl_store_id,cl_pricelist.in_package, cl_pricelist.cl_partners_book.supplier_sum')->
                            group('cl_pricelist.id')->
                            having('(SUM((cl_store_move.s_out + cl_store.quantity_to_order) * cl_store_move.price_s) + cl_pricelist.cl_partners_book.supplier_sum < cl_pricelist.cl_partners_book.min_order)  
                                    OR SUM(cl_store_move.s_out + cl_store.quantity_to_order) / IF(cl_pricelist.in_package>0, cl_pricelist.in_package, 1) < 1 ')->
                            order('cl_pricelist.cl_partners_book_id ASC, cl_pricelist.identification ASC');


            ////profiler::start();
            foreach($nodata as $key => $one)
            {
                bdump($one);
                $this->update(array('id' => $one->cl_store_id, 'quantity_to_order' => new Nette\Database\SqlLiteral('quantity_to_order + ' . $one->quantity)));
                    //$one->quantity));
            }



            ////profiler::finish('nodata after update');
            $tmpArr = json_decode($this->settings->order_period_last, true);
            //bdump($tmpArr);
            foreach($storages as $one)
            {
                //bdump($one, 'cl_storage_id');
                $tmpArr[$one] = array($date_from, $date_to);
                $this->updateSupplierSum($one);
            }
            //bdump($tmpArr);
            $tmpJson = json_encode($tmpArr);
            $this->settings->update(array('order_period_last' => $tmpJson));

        }else{
                //no limits
                $data = $data->where('cl_pricelist.cl_partners_book_id IS NOT NULL')->
                                        select('SUM(cl_store_move.s_out) AS quantity, cl_pricelist.id AS cl_pricelist_id, cl_pricelist.identification, cl_pricelist.item_label,'
                                            . 'cl_pricelist.vat, cl_pricelist.price_s, cl_pricelist.unit AS units, cl_pricelist.cl_partners_book_id,cl_pricelist.cl_partners_book_id2,cl_pricelist.cl_partners_book_id3,cl_pricelist.cl_partners_book_id4, '
                                        . 'cl_store_move.cl_storage_id AS cl_storage_id')->
                                        group('cl_pricelist.id, cl_store_move.cl_storage_id')->
                                        order('cl_pricelist.cl_partners_book_id ASC, cl_pricelist.identification ASC');
        }
        //dump($data,'data');
        //die;
        ////profiler::finish('end of getMovesPeriod');
        return $data;
    }

    public function repairBalance($cl_pricelist_id, $cl_storage_id = NULL)
    {
        //try {

            ////profiler::start();
            if (is_null($cl_pricelist_id)) {
                $all = $this->findAll()->where('cl_storage_id = ?', $cl_storage_id);
            } elseif (is_null($cl_storage_id)) {
                $all = $this->findAll()->where('cl_pricelist_id = ?', $cl_pricelist_id);
            } else {
                $all = $this->findAll()->where('cl_pricelist_id = ? AND cl_storage_id = ?', $cl_pricelist_id, $cl_storage_id);
            }
        session_write_close();
            $value = 0;
            $this->userManager->setProgressBar(0, 4, $this->user->getId(), 'Nulování výdejů.');
            $this->database->beginTransaction(); // zahájení transakce
            $all->update(['quantity' => 0,
								'change_by' => $this->user->getIdentity()->name,
								'changed' => new \Nette\Utils\DateTime]);


            $tmpPricelist = $this->findAll()->select('DISTINCT cl_pricelist.id');
            if (is_null($cl_pricelist_id)) {
                $tmpStoreIn = $this->StoreMoveManager->findAll()->
                                                    where('cl_store_move.cl_storage_id = ? AND  cl_store_move.s_in > 0', $cl_storage_id);

                $tmpStoreIn->update(['s_end' => new Nette\Database\SqlLiteral('s_in')]);


                $tmpPricelist = $tmpPricelist->where('cl_store.cl_storage_id = ?', $cl_storage_id);
                //find outcome to minus
                //it's every cl_store record having cl_store_move with outcome and no income
                //those outcome cl_store_move we have to assign to cl_store with any cl_store_move income
                //31.10.2019 - from now we work with all cl_store_move because we will reconstruct all cl_store_out
                $tmpStoreMinus = $this->StoreMoveManager->findAll()->
		                where('cl_store_move.cl_storage_id = ?', $cl_storage_id)->
                		order('cl_store_move.cl_storage_id ASC, cl_store_move.cl_pricelist_id ASC, cl_store_docs.doc_type ASC, cl_store_docs.doc_date ASC, cl_store_move.id');


                foreach ($tmpStoreMinus as $key => $one) {
                    if ($one->cl_store_docs->doc_type == 1) {
                        $tmpStoreOut = $this->StoreOutManager->findAll()->
                        					where('cl_store_move_id', $one->id);
                        $tmpStoreOut->delete();
                    }
                }

                $tmpStoreMain = $this->findAll()->where('cl_storage_id = ?', $cl_storage_id);

            } elseif (is_null($cl_storage_id)) {

                $tmpStoreIn = $this->StoreMoveManager->findAll()->
                				where('cl_store_move.cl_pricelist_id = ? AND cl_store_move.s_in > 0', $cl_pricelist_id);
                $tmpStoreIn->update(array('s_end' => new Nette\Database\SqlLiteral('s_in')));

                $tmpPricelist = $tmpPricelist->where('cl_store.cl_pricelist_id = ?', $cl_pricelist_id);
                //find outcome to minus
                //it's every cl_store record having cl_store_move with outcome and no income
                //those outcome cl_store_move we have to assign to cl_store with any cl_store_move income
                $tmpStoreMinus = $this->StoreMoveManager->findAll()->
								where('cl_store_move.cl_pricelist_id = ? AND cl_store_move.cl_storage_id IS NOT NULL', $cl_pricelist_id)->
								order('cl_store_docs.doc_type ASC, cl_store_docs.doc_date ASC, cl_store_move.id');


                foreach ($tmpStoreMinus as $key => $one) {
                    if ($one->cl_store_docs->doc_type == 1) {
                        $tmpStoreOut = $this->StoreOutManager->findAll()->
                        where('cl_store_move_id', $one->id);
                        $tmpStoreOut->delete();
                    }
                }
                $tmpStoreMain = $this->findAll()->where('cl_pricelist_id = ?', $cl_pricelist_id);
            } else {

                $tmpStoreIn = $this->StoreMoveManager->findAll()->
                where('cl_store_move.cl_pricelist_id = ? AND cl_store_move.s_in > 0 AND cl_store_move.cl_storage_id = ? ', $cl_pricelist_id, $cl_storage_id);
                $tmpStoreIn->update(array('s_end' => new Nette\Database\SqlLiteral('s_in')));

                $tmpPricelist = $tmpPricelist->where('cl_store.cl_pricelist_id = ? AND cl_store.cl_storage_id = ?', $cl_pricelist_id, $cl_storage_id);
                    //find outcome to minus
                    //it's every cl_store record having cl_store_move with outcome and no income
                    //those outcome cl_store_move we have to assign to cl_store with any cl_store_move income
                $tmpStoreMinus = $this->StoreMoveManager->findAll()->
                    where('cl_store_move.cl_pricelist_id = ? AND cl_store_move.cl_storage_id = ?', $cl_pricelist_id, $cl_storage_id)->
                    order('cl_store_docs.doc_type ASC, cl_store_docs.doc_date ASC, cl_store_move.id');


                foreach ($tmpStoreMinus as $key => $one) {
                    if ($one->cl_store_docs->doc_type == 1) {
                        $tmpStoreOut = $this->StoreOutManager->findAll()->
                            where('cl_store_move_id', $one->id);
                        $tmpStoreOut->delete();
                    }
                }

                $tmpStoreMain = $this->findAll()->where('cl_pricelist_id = ? AND cl_storage_id = ?', $cl_pricelist_id, $cl_storage_id);

            }
            $this->database->commit();

            //30.10.2019 - test of erase all cl_store_out in next step cl_store_out records will be recreated
            $this->userManager->setProgressBar(1, 4, $this->user->getId(), 'Mazání výdejů ze zásob.');
            $this->database->beginTransaction(); // zahájení transakcev

            Debugger::timer();
            Debugger::log('START ----> Vymazání zásob bez pohybů', 'repair_balance');
            $tmpStoreMain->update(['quantity' => 0]);
                $this->userManager->setProgressBar(2, 4, $this->user->getId(), 'Vymazání zásob bez pohybů.');
                foreach ($tmpStoreMain as $key => $one) {
                //    Debugger::log('$key = ' . $key, 'repair_balance');
                //    Debugger::log('$cl_pricelist_id = ' . $one['cl_pricelist_id'], 'repair_balance');
                //    Debugger::log('$cl_company_id = ' . $one['cl_company_id'], 'repair_balance');
                    $this->deleteStoreWMRB($one->cl_pricelist_id, $one->cl_company_id);
                }
            Debugger::log('END ----> Vymazání zásob bez pohybů', 'repair_balance');
            $this->database->commit();



//            die;

           // //profiler::finish('end of data prepare');

           // //profiler::start();
            $this->userManager->setProgressBar(2, 4, $this->user->getId(), 'Nové odepsání výdejů.');
            $this->database->beginTransaction(); // zahájení transakce
            $leftOnMove = 0;
            $max = count($tmpStoreMinus);
            $counter = 0;
            $arrVAPupdate = [];
            Debugger::log('START ----> Nové odepsání výdejů', 'repair_balance');
            foreach ($tmpStoreMinus as $key => $one) {
             //   Debugger::log('Odepisujeme $cl_pricelist_id = ' . $one->cl_pricelist_id, 'repair_balance');
                $counter++;
                $this->userManager->setProgressBar($counter, $max, $this->user->getId(), 'Výdeje ' . $counter . '/' . $max);

                //bdump($one->cl_store_docs->doc_type, 'doc_type');
            //    Debugger::log('Doklad $one->cl_store_docs_id = ' . $one->cl_store_docs_id . ' $one->cl_store_docs->doc_number = ' . $one->cl_store_docs['doc_number'], 'repair_balance');
                if ($one->cl_store_docs->doc_type == 1) {

                    $tmpStoreOk = $this->StoreMoveManager->findAll()->select('cl_store_move.price_s, cl_store_docs.doc_date, cl_store_move.id, cl_store.id AS cl_store_id, cl_store.quantity, cl_store_move.s_end, cl_store_move.s_in')->
                                    where('cl_store_move.cl_pricelist_id = ? AND cl_store_docs.doc_type = 0', $one->cl_pricelist_id)->
                                    where('cl_store_move.s_end > 0 AND cl_store_move.cl_storage_id = ?', $one->cl_storage_id)->
                                    order('cl_store.exp_date ASC, cl_store_docs.doc_date ASC, cl_store_move.id');

                    //$quantity_on_store = $one->cl_store->quantity;
                    $toOut = $one->s_out;
                    $priceS = 0;
                    //$this->updateVAP($one->cl_store_id);
            //        Debugger::log('Výdej k odepsání $cl_store_move.id = ' . $key . '  $toOut = ' . $toOut, 'repair_balance');
                    foreach ($tmpStoreOk as $keyStore => $oneStore) {
                        if ($toOut == 0) {
                            break;
                        }
                        if ($toOut <= $oneStore->s_end) {
                            $quant = $toOut;
                            $leftOnMove = $oneStore->s_end - $toOut;
                        } else {
                            $quant = $oneStore->s_end;
                            $leftOnMove = 0;
                        }
                        if ($one->cl_storage->price_method == 0) {
                            //FiFo case
                            $priceS += $quant * $oneStore->price_s;
                        }else{
                            //VAP case
                            $priceS += $quant * $oneStore->cl_store->price_s;
                        }
            //            Debugger::log('Odepsáno  $quant = ' . $quant . ' z $oneStore.id = ' . $keyStore , 'repair_balance');
                        $toOut = $toOut - $quant;
                        //$this->database->beginTransaction(); // zahájení transakce
                        $this->StoreMoveManager->update(['id' => $one->id, 'cl_store_id' => $oneStore->cl_store_id]);
            //            Debugger::log('$this->StoreMoveManager->update([id => ' . $one->id . ' cl_store_id => ' . $oneStore->cl_store_id . '])' , 'repair_balance');
                        //bdump($oneStore->s_end, 'oneStore->s_end');
                        //bdump($quant, 'outcome quant');
                        if (!is_null($oneStore->cl_store_id))
                            $prices2 = $oneStore->cl_store->price_s;
                        else
                            $prices2 = 0;

                        $this->StoreOutManager->insert(['cl_store_move_id' => $key, 'cl_store_move_in_id' => $oneStore->id, 's_total' => $oneStore->s_end - $quant,
                                                             's_out' => $quant, 'price_s' => $prices2, 'cl_store_id' => $oneStore->cl_store_id]);
            //            Debugger::log('$this->StoreMoveManager->Insert(.....)' , 'repair_balance');
                        $oneStore->update(['s_end' => $oneStore->s_end - $quant]);
            //            Debugger::log('$oneStore->update([s_end => $oneStore->s_end - $quant])' , 'repair_balance');

                        //$this->database->commit();
                    }
                   // $this->database->commit();
                    $dataOne = array();
                    if ($one->s_out > 0) {
                        $dataOne['price_s'] = $priceS / $one->s_out;
                    } else {
                        $dataOne['price_s'] = 0;
                    }
                    $dataOne['s_out_fin'] = $this->StoreOutManager->findBy(['cl_store_move_id' => $one['id']])->sum('s_out');
                    $one->update($dataOne);
            //        Debugger::log('Odepsáno $cl_pricelist_id = ' . $one->cl_pricelist_id, 'repair_balance');
                } elseif ($one->cl_store_docs->doc_type == 0) {
                    //  bdump($one->cl_store->quantity, 'quantity '.$one->id);
//                    bdump($one->s_end, 's_end '.$one->id);
                    //$this->database->beginTransaction(); // zahájení transakce
                    //$one->update(array('s_total' => $one->cl_store->quantity + $one->s_end));
                    $one->update(['s_total' => $one->s_end]);
            //        Debugger::log('Příjem k připsání $one->update([s_total => ' . $one->s_end . '])', 'repair_balance');
                    if (!is_null($one->cl_store_id)) {
                        $one->cl_store->update(['quantity' => $one->cl_store->quantity + $one->s_end]);
                        $arrVAPupdate[$one->cl_store_id] = $one->cl_store_docs['doc_date'];
                    }

                    //$this->database->commit();
                }
            }
            $maxVap = count($arrVAPupdate);
            $counterVAP = 1;
            foreach($arrVAPupdate as $key => $one){
                $this->userManager->setProgressBar($counter, $max, $this->user->getId(), 'Update VAP ' . $counterVAP . '/' . $maxVap);
                $this->updateVAP($key, $one);
             //   Debugger::log('$this->updateVAP(' . $key . ', ' . $one . ')', 'repair_balance');
                $counterVAP++;
            }

            Debugger::log('END ----> Nové odepsání výdejů', 'repair_balance');
            $this->database->commit();
			//profiler::finish('end nové odepsání výdejů');

      //  die;

            if (is_null($cl_storage_id)) {
				$this->updateStoreAll($tmpStoreMain, FALSE);
				////profiler::start();
				$this->updatePricelistAll($tmpPricelist);
			}

            
            $this->userManager->setProgressBar(4, 4, $this->user->getId(), 'Hotovo.');
            $this->userManager->resetProgressBar($this->user->getId());
            return('');
        //}catch (\Exception $e){
    //        //bdump($e);
  //          //$this->database->rollback(); // vrácení zpět
//            return ($e);
//        }


    }
    
    
    public function updateStoreAll($tmpStoreMain, $noVAP = FALSE)
	{
        session_write_close();
            $this->userManager->setProgressBar(3, 5, $this->user->getId(), 'Aktualizace zásob.');
			$max = count($tmpStoreMain);
			$counter = 0;
            $this->database->beginTransaction(); // zahájení transakce
            foreach ($tmpStoreMain as $key => $one)
            {
            	$counter++;
				$this->userManager->setProgressBar($counter, $max, $this->user->getId(), 'Aktualizace zásob ' . $counter . '/' . $max);
                //$one->update(array('quantity' => $one->related('cl_store_move')->where('s_in > 0')->sum('s_end')));
				$s_end = $one->related('cl_store_move')->where('s_in > 0')->sum('s_end');
				$s_minus = $one->related('cl_store_move')->where('s_out > 0')->sum('s_out - s_out_fin');
				$one->update(array('quantity' => $s_end - $s_minus));
				if (!$noVAP) {
                    $this->updateVAP($one->id);
                }
            }
            $this->userManager->resetProgressBar($this->user->getId());
        $this->database->commit();
		
	}

	public function updatePricelistAll($tmpPricelist)
	{
        session_write_close();
		$this->userManager->setProgressBar(4, 5, $this->user->getId(), 'Aktualizace ceníku.');
	    $this->database->beginTransaction(); // zahájení transakce
		foreach ($tmpPricelist as $key => $one) {
			$quantity = $this->findAll()->where('cl_pricelist_id = ? ', $one->id)->sum('cl_store.quantity');
			/*if (!is_null($quantity)){
				$quantNum = $quantity->quantity;
			}else{
				$quantNum = 0;
			}*/
			$this->PriceListManager->find($one->id)->update(array('quantity' => $quantity));
		}
        $this->database->commit();
        $this->userManager->resetProgressBar($this->user->getId());
		return "";
		//$this->database->commit();
	}



    public function repairOutcomePriceS($tmpPricelistId = NULL)
    {
        //1. fill missing cl_pricelist.price_s with last not zero income cl_store_move.price_s
        //2. cl_store_move.price_s for income
        //3. repair of cl_store_move.price_s and cl_store_move.profit for outcome
        //4. repair of cl_store_docs.price_s and cl_store_docs.profit, cl_store_docs.profit_abs
        try{
            session_write_close();
            //1. and 2.

            $this->database->beginTransaction();
            if (is_null($tmpPricelistId)){
                $data = $this->StoreMoveManager->findAll()->select('cl_pricelist_id, cl_pricelist.identification')->where('s_in > 0 AND (cl_store_move.price_s = 0 OR cl_store_move.price_in = 0)')->group('cl_pricelist_id');
            }else{
                $data = $this->StoreMoveManager->findAll()->select('cl_pricelist_id, cl_pricelist.identification')->
                                                            where('cl_pricelist_id = ? AND s_in > 0 AND (cl_store_move.price_s = 0 OR cl_store_move.price_in = 0)', $tmpPricelistId)->group('cl_pricelist_id');
            }
            $count = 0;
            $maxCount = count($data);
            $updateCount = array('price_s' => 0, 'price_in' => 0, 'pricelist' => 0);
            foreach($data as $key => $one)
            {
                $this->userManager->setProgressBar($count, $maxCount, $this->user->getId(), '1/9 price_in, price_s');
                //find last not 0 price_s
                $tmpPrice = $this->StoreMoveManager->findAll()->where('cl_pricelist_id = ? AND price_s > 0', $one->cl_pricelist_id)->limit(1)->order('id DESC')->fetch();
                if ($tmpPrice)
                {
                    //Debugger::log('RepairOutcomePrices ' . $count . ' / ' . $maxCount . 'price_s refill cl_pricelist_identification: ' . dump($one->identification) . ' tmpPrice_s: ' . dump($tmpPrice->price_s), 'store');
                    $this->database->table('cl_store_move')->where('cl_store_move.cl_pricelist_id IN (?) AND cl_store_move.price_s = 0', $one->cl_pricelist_id)->update(['cl_store_move.price_s' => $tmpPrice->price_s]);
                    $this->database->table('cl_store_move')->where('cl_store_move.cl_pricelist_id IN (?) AND cl_store_move.price_in = 0', $one->cl_pricelist_id)->update(['cl_store_move.price_in' => $tmpPrice->price_s]);

                    if ($one->cl_pricelist->price_s == 0){
                        $this->PriceListManager->update(['id' => $one->cl_pricelist_id, 'cl_pricelist.price_s' => $tmpPrice->price_s]);
                        $updateCount['pricelist']++;
                    }
                }
                $count++;
            }
            $this->database->commit(); // potvrzení
            //Debugger::log('RepairOutcomePrices price_s refill count: ' . dump($count) . ' updated records: ' . dump(implode(", ", $updateCount)), 'store');

            //cl_pricelist
            $this->database->beginTransaction();
            if (is_null($tmpPricelistId)) {
                $data = $this->PriceListManager->findAll()->where('price_s = 0');
            }else{
                $data = $this->PriceListManager->findAll()->where('id = ? AND price_s = 0', $tmpPricelistId);
            }
            $count = 0;
            $maxCount = count($data);
            foreach($data as $key => $one) {
                $this->userManager->setProgressBar($count, $maxCount, $this->user->getId(), '2/9 Ceník price_s');
                $tmpPrice = $this->StoreMoveManager->findAll()->where('cl_pricelist_id = ? AND price_s > 0', $one->id)->limit(1)->order('id DESC')->fetch();
                if ($tmpPrice)
                {
                    $one->update(['price_s' => $tmpPrice->price_s]);
                }
                $count++;
            }
            $this->database->commit(); // potvrzení

            //cl_Store_move
            $this->database->beginTransaction();
            //dump('ted');

            /*$limit = 10000;
            $offset = 0;
            ->limit($limit, $offset)*/
            //$maxCount = $this->StoreMoveManager->findAll()->where('s_out > 0')->count('id');
            if (is_null($tmpPricelistId)) {
                $data = $this->StoreMoveManager->findAll()->where('s_out > 0');
            }else{
                $data = $this->StoreMoveManager->findAll()->where('cl_pricelist_id = ? AND s_out > 0', $tmpPricelistId);
            }
            $count = 0;
            $maxCount = count($data);
            //dump($maxCount);
            //die;
            foreach($data as $key => $one)
            {
                //dump($key);
                //die;
                  $this->userManager->setProgressBar($count, $maxCount, $this->user->getId(), '3/9 Pohyby');
                  $tmpStoreOut = $this->StoreOutManager->findAll()->where('cl_store_move_id = ?', $key);

                  //$tmpStoreOut->update(['price_s' => $one->cl_pricelist->price_s]);
                  foreach($tmpStoreOut as $tmpStoreOutKey => $tmpStoreOutOne) {
                      if ($tmpStoreOutIn = $this->StoreMoveManager->findAll()->where('id = ?', $tmpStoreOutOne['cl_store_move_in_id'])->fetch())
                        $tmpStoreOut->update(['price_s' => $tmpStoreOutIn->price_s]);
                  }

                  //$tmpStoreOut->update(['price_s' => $one->cl_pricelist->price_s]);
                  //foreach($tmpStoreOut as $keySout => $oneSout){
                  //    $oneSout->update(array('id' => $keySout, 'price_s' => $one->cl_pricelist->price_s));
                  //}

                  $priceS = $this->StoreOutManager->findAll()->where('cl_store_move_id = ?', $key)->sum('price_s * s_out');
                  $priceS = $priceS / $one->s_out;
                  if ($priceS > 0) {
                      $profit = (($one->price_e / $priceS) - 1) * 100;
                  }else{
                      $profit = 100;
                  }
                  $one->update(['price_s' => $priceS, 'profit' => $profit]);
                  $count++;
            }
            $this->database->commit(); // potvrzení
        //die;
           // $this->userManager->setProgressBar(2, 5, $this->user->getId(), 'Doklady');
            $this->database->beginTransaction();
            //cl_store_docs
            if (is_null($tmpPricelistId)) {
                $data = $this->StoreDocsManager->findAll();
            }else{
                $data = $this->StoreDocsManager->findAll()->where(':cl_store_move.cl_pricelist_id = ?', $tmpPricelistId);
            }
            $count = 0;
            $maxCount = count($data);
            //dump($data);
            foreach($data as $key => $one)
            {
                $this->userManager->setProgressBar($count, $maxCount, $this->user->getId(), '4/9 Doklady');
                //$priceS = $this->StoreMoveManager->findAll()->where('cl_store_docs_id = ?', $key)->sum('price_s * s_out');
                $priceS = $this->StoreMoveManager->findAll()->where('cl_store_docs_id = ?', $key)->
                                                            select('SUM(price_s * s_out) AS price_s, SUM(price_in * s_in) AS price_e2, SUM(price_in * s_in * (1 + (vat / 100))) AS price_e2_vat')->fetch();
                if ($priceS && $priceS['price_s'] > 0) {
                    $profit = (($one->price_e2 / $priceS['price_s']) - 1) * 100;
                }else{
                    $profit = 100;
                }
                //dump($one['doc_type']);
                //dump($priceS);
                $profitAbs = $one->price_e2 - $priceS['price_s'];
                if ($one['doc_type'] == 1) {
                    $one->update(array('price_s' => $priceS['price_s'], 'profit' => $profit, 'profit_abs' => $profitAbs));
                }elseif ($one['doc_type'] == 0){
                    $one->update(array('price_s' => $priceS['price_s'], 'profit' => $profit, 'profit_abs' => $profitAbs, 'price_in' => $priceS['price_e2'], 'price_in_vat' => $priceS['price_e2_vat']));
                }
                $count++;
            }
            $this->database->commit(); // potvrzení

            $this->database->beginTransaction();
            //cl_invoice_items
            if (is_null($tmpPricelistId)) {
                $data = $this->DeliveryNoteItemsManager->findAll()->where('cl_store_move_id IS NOT NULL');
            }else{
                $data = $this->DeliveryNoteItemsManager->findAll()->where('cl_pricelist_id = ? AND cl_store_move_id IS NOT NULL', $tmpPricelistId);
            }
            $count = 0;
            $maxCount = count($data);
            foreach($data as $key => $one)
            {
                $this->userManager->setProgressBar($count, $maxCount, $this->user->getId(), '5/9 Položky DL');
                //$priceS = $this->StoreMoveManager->findAll()->where('id = ?', $one->cl_store_move_id)->sum('price_s * s_out');
                $priceS = $one->cl_store_move['price_s'];
                $priceS2 = $priceS * $one->quantity;
                if ($priceS2 > 0) {
                    $profit = (($one->price_e2 / $priceS2) - 1) * 100;
                }else{
                    $profit = 100;
                }

                if (!is_null($one->cl_pricelist->cl_pricelist_group_id))
                {
                    $isReturn = $one->cl_pricelist->cl_pricelist_group->is_return_package;
                }else{
                    $isReturn = 0;
                }
                //$one->update(array('price_s' => $priceS, 'is_return_package' => $isReturn, 'profit' => $profit));
                $one->update(array('price_s' => $priceS));
                $count++;
            }
            $this->database->commit(); // potvrzení

            $this->database->beginTransaction();
            //cl_invoice_items
            if (is_null($tmpPricelistId)) {
                $data = $this->DeliveryNoteItemsBackManager->findAll()->where('cl_store_move_id IS NOT NULL');
            }else{
                $data = $this->DeliveryNoteItemsBackManager->findAll()->where('cl_pricelist_id = ? AND cl_store_move_id IS NOT NULL', $tmpPricelistId);
            }
            $count = 0;
            $maxCount = count($data);
            foreach($data as $key => $one)
            {
                $this->userManager->setProgressBar($count, $maxCount, $this->user->getId(), '6/9 Položky zpět DL');
                //$priceS = $this->StoreMoveManager->findAll()->where('id = ?', $one->cl_store_move_id)->sum('price_s * s_out');
                $priceS = $one->cl_store_move->price_s;
                $priceS2 = $priceS * $one->quantity;
                if ($priceS2 > 0) {
                    $profit = (($one->price_e2 / $priceS2) - 1) * 100;
                }else{
                    $profit = 100;
                }

                if (!is_null($one->cl_pricelist->cl_pricelist_group_id))
                {
                    $isReturn = $one->cl_pricelist->cl_pricelist_group->is_return_package;
                }else{
                    $isReturn = 0;
                }
                //$one->update(array('price_s' => $priceS, 'is_return_package' => $isReturn, 'profit' => $profit));
                $one->update(array('price_s' => $priceS));
                $count++;
            }
            $this->database->commit(); // potvrzení


            //$this->userManager->setProgressBar(3, 5, $this->user->getId(), 'Položky faktur');
            $this->database->beginTransaction();
            //cl_invoice_items
            if (is_null($tmpPricelistId)) {
                $data = $this->InvoiceItemsManager->findAll()->where('cl_store_move_id IS NOT NULL');
            }else{
                $data = $this->InvoiceItemsManager->findAll()->where('cl_pricelist_id = ? AND cl_store_move_id IS NOT NULL', $tmpPricelistId);
            }
            $count = 0;
            $maxCount = count($data);
            foreach($data as $key => $one)
            {
                $this->userManager->setProgressBar($count, $maxCount, $this->user->getId(), '7/9 Položky faktur');
                //$priceS = $this->StoreMoveManager->findAll()->where('id = ?', $one->cl_store_move_id)->sum('price_s * s_out');
                $priceS = $one->cl_store_move->price_s;
                $priceS2 = $priceS * $one->quantity;
                if ($priceS2 > 0) {
                    $profit = (($one->price_e2 / $priceS2) - 1) * 100;
                }else{
                    $profit = 100;
                }

                if (!is_null($one->cl_pricelist->cl_pricelist_group_id))
                {
                    $isReturn = $one->cl_pricelist->cl_pricelist_group->is_return_package;
                }else{
                    $isReturn = 0;
                }
                $one->update(array('price_s' => $priceS, 'is_return_package' => $isReturn, 'profit' => $profit));
                $count++;
            }
            //cl_invoice_items_back
            if (is_null($tmpPricelistId)) {
                $data = $this->InvoiceItemsBackManager->findAll()->where('cl_store_move_id IS NOT NULL');
            }else{
                $data = $this->InvoiceItemsBackManager->findAll()->where('cl_pricelist_id = ? AND cl_store_move_id IS NOT NULL', $tmpPricelistId);
            }
            $count = 0;
            $maxCount = count($data);
            foreach($data as $key => $one)
            {
                //$priceS = $this->StoreMoveManager->findAll()->where('id = ?', $one->cl_store_move_id)->sum('price_s * s_out');
                $this->userManager->setProgressBar($count, $maxCount, $this->user->getId(), '8/9 Položky faktur 2');
                $priceS = $one->cl_store_move->price_s;
                $priceS2 = $priceS * $one->quantity;
                if ($priceS > 0) {
                    $profit = (1-($one->price_e2 / $priceS2) ) * 100;
                }else{
                    $profit = 100;
                }

                if (!is_null($one->cl_pricelist->cl_pricelist_group_id))
                {
                    $isReturn = $one->cl_pricelist->cl_pricelist_group->is_return_package;
                }else{
                    $isReturn = 0;
                }
                $one->update(array('price_s' => $priceS, 'is_return_package' => $isReturn, 'profit' => $profit));
                $count++;
            }

            $this->database->commit(); // potvrzení

            //$this->userManager->setProgressBar(4, 5, $this->user->getId(), 'Faktury');
            $this->database->beginTransaction();
            //cl_invoice
            if (is_null($tmpPricelistId)) {
                $data = $this->InvoiceManager->findAll()->where(':cl_invoice_items.cl_store_move_id IS NOT NULL OR :cl_invoice_items_back.cl_store_move_id IS NOT NULL ');
            }else{
                $data = $this->InvoiceManager->findAll()->
                            where(':cl_invoice_items.cl_pricelist_id = ? AND :cl_invoice_items.cl_store_move_id IS NOT NULL OR
                                            :cl_invoice_items_back.cl_store_move_id IS NOT NULL ', $tmpPricelistId);
            }
            $count = 0;
            $maxCount = count($data);
            foreach($data as $key => $one)
            {
                $this->userManager->setProgressBar($count, $maxCount, $this->user->getId(), '9/9 Faktury');
                $price_s = $one->related('cl_invoice_items')->sum('price_s * quantity');
                $price_s_back = $one->related('cl_invoice_items_back')->sum('price_s * quantity');
                $profit_abs = $one->price_e2 - ($price_s - $price_s_back);
                if (($price_s - $price_s_back) != 0) {
                    $profit = (($one->price_e2 / ($price_s - $price_s_back)) - 1) * 100;
                }else{
                    $profit = 100;
                }
                $one->update(array('price_s' => $price_s - $price_s_back, 'profit' => $profit, 'profit_abs' => $profit_abs));
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

    public function repairMinuses()
    {
        //1. we have to find cl_store_move records where cl_store_move.s_out < SUM(cl_store_out.s_out)
        try{
            $this->userManager->setProgressBar(1, 5, $this->user->getId());
            $this->database->beginTransaction();
            $data = $this->StoreMoveManager->findAll()->where('minus = 1');
            foreach($data as $key => $one){
                $one->update(['minus' => 0]);
            }
            $this->database->commit(); // potvrzení

            $this->database->beginTransaction();
            $this->userManager->setProgressBar(2, 5, $this->user->getId());
            $data = $this->StoreDocsManager->findAll()->where('minus = 1');
            foreach($data as $key => $one){
                $one->update(['minus' => 0]);
            }
            $this->database->commit(); // potvrzení

            $this->database->beginTransaction();
            $this->userManager->setProgressBar(3, 5, $this->user->getId());
            $data = $this->StoreMoveManager->findAll()->having('cl_store_move.s_out > SUM(:cl_store_out.s_out)')->group('cl_store_move.id');
            //bdump($data);
            foreach($data as $key => $one){
              //  bdump($one);
                $one->update(['cl_store_move.minus' => 1]);
                $one->cl_store_docs->update(['minus' => 1]);
            }
            $this->database->commit(); // potvrzení
            $this->userManager->resetProgressBar($this->user->getId());

        }catch (\Exception $e){
            $this->database->rollback();
            return ($e);
        }
    }

    public function updateZeroPriceSOut()
    {
        try{
            $this->database->beginTransaction();
            $tmpData = $this->StoreMoveManager->findAll()->where('s_out > 0 AND price_s = 0');
            $this->userManager->setProgressBar(0, count($tmpData), $this->user->getId());
            $i = 0;
            foreach($tmpData as $key => $one)
            {
                $this->userManager->setProgressBar($i++, count($tmpData), $this->user->getId());
                $tmpPrice = !is_null($one->cl_pricelist_id) ? $one->cl_pricelist['price_s'] : 0;
                $one->update(['price_s' => $tmpPrice]);
            }
            $this->database->commit(); // potvrzení
            $this->userManager->resetProgressBar($this->user->getId());
        }catch (\Exception $e){
            $this->database->rollback();
            return ($e);
        }
    }


	/**Return total sum of quantity for every cl_store (there can be more expirations and batches)
	 * @param $cl_pricelist_id
	 * @param $cl_storage_id
	 * @return float
	 */
    public function getQuantityStorage($cl_pricelist_id, $cl_storage_id){
    	$quantity = $this->findAll()->where('cl_pricelist_id = ? AND cl_storage_id = ?', $cl_pricelist_id, $cl_storage_id)->sum('quantity');
    	return (float)$quantity;
	}


	public function createInvoiceArrived($bscId, $vsIgnore = FALSE, $caller = NULL, $caller_id = NULL, $bscIdOut = NULL){
        //find cl_store_docs record
        $tmpStoreDoc = $this->StoreDocsManager->find($bscId);
        if (($tmpStoreDoc && $tmpStoreDoc->invoice_number != "") || $vsIgnore){
            $tmpNow = new \Nette\Utils\DateTime;
            //20.02.2019 - at first test if there is not allready created invoice
            //if ($caller == 'cl_delivery_note_in_id')
                bdump($tmpStoreDoc);
                if (!is_null($tmpStoreDoc['cl_invoice_arrived_id'])){
                    $invoice = $this->InvoiceArrivedManager->find($tmpStoreDoc['cl_invoice_arrived_id']);
                    bdump($invoice);
                }elseif ($vsIgnore) {
                    bdump($vsIgnore, 'vsIgnore = T');
                    if ($tmpStoreDoc->delivery_number != '')
                        $invoice = $this->InvoiceArrivedManager->findAll()->where('delivery_number = ? && cl_partners_book_id = ?', $tmpStoreDoc->delivery_number, $tmpStoreDoc->cl_partners_book_id)->limit(1)->fetch();
                    elseif ($tmpStoreDoc->invoice_number != '')
                        $invoice = $this->InvoiceArrivedManager->findAll()->where('rinv_number = ? && cl_partners_book_id = ?', $tmpStoreDoc->invoice_number, $tmpStoreDoc->cl_partners_book_id)->limit(1)->fetch();
                    else
                        $invoice = FALSE;

                }else {
                    bdump($vsIgnore, 'vsIgnore = F');
                    $invoice = $this->InvoiceArrivedManager->findAll()->where('rinv_number = ? && cl_partners_book_id = ?', $tmpStoreDoc->invoice_number, $tmpStoreDoc->cl_partners_book_id)->limit(1)->fetch();
                }
                bdump($invoice, 'invoice');

            if (!is_null($bscIdOut)){
                //$itemsBack = $this->DeliveryNoteInItemsBackManager->findAll()->where('cl_delivery_note_in_id = ?', $invoice['cl_delivery_note_in_id']);
                $itemsBack = $this->StoreMoveManager->findAll()
                                                    ->where('cl_store_docs_id = ?', $bscIdOut)
                                                    ->select('SUM(s_out * price_e) AS price_e2, SUM(price_e2_vat) AS price_e2_vat, vat')
                                                    //->select('SUM(price_e2) AS price_e2, SUM(price_e2_vat) AS price_e2_vat, vat')
                                                    ->group('vat');
            }else{
                $itemsBack = FALSE;
            }

            //!is_null($tmpStoreDoc->cl_invoice_arrived_id) &&
            //bdump($invoice);
            if ($invoice)
            {
                //invoice is already existing
                //$invoice = $this->InvoiceArrivedManager->find($tmpStoreDoc->cl_invoice_arrived_id);
                $arrInvoice = ['id'                      => $invoice->id,
                                'rinv_number'            => $tmpStoreDoc->invoice_number,
                                'delivery_number'        => $tmpStoreDoc->delivery_number,
                                'cl_currencies_id'       => $tmpStoreDoc->cl_currencies_id,
                                'currency_rate'          => $tmpStoreDoc->currency_rate,
                                'cl_delivery_note_in_id' => $tmpStoreDoc['cl_delivery_note_in_id']];
                //20.02.2019 - update cl_store_docs with cl_invoice_arrived_id
                $tmpStoreDoc->update(['cl_invoice_arrived_id' => $invoice->id]);
            } else {
                //invoice was not found by rinv_number -> we have to create it
                //at first main record cl_invoice

                $arrInvoice = ['cl_partners_book_id'     => $tmpStoreDoc->cl_partners_book_id,
                                'inv_date'               => $tmpNow->format('Y-m-d H:i:s'),
                                'vat_date'               => $tmpNow->format('Y-m-d H:i:s'),
                                'arv_date'               => $tmpNow->format('Y-m-d H:i:s'),
                                'rinv_number'            => $tmpStoreDoc->invoice_number,
                                'delivery_number'        => $tmpStoreDoc->delivery_number,
                                'cl_currencies_id'       => $tmpStoreDoc->cl_currencies_id,
                                'currency_rate'          => $tmpStoreDoc->currency_rate,
                                'cl_delivery_note_in_id' => $tmpStoreDoc['cl_delivery_note_in_id']
                        ];
                $numberSeries = ['use' => 'invoice_arrived', 'table_key' => 'cl_number_series_id', 'table_number' => 'inv_number'];
                $nSeries = $this->NumberSeriesManager->getNewNumber($numberSeries['use']);
                $arrInvoice[$numberSeries['table_key']]		= $nSeries['id'];
                $arrInvoice[$numberSeries['table_number']]	= $nSeries['number'];

                $tmpStatus = $numberSeries['use'];
                $nStatus = $this->StatusManager->findAll()->where('status_use = ? AND s_new = ?',$tmpStatus,1)->fetch();
                if ($nStatus)
                {
                    $arrInvoice['cl_status_id'] = $nStatus->id;
                }
                $tmpInvoiceTypes = $this->InvoiceTypesManager->findAll()->where('inv_type = ?', '4')->order('default_type DESC')->fetch();
                if ($tmpInvoiceTypes){
                    $arrInvoice['cl_invoice_types_id'] = $tmpInvoiceTypes->id;
                }else{
                    $arrInvoice['cl_invoice_types_id'] = NULL;
                }
                $arrInvoice['cl_payment_types_id']  = $this->PartnersManager->getPaymentType($tmpStoreDoc->cl_partners_book_id);
                $arrInvoice['due_date']		        = $this->PartnersManager->getDueDate($tmpStoreDoc->cl_partners_book_id, $tmpNow);
                $arrInvoice['cl_store_docs_id' ]    = $tmpStoreDoc->id;
                $arrInvoice['cl_company_branch_id'] = $this->user->getIdentity()->cl_company_branch_id;
                $invoice = $this->InvoiceArrivedManager->insert($arrInvoice);
                $arrInvoice['id'] = $invoice->id;
                //20.02.2019 - update cl_store_docs with cl_invoice_arrived_id
                $tmpStoreDoc->update(['cl_invoice_arrived_id' => $invoice->id]);
         //   }else {

            }

            //01.10.2019 - sum for every VAT rate
            $validRates = $this->RatesVatManager->findAllValid($tmpNow);
            $arrInvoiceVat = [];
            $arrInvoice['price_base0'] = 0;
            $arrInvoice['price_base1'] = 0;
            $arrInvoice['price_base2'] = 0;
            $arrInvoice['price_base3'] = 0;
            $arrInvoice['price_vat1'] = 0;
            $arrInvoice['price_vat2'] = 0;
            $arrInvoice['price_vat3'] = 0;
            $arrInvoice['price_e2'] = 0;
            $arrInvoice['price_e2_vat'] = 0;
            foreach ($validRates as $key => $one) {
                $arrInvoiceVat[$one['rates']] = array('base' => 0 ,
                    'vat' => 0,
                    'payed' => 0,
                    'vatpayed' => 0);
            }

            if (isset(array_keys($arrInvoiceVat)[0])) {
                $arrInvoice['vat1'] = array_keys($arrInvoiceVat)[0];
            }
            if (isset(array_keys($arrInvoiceVat)[1])) {
                $arrInvoice['vat2'] = array_keys($arrInvoiceVat)[1];
            }
            if (isset(array_keys($arrInvoiceVat)[2])) {
                $arrInvoice['vat3'] = array_keys($arrInvoiceVat)[2];
            }
            //29.03.2020 - calculate VAT for every income for current invoice
            $tmpStoreDocs = $this->StoreDocsManager->findAll()->where('cl_invoice_arrived_id = ?', $invoice->id);
            foreach($tmpStoreDocs as $keyS => $oneS) {
                $arrVatSum = $this->StoreMoveManager->getVatSum($oneS->id);
                //bdump('tedddd');
                //bdump($arrVatSum);
                foreach ($arrVatSum as $key => $one) {
                    if (!is_null($one['price_in'])) {
                        if ($one['vat'] == 0) {
                            $arrInvoice['price_base0'] += $one['price_in'];
                        } elseif ($one['vat'] == $arrInvoice['vat1']) {
                            $arrInvoice['price_base1'] += $one['price_in'];
                            $arrInvoice['price_vat1'] += $one['price_in'] * ($arrInvoice['vat1'] / 100);
                        } elseif ($one['vat'] == $arrInvoice['vat2']) {
                            $arrInvoice['price_base2'] += $one['price_in'];
                            $arrInvoice['price_vat2'] += $one['price_in'] * ($arrInvoice['vat2'] / 100);
                        } elseif ($one['vat'] == $arrInvoice['vat3']) {
                            $arrInvoice['price_base3'] += $one['price_in'];
                            $arrInvoice['price_vat3'] += $one['price_in'] * ($arrInvoice['vat3'] / 100);
                        }
                    }
                }
            }

            if ($itemsBack){
                foreach ($itemsBack as $key => $one) {
                        if ($one['vat'] == 0) {
                            $arrInvoice['price_base0'] -= $one['price_e2'];
                        } elseif ($one['vat'] == $arrInvoice['vat1']) {
                            $arrInvoice['price_base1'] -= $one['price_e2'];
                            $arrInvoice['price_vat1'] -= $one['price_e2'] * ($arrInvoice['vat1'] / 100);
                        } elseif ($one['vat'] == $arrInvoice['vat2']) {
                            $arrInvoice['price_base2'] -= $one['price_e2'];
                            $arrInvoice['price_vat2'] -= $one['price_e2'] * ($arrInvoice['vat2'] / 100);
                        } elseif ($one['vat'] == $arrInvoice['vat3']) {
                            $arrInvoice['price_base3'] -= $one['price_e2'];
                            $arrInvoice['price_vat3'] -= $one['price_e2'] * ($arrInvoice['vat3'] / 100);
                        }
                }
            }


            $arrInvoice['price_e2'] = $arrInvoice['price_base3'] + $arrInvoice['price_base2'] + $arrInvoice['price_base1'] + $arrInvoice['price_base0'];
            $arrInvoice['price_e2_vat'] = $arrInvoice['price_e2'] + $arrInvoice['price_vat3'] + $arrInvoice['price_vat2'] + $arrInvoice['price_vat1'];
            //bdump($arrInvoice);
            $updatedInvoice = $this->InvoiceArrivedManager->update($arrInvoice);


            //create pairedocs record
            $this->PairedDocsManager->insertOrUpdate(array('cl_invoice_arrived_id' => $invoice->id, 'cl_store_docs_id' => $tmpStoreDoc->id));
            if ($caller == 'cl_delivery_note_in_id'){
                $this->PairedDocsManager->insertOrUpdate(array('cl_invoice_arrived_id' => $invoice->id, 'cl_delivery_note_in_id' => $caller_id));
            }

            //total sums of invoice
            $this->InvoiceArrivedManager->updateInvoiceSum($invoice->id);
            //redirect to invoice
            //$this->redirect(':Application:InvoiceArrived:default', array('id' => $invoice->id));
            return $invoice->id;
        }else{
            return FALSE;
        }
    }

    /**Add items for
     * @param $dataMove
     * @return array
     */
    public function addToOrder($dataMove)
    {
        $arrPartners = array();
        foreach ($dataMove as $key => $one) {
            $arrPartners[$one['cl_partners_book_id']]   = $one['cl_partners_book_id'];
            $tmpStorage_id[$one['cl_storage_id']]       = $one['cl_storage_id'];
        }
        //bdump($arrPartners);
        //bdump($tmpStorage_id, 'storage_id');
        //die;
        //bdump($dataMove);
        foreach ($arrPartners as $key => $one){
            $tmpData = $this->findAll()->where('cl_pricelist.cl_partners_book_id = ? AND cl_store.cl_storage_id IN (?) AND cl_store.quantity_to_order > 0',
                                                                $one, $tmpStorage_id)
                                       ->where('cl_store.quantity_to_order / IF(cl_pricelist.in_package>0, cl_pricelist.in_package, 1) >= 1 ');
            $oneRecord = array();
            //echo($tmpData->getSql());
            //bdump($tmpData, 'tmpData');
            foreach($tmpData as $key2 => $one2){
                $oneRecord['quantity']              = $one2->quantity_to_order;
                $oneRecord['cl_pricelist_id']       = $one2->cl_pricelist_id;
                $oneRecord['identification']        = $one2->cl_pricelist->identification;
                $oneRecord['item_label']            = $one2->cl_pricelist->item_label;
                $oneRecord['vat']                   = $one2->cl_pricelist->vat;
                $oneRecord['price_s']               = $one2->cl_pricelist->price_s;
                $oneRecord['units']                 = $one2->cl_pricelist->unit;
                $oneRecord['cl_partners_book_id']   = $one2->cl_pricelist->cl_partners_book_id;
                $oneRecord['cl_partners_book_id2']  = $one2->cl_pricelist->cl_partners_book_id2;
                $oneRecord['cl_partners_book_id3']  = $one2->cl_pricelist->cl_partners_book_id3;
                $oneRecord['cl_partners_book_id4']  = $one2->cl_pricelist->cl_partners_book_id4;
                $oneRecord['cl_storage_id']         = $one2->cl_storage_id;
                $oneRecord['min_order']             = $one2->cl_pricelist->cl_partners_book->min_order;
                $oneRecord['cl_store_id']           = $one2->id;
                $oneRecord['in_package']            = $one2->cl_pricelist->in_package;
                $oneRecord['note2']                 = 'z čekajících na objednání';
                $dataMove[] = $oneRecord;
            }
            //bdump($oneRecord, 'oneRecord');
        }
        //bdump($dataMove);
        //die;
        //dump($dataMove);
        usort($dataMove, function($a, $b) {
            return $a['cl_partners_book_id'] <=> $b['cl_partners_book_id'];
        });

        //dump($dataMove);
        //die;
        return $dataMove;
    }


    public function UpdateZeroPriceS($pricelistId = NULL){
        session_write_close();
        if (is_null($pricelistId)){
            $data = $this->StoreMoveManager->findAll()->where('price_in = 0 AND s_in > 0');
        }else{
            $data = $this->StoreMoveManager->findAll()->where('cl_pricelist_id = ? AND price_in = 0 AND s_in > 0', $pricelistId);
        }

        $count = 0;
        $maxCount = count($data);
        //bdump($maxCount);
        foreach($data as $key => $one){
            $this->userManager->setProgressBar($count++, $maxCount, $this->user->getId(), 'Aktualizace nákupních cen');
            $one->update(['price_in' => $one->cl_pricelist['price_s'],
                                'price_s' => $one->cl_pricelist['price_s'],
                                'price_in_vat' => ($one->cl_pricelist['price_s'] * (1 + ($one['vat'] / 100))),
                                'price_e2' => $one->cl_pricelist['price_s'] * $one['s_in'],
                                'price_e2_vat' => $one->cl_pricelist['price_s'] * $one['s_in'] * (1 + ($one['vat'] / 100))
            ]);
        }
        $this->userManager->resetProgressBar($this->user->getId());
        return (['success' => $count]);
    }

    /**return quantity on storemove records.
     * @param $params [cl_pricelist_id,cl_storage_id,cl_store_id]
     * @return int|mixed
     */
    public function calcQuantity($params){
        $cl_pricelist_id = NULL;
        $retVal = FALSE;
        if (isset($params['id']))
            $cl_pricelist_id = $params['id'];
        elseif (isset($params['cl_pricelist_id']))
            $cl_pricelist_id = $params['cl_pricelist_id'];

        if (!is_null($cl_pricelist_id)) {
            $retVal = $this->StoreMoveManager->findAll()->where('cl_pricelist_id = ?', $cl_pricelist_id);
            if (isset($params['cl_storage_id']))
                $retVal = $retVal->where('cl_storage_id = ?', $params['cl_storage_id']);
            if (isset($params['cl_store_id']))
                $retVal = $retVal->where('cl_store_id = ?', $params['cl_store_id']);

            $retVal = $retVal->sum('s_in - s_out');
        }

        return (!$retVal) ? 0 : $retVal;
    }


}


