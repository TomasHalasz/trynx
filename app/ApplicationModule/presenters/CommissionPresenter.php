<?php

namespace App\ApplicationModule\Presenters;

use App\Controls;
use Nette\Application\UI\Form,
    Nette\Image;
use Exception;
use Nette\Utils\DateTime;
use Tracy\Debugger;
use Nette\Mail\Message,
    Nette\Utils\Strings;
use Nette\Mail\SendmailMailer;

class CommissionPresenter extends \App\Presenters\BaseListPresenter
{


    const
        DEFAULT_STATE = 'Czech Republic';


    public $newId = NULL, $headerModalShow = FALSE, $descriptionModalShow = FALSE, $pairedDocsShow = FALSE, $createDocShow = FALSE;
    public $importType = 0;

    /** @persistent */
    public $page_b;

    /** @persistent */
    public $filter;

    /** @persistent */
    public $filterColumn;

    /** @persistent */
    public $filterValue;

    /** @persistent */
    public $sortKey;

    /** @persistent */
    public $sortOrder;


    public $filterInvoiceUsed = array();
    public $filterStoreCreate = array();
    public $filterStoreUpdate = array();

    /**
     * @inject
     * @var \App\Model\CommissionManager
     */
    public $DataManager;

    /**
     * @inject
     * @var \App\Model\DocumentsManager
     */
    public $DocumentsManager;

    /**
     * @inject
     * @var \App\Model\CommissionItemsManager
     */
    public $CommissionItemsManager;

    /**
     * @inject
     * @var \App\Model\CommissionItemsSelManager
     */
    public $CommissionItemsSelManager;

    /**
     * @inject
     * @var \App\Model\CommissionItemsProductionManager
     */
    public $CommissionItemsProductionManager;

    /**
     * @inject
     * @var \App\Model\CommissionWorkManager
     */
    public $CommissionWorkManager;

    /**
     * @inject
     * @var \App\Model\CommissionTaskManager
     */
    public $CommissionTaskManager;

    /**
     * @inject
     * @var \App\Model\CurrenciesManager
     */
    public $CurrenciesManager;

    /**
     * @inject
     * @var \App\Model\PartnersManager
     */
    public $PartnersManager;

    /**
     * @inject
     * @var \App\Model\PartnersBookWorkersManager
     */
    public $PartnersBookWorkersManager;

    /**
     * @inject
     * @var \App\Model\PartnersBranchManager
     */
    public $PartnersBranchManager;

    /**
     * @inject
     * @var \App\Model\PriceListTaskManager
     */
    public $PricelistTaskManager;

    /**
     * @inject
     * @var \App\Model\RatesVatManager
     */
    public $RatesVatManager;

    /**
     * @inject
     * @var \App\Model\PriceListManager
     */
    public $PriceListManager;

    /**
     * @inject
     * @var \App\Model\PriceListPartnerManager
     */
    public $PriceListPartnerManager;


    /**
     * @inject
     * @var \App\Model\InvoiceManager
     */
    public $InvoiceManager;


    /**
     * @inject
     * @var \App\Model\InvoiceItemsManager
     */
    public $InvoiceItemsManager;

    /**
     * @inject
     * @var \App\Model\InvoiceTypesManager
     */
    public $InvoiceTypesManager;

    /**
     * @inject
     * @var \App\Model\InvoiceArrivedCommissionManager
     */
    public $InvoiceArrivedCommissionManager;

    /**
     * @inject
     * @var \App\Model\PaymentTypesManager
     */
    public $PaymentTypesManager;

    /**
     * @inject
     * @var \App\Model\TransportTypesManager
     */
    public $TransportTypesManager;

    /**
     * @inject
     * @var \App\Model\InvoicePaymentsManager
     */
    public $InvoicePaymentsManager;


    /**
     * @inject
     * @var \App\Model\EmailingManager
     */
    public $EmailingManager;


    /**
     * @inject
     * @var \App\Model\FilesManager
     */
    public $FilesManager;

    /**
     * @inject
     * @var \App\Model\PricesManager
     */
    public $PricesManager;

    /**
     * @inject
     * @var \App\Model\CenterManager
     */
    public $CenterManager;

    /**
     * @inject
     * @var \App\Model\PairedDocsManager
     */
    public $PairedDocsManager;

    /**
     * @inject
     * @var \App\Model\HeadersFootersManager
     */
    public $HeadersFootersManager;

    /**
     * @inject
     * @var \App\Model\TextsManager
     */
    public $TextsManager;

    /**
     * @inject
     * @var \App\Model\PriceListMacroManager
     */
    public $PriceListMacroManager;

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
     * @var \App\Model\StoreMoveManager
     */
    public $StoreMoveManager;


    /**
     * @inject
     * @var \App\Model\PriceListBondsManager
     */
    public $PriceListBondsManager;

    /**
     * @inject
     * @var \App\Model\OrderManager
     */
    public $OrderManager;

    /**
     * @inject
     * @var \App\Model\DeliveryNoteManager
     */
    public $DeliveryNoteManager;

    /**
     * @inject
     * @var \App\Model\InvoiceArrivedManager
     */
    public $invoiceArrivedManager;

    public function DataProcessMain($defValues, $data)
    {

        //20.12.2018 - headers and footers
        //19.10.2019 - solved in BaseListPresenter->getNumberSeries
        //if ($hfData = $this->HeadersFootersManager->findBy(array('cl_number_series_id' => $defValues['cl_number_series_id']))->fetch()){
        //  $defValues['header_txt'] = $hfData['header_txt'];
        //$defValues['footer_txt'] = $hfData['footer_txt'];
        //}

        return $defValues;
    }

    public function renderDefault($page_b = 1, $idParent, $filter, $sortKey, $sortOrder, $filterColumn, $filterValue, $modal, $cxs)
    {
        parent::renderDefault($page_b, $idParent, $filter, $sortKey, $sortOrder, $filterColumn, $filterValue, $modal, $cxs);
        $this->template->importType = $this->ArraysManager->getImportTypeName($this->importType);
    }

    public function renderEdit($id, $copy = FALSE, $modal = FALSE)
    {
        parent::renderEdit($id, $copy, $modal);

    }

    /*public function renderEdit($id, $copy, $modal)
		{
			parent::renderEdit($id, $copy, $modal);

			if ($defData = $this->DataManager->findOneBy(array('id' => $id))) {
				$this['headerEdit']->setValues($defData);
				$this['descriptionEdit']->setValues($defData);
			}

		}*/

    public function FormValidate(Form $form)
    {
        $data = $form->values;
        $data = $this->updatePartnerId($data);
        //$this->redrawControl('content');
        if ($data['cl_partners_book_id'] == NULL || $data['cl_partners_book_id'] == 0) {
            $form->addError($this->translator->translate('Partner_musí_být_vybrán'));
        }

        if ($data['vat'] === NULL && $this->settings->platce_dph) {
            $form->addError($this->translator->translate('Sazba_DPH_musí_být_vybrána'));
        }

        $this->redrawControl('content');

    }

    public function stepBack()
    {
        //06.07.2018 - unset value of selectbox from session. Selectbox must be filled with default values
        $mySection = $this->getSession('selectbox');
        unset($mySection['cl_partners_book_id_values']);

        $this->flashMessage($this->translator->translate('Změny_nebyly_uloženy'), 'danger');
        $this->redirect('default');
    }

    public function SubmitEditSubmitted(Form $form)
    {
        $data = $form->values;
        //dump($data);

        //06.07.2018 - unset value of selectbox from session. Selectbox must be filled with default values
        $mySection = $this->getSession('selectbox');
        unset($mySection['cl_partners_book_id_values']);


        if ($form['send']->isSubmittedBy()) {

            $data = $this->RemoveFormat($data);


            $myReadOnly = isset($this->DataManager->find($data['id'])->cl_status_id) && $this->DataManager->find($data['id'])->cl_status->s_fin == 1;
            if (!($myReadOnly))
                $myReadOnly = false;

            //{//if record is not marked as finished, we can save edited data
            if (!empty($data->id)) {
                $this->DataManager->update($data, TRUE);
                $this->UpdatePairedDocs($data);
                $this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
            }


            //create invoice
            //$this->createInvoice();

            $this->redrawControl('content');

        } else {
            $this->flashMessage($this->translator->translate('Změny_nebyly_uloženy'), 'warning');
            //$this->redrawControl('flash');
            //$this->redrawControl('formedit');
            //$this->redrawControl('timestamp');
            //$this->redrawControl('items');
            //$this->redirect('default');

            //$this->redirect('default');
            $this->redrawControl('content');
        }

    }

    public function UpdatePairedDocs($data){

        $tmpPaired = $this->PairedDocsManager->findAll()->where('cl_delivery_note_id IS NOT NULL AND cl_commission_id = ?', $data['id'])->fetch();
        if ($tmpPaired && !is_null($tmpPaired['cl_delivery_note_id'])) {
            $this->DeliveryNoteManager->update(['id' => $tmpPaired['cl_delivery_note_id'],
                                                'cl_partners_book_id' => $data['cl_partners_book_id'],
                                                'cl_center_id' => $data['cl_center_id'],
                                                'cl_partners_branch_id' => $data['cl_partners_branch_id'],
                                                'cl_partners_book_workers_id' => $data['cl_partners_book_workers_id'],
                                                'cl_users_id' => $data['cl_users_id']
            ]);

                $tmpPaired2 = $this->PairedDocsManager->findAll()->where('cl_invoice_id IS NOT NULL AND cl_delivery_note_id = ?', $tmpPaired['cl_delivery_note_id'])->fetch();
                if ($tmpPaired && !is_null($tmpPaired2['cl_invoice_id'])){
                    $this->InvoiceManager->update(['id' => $tmpPaired2['cl_invoice_id'],
                                                    'cl_partners_book_id' => $data['cl_partners_book_id'],
                                                    'cl_center_id' => $data['cl_center_id'],
                                                    'cl_partners_branch_id' => $data['cl_partners_branch_id'],
                                                    'cl_partners_book_workers_id' => $data['cl_partners_book_workers_id'],
                                                    'cl_users_id' => $data['cl_users_id']
                    ]);
                }

                $tmpPaired3 = $this->PairedDocsManager->findAll()->where('cl_delivery_note_id = ? AND cl_store_docs_id IS NOT NULL',  $tmpPaired['cl_delivery_note_id']);
                foreach ($tmpPaired3 as $key => $one){
                    $this->StoreDocsManager->update(['id' => $one['cl_store_docs_id'],
                                                    'cl_partners_book_id' => $data['cl_partners_book_id'],
                                                    'cl_center_id' => $data['cl_center_id'],
                                                    'cl_partners_book_workers_id' => $data['cl_partners_book_workers_id'],
                                                    'cl_users_id' => $data['cl_users_id']
                    ]);
                }


        }

//        if ($tmpPaired && !is_null($tmpPaired['cl_store_docs_id'])) {
//        }
    }


    /*public function handleGetCurrencyRate($idCurrency)
		{
			if ($rate = $this->CurrenciesManager->findOneBy(array('id' => $idCurrency))->fix_rate)
				echo($rate);
			else {
				echo(0);
			}
			//in future there can be another work with rates

			$this->terminate();

		}*/

    public function handleMakeRecalc($idCurrency, $rate, $oldrate, $recalc, $vat)
    {
        //in future there can be another work with rates
        //dump($this->editId);
        if ($rate > 0) {
            if ($recalc == 1) {
                $recalcItems = $this->CommissionItemsManager->findBy(array('cl_commission_id', $this->id));
                foreach ($recalcItems as $one) {
                    //'price_e' => array('Cena bez DPH',"number",'size' => 10),
                    //'discount' => array('Sleva %',"number",'size' => 10),'price_e2' => array('Celkem bez DPH',"number"),
                    //'vat' => array('Sazba DPH',"number",'values' => array($this->RatesVatManager->findAllValid()->fetchPairs('rates','rates')),'size' => 10),
                    //'price_e2_vat' => array('Celkem s DPH',"number")),
                    $data = array();
                    $data['price_s'] = $one['price_s'] * $oldrate / $rate;
                    $data['price_e'] = $one['price_e'] * $oldrate / $rate;
                    $data['price_e2'] = $one['price_e2'] * $oldrate / $rate;
                    $data['price_e2_vat'] = $one['price_e2_vat'] * $oldrate / $rate;
                    $one->update($data);
                }
                $recalcWorks = $this->CommissionWorkManager->findBy(array('cl_commission_id', $this->id));
                foreach ($recalcWorks as $one) {
                    $data = array();
                    $data['work_rate'] = $one['work_rate'] * $oldrate / $rate;
                    $one->update($data);
                }
            }

            //we must save parent data
            $parentData = array();
            $parentData['id'] = $this->id;
            if ($vat != 0) {
                $parentData['vat'] = $vat;
            } else {
                $parentData['vat'] = NULL;
            }

            if ($rate <> $oldrate) {
                $parentData['currency_rate'] = $rate;
            }

            $parentData['cl_currencies_id'] = $idCurrency;
            $this->DataManager->update($parentData);


            $this->UpdateSum();
            /*$price_s = $this->CommissionItemsManager->findBy(array('cl_commission_id' => $this->id))->sum('price_s*quantity');
			$price_e2 = $this->CommissionItemsManager->findBy(array('cl_commission_id' => $this->id))->sum('price_e2');
			$price_e2_vat = $this->CommissionItemsManager->findBy(array('cl_commission_id' => $this->id))->sum('price_e2_vat');
			$parentData = array();
			$parentData['id'] = $this->id;
			$parentData['price_s'] = $price_s;
			$parentData['price_e2'] = $price_e2;
			$parentData['price_e2_vat'] = $price_e2_vat;
			$parentData['cl_currencies_id'] = $idCurrency;
			$parentData['currency_rate'] = $rate;
			$this->DataManager->update($parentData);
			 */
        }
        $this->redrawControl('');

    }

    public function beforeAddLine($data)
    {
        if (isset($data['work_date_s'])) {
            $data['profit'] = 5;
        } else {
            $data['profit'] = 2;
        }

        $data['price_e_type'] = $this->settings->price_e_type;
        return ($data);
    }

    public function ListGridInsert($sourceData, $dataManager)
    {
        if ($dataManager->tableName == 'cl_commission_items') {
            $row = $this->listGridInsertCore($sourceData, $dataManager);

        } elseif ($dataManager->tableName == 'cl_commission_items_sel') {
            $row = $this->listGridInsertCore($sourceData, $dataManager);
            //21.01.2018 - insert of macro items to cl_commission_items if there are any
            $macroData = $this->PriceListMacroManager->findAll()->where('cl_pricelist_macro_id = ?', $sourceData['id']);
            foreach ($macroData as $one) {
                $newQuantity = $this->PriceListMacroManager->getQuantity($one->id, 1);
                $this->listGridInsertCore($one->cl_pricelist, $this->CommissionItemsManager, $row->id, $one->id, $newQuantity);
            }
        }
        return ($row);
    }

    private function listGridInsertCore($sourceData, $dataManager, $macroParentId = NULL, $macroId = NULL, $newQuantity = 0)
    {
        $arrPrice = array();
        //if (isset($sourceData['cl_pricelist_id'])) {
        if (array_key_exists('cl_pricelist_id', $sourceData->toArray())) {
            $arrPrice['id'] = $sourceData['cl_pricelist_id'];
            $sourcePriceData = $this->PriceListManager->find($sourceData['cl_pricelist_id']);
        } else {
            $arrPrice['id'] = $sourceData['id'];
            $sourcePriceData = $this->PriceListManager->find($sourceData['id']);
        }
        $arrPrice['cl_currencies_id'] = $sourcePriceData['cl_currencies_id'];
        ///04.09.2017 - find price if there are defined in prices_groups
        $tmpData = $this->DataManager->find($this->id);
        if (isset($tmpData['cl_partners_book_id'])
            && $tmpPrice = $this->PricesManager->getPrice($tmpData->cl_partners_book,
                $arrPrice['id'],
                $tmpData->cl_currencies_id,
                $this->settings['cl_storage_id_commission'])) {
            $arrPrice['price'] = $tmpPrice['price'];
            $arrPrice['price_vat'] = $tmpPrice['price_vat'];
            $arrPrice['discount'] = $tmpPrice['discount'];
            $arrPrice['price_e2'] = $tmpPrice['price_e2'];
            $arrPrice['price_e2_vat'] = $tmpPrice['price_e2_vat'];
            $arrPrice['cl_currencies_id'] = $tmpPrice['cl_currencies_id'];
        } else {
            $arrPrice['price'] = $sourceData->price;
            $arrPrice['price_vat'] = $sourceData->price_vat;
            $arrPrice['discount'] = 0;
            $arrPrice['price_e2'] = $sourceData->price;
            $arrPrice['price_e2_vat'] = $sourceData->price_vat;
           // $arrPrice['cl_currencies_id'] = $sourceData->cl_currencies_id;
        }
        $arrPrice['vat'] = $sourceData->vat;


        $arrData = [];
        $arrData[$this->DataManager->tableName . '_id'] = $this->id;
        //$arrData['cl_pricelist_id'] = $sourceData->id;
        $arrData['cl_pricelist_id'] = $sourcePriceData->id;
        if ($dataManager->tableName == 'cl_commission_items') {
            $arrData['item_order'] = $this->CommissionItemsManager->findAll()->where($this->DataManager->tableName . '_id = ?', $arrData[$this->DataManager->tableName . '_id'])->max('item_order') + 1;
        } elseif ($dataManager->tableName == 'cl_commission_items_sel') {
            $arrData['item_order'] = $this->CommissionItemsSelManager->findAll()->where($this->DataManager->tableName . '_id = ?', $arrData[$this->DataManager->tableName . '_id'])->max('item_order') + 1;
        }
        //$arrData['item_label'] = $sourceData->item_label;
        $arrData['item_label'] = $sourcePriceData->item_label;
        $arrData['quantity'] = 1;
        //$arrData['units'] = $sourceData->unit;
        $arrData['units'] = $sourcePriceData->unit;
        //$arrData['price_s'] = $sourceData->price_s;
        //01.06.2017 FiFo x VAP
        //for now without solution, because we don't know from which store will be item used
        $arrData['price_s'] = $sourcePriceData->price_s;
        //$arrData['price_e'] = $sourceData->price;
        //$arrData['price_e2'] = $sourceData->price;
        //$arrData['price_e2_vat'] = $sourceData->price_vat;
        //$arrData['vat'] = $sourceData->vat;

        $arrData['price_e_type'] = $this->settings->price_e_type;
        //25.07.2018 - requested profit
        $arrData['profit'] = $tmpData['profit_items'];
        if ($arrData['profit'] > 0) {
            $arrData['price_e'] = $arrData['price_s'] * (1 + ($arrData['profit'] / 100));
            $arrData['price_e2'] = $arrData['price_e'];
            $arrData['price_e2_vat'] = $arrData['price_e'] * (1 + ($arrPrice['vat'] / 100));
            if ($arrData['price_e_type'] == 1) {
                $arrData['price_e'] = $arrData['price_e'] * (1 + ($arrPrice['vat'] / 100));
            }

        } else {

            if ($arrData['price_e_type'] == 1) {
                $arrData['price_e'] = $arrPrice['price_vat'];
            } else {
                $arrData['price_e'] = $arrPrice['price'];
            }
            $arrData['discount'] = $arrPrice['discount'];
            $arrData['price_e2'] = $arrPrice['price_e2'];
            $arrData['price_e2_vat'] = $arrPrice['price_e2_vat'];
        }
        $arrData['vat'] = $arrPrice['vat'];

        //prepocet kurzem
        //potrebujeme kurz ceníkove polozky a kurz zakazky
        //if ($sourceData->cl_currencies_id != NULL) {
        if ($arrPrice['cl_currencies_id'] != NULL && ($tmpCurrency = $this->CurrenciesManager->find($arrPrice['cl_currencies_id']))) {
            //$ratePriceList = $sourceData->cl_currencies->fix_rate;
            $ratePriceList = $tmpCurrency->fix_rate;
        } else {
            $ratePriceList = 1;
        }
        if ($tmpCommission = $this->DataManager->find($this->id)) {
            $rateCommission = $tmpCommission->currency_rate;
        } else {
            $rateCommission = 1;
        }

        if ( $arrPrice['cl_currencies_id'] != $tmpCommission['cl_currencies_id'] ) {
            $arrData['price_s'] = $arrData['price_s'] * $ratePriceList / $rateCommission;
            $arrData['price_e'] = $arrData['price_e'] * $ratePriceList / $rateCommission;
            $arrData['price_e2'] = $arrData['price_e2'] * $ratePriceList / $rateCommission;
            $arrData['price_e2_vat'] = $arrData['price_e2_vat'] * $ratePriceList / $rateCommission;
        }

        if (!is_null($macroParentId)) {
            $arrData['cl_commission_items_sel_id'] = $macroParentId;
            $arrData['cl_pricelist_macro_id'] = $macroId;
            $arrData['quantity'] = $newQuantity;
        }

        $row = $dataManager->insert($arrData);
        $this->updateSum();

        //21.01.2018 - solution of macro cards
        //cl_pricelist item is inserted into cl_commission_items_sel  and child records from cl_pricelist_macro are inserted into cl_commission_items


        return ($row);
    }

    public function handleRedrawPriceList2($cl_partners_book_id)
    {
        //dump($cl_partners_book_id);
        $arrUpdate = array();
        $arrUpdate['id'] = $this->id;
        $arrUpdate['cl_partners_book_id'] = $cl_partners_book_id;

        //dump($arrUpdate);
        //die;
        $this->DataManager->update($arrUpdate);

        $this['listgrid']->redrawControl('pricelist2');
    }

    public function emailSetStatus()
    {
        $this->setStatus($this->id, array('status_use' => 'commission',
            's_new' => 0,
            's_eml' => 1));
    }

    public function stepHeaderBack()
    {
        $this->headerModalShow = FALSE;
        $this->activeTab = 5;
        $this->redrawControl('headerModalControl');
    }

