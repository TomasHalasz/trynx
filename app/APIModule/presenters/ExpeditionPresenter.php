<?php

namespace App\APIModule\Presenters;

use mysql_xdevapi\Exception;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Nette\Utils\DateTime;
use Nette\Utils\Strings;
use Tracy\Debugger;

class ExpeditionPresenter  extends \App\APIModule\Presenters\BaseAPI
{

    /**
     * @inject
     * @var \App\Model\ArraysManager
     */
    public $ArraysManager;

    /**
     * @inject
     * @var \App\Model\CommissionManager
     */
    public $commissionManager;

    /**
     * @inject
     * @var \App\Model\StoreMoveManager
     */
    public $storeMoveManager;


    /**
     * @inject
     * @var \App\Model\CommissionItemsSelManager
     */
    public $commissionItemsSelManager;

    /**
     * @inject
     * @var \App\Model\StoragePlacesManager
     */
    public $storagePlacesManager;

    /**
     * @inject
     * @var \App\Model\DeliveryNoteManager
     */
    public $deliveryNoteManager;

    /**
     * @inject
     * @var \App\Model\PairedDocsManager
     */
    public $pairedDocsManager;

    /**Return all cl_commission with cl_status.s_exp = 1
     * @throws \Nette\Application\AbortException
     */
    public function actionGetAll()
    {
        parent::actionGetAll();

        $tmpData = $this->commissionManager->findAllTotal()->
        where('cl_commission.cl_company_id = ?', $this->cl_company_id)->
        where('cl_status.s_fin = 0 AND cl_status.s_storno = 0 AND cl_status.s_exp = 1')->
        order('cm_number');

        $arrData = [];
        foreach ($tmpData as $key => $one) {
            $arrData[] = array('Id' => $key, 'Cm_number' => $one['cm_number'], 'Cm_date' => $one['cm_date'],
                'Company' => $one->cl_partners_book['company'], 'Company_id' => $one['cl_partners_book_id'], 'Price_e2_vat' => $one['price_e2_vat'],
                'Items_count' => $one->related('cl_commission_items_sel')->count('id'));
        }
        $this->sendJson($arrData, \Nette\Utils\Json::PRETTY);
    }

    public function actionGetItemsPrepared()
    {
        parent::actionGetAll();
        $tmpData = $this->commissionItemsSelManager->findAllTotal()->
                                    where('cl_commission.cl_company_id = ?', $this->cl_company_id)->
                                    where('cl_commission.cl_status.s_fin = 0 AND cl_commission.cl_status.s_storno = 0 AND cl_commission.cl_status.s_exp = 1')->
                                    where('quantity != quantity_checked AND cl_commission.id = ?', $this->data['cl_commission_id']);

        $arrData = [];
        foreach ($tmpData as $key => $one) {
            if (!is_null($one['cl_pricelist_id'])) {
                $tmpStoragePlaces = $this->storeMoveManager->findAllTotal()->
                                    where('cl_pricelist_id = ?', $one['cl_pricelist_id'])->
                                    where('s_end > 0 AND cl_store_docs.doc_type = 0')->
                                    group('cl_storage_places');
                $storage_places = "";
                foreach ($tmpStoragePlaces as $key2 => $one2) {
                    $storage_place = $this->storagePlacesManager->getStoragePlaceName(array('cl_storage_places' => $one2['cl_storage_places']));
                    if ($storage_places != "")
                        $storage_places .= ', ';

                    $storage_places .= $storage_place;
                }
                if (empty($storage_places))
                    $storage_places = "není";

            }else{
                $storage_places = "";
            }

            $arrData[] = array('Id' => $key, 'Identification' => (is_null($one['cl_pricelist_id']) ? NULL : $one->cl_pricelist['identification']), 'Item_label' => $one['item_label'],
                'Quantity' => $one['quantity'], 'Quantity_checked' => $one['quantity_checked'], 'Units' => $one['units'], 'Storage_places' => $storage_places);


        }
        $this->sendJson($arrData, \Nette\Utils\Json::PRETTY);
    }


