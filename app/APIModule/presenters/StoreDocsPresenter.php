<?php
namespace App\APIModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Tracy\Debugger;

class StoreDocsPresenter extends \App\APIModule\Presenters\BaseAPI{


    /**
     * @inject
     * @var \App\Model\StoreDocsManager
     */
    public $DataManager;

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
     * @var \App\Model\PriceListManager
     */
    public $PriceListManager;

    public function actionSet()
    {
        parent::actionSet();

        $this->terminate();
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

        $tmpData = $this->DataManager->findAllTotal()->
        where(array('cl_pricelist.cl_company_id' => $this->cl_company_id))->
        where('cl_pricelist.changed >= ? OR cl_pricelist.created >= ?', $this->sync_last, $this->sync_last);
        if (!empty($this->dataxml))
        {
            $tmpData = $tmpData->where('cl_pricelist.id NOT IN(?)', $this->dataxml);
        }

        $tmpData = $tmpData->select('cl_pricelist.*, cl_pricelist_group.name AS pricelist_group');

        $tmpDataArr = array();
        foreach($tmpData as $key => $one)
        {
            $cl_pricelist = $one->toArray();
            $tmpDataArr['cl_pricelist'][] = $cl_pricelist;

        }
        //dump($tmpDataArr);
        //die;
        $xml = Array2XML::createXML('cl_pricelist', $tmpDataArr);
        //dump($xml);
        echo $xml->saveXML();

        $this->terminate();
    }

    public function actionGetAll()
    {
        parent::actionGetAll();
        $data = $this->DataManager->findAllTotal()->select('cl_store_docs.id,doc_number,doc_date,cl_partners_book.company')->
                                        where('cl_store_docs.cl_company_id = ?',$this->cl_company_id)->order('doc_number')->fetchAll();
        $arrData = array();
        foreach ($data as $key => $one)
        {
            $arrData[] = array('Id' => $one['id'], 'DocNumber' => $one['DocNumber'], 'DocDate' => $one['doc_date'], 'Company' => $one['company']);
        }
        $this->sendJson($arrData, \Nette\Utils\Json::PRETTY);

    }

    public function actionGetOpenOut()
    {
        parent::actionGetAll();
        $data = $this->DataManager->findAllTotal()->select('cl_store_docs.id,doc_number,doc_date,cl_partners_book.company,doc_title')->
                                where('cl_store_docs.cl_company_id = ? AND cl_status.s_work = 1 AND cl_invoice_id IS NULL AND doc_type = 1',$this->cl_company_id)->order('doc_date DESC, doc_number DESC')->fetchAll();
        $arrData = array();
        foreach ($data as $key => $one)
        {
            $arrData[] = array('Id' => $one['id'], 'DocNumber' => $one['doc_number'], 'DocDate' => $one['doc_date'], 'DocTitle' => $one['doc_title'], 'Company' => $one['company']);
        }
        $this->sendJson($arrData, \Nette\Utils\Json::PRETTY);

    }

    public function actionGetOpenIncome()
    {
        parent::actionGetAll();
        $data = $this->DataManager->findAllTotal()->select('cl_store_docs.id,doc_number,doc_date,cl_partners_book.company,doc_title')->
                         where('cl_store_docs.cl_company_id = ? AND cl_status.s_work = 1  AND doc_type = 0',$this->cl_company_id)->order('doc_date DESC, doc_number DESC')->fetchAll();
        $arrData = array();
        foreach ($data as $key => $one)
        {
            $arrData[] = array('Id' => $one['id'], 'DocNumber' => $one['doc_number'], 'DocDate' => $one['doc_date'], 'DocTitle' => $one['doc_title'], 'Company' => $one['company']);
        }
        $this->sendJson($arrData, \Nette\Utils\Json::PRETTY);

    }