    public function SubmitEditHeaderSubmitted(Form $form)
    {
        $data = $form->values;
        //later there must be another condition for user rights, admin can edit everytime
        if ($form['send']->isSubmittedBy()) {
            $this->DataManager->update($data, TRUE);
        }
        $this->headerModalShow = FALSE;
        $this->activeTab = 5;
        $this->redrawControl('items');
        $this->redrawControl('header_txt');
        $this->redrawControl('headerModalControl');
    }

    public function handleCmHeaderShow($value)
    {
        $arrData = array();
        $arrData['id'] = $this->id;
        //Debugger::fireLog($value);
        if ($value == 'true')
            $arrData['header_show'] = 1;
        else
            $arrData['header_show'] = 0;

        $this->DataManager->update($arrData);

        $this->terminate();
    }

    public function handleHeaderShow()
    {
        $this->headerModalShow = TRUE;
        $this->redrawControl('headerModalControl');
    }

    public function stepDescriptionBack()
    {
        $this->descriptionModalShow = FALSE;
        $this->activeTab = 4;
        $this->redrawControl('descriptionModalControl');
    }

    public function SubmitEditDescriptionSubmitted(Form $form)
    {
        $data = $form->values;
        //later there must be another condition for user rights, admin can edit everytime
        if ($form['send']->isSubmittedBy()) {
            $this->DataManager->update($data, TRUE);
        }
        $this->descriptionModalShow = FALSE;
        $this->activeTab = 4;
        $this->redrawControl('items');
        $this->redrawControl('description_txt');
        $this->redrawControl('descriptionModalControl');
    }

    public function handleCmDescriptionShow($value)
    {
        $arrData = array();
        $arrData['id'] = $this->id;
        //Debugger::fireLog($value);
        if ($value == 'true') {
            $arrData['description_show'] = 1;
        } else {
            $arrData['description_show'] = 0;
        }

        $this->DataManager->update($arrData);

        $this->terminate();
    }

    public function handleDescriptionShow()
    {
        $this->descriptionModalShow = TRUE;
        $this->redrawControl('descriptionModalControl');
    }

    public function beforeDelete($lineId, $name = "")
    {
        $result = TRUE;
        //07.05.2017 - if line is from helpdesk, we must delete connection
        if ($name == 'listgridWork') {
            if ($tmpLine = $this->CommissionWorkManager->find($lineId)) {
                if (!is_null($tmpLine->cl_partners_event_id)) {
                    $this->PartnersEventManager->find($tmpLine->cl_partners_event_id)->update(array('cl_commission_id' => NULL));
                    $tmpLine->update(array('cl_partners_event_id' => NULL));
                }
            }
        } elseif ($name == 'listgridTask') {
            $tmpLine = $this->CommissionTaskManager->find($lineId);
            if (!is_null($tmpLine['cl_commission_task_id'])) {
                $sub_count = $this->CommissionTaskManager->findAll()->where('cl_commission_task_id = ?', $tmpLine['cl_commission_task_id'])->count('id');
                $tmpLine->cl_commission_task->update(['sub_count' => $sub_count]);
            }

        } elseif ($name == 'listgridSel') {
            if ($tmpLine = $this->CommissionItemsSelManager->find($lineId)) {
                if (!is_null($tmpLine['cl_store_move_id'])) {
                    $this->flashMessage($this->translator->translate('Řádek_není_možné_smazat_existuje_k_němu_výdejka_Nejprve_položku_odeberte_z_výdejky'), 'danger');
                    $result = FALSE;
                }
            }
        }
        $this->redrawControl('content');

        return $result;
    }

    public function beforeDeleteBaseList($id)
    {
        foreach ($this->DataManager->find($id)->related('cl_commission_work') as $one) {
            //07.05.2017 - if line is from helpdesk, we must delete connection
            if (!is_null($one->cl_partners_event_id)) {
                $this->PartnersEventManager->find($one->cl_partners_event_id)->update(array('cl_commission_id' => NULL));
                $one->update(array('cl_partners_event_id' => NULL));
            }

        }
        return TRUE;
    }

    public function handleShowPairedDocs()
    {
        //bdump('ted');
        $this->pairedDocsShow = TRUE;
        /*$this->showModal('pairedDocsModal');
        $this->redrawControl('pairedDocs');
        $this->redrawControl('contents');*/

        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('pairedDocs2');
        $this->showModal('pairedDocsModal');

    }

    public function handleGetWorkerTax($cl_users_id)
    {
        $tax = $this->UserManager->getWorkerTax($cl_users_id);
        $this->payload->tax = $tax;
        $this->sendPayload();
        //$this->redrawControl();
        //$this->sendJson(array('tax' => $tax));
        //echo($tax);

    }

    public function afterDataSaveListGrid($dataId, $name = NULL)
    {

        //bdump($dataId,$name);
        if ($name == 'listgridTask') {
            $this->listgridTaskSave($dataId);
            // } elseif ($name == 'listgrid'){
            /*26.03.2021 insert task from tasklist in pricelist*/
            //if ($tmpData = $this->CommissionItemsManager->findAll()->where('id = ?', $dataId)->fetch()){
            //    $this->CommissionTaskManager->pricelistTaskInsert( $tmpData['cl_pricelist_id'], $tmpData['cl_commission_id']);
            //    $this->redrawControl('content');
            //}

        } elseif ($name == 'listgridSel') {
            $this->listgridSelSave($dataId);
            //14.03.2019 - insert cl_pricelist_bond into cl_invoice_items
            $tmpComissionItem = $this->CommissionItemsSelManager->find($dataId);
            //bdump($tmpInvoiceItem->cl_pricelist_id, 'cl_pricelist_id');
            if (!is_null($tmpComissionItem->cl_pricelist_id)) {
                //find if there are bonds in cl_pricelist_bonds
                //$tmpBonds = $this->PriceListBondsManager->findBy(array('cl_pricelist_bonds_id' => $tmpComissionItem->cl_pricelist_id));
                $tmpBonds = $this->PriceListBondsManager->findAll()->where('cl_pricelist_bonds_id = ? AND limit_for_bond <= ?', $tmpComissionItem->cl_pricelist_id, $tmpComissionItem->quantity);
                foreach ($tmpBonds as $key => $oneBond) {
                    //found in cl_invoice_items if there already is bonded item
                    $tmpCommissionItemBond = $this->CommissionItemsSelManager->findBy(array('cl_parent_bond_id' => $tmpComissionItem->id,
                        'cl_pricelist_id' => $oneBond->cl_pricelist_id))->fetch();
                    $newItem = array();
                    $newItem['cl_commission_id'] = $this->id;
                    $newItem['item_order'] = $tmpComissionItem->item_order + 1;
                    $newItem['cl_pricelist_id'] = $oneBond->cl_pricelist_id;
                    $newItem['item_label'] = $oneBond->cl_pricelist->item_label;
                    $newItem['quantity'] = $oneBond->quantity * ($oneBond->multiply == 1) ? $tmpComissionItem->quantity : 1;// $tmpComissionItem->quantity;
                    $newItem['units'] = $oneBond->cl_pricelist->unit;
                    $newItem['price_s'] = $oneBond->cl_pricelist->price_s;
                    $newItem['price_e'] = $oneBond->cl_pricelist->price;
                    $newItem['discount'] = $oneBond->discount;
                    $newItem['price_e2'] = ($oneBond->cl_pricelist->price * (1 - ($oneBond->discount / 100))) * ($oneBond->quantity * $tmpComissionItem->quantity);
                    $newItem['vat'] = $oneBond->cl_pricelist->vat;
                    $newItem['price_e2_vat'] = $oneBond->cl_pricelist->price_vat * (1 - ($oneBond->discount / 100)) * ($oneBond->quantity * $tmpComissionItem->quantity);
                    $newItem['price_e_type'] = $tmpComissionItem->price_e_type;
                    $newItem['cl_parent_bond_id'] = $tmpComissionItem->id;
                    //bdump($newItem);
                    if (!$tmpCommissionItemBond) {
                        $tmpNew = $this->CommissionItemsSelManager->insert($newItem);
                        $tmpId = $tmpNew->id;
                    } else {
                        $newItem['id'] = $tmpCommissionItemBond->id;
                        $tmpNew = $this->CommissionItemsSelManager->update($newItem);
                        $tmpId = $tmpCommissionItemBond->id;
                    }
                }

                /*26.03.2021 insert task from tasklist in pricelist*/
                if ($tmpData = $this->CommissionItemsSelManager->findAll()->where('id = ?', $dataId)->fetch()) {
                    $this->CommissionTaskManager->pricelistTaskInsert($tmpData['cl_pricelist_id'], $tmpData['cl_commission_id']);
                    // $this->activeTab = ;
                     $this->redrawControl('content');
                    //$this->redrawControl('listgridtask');

                    //$this->redrawControl('listgridTask-editLines');
                }
            }

        }

    }

    /*
     * modify data before addline
     */

    private function listgridTaskSave($dataId)
    {
        if ($tmpDataTask = $this->CommissionTaskManager->find($dataId)) {
            $tmpDataWork = $this->CommissionWorkManager->findAll()->where('cl_commission_task_id=?', $dataId)->fetch();

            //22.08.2021 - update sum work_time if there is main_task
            if (!is_null($tmpDataTask['cl_commission_task_id'])) {
                $mainId = $tmpDataTask['cl_commission_task_id'];
            } elseif ($tmpDataTask['main_task'] == 1) {
                $mainId = $tmpDataTask['id'];
            } else {
                $mainId = NULL;
            }
            if (!is_null($mainId)) {
                $sumWorkTime = $this->CommissionTaskManager->findAll()->where('cl_commission_task_id = ?', $mainId)->sum('work_time');
                $sumWorkTime = (is_null($sumWorkTime) ? 0 : $sumWorkTime);
                if (!is_null($tmpDataTask['cl_commission_task_id']))
                    $tmpDataTask->cl_commission_task->update(['work_time' => $sumWorkTime]);
            }


            if ($tmpDataTask->done == 1) {
                $maxItemOrder = $this->CommissionWorkManager->findAll()->where('cl_commission_id = ?', $tmpDataTask->cl_commission_id)
                    ->max('item_order');
                //task is done, insert or update work
                $tmpInsertData = array('cl_commission_id' => $tmpDataTask->cl_commission_id,
                    'cl_users_id' => $tmpDataTask->cl_users_id,
                    'item_order' => $maxItemOrder + 1,
                    'work_label' => $tmpDataTask->name,
                    'work_date_s' => $tmpDataTask->work_date_s,
                    'work_date_e' => $tmpDataTask->work_date_e,
                    'work_time' => $tmpDataTask->work_time,
                    'work_rate' => $tmpDataTask->work_rate,
                    'price_e_type' => $tmpDataTask->price_e_type,
                    'cl_commission_task_id' => $dataId,
                    'note' => $tmpDataTask->note);
                if ($tmpDataWork) {
                    $tmpInsertData['id'] = $tmpDataWork->id;
                    $this->CommissionWorkManager->update($tmpInsertData);
                } else {
                    $this->CommissionWorkManager->insert($tmpInsertData);
                }

            } elseif ($tmpDataTask->done == 0 && $tmpDataWork) {
                //task is not done and exist in work, lets delete from work
                $this->CommissionWorkManager->delete($tmpDataWork->id);

            }
        }
    }

    //21.01.2018 - insert of choosen pricelist item
    //check if there is macro content, if so put it into cl_commission_items

    private function listgridSelSave($dataId)
    {
        if ($tmpItemsSel = $this->CommissionItemsSelManager->find($dataId)) {
            //$macroItems = $this->PriceListMacroManager->findAll()->where('cl_pricelist_macro_id = ?', $tmpItemsSel->cl_pricelist_id);
            $items = $this->CommissionItemsManager->findAll()->where('cl_commission_items_sel_id = ?', $tmpItemsSel->id);
            $newPrice_s = 0;
            foreach ($items as $one) {
                $newQuantity = $this->PriceListMacroManager->getQuantity($one->cl_pricelist_macro_id, $tmpItemsSel->quantity);
                $one->update(array('quantity' => $newQuantity));
                $newPrice_s += $one->price_s * $newQuantity;
            }
            //bdump($newPrice_s);
            //bdump($items == TRUE);
            if ($items && $newPrice_s > 0) {

                $this->CommissionItemsSelManager->updatePrice_s($dataId, $newPrice_s);
                //$tmpItemsSel->update(['price_s' => $newPrice_s]);

            }
        }
    }

    //21.01.2018 - main listGridInsert - its called from listGridInsert

    public function handleSavePDFWorks($id)
    {
        $data = $this->DataManager->find($id);
        $template = $this->createMyTemplateWS($data, $this->ReportManager->getReport(__DIR__ . '/../templates/Commission/rptWorks.latte'));
        $this->pdfCreate($template, $data['cm_number'] . $this->translator->translate('Práce_na_zakázce'));

    }

    //javascript call when changing cl_partners_book_id

    public function handleSavePDFTasks($id)
    {
        $data = $this->DataManager->find($id);
        $template = $this->createMyTemplateWS($data, $this->ReportManager->getReport(__DIR__ . '/../templates/Commission/rptTasks.latte'));
        $this->pdfCreate($template, $data['cm_number'] . $this->translator->translate('Úkoly_na_zakázce'));
    }