    public function actionGetItemsReady()
    {
        parent::actionGetAll();
        //dump($this->dataxml);
        //dump($this->data);
        $tmpData = $this->commissionItemsSelManager->findAllTotal()->
                                        where('cl_commission.cl_company_id = ?', $this->cl_company_id)->
                                        where('cl_commission.cl_status.s_fin = 0 AND cl_commission.cl_status.s_storno = 0 AND cl_commission.cl_status.s_exp = 1')->
                                        where('quantity = quantity_checked AND cl_commission.id = ?', $this->data['cl_commission_id']);

        $arrData = [];
        foreach ($tmpData as $key => $one) {
            if (!is_null($one['cl_pricelist_id'])) {
                $tmpStoragePlaces = $this->storeMoveManager->findAllTotal()->
                                                    where('cl_pricelist_id = ?', $one['cl_pricelist_id'])->
                                                    where('s_end > 0 AND cl_store_docs.doc_type = 0')->
                                                    group('cl_storage_places');
                $storage_places = "";
                foreach ($tmpStoragePlaces as $key2 => $one2) {
                    $storage_place = $this->storagePlacesManager->getStoragePlaceName(array('cl_storage_places' => $one2['cl_storage_places']));
                    if ($storage_places != "")
                        $storage_places .= ', ';

                    $storage_places .= $storage_place;
                }
                if (empty($storage_places))
                    $storage_places = "není";

            }else{
                $storage_places = "";
            }
            $arrData[] = array('Id' => $key, 'Identification' =>  (is_null($one['cl_pricelist_id']) ? NULL : $one->cl_pricelist['identification']), 'Item_label' => $one['item_label'],
                'Quantity' => $one['quantity'], 'Quantity_checked' => $one['quantity_checked'], 'Units' => $one['units'], 'Storage_places' => $storage_places);
        }
        $this->sendJson($arrData, \Nette\Utils\Json::PRETTY);
    }


    public function actionSetItemReady()
    {
        parent::actionGetAll();
        $tmpItem = $this->commissionItemsSelManager->findAllTotal()->
        where('cl_commission.cl_company_id = ?', $this->cl_company_id)->
        where('cl_commission.cl_status.s_fin = 0 AND cl_commission.cl_status.s_storno = 0 AND cl_commission.cl_status.s_exp = 1')->
        where('cl_commission_items_sel.quantity > cl_commission_items_sel.quantity_checked AND (cl_pricelist.ean_code = ? OR cl_pricelist.identification = ?) AND cl_commission.id = ?', $this->data['searched_text'], $this->data['searched_text'], $this->data['cl_commission_id'])->
        limit(1)->fetch();
        if ($tmpItem) {
            $totalChecked = $tmpItem['quantity_checked'] + 1;
            $this->commissionItemsSelManager->updateForeign(['id' => $tmpItem['id'], 'quantity_checked' => $totalChecked]);
        } else {
            $totalChecked = 0;
        }
        if ($totalChecked == 0)
            $arrResp = ['error' => $totalChecked];
        else
            $arrResp = ['ok' => $totalChecked];

        $this->sendJson($arrResp, \Nette\Utils\Json::PRETTY);
    }

    public function actionUnsetItemReady()
    {
        parent::actionGetAll();
        $tmpItem = $this->commissionItemsSelManager->findAllTotal()->
        where('cl_commission.cl_company_id = ?', $this->cl_company_id)->
        where('cl_commission.cl_status.s_fin = 0 AND cl_commission.cl_status.s_storno = 0 AND cl_commission.cl_status.s_exp = 1')->
        where('cl_commission_items_sel.id = ? AND cl_commission.id = ?', $this->data['id'], $this->data['cl_commission_id'])->
        limit(1)->fetch();
        if ($tmpItem) {
            $totalChecked = 0;
            $this->commissionItemsSelManager->updateForeign(['id' => $tmpItem['id'], 'quantity_checked' => $totalChecked]);
        } else {
            $totalChecked = 0;
        }
        if ($totalChecked == 0)
            $arrResp = ['error' => $totalChecked];
        else
            $arrResp = ['ok' => $totalChecked];

        $this->sendJson($arrResp, \Nette\Utils\Json::PRETTY);
    }

