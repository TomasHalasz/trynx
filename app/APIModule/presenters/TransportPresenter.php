<?php
namespace App\APIModule\Presenters;

use mysql_xdevapi\Exception;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Nette\Utils\DateTime;
use Nette\Utils\Strings;
use Tracy\Debugger;

class TransportPresenter  extends \App\APIModule\Presenters\BaseAPI{

    /**
     * @inject
     * @var \App\Model\ArraysManager
     */
    public $ArraysManager;

    /**
     * @inject
     * @var \App\Model\PricelistManager
     */
    public $PricelistManager;

    /**
     * @inject
     * @var \App\Model\PricelistBondsManager
     */
    public $PriceListBondsManager;


    /**
     * @inject
     * @var \App\Model\DocumentsManager
     */
    public $DocumentsManager;

    /**
     * @inject
     * @var \App\Model\EmailingTextManager
     */
    public $EmailingTextManager;

    /**
     * @inject
     * @var \App\Model\EmailingManager
     */
    public $EmailingManager;

    /**
     * @inject
     * @var \App\Model\PartnersManager
     */
    public $PartnersManager;



    /**
     * @inject
     * @var \App\Model\TransportManager
     */
    public $TransportManager;


    /**
     * @inject
     * @var \App\Model\TransportDocsManager
     */
    public $TransportDocsManager;

    /**
     * @inject
     * @var \App\Model\TransportItemsBackManager
     */
    public $TransportItemsBackManager;

    /**
     * @inject
     * @var \App\Model\TransportCashManager
     */
    public $TransportCashManager;

    /**
     * @inject
     * @var \App\Model\DeliveryNotePaymentsManager
     */
    public $DeliveryNotePaymentsManager;

    /**
     * @inject
     * @var \App\Model\DeliveryNoteManager
     */
    public $DeliveryNoteManager;

    /**
     * @inject
     * @var \App\Model\InvoiceManager
     */
    public $InvoiceManager;

    /**
     * @inject
     * @var \App\Model\InvoicePaymentsManager
     */
    public $InvoicePaymentsManager;

    /**
     * @inject
     * @var \App\Model\DeliveryNoteItemsBackManager
     */
    public $DeliveryNoteItemsBackManager;

    /**
     * @inject
     * @var \MainServices\EETService
     */
    public $EETService;

    /**
     * @inject
     * @var \App\Model\EETManager
     */
    public $EETManager;

    /**
     * @inject
     * @var \App\Model\ReportManager
     */
    public $ReportManager;

    /**
     * @inject
     * @var \App\Model\PairedDocsManager
     */
    public $PairedDocsManager;

	public function actionSetDNPayed()
	{
		parent::actionSet();
        $arrData = json_decode($this->dataxml,true);
       // file_put_contents('../log/logjson.txt', $this->dataxml);

        foreach($arrData as $key => $one){
            Debugger::log('actionSetDNPayed cl_company_id = ' . $this->cl_company_id);
            Debugger::log('cl_transport_id = ' .  $one['Transport_id']);
            Debugger::log('cl_partners_book_id = ' . $one['Id']);

            $tmpData = $this->TransportDocsManager->findAllTotal()->
                                    where(['cl_company_id' => $this->cl_company_id])->
                                    where(['cl_transport_id' => $one['Transport_id'], 'cl_delivery_note_id' => $one['Id']])->
                                    limit(1)->fetch();
            $arrData2 = [];
            $arrData2['payed'] = ($one['Payed'] == 'true') ? 1 : 0;
            $arrData2['delivered'] = ($one['Delivered'] == 'true') ? 1 : 0;
            /*if ($arrData['payed']) {
                $arrData['price_payed'] = $tmpData->cl_delivery_note->price_e2_vat;
            }else{
                $arrData['price_payed'] = 0;
            }*/
            if ($tmpData){
                $tmpData->update($arrData2);
            }
        }
        echo('OK');
		$this->terminate();
	}

    public function actionGet()
    {
        parent::actionGet();
    }


	public function actionGetCurrent($cl_transport_types_name = NULL)
	{
		parent::actionGetAll();
		
        $transport_id = $this->TransportManager->findAllTotal()->
                                    where('cl_transport.cl_company_id = ?',$this->cl_company_id)->
                                    where('cl_transport_types.name = ?', $cl_transport_types_name)->
                                    where('cl_status.s_fin = 0 AND cl_status.s_storno = 0')->
                                    order('cl_transport.id DESC')->
                                    limit(1)->fetchAll();
        if ($transport_id) {
            $arrData = [];
            foreach ($transport_id as $key => $one) {
                $arrData[] = ['id' => $key];
            }
            //bdump($arrData);
            $this->sendJson($arrData, \Nette\Utils\Json::PRETTY);
        }else{
            echo('<error>No data</error>');
            $this->terminate();
        }

	}

	public function actionGetNew()
	{
		parent::actionGetNew();
		$tmpData = $this->DeliveryNoteManager->findAllTotal()->
										where(['cl_company_id' => $this->cl_company_id])->
										where('cl_delivery_note.changed >= ? OR cl_delivery_note.created >= ?', $this->sync_last, $this->sync_last);
		if (!empty($this->dataxml))
		{
			$tmpData = $tmpData->where('cl_delivery_note.id NOT IN(?)', $this->dataxml);
		}
		

		$tmpDataArr = [];
		foreach($tmpData as $key => $one)
		{
			$cl_delivery_note = $one->toArray();
			$tmpDataArr['cl_delivery_note'][] = $cl_delivery_note;

		}
		//dump($tmpDataArr);
		//die;
			$xml = Array2XML::createXML('cl_delivery_note', $tmpDataArr);
			//dump($xml);
			echo $xml->saveXML();		

		$this->terminate();		
	}	
	
	public function actionGetAll()
	{
	    parent::actionGetAll();    
	    $data = $this->DeliveryNoteManager->findAllTotal()->select('cl_delivery_note.id, cl_delivery_note.dn_number, cl_partners_book.company')->
							    where('cl_delivery_note.cl_company_id = ?',$this->cl_company_id)->
                                order('cl_partners_book.company, cl_delivery_note.dn_number')->fetchAll();
	    $arrData = [];
	    foreach ($data as $key => $one)
	    {
		    $arrData[] = ['id' => $key, 'DnNumber' => $one['dn_number'], 'Company' => $one['company']];
	    }
	    bdump($arrData);
	    $this->sendJson($arrData, \Nette\Utils\Json::PRETTY);
	}

    public function actionGetCashAll($cl_transport_types_name = null)
    {
        parent::actionGetAll();
        $transport_id = $this->TransportManager->findAllTotal()->
                                        where('cl_transport.cl_company_id = ?',$this->cl_company_id)->
                                        where('cl_transport_types.name = ?', $cl_transport_types_name)->
                                        where('cl_status.s_fin = 0 AND cl_status.s_storno = 0')->
                                        order('cl_transport.id DESC')->
                                        limit(1)->fetch();
        if ($transport_id) {

            $data = $this->TransportCashManager->findAllTotal()->
                                        where('cl_company_id = ?', $this->cl_company_id)->
                                        where('cl_transport_id = ?', $transport_id->id)->
                                        order('item_order,id')->fetchAll();
            $arrData = [];
            foreach ($data as $key => $one) {
                $arrData[] = ['Id' => $key, 'Transport_id' => $one['cl_transport_id'], 'Item_order' => $one['item_order'],
                    'Date' => $one['date'], 'Amount' => $one['amount'], 'Description' => $one['description']];
            }
            //bdump($arrData);
            $this->sendJson($arrData, \Nette\Utils\Json::PRETTY);
        }else{
            echo('<error>Empty data result</error>');
        }
    }