    public function handleReport($index = 0)
    {
        $this->rptIndex = $index;
        $this->reportModalShow = TRUE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    public function stepBackReportClients()
    {
        $this->rptIndex = 0;
        $this->reportModalShow = FALSE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    public function SubmitReportClientsSubmitted(Form $form)
    {
        $data = $form->values;

        if ($form['save']->isSubmittedBy()) {
            $data['cl_partners_branch'] = array();
            if ($data['cm_date_to'] == "")
                $data['cm_date_to'] = NULL;
            else {
                $data['cm_date_to'] = date('Y-m-d H:i:s', strtotime($data['cm_date_to']) + 86400 - 1);
            }

            if ($data['cm_date_from'] == "")
                $data['cm_date_from'] = NULL;
            else
                $data['cm_date_from'] = date('Y-m-d H:i:s', strtotime($data['cm_date_from']));

            if ($data['type'] == 0) {
                $dataReport = $this->DataManager->findAll()->
                where('cm_date >= ? AND cm_date <= ? ', $data['cm_date_from'], $data['cm_date_to'])->
                order('cl_partners_book.company ASC, cl_partners_category_id ASC, cm_date ASC');
            } elseif ($data['type'] == 1) {
                $dataReport = $this->DataManager->findAll()->
                where('delivery_date >= ? AND delivery_date <= ? ', $data['cm_date_from'], $data['cm_date_to'])->
                order('cl_partners_book.company ASC, cl_partners_category_id ASC, delivery_date ASC');
            }

            if (count($data['cl_partners_book']) > 0) {

                $tmpPartners = array();
                $tmpBranches = array();
                foreach ($data['cl_partners_book'] as $one) {
                    $arrOne = str_getcsv($one, "-");
                    $tmpPartners[] = $arrOne[0];
                    $tmpBranches[] = $arrOne[1];
                }
                $data['cl_partners_book'] = $tmpPartners;
                $data['cl_partners_branch'] = $tmpBranches;

                //$dataReport = $dataReport->where(array('cl_commission.cl_partners_book_id' => $data['cl_partners_book']))->
                //							where(array('cl_commission.cl_partners_branch_id' => $data['cl_partners_branch']));
                $dataReport = $dataReport->where('cl_partners_book_id IN (?) OR cl_partners_branch_id IN (?)', $data['cl_partners_book'], $data['cl_partners_branch']);
            }

            if (count($data['cl_center_id']) > 0) {
                $dataReport = $dataReport->where(array('cl_commission.cl_center_id' => $data['cl_center_id']));
            }

            if (count($data['cl_users_id']) > 0) {
                $dataReport = $dataReport->where(array('cl_commission.cl_users_id' => $data['cl_users_id']));
            }

            if ($data['done']) {
                $dataReport->where('cl_status.s_fin = 1');
            }

            $data['price_e2_from'] = str_replace(' ', '', $data['price_e2_from']);
            $data['price_e2_from'] = str_replace(',', '.', $data['price_e2_from']);
            $data['price_e2_to'] = str_replace(' ', '', $data['price_e2_to']);
            $data['price_e2_to'] = str_replace(',', '.', $data['price_e2_to']);
            if ($data['price_e2_from'] != $data['price_e2_to'] && $data['price_e2_to'] > 0) {
                $dataReport->where('cl_commission.price_e2_base*cl_commission.currency_rate >= ? AND cl_commission.price_e2_base*cl_commission.currency_rate <= ?', $data['price_e2_from'], $data['price_e2_to']);
            }


            //bdump($data);
            $dataOther = array();//$this->CommissionTaskManager->find($itemId);
            $dataSettings = $data;
            //$dataOther['dataSettingsPartners']   = $this->PartnersManager->findAll()->where(array('id' =>$data['cl_partners_book']))->order('company');
            $dataOther['dataSettingsPartners'] = $this->PartnersManager->findAll()->
            where('cl_partners_book.id IN (?) OR :cl_partners_branch.id IN (?)', $data['cl_partners_book'], $data['cl_partners_branch'])->
            select('cl_partners_book.company')->
            order('company');
            //select('CONCAT(cl_partners_book.company," ",:cl_partners_branch.b_name) AS company')->
            $dataOther['dataSettingsCenter'] = $this->CenterManager->findAll()->where(array('id' => $data['cl_center_id']))->order('name');
            $dataOther['dataSettingsUsers'] = $this->UserManager->getAll()->where(array('id' => $data['cl_users_id']))->order('name');
            $dataOther['settings'] = $this->settings;
            $template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/Commission/rptCommission.latte', $dataOther, $dataSettings, 'Přehled zakázek');
            $tmpDate1 = new \DateTime($data['cm_date_from']);
            $tmpDate2 = new \DateTime($data['cm_date_to']);
            $this->pdfCreate($template, 'Přehled zakázek ' . date_format($tmpDate1, 'd.m.Y') . ' - ' . date_format($tmpDate2, 'd.m.Y'));
        }
    }

    public function handleShowTextsUse()
    {
        //bdump('ted');
        $this->pairedDocsShow = TRUE;
        $this->showModal('textsUseModal');
        $this->redrawControl('textsUse');
        //$this->redrawControl('contents');
    }

    public function groupActionsMethod($data, $checked, $totalR)
    {
        parent::groupActionsMethod($data, $checked, $totalR);
        if ($data['action'] == 'invoice') {
            $counterR = 0;
            $counterTotal = 0;
            $errorCM = '';
            try {
                session_write_close();
                foreach ($checked as $key => $one) {
                    $this->UserManager->setProgressBar($counterR++, $totalR, $this->user->getId(), $this->translator->translate('Probíhá_vytváření_faktur') . ' <br>' . $counterR . ' / ' . $totalR);
                    if (($tmpCommission = $this->DataManager->find($one)) && is_null($tmpCommission['cl_invoice_id'])) {
                        $dataItems = $this->CommissionItemsSelManager->findAll()->where('cl_commission_id = ?', $one)->fetchPairs('id', 'id');
                        //bdump($dataItems);
                        $dataWorks = $this->CommissionWorkManager->findAll()->where('cl_commission_id = ?', $one)->fetchPairs('id', 'id');
                        $arrRet = $this->DataManager->CreateInvoice2(json_encode($dataItems), json_encode($dataWorks), $one, 1);
                        if (self::hasError($arrRet)) {
                            $this->flashMessage($this->translator->translate('Chyba_při_vytváření_faktury'), 'error');
                        }else{
                            //19.02.2022 - set status to finished
                            if ($nStatus= $this->StatusManager->findAll()->where('status_use = ? AND s_fin = ?','commission',1)->fetch()){
                                $arrData = [];
                                $arrData['id'] = $one;
                                $arrData['cl_status_id'] = $nStatus->id;
                                //bdump($arrData);
                                $this->DataManager->update($arrData);
                            }
                            $counterTotal++;
                        }
                    } else {
                        $errorCM = $errorCM . ($errorCM != '')?', ':'' . $tmpCommission['cm_number'];
                        //$this->flashMessage($this->translator->translate('Faktura_již_existuje_pro_zakázku') . ' ' . $tmpCommission['cm_number'], 'error');
                    }
                }
                $this->UserManager->resetProgressBar($this->user->getId());
                if ($counterTotal > 0) {
                    $this->flashMessage($this->translator->translate('Všechny_požadované_faktury_byly_vytvořeny') . ': ' . $counterTotal, 'success');
                  //  $this->redirect(':Application:Invoice:default', array('id' => NULL));
                }elseif ($errorCM != ''){
                    $this->flashMessage($this->translator->translate('Vytvořené_faktury') . ': ' . $counterTotal, 'success');
                    $this->flashMessage($this->translator->translate('Z_těchto_zakázek_nebyly_faktury_vytvořeny') . ': ' . $errorCM, 'error');
                }
          } catch (\Exception $e) {
                $this->flashMessage($this->translator->translate('Chyba_při_vytváření_faktur'), 'error');
                Debugger::log('GroupInvoiceFromCommission . ' . $e->getMessage());
             //   $this->redirect(':Application:Commission:default', array('id' => NULL));
            }
        }

    }

    public function handleCreateInvoice($dataItems, $dataWorks, $newInvoice)
    {
        $arrRet = $this->DataManager->CreateInvoice2($dataItems, $dataWorks, $this->id, $newInvoice);
        if (self::hasError($arrRet)) {
            $this->flashMessage($this->translator->translate('Chyba_při_vytváření_faktury'), 'error');
        } else {
            //19.02.2022 - set status to finished
            if ($nStatus= $this->StatusManager->findAll()->where('status_use = ? AND s_fin = ?','commission',1)->fetch()){
                $arrData['id'] = $this->id;
                $arrData['cl_status_id'] = $nStatus->id;
                $this->DataManager->update($arrData);
            }
            $this->flashMessage($this->translator->translate('Změny_byly_uloženy_faktura_byla_vytvořena'), 'success');
            $this->createDocShow = FALSE;

            //14.11.2020 - redirect to invoice edit by javascript
            $this->redirect(':Application:Invoice:edit', ['id' => $arrRet['invoiceId']]);
        }
    }

    public function handleCreateInvoiceAdvance($dataItems, $dataWorks, $newInvoice)
    {
        $arrRet = $this->DataManager->CreateInvoiceAdvance2($dataItems, $dataWorks, $this->id, $newInvoice);
        if (self::hasError($arrRet)) {
            $this->flashMessage($this->translator->translate('Chyba_při_vytváření_zálohové_faktury'), 'error');
        } else {
            //19.02.2022 - set status to finished
            if ($nStatus= $this->StatusManager->findAll()->where('status_use = ? AND s_fin = ?','commission', 1)->fetch()){
                $arrData['id'] = $this->id;
                $arrData['cl_status_id'] = $nStatus->id;
                $this->DataManager->update($arrData);
            }
            $this->flashMessage($this->translator->translate('Změny_byly_uloženy_zálohová_faktura_byla_vytvořena'), 'success');
            $this->createDocShow = FALSE;
            //14.11.2020 - redirect to invoice edit by javascript
            $this->redirect(':Application:InvoiceAdvance:edit', ['id' => $arrRet['invoiceId']]);
        }
    }


    public function handleCreateStoreOut($dataItemsSel, $dataItems, $makeDN = FALSE)
    {
        $arrDataItems = json_decode($dataItems, true);
        $arrDataItemsSel = json_decode($dataItemsSel, true);

        $docId = $this->DataManager->createOut($this->id, $arrDataItemsSel, $arrDataItems);
        if ($makeDN) {
            $arrRet = $this->makeDeliveryNote($docId);
            if (self::hasError($arrRet)) {
                $this->flashMessage($this->translator->translate('Dodací_list_nebyl_vytvořen'), 'warning');
            } else {
                $this->PairedDocsManager->insertOrUpdate(['cl_commission_id' => $this->id, 'cl_delivery_note_id' => $arrRet['deliveryN_id']]);
                $this->flashMessage($this->translator->translate('Dodací_list_byl_vytvořen'), 'success');
                $this->payload->id = $arrRet['deliveryN_id'];
            }
        }
        if (!is_null($docId)) {
            $this->flashMessage($this->translator->translate('Výdejka_byla_vytvořena'), 'success');
            $this->createDocShow = FALSE;
            if ($docId != NULL) {
                $this->payload->id = $docId;
            }
        }
        if ($makeDN) {
            $this->redirect(':Application:DeliveryNote:edit', ['id' => $arrRet['deliveryN_id']]);
        } else {
            $this->redirect(':Application:Store:edit', ['id' => $docId]);
        }

    }

    public function handleCreateStoreOutUpdate($dataItemsSel, $dataItems, $makeDN = FALSE)
    {
        $arrDataItems = json_decode($dataItems, true);
        $arrDataItemsSel = json_decode($dataItemsSel, true);

        $docId = NULL;
        //bdump($arrDataItems);
        $tmpStoreDoc = NULL;
        foreach ($arrDataItemsSel as $key => $one) {
            $commissionItem = $this->CommissionItemsSelManager->find($one);
            if ($commissionItem) {

                if (!is_null($commissionItem->cl_pricelist_id) && !is_null($this->settings->cl_storage_id_commission)) {

                    //if there is new cl_store_docs_id on sel item, then we have to delete content of cl_Store_docs related to this cl_commission
                    if ($tmpStoreDoc != $commissionItem->cl_store_docs_id) {

                        if ($commissionItem->cl_store_docs_id != NULL) {
                            $tmpStoreDoc = $commissionItem->cl_store_docs_id;
                            //delete items for this cl_Store_docs from cl_store_move
                            foreach ($this->CommissionItemsSelManager->findAll()->where('cl_store_docs_id = ?', $tmpStoreDoc) as $keyDel => $oneDel) {
                                $this->StoreManager->deleteItemStoreMove($oneDel);

                            }
                            $tmpDel = $this->CommissionItemsSelManager->
                            findAll()->
                            where('cl_commission_id = ? AND cl_store_docs_id = ? AND id NOT IN ?', $this->id, $tmpStoreDoc, $arrDataItemsSel);
                            $tmpDel->update(array('cl_store_docs_id' => NULL));
                        }
                    }

                    //update cl_commission.cl_store_docs_id with current cl_store_docs_id
                    $commissionItem->cl_commission->update(array('cl_store_docs_id' => $tmpStoreDoc));

                    $tmpData = $this->CommissionItemsSelManager->findAll()->
                    where('cl_commission_id = ?', $this->id);
                    $tmpData->update(array('cl_storage_id' => $this->settings->cl_storage_id_commission));

                    //commission items - give out
                    //1. check if cl_store_docs exists if not, create new one
                    $docId = $this->StoreDocsManager->createStoreDoc(1, $this->id, $this->DataManager, FALSE);
                    $this->StoreDocsManager->update(array('id' => $docId, 'doc_title' => $this->translator->translate('prodejní_položky_zakázky')));
                    //$this->StoreDocsManager->update(array('id' => $docId, 'doc_title' => 'výdejka ze zakázky'));

                    //store doc is created from commission, we need to update cl_invoice_id too
                    $this->StoreDocsManager->update(array('id' => $docId, 'cl_commission_id' => $commissionItem->cl_commission_id));
                    $commissionItem->update(array('cl_store_docs_id' => $docId));

                    //2. giveout current item
                    $dataId = $this->StoreManager->giveOutItem($docId, $commissionItem->id, $this->CommissionItemsSelManager);

                    //create pairedocs record with created cl_store_docs_id
                    $this->PairedDocsManager->insertOrUpdate(array('cl_commission_id' => $this->id, 'cl_store_docs_id' => $docId));
                }
            }
        }

        //$docId = NULL;
        //bdump($arrDataItems);
        $tmpStoreDoc = NULL;
        foreach ($arrDataItems as $key => $one) {
            $commissionItem = $this->CommissionItemsManager->find($one);
            if ($commissionItem) {

                if (!is_null($commissionItem->cl_pricelist_id) && !is_null($this->settings->cl_storage_id_commission)) {

                    //if there is new cl_store_docs_id on sel item, then we have to delete content of cl_Store_docs related to this cl_commission
                    if ($tmpStoreDoc != $commissionItem->cl_store_docs_id) {

                        if ($commissionItem->cl_store_docs_id != NULL) {
                            $tmpStoreDoc = $commissionItem->cl_store_docs_id;
                            //delete items for this cl_Store_docs from cl_store_move
                            foreach ($this->CommissionItemsManager->findAll()->where('cl_store_docs_id = ?', $tmpStoreDoc) as $keyDel => $oneDel) {
                                $this->StoreManager->deleteItemStoreMove($oneDel);

                            }
                            $tmpDel = $this->CommissionItemsManager->
                            findAll()->
                            where('cl_commission_id = ? AND cl_store_docs_id = ? AND id NOT IN ?', $this->id, $tmpStoreDoc, $arrDataItems);
                            $tmpDel->update(array('cl_store_docs_id' => NULL));
                        }
                    }

                    //update cl_commission.cl_store_docs_id with current cl_store_docs_id
                    $commissionItem->cl_commission->update(array('cl_store_docs_id' => $tmpStoreDoc));

                    $tmpData = $this->CommissionItemsManager->findAll()->
                    where('cl_commission_id = ?', $this->id);
                    $tmpData->update(array('cl_storage_id' => $this->settings->cl_storage_id_commission));

                    //commission items - give out
                    //1. check if cl_store_docs exists if not, create new one
                    $docId = $this->StoreDocsManager->createStoreDoc(1, $this->id, $this->DataManager, FALSE);
                    $this->StoreDocsManager->update(array('id' => $docId, 'doc_title' => $this->translator->translate('nákladové_položky_zakázky')));

                    //store doc is created from commission, we need to update cl_invoice_id too
                    $this->StoreDocsManager->update(array('id' => $docId, 'cl_commission_id' => $commissionItem->cl_commission_id));
                    $commissionItem->update(array('cl_store_docs_id' => $docId));

                    //2. giveout current item
                    $dataId = $this->StoreManager->giveOutItem($docId, $commissionItem->id, $this->CommissionItemsManager);

                    //create pairedocs record with created cl_store_docs_id
                    $this->PairedDocsManager->insertOrUpdate(array('cl_commission_id' => $this->id, 'cl_store_docs_id' => $docId));
                }
            }
        }


        //update storedocs_id in commission
        $this->DataManager->update(array('id' => $this->id, 'cl_store_docs_id' => $docId));

        $this->flashMessage($this->translator->translate('Výdejky_byly_aktualizovány'), 'success');
        //$this->redirect('Offer:default');
        $this->createDocShow = FALSE;
        //$this->hideModal('createDocModal');
        //$this->redrawControl('unMoHandler');
        if ($makeDN) {
            $arrRet = $this->makeDeliveryNote($docId);
            if (self::hasError($arrRet)) {
                $this->flashMessage($this->translator->translate('Dodací_list_nebyl_aktualizován'), 'warning');
            } else {
                //create pairedocs record with created cl_store_docs_id
                $this->PairedDocsManager->insertOrUpdate(array('cl_commission_id' => $this->id, 'cl_delivery_note_id' => $arrRet['deliveryN_id']));
                $this->flashMessage($this->translator->translate('Dodací_list_byl_aktualizován'), 'success');
//                    $this->payload->id = $arrRet['id'];
                $this->redirect(':Application:DeliveryNote:edit', array('id' => $arrRet['deliveryN_id']));
            }
        } else {
            //$this->payload->id = $docId;
            $this->redirect(':Application:Store:edit', array('id' => $docId));
        }
        //$this->redrawControl('content');
    }

    private function makeDeliveryNote($id)
    {
        $retArr = $this->DeliveryNoteManager->createDelivery($id);
        return $retArr;
    }

    public function handleCreateStoreOutModalWindow()
    {

        $this->filterStoreCreate = array('filter' => 'cl_store_docs_id IS NULL AND cl_pricelist_id IS NOT NULL');
        $this['listgridItemsSelSelect']->setFilter($this->filterStoreCreate);
        $this['listgridItemsSelect']->setFilter($this->filterStoreCreate);

        $this->createDocShow = TRUE;
        $this->showModal('createStoreOutModal');
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('createDocs');
        $this->redrawControl('contents');
    }

    public function handleCreateStoreOutUpdateModalWindow()
    {
        $this->filterStoreUpdate = array('filter' => 'cl_pricelist_id IS NOT NULL');
        $this['listgridItemsSelSelect']->setFilter($this->filterStoreUpdate);
        $this['listgridItemsSelect']->setFilter($this->filterStoreUpdate);

        $this->createDocShow = TRUE;
        $this->showModal('createStoreOutUpdateModal');
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('createDocs');
        $this->redrawControl('contents');
    }

    public function handleCreateInvoiceModalWindow()
    {
        $this['listgridItemsSelSelect']->setFilter($this->filterInvoiceUsed);
        $this['listgridWorksSelect']->setFilter($this->filterInvoiceUsed);
        $this->createDocShow = TRUE;
        $this->showModal('createInvoiceModal');
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('createDocs');
        $this->redrawControl('contents');
    }

    public function handleCreateInvoiceAdvanceModalWindow()
    {
        $this['listgridItemsSelSelect']->setFilter($this->filterInvoiceUsed);
        $this['listgridWorksSelect']->setFilter($this->filterInvoiceUsed);
        $this->createDocShow = TRUE;
        $this->showModal('createInvoiceAdvanceModal');
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('createDocs');
        $this->redrawControl('contents');
    }

    public function handleShowStoreUsed()
    {
        $this->filterStoreUsed = array();
        $this->redrawControl('itemsForStore');
    }

    //control method to determinate if we can delete

    public function handleShowStoreNotUsed()
    {
        //$this->filterCommissionUsed = array('filter' => 'cl_store_docs_id IS NULL');
        $this->redrawControl('itemsForCommission');
    }

    //aditional control before delete from baseList

    public function handleShowInvoiceUsed()
    {
        $this->filterInvoiceUsed = array();
        //$this->filterInvoiceUsed = array('filter' => 'cl_invoice_id IS NULL');
        $this['listgridItemsSelSelect']->setFilter($this->filterInvoiceUsed);
        $this['listgridWorksSelect']->setFilter($this->filterInvoiceUsed);
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('itemsForInvoice');
    }

    public function handleShowInvoiceNotUsed()
    {
        $this->filterInvoiceUsed = array('filter' => 'cl_invoice_id IS NULL');
        $this['listgridItemsSelSelect']->setFilter($this->filterInvoiceUsed);
        $this['listgridWorksSelect']->setFilter($this->filterInvoiceUsed);
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('itemsForInvoice');
    }


    public function OrderSelItemsMain2()
    {
        $tmpData = $this->DataManager->findOneBy(array('id' => $this->id));
        $one2 = NULL;
        if ($tmpData) {
            $arrDocId = array();
            $dataMoveSel = $this->DataManager->getAll2SelForOrder($this->id);

            if ($tmpData->cm_title == "") {
                $od_title = $this->translator->translate('prodejní_položky_pro_zakázku') . $tmpData->cm_number;
            } else {
                $od_title = $tmpData->cm_title;
            }
            $arrDocId[] = $this->OrderManager->createOrder($dataMoveSel, $od_title, NULL, array(NULL), $tmpData['cl_storage_id'], $this->user->getId());  //$tmpData['cl_users_id']


            $counter = 0;
            foreach ($arrDocId as $one) {
                foreach ($one as $one2) {
                    //create pairedocs record
                    $this->PairedDocsManager->insertOrUpdate(array('cl_commission_id' => $this->id, 'cl_order_id' => $one2));
                    foreach ($dataMoveSel as $keySel => $oneSel) {
                        $oneSel->update(array('cl_order_id' => $one2));
                    }
                    $counter++;
                }
            }
        } else {
            $counter = 0;
        }
        return array($counter, $one2);
    }

    public function OrderCostItemsMain2()
    {
        $one2 = NULL;
        $tmpData = $this->DataManager->findOneBy(array('id' => $this->id));
        if ($tmpData) {
            $arrDocId = array();
            $dataMove = $this->DataManager->getAll2ForOrder($this->id);
            if ($tmpData->cm_title == "") {
                $od_title = 'nákladové položky pro zakázku ' . $tmpData->cm_number;
            } else {
                $od_title = $tmpData->cm_title;
            }

            $arrDocId[] = $this->OrderManager->createOrder($dataMove, $od_title, NULL, array(NULL), $tmpData['cl_storage_id'], $this->user->getId()); //$tmpData['cl_users_id']


            $counter = 0;
            foreach ($arrDocId as $one) {
                foreach ($one as $one2) {
                    //create pairedocs record
                    $this->PairedDocsManager->insertOrUpdate(array('cl_commission_id' => $this->id, 'cl_order_id' => $one2));
                    foreach ($dataMove as $keyMove => $oneMove) {
                        $oneMove->update(array('cl_order_id' => $one2));
                    }
                    $counter++;
                }
            }
        } else {
            $counter = 0;
        }

        return array($counter, $one2);
    }


    /**
     * generate order for all items of commission into one order
     *
     */
    public function handleGenOrderAll2()
    {

        $counter = 0;
        $arrRet = $this->OrderSelItemsMain2();
        $counter += $arrRet[0];
        $arrRet = $this->OrderCostItemsMain2();
        $counter += $arrRet[0];


        if ($counter > 0) {
            $this->flashMessage($this->translator->translate('Objednávky_byly_vygenerovány'), 'success');
            //20.02.2020 - next line is important for change url in address line of browser
            $this->payload->allowAjax = false;
            $this->redirect('Order:edit', array('id' => $arrRet[1]));
        } else {
            $this->flashMessage($this->translator->translate('Objednávky_nebyly_vygenerovány'), 'danger');
            $this->redrawControl('flash');
            $this->redrawControl('content');
            $this->redrawControl('baselist');
            $this->redrawControl('baselistArea');
        }

        //$this->redrawControl('content');

    }

    public function OrderSelItemsMain()
    {
        $arrDocId = "";
        $tmpData = $this->DataManager->findOneBy(array('id' => $this->id));
        if ($tmpData) {
            if ($tmpData->cm_title == "") {
                $od_title = $this->translator->translate('prodejní_položky_pro_zakázku') . $tmpData->cm_number;
            } else {
                $od_title = $tmpData->cm_title;
            }
            $dataMoveSel = $this->DataManager->getAllSelForOrder($this->id);
            //$dataMoveSel 	= $this->ArraysManager->select2array($dataMoveSel);

            $arrDocId = $this->OrderManager->createOrder($dataMoveSel, $od_title, NULL, array(NULL), $tmpData['cl_storage_id'], $this->user->getId()); //$tmpData['cl_users_id']
        }
        $counter = 0;
        foreach ($arrDocId as $one) {
            foreach ($one as $one2) {
                //create pairedocs record
                $this->PairedDocsManager->insertOrUpdate(array('cl_commission_id' => $this->id, 'cl_order_id' => $one2));
                foreach ($dataMoveSel as $keySel => $oneSel) {
                    $oneSel->update(array('cl_order_id' => $one2));
                }
                $counter++;
            }
        }
        return $counter;
    }

    public function OrderCostItemsMain()
    {
        $arrDocId = "";
        $tmpData = $this->DataManager->findOneBy(array('id' => $this->id));
        if ($tmpData) {
            if ($tmpData->cm_title == "") {
                $od_title = $this->translator->translate('nákladové_položky_pro_zakázku') . $tmpData->cm_number;
            } else {
                $od_title = $tmpData->cm_title;
            }
            //$arrDocId[] = $this->OrderManager->createOrder($dataMoveSel, $od_title);
            $dataMove = $this->DataManager->getAllForOrder($this->id);
            //$dataMove		= $this->ArraysManager->select2array($dataMove);
            $arrDocId[] = $this->OrderManager->createOrder($dataMove, $od_title, NULL, array(NULL), $tmpData['cl_storage_id'], $this->user->getId()); //$tmpData['cl_users_id']
        }
        $counter = 0;
        foreach ($arrDocId as $one) {
            foreach ($one as $one2) {
                //create pairedocs record
                $this->PairedDocsManager->insertOrUpdate(array('cl_commission_id' => $this->id, 'cl_order_id' => $one2));
                foreach ($dataMove as $keyMove => $oneMove) {
                    $oneMove->update(array('cl_order_id' => $one2));
                }
                $counter++;
            }
        }

        return $counter;

    }

    /**
     * generate order for all items of commission split into orders for suppliers
     *
     */
    public function handleGenOrderAll()
    {
        $counter = 0;
        $counter += $this->OrderSelItemsMain();
        $counter += $this->OrderCostItemsMain();

        //bdump($arrDocId);

        if ($counter > 0) {
            $this->flashMessage($this->translator->translate('Objednávky_byly_vygenerovány.'), 'success');
            //20.02.2020 - next line is important for change url in address line of browser
            $this->payload->allowAjax = false;
            $this->redirect('Order:default');
        } else {
            $this->flashMessage($this->translator->translate('Objednávky_nebyly_vygenerovány.'), 'danger');
        }


        $this->redrawControl('flash');
        $this->redrawControl('content');
    }


    /*27.07.2018 - metod called after saving record in listgrid component
    * here we are solving for example transfering finished record from tasks to work
    */

    public function handleGenOrderMissing()
    {
        //bdump($this->id);
        //bdump($this->bscId);
        $tmpData = $this->DataManager->findOneBy(array('id' => $this->id));
        if ($tmpData) {
            $arrDocId = array();
            $dataMoveSel = $this->DataManager->getMissingSelForOrder($this->id);
            //$dataMoveSel 	= $this->ArraysManager->select2array($dataMoveSel);
            $arrDocId[] = $this->OrderManager->createOrder($dataMoveSel, $this->translator->translate('prodejní_položky_pro_zakázku') . $tmpData->cm_number, NULL, array(NULL), NULL, $this->user->getId()); //$tmpData['cl_users_id']
            $dataMove = $this->DataManager->getMissingForOrder($this->id);
            //$dataMove 		= $this->ArraysManager->select2array($dataMove);
            $arrDocId[] = $this->OrderManager->createOrder($dataMove, $this->translator->translate('nákladové_položky_pro_zakázku') . $tmpData->cm_number, NULL, array(NULL), NULL, $this->user->getId()); //$tmpData['cl_users_id']
            $counter = 0;
            foreach ($arrDocId as $one) {
                foreach ($one as $one2) {
                    //create pairedocs record
                    $this->PairedDocsManager->insertOrUpdate(array('cl_commission_id' => $this->id, 'cl_order_id' => $one2));
                    foreach ($dataMoveSel as $keySel => $oneSel) {
                        $oneSel->update(array('cl_order_id' => $one2));
                    }
                    foreach ($dataMove as $keyMove => $oneMove) {
                        $oneMove->update(array('cl_order_id' => $one2));
                    }
                    $counter++;
                }
            }
            if ($counter > 0) {
                $this->flashMessage($this->translator->translate('Objednávky_byly_vygenerovány.'), 'success');
                //20.02.2020 - next line is important for change url in address line of browser
                $this->payload->allowAjax = false;
                $this->redirect('Order:edit', array('id' => $one2));
            } else {
                $this->flashMessage($this->translator->translate('Objednávky_nebyly_vygenerovány.'), 'danger');
            }
            $this->redrawControl('content');
        } else {
            $this->flashMessage($this->translator->translate('Objednávky_nebyly_vygenerovány.'), 'danger');
        }
        $this->redrawControl('flash');
        $this->redrawControl('content');
    }

    /**14.11.2020 - check if is possible to make outcome. This could be only when invoice doesn't exists
     * @return bool|string
     */
    public function StoreOutDisabled()
    {
        $tmpData = $this->DataManager->find($this->id);
        $result = TRUE;
        if ($tmpData) {
            if ($tmpData->cl_invoice_id != NULL) {
                $result = $this->translator->translate("K_zakázce_již_existuje_faktura");
            } else {
                $result = FALSE;
            }

        }

        return $result;
    }


    /**14.11.2020 - check if is possible to make invoice. This could be only when outcome from store doesn't exists
     * @return bool|string
     */
    public function InvoiceDisabled()
    {
        $tmpData = $this->DataManager->find($this->id);
        $result = TRUE;
        if ($tmpData) {
            if ($tmpData->cl_store_docs_id != NULL) {
                $result = $this->translator->translate("K_zakázce_již_existuje_výdejka");
            } else {
                $result = FALSE;
            }

        }

        return $result;
    }

    /*21.01.2018 - separate process for save task
    *
    */

    protected function createComponentEditTextFooter()
    {
        return new Controls\EditTextControl($this->translator,
            $this->DataManager, $this->id, 'footer_txt');
    }

    protected function createComponentEditTextHeader()
    {
        return new Controls\EditTextControl($this->translator,
            $this->DataManager, $this->id, 'header_txt');
    }

    protected function createComponentEditTextDescription()
    {
        return new Controls\EditTextControl($this->translator,
            $this->DataManager, $this->id, 'description_txt');
    }

    protected function createComponentPairedDocs()
    {
        // $translator = clone $this->translator;
        // $translator->setPrefix([]);
        return new PairedDocsControl(
            $this->DataManager, $this->id, $this->PairedDocsManager, $this->translator);
    }

    protected function createComponentTextsUse()
    {
        //$translator = clone $this->translator;
        //$translator->setPrefix([]);
        return new TextsUseControl($this->DataManager, $this->id, 'commission', $this->TextsManager, $this->translator);
    }

    protected function createComponentSumOnDocs()
    {
        //$this->translator->setPrefix(['applicationModule.Commission']);
        if ($data = $this->DataManager->findBy(array('id' => $this->id))->fetch()) {
            if ($data->cl_currencies) {
                $tmpCurrencies = $data->cl_currencies->currency_name;
            }

            //$tmpCommissionItemsPackage = $this->CommissionItemsSelManager->findBy(array('cl_commission_id' => $this->id))->
            //                                       where('cl_pricelist.cl_pricelist_group.is_return_package = 1');

            //$price_s_package            = $tmpCommissionItemsPackage->sum('cl_commission_items_sel.price_s * cl_commission_items_sel.quantity');
            //$price_e2_package           = $tmpCommissionItemsPackage->sum('cl_commission_items_sel.price_e2');
            //$price_e2_vat_package       = $tmpCommissionItemsPackage->sum('cl_commission_items_sel.price_e2_vat');

            $data_price_s = $data->price_s - $data->price_s_package; //costs only from sel_items
            $data_price_e = $data->price_e - $data->price_e2_package; //costs with work
            $data_price_e2 = $data->price_e2 - $data->price_e2_package; //sell price
            $data_price_e2_base = $data->price_e2_base - $data->price_e2_package; // total sell price without packages

            $data_price_invoice_arrived = $this->InvoiceArrivedCommissionManager->findAll()->where('cl_commission_id = ?', $this->id)->sum('amount');

            if ($data_price_s > 0) {
                $tmpProfit = (int)((($data_price_e2 / ($data_price_s + $data_price_invoice_arrived) ) - 1) * 100);
            } else {
                $tmpProfit = 100;
            }
            if ($data->price_w > 0) {
                $tmpProfitW = (int)((($data->price_w2 / $data->price_w) - 1) * 100);
            } else {
                $tmpProfitW = 100;
            }

            if ($data->price_pe2_base > 0) {
                $tmpPriceE2Base = $data->price_pe2_base;
                $tmpPriceE2Vat = $data->price_pe2_vat;
                $tmpProfitAbs = $data->price_pe2_base - ($data->price_w + $data_price_s + $data_price_invoice_arrived);
            } else {
                $tmpPriceE2Base = $data->price_e2_base;
                $tmpPriceE2Vat = $data->price_e2_vat;
                $tmpProfitAbs = $data_price_e2_base - ($data->price_w + $data_price_s + $data_price_invoice_arrived);
            }
            if ($data_price_e > 0) {
                //$tmpProfitWS = (int)(((($data->price_w2+$data->price_e2 ) / $data->price_e)-1)*100);
                if ($data->price_pe2_base > 0) {
                    $tmpProfitWS = (int)((($data->price_pe2_base / $data_price_e) - 1) * 100);

                } else {
                    $tmpProfitWS = (int)(((($data->price_w2 + $data_price_e2) / $data_price_e) - 1) * 100);

                }
            } else {
                $tmpProfitWS = 100;
            }

            if ($this->settings->platce_dph) {
                $tmpPriceNameBase = $this->translator->translate("Celkem_bez_DPH");
                $tmpPriceNameVat = $this->translator->translate("Celkem_s_DPH");
            } else {
                $tmpPriceNameBase = $this->translator->translate("Celkem");
                $tmpPriceNameVat = "";
            }


            $dataArr = [
                ['name' => $this->translator->translate('Přijaté_faktury'), 'value' => $data_price_invoice_arrived, 'currency' => $tmpCurrencies, 'title' => $this->translator->translate('Součet_cen_bez_dph_z_přiřazených_přijatých_faktur')],
                ['name' => $this->translator->translate('Nákup_položek'), 'value' => $data_price_s, 'currency' => $tmpCurrencies],
                ['name' => $this->translator->translate('Zisk_položek') . ' ' .$tmpProfit . ' %:', 'value' => ($data_price_e2 - $data_price_s - $data_price_invoice_arrived), 'currency' => $tmpCurrencies],
                ['name' => $this->translator->translate('Prodej_položek'), 'value' => $data_price_e2, 'currency' => $tmpCurrencies],
                ['name' => 'separator'],
                ['name' => $this->translator->translate('Náklady_práce'), 'value' => $data->price_w, 'currency' => $tmpCurrencies],
                ['name' => $this->translator->translate('Zisk_práce') . ' ' . $tmpProfitW . ' %:', 'value' => ($data->price_w2 - $data->price_w), 'currency' => $tmpCurrencies],
                ['name' => $this->translator->translate('Prodej_práce'), 'value' => $data->price_w2, 'currency' => $tmpCurrencies],
                ['name' => 'separator'],
                ['name' => $this->translator->translate('Náklady_celkem'), 'value' => $data_price_s + $data->price_w + $data_price_invoice_arrived, 'currency' => $tmpCurrencies],
                ['name' => $this->translator->translate('Zisk_celkem') . ' ' . $tmpProfitWS . ' %:', 'value' => $tmpProfitAbs, 'currency' => $tmpCurrencies],
                ['name' => $tmpPriceNameBase, 'value' => $tmpPriceE2Base, 'currency' => $tmpCurrencies],
                ['name' => $tmpPriceNameVat, 'value' => $tmpPriceE2Vat, 'currency' => $tmpCurrencies],
            ];
        } else {
            $dataArr = [];
        }

        return new SumOnDocsControl($this->translator, $this->DataManager, $this->id, $this->settings, $dataArr);
    }

    protected function createComponentEmail()
    {
        //  $translator = clone $this->translator->setPrefix([]);
        return new Controls\EmailControl($this->translator, $this->EmailingManager, $this->mainTableName, $this->id);
    }

    protected function createComponentFiles()
    {
        if ($this->getUser()->isLoggedIn()) {
            $user_id = $this->user->getId();
            $cl_company_id = $this->settings->id;
        }
        // $translator = clone $this->translator->setPrefix([]);
        return new Controls\FilesControl($this->translator, $this->FilesManager, $this->UserManager, $this->id, 'cl_commission_id', NULL, $cl_company_id, $user_id,
            $this->CompaniesManager, $this->ArraysManager);
    }

    protected function createComponentListgrid()
    {
        $tmpParentData = $this->DataManager->find($this->id);
        //dump($this->settings->platce_dph);
        //die;
        if ($tmpParentData->price_e_type == 1) {
            $tmpProdej = $this->translator->translate("Prodej_s_DPH");
        } else {
            $tmpProdej = $this->translator->translate("Prodej_bez_DPH");
        }


        //29.12.2017 - adaption of names
        $userTmp = $this->UserManager->getUserById($this->getUser()->id);
        $userCompany1 = $this->CompaniesManager->getTable()->where('cl_company.id', $userTmp->cl_company_id)->fetch();
        $userTmpAdapt = json_decode($userCompany1->own_names, true);
        if (!isset($userTmpAdapt['cl_commission_items__description1'])) {
            $userTmpAdapt['cl_commission_items__description1'] = $this->translator->translate("Poznámka_1");

        }
        if (!isset($userTmpAdapt['cl_commission_items__description2'])) {
            $userTmpAdapt['cl_commission_items__description2'] = $this->translator->translate("Poznámka_2");
        }
        $arrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
        $arrInvoiceArrived = $this->invoiceArrivedManager->findAll()->
                        select('CONCAT(rinv_number, " ", cl_partners_book.company, " ", cl_invoice_arrived.inv_title) AS inv_number, cl_invoice_arrived.id AS id')->
                        where(':cl_invoice_arrived_commission.cl_commission_id = ?', $this->id )->
                        order('rinv_number')->fetchPairs('id', 'inv_number');


        if ($this->settings->platce_dph == 1) {
            $arrData = array('cl_pricelist.identification' => array($this->translator->translate('Kód'), 'format' => 'text', 'size' => 10, 'readonly' => TRUE),
                'item_label' => array($this->translator->translate('Popis'), 'format' => 'text', 'size' => 30, 'roCondition' => '$defData["cl_pricelist_id"] != NULL'),
                'quantity' => array($this->translator->translate('Množství'), 'format' => 'number', 'size' => 10, 'decplaces' => $this->settings->des_mj,
                    'roCondition' => '$defData["cl_commission_items_sel_id"] != NULL'),
                'units' => array('', 'format' => 'text', 'size' => 7),
                'price_s' => array($this->translator->translate('Nákup'), 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena),
                'cl_order.od_number' => array($this->translator->translate('Objednávka'), 'format' => "url", 'size' => 12, 'url' => 'order', 'value_url' => 'cl_order_id'),
                'cl_invoice_arrived.rinv_number' => array($this->translator->translate('Faktura_přijatá'), 'format' => "url-select", 'size' => 12, 'values' => $arrInvoiceArrived, 'url' => 'invoicearrived', 'value_url' => 'cl_invoice_arrived_id'),
                'description1' => array($userTmpAdapt['cl_commission_items__description1'], 'format' => "text", 'size' => 50, 'newline' => TRUE),
                'description2' => array($userTmpAdapt['cl_commission_items__description2'], 'format' => "text", 'size' => 50, 'newline' => TRUE),
                'cl_center.name' => array($this->translator->translate('Středisko'), 'format' => "select", 'size' => 10, 'newline' => TRUE, 'values' => $arrCenter),
                'note' => array('', 'format' => "textarea", 'size' => 70, 'rows' => 3, 'newline' => TRUE, 'classMy' => 'form-control input-sm newline description_txt'));
        } else {
            $arrData = array('cl_pricelist.identification' => array($this->translator->translate('Kód'), 'format' => 'text', 'size' => 10, 'readonly' => TRUE),
                'item_label' => array($this->translator->translate('Popis'), 'format' => 'text', 'size' => 30, 'roCondition' => '$defData["cl_pricelist_id"] != NULL'),
                'quantity' => array($this->translator->translate('Množství'), 'format' => 'number', 'size' => 10, 'decplaces' => $this->settings->des_mj,
                    'roCondition' => '$defData["cl_commission_items_sel_id"] != NULL'),
                'units' => array('', 'format' => 'text', 'size' => 7),
                'price_s' => array($this->translator->translate('Nákup'), 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena),
                'cl_order.od_number' => array($this->translator->translate('Objednávka'), 'format' => "url", 'size' => 12, 'url' => 'order', 'value_url' => 'cl_order_id'),
                'cl_invoice_arrived.rinv_number' => array($this->translator->translate('Faktura_přijatá'), 'format' => "url-select", 'size' => 12, 'values' => $arrInvoiceArrived, 'url' => 'invoicearrived', 'value_url' => 'cl_invoice_arrived_id'),
                'description1' => array($userTmpAdapt['cl_commission_items__description1'], 'format' => "text", 'size' => 50, 'newline' => TRUE),
                'description2' => array($userTmpAdapt['cl_commission_items__description2'], 'format' => "text", 'size' => 50, 'newline' => TRUE),
                'cl_center.name' => array($this->translator->translate('Středisko'), 'format' => "select", 'size' => 10, 'newline' => TRUE, 'values' => $arrCenter),
                'note' => array('', 'format' => "textarea", 'size' => 70, 'rows' => 3, 'newline' => TRUE, 'classMy' => 'form-control input-sm newline description_txt'));
        }
        $arrToolbar = array(
            0 => array('url' => $this->link('OrderCostItems!'), 'rightsFor' => 'read', 'data' => array('data-history=false'),
                'label' => $this->translator->translate('Objednat'), 'title' => $this->translator->translate('Objedná doposud neobjednané nákladové položky'), 'class' => 'btn btn-primary', 'icon' => 'iconfa-bell'),
        );

        //$tmpTrans = clone $this->translator->setPrefix([]);
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->CommissionItemsManager,
            $arrData,
            array(),
            $this->id,
            array('units' => $this->settings->def_mj, 'vat' => $this->settings->def_sazba),
            $this->DataManager,
            $this->PriceListManager,
            $this->PriceListPartnerManager,
            TRUE,
            array('pricelist2' => $this->link('RedrawPriceList2!'),
                'activeTab' => 2
            ), //custom links
            TRUE, //movable row
            NULL, //ordercolumn
            FALSE, //selectmode
            array(), //quicksearch
            "", //fontsize
            FALSE, //parentcolumnname
            TRUE //pricelistbottom
        );
        $control->setContainerHeight("auto");
        $control->setEnableSearch('cl_pricelist.identification LIKE ? OR cl_pricelist.item_label LIKE ? OR cl_pricelist.ean_code LIKE ?');
        $control->setToolbar($arrToolbar);
        $control->onChange[] = function () {
            $this->updateSum();

        };
        return $control;
    }

    public function UpdateSum()
    {
        $this->DataManager->updateSum($this->id);
        parent::UpdateSum();

        $this['listgrid']->redrawControl('editLines');
        //$this['sumOnDocs']->redrawControl();
    }

    protected function createComponentListgridSel()
    {
        $tmpParentData = $this->DataManager->find($this->id);
        if ($tmpParentData->price_e_type == 1) {
            $tmpProdej = $this->translator->translate("Prodej_s_DPH");
        } else {
            $tmpProdej = $this->translator->translate("Prodej_bez_DPH");
        }
        //29.12.2017 - adaption of names
        $userTmp = $this->UserManager->getUserById($this->getUser()->id);
        $userCompany1 = $this->CompaniesManager->getTable()->where('cl_company.id', $userTmp->cl_company_id)->fetch();
        $userTmpAdapt = json_decode($userCompany1->own_names, true);
        if (!isset($userTmpAdapt['cl_commission_items__description1'])) {
            $userTmpAdapt['cl_commission_items__description1'] = $this->translator->translate("Poznámka_1");

        }
        if (!isset($userTmpAdapt['cl_commission_items__description2'])) {
            $userTmpAdapt['cl_commission_items__description2'] = $this->translator->translate("Poznámka_2");
        }
        $arrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
        if ($this->settings->platce_dph == 1) {
            $arrData = ['cl_pricelist.identification' => [$this->translator->translate('Kód'), 'format' => 'text', 'size' => 10, 'readonly' => TRUE],
                'item_label' => [$this->translator->translate('Popis'), 'format' => 'text', 'size' => 15, 'roCondition' => '$defData["cl_pricelist_id"] != NULL'],
                'cl_pricelist.quantity' => [$this->translator->translate('Skladem'), 'format' => 'number', 'size' => 10, 'readonly' => TRUE],
                'quantity' => [$this->translator->translate('Množství'), 'format' => 'number', 'size' => 10, 'decplaces' => $this->settings->des_mj],
                'units' => ['', 'format' => 'text', 'size' => 7],
                'price_s' => [$this->translator->translate('Nákup'), 'format' => "number", 'size' => 9, 'decplaces' => $this->settings->des_cena],
                'profit' => [$this->translator->translate('Zisk_%'), 'format' => "number", 'size' => 5, 'decplaces' => 1],
                'price_e' => [$tmpProdej, 'format' => "number", 'size' => 10, 'decplaces' => $this->settings->des_cena],
                'price_e_type' => [$this->translator->translate('Zisk_%'), 'format' => "hidden"],
                'discount' => [$this->translator->translate('Sleva_%'), 'format' => "number", 'size' => 5, 'decplaces' => 1],
                'price_e2' => [$this->translator->translate('Celkem_bez_DPH'), 'format' => "number", 'size' => 12],
                'vat' => [$this->translator->translate('DPH_%'), 'format' => "select", 'values' => $this->RatesVatManager->findAllValid()->fetchPairs('rates', 'rates'), 'size' => 5, 'decplaces' => 0],
                'price_e2_vat' => [$this->translator->translate('Celkem_s_DPH'), 'format' => "number", 'size' => 12],
                'cl_order.od_number' => [$this->translator->translate('Objednávka'), 'format' => "url", 'size' => 12, 'url' => 'order', 'value_url' => 'cl_order_id'],
                'cl_store_docs.doc_number' => [$this->translator->translate('Výdejka'), 'format' => "text", 'size' => 12],
                'cl_invoice.inv_number' => [$this->translator->translate('Faktura'), 'format' => "text", 'size' => 12],
                'cl_invoice_advance.inv_number' => [$this->translator->translate('Záloha'), 'format' => "text", 'size' => 12],
                'quantity_prices__' => [$this->translator->translate('množstevní_ceny'), 'format' => 'hidden-data-values', 'function' => 'getQPrices', 'function_param' => ['cl_pricelist_id', 'cl_commission.cl_currencies_id', 'cl_pricelist.price', 'cl_commission.cl_partners_book_id']],
                'description1' => [$userTmpAdapt['cl_commission_items__description1'], 'format' => "text", 'size' => 50, 'newline' => TRUE],
                'description2' => [$userTmpAdapt['cl_commission_items__description2'], 'format' => "text", 'size' => 50, 'newline' => TRUE],
                'cl_center.name' => [$this->translator->translate('Středisko'), 'format' => "select", 'size' => 10, 'newline' => TRUE, 'values' => $arrCenter],
                'note' => [$this->translator->translate('Poznámka'), 'format' => "textarea", 'size' => 70, 'rows' => 3, 'newline' => TRUE, 'classMy' => 'form-control input-sm newline description_txt']];
        } else {
            $arrData = ['cl_pricelist.identification' => [$this->translator->translate('Kód'), 'format' => 'text', 'size' => 10, 'readonly' => TRUE],
                'item_label' => [$this->translator->translate('Popis'), 'format' => 'text', 'size' => 15, 'roCondition' => '$defData["cl_pricelist_id"] != NULL'],
                'cl_pricelist.quantity' => [$this->translator->translate('Skladem'), 'format' => 'number', 'size' => 10, 'readonly' => TRUE],
                'quantity' => [$this->translator->translate('Množství'), 'format' => 'number', 'size' => 10, 'decplaces' => $this->settings->des_mj],
                'units' => ['', 'format' => 'text', 'size' => 7],
                'price_s' => [$this->translator->translate('Nákup'), 'format' => "number", 'size' => 9, 'decplaces' => $this->settings->des_cena],
                'profit' => [$this->translator->translate('Zisk_%'), 'format' => "number", 'size' => 5, 'decplaces' => 1],
                'price_e' => [$tmpProdej, 'format' => "number", 'size' => 10, 'decplaces' => $this->settings->des_cena],
                'price_e_type' => [$this->translator->translate('Typ_prodejni_ceny'), 'format' => "hidden"],
                'discount' => [$this->translator->translate('Sleva_%'), 'format' => "number", 'size' => 5, 'decplaces' => 1],
                'price_e2' => [$this->translator->translate('Celkem'), 'format' => "number", 'size' => 12],
                'cl_order.od_number' => [$this->translator->translate('Objednávka'), 'format' => "url", 'size' => 12, 'url' => 'order', 'value_url' => 'cl_order_id'],
                'cl_store_docs.doc_number' => [$this->translator->translate('Výdejka'), 'format' => "text", 'size' => 12],
                'cl_invoice.inv_number' => [$this->translator->translate('Faktura'), 'format' => "text", 'size' => 12],
                'cl_invoice_advance.inv_number' => [$this->translator->translate('Záloha'), 'format' => "text", 'size' => 12],
                'quantity_prices__' => [$this->translator->translate('množstevní_ceny'), 'format' => 'hidden-data-values', 'function' => 'getQPrices', 'function_param' => ['cl_pricelist_id', 'cl_commission.cl_currencies_id', 'cl_pricelist.price', 'cl_commission.cl_partners_book_id']],
                'description1' => [$userTmpAdapt['cl_commission_items__description1'], 'format' => "text", 'size' => 50, 'newline' => TRUE],
                'description2' => [$userTmpAdapt['cl_commission_items__description2'], 'format' => "text", 'size' => 50, 'newline' => TRUE],
                'cl_center.name' => [$this->translator->translate('Středisko'), 'format' => "select", 'size' => 10, 'newline' => TRUE, 'values' => $arrCenter],
                'note' => [$this->translator->translate('Poznámka'), 'format' => "textarea", 'size' => 70, 'rows' => 3, 'newline' => TRUE, 'classMy' => 'form-control input-sm newline description_txt']];
        }
        $arrToolbar = [
            0 => ['url' => $this->link('OrderSelItems!'), 'rightsFor' => 'read', 'data' => ['data-history=false'],
                'label' => $this->translator->translate('Objednat'), 'title' => $this->translator->translate('Objedná_doposud_neobjednané_prodejní_položky'), 'class' => 'btn btn-primary', 'icon' => 'iconfa-bell'],
            1 => ['group' =>
                [0 => ['url' => $this->link('SortItems!', ['sortBy' => 'cl_pricelist.identification', 'cmpName' => 'listgridSel']),
                    'rightsFor' => 'write',
                    'label' => $this->translator->translate('Kód_zboží'),
                    'title' => $this->translator->translate('Seřadí_podle_kódu_zboží'),
                    'data' => ['data-ajax="true"', 'data-history="false"'],
                    'class' => 'ajax', 'icon' => ''],
                    1 => ['url' => $this->link('SortItems!', ['sortBy' => 'item_label', 'cmpName' => 'listgridSel']),
                        'rightsFor' => 'write',
                        'label' => $this->translator->translate('Název'),
                        'title' => $this->translator->translate('Seřadí_podle_názvu_položky'),
                        'data' => ['data-ajax="true"', 'data-history="false"'],
                        'class' => 'ajax', 'icon' => ''],
                    2 => ['url' => $this->link('SortItems!', ['sortBy' => 'id', 'cmpName' => 'listgridSel']),
                        'rightsFor' => 'write',
                        'label' => $this->translator->translate('Výchozí_pořadí'),
                        'title' => $this->translator->translate('Seřadí_tak_jak_položky_vznikly'),
                        'data' => ['data-ajax="true"', 'data-history="false"'],
                        'class' => 'ajax', 'icon' => ''],
                ],
                'group_settings' =>
                    ['group_label' => $this->translator->translate('Seřadit'),
                        'group_class' => 'btn btn-primary dropdown-toggle btn-sm',
                        'group_title' => $this->translator->translate('seřadit'), 'group_icon' => 'iconfa-sort']
            ]
        ];

        $control = new Controls\ListgridControl(
            $this->translator,
            $this->CommissionItemsSelManager,
            $arrData,
            [],
            $this->id,
            ['units' => $this->settings->def_mj, 'vat' => $this->settings->def_sazba],
            $this->DataManager,
            $this->PriceListManager,
            $this->PriceListPartnerManager,
            TRUE,
            ['pricelist2' => $this->link('RedrawPriceList2!'),
                'activeTab' => 2
            ], //custom links
            TRUE, //movable row
            NULL, //ordercolumn
            FALSE, //selectmode
            [], //quicksearch
            "", //fontsize
            FALSE, //parentcolumnname
            TRUE //pricelistbottom
        );
        $control->setContainerHeight("auto");
        $control->setEnableSearch('cl_pricelist.identification LIKE ? OR cl_pricelist.item_label LIKE ? OR cl_pricelist.ean_code LIKE ?');


        // //$this->translator->setPrefix(['applicationModule.Commission']);
        $control->setToolbar($arrToolbar);


        $control->onChange[] = function () {
            $this->updateSum();

        };
        return $control;
    }

    public function handleOrderSelItems()
    {
        $arrRet = $this->OrderSelItemsMain2();
        if ($arrRet[0] > 0) {
            $this->flashMessage($this->translator->translate('Objednávka_prodejních_položek_byla_vygenerována'), 'success');
            //20.02.2020 - next line is important for change url in address line of browser
            $this->payload->allowAjax = false;
            $this->redirect('Order:edit', array('id' => $arrRet[1]));
        } else {
            $this->flashMessage($this->translator->translate('Objednávka_prodejních_položek_nebyla_vygenerována'), 'danger');
            $this->redrawControl('content');
        }

    }

    public function handleOrderCostItems()
    {
        $arrRet = $this->OrderCostItemsMain2();
        if ($arrRet[0] > 0) {
            $this->flashMessage($this->translator->translate('Objednávka_nákladových_položek_byla_vygenerována'), 'success');
            //20.02.2020 - next line is important for change url in address line of browser
            $this->payload->allowAjax = false;
            $this->redirect('Order:edit', array('id' => $arrRet[1]));
        } else {
            $this->flashMessage($this->translator->translate('Objednávka_nákladových_položek_nebyla_vygenerována'), 'danger');
            $this->redrawControl('content');
        }

    }

    protected function createComponentListgridProduction()
    {
        $tmpParentData = $this->DataManager->find($this->id);
        $arrItems =  $this->CommissionItemsSelManager->findAll()->where('cl_commission_id = ?', $this->id)
                                    ->select('cl_commission_items_sel.id,CONCAT(cl_pricelist.identification," - ",cl_pricelist.item_label, ? , cl_commission_items_sel.quantity) AS item', ' vyrobit: ' )
                                    ->order('cl_commission_items_sel.id')->fetchPairs('id', 'item');
        $arrData = ['cl_commission_items_sel.cl_pricelist.identification' => [$this->translator->translate('Kód'), 'format' => 'chzn-select-req', 'size' => 10,
                            'values' => $arrItems,  'roCondition' => '$defData["cl_store_docs_id"] != NULL', 'required' => $this->translator->translate('Musí_být_vybrán_výrobek'),
                            'valuesFunction' => '$valuesToFill = $this->presenter->getItemsSel($defData1["id"]);',],
                'cl_commission_items_sel.item_label' => [$this->translator->translate('Popis'), 'format' => 'text', 'size' => 15, 'readonly' => TRUE],
                'cl_commission_items_sel.cl_pricelist.unit' => ['Jednotky', 'format' => 'text', 'size' => 7, 'readonly' => TRUE],
                'cl_commission_items_sel.cl_pricelist.quantity' => [$this->translator->translate('Skladem'), 'format' => 'number', 'size' => 10, 'readonly' => TRUE],
                'quantity_prod' => [$this->translator->translate('Vyrobeno'), 'format' => 'number', 'size' => 10, 'decplaces' => $this->settings->des_mj, 'roCondition' => '$defData["cl_store_docs_id"] != NULL'],
                'quantity_left_function_' => [$this->translator->translate('Zbývá_vyrobit'), 'format' => 'number', 'size' => 10, 'decplaces' => $this->settings->des_mj,
                                    'function' => 'getQuantProduction', 'function_param' => ['cl_commission_items_sel_id', 'quantity_prod'], 'readonly' => TRUE],
                'units_2_function_' => ['', 'format' => 'text', 'size' => 7, 'readonly' => TRUE],
                'order_number' => [$this->translator->translate('Objednávka'), 'format' => 'text', 'size' => 12],
                'cl_store_docs.doc_number' => [$this->translator->translate('Výdejka'), 'format' => 'url', 'size' => 12, 'url' => 'storeout', 'value_url' => 'cl_store_docs_id', 'readonly' => TRUE],
                'cl_store_docs.cl_delivery_note.dn_number' => [$this->translator->translate('Dodací_list'), 'format' => 'url', 'size' => 12, 'url' => 'deliverynote', 'value_url' => 'cl_store_docs.cl_delivery_note_id', 'readonly' => TRUE],
                'arrTools' => ['tools', [1 => ['url' => 'customFunction!', 'type' => 'giveOutItem', 'rightsFor' => 'enable', 'label' => 'vydat', 'class' => 'btn btn-success btn-xs', 'title' => 'vydá_vyrobené_množství_ze_skladu',
                    'showCondition' => ['left' => 'quantity_prod', 'condition' => '!=', 'right' => 0] ,
                    'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'iconfa-bell'],
                            ]
                            ],
                ];

        $arrToolbar = [
            0 => ['url' => $this->link('GiveOutProduction!'), 'rightsFor' => 'read', 'data' => ['data-history=false'],
                'label' => $this->translator->translate('Vydat_vše'), 'title' => $this->translator->translate('Vydá_doposud_nevydané_položky'), 'class' => 'btn btn-primary', 'icon' => 'iconfa-bell'],
        ];

        $control = new Controls\ListgridControl(
            $this->translator,
            $this->CommissionItemsProductionManager,
            $arrData,
            [], //condition rows
            $this->id,
            ['vat' => $this->settings->def_sazba],
            $this->DataManager,
            FALSE,
            FALSE,
            TRUE,
            [
            ], //custom links
            FALSE, //movable row
            NULL, //ordercolumn
            FALSE, //selectmode
            [], //quicksearch
            '', //fontsize
            FALSE, //parentcolumnname
            FALSE //pricelistbottom
        );
        $control->setContainerHeight("auto");
        $control->setEnableSearch('cl_pricelist.identification LIKE ? OR cl_pricelist.item_label LIKE ? OR cl_pricelist.ean_code LIKE ?');


        // //$this->translator->setPrefix(['applicationModule.Commission']);
        $control->setToolbar($arrToolbar);

        $control->onCustomFunction[] = function ($itemId, $type)
        {
            if ($type == 'giveOutItem') {
                $arrRet = $this->giveOutItem($itemId);

                if (self::hasError($arrRet)) {
                    $this->flashMessage($arrRet['error'], 'error');
                }else{
                    $this->flashMessage($arrRet['success'], 'success');
                }
            }
            $this['listgridProduction']->redrawControl('paginator');
            $this['listgridProduction']->redrawControl('editLines');
        };

        $control->onChange[] = function () {
            $this->updateProduction();
            $this->updateSum();
        };
        return $control;
    }

    public function getItemsSel($rowId)
    {
        $tmpRow = $this->CommissionItemsProductionManager->find($rowId);
        $retVal = [];
        if ($tmpRow){
            bdump($tmpRow);
            if ($tmpRow['cl_store_docs_id'] == null  || $tmpRow['cl_commission_items_sel_id'] == null){
                $retVal = $this->CommissionItemsSelManager->findAll()->where('cl_commission_id = ?', $this->id)
                    ->select('cl_commission_items_sel.id,CONCAT(cl_pricelist.identification," - ",cl_pricelist.item_label, ? , cl_commission_items_sel.quantity) AS item', ' vyrobit: ' )
                    ->order('cl_commission_items_sel.id')->fetchPairs('id', 'item');
            }else{
                $retVal = $this->CommissionItemsSelManager->findAll()->where('cl_commission_id = ? AND cl_commission_items_sel.id = ?', $this->id, $tmpRow['cl_commission_items_sel_id'])
                    ->select('cl_commission_items_sel.id,CONCAT(cl_pricelist.identification," - ",cl_pricelist.item_label, ? , cl_commission_items_sel.quantity) AS item', ' vyrobit: ' )
                    ->order('cl_commission_items_sel.id')->fetchPairs('id', 'item');
            }
        }

        return $retVal;
    }

    public function updateProduction(){
        //12.03.2022 - update cl_pricelist_id on production items. It's needed for give out production items from store
        $tmpProduction = $this->CommissionItemsProductionManager->findAll()->where('cl_commission_id = ?', $this->id);
        foreach($tmpProduction as $key => $one){
            $one->update(['cl_pricelist_id' => $one->cl_commission_items_sel['cl_pricelist_id']]);
        }
    }

    public function getQuantProduction($function_param){
        if ($tmpItem = $this->CommissionItemsSelManager->find($function_param['cl_commission_items_sel_id'])) {
            $tmpSumProd = $this->CommissionItemsProductionManager->findAll()->
                            where('cl_commission_id = ? AND
                                            cl_commission_items_sel_id = ?', $tmpItem['cl_commission_id'], $function_param['cl_commission_items_sel_id'])->
                            sum('quantity_prod');
            $retVal = $tmpItem['quantity'] - $tmpSumProd;
        }else {
            $retVal = 0;
        }

        return $retVal;
    }

    public function handleGiveOutProduction(){
        $arrDataItemsProduction = $this->CommissionItemsProductionManager->findAll()->where('cl_commission_id = ? AND quantity_prod > 0 AND cl_store_docs_id IS NULL', $this->id)->fetchPairs('id','id');
        $docId = $this->DataManager->createOut($this->id, [], [], $arrDataItemsProduction);
        if (!is_null($docId)) {
            $this->flashMessage($this->translator->translate('Výdejka_byla_vytvořena'), 'success');
        }
        $this['listgridProduction']->redrawControl('paginator');
        $this['listgridProduction']->redrawControl('editLines');
    }

    public function giveOutItem($itemId){
        $arrDataItemsProduction = $this->CommissionItemsProductionManager->findAll()->where('id = ? AND cl_store_docs_id IS NULL', $itemId)->fetchPairs('id','id');
        if (count($arrDataItemsProduction) > 0){
            $docId = $this->DataManager->createOut($this->id, [], [], $arrDataItemsProduction);
            if (!is_null($docId)) {
                $arrResp = ['success' => $this->translator->translate('Výdejka_byla_vytvořena')];
            }else{
                $arrResp = ['error' => $this->translator->translate('Výdejka_nebyla_vytvořena')];
            }
        }else{
            $arrResp = ['error' => $this->translator->translate('Výdejka_již_existuje')];
        }
        return $arrResp;
    }



    protected function createComponentListgridWork()
    {
        $arrUsers = [];
        //$this->translator->setPrefix(['applicationModule.Commission']);
        $arrWorkplaces = $this->WorkplacesManager->findAll()->where('disabled = 0')->order('workplace_name')->fetchPairs('id', 'workplace_name');
        $arrUsers['Aktivní'] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 0')->fetchPairs('id', 'name');
        $arrUsers['Neaktivní'] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 1')->fetchPairs('id', 'name');
        $arrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
        $arrData = ['work_label' => [$this->translator->translate('Popis'), 'format' => 'text', 'size' => 30],
            'work_date_s' => [$this->translator->translate('Začátek'), 'format' => 'datetime2', 'size' => 15],
            'work_date_e' => [$this->translator->translate('Konec'), 'format' => 'datetime2', 'size' => 15],
            'work_time' => [$this->translator->translate('Hodin'), 'format' => 'number', 'size' => 10],
            'cl_workplaces.workplace_name' => [$this->translator->translate('Pracoviště'), 'format' => 'select',
                'values' => $arrWorkplaces,
                'size' => 10],
            'cl_users.name' => [$this->translator->translate('Pracovník'), 'format' => 'select',
                'values' => $arrUsers,
                'size' => 15],
            'work_rate' => [$this->translator->translate('Sazba'), 'format' => 'currency', 'size' => 10],
            'profit' => [$this->translator->translate('Zisk_%'), 'format' => 'integer', 'size' => 10],
            'qty_ok' => [$this->translator->translate('Dobré'), 'format' => 'integer', 'size' => 10],
            'qty_nok' => [$this->translator->translate('Zmetky'), 'format' => 'integer', 'size' => 10],
            'qty_total' => [$this->translator->translate('Celkem'), 'format' => 'integer', 'size' => 10],
            'cl_center.name' => [$this->translator->translate('Středisko'), 'format' => 'select', 'size' => 10, 'values' => $arrCenter],
            'note' => [$this->translator->translate('Poznámka'), 'format' => "textarea", 'size' => 50, 'rows' => 3, 'newline' => TRUE],
            'arrTools' => ['tools', [1 => ['url' => 'report!', 'rightsFor' => 'enable', 'label' => 'PDF', 'class' => 'btn btn-success btn-xs',
                    'data' => ['data-ajax="false"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-print'],
            ]],
        ];

        $tmpMain = $this->DataManager->find($this->id);
        if ($tmpMain)
            $defCenterId = $tmpMain['cl_center_id'];
        else
            $defCenterId = NULL;

        $now = new \Nette\Utils\DateTime;
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->CommissionWorkManager,
            $arrData,
            [],
            $this->id,
            ['work_date_s' => $now->format('Y.m.d'), 'work_date_e' => $now->format('Y.m.d'),
                'work_time_s' => $now->format('H:i'), 'work_time_e' => $now->format('H:i'), 'cl_center_id' => $defCenterId],
            $this->DataManager,
            FALSE,
            FALSE,
            TRUE,
            ['activeTab' => 3], //custom links
            TRUE, //movable row
            NULL, //ordercolumn
            FALSE, //selectmode
            [], //quicksearch
            "", //fontsize
            FALSE, //parentcolumnname
            TRUE //pricelistbottom
        );
        $control->setContainerHeight("auto");
        $control->onChange[] = function () {
            $this->updateSum();

        };
        $control->onPrint[] = function ($itemId) {
            $this->reportTask($itemId);
        };

        return $control;

    }

    protected function createComponentListgridTask()
    {

        $arrWorkplaces = $this->WorkplacesManager->findAll()->where('disabled = 0')->order('workplace_name')->fetchPairs('id', 'workplace_name');
        $arrUsers = [];
        $arrUsers[$this->translator->translate('Aktivní')] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 0')->fetchPairs('id', 'name');
        $arrUsers[$this->translator->translate('Neaktivní')] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 1')->fetchPairs('id', 'name');
        $arrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
        $arrData = [
            'name' => [$this->translator->translate('Popis'), 'format' => 'text', 'size' => 25],
            'work_date_s' => [$this->translator->translate('Začátek'), 'format' => 'datetime2', 'size' => 12],
            'work_date_e' => [$this->translator->translate('Konec'), 'format' => 'datetime2', 'size' => 12],
            'work_time' => [$this->translator->translate('Hodin'), 'format' => 'number', 'size' => 10, 'roCondition' => '$defData["sub_count"] >= 1'],
            'qty_norm' => [$this->translator->translate('Norma_ks/hod'), 'format' => 'number', 'size' => 10],
            'cl_workplaces.workplace_name' => [$this->translator->translate('Pracoviště'), 'format' => 'select',
                'values' => $arrWorkplaces,
                'size' => 10],
            'cl_users.name' => [$this->translator->translate('Pracovník'), 'format' => 'select',
                'values' => $arrUsers,
                'size' => 10],
            'work_rate' => [$this->translator->translate('Sazba'), 'format' => 'number', 'size' => 10],
            'done' => [$this->translator->translate('Hotovo'), 'format' => 'boolean', 'size' => 8],
            'cl_center.name' => [$this->translator->translate('Středisko'), 'format' => "select", 'size' => 10, 'values' => $arrCenter],
            'note' => [$this->translator->translate('Poznámka'), 'format' => "textarea", 'size' => 70, 'rows' => 3, 'newline' => TRUE],
            'arrTools' => ['tools', [1 => ['url' => 'newSubTask!', 'rightsFor' => 'enable', 'label' => 'podúkol', 'class' => 'btn btn-success btn-xs',
                'showCondition' => ['left' => 'main_task', 'right' => 1],
                'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-plus'],
                2 => ['url' => 'report!', 'rightsFor' => 'enable', 'label' => 'PDF', 'class' => 'btn btn-success btn-xs',
                    'data' => ['data-ajax="false"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-print'],
            ]
            ],
        ];


    //    bdump($defCenterId);

        $now = new \Nette\Utils\DateTime;
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->CommissionTaskManager,
            $arrData,
            [],
            $this->id,
            ['work_date_s' => $now->format('Y.m.d H:i'), 'work_date_e' => $now->format('Y.m.d H:i')],
            $this->DataManager,
            FALSE,
            FALSE,
            TRUE,
            ['activeTab' => 3], //custom links
            TRUE, //movable row
            NULL, //ordercolumn
            FALSE, //selectmode
            [], //quicksearch
            "", //fontsize
            FALSE, //parentcolumnname
            TRUE //pricelistbottom
        );
        $control->setContainerHeight("auto");
        $control->setFilter(['filter' => 'main_task = 1']);
        $control->setChildRelation('cl_commission_task.cl_commission_task_id');
        $control->setColoursCondition(
            [1 => ['conditions' => [1 => ['left' => 'sub_count', 'condition' => '>=', 'right' => '1',
                'lfunc' => ['name' => 'round', 'param' => 0],
                'rfunc' => ['name' => 'round', 'param' => 0]]
            ],
                'colour' => $this->RGBtoHex(167, 200, 249)], //blue
            ]);

        $control->onChange[] = function () {
            $this->updateSum();
        };
        $control->onPrint[] = function ($itemId) {
            $this->reportTask($itemId);
        };
        $control->onNewSubTask[] = function ($itemId) {
            $this->newSubTask($itemId);
        };
        return $control;

    }

    public function newSubTask($itemId)
    {
        if ($this->isAllowed($this->name, 'write') || $this->isAllowed($this->name, 'edit')) {
            if ($tmpData = $this->CommissionTaskManager->findAll()->where('id = ?', $itemId)->fetch()) {
                $now = new DateTime();
                $itemOrder = $this->CommissionTaskManager->findAll()->where('cl_commission_task_id = ?', $itemId)->max('item_order_sub');
                $arrData = [];
                $arrData['cl_company_id'] = $tmpData['cl_company_id'];
                $arrData['cl_commission_task_id'] = $tmpData['id'];
                //$arrData['work_date_s']             = $now;
                $arrData['main_task'] = 0;
                $arrData['cl_center_id'] = $tmpData['cl_center_id'];
                $arrData['cl_workplaces_id'] = $tmpData['cl_workplaces_id'];
                $arrData['item_order'] = $tmpData['item_order'];
                $arrData['item_order_sub'] = $itemOrder + 1;
                $newLine = $this->CommissionTaskManager->insert($arrData);
                $this['listgridTask']->setEditIdLine($newLine->id);
                //15.9.2019 -erase potencial search
                $this['listgridTask']->setTxtSearch("");
                $this['listgridTask']->setNewLine(TRUE);
                //$this->txtSearch = "";
                //$this->newLine = TRUE;
                $sub_count = $this->CommissionTaskManager->findAll()->where('cl_commission_task_id = ?', $itemId)->count('id');
                $tmpData->update(['sub_count' => $sub_count]);
            }
        } else {
            $this->flashMessage($this->translator->translate('Ke_zvolené_akci_nemáte_oprávnění!'), 'danger');
        }
        $this['listgridTask']->redrawControl('paginator');
        $this['listgridTask']->redrawControl('editLines');
    }


    public function reportTask($itemId)
    {
        //bdump($itemId,'itemid');
        $data = $this->DataManager->find($this->id);
        //$dataOther = $this->CommissionTaskManager->find($itemId);
        $dataOther['task_id'] = $itemId;
        if ($this->settings['ico'] == '25847163') //PRECISTEC
            $template = $this->createMyTemplateWS($data, $this->ReportManager->getReport(__DIR__ . '/../templates/Commission/rptTaskProductionSheet.latte'), $dataOther);
        else
            $template = $this->createMyTemplateWS($data, $this->ReportManager->getReport(__DIR__ . '/../templates/Commission/rptOneTask.latte'), $dataOther);

        $this->pdfCreate($template, $data['cm_number'] . $this->translator->translate('Úkolový_list'));
    }

    protected function createComponentListgridWorksSelect()
    {

        $arrUsers = [];
        $arrUsers['Aktivní'] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 0')->fetchPairs('id', 'name');
        $arrUsers['Neaktivní'] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 1')->fetchPairs('id', 'name');

        $arrData = ['work_label' => [$this->translator->translate('Popis'), 'format' => 'text', 'size' => 30],
            'work_date_s' => [$this->translator->translate('Začátek'), 'format' => 'datetime2', 'size' => 15],
            'work_date_e' => [$this->translator->translate('Konec'), 'format' => 'datetime2', 'size' => 15],
            'work_time' => [$this->translator->translate('Hodin'), 'format' => 'number', 'size' => 10],
            'cl_users.name' => [$this->translator->translate('Pracovník'), 'format' => 'text',
                'values' => $arrUsers,
                'size' => 15],
            'work_rate' => [$this->translator->translate('Sazba'), 'format' => 'currency', 'size' => 10],
            'profit' => [$this->translator->translate('Zisk_%'), 'format' => "number", 'size' => 10],
            'note' => [$this->translator->translate('Poznámka'), 'format' => "textarea", 'size' => 50, 'rows' => 3, 'newline' => TRUE],
        ];
        $now = new \Nette\Utils\DateTime;
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->CommissionWorkManager,
            $arrData,
            [],
            $this->id,
            ['work_date_s' => $now->format('Y.m.d'), 'work_date_e' => $now->format('Y.m.d'),
                'work_time_s' => $now->format('H:i'), 'work_time_e' => $now->format('H:i')],
            $this->DataManager,
            NULL, //pricelist manager
            FALSE,
            FALSE, //add empty row
            [], //custom links
            FALSE, //movable row
            NULL, //ordercolumn
            TRUE, //selectmode
            [], //quicksearch
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE //pricelistbottom
        );
        $control->setPaginatorOff();
        $control->setContainerHeight("auto");

        return $control;

    }

    protected function createComponentListgridItemsSelSelect()
    {
        $tmpParentData = $this->DataManager->find($this->id);
        if ($tmpParentData && $tmpParentData->price_e_type == 1) {
            $tmpProdej = $this->translator->translate("Prodej_s_DPH");
        } else {
            $tmpProdej = $this->translator->translate("Prodej_bez_DPH");
        }
        //29.12.2017 - adaption of names
        $userTmp = $this->UserManager->getUserById($this->getUser()->id);
        $userCompany1 = $this->CompaniesManager->getTable()->where('cl_company.id', $userTmp->cl_company_id)->fetch();
        $userTmpAdapt = json_decode($userCompany1->own_names, true);
        if (!isset($userTmpAdapt['cl_commission_items__description1'])) {
            $userTmpAdapt['cl_commission_items__description1'] = $this->translator->translate("Poznámka_1");

        }
        if (!isset($userTmpAdapt['cl_commission_items__description2'])) {
            $userTmpAdapt['cl_commission_items__description2'] = $this->translator->translate("Poznámka_2");
        }
        if ($this->settings->platce_dph == 1) {
            $arrData = array('cl_pricelist.identification' => array($this->translator->translate('Kód'), 'format' => 'text', 'size' => 10, 'readonly' => TRUE),
                'item_label' => array($this->translator->translate('Popis'), 'format' => 'text', 'size' => 15, 'roCondition' => '$defData["cl_pricelist_id"] != NULL'),
                'quantity' => array($this->translator->translate('Množství'), 'format' => 'number', 'size' => 10, 'decplaces' => $this->settings->des_mj),
                'units' => array('', 'format' => 'text', 'size' => 7),
                'price_s' => array($this->translator->translate('Nákup'), 'format' => "number", 'size' => 7, 'decplaces' => $this->settings->des_cena),
                'profit' => array($this->translator->translate('Zisk_%'), 'format' => "number", 'size' => 5),
                'price_e' => array($tmpProdej, 'format' => "number", 'size' => 10, 'decplaces' => $this->settings->des_cena),
                'price_e_type' => array($this->translator->translate('Typ prodejni ceny'), 'format' => "hidden"),
                'discount' => array($this->translator->translate('Sleva_%'), 'format' => "number", 'size' => 5),
                'price_e2' => array($this->translator->translate('Celkem_bez_DPH'), 'format' => "number", 'size' => 12),
                'cl_store_docs.doc_number' => array($this->translator->translate('Výdejka'), 'format' => "text", 'size' => 12),
                'cl_invoice.inv_number' => array($this->translator->translate('Faktura'), 'format' => "text", 'size' => 12),
                'description1' => array($userTmpAdapt['cl_commission_items__description1'], 'format' => "text", 'size' => 50, 'newline' => TRUE),
                'description2' => array($userTmpAdapt['cl_commission_items__description2'], 'format' => "text", 'size' => 50, 'newline' => TRUE),
                'note' => array($this->translator->translate('Poznámka'), 'format' => "textarea", 'size' => 70, 'rows' => 3, 'newline' => TRUE));
        } else {
            $arrData = array('cl_pricelist.identification' => array($this->translator->translate('Kód'), 'format' => 'text', 'size' => 10, 'readonly' => TRUE),
                'item_label' => array($this->translator->translate('Popis'), 'format' => 'text', 'size' => 15, 'roCondition' => '$defData["cl_pricelist_id"] != NULL'),
                'quantity' => array($this->translator->translate('Množství'), 'format' => 'number', 'size' => 10, 'decplaces' => $this->settings->des_mj),
                'units' => array('', 'format' => 'text', 'size' => 7),
                'price_s' => array($this->translator->translate('Nákup'), 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena),
                'profit' => array($this->translator->translate('Zisk_%'), 'format' => "number", 'size' => 5),
                'price_e' => array($tmpProdej, 'format' => "number", 'size' => 10, 'decplaces' => $this->settings->des_cena),
                'price_e_type' => array($this->translator->translate('Typ prodejni ceny'), 'format' => "hidden"),
                'discount' => array($this->translator->translate('Sleva_%'), 'format' => "number", 'size' => 5),
                'price_e2' => array($this->translator->translate('Celkem'), 'format' => "number", 'size' => 12),
                'cl_store_docs.doc_number' => array($this->translator->translate('Výdejka'), 'format' => "text", 'size' => 12),
                'cl_invoice.inv_number' => array($this->translator->translate('Faktura'), 'format' => "text", 'size' => 12),
                'description1' => array($userTmpAdapt['cl_commission_items__description1'], 'format' => "text", 'size' => 50, 'newline' => TRUE),
                'description2' => array($userTmpAdapt['cl_commission_items__description2'], 'format' => "text", 'size' => 50, 'newline' => TRUE),
                'note' => array($this->translator->translate('Poznámka'), 'format' => "textarea", 'size' => 70, 'rows' => 3, 'newline' => TRUE));
        }
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->CommissionItemsSelManager,
            $arrData,
            array(),
            $this->id,
            array('units' => $this->settings->def_mj, 'vat' => $this->settings->def_sazba),
            $this->DataManager,
            NULL, //pricelist manager
            $this->PriceListPartnerManager,
            FALSE, //add emtpy row
            array('pricelist2' => $this->link('RedrawPriceList2!'),
                'activeTab' => 2
            ), //custom links,
            FALSE, //movable row
            NULL, //ordercolumn
            TRUE, //selectmode
            array(), //quicksearch
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE //pricelistbottom
        );
        $control->showHistory(FALSE);
        $control->setHideTimestamps(TRUE);
        $control->setPaginatorOff();
        $control->setContainerHeight("auto");
        return $control;
    }

    protected function createComponentListgridItemsSelect()
    {
        $tmpParentData = $this->DataManager->find($this->id);
        if ($tmpParentData && $tmpParentData->price_e_type == 1) {
            $tmpProdej = $this->translator->translate("Prodej_s_DPH");
        } else {
            $tmpProdej = $this->translator->translate("Prodej_bez_DPH");
        }
        //29.12.2017 - adaption of names
        $userTmp = $this->UserManager->getUserById($this->getUser()->id);
        $userCompany1 = $this->CompaniesManager->getTable()->where('cl_company.id', $userTmp->cl_company_id)->fetch();
        $userTmpAdapt = json_decode($userCompany1->own_names, true);
        if (!isset($userTmpAdapt['cl_commission_items__description1'])) {
            $userTmpAdapt['cl_commission_items__description1'] = $this->translator->translate("Poznámka_1");

        }
        if (!isset($userTmpAdapt['cl_commission_items__description2'])) {
            $userTmpAdapt['cl_commission_items__description2'] = $this->translator->translate("Poznámka_2");
        }
        if ($this->settings->platce_dph == 1) {
            $arrData = array('item_label' => array($this->translator->translate('Popis'), 'format' => 'text', 'size' => 30, 'roCondition' => '$defData["cl_pricelist_id"] != NULL'),
                'quantity' => array($this->translator->translate('Množství'), 'format' => 'number', 'size' => 10, 'decplaces' => $this->settings->des_mj,
                    'roCondition' => '$defData["cl_commission_items_sel_id"] != NULL'),
                'units' => array('', 'format' => 'text', 'size' => 7),
                'price_s' => array($this->translator->translate('Nákup'), 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena),
                'cl_store_docs.doc_number' => array($this->translator->translate('Výdejka'), 'format' => "text", 'size' => 12),
                'description1' => array($userTmpAdapt['cl_commission_items__description1'], 'format' => "text", 'size' => 50, 'newline' => TRUE),
                'description2' => array($userTmpAdapt['cl_commission_items__description2'], 'format' => "text", 'size' => 50, 'newline' => TRUE),
                'note' => array($this->translator->translate('Poznámka'), 'format' => "textarea", 'size' => 70, 'rows' => 3, 'newline' => TRUE));
        } else {
            $arrData = array('item_label' => array('Popis', 'format' => 'text', 'size' => 30, 'roCondition' => '$defData["cl_pricelist_id"] != NULL'),
                'quantity' => array($this->translator->translate('Množství'), 'format' => 'number', 'size' => 10, 'decplaces' => $this->settings->des_mj,
                    'roCondition' => '$defData["cl_commission_items_sel_id"] != NULL'),
                'units' => array('', 'format' => 'text', 'size' => 7),
                'price_s' => array($this->translator->translate('Nákup'), 'format' => "number", 'size' => 8, 'decplaces' => $this->settings->des_cena),
                'cl_store_docs.doc_number' => array($this->translator->translate('Výdejka'), 'format' => "text", 'size' => 12),
                'description1' => array($userTmpAdapt['cl_commission_items__description1'], 'format' => "text", 'size' => 50, 'newline' => TRUE),
                'description2' => array($userTmpAdapt['cl_commission_items__description2'], 'format' => "text", 'size' => 50, 'newline' => TRUE),
                'note' => array($this->translator->translate('Poznámka'), 'format' => "textarea", 'size' => 70, 'rows' => 3, 'newline' => TRUE));
        }
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->CommissionItemsManager,
            $arrData,
            array(),
            $this->id,
            array('units' => $this->settings->def_mj, 'vat' => $this->settings->def_sazba),
            $this->DataManager,
            NULL, //pricelist manager
            $this->PriceListPartnerManager,
            FALSE, //add emtpy row
            array('pricelist2' => $this->link('RedrawPriceList2!'),
                'activeTab' => 2
            ), //custom links,
            FALSE, //movable row
            NULL, //ordercolumn
            TRUE, //selectmode
            array(), //quicksearch
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE //pricelistbottom
        );
        $control->setPaginatorOff();
        $control->showHistory(FALSE);
        $control->setHideTimestamps(TRUE);
        $control->setContainerHeight("auto");
        return $control;
    }

    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.Commission']);
        $this->formName = $this->translator->translate("Zakázky");
        $this->mainTableName = 'cl_commission';
        //$settings = $this->CompaniesManager->getTable()->fetch();
        if ($this->settings->platce_dph == 1) {
            $arrData = ['cm_number' => $this->translator->translate('Číslo_zakázky'),
                'locked' => [$this->translator->translate('Zamčeno'), 'format' => 'boolean', 'size' => 5],
                'cl_partners_book.company' => [$this->translator->translate('Klient'), 'format' => 'text', 'show_clink' => true],
                'cl_partners_branch.b_name' => $this->translator->translate('Pobočka'),
                'cl_status.status_name' => [$this->translator->translate('Stav'), 'format' => 'colortag', 'size' => 5],
                'cl_center.name' => [$this->translator->translate('Středisko'), 'format' => 'text'],
                'price_pe2_base' => [$this->translator->translate('Smluvní_cena_bez_DPH'), 'format' => 'currency'],
                'price_pe2_vat' => [$this->translator->translate('Smluvní_cena_s_DPH'), 'format' => 'currency'],
                'price_e2_base' => [$this->translator->translate('Vypočtená_cena_bez_DPH'), 'format' => 'currency'],
                'price_e2_vat' => [$this->translator->translate('Vypočtená_cena_s_DPH'), 'format' => 'currency'],
                'cl_currencies.currency_name' => $this->translator->translate('Měna'),
                'currency_rate' => $this->translator->translate('Kurz'),
                'work_hours__' => [$this->translator->translate('Hodiny'), 'size' => 5, 'format' => 'number',  'function' => 'getWorkHours',  'function_param' => ['id']],
                'cm_date' => [$this->translator->translate('Datum_přijetí'), 'format' => 'date'],
                'start_date' => [$this->translator->translate('Začátek_práce'), 'format' => 'date'],
                'req_date' => [$this->translator->translate('Požadované_dodání'), 'format' => 'date'],
                'delivery_date' => [$this->translator->translate('Skutečné_dodání'), 'format' => 'date'],
                'cl_transport_types.name' => [$this->translator->translate('Doprava'), 'format' => 'text'],
                'cl_payment_types.name' => [$this->translator->translate('Platba'), 'format' => 'text'],
                's_eml' => ['E-mail', 'format' => 'boolean'],
                'cm_title' => [$this->translator->translate('Popis'), 'format' => 'textoneline'],
                'cl_eshops.name' => [$this->translator->translate('Eshop'), 'format' => 'text'],
                'expedition_ok' => ['Expedice ok', 'format' => 'boolean'],
                'inv_number' => [$this->translator->translate('Faktura'), 'format' => 'text'],
                'cl_invoice.inv_number' => [$this->translator->translate('Spárovaná_faktura'), 'format' => 'text'],
                'cl_invoice_advance.inv_number' => [$this->translator->translate('Spárovaná_záloha'), 'format' => 'text'],
                'cl_store_docs.doc_number' => [$this->translator->translate('Výdejka'), 'format' => 'text'],
                'profit' => [$this->translator->translate('Zisk_%'), 'format' => 'number'],
                'profit_abs' => [$this->translator->translate('Zisk'), 'format' => 'currency'],
                'cm_order' => $this->translator->translate('Objednávka'),
                'cl_users.name' => $this->translator->translate('Obchodník'),
                'created' => [$this->translator->translate('Vytvořeno'), 'format' => 'datetime'], 'create_by' => $this->translator->translate('Vytvořil'), 'changed' => [$this->translator->translate('Změněno'), 'format' => 'datetime'], 'change_by' => $this->translator->translate('Změnil')];
        } else {
            $arrData = ['cm_number' => $this->translator->translate('Číslo_zakázky'),
                'locked' => [$this->translator->translate('Zamčeno'), 'format' => 'boolean'],
                'cl_partners_book.company' => [$this->translator->translate('Klient'), 'format' => 'text', 'show_clink' => true],
                'cl_partners_branch.b_name' => $this->translator->translate('Pobočka'),
                'cl_status.status_name' => [$this->translator->translate('Stav'), 'format' => 'colortag'],
                'cl_center.name' => [$this->translator->translate('Středisko'), 'format' => 'text'],
                'price_pe2_base' => [$this->translator->translate('Smluvní_cena'), 'format' => 'currency'],
                'price_e2_base' => [$this->translator->translate('Vypočtená_cena'), 'format' => 'currency'],
                'cl_currencies.currency_name' => $this->translator->translate('Měna'),
                'currency_rate' => $this->translator->translate('Kurz'),
                'work_hours__' => [$this->translator->translate('Hodiny'), 'size' => 5, 'format' => 'number',  'function' => 'getWorkHours',  'function_param' => ['id']],
                'cl_eshops.name' => [$this->translator->translate('Eshop'), 'format' => 'text'],
                'cm_title' => [$this->translator->translate('Popis'), 'format' => 'textoneline'],
                'cm_date' => [$this->translator->translate('Datum_přijetí'), 'format' => 'date'],
                'req_date' => [$this->translator->translate('Požadované_dodání'), 'format' => 'date'],
                'delivery_date' => [$this->translator->translate('Skutečné_dodání'), 'format' => 'date'],
                's_eml' => ['E-mail', 'format' => 'boolean'],
                'expedition_ok' => ['Expedice ok', 'format' => 'boolean'],
                'inv_number' => [$this->translator->translate('Faktura'), 'format' => 'text'],
                'cl_invoice.inv_number' => [$this->translator->translate('Spárovaná_faktura'), 'format' => 'text'],
                'cl_invoice_advance.inv_number' => [$this->translator->translate('Spárovaná_záloha'), 'format' => 'text'],
                'cl_store_docs.doc_number' => [$this->translator->translate('Výdejka'), 'format' => 'text'],
                'cm_order' => $this->translator->translate('Objednávka'),
                'cl_users.name' => $this->translator->translate('Obchodník'),
                'created' => [$this->translator->translate('Vytvořeno'), 'format' => 'datetime'], 'create_by' => $this->translator->translate('Vytvořil'), 'changed' => [$this->translator->translate('Změněno'), 'format' => 'datetime'], 'change_by' => $this->translator->translate('Změnil')];
        }