    public function actionSetOutItems()
    {
        parent::actionSet();
        $arrData = json_decode($this->dataxml,true);
        //file_put_contents('../log/logjson.txt', $this->dataxml);
        $arrItems = $arrData['data'];

        $tmpParent = $this->DataManager->findAllTotal()->
                                            where(array('cl_company_id' => $this->cl_company_id,
                                                                'id' => $arrData['id']))->
                                            limit(1)->fetch();
        if ($tmpParent) {
            $i = $tmpParent->related('cl_store_move')->max('item_order') + 1;
            try {
                if (!isset($tmpParent['cl_storage_id']) || is_null($tmpParent['cl_storage_id']))
                {
                    throw new \Exception($this->translator->translate('Chybí sklad na kartě dokladu'));
                }
                foreach($arrItems as $key => $one){
                        $data = array();

                    $tmpPricelist = $this->PriceListManager->findAllTotal()->where('id = ?', $one['Id'])->fetch();
                    if ($tmpPricelist) {
                        $data['cl_pricelist_id']    = $one['Id'];
                        $data['price_e']            = $tmpPricelist['price'];
                        $data['price_e2']           = $tmpPricelist['price'] * $one['Quantity'];
                        $data['price_e2_vat']       = $tmpPricelist['price_vat'] * $one['Quantity'];
                        $data['vat']                = $tmpPricelist['vat'];
                        $data['price_s']            = $tmpPricelist['price_s'];

                        $data['cl_company_id'] = $this->cl_company_id;
                        //$data['']
                        $s_out = $one['Quantity'];
                        $data['s_in'] = 0;
                        $data['s_end'] = 0;
                        $data['s_out'] = 0;
                        $data['s_out_fin'] = 0;
                        $data['s_total'] = 0;
                        $data['cl_storage_id'] = $tmpParent['cl_storage_id'];
                        $data['cl_store_id'] = NULL;
                        $data['cl_invoice_items_id'] = NULL;
                        $data['cl_invoice_items_back_id'] = NULL;
                        $data['cl_store_docs_id'] = $tmpParent->id;
                        $data['item_order'] = $i;
                        //unset($data['id']);
                        $row = $this->StoreMoveManager->insert($data);
                        $data['id'] = $row->id;
                        $data['s_in'] = 0;
                        $data['s_end'] = 0;
                        $data['s_out'] = $s_out;


                        $data2 = $this->StoreManager->GiveOutStore($data, $row, $tmpParent, $this->cl_company_id);
                        $this->StoreMoveManager->updateForeign($data2);

                        //23.07.2021 - removed because VAP should be changed only on income
                        //$this->StoreManager->updateVAP($data2['cl_store_id'], $tmpParent['doc_date']);
                        $i++;
                    }
                }
                $this->StoreManager->UpdateSum($tmpParent['id']);
                //$this->flashMessage($this->translator->translate('Data z XML soubory byly naimportovány do výdejky.'), 'success');
                echo('OK');

                }catch (\Exception $e) {
                    Debugger::log('Chyba při zpracování importu: ' . $arrData['id']);
                    echo('Chybě při zpracování importu');
            }
        }else{
            echo('Nenalezen doklad');
        }
        $this->terminate();
    }

    public function actionSetIncomeItems()
    {
        parent::actionSet();
        $arrData = json_decode($this->dataxml,true);
       // file_put_contents('../log/logjson.txt', $this->dataxml);
        $arrItems = $arrData['data'];

        $tmpParent = $this->DataManager->findAllTotal()->
                                        where(array('cl_company_id' => $this->cl_company_id,
                                            'id' => $arrData['id']))->
                                        limit(1)->fetch();
        if ($tmpParent) {
            $i = $tmpParent->related('cl_store_move')->max('item_order') + 1;
            //try {
                if (!isset($tmpParent['cl_storage_id']) || is_null($tmpParent['cl_storage_id']))
                {
                    throw new \Exception($this->translator->translate('Chybí sklad na kartě dokladu'));
                }
                foreach($arrItems as $key => $one){
                    $data = array();

                    $tmpPricelist = $this->PriceListManager->findAllTotal()->where('id = ?', $one['Id'])->fetch();
                    if ($tmpPricelist) {
                        $data['cl_pricelist_id']    = $one['Id'];
                        $data['price_e']            = $tmpPricelist['price'];
                        $data['price_e2']           = $tmpPricelist['price'] * $one['Quantity'];
                        $data['price_e2_vat']       = $tmpPricelist['price_vat'] * $one['Quantity'];
                        $data['vat']                = $tmpPricelist['vat'];
                        $data['price_s']            = $tmpPricelist['price_s'];
                        $data['cl_company_id']      = $this->cl_company_id;

                        $s_in                               = $one['Quantity'];
                        $data['s_in']	                    = 0;
                        $data['s_end']	                    = 0;
                        $data['s_out']	                    = 0;
                        $data['s_out_fin']	                = 0;

                        $data['cl_storage_id']              = $tmpParent['cl_storage_id'];
                        $data['cl_store_id']		        = NULL;
                        $data['cl_invoice_items_id']	    = NULL;
                        $data['cl_invoice_items_back_id']	= NULL;
                        $data['cl_store_docs_id']		    = $tmpParent['id'];
                        $data['item_order'] = $i;
                        $row            = $this->StoreMoveManager->insertForeign($data);
                        $data['id']     = $row->id;
                        $data['s_in']	= $s_in;
                        $data['s_end']	= $s_in;
                        $data2          = $this->StoreManager->GiveInStore($data, $row, $tmpParent, $this->cl_company_id);
                        $this->StoreMoveManager->updateForeign($data2);
                        $this->StoreManager->updateVAP($data2['cl_store_id'], $tmpParent['doc_date']);
                        $i++;
                    }
                }
                $this->StoreManager->UpdateSum($tmpParent['id']);
                //$this->flashMessage($this->translator->translate('Data z XML soubory byly naimportovány do výdejky.'), 'success');
                echo('OK');

            //}catch (\Exception $e) {
            //    Debugger::log('Chyba při zpracování importu: ' . $arrData['id']);
            //    echo('Chybě při zpracování importu');
            //}
        }else{
            echo('Nenalezen doklad');
        }
        $this->terminate();
    }


}