    public function actionGetTransport($cl_transport_types_name = null)
    {
        parent::actionGetAll();
        $transport_id = $this->TransportManager->findAllTotal()->
                            where('cl_transport.cl_company_id = ?',$this->cl_company_id)->
                            where('cl_transport_types.name = ?', $cl_transport_types_name)->
                            where('cl_status.s_fin = 0 AND cl_status.s_storno = 0')->
                            order('cl_transport.id DESC')->
                            limit(1)->fetch();
        if ($transport_id) {
            $data = $this->TransportDocsManager->findAllTotal()->select('cl_delivery_note.id, cl_delivery_note.dn_number, cl_delivery_note.cl_partners_book.company,
                                                cl_delivery_note.issue_date, cl_delivery_note.price_e2_vat, cl_transport_docs.cl_transport_id')->
                                where('cl_transport_docs.cl_company_id = ?', $this->cl_company_id)->
                                where('cl_transport_docs.cl_transport_id = ?', $transport_id->id)->
                                where('cl_transport_docs.cl_delivery_note_id IS NOT NULL')->
                                order('cl_delivery_note.cl_partners_book.company, cl_delivery_note.dn_number')->fetchAll();
            $arrData = [];
            foreach ($data as $key => $one) {
                $arrData[] = ['id' => $key, 'Transport_id' => $one['cl_transport_id'], 'DnNumber' => $one['dn_number'], 'Company' => $one['company'],
                                    'Issue_date' => $one['issue_date'], 'Price_e2_vat' => $one['price_e2_vat'], 'Payed' => $one['payed']];
            }
            $this->sendJson($arrData, \Nette\Utils\Json::PRETTY);
        }else{
            echo('<error>Empty data result</error>');
        }
    }

    public function actionGetTransportPartners($cl_transport_types_name = null)
    {
        parent::actionGetAll();
        $data = $this->TransportDocsManager->findAllTotal()->
                                select('cl_transport_docs.cl_transport_id,
                                                cl_delivery_note.cl_partners_book_id,cl_delivery_note.cl_partners_book.company, cl_delivery_note.cl_partners_branch.b_name,
                                                cl_delivery_note.cl_partners_branch.b_street,
                                                cl_delivery_note.cl_partners_branch.b_city')->
                                where('cl_transport_docs.cl_company_id = ?',$this->cl_company_id)->
                                where('cl_transport.cl_transport_types.name = ?', $cl_transport_types_name)->
                                where('cl_transport.cl_status.s_fin = 0 AND cl_transport.cl_status.s_storno = 0')->
                                where('cl_transport_docs.delivered = 0')->
                                where('cl_transport_docs.cl_delivery_note_id IS NOT NULL')->
                                order('cl_transport_docs.item_order')->
                                group('cl_delivery_note.cl_partners_book_id ')->
                                fetchAll();
        //21022023 TH - řazení podle pořadí vložení do dopravy
        //order('cl_delivery_note.cl_partners_book.company ASC')->
        $arrData = [];
        foreach ($data as $key => $one) {
            $arrData[] = ['Transport_id' => $one['cl_transport_id'],
                                'Id' => $one['cl_partners_book_id'], 'Company' => $one['company'], 'B_name' => $one['b_name'],
                'B_street' => $one['b_street'], 'B_city' => $one['b_city']];
        }
        $this->sendJson($arrData, \Nette\Utils\Json::PRETTY);
    }

    /** return all delivery notes for given transport and customer
     * @param null $cl_transport_types_name
     * @param null $cl_partners_book_id
     * @throws \Nette\Application\AbortException
     */
    public function actionGetTransportPartner($cl_transport_types_name = null, $cl_partners_book_id = null)
    {
        parent::actionGetAll();
            $data = $this->TransportDocsManager->findAllTotal()->select('cl_transport_docs.id AS cl_transport_docs_id, cl_transport_docs.cl_transport_id,cl_transport_docs.payed, cl_transport_docs.price_e2_vat_back,
                                                                        cl_delivery_note.id, cl_delivery_note.dn_number, cl_delivery_note.cl_partners_book.company,cl_delivery_note.issue_date, 
                                                                        cl_delivery_note.issue_date, cl_delivery_note.price_e2_vat, cl_delivery_note.price_payed,cl_delivery_note.cl_currencies.currency_code')->
                                                where('cl_transport_docs.cl_company_id = ?', $this->cl_company_id)->
                                                where('cl_transport.cl_transport_types.name = ?', $cl_transport_types_name)->
                                                where('cl_delivery_note.cl_partners_book_id = ?', $cl_partners_book_id)->
                                                where('(cl_transport_docs.delivered = 1 AND cl_transport.cl_transport_types.name = ?)', $cl_transport_types_name)->
                                                where('cl_delivery_note.price_e2_vat > cl_delivery_note.price_payed')->
                                                order('cl_delivery_note.cl_partners_book.company, issue_date DESC, cl_delivery_note.dn_number')->fetchAll();
            //AND cl_delivery_note.price_payed < cl_delivery_note.price_e2_vat  AND cl_transport_docs.payed = 0
        //where('((cl_transport_docs.payed = 0 AND cl_transport_docs.delivered = 1) OR (cl_transport_docs.payed = 0 AND cl_transport.cl_transport_types.name = ?))', $cl_transport_types_name)->
            $arrData = [];
            foreach ($data as $key => $one) {
                $arrData[] = ['Id' => $key,
                                    'Dn_number' => $one['dn_number'], 'Company' => $one['company'], 'Transport_id' => $one['cl_transport_id'], 'Payed' => $one['payed'], 'Issue_date' => $one['issue_date'],
                                    'Issue_date' => $one['issue_date'], 'Price_e2_vat' => $one['price_e2_vat'], 'Price_e2_vat_back' => $one['price_e2_vat_back'], 'Price_payed' => $one['price_payed'], 'Currency_code' => $one['currency_code']];
            }
            $this->sendJson($arrData, \Nette\Utils\Json::PRETTY);
    }

    /** return current delivery notes for given transport type and transport id and customer
     * @param null $cl_transport_types_name
     * @param null $cl_partners_book_id
     * @param null $cl_transport_id
     * @throws \Nette\Application\AbortException
     */
    public function actionGetTransportPartnerCurrent($cl_transport_types_name = null, $cl_partners_book_id = null, $cl_transport_id = null)
    {
        parent::actionGetAll();
        $data = $this->TransportDocsManager->findAllTotal()->select('cl_transport_docs.id AS cl_transport_docs_id, cl_transport_docs.cl_transport_id,cl_transport_docs.payed, cl_transport_docs.delivered , cl_transport_docs.price_e2_vat_back,
                                                                        cl_delivery_note.id, cl_delivery_note.dn_number, cl_delivery_note.cl_partners_book.company,cl_delivery_note.issue_date,
                                                                        IF(cl_transport_docs.only_for_pay = 1, "onlypay", IF(cl_delivery_note.cl_payment_types.payment_type != 1, "transfer",  "normal")) AS item_type, 
                                                                        cl_delivery_note.issue_date, cl_delivery_note.price_e2_vat, cl_delivery_note.price_payed AS price_payed_dn, cl_transport_docs.price_payed AS price_payed, cl_delivery_note.cl_currencies.currency_code')->
                                                where('cl_transport_docs.cl_company_id = ?', $this->cl_company_id)->
                                                where('cl_transport_docs.cl_transport_id = ?', $cl_transport_id)->
                                                where('cl_delivery_note.cl_partners_book_id = ?', $cl_partners_book_id)->
                                                where('cl_transport.cl_status.s_fin = 0 AND cl_transport.cl_status.s_storno = 0')->
                                                where('cl_transport_docs.delivered = 0')->
                                                order('cl_delivery_note.cl_partners_book.company, item_type, issue_date DESC, cl_delivery_note.dn_number')->limit(50)->fetchAll();
        //where('cl_transport.cl_transport_types.name = ?', $cl_transport_types_name)->
        //       where('cl_transport_docs.cl_transport_id = ?', $cl_transport_id)->
        $arrData = [];
        foreach ($data as $key => $one) {
            $arrData[$key] = ['Id' => $key,
                'Dn_number' => $one['dn_number'], 'Company' => $one['company'], 'Transport_id' => $one['cl_transport_id'], 'Payed' => $one['payed'],'Delivered' => $one['delivered'], 'Issue_date' => $one['issue_date'], 'ItemType' => $one['item_type'],
                'Issue_date' => $one['issue_date'], 'Price_e2_vat' => $one['price_e2_vat'], 'Price_payed' => $one['price_payed'], 'Price_payed_dn' => $one['price_payed_dn'], 'Price_e2_vat_back' => $one['price_e2_vat_back'], 'Currency_code' => $one['currency_code']];
        }
        //dump($arrData);

        $data = $this->TransportDocsManager->findAllTotal()->select('cl_transport_docs.id AS cl_transport_docs_id, cl_transport_docs.cl_transport_id,cl_transport_docs.payed, cl_transport_docs.delivered , cl_transport_docs.price_e2_vat_back AS price_e2_vat_back,
                                                                        cl_delivery_note.id, cl_delivery_note.dn_number, cl_delivery_note.cl_partners_book.company,cl_delivery_note.issue_date, 
                                                                        IF(commission = 1, "commission", "onlypay") AS item_type, 
                                                                        cl_delivery_note.issue_date, cl_delivery_note.price_e2_vat, cl_delivery_note.price_payed AS price_payed_dn, cl_delivery_note.cl_currencies.currency_code')->
                                                    where('cl_delivery_note.cl_company_id = ?', $this->cl_company_id)->
                                                    where('cl_delivery_note.cl_partners_book_id = ?', $cl_partners_book_id)->
                                                    where('((cl_transport_docs.delivered = 1 ) OR cl_delivery_note.commission = 1)')->
                                                    where('cl_delivery_note.price_e2_vat > cl_delivery_note.price_payed')->
                                                    order('cl_delivery_note.cl_partners_book.company, issue_date DESC, cl_delivery_note.dn_number')->limit(50)->fetchAll();
        //where('((cl_transport_docs.delivered = 1 AND cl_transport.cl_transport_types.name = ?) OR cl_delivery_note.commission = 1)', $cl_transport_types_name)->
        //AND cl_delivery_note.price_payed < cl_delivery_note.price_e2_vat  AND cl_transport_docs.payed = 0
        //where('((cl_transport_docs.payed = 0 AND cl_transport_docs.delivered = 1) OR (cl_transport_docs.payed = 0 AND cl_transport.cl_transport_types.name = ?))', $cl_transport_types_name)->
        //                                                where('cl_transport.cl_transport_types.name = ?', $cl_transport_types_name)->
        foreach ($data as $key => $one) {
            if (!array_key_exists($key, $arrData)){
                $arrData[$key] = ['Id' => $key,
                    'Dn_number' => $one['dn_number'], 'Company' => $one['company'], 'Transport_id' => $one['cl_transport_id'], 'Payed' => $one['payed'], 'Delivered' => $one['delivered'], 'Issue_date' => $one['issue_date'], 'ItemType' => $one['item_type'],
                    'Issue_date' => $one['issue_date'], 'Price_e2_vat' => $one['price_e2_vat'], 'Price_e2_vat_back' => $one['price_e2_vat_back'], 'Price_payed_dn' => $one['price_payed_dn'], 'Currency_code' => $one['currency_code']];
            }
        }
        //dump($arrData);
        //die;

        $arrResult = [];
        foreach ($arrData as $one){
            $arrResult[] = $one;
        }

        $this->sendJson($arrResult, \Nette\Utils\Json::PRETTY);
    }

    public function actionGetPricelist($cl_transport_types_name = null, $cl_partners_book_id = null, $cl_delivery_note_id = null)
    {
        parent::actionGetAll();
        $data = $this->TransportDocsManager->findAllTotal()->select('cl_delivery_note:cl_delivery_note_items.cl_pricelist_id, 
                                                            0 AS cl_delivery_note_id,
                                                            cl_delivery_note:cl_delivery_note_items.cl_pricelist.identification,
                                                            cl_delivery_note:cl_delivery_note_items.item_label,
                                                            cl_delivery_note:cl_delivery_note_items.cl_storage_id,
                                                            cl_delivery_note:cl_delivery_note_items.cl_pricelist.ean_code,
                                                            cl_delivery_note:cl_delivery_note_items.units,
                                                            cl_delivery_note:cl_delivery_note_items.price_e2_vat AS price_e2_vat,
                                                            cl_delivery_note:cl_delivery_note_items.price_e2_vat/cl_delivery_note:cl_delivery_note_items.quantity AS price_vat,
                                                            cl_delivery_note:cl_delivery_note_items.quantity')->
                                        where('cl_transport_docs.cl_company_id = ?', $this->cl_company_id)->
                                        where('cl_delivery_note.commission = 0')->
                                        where('cl_delivery_note.id = ?', $cl_delivery_note_id)->
                                        where('cl_transport.cl_transport_types.name = ?', $cl_transport_types_name)->
                                        where('cl_delivery_note.cl_partners_book_id = ?', $cl_partners_book_id)->
                                        order('cl_delivery_note:cl_delivery_note_items.cl_pricelist.cl_pricelist_group.order_on_docs ASC, cl_delivery_note:cl_delivery_note_items.cl_pricelist.item_label ASC')->fetchAll();
        $arrData = [];
        foreach ($data as $key => $one) {
            $arrData[] = ['Id' => $one['cl_pricelist_id'], 'Identification' => $one['identification'], 'Ean_code' => $one['ean_code'], 'Storage_id' => $one['cl_storage_id'],
                'Item_label' => $one['item_label'], 'Quantity' => $one['quantity'], 'Units' => $one['units'], 'Price_vat' => $one['price_vat'], 'Price_e2_vat' => $one['price_e2_vat'],
                'commission' => 0, 'ItemType' => 'normal', 'Delivery_note_id' => $one['cl_delivery_note_id']];
        }

        //komise
        $data = $this->DeliveryNoteManager->findAllTotal()->select(':cl_delivery_note_items.cl_pricelist_id, 
                                                            :cl_delivery_note_items.cl_delivery_note_id, 
                                                            :cl_delivery_note_items.cl_pricelist.identification,
                                                            :cl_delivery_note_items.item_label,
                                                            :cl_delivery_note_items.cl_storage_id,
                                                            :cl_delivery_note_items.cl_pricelist.ean_code,
                                                            :cl_delivery_note_items.units,
                                                            :cl_delivery_note_items.price_e2_vat/:cl_delivery_note_items.quantity AS price_vat,
                                                            :cl_delivery_note_items.price_e2_vat AS price_e2_vat,
                                                            :cl_delivery_note_items.quantity')->
                                            where('cl_delivery_note.cl_company_id = ?', $this->cl_company_id)->
                                            where('commission = 1 AND cl_status.s_fin = 1 AND cl_status.s_storno != 1 AND cl_delivery_note.cl_invoice_id IS NULL')->
                                            where('cl_delivery_note.id = ?', $cl_delivery_note_id)->
                                            where('cl_delivery_note.cl_partners_book_id = ?', $cl_partners_book_id)->
                                            order(':cl_delivery_note_items.cl_pricelist.cl_pricelist_group.order_on_docs ASC, :cl_delivery_note_items.cl_pricelist.item_label ASC')->fetchAll();

        foreach ($data as $key => $one) {
            $arrData[] = ['Id' => $one['cl_pricelist_id'], 'Identification' => $one['identification'], 'Ean_code' => $one['ean_code'], 'Storage_id' => $one['cl_storage_id'],
                'Item_label' => $one['item_label'], 'Quantity' => $one['quantity'], 'Units' => $one['units'], 'Price_vat' => $one['price_vat'], 'Price_e2_vat' => $one['price_e2_vat'],
                'commission' => 1, 'ItemType' => 'commission', 'Delivery_note_id' => $one['cl_delivery_note_id']];
        }        
        //dump($arrData);
        //die;
        $this->sendJson($arrData, \Nette\Utils\Json::PRETTY);
    }

    public function actionGetPackagelist()
    {
        parent::actionGetAll();
        $data = $this->PricelistManager->findAllTotal()->select('cl_pricelist.id, 
                                                            identification,
                                                            item_label,
                                                            ean_code,
                                                            unit,
                                                            price_vat, cl_storage_id,
                                                            1 AS quantity')->
                                            where('cl_pricelist.cl_company_id = ?', $this->cl_company_id)->
                                            where('cl_pricelist_group.is_return_package = 1')->
                                            order('cl_pricelist_group.order_on_docs ASC, item_label ASC')->fetchAll();
        $arrData = [];
        //dump($data);
        foreach ($data as $key => $one) {
            //dump($one);
            $arrData[] = ['Id' => $one['id'], 'Identification' => $one['identification'], 'Ean_code' => $one['ean_code'],
                'Storage_id' => (is_null($one['cl_storage_id']) ? 0 : $one['cl_storage_id']) ,
                'Item_label' => $one['item_label'], 'ItemType' => 'packages', 'Quantity' => $one['quantity'], 'Units' => $one['unit'], 'Price_vat' => $one['price_vat'], 'Price_e2_vat' => $one['price_vat']];        }
        $this->sendJson($arrData, \Nette\Utils\Json::PRETTY);
    }

        


    public function actionSetItemback($cl_transport_id = NULL, $cl_pricelist_id = NULL, $cl_partners_book_id = NULL, $cl_storage_id = NULL, $price_vat, $quantity,
                                        $cl_delivery_note_id = NULL, $commission = 0, $price_to_pay = 0, $item_type = 'normal')
    {
        parent::actionSet();
        $quantity = str_ireplace(',','.', $quantity);
        $price_vat = str_ireplace(',','.', $price_vat);
        if ($cl_delivery_note_id == 0)
            $cl_delivery_note_id = NULL;

        try {
            $tmpCountItems = $this->TransportItemsBackManager->findAllTotal()->
                                                    where('cl_company_id = ?', $this->cl_company_id)->
                                                    where('cl_transport_id = ?', $cl_transport_id)->
                                                    count('id');

            $tmpData = $this->TransportItemsBackManager->findAllTotal()->
                                where(['cl_company_id' => $this->cl_company_id,
                                            'cl_transport_id' => $cl_transport_id,
                                            'cl_pricelist_id' => $cl_pricelist_id,
                                            'cl_delivery_note_id' => $cl_delivery_note_id,
                                            'cl_partners_book_id' => $cl_partners_book_id])->fetch();


            $tmpPricelist = $this->PricelistManager->findAllTotal()->
                                where('cl_company_id = ?', $this->cl_company_id)->
                                where('id = ?', $cl_pricelist_id)->fetch();



            if ($tmpPricelist) {
                $arrData = [];
                $arrData['cl_company_id']       = $this->cl_company_id;
                $arrData['cl_transport_id']     = $cl_transport_id;

                $arrData['cl_delivery_note_id'] = $cl_delivery_note_id;
                $arrData['cl_pricelist_id']     = $cl_pricelist_id;
                $arrData['cl_storage_id']       = $cl_storage_id;
                $arrData['item_label']          = $tmpPricelist->item_label;
                $arrData['units']               = $tmpPricelist->unit;
                $arrData['vat']                 = $tmpPricelist->vat;
                $arrData['cl_partners_book_id'] = $cl_partners_book_id;
                $arrData['price_s']             = $tmpPricelist['price_s'];
                $arrData['price_e']             = $price_vat / (1 + $tmpPricelist->vat / 100);
                $arrData['price_e2']            = $price_vat / (1 + $tmpPricelist->vat / 100) * $quantity ;
                $arrData['price_e2_vat']        = $price_vat * $quantity;
                $arrData['quantity']            = $quantity;
                $arrData['commission']          = $commission;
                $arrData['item_type']           = $item_type;

                if (!$tmpData) {
                    $arrData['item_order']      = $tmpCountItems + 1;
                    $tmpData = $this->TransportItemsBackManager->insertForeign($arrData);

                } else {
                    $arrData['id'] = $tmpData->id;
                    $this->TransportItemsBackManager->updateForeign($arrData);

                }
                /*02.07.2020 - bonds solution */
                //$tmpBonds = $this->PriceListBondsManager->findAllTotal()->
                //                                where('cl_company_id = ?', $this->cl_company_id)->
                //                                where(array('cl_pricelist_bonds_id' => $cl_pricelist_id));
                $tmpBonds = $this->PriceListBondsManager->findAllTotal()->
                                                where('cl_company_id = ?', $this->cl_company_id)->
                                                where('cl_pricelist_bonds_id = ? AND limit_for_bond <= ?', $cl_pricelist_id, $quantity);



                foreach ($tmpBonds as $key => $oneBond) {
                    //found in cl_invoice_items if there already is bonded item
                    $tmpItemsBackItemBond = $this->TransportItemsBackManager->findAllTotal()->
                                                where('cl_company_id = ?', $this->cl_company_id)->
                                                where(['cl_parent_bond_id' => $tmpData['id'],
                                                            'cl_pricelist_id' => $oneBond->cl_pricelist_id])->fetch();
                    $newItem = [];
                    $newItem['cl_company_id']   = $this->cl_company_id;
                    $newItem['cl_partners_book_id'] = $cl_partners_book_id;
                    $newItem['cl_transport_id'] = $cl_transport_id;
                    $newItem['cl_delivery_note_id'] = $cl_delivery_note_id;
                    $newItem['cl_storage_id']       = $cl_storage_id;
                    $newItem['item_order'] = $tmpData['item_order'] + 1;
                    $newItem['cl_pricelist_id'] = $oneBond->cl_pricelist_id;
                    $newItem['item_label'] = $oneBond->cl_pricelist->item_label;
                    $newItem['quantity'] = $oneBond->quantity *  ($oneBond->multiply == 1) ? $tmpData['quantity'] : 1 ;//$tmpData['quantity'];
                    $newItem['units'] = $oneBond->cl_pricelist->unit;
                    $newItem['price_s'] = $oneBond->cl_pricelist->price_s;
                    $newItem['price_e'] = $oneBond->cl_pricelist->price;
                    $newItem['discount'] = $oneBond->discount;
                    $newItem['price_e2'] = ($oneBond->cl_pricelist->price * (1 - ($oneBond->discount / 100))) * ($oneBond->quantity * $tmpData['quantity']);
                    $newItem['vat'] = $oneBond->cl_pricelist->vat;
                    $newItem['price_e2_vat'] = $oneBond->cl_pricelist->price_vat * (1 - ($oneBond->discount / 100)) * ($oneBond->quantity * $tmpData['quantity']);
                    $newItem['price_e_type'] = $tmpData['price_e_type'];
                    $newItem['cl_parent_bond_id'] = $tmpData['id'];
                    $newItem['commission']          = $commission;
                    $newItem['item_type']           = $item_type;
                    //bdump($newItem);
                    if (!$tmpItemsBackItemBond) {
                        $tmpNew = $this->TransportItemsBackManager->insertForeign($newItem);
                        $tmpId = $tmpNew->id;
                    } else {
                        $newItem['id'] = $tmpItemsBackItemBond->id;
                        $tmpNew = $this->TransportItemsBackManager->updateForeign($newItem);
                        $tmpId = $tmpItemsBackItemBond->id;
                    }
                }
                /* end of bonds */

                $tmpItems = $this->TransportItemsBackManager->findAllTotal()->
                                    where(['cl_company_id' => $this->cl_company_id,
                                        'cl_transport_id' => $cl_transport_id,
                                        'cl_delivery_note_id' => $cl_delivery_note_id,
                                        'cl_partners_book_id' => $cl_partners_book_id])->
                                    where('commission = 0')->
                                    select('SUM(price_e2_vat) AS price_e2_vat_back')->fetch();
                if ($tmpItems){
                    $tmpDoc = $this->TransportDocsManager->findAllTotal()->where('cl_company_id = ?', $this->cl_company_id)->
                                                    where('cl_delivery_note_id = ?', $cl_delivery_note_id);
                    $tmpDoc->update(['price_e2_vat_back' => $tmpItems->price_e2_vat_back]);
                }

                echo('ok');
            }
        }catch (Exception $e)
        {
            echo('<error>Error on server: '.$e->getMessage().' </error>');
        }
        $this->terminate();
    }


    public function actionGetItemback($cl_transport_id = NULL, $cl_partners_book_id = NULL, $cl_delivery_note_id = NULL)
    {
        parent::actionSet();
        try {
            $tmpData = $this->TransportItemsBackManager->findAllTotal()->
                                    select('cl_transport_items_back.id, cl_pricelist.identification, cl_transport_items_back.item_label, cl_transport_items_back.units,cl_transport_items_back.cl_storage_id,
                                                    cl_transport_items_back.cl_pricelist_id, cl_pricelist.ean_code, cl_transport_items_back.quantity, (cl_transport_items_back.price_e2_vat / cl_transport_items_back.quantity) AS price_vat,
                                                    cl_transport_items_back.price_e2_vat AS price_e2_vat,cl_transport_items_back.item_type,
                                                    cl_transport_items_back.cl_delivery_note_id, cl_transport_items_back.commission')->
                                    where('cl_transport_items_back.cl_company_id = ?', $this->cl_company_id)->
                                    where('cl_transport_id = ?', $cl_transport_id)->
                                    where('cl_transport_items_back.cl_partners_book_id = ?', $cl_partners_book_id)->
                                    order('cl_transport_items_back.item_order ASC');
                                    //order('cl_transport_items_back.item_type DESC, cl_transport_items_back.item_label ASC,cl_transport_items_back.id DESC');

            if (!is_null($cl_delivery_note_id) && $cl_delivery_note_id > 0){
                $tmpData = $tmpData->where('cl_transport_items_back.cl_delivery_note_id = ?', $cl_delivery_note_id);
            }
            $tmpData = $tmpData->fetchAll();

            $arrData = [];
            foreach ($tmpData as $key => $one) {
                $arrData[] = [
                    'Transport_items_back_id' => $one['id'],
                    'Id' => $one['cl_pricelist_id'], 'Identification' => $one['identification'], 'Ean_code' => $one['ean_code'], 'Storage_id' => $one['cl_storage_id'],
                    'Item_label' => $one['item_label'], 'Quantity' => $one['quantity'], 'Units' => $one['units'], 'Price_e2_vat' => $one['price_e2_vat'], 'Price_vat' => $one['price_vat'],
                    'Delivery_note_id' => (is_null($one['cl_delivery_note_id']) ? 0 : $one['cl_delivery_note_id']), 'commission' => $one['commission'], 'ItemType' => $one['item_type'],];
            }
           // dump($arrData);
            $this->sendJson($arrData, \Nette\Utils\Json::PRETTY);

        }catch (Exception $e)
        {
            echo('<error>Error on server: '.$e->getMessage().' </error>');
            $this->terminate();
        }
    }

    public function actionEraseItemback($cl_transport_id = NULL, $cl_pricelist_id = NULL, $cl_transport_items_back_id = NULL, $price_e2_vat, $quantity)
    {
        parent::actionSet();
        try {
            $tmpData = $this->TransportItemsBackManager->findAllTotal()->
                                where('cl_company_id = ?', $this->cl_company_id)->
                                where('cl_transport_id = ?', $cl_transport_id)->
                                where('cl_pricelist_id = ?', $cl_pricelist_id)->
                                where('id = ?', $cl_transport_items_back_id)->fetch();
            if ($tmpData){
                $tmpData->delete();
                echo('ok');
            }else{
                echo('<error>Erase error</error>');
            }
            $this->terminate();
        }catch (Exception $e)
        {
            echo('<error>Error on server: '.$e->getMessage().' </error>');
            $this->terminate();
        }

    }

    public function actionSetItemsback()
    {
        parent::actionSet();
        echo('OK');
        $this->terminate();
    }


    public function actionSetCash()
    {
        parent::actionSet();
        $one = json_decode($this->dataxml,true);
       // file_put_contents('../log/logjson.txt', $this->dataxml);

        //foreach($arrData as $key => $one){
            $tmpParent = $this->TransportManager->findAllTotal()->
                                            where(['cl_company_id' => $this->cl_company_id,
                                                'id' => $one['Transport_id']])->
                                            limit(1)->fetch();
            if ($tmpParent) {
                $tmpData = $this->TransportCashManager->findAllTotal()->
                                        where(['cl_company_id' => $this->cl_company_id,
                                            'cl_transport_id' => $one['Transport_id'],
                                            'id' => $one['Id']])->
                                        limit(1)->fetch();
                $arrData = [];
                $arrData['cl_company_id'] = $this->cl_company_id;
                $arrData['cl_transport_id'] = $one['Transport_id'];
                $arrData['item_order'] = $one['Item_order'];
                $arrData['date'] = $one['Date'];
                $arrData['amount'] = $one['Amount'];
                $arrData['description'] = $one['Description'];
                $arrData['cl_currencies_id'] = $tmpParent->cl_currencies_id;
                if ($tmpData) {
                    //$tmpData->update($arrData);
                    $arrData['id'] = $one['Id'];
                    $this->TransportCashManager->updateForeign($arrData);
                } else {
                    $this->TransportCashManager->insertForeign($arrData);
                }
            }
        //}
        echo('OK');
        $this->terminate();
    }

    public function actionEraseCash()
    {
        parent::actionSet();
        $one = json_decode($this->dataxml,true);
      //  file_put_contents('../log/logjson.txt', $this->dataxml);
        try {
            $tmpData = $this->TransportCashManager->findAllTotal()->
                                where(['cl_company_id' => $this->cl_company_id,
                                    'cl_transport_id' => $one['Transport_id'],
                                    'id' => $one['Id']])->
                                limit(1)->fetch();
            if ($tmpData){
                $tmpData->delete();
                echo('ok');
            }else{
                echo('<error>Erase error</error>');
            }
            $this->terminate();
        }catch (Exception $e)
        {
            echo('<error>Error on server: '.$e->getMessage().' </error>');
            $this->terminate();
        }

    }


    public function actionGetCashSum($cl_transport_types_name = null)
    {
        parent::actionGetAll();
        $totalSum = $this->TransportManager->transportSum( $cl_transport_types_name, $this->cl_company_id);
        echo('<result>' . $totalSum . '</result>');
        $this->terminate();
    }



    public function actionSetDnPay()
    {
        parent::actionSet();
        $one = json_decode($this->dataxml,true);
       // file_put_contents('../log/logjson.txt', $this->dataxml);

        //foreach($arrData as $key => $one){
        //$one['Transport_docs_id']
        $tmpParent = $this->DeliveryNoteManager->findAllTotal()->
                                            where(['cl_company_id' => $this->cl_company_id,
                                                'id' => $one['Delivery_note_id']])->
                                            limit(1)->fetch();
        if ($tmpParent) {
            $tmpData = $this->TransportDocsManager->findAllTotal()->
                                    where(['cl_company_id' => $this->cl_company_id,
                                            'cl_transport_id' => $one['Transport_docs_id'],
                                            'cl_delivery_note_id' => $one['Delivery_note_id']])->
                                    limit(1)->fetch();
            $arrData                        = [];
            $arrData['cl_company_id']       = $this->cl_company_id;
            $arrData['cl_transport_id']     = $one['Transport_docs_id'];
            $arrData['cl_delivery_note_id'] = $one['Delivery_note_id'];
            $arrData['price_payed']         = $one['Price_payed'];
            //$arrData['delivered']            = $one['Delivered'];
             /*  if ($arrData['price_payed'] == 0){
                $arrData['payed'] = 0;
            }else {
                $arrData['payed'] = 1;
            }*/

            if ($tmpData) {
                //$tmpData->update($arrData);
                $arrData['id']                  = $tmpData['id'];
                $this->TransportDocsManager->updateForeign($arrData);
            } else {
                $arrData['only_for_pay']        = 1;
                $this->TransportDocsManager->insertForeign($arrData);
            }
        }
        //}
        echo('OK');
        $this->terminate();
    }


    public function actionMakeInvoice($cl_transport_id = NULL, $cl_partners_book_id = NULL)
    {
        parent::actionSet();
        /* 1. find delivered cl_transport_docs, but not only payed dn = it means that cl_transport_docs.only_for_pay == 0
         * 2. add items back to main_dn
         * 3. create invoices for all dn
         * 4. send them by email
         */

        $arrData                = json_decode($this->dataxml,true);
/*        $str = '';
        foreach($arrData[0] as $key => $one){
            if (is_array($one)){
                $str .= '<<' . $key . '>>' ;
                foreach($one as $key2 => $one2) {
                    $str .= '<' . $key2 . '>' . $one2 . '</' . $key2 . '>';
                }
                $str .= '<</' . $key . '>>' ;
            }else {
                $str .= '<' . $key . '>' . $one . '</' . $key . '>';
            }
        }
        Debugger::log('dataxml = ' .$str);
*/
        foreach($arrData[0] as $key => $one){
            $cl_transport_id = $one;
        }

        foreach($arrData[1] as $key => $one){
            $cl_partners_book_id = $one;
        }

        $mySection = $this->session->getSection('company');
        $mySection['cl_company_id'] = $this->cl_company_id;

        Debugger::log('actionMakeInvoice cl_company_id = ' . $mySection['cl_company_id']);
        Debugger::log('cl_transport_id = ' . $cl_transport_id);
        Debugger::log('cl_partners_book_id = ' . $cl_partners_book_id);
        //invoices are made only from delivery notes their cl_payment_types.dn_to_cash is not set to 1
        $mainData = $this->TransportDocsManager->findAllTotal()->where(['cl_transport_docs.cl_transport_id ' => $cl_transport_id,
                                                                            'cl_delivery_note.cl_partners_book_id ' => $cl_partners_book_id]);
        //add items back to main_dn
        /*if ($mainData){
            $mainDnId = $mainData->cl_delivery_note_id;
            $oldPrice_e2_vat = $mainData->cl_delivery_note->price_e2_vat;
        }else{
            $mainDnId = null;
            $oldPrice_e2_vat = 0;
        }*/
        /*03072020*/
        //Debugger::log('transports to close = ' . count($mainData->fetchAll()));

        foreach($mainData as $key => $one){
            //set items back to delivery note
            $itemsBack = $this->TransportItemsBackManager->findAllTotal()->where(['cl_partners_book_id ' => $cl_partners_book_id, 'commission' => 0,
                                                                 'cl_transport_id' =>$cl_transport_id, 'cl_delivery_note_id' => $one->cl_delivery_note_id]);
           //echo($cl_partners_book_id);
           //echo($cl_transport_id);
            //Debugger::log('items back = ' . count($mainData->fetchAll()));
            foreach($itemsBack as $keyI => $oneI){
                $arrData = $oneI->toArray();
                unset($arrData['id']);
                unset($arrData['cl_transport_id']);
                unset($arrData['cl_parent_bond_id']);
                unset($arrData['cl_cash_id']);
                unset($arrData['commission']);
                unset($arrData['item_type']);
                $arrData['cl_transport_items_back_id'] = $oneI->id;
                //$arrData['cl_delivery_note_id'] = $mainDnId;
                $tmpDnBack = $this->DeliveryNoteItemsBackManager->findAllTotal()->where('cl_transport_items_back_id = ?', $oneI->id)->fetch();
                if ($tmpDnBack){
                    $arrData['id'] = $tmpDnBack['id'];
                    $this->DeliveryNoteItemsBackManager->updateForeign($arrData);
                }else {
                    $this->DeliveryNoteItemsBackManager->insertForeign($arrData);
                }
            }

            $this->DeliveryNoteManager->updateSum($one->cl_delivery_note_id);
            $this->PairedDocsManager->insertOrUpdate(['cl_delivery_note_id' => $one->cl_delivery_note_id, 'cl_transport_id' => $cl_transport_id]);

                //now update cl_transport_docs.price_payed
                //if price_payed < previous cl_delivery_note.price_e2_vat
                //then we have to let price_payed bee, because the payment was partial
                //in other case have to set actual cl_delivery_note.price_e2_vat because it can has new value lowered by returned items
                $tmpDN = $this->DeliveryNoteManager->findAllTotal()->where('id = ?', $one->cl_delivery_note_id)->fetch();
                $tmpNow = new DateTime();
                if ($tmpDN) {
                        $arrDnPay                           = [];
                        $arrDnPay['pay_price']              = $one['price_payed'];
                        $arrDnPay['pay_date']               = $one['created'];
                        $arrDnPay['cl_payment_types_id']    = $one->cl_delivery_note->cl_payment_types_id;
                        $arrDnPay['cl_currencies_id']       = $one->cl_delivery_note->cl_currencies_id;
                        $arrDnPay['cl_transport_docs_id']   = $one->id;
                        $arrDnPay['cl_delivery_note_id']    = $one['cl_delivery_note_id'];
                        $arrDnPay['cl_company_id']          = $one['cl_company_id'];
                        //$arrDnPay['changed']                = NULL;
                        $tmpDnPay = $this->DeliveryNotePaymentsManager->findAllTotal()->
                                                where(['cl_transport_docs_id' => $one->id,
                                                            'cl_delivery_note_id' => $one['cl_delivery_note_id']])->fetch();
                        if (!$tmpDnPay) {
                            $arrDnPay['create_by'] = $one->cl_transport->cl_users['name'];
                            $arrDnPay['created'] = $tmpNow;
                            $tmpDNPay = $this->DeliveryNotePaymentsManager->insertForeign($arrDnPay);
                        }else{
                            $arrDnPay['change_by'] = $one->cl_transport->cl_users['name'];
                            $arrDnPay['changed'] = $tmpNow;
                            $arrDnPay['id'] = $tmpDnPay['id'];
                            $tmpDNPay = $this->DeliveryNotePaymentsManager->updateForeign($arrDnPay);
                        }

                    //payment update
                    $decimal_places = 2;
                    $parentData = [];
                    $parentData['price_payed'] = $this->DeliveryNotePaymentsManager->findAllTotal()->where(['cl_delivery_note_id' => $one->cl_delivery_note_id,
                        'pay_type' => 0])->sum('pay_price');

                    $tmpPrice = round($tmpDN['price_e2_vat'],$decimal_places);

                    if ($parentData['price_payed'] == $tmpPrice) {
                        $parentData['pay_date'] = $this->DeliveryNotePaymentsManager->findAllTotal()->
                                                                where(['cl_delivery_note_id' => $one->cl_delivery_note_id,
                                                                            'pay_type' => 0])->max('pay_date');
                        $tmpStatus = 'delivery_note';
                        if ($nStatus = $this->StatusManager->findAll()->where('status_use = ? AND s_fin = ?', $tmpStatus, 1)->fetch())
                            $parentData['cl_status_id'] = $nStatus->id;

                    }else{
                        $parentData['pay_date'] = NULL;
                    }

                    $parentData['delivery_date'] = $tmpNow;
                    $tmpDN->update($parentData);
                }


        }
        //create invoices
        $tmpData = $this->TransportDocsManager->findAllTotal()->where(['cl_transport_docs.cl_transport_id ' => $cl_transport_id,
                                                                            'cl_delivery_note.cl_partners_book_id ' => $cl_partners_book_id])
                                                        ->where('cl_delivery_note.cl_payment_types.dn_to_cash = 0 AND cl_transport_docs.only_for_pay = 0')
                                                        ->where('cl_delivery_note.commission = 0'); //cl_delivery_note.cl_partners_book.no_invoice_from_dn = 0 AND
        $result = 'OK';
        foreach($tmpData as $key => $one){
            if ($one->cl_delivery_note->cl_partners_book['no_invoice_from_dn'] == 0) {
                $arrRet = $this->DeliveryNoteManager->createInvoice(json_encode([$one->cl_delivery_note_id => ($one->cl_delivery_note_id)]), $one->cl_delivery_note_id);
                //dump($arrRet);

                if ($arrRet['status'] == "OK") {
                    $tmpStatus = $arrRet['data']['tmpStatus'];
                    $invoiceId = $arrRet['data']['invoiceId'];

                    //send to EET
                    $this->sendEET($invoiceId);

                    try {
                        $arrRetE = $this->sendEmailInvoice($invoiceId);
                        if ($arrRetE['status'] == "OK") {
                            $result = 'OK';
                        } else {
                            $result = 'EMAIL ERROR: ' . $arrRetE['message'];
                        }

                    } catch (\Exception $e) {
                        $result = 'EMAIL ERROR' . $e->getMessage();
                        Debugger::log('Transport API send Email: ' . $e->getMessage());
                    }
                } elseif ($arrRet['status'] == "NO_INVOICE") {
                    //21.01.2021 - invoice wasn't created due to used payment_type
                    $result = 'OK';
                } else {
                    $result = 'INVOICE ERROR';
                }

                if ($result == 'INVOICE ERROR') {
                    break;
                }
            }
            if ($one->cl_delivery_note->cl_partners_book['send_dn_from_app'] == 1) {
                $this->sendEmailDN($one['cl_delivery_note_id']);
            }



        }

        //28.09.2020 - make invoice payments if delivery note is payed
        $tmpInv = $this->TransportDocsManager->findAllTotal()->where(['cl_transport_docs.cl_transport_id ' => $cl_transport_id,
                                        'cl_delivery_note.cl_partners_book_id ' => $cl_partners_book_id])
                                        ->where('cl_delivery_note.cl_payment_types.dn_to_cash = 0 AND cl_transport_docs.price_payed != 0')
                                        ->where('cl_delivery_note.cl_invoice_id IS NOT NULL AND cl_delivery_note.commission = 0')
                                        ->where('cl_delivery_note.cl_partners_book.no_invoice_from_dn = 0');

        foreach($tmpInv as $key => $one){
            $arrInvPay                           = [];
            $arrInvPay['pay_price']              = $one['price_payed'];
            $arrInvPay['pay_date']               = $one['created'];
            $arrInvPay['cl_payment_types_id']    = $one->cl_delivery_note['cl_payment_types_id'];
            $arrInvPay['cl_currencies_id']       = $one->cl_delivery_note['cl_currencies_id'];
            $arrInvPay['cl_transport_docs_id']   = $one->id;
            $arrInvPay['cl_invoice_id']          = $one->cl_delivery_note['cl_invoice_id'];
            $arrInvPay['cl_company_id']          = $one['cl_company_id'];
            $arrInvPay['changed']                = NULL;
            $tmpInvPay = $this->InvoicePaymentsManager->findAllTotal()->
                                                    where(['cl_transport_docs_id' => $one->id,
                                                    'cl_invoice_id' => $one->cl_delivery_note['cl_invoice_id']])->fetch();
            if (!$tmpInvPay) {
                $tmpInvPay = $this->InvoicePaymentsManager->insertForeign($arrInvPay);
            }else{
                $arrInvPay['id'] = $tmpInvPay['id'];
                $tmpInvPay = $this->InvoicePaymentsManager->updateForeign($arrInvPay);
            }

            //payment update
            $decimal_places = 2;
            $parentData = [];
            $parentData['price_payed'] = $this->InvoicePaymentsManager->findAllTotal()->where(['cl_invoice_id' => $one->cl_delivery_note['cl_invoice_id'],
                'pay_type' => 0])->sum('pay_price');

            $tmpPrice = round($one->cl_delivery_note->cl_invoice['price_e2_vat'],$decimal_places);

            if ($parentData['price_payed'] == $tmpPrice) {
                $parentData['pay_date'] = $this->InvoicePaymentsManager->findAllTotal()->where(['cl_invoice_id' => $one->cl_delivery_note['cl_invoice_id'],
                    'pay_type' => 0])->max('pay_date');
            }else{
                $parentData['pay_date'] = NULL;
            }
            $tmpInvoice = $this->InvoiceManager->findAllTotal()->where(['id' => $one->cl_delivery_note['cl_invoice_id']])->limit(1)->fetch();
            if ($tmpInvoice){
                $tmpInvoice->update($parentData);
            }

        }

        //$mySection = $this->session->getSection('company');
        //21.04.2021 - removed due to huge ammount of session files
        $mySection['cl_company_id'] = NULL;
        Debugger::log('actionMakeInvoice result: ' . $result);
        echo($result);
        $this->terminate();
        //send mails

    }

    private function sendEET($invoiceId)
    {
       // parent::actionSet();

        $tmpData = $this->InvoiceManager->find($invoiceId);
        if (($tmpData['price_e2'] == 0 && $tmpData['price_e2_vat'] == 0) || $tmpData['price_payed'] == 0 ){
            //$this->flashMessage('Doklad má nulovou částku, nebo ještě nemá hotovostní úhradu. Není možné jej odeslat do EET.', 'danger');

        }elseif (!is_null($tmpData->cl_payment_types_id) &&
                    $tmpData->cl_company['eet_active'] == 1 &&
                ($tmpData->cl_payment_types->payment_type == 1  || $tmpData->cl_payment_types->eet_send == 1)) {
            //send to EET
            $dataForSign = $this->CompaniesManager->getDataForSignEET($tmpData['cl_company_branch_id']);
            try {
                //bdump($dataForSign);
                $arrRet = $this->EETService->sendEET($tmpData, $dataForSign);
                //bdump($arrRet);
                $tmpId = $this->EETManager->insertNewEET($arrRet, $dataForSign['eet_test']);
                $tmpData->update(['cl_eet_id' => $tmpId]);
            } catch (\Exception $e) {
              //  $this->flashMessage('Chyba certifikátu. Zkontrolujte nahraný certifikát a heslo', 'danger');
              //  $this->flashMessage($e->getMessage(), 'danger');
            }
        }else{
            //$this->flashMessage('Doklad není hrazen v hotovosti. Není možné jej odeslat do EET.', 'danger');
        }

    }

    public function sendEmailInvoice($invoiceId)
    {

        $this->DataManager = $this->InvoiceManager;
        $this->mainTableName = "cl_invoice";
        //settings for CSV attachments
        $this->csv_h        = ['columns' => 'inv_number,inv_date,vat_date,cl_invoice.due_date,var_symb,konst_symb,cl_invoice.spec_symb,inv_title,cl_partners_book.company,cl_partners_book_workers.worker_name,cl_currencies.currency_code,price_e2,price_e2_vat,price_correction,price_base0,price_base1,price_base2,price_base3,
                                                    correction_base1,correction_base2,correction_base3,price_vat0,price_vat1,price_vat2,price_vat3,vat1,vat2,vat3,price_payed,base_payed0,base_payed1,base_payed2,base_payed3,vat_payed1,vat_payed2,vat_payed3,advance_payed,cl_invoice.header_txt,cl_invoice.footer_txt,pdp,export,storno'];
        $this->csv_i        = ['columns' => 'item_order,cl_pricelist.ean_code,cl_pricelist.order_code,cl_pricelist.identification,cl_invoice_items.item_label,cl_pricelist.order_label,cl_invoice_items.quantity,cl_invoice_items.units,cl_storage.name AS storage_name,cl_invoice_items.price_e,cl_invoice_items.discount,cl_invoice_items.price_e2,cl_invoice_items.price_e2_vat',
                                                    'datasource' => 'cl_invoice_items',
                                                    'columns2' => 'item_order,cl_pricelist.ean_code,cl_pricelist.order_code,cl_pricelist.identification,cl_invoice_items_back.item_label,cl_pricelist.order_label,cl_invoice_items_back.quantity,cl_invoice_items_back.units,cl_storage.name AS storage_name,cl_invoice_items_back.price_e,cl_invoice_items_back.discount,cl_invoice_items_back.price_e2,cl_invoice_items_back.price_e2_vat',
                                                    'datasource2' => 'cl_invoice_items_back'];

        $this->docEmail	    = ['template' => __DIR__ .'/../../ApplicationModule/templates/Invoice/emailInvoice.latte',
                                    'emailing_text' => 'invoice'];
        //17.05.2020 - settings for documents saving and emailing
        $this->docTemplate[1]  =  $this->ReportManager->getReport(__DIR__ . '/../../ApplicationModule/templates/Invoice/invoicev2.latte');
        $this->docTemplate[2]  =  $this->ReportManager->getReport(__DIR__ . '/../../ApplicationModule/templates/Invoice/correctionv1.latte');
        $this->docTemplate[3]  =  $this->ReportManager->getReport(__DIR__ . '/../../ApplicationModule/templates/Invoice/advancev1.latte');
        //$this->docAuthor    = $this->user->getIdentity()->name . " z " . $this->settings->name;
        $this->docAuthor        = "";
        $this->docTitle[1]	    = ["", "cl_partners_book.company", "inv_number"];
        $this->docTitle[2]	    = ["", "cl_partners_book.company", "inv_number"];
        $this->docTitle[3]	    = ["", "cl_partners_book.company", "inv_number"];

        $tmpData = $this->InvoiceManager->preparePDFData($invoiceId);
        $this->emlPreview = FALSE;
        $this->id = $invoiceId;

        //parent::handleSavePDF($invoiceId, $tmpData['latteIndex'], $tmpData, TRUE, FALSE);
        parent::handleSendDoc($invoiceId, $tmpData['latteIndex'], $tmpData);

        $data = $this->emailData;
        //dump($data);
        foreach($data['workers'] as $key => $one)
        {
            if ($data['singleEmailTo'] != '') {
                $data['singleEmailTo'] .= ';';
            }
            //$data['singleEmailTo'] .= $one . ' <' . $arrWorkers[$one] . '>';
            $data['singleEmailTo'] .= $one;
        }

        unset($data['workers']);

        $emailTo = str_getcsv($data['singleEmailTo'], ';');
        if (empty($emailTo)){
            return ['status' => 'ERROR', 'message' => 'no email'];
        }

        $emailFrom = $data['singleEmailFrom'];
        $subject = $data['subject'];
        $body = $data['body'];

        //$data['attachment'] = $this->myEmailData['attachment'];

        $attachment = json_decode($data['attachment'], true);
        //dump($attachment);
        $this->emailService->sendMail($this->presenter->settings, $emailFrom, $emailTo, $subject, $body, $attachment);

        //26.07.2018 - connect parent table
        $data[$this->mainTableName . '_id'] = $invoiceId;

        if (is_null($data['attachment']))
            unset($data['attachment']);

        //save to cl_emailing
        $this->EmailingManager->insert($data);

        //$this->presenter->emailSetStatus(); //call setStatus in presenter
        return ['status' => 'OK', 'message' => 'send'];
      // echo('ok');
//        $this->terminate();
    }

    public function sendEmailDN($dnId)
    {

        //$tmpInvoice = $this->InvoiceManager->findAllTotal()->where(['id' => $invoiceId])->fetch();
        //$dnId = $tmpInvoice['cl_delivery_note_id'];

        $this->DataManager = $this->DeliveryNoteManager;
        $this->mainTableName = "cl_delivery_note";
        //settings for CSV attachments
        $this->csv_h = array('columns' => 'dn_number,issue_date,delivery_date,dn_title,cl_partners_book.company,cl_partners_book_workers.worker_name,cl_currencies.currency_code,price_e2,price_e2_vat,price_correction,price_base0,price_base1,price_base2,price_base3,
                                            price_vat1,price_vat2,price_vat3,vat1,vat2,vat3,price_payed,cl_delivery_note.header_txt,cl_delivery_note.footer_txt,storno');
        $this->csv_i = array('columns' => 'item_order,cl_pricelist.ean_code,cl_pricelist.order_code,cl_pricelist.identification,cl_delivery_note_items.item_label,cl_pricelist.order_label,cl_delivery_note_items.quantity,cl_delivery_note_items.units,cl_storage.name AS storage_name,cl_delivery_note_items.price_e,cl_delivery_note_items.discount,cl_delivery_note_items.price_e2,cl_delivery_note_items.price_e2_vat,cl_delivery_note_items.vat',
            'datasource' => 'cl_delivery_note_items',
            'columns2' => 'item_order,cl_pricelist.ean_code,cl_pricelist.order_code,cl_pricelist.identification,cl_delivery_note_items_back.item_label,cl_pricelist.order_label,cl_delivery_note_items_back.quantity,cl_delivery_note_items_back.units,cl_storage.name AS storage_name,cl_delivery_note_items_back.price_e,cl_delivery_note_items_back.discount,cl_delivery_note_items_back.price_e2,cl_delivery_note_items_back.price_e2_vat,cl_delivery_note_items_back.vat',
            'datasource2' => 'cl_delivery_note_items_back');
        $this->docEmail	    = ['template' => __DIR__ .'/../../ApplicationModule/templates/DeliveryNote/emailDeliveryNote.latte',
                                'emailing_text' => 'delivery_note'];

        //17.05.2020 - settings for documents saving and emailing
        $this->docTemplate = $this->ReportManager->getReport(__DIR__ . '/../../ApplicationModule/templates/DeliveryNote/DeliveryNotev2.latte'); //Precistec

        $this->docAuthor = "";
        $this->docTitle = ["", "cl_partners_book.company", "dn_number"];
        
        //$tmpData = $this->DeliveryNoteManager->preparePDFData($invoiceId);
        $this->emlPreview = FALSE;
        $this->id = $dnId;
        parent::handleSendDoc($dnId);

        $data = $this->emailData;
        //dump($data);
        foreach($data['workers'] as $key => $one)
        {
            if ($data['singleEmailTo'] != '') {
                $data['singleEmailTo'] .= ';';
            }
            //$data['singleEmailTo'] .= $one . ' <' . $arrWorkers[$one] . '>';
            $data['singleEmailTo'] .= $one;
        }

        unset($data['workers']);

        $emailTo = str_getcsv($data['singleEmailTo'], ';');
        if (empty($emailTo)){
            return ['status' => 'ERROR', 'message' => 'no email'];
        }

        $emailFrom = $data['singleEmailFrom'];
        $subject = $data['subject'];
        $body = $data['body'];

        //$data['attachment'] = $this->myEmailData['attachment'];

        $attachment = json_decode($data['attachment'], true);
        //dump($attachment);
        $this->emailService->sendMail($this->presenter->settings, $emailFrom, $emailTo, $subject, $body, $attachment);

        //26.07.2018 - connect parent table
        $data[$this->mainTableName . '_id'] = $dnId;

        if (is_null($data['attachment']))
            unset($data['attachment']);

        //save to cl_emailing
        $this->EmailingManager->insert($data);

        //$this->presenter->emailSetStatus(); //call setStatus in presenter
        return ['status' => 'OK', 'message' => 'send'];
        // echo('ok');
//        $this->terminate();
    }


}