        $this->dataColumns = $arrData;
        //$this->formatColumns = array('cm_date' => "date",'created' => "datetime",'changed' => "datetime");
        //$this->agregateColumns = 'cl_partners_book.*,MAX(:cl_partners_event.date) AS cdate';
        //$this->FilterC = 'UPPER(company) LIKE ? OR UPPER(street) LIKE ? OR UPPER(city) LIKE ? OR UPPER(:cl_partners_event.tags) LIKE ?';
        $this->filterColumns = ['cm_number' => 'autocomplete', 'cl_status.status_name' => 'autocomplete', 'cl_partners_book.company' => 'autocomplete', 'cl_invoice.inv_number' => 'autocomplete',
            'cm_order' => 'autocomplete', 'cm_title' => 'autocomplete', 'cl_users.name' => 'autocomplete', 'cl_center.name' => 'autocomplete', 'cl_currencies.currency_name' => 'cl_currencies.currency_name'];

        $this->userFilterEnabled = TRUE;
        $this->userFilter = ['cm_number', 'cm_title', 'cm_order', 'cl_partners_book.company', 'price_e2_vat', 'price_e2'];

        $this->cxsEnabled = TRUE;
        $this->userCxsFilter = [':cl_commission_items_sel.item_label', ':cl_commission_items_sel.cl_pricelist.identification',
            ':cl_commission_items_sel.cl_pricelist.item_label', ':cl_commission_items_sel.description1', ':cl_commission_items_sel.description2',
            ':cl_commission_items.item_label',
            ':cl_commission_task.name', ':cl_commission_task.description',
            ':cl_commission_work.work_label', ':cl_commission_work.note'];