    public function actionGetAllReady()
    {
        parent::actionGetAll();
        $tmpItem = $this->commissionItemsSelManager->findAllTotal()->
                            where('cl_commission.cl_company_id = ?', $this->cl_company_id)->
                            where('cl_commission_id = ?', $this->data['cl_commission_id'])->
                            where('cl_commission_items_sel.quantity != cl_commission_items_sel.quantity_checked')->
                            count('cl_commission_items_sel.id');
        if ($tmpItem) {
            $totalChecked = $tmpItem;
        } else {
            $totalChecked = 0;
        }
        if ($totalChecked > 0)
            $arrResp[] = ['code' => 'error', 'description' => 'Nejsou zkontrolovány všechny položky!'];
        else {
            $arrResp[] = ['code' => 'ok', 'description' => 'Vše je v pořádku. Expedice je hotova.'];
            //TODO: set cl_status_id to expedition finished
            $docId = $this->makeStoreOut($this->data['cl_commission_id']);
            //dump($docId);
            $dnId = $this->makeDeliveryNote($docId);
            //dump($dnId);
            //die;
            $this->finishCommission();
        }

        $this->sendJson($arrResp, \Nette\Utils\Json::PRETTY);
    }


    private function makeStoreOut($cl_commission_id)
    {
        //make giveout from store
        $arrDataItemsSel = $this->commissionItemsSelManager->findAll()->where('cl_commission_id = ?', $cl_commission_id)->fetchPairs('id', 'id');
        $arrDataItems = array();
        $docId = $this->commissionManager->createOut($cl_commission_id, $arrDataItemsSel, $arrDataItems);
        $this->pairedDocsManager->insertOrUpdate(array('cl_company_id' => $this->cl_company_id, 'cl_commission_id' => $cl_commission_id, 'cl_store_docs_id' => $docId));
        return $docId;

    }

    private function makeDeliveryNote($docId)
    {
        //make delivery note
        $arrRet = $this->deliveryNoteManager->createDelivery($docId);
        if (self::hasError($arrRet)) {
            //$this->flashMessage($this->translator->translate('Dodací_list_nebyl_vytvořen'), 'warning');
            $retVal = NULL;
        }else {
            //TODO: connection with cl_transport
            $retVal = $arrRet['deliveryN_id'];
            $cl_commission_id = $this->data['cl_commission_id'];
            $this->pairedDocsManager->insertOrUpdate(array('cl_company_id' => $this->cl_company_id, 'cl_commission_id' => $cl_commission_id, 'cl_delivery_note_id' => $retVal));
        }
        return $retVal;
    }

    private function finishCommission()
    {
        //mark cl_commission as expedition_ok and set correct cl_status_id
        $cl_commission_id = $this->data['cl_commission_id'];
        $tmpStatus = $this->StatusManager->findAllTotal()->where('status_use = ? AND s_exp_ok = 1',  "commission")->fetch();
        if ($tmpStatus){
            $statExpOk = $tmpStatus['id'];
        }else{
            $statExpOk = NULL;
        }
        //dump($statExpOk);
        //die;
        if (!is_null($statExpOk)){
            $this->commissionManager->updateForeign(['id' => $cl_commission_id, 'expedition_ok' => 1, 'cl_status_id' => $statExpOk, 'exp_packages' => $this->data['packages']]);
        }else{
            $this->commissionManager->updateForeign(['id' => $cl_commission_id, 'expedition_ok' => 1, 'exp_packages' =>  $this->data['packages']]);
        }

        //$this->flashMessage($this->translator->translate('Dodací_list_byl_vytvořen'), 'success');
        //$this->payload->id = $arrRet['deliveryN_id'];
    }



}