        $this->DefSort = 'cm_date DESC';


        //if (!($currencyRate = $this->CurrenciesManager->findOneBy(array('currency_name' => $settings->def_mena))->fix_rate))
//		$currencyRate = 1;


        $this->defValues = ['cm_date' => new \Nette\Utils\DateTime,
            'cl_company_branch_id' => $this->user->getIdentity()->cl_company_branch_id,
            'cl_currencies_id' => $this->settings->cl_currencies_id,
            'currency_rate' => $this->settings->cl_currencies->fix_rate,
            'header_show' => $this->settings->header_show_cm,
            'header_txt' => $this->settings->header_txt_cm,
            'vat' => $this->settings->def_sazba,
            'price_e_type' => $this->settings->price_e_type,
            'cl_users_id' => $this->user->getId()];
        //$this->numberSeries = 'commission';
        $this->numberSeries = ['use' => 'commission', 'table_key' => 'cl_number_series_id', 'table_number' => 'cm_number'];
        $this->readOnly = ['cm_number' => TRUE,
            'created' => TRUE,
            'create_by' => TRUE,
            'changed' => TRUE,
            'change_by' => TRUE];
        //$this->toolbar = array(1 => array('url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => 'Nový záznam', 'class' => 'btn btn-primary', 'data' => array('data-ajax="false"', 'data-history="false"')));


        //$this->showChildLink = 'PartnersEvent:default';
        //Condition for color highlit rows
        //$testDate = new \Nette\Utils\DateTime;
        //$testDate = $testDate->modify('-30 day');
        //$this->conditionRows = array( 'cdate','<=',$testDate);
        $this->rowFunctions = array('copy' => 'disabled');

        /*baselist child section
	 * all bellow is for master->child show
	 */
        $this->bscOff = FALSE;
        $this->bscEnabled = $this->getUser()->getIdentity()->bsc_enabled;
        $this->bscPages = ['card' => ['active' => false, 'name' => $this->translator->translate('karta'), 'lattefile' => $this->getLattePath() . 'Commission\card.latte'],
            'items_sel' => ['active' => true, 'name' => $this->translator->translate('prodejní_položky'), 'lattefile' => $this->getLattePath() . 'Commission\items_sel.latte'],
            'items_production' => ['active' => false, 'name' => $this->translator->translate('výroba'), 'lattefile' => $this->getLattePath() . 'Commission\items_production.latte'],
            'items' => ['active' => false, 'name' => $this->translator->translate('nákladové_položky'), 'lattefile' => $this->getLattePath() . 'Commission\items_cost.latte'],
            'works' => ['active' => false, 'name' => $this->translator->translate('práce'), 'lattefile' => $this->getLattePath() . 'Commission\works.latte'],
            'tasks' => ['active' => false, 'name' => $this->translator->translate('úkoly'), 'lattefile' => $this->getLattePath() . 'Commission\tasks.latte'],
            'header' => ['active' => false, 'name' => $this->translator->translate('záhlaví'), 'lattefile' => $this->getLattePath() . 'Commission\header.latte'],
            'assignment' => ['active' => false, 'name' => $this->translator->translate('zápatí'), 'lattefile' => $this->getLattePath() . 'Commission\footer.latte'],
            'memos' => ['active' => false, 'name' => $this->translator->translate('poznámky'), 'lattefile' => $this->getLattePath() . 'Commission\description.latte'],
            'files' => ['active' => false, 'name' => $this->translator->translate('soubory'), 'lattefile' => $this->getLattePath() . 'Commission\files.latte']
        ];
        $this->bscSums = ['lattefile' => $this->getLattePath() . 'Commission\sums.latte'];
        $this->bscToolbar = [1 => ['group' =>
            [1 => ['url' => 'savePDFWorks!',
                'rightsFor' => 'report',
                'label' => $this->translator->translate('Práce_na_zakázce'),
                'title' => '',
                'data' => ['data-ajax="false"', 'data-history="false"'],
                'class' => 'ajax', 'icon' => 'iconfa-print'],
                2 => ['url' => 'savePDFTasks!',
                    'rightsFor' => 'report',
                    'label' => $this->translator->translate('Úkoly_na_zakázce'),
                    'title' => '',
                    'data' => ['data-ajax="false"', 'data-history="false"'],
                    'class' => 'ajax', 'icon' => 'iconfa-print'],
            ],
            'group_settings' =>
                ['group_label' => $this->translator->translate('Tisk'),
                    'group_class' => 'btn btn-primary dropdown-toggle btn-sm',
                    'group_title' => $this->translator->translate('Tiskové_sestavy'), 'group_icon' => 'iconfa-print']
        ],
            2 => ['group' =>
                [
                    1 => ['url' => 'genOrderAll2!',
                        'rightsFor' => 'write',
                        'label' => $this->translator->translate('Objednat_vše_jedna_objednávka'),
                        'title' => $this->translator->translate('Vygeneruje_objednávku_všech_položek_této_zakázky'),
                        'data' => ['data-ajax="true"', 'data-history="false"'],
                        'class' => 'ajax', 'icon' => 'iconfa-bell'],
                    2 => ['url' => 'genOrderAll!',
                        'rightsFor' => 'write',
                        'label' => $this->translator->translate('Objednat_vše_podle_dodavatelů'),
                        'title' => $this->translator->translate('Vygeneruje_objednávky_pro_dodavatele_všech_položek_této_zakázky'),
                        'data' => ['data-ajax="true"', 'data-history="false"'],
                        'class' => 'ajax', 'icon' => 'iconfa-bell'],
                    3 => ['url' => 'genOrderMissing!',
                        'rightsFor' => 'report',
                        'label' => $this->translator->translate('Objednat_chybějící_podle_dodavatelů'),
                        'title' => $this->translator->translate('Vygeneruje_objednávky_jen_pro_dodavatele_položek_této_zakázky_které_nejsou_skladem'),
                        'data' => ['data-ajax="true"', 'data-history="false"'],
                        'class' => 'ajax', 'icon' => 'iconfa-bell']
                ],
                'group_settings' =>
                    ['group_label' => $this->translator->translate('Objednat'),
                        'group_class' => 'btn btn-success dropdown-toggle btn-sm',
                        'group_title' => $this->translator->translate('tisk'), 'group_icon' => 'iconfa-bell']
            ],
            3 => ['url' => 'createInvoiceAdvanceModalWindow!', 'rightsFor' => 'write', 'label' => $this->translator->translate('záloha'), 'title' => $this->translator->translate('vytvořit_zálohovou_fakturu_z_celé_zakázky'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-edit'],
            4 => ['url' => 'createInvoiceModalWindow!', 'rightsFor' => 'write', 'label' => $this->translator->translate('faktura'), 'title' => $this->translator->translate('vytvořit_fakturu_z_celé_zakázky'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-edit'],
            5 => ['group' =>
                [1 => ['url' => 'createStoreOutModalWindow!',
                    'rightsFor' => 'write',
                    'label' => $this->translator->translate('nová_výdejka'),
                    'title' => $this->translator->translate('vytvořit_novou_výdejku_ze_zakázky'),
                    'class' => 'ajax', 'icon' => 'iconfa-edit',
                    'data' => ['data-ajax="true"', 'data-history="false"'],
                ],
                    2 => ['url' => 'createStoreOutUpdateModalWindow!',
                        'rightsFor' => 'write',
                        'label' => $this->translator->translate('aktualizovat_výdejky'),
                        'title' => $this->translator->translate('aktualizuje_již_vytvořené_výdejky_z_této_zakázky'),
                        'class' => 'ajax', 'icon' => 'iconfa-edit',
                        'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-edit'],
                ],
                'group_settings' =>
                    ['group_label' => $this->translator->translate('Vydat'),
                        'group_class' => 'btn btn-success dropdown-toggle btn-sm',
                        'group_title' => $this->translator->translate('tisk'), 'group_icon' => 'iconfa-edit']
            ],
            6 => ['url' => 'Expedition:default', 'urlparams' => ['keyname' => 'dataId', 'key' => 'id'], 'rightsFor' => 'write', 'label' => $this->translator->translate('Expedice'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="true"', 'data-history="false"', 'data-not-check="1"', 'data-title="Expedice zakázky"', 'target="_new"'], 'icon' => 'glyphicon glyphicon-eye'],
            7 => ['url' => 'showTextsUse!', 'rightsFor' => 'write', 'label' => $this->translator->translate('časté_texty'), 'class' => 'btn btn-success showTextsUse',
                'data' => ['data-ajax="true"', 'data-history="false"', 'data-not-check="1"'], 'icon' => 'glyphicon glyphicon-list'],
            8 => ['url' => 'showPairedDocs!', 'rightsFor' => 'write', 'label' => $this->translator->translate('doklady'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-list-alt'],
            9 => ['url' => 'savePDF!', 'rightsFor' => 'enable', 'label' => $this->translator->translate('Náhled'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="false"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-print'],
            10 => ['url' => 'downloadPDF!', 'rightsFor' => 'enable', 'label' => 'PDF', 'class' => 'btn btn-success',
                'data' => ['data-ajax="false"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-save'],
            11 => ['url' => 'sendDoc!', 'rightsFor' => 'write', 'label' => 'E-mail', 'class' => 'btn btn-success', 'icon' => 'glyphicon glyphicon-send'],
        ];

        /*     1 => array('type' => 'handle', 'url' => $this->link('Expedition:default', ['modal' => TRUE]), 'label' =>  $this->translator->translate('Expedice'),
            'title' =>  $this->translator->translate('Otevře_zakázku_v_modulu_expedice'), 'class' => 'modalClick',
            'data-title' => $this->translator->translate('Expedice_zakázky'))
*/
        $this->bscTitle = ['cm_number' => $this->translator->translate('Číslo_zakázky'), 'cl_partners_book.company' => 'Odběratel'];
        /*end of bsc section
	 *
	 */

        //04.07.2018 - settings for documents saving and emailing
        $this->docTemplate = $this->ReportManager->getReport(__DIR__ . '/../templates/Commission/commissionv1.latte');
        $this->docAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;
        //$this->docTitle	    = array("Zakázka ", "cm_number");
        $this->docTitle = ["", "cl_partners_book.company", "cm_number"];
        //22.07.2018 - settings for sending doc by email
        $this->docEmail = ['template' => __DIR__ . '/../templates/Commission/emailCommission.latte',
            'emailing_text' => 'commission'];


        $this->toolbar = [0 => ['group_start' => ''],
            1 => ['url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nový_záznam'), 'class' => 'btn btn-primary', 'data' => ['data-ajax="false"', 'data-history="false"']],
            2 => $this->getNumberSeriesArray('commission'),
            3 => ['group_end' => ''],
            4 => ['url' => $this->link('syncEshops!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Eshop'), 'title' => $this->translator->translate('EshopTitle'), 'class' => 'btn btn-primary', 'icon' => 'iconfa-refresh', 'data' => ['data-ajax="true"', 'data-history="false"']],
            5 => ['group' =>
                [0 => ['url' => $this->link('importPohoda!', ['type' => 2]),
                    'rightsFor' => 'write',
                    'label' => $this->translator->translate('XML_Pohoda'),
                    'title' => $this->translator->translate('Import_z_formátu_XML_Pohoda'),
                    'data' => ['data-ajax="true"', 'data-history="false"'],
                    'class' => 'ajax', 'icon' => 'iconfa-import'],
                ],
                'group_settings' => ['group_label' => $this->translator->translate('Import'),
                    'group_class' => 'btn btn-primary dropdown-toggle btn-sm',
                    'group_title' => $this->translator->translate('tisk'), 'group_icon' => 'iconfa-import']
            ],
            6 => ['group' =>
                [0 => ['url' => $this->link('report!', ['index' => 1]), 'rightsFor' => 'report', 'label' => $this->translator->translate('report_commission'), 'title' => $this->translator->translate('report_commission_title'),
                    'class' => 'ajax', 'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'iconfa-print'],
                    1 => ['url' => $this->link('report!', ['index' => 2]), 'rightsFor' => 'report', 'label' => $this->translator->translate('report_commissions_centers'), 'title' => $this->translator->translate('report_commissions_centers_title'),
                        'class' => 'ajax', 'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'iconfa-print'],
                ],
                'group_settings' => ['group_label' => $this->translator->translate('Tisk'), 'group_class' => 'btn btn-primary dropdown-toggle btn-sm', 'group_title' => $this->translator->translate('tisk'), 'group_icon' => 'iconfa-print']
            ]
        ];

        $this->report = [1 => ['reportLatte' => __DIR__ . '/../templates/Commission/rptCommissionSet.latte',
            'reportName' => $this->translator->translate('report_commission_title2')],
            2 => ['reportLatte' => __DIR__ . '/../templates/Commission/rptCommissionsCentersSet.latte',
                'reportName' => $this->translator->translate('report_commissions_centers')]];

        //27.08.2018 - filter for show only not used items and tasks to create commission
        //$this->filterStoreUsed		= array('filter' => 'cl_store_docs_id IS NULL');
        //15.11.2020 - filter for items used on invoice are no longer necessary
        //$this->filterInvoiceUsed = array('filter' => 'cl_invoice_id IS NULL');
        $this->filterStoreCreate = ['filter' => 'cl_store_docs_id IS NULL AND cl_pricelist_id IS NOT NULL'];
        $this->filterStoreUpdate = ['filter' => 'cl_pricelist_id IS NOT NULL'];

        $this->quickFilter = ['cl_status.status_name' => ['name' => $this->translator->translate('Zvolte_filtr_zobrazení'),
            'values' => $this->StatusManager->findAll()->where('status_use = ?', 'commission')->order('s_new DESC,s_work DESC,s_fin DESC,s_storno DESC,status_name ASC')->fetchPairs('id', 'status_name')]
        ];

        /*predefined filters*/
        $this->pdFilter = [0 => ['url' => $this->link('pdFilter!', ['index' => 0, 'pdFilterIndex' => 0]),
            'filter' => '(cl_status.s_fin = 0 AND req_date <= NOW())',
            'sum' => ['price_e2*currency_rate' => 'bez DPH', 'price_e2_vat*currency_rate' => 's DPH'],
            'rightsFor' => 'read',
            'label' => $this->translator->translate('nedokončené_v_termínu'),
            'title' => $this->translator->translate('Zakázky_které_nejsou_dokončeny_v_předpokládaném_termínu'),
            'data' => ['data-ajax="true"', 'data-history="true"'],
            'class' => 'ajax', 'icon' => 'iconfa-filter'],
            1 => ['url' => $this->link('pdFilter!', ['index' => 1, 'pdFilterIndex' => 1]),
                'filter' => '(cl_status.s_fin = 0 )',
                'sum' => ['price_e2*currency_rate' => 'bez DPH', 'price_e2_vat*currency_rate' => 's DPH'],
                'rightsFor' => 'read',
                'label' => $this->translator->translate('zatím_nedokončené'),
                'title' => $this->translator->translate(''),
                'data' => ['data-ajax="true"', 'data-history="true"'],
                'class' => 'ajax', 'icon' => 'iconfa-filter']
        ];
        if ($this->settings->platce_dph == 0) {
            $this->pdFilter[0]['sum'] = ['price_e2*currency_rate' => 'celkem'];
            $this->pdFilter[1]['sum'] = ['price_e2*currency_rate' => 'celkem'];
        }

        $this->actionList = [
            1 => ['type' => 'handle', 'url' => $this->link('Expedition:default', ['modal' => TRUE]), 'label' => $this->translator->translate('Expedice'),
                'title' => $this->translator->translate('Otevře_zakázku_v_modulu_expedice'), 'class' => 'modalClick',
                'data-title' => $this->translator->translate('Expedice_zakázky')]
        ];
        $this->actionList[1]['data-href'] = $this->actionList[1]['url'];
        if ($this->isAllowed($this->presenter->name, 'report')) {
            $this->groupActions['pdf'] = 'stáhnout PDF';
            $this->groupActions['invoice'] = 'vytvořit faktury';
        }
    }

    public function handleImportPohoda($type)
    {
        $this->importType = $type;
        $this->showModal('importPohoda');
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('createDocs');
    }

    protected function createComponentImportPohodaForm()
    {
        $form = new Form;
        $form->addHidden('importType')
            ->setDefaultValue($this->importType);
        $form->addUpload('upload_file', $this->translator->translate('Importní_soubor'))
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->addRule(Form::MAX_FILE_SIZE, $this->translator->translate('Maximální_velikost_souboru_je_512_kB'), 512 * 1024 /* v bytech */);
        $form->addSubmit('submit', 'Importovat')
            ->setHtmlAttribute('class', 'form-control btn-sm btn-primary');
        $form->onSuccess[] = array($this, "ImportPohodaFormSubmited");
        return $form;
    }

    /**
     * ImportTrans form submitted
     * @param \Nette\Application\UI\Form
     * @return void
     */
    public function importPohodaFormSubmited($form)
    {
        $values = $form->getValues();
        try {
            $file = $form->getHttpData($form::DATA_FILE, 'upload_file');
            if ($file && $file->isOk()) {
                $xml = $file->getContents();
                $arrRet = $this->DataManager->importPohoda20($xml, NULL);
                $i = count($arrRet);
                $this->flashMessage('Naimportováno ' . $i . ' zakázek.', 'success');
            }
            //$this->redrawControl('content');
            $this->hideModal('importPohoda');
            //$this->redrawControl('baselistArea');
            //$this->redrawControl('baselist');
            //$this->redrawControl('paginator_top');
        } catch (\Nette\Security\AuthenticationException $e) {
            $form->addError($e->getMessage());
        }
    }


    protected function createComponentEdit($name)
    {
        $form = new Form($this, $name);
        //$form->setMethod('POST');
        $form->addHidden('id', NULL);
        $form->addText('cm_number', $this->translator->translate('Číslo_zakázky'), 10, 10)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Číslo_zakázky'));
        $form->addText('cm_date', $this->translator->translate('Datum_přijetí'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm datetimepicker')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_přijetí'));
        $form->addText('start_date', $this->translator->translate('Začátek_práce'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm datepicker')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Začátek_práce'));
        $form->addText('delivery_date', $this->translator->translate('Skutečné_dodání'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm datetimepicker')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_dodání'));
        $form->addText('req_date', $this->translator->translate('Požadované_dodání'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm datetimepicker')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Požadovaný_datum_dodání'));
        $form->addTextArea('cm_title', $this->translator->translate('Popis_zakázky'), 100, 4)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Popis_zakázky'));
        $form->addTextArea('memos_txt', $this->translator->translate('Popis_práce'), 100, 4)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Popis_práce'));
        $form->addText('description_txt2', $this->translator->translate('Poznámka'), 10, 50)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Poznámka'));
        $form->addText('cm_order', $this->translator->translate('Číslo_objednávky'), 40, 40)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Číslo_objednávky'));

        $form->addText('inv_number', $this->translator->translate('Faktura'), 20, 40)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Číslo_faktury'));

        $arrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addSelect('cl_center_id', $this->translator->translate("Středisko"), $arrCenter)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_středisko'))
            ->setPrompt($this->translator->translate('Zvolte_středisko'));

        $arrStatus = $this->StatusManager->findAll()->where('status_use = ?', 'commission')->order('s_new DESC,s_work DESC,s_fin DESC,s_storno DESC,status_name ASC')->fetchPairs('id', 'status_name');
        $form->addSelect('cl_status_id', $this->translator->translate("Stav"), $arrStatus)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_stav_zakázky'))
            ->setPrompt($this->translator->translate('Zvolte_stav_zakázky'));

        $arrPayments = $this->PaymentTypesManager->findAll()->order('name ASC')->fetchPairs('id', 'name');
        $form->addSelect('cl_payment_types_id', $this->translator->translate("Platba"), $arrPayments)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_druh_platby'))
            ->setPrompt($this->translator->translate('Zvolte_druh_platby'));

        $arrTransports = $this->TransportTypesManager->findAll()->order('name ASC')->fetchPairs('id', 'name');
        $form->addSelect('cl_transport_types_id', $this->translator->translate("Doprava"), $arrTransports)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_druh_dopravy'))
            ->setPrompt($this->translator->translate('Zvolte_druh_dopravy'));


        //28.12.2018 - have to set $tmpId for found right record it could be bscId or id
        if ($this->id == NULL) {
            $tmpId = $this->bscId;
        } else {
            $tmpId = $this->id;
        }
        if ($tmpInvoice = $this->DataManager->find($tmpId)) {
            if (isset($tmpInvoice['cl_partners_book_id'])) {
                $tmpPartnersBookId = $tmpInvoice->cl_partners_book_id;
            } else {
                $tmpPartnersBookId = 0;
            }

        } else {
            $tmpPartnersBookId = 0;
        }
        $arrPartners = $this->PartnersManager->findAll()->where('id = ?', $tmpPartnersBookId)->fetchPairs('id', 'company');

        $mySection = $this->getSession('selectbox'); // returns SessionSection with given name
        //06.07.2018 - session selectbox is filled via baselist->handleUpdatePartnerInForm which is called by ajax from onchange event of selectbox
        //this is necessary because Nette is controlling values of selectbox send in form with values which were in selectbox accesible when it was created.
        /*if (isset($mySection->cl_partners_book_id_values ))
	    {
		    $arrPartners = 	$mySection->cl_partners_book_id_values;
	    }else{
		    $arrPartners = $this->PartnersManager->findAll()->where('id = ?', $tmpPartnersBookId)->fetchPairs('id','company');
	    }*/
        $form->addSelect('cl_partners_book_id', $this->translator->translate("Klient"), $arrPartners)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_klienta'))
            ->setHtmlAttribute('data-urlajax', $this->link('getPartners!'))
            ->setHtmlAttribute('data-url-update-partner-in-form', $this->link('updatePartnerInForm!'))
            ->setPrompt($this->translator->translate('Zvolte_klienta'));
        //$form['cl_partners_book_id']->checkAllowedValues = FALSE;

        $arrWorkers = $this->PartnersBookWorkersManager->getWorkersGrouped($tmpPartnersBookId);
        $form->addSelect('cl_partners_book_workers_id', $this->translator->translate("Kontakt"), $arrWorkers)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_kontaktní_osobu'))
            ->setPrompt($this->translator->translate('Zvolte_kontaktní_osobu'));

        $arrBranch = $this->PartnersBranchManager->findAll()->where('cl_partners_book_id = ?', $tmpPartnersBookId)->fetchPairs('id', 'b_name');
        $form->addSelect('cl_partners_branch_id', $this->translator->translate("Pobočka"), $arrBranch)
            ->setPrompt($this->translator->translate('Zvolte_pobočku'))
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Pobočka'));

        $arrCurrencies = $this->CurrenciesManager->findAll()->order('currency_name')->fetchPairs('id', 'currency_name');
        $form->addSelect('cl_currencies_id', $this->translator->translate("Měna"), $arrCurrencies)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_měnu'))
            ->setHtmlAttribute('class', 'form-control chzn-select input-sm')
            ->setHtmlAttribute('data-urlajax', $this->link('GetCurrencyRate!'))
            ->setHtmlAttribute('data-urlrecalc', $this->link('makeRecalc!'))
            ->setPrompt($this->translator->translate('Zvolte_měnu'));
        $form->addText('currency_rate', $this->translator->translate('Kurz'), 7, 7)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('data-urlrecalc', $this->link('makeRecalc!'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Kurz'));
        $arrVat = $this->RatesVatManager->findAllValid()->fetchPairs('rates', 'rates');
        if ($this->settings->platce_dph) {
            $form->addSelect('vat', $this->translator->translate("Sazba_DPH"), $arrVat)
                ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_DPH'))
                ->setHtmlAttribute('class', 'form-control chzn-select input-sm')
                ->setHtmlAttribute('data-urlrecalc', $this->link('makeRecalc!'))
                ->setPrompt($this->translator->translate('Zvolte_DPH'))
                ->setRequired($this->translator->translate('Sazba_DPH_musí_být_zvolena'));
        }
        if ($this->settings->platce_dph) {
            $price_pe2_base = $this->translator->translate("Smluvní_cena_bez_DPH");
        } else {
            $price_pe2_base = $this->translator->translate("Smluvní_cena");
        }
        $form->addText('price_pe2_base', $price_pe2_base);
        $form->addText('price_pe2_vat', $this->translator->translate('Smluvní_cena_s_DPH'));

        $form->addText('profit_items', $this->translator->translate("Požadovaný_zisk_na_položkách"));
        $form->addText('profit_works', $this->translator->translate("Požadovaný_zisk_na_práci"));

        //
        //
        //$arrUsers = $this->UserManager->getAll()->fetchPairs('id','name');
        $arrUsers = $this->UserManager->getUsersAN($this->user->getIdentity()->cl_company_id);
        $form->addSelect('cl_users_id', $this->translator->translate("Obchodník"), $arrUsers)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_obchodníka'))
            ->setPrompt($this->translator->translate('Zvolte_obchodníka'));

        //$form->addText('created', 'Datum vytvoření:', 10, 10)
        //	    	->setHtmlAttribute('class','form-control input-sm')
        //		->setHtmlAttribute('placeholder','Datum vytvoření');
        //$form->addText('create_by', 'Vytvořil:', 20, 20)
        //	    	->setHtmlAttribute('class','form-control input-sm')
        //		->setHtmlAttribute('placeholder','Vytvořil');
        //$form->addText('changed', 'Datum změny:', 10, 10)
        //	    	->setHtmlAttribute('class','form-control input-sm')
        //		->setHtmlAttribute('placeholder','Datum změny');
        //$form->addText('change_by', 'Změnil:', 20, 20)
        //	    	->setHtmlAttribute('class','form-control input-sm')
        //		->setHtmlAttribute('placeholder','Změnil');

        $form->addCheckbox('use_for_hd', $this->translator->translate('Použít_pro_Helpdesk'))
            ->setDefaultValue(FALSE)
            ->setHtmlAttribute('class', 'items-show');

        $form->onValidate[] = [$this, 'FormValidate'];
        $form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class', 'btn btn-primary');
        $form->addSubmit('create_invoice', $this->translator->translate('Vytvořit_fakturu'))->setHtmlAttribute('class', 'btn btn-primary');
        $form->addSubmit('send_fin', $this->translator->translate('Odeslat'))->setHtmlAttribute('class', 'btn btn-primary');
        $form->addSubmit('save_pdf', $this->translator->translate('PDF'))->setHtmlAttribute('class', 'btn btn-primary');
        $form->addSubmit('back', $this->translator->translate('Zpět'))
            ->setHtmlAttribute('class', 'btn btn-primary')
            ->setValidationScope([])
            ->onClick[] = [$this, 'stepBack'];
        //	    ->onClick[] = callback($this, 'stepSubmit');

        $form->onSuccess[] = [$this, 'SubmitEditSubmitted'];
        return $form;
    }


    protected function createComponentHeaderEdit($name)
    {
        $form = new Form($this, $name);
        $form->addHidden('id', NULL);
        //$form->addCheckbox('header_show', 'Tiskount záhlaví');
        $form->addTextArea('header_txt', $this->translator->translate('Záhlaví'), 100, 20)
            ->setHtmlAttribute('placeholder', $this->translator->translate('Záhlaví'));
        $form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class', 'btn btn-primary');
        $form->addSubmit('back', $this->translator->translate('Zpět'))
            ->setHtmlAttribute('class', 'btn btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepHeaderBack');
        $form->onSuccess[] = array($this, 'SubmitEditHeaderSubmitted');
        return $form;
    }

    protected function createComponentDescriptionEdit($name)
    {
        $form = new Form($this, $name);
        $form->addHidden('id', NULL);
        $form->addTextArea('description_txt', $this->translator->translate('Zadání'), 100, 20)
            ->setHtmlAttribute('placeholder', $this->translator->translate('Zadání'));
        $form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class', 'btn btn-primary');
        $form->addSubmit('back', $this->translator->translate('Zpět'))
            ->setHtmlAttribute('class', 'btn btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepDescriptionBack');
        $form->onSuccess[] = array($this, 'SubmitEditDescriptionSubmitted');
        return $form;
    }

    protected function createComponentReportClients($name)
    {
        $form = new Form($this, $name);
        //$form->setMethod('POST');
        $form->addHidden('id', NULL);

        $now = new \Nette\Utils\DateTime;
        $form->addText('cm_date_from', 'Od:', 0, 16)
            ->setDefaultValue('01.' . $now->format('m.Y'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_začátek'));

        $form->addText('cm_date_to', $this->translator->translate('Do'), 0, 16)
            ->setDefaultValue($now->format('d.m.Y'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_konec'));

        if ($this->settings->platce_dph == 1) {
            $tmpPriceFrom = $this->translator->translate("Cena_bez_DPH_od");
            $tmpPriceTo = $this->translator->translate("Cena_bez_DPH_do");
        } else {
            $tmpPriceFrom = $this->translator->translate("Cena_od");
            $tmpPriceTo = $this->translator->translate("Cena_do");
        }
        $form->addText('price_e2_from', $tmpPriceFrom . ":", 0, 16)
            ->setDefaultValue(0)
            ->setHtmlAttribute('placeholder', $tmpPriceFrom);

        $form->addText('price_e2_to', $tmpPriceTo . ":", 0, 16)
            ->setDefaultValue(0)
            ->setHtmlAttribute('placeholder', $tmpPriceTo);


        $form->addRadioList('type', $this->translator->translate('Typ_filtru'), array(0 => $this->translator->translate('Datum_přijetí'), 1 => $this->translator->translate('Datum_dodání')))
            ->setDefaultValue(0);
        $form->addCheckbox('done', $this->translator->translate('Pouze_hotové'))
            ->setDefaultValue(true);

//		$tmpArrPartners = $this->PartnersManager->findAll()->order('company')->fetchPairs('id','company');
        $tmpArrPartners = $this->PartnersManager->findAll()->
        select('CONCAT(cl_partners_book.id,"-",IFNULL(:cl_partners_branch.id,"")) AS id, CONCAT(cl_partners_book.company," ",IFNULL(:cl_partners_branch.b_name,"")) AS company')->
        order('company')->fetchPairs('id', 'company');

        $form->addMultiSelect('cl_partners_book', $this->translator->translate('report_clients') . ':', $tmpArrPartners)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('report_clientsPh'));

        $tmpArrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addMultiSelect('cl_center_id', $this->translator->translate('Střediska'), $tmpArrCenter)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_střediska_pro_tisk'));


        $tmpUsers = $this->UserManager->getUsersAN($this->user->getIdentity()->cl_company_id);
        $form->addMultiSelect('cl_users_id', $this->translator->translate('Obchodníci'), $tmpUsers)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_obchodníka_pro_tisk'));

        $form->addSubmit('save', $this->translator->translate('Tisk'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');

        $form->addSubmit('back', $this->translator->translate('Návrat'))
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBackReportClients');
        $form->onSuccess[] = array($this, 'SubmitReportClientsSubmitted');
        //$form->getElementPrototype()->target = '_blank';
        return $form;
    }

    private function createInvoice()
    {
        if ($tmpData = $this->DataManager->find($this->id)) {
            if ($tmpInvoiceType = $this->InvoiceTypesManager->findAll()->where('default_type = ?', 1)->fetch()) {
                $tmpInvoiceType = $tmpInvoiceType->id;
            } else {
                $tmpInvoiceType = NULL;
            }
            //default values for invoice
            $defDueDate = new \Nette\Utils\DateTime;
            $arrInvoice = array();
            $arrInvoice['cl_currencies_id'] = $this->settings->cl_currencies_id;
            $arrInvoice['currency_rate'] = $this->settings->cl_currencies->fix_rate;
            $arrInvoice['vat_active'] = $this->settings->platce_dph;
            $arrInvoice['cl_partners_book_id'] = $tmpData->cl_partners_book_id;
            $arrInvoice['cl_users_id'] = $tmpData->cl_users_id;
            $arrInvoice['cl_currencies_id'] = $tmpData->cl_currencies_id;
            $arrInvoice['currency_rate'] = $tmpData->currency_rate;
            $arrInvoice['cl_commission_id'] = $tmpData->id;
            $arrInvoice['price_e_type'] = $tmpData->price_e_type;
            $arrInvoice['inv_date'] = new \Nette\Utils\DateTime;
            $arrInvoice['vat_date'] = new \Nette\Utils\DateTime;

            $arrInvoice['konst_symb'] = $this->settings->konst_symb;
            $arrInvoice['cl_invoice_types_id'] = $tmpInvoiceType;
            //$arrInvoice['cl_invoice_types_id'] = $tmpInvoiceType;

            $arrInvoice['header_show'] = $this->settings->header_show;
            $arrInvoice['footer_show'] = $this->settings->footer_show;
            $arrInvoice['header_txt'] = $this->settings->header_txt;
            $arrInvoice['footer_txt'] = $this->settings->footer_txt;

            //settings for concrete partner
            if ($tmpData->cl_partners_book->due_date > 0)
                $strModify = '+' . $tmpData->cl_partners_book->due_date . ' day';
            else
                $strModify = '+' . $this->settings->due_date . ' day';

            $arrInvoice['due_date'] = $defDueDate->modify($strModify);

            if (isset($tmpData->cl_partners_book->cl_payment_types_id)) {
                $clPayment = $tmpData->cl_partners_book->cl_payment_types_id;
                $spec_symb = $tmpData->cl_partners_book->spec_symb;
            } else {
                $clPayment = $this->settings->cl_payment_types_id;
                $spec_symb = "";
            }
            $arrInvoice['cl_payment_types_id'] = $clPayment;
            $arrInvoice['spec_symb'] = $spec_symb;

            //create or update invoice
            if ($tmpData->cl_invoice_id == NULL) {
                //new number
                $nSeries = $this->NumberSeriesManager->getNewNumber('invoice');
                $arrInvoice['inv_number'] = $nSeries['number'];
                $arrInvoice['cl_number_series_id'] = $nSeries['id'];
                $arrInvoice['var_symb'] = preg_replace('/\D/', '', $arrInvoice['inv_number']);
                $tmpStatus = 'invoice';
                if ($nStatus = $this->StatusManager->findAll()->where('status_use = ? AND s_new = ?', $tmpStatus, 1)->fetch())
                    $arrInvoice['cl_status_id'] = $nStatus->id;
                bdump($arrInvoice);
                $row = $this->InvoiceManager->insert($arrInvoice);
                $this->DataManager->update(array('id' => $this->id, 'cl_invoice_id' => $row->id));
                $invoiceId = $row->id;
            } else {
                $arrInvoice['id'] = $tmpData->cl_invoice_id;
                $row = $this->InvoiceManager->update($arrInvoice);
                $invoiceId = $tmpData->cl_invoice_id;
            }

            //now content of invoice
            //at first, delete old content
            //next insert new content
            $this->InvoiceItemsManager->findBy(array('cl_invoice_id' => $invoiceId))->delete();
            $tmpItems = $tmpData->related('cl_commission_items');
            $lastOrder = 0;
            foreach ($tmpItems as $one) {
                $newItem = array();
                $newItem['cl_invoice_id'] = $invoiceId;
                $newItem['item_order'] = $one->item_order;
                $newItem['cl_pricelist_id'] = $one->cl_pricelist_id;
                $newItem['item_label'] = $one->item_label;
                $newItem['quantity'] = $one->quantity;
                $newItem['units'] = $one->units;
                $newItem['price_s'] = $one->price_s;
                $newItem['price_e'] = $one->price_e;
                $newItem['discount'] = $one->discount;
                $newItem['price_e2'] = $one->price_e2;
                $newItem['vat'] = $one->vat;
                $newItem['price_e2_vat'] = $one->price_e2_vat;
                $newItem['price_e_type'] = $one->price_e_type;
                $this->InvoiceItemsManager->insert($newItem);
                $lastOrder = $one->item_order;
            }

            $tmpWorks = $tmpData->related('cl_commission_work');

            foreach ($tmpWorks as $one) {
                $newItem = array();
                $newItem['cl_invoice_id'] = $invoiceId;
                $newItem['item_order'] = $lastOrder + $one->item_order;
                $newItem['cl_pricelist_id'] = NULL;
                if (isset($one->cl_users->name))
                    $tmpLabel = $one->work_label . ":" . $one->cl_users->name;
                else
                    $tmpLabel = $one->work_label;

                $newItem['item_label'] = $tmpLabel;
                $newItem['quantity'] = $one->work_time;
                $newItem['units'] = 'hod.';
                $newItem['price_s'] = 0;
                $newItem['discount'] = 0;
                if ($tmpData->price_e_type == 0 || $this->settings->platce_dph == 0) {
                    $newItem['price_e'] = $one->work_rate;
                } else {
                    $calcVat = round(($one->work_rate) * ($tmpData->vat / 100), 2);
                    $newItem['price_e'] = $one->work_rate + $calcVat;
                }

                $newItem['price_e2'] = $one->work_rate * $one->work_time;

                $newItem['vat'] = $tmpData->vat;
                $calcVat = round(($one->work_rate * $one->work_time) * ($tmpData->vat / 100), 2);
                $newItem['price_e2_vat'] = ($one->work_rate * $one->work_time) + $calcVat;
                $this->InvoiceItemsManager->insert($newItem);
            }
            //InvoicePresenter::updateSum($invoiceId,$this);
            $this->InvoiceManager->updateInvoiceSum($invoiceId);

            $this->flashMessage($this->translator->translate('Změny_byly_uloženy_faktura_byla_vytvořena'), 'success');
            //$this->redirect('Commission:default');



            $this->redirect('Invoice:edit', $invoiceId);
        }

    }



    public function handleSyncEshops()
    {
        $retArr = $this->DataManager->syncEshops();
        //$retArr = $this->DeliveryNoteManager->RemoveInvoiceBond($this->id);
        if (self::hasError($retArr)) {
            $this->flashmessage($this->translator->translate($retArr['error'], ['eshop_name' => $retArr['eshop_name']]), 'error');
        }
        $this->flashmessage($this->translator->translate($retArr['success'], ['counter' => $retArr['counter']]), 'success');

        /*if (is_null($retVal)){
                $this->flashMessage($this->translator->translate('EshopSyncResult1'), 'warning');
            }else{
                $this->flashMessage($this->translator->translate('EshopSyncResult2', ['counter' => $retVal]), 'success');
            }*/
        $this->redrawControl('content');
    }


    /*public function handleopenExpedition($dataId){
		    $this->redirect('Expedition:default', ['dataId' => $dataId, 'modal' => TRUE]);
            $this->redrawControl('content');
        }*/


    protected function createComponentReportCommissionsCenters($name)
    {
        $form = new Form($this, $name);
        $now = new \Nette\Utils\DateTime;
        $form->addText('cm_date_from', 'Od:', 0, 16)
            ->setDefaultValue('01.' . $now->format('m.Y'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_začátek'));

        $form->addText('cm_date_to', $this->translator->translate('Do'), 0, 16)
            ->setDefaultValue($now->format('d.m.Y'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_konec'));

        $form->addRadioList('type', $this->translator->translate('Typ_filtru'), array(0 => $this->translator->translate('Datum_přijetí'), 1 => $this->translator->translate('Datum_dodání')))
            ->setDefaultValue(0);
        $form->addCheckbox('done', $this->translator->translate('Pouze_hotové'))
            ->setDefaultValue(false);

        $tmpArrPartners = $this->PartnersManager->findAll()->
        select('CONCAT(cl_partners_book.id,"-",IFNULL(:cl_partners_branch.id,"")) AS id, CONCAT(cl_partners_book.company," ",IFNULL(:cl_partners_branch.b_name,"")) AS company')->
        order('company')->fetchPairs('id', 'company');

        $form->addSelect('cl_partners_book', $this->translator->translate('Odběratel'), $tmpArrPartners)
            ->setPrompt($this->translator->translate('žádný_výběr'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('report_clientsPh'));


        $tmpArrCommissions = $this->DataManager->findAll()->order('cm_number')->fetchPairs('id', 'cm_number');
        $form->addSelect('cl_commission_id', $this->translator->translate('Zakázka'), $tmpArrCommissions)
            ->setPrompt($this->translator->translate('žádný_výběr'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_zakázku_pro_tisk'));

        $tmpArrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addMultiSelect('cl_center_id', $this->translator->translate('Střediska'), $tmpArrCenter)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_střediska_pro_tisk'));

        $tmpUsers = $this->UserManager->getUsersAN($this->user->getIdentity()->cl_company_id);
        $form->addMultiSelect('cl_users_id', $this->translator->translate('Obchodníci'), $tmpUsers)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_obchodníka_pro_tisk'));

        $form->addSubmit('save', $this->translator->translate('Tisk'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');

        $form->addSubmit('back', $this->translator->translate('Návrat'))
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBackReportCommissionsCenters');
        $form->onSuccess[] = array($this, 'SubmitReportCommissionsCenters');
        return $form;
    }


    public function stepBackReportCommissionsCenters()
    {
        $this->rptIndex = 0;
        $this->reportModalShow = FALSE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    public function SubmitReportCommissionsCenters(Form $form)
    {
        $data = $form->values;

        if ($form['save']->isSubmittedBy()) {
            $data['cl_partners_branch'] = [];
            if ($data['cm_date_to'] == "")
                $data['cm_date_to'] = NULL;
            else {
                $data['cm_date_to'] = date('Y-m-d H:i:s', strtotime($data['cm_date_to']) + 86400 - 1);
            }

            if ($data['cm_date_from'] == "")
                $data['cm_date_from'] = NULL;
            else
                $data['cm_date_from'] = date('Y-m-d H:i:s', strtotime($data['cm_date_from']));

            if ($data['type'] == 0) {
                $dataReport = $this->DataManager->findAll()->
                where('cm_date >= ? AND cm_date <= ? ', $data['cm_date_from'], $data['cm_date_to']);
            } elseif ($data['type'] == 1) {
                $dataReport = $this->DataManager->findAll()->
                where('delivery_date >= ? AND delivery_date <= ? ', $data['cm_date_from'], $data['cm_date_to']);
            }

            $data['cl_partners_book'] = ($data['cl_partners_book'] == "") ? [] : [$data['cl_partners_book']];
            if (count($data['cl_partners_book']) > 0) {


                $tmpPartners = [];
                $tmpBranches = [];
                foreach ($data['cl_partners_book'] as $one) {
                    $arrOne = str_getcsv($one, "-");
                    $tmpPartners[] = $arrOne[0];
                    $tmpBranches[] = $arrOne[1];
                }
                $data['cl_partners_book'] = $tmpPartners;
                $data['cl_partners_branch'] = $tmpBranches;

                //$dataReport = $dataReport->where(array('cl_commission.cl_partners_book_id' => $data['cl_partners_book']))->
                //							where(array('cl_commission.cl_partners_branch_id' => $data['cl_partners_branch']));
                $dataReport = $dataReport->where('cl_partners_book_id IN (?) OR cl_partners_branch_id IN (?)', $data['cl_partners_book'], $data['cl_partners_branch']);
            }


            if (count($data['cl_users_id']) > 0) {
                $dataReport = $dataReport->where(['cl_commission.cl_users_id' => $data['cl_users_id']]);
            }

            if ($data['done']) {
                $dataReport->where('cl_status.s_fin = 1');
            }

            $data['cl_commission_id'] = ($data['cl_commission_id'] == "") ? [] : [$data['cl_commission_id']];
            if (count($data['cl_commission_id']) > 0) {
                $dataReport = $dataReport->where(['cl_commission.id' => $data['cl_commission_id']]);
            }


            /*$dataReport = $dataReport->select('DISTINCT :cl_commission_items_sel.cl_center.id, :cl_commission_items_sel.cl_center.name, :cl_commission_items_sel.cl_center.description')
                                ->where(':cl_commission_items_sel.cl_center.id IS NOT NULL')
                                ->order(':cl_commission_items_sel.cl_center.name');
                */
            //bdump($data);
            $dataOther = [];//$this->CommissionTaskManager->find($itemId);
            if (count($data['cl_center_id']) > 0) {
                $dataOther['cl_center'] = $this->CenterManager->findAll()->where(['id' => $data['cl_center_id']])->order('name');
            } else {
                $dataOther['cl_center'] = $this->CenterManager->findAll()->order('name');
            }
            $dataSettings = $data;
            //$dataOther['dataSettingsPartners']   = $this->PartnersManager->findAll()->where(array('id' =>$data['cl_partners_book']))->order('company');
            $dataOther['dataSettingsPartners'] = $this->PartnersManager->findAll()->
                                                            where('cl_partners_book.id IN (?) OR :cl_partners_branch.id IN (?)', $data['cl_partners_book'], $data['cl_partners_branch'])->
                                                            select('cl_partners_book.company')->
                                                            order('company');
            //select('CONCAT(cl_partners_book.company," ",:cl_partners_branch.b_name) AS company')->
            $dataOther['dataSettingsCommission'] = $this->DataManager->findAll()->where(['id' => $data['cl_commission_id']])->order('cm_number');
            $dataOther['dataSettingsCenter'] = $this->CenterManager->findAll()->where(['id' => $data['cl_center_id']])->order('name');
            $dataOther['dataSettingsUsers'] = $this->UserManager->getAll()->where(['id' => $data['cl_users_id']])->order('name');
            $dataOther['settings'] = $this->settings;
            $template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/Commission/rptCommissionsCenters.latte', $dataOther, $dataSettings, 'Přehled zakázek podle středisek');
            $tmpDate1 = new \DateTime($data['cm_date_from']);
            $tmpDate2 = new \DateTime($data['cm_date_to']);
            $this->pdfCreate($template, 'Přehled zakázek podle středisek' . date_format($tmpDate1, 'd.m.Y') . ' - ' . date_format($tmpDate2, 'd.m.Y'));
        }
    }


    public function getWorkHours($arrData){
        if (!is_null($arrData['id'])){
            $result = $this->CommissionWorkManager->findAll()->where('cl_commission_id = ?', ($arrData['id']))->sum('work_time');
        }else{
            $result = '';
        }
        return $result;
    }


}

