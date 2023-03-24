<?php

namespace App\ApplicationModule\Presenters;

use App\Controls;
use Mpdf\Utils\Arrays;
use Nette\Application\UI\Form,
    Nette\Image;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;

class ExpeditionPresenter extends \App\Presenters\BaseAppPresenter {

    /** @persistent */
    public $id;
	
	/** @persistent */
    public $type;
	
	/** @persistent */
    public $searchTxt="";

    public $myReadOnly;

    /**
     * @inject
     * @var \App\Model\CommissionManager
     */
    public $commissionManager;
	
	/**
	 * @inject
	 * @var \App\Model\DeliveryNoteManager
	 */
	public $DeliveryNoteManager;

    /**
     * @inject
     * @var \App\Model\CommissionItemsSelManager
     */
    public $CommissionItemsSelManager;
	
	/**
	 * @inject
	 * @var \App\Model\DeliveryNoteItemsManager
	 */
	public $DeliveryNoteItemsManager;
    

    /**
     * @inject
     * @var \App\Model\TransportTypesManager
     */
    public $TransportTypesManager;

    /**
     * @inject
     * @var \App\Model\PairedDocsManager
     */
    public $pairedDocsManager;

    /**
     * @inject
     * @var \App\Model\StoreMoveManager
     */
    public $storeMoveManager;

    /**
     * @inject
     * @var \App\Model\StoragePlacesManager
     */
    public $storagePlacesManager;


	
	protected function createComponentAuthorizeUser() {
		return new Controls\AuthorizeUserControl($this->translator, $this->UserManager, 'autorizovat nezkontrolované položky', $this->session);
	}

    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.Expedition']);
    }


    public function renderDefault($modal = FALSE, $dataId = NULL) {
        $this->template->modal = $modal;
	    if ($dataId != NULL){
            $tmpData = $this->commissionManager->findAll()->where('id = ?', $dataId)->fetch();
        }else{
            $tmpData = $this->commissionManager->findAll()->
                                                    where('cm_number LIKE ?', '%' . $this->searchTxt . '%')->
                                                    order('expedition_ok ASC, cm_date DESC')->limit(1)->
                                                    fetch();
            //AND cl_store_docs_id IS NULL
        }

        $this->type = 'commission';
        $notChecked = array();
       /* if (!$tmpData) {
			$tmpData = $this->DeliveryNoteManager->findAll()->where('dn_number = ?', $this->searchTxt)->fetch();
			if ($tmpData) {
				$this->type = 'deliveryNote';
				$notChecked = $this->DeliveryNoteItemsManager->findAll()->where('cl_delivery_note_items.quantity != cl_delivery_note_items.quantity_checked AND pricelist.ean_code != ""')->fetchAll();
			}
		}else{*/
        $notChecked = $this->CommissionItemsSelManager->findAll()->where('cl_commission_items_sel.quantity != cl_commission_items_sel.quantity_checked AND cl_pricelist.ean_code != ""')->fetchAll();
		//}
        //bdump($this->type);
		//$this->tmpData = $tmpData;
        $this->template->data = $tmpData;
        $this->template->searchTxt = $this->searchTxt;
		$this->template->notChecked = count($notChecked);
		$mySection = $this->session->getSection('authorized');
        //bdump($mySection);
		if (!isset($mySection['authorizedPIN']) || !isset($mySection['authorizedDateTime'])){
			$this->template->authorized = FALSE;
		}else{
			$now = new DateTime();
			//bdump($mySection['authorizedDateTime'] );
			if ($mySection['authorizedDateTime'] >= $now) {
				$this->template->authorized = $mySection['authorizedPIN'];
			}else{
				$this->template->authorized = FALSE;
			}
		}
        //bdump($this->template->authorized);
		//bdump($this->template->notChecked);
        if ($tmpData){
            $this->id = $tmpData->id;
            if ($tmpData['expedition_ok'] == 1 && $this->template->authorized == FALSE){
                $this['listgridItems']->setReadOnly();
            }elseif ($this->template->authorized){
                $this['listgridItems']->setReadOnly(FALSE);
            }
        }
       // bdump($this->id,'this->id');

    }


    protected function createComponentSearchCommission($name)
    {
        $form = new Form($this, $name);
        $form->addText('searchTxt', $this->translator->translate('Číslo_zakázky_/_dodacího_listu'), 30, 30);
        $form->addSubmit('send', $this->translator->translate('Hledat'))->
                    setHtmlAttribute('class','btn btn-success');
        $form->onSuccess[] = array($this,'SubmitSearchCommission');
        return $form;
    }

    public function SubmitSearchCommission(Form $form)
    {
        $data = $form->values;
        if ($form['send']->isSubmittedBy()) {
            $this->searchTxt = $data['searchTxt'];
            $this->redrawControl('commission_content');
        }
    }

    protected function createComponentListgridItems()
    {
        //$this->id = $id;
        //bdump($this->id);
		if ($this->type == 'commission') {
			$tmpParentData = $this->commissionManager->find($this->id);
			$tmpItems = $this->CommissionItemsSelManager;
		}elseif ($this->type == 'deliveryNote') {
			$tmpParentData = $this->DeliveryNoteManager->find($this->id);
			$tmpItems = $this->DeliveryNoteItemsManager;
		}else{
			$tmpItems = FALSE;
		}

        if ( $tmpParentData->price_e_type == 1)
        {
            $tmpProdej = $this->translator->translate("Prodej_s_DPH");
        }else{
            $tmpProdej = $this->translator->translate("Prodej_bez_DPH");
        }


        if ($this->settings->platce_dph == 1)
        {
            $arrData = array( 'cl_pricelist.identification' => array($this->translator->translate('Kód'),'format' => 'text','size' => 10,'readonly' => TRUE),
                'item_label' => array($this->translator->translate('Popis'), 'format' => 'text', 'size' => 30, 'readonly' => TRUE),
                'quantity' => array($this->translator->translate('Množství'),'format' => 'number','size' => 10,'decplaces' => $this->settings->des_mj, 'readonly' => TRUE),
                'units' => array($this->translator->translate('Jednotky'),'format' => 'text','size' => 7, 'readonly' => TRUE),
                'quantity_checked' => array($this->translator->translate('Zkontrolováno'),'format' => 'currency','size' => 10,'decplaces' => $this->settings->des_mj),
                'vat' => array($this->translator->translate('Sazba_DPH'),'format' => 'text','size' => 6, 'readonly' => TRUE),
                'price_e' => array($this->translator->translate('Prodejní_cena'),'format' => "number",'size' => 8,'decplaces' => $this->settings->des_cena, 'readonly' => TRUE),
                'price_e2' => array($this->translator->translate('Prodejní_celkem'),'format' => "number",'size' => 8,'decplaces' => $this->settings->des_cena, 'readonly' => TRUE));
        }else{
            $arrData = array( 'cl_pricelist.identification' => array($this->translator->translate('Kód'),'format' => 'text','size' => 10,'readonly' => TRUE),
                'item_label' => array($this->translator->translate('Popis'), 'format' => 'text', 'size' => 30, 'readonly' => TRUE),
                'quantity' => array($this->translator->translate('Množství'),'format' => 'number','size' => 10,'decplaces' => $this->settings->des_mj, 'readonly' => TRUE),
                'units' => array($this->translator->translate('Jednotky'),'format' => 'text','size' => 7, 'readonly' => TRUE),
                'quantity_checked' => array($this->translator->translate('Zkontrolováno'),'format' => 'currency','size' => 10,'decplaces' => $this->settings->des_mj),
                'price_e' => array($this->translator->translate('Prodejní_cena'),'format' => "number",'size' => 8,'decplaces' => $this->settings->des_cena, 'readonly' => TRUE),
                'price_e2' => array($this->translator->translate('Prodejní_celkem'),'format' => "number",'size' => 8,'decplaces' => $this->settings->des_cena, 'readonly' => TRUE));
        }
        $control =  new Controls\ListgridControl(
            $this->translator,
            $tmpItems,
            $arrData,
            array(),
            $this->id,
            array('units' => $this->settings->def_mj, 'vat' => $this->settings->def_sazba),
            $this->commissionManager,
            FALSE,
            FALSE,
            FALSE,
            array(
            ), //custom links
            FALSE, //movable row
            NULL, //ordercolumn
            FALSE, //selectmode
            array(), //quicksearch
            "", //fontsize
            FALSE, //parentcolumnname
            TRUE, //pricelistbottom
            TRUE, //readonly
            TRUE,  //nodelete
			FALSE, //emnableSearch
			'', //txtSearchCondition
			array(), //toolbar
			FALSE, //forceEnable
			TRUE, //paginatoroff
			array(1 => array('conditions' => array(1 => array('left' => 'quantity', 'condition' => '>', 'right' => 'quantity_checked'),
													2 => array('left' => 'pricelist.ean_code', 'condition' => '!=', 'right' => '')),
													'colour' => $this->RGBtoHex(255,151,151)), //red  -

			), //colours conditions
			/*- cervene - pocet je vetsi nez 0, skutecnost je 0 - toto zustava
			*- modre - pocet je vetsi nez 0, skutecnost je mensi od pocet
			*- zelene - pocet je vetsi nez 0, skutecnost je vetsi od pocet
			*- oranzove - pocet je mensi nez 0
			*- zlute - pocet je 0, skutecnost je vetsi nez 0 - toto zustava*/
			20, //pagelength
			'500px' //containerHeight
        );
        $control->showHistory(FALSE);
        $control->setHideTimestamps(TRUE);
        $control->onChange[] = function ()
        {
            //$this->updateSum();

        };
        return $control;
    }


    //aditional processing of data from listgrid
    public function DataProcessListGrid($data)
    {
        if (isset($data['cl_pricelist_id']) && $data['cl_pricelist_id'] == 0){
            $data['cl_pricelist_id'] = NULL;
        }

        return $data;

    }

    //validating of data from listgrid
    public function DataProcessListGridValidate($data)
    {
        return NULL;
    }

    //aditional processing data after save in listgrid
    public function afterDataSaveListGrid($dataId, $name = NULL)
    {

    }
	
	
	protected function createComponentSearchItem($name)
	{
		$form = new Form($this, $name);
		$form->addText('searchCode', $this->translator->translate('Položka'), 30, 30);
		$form->addSubmit('send', $this->translator->translate('Hledat'))->
				setHtmlAttribute('class','btn btn-success');
		$form->onSuccess[] = array($this,'SubmitSearchItem');
		return $form;
	}
	
	public function SubmitSearchItem(Form $form)
	{
		//bdump($this->type);
		$data = $form->values;
		if ($form['send']->isSubmittedBy()) {
			$searchCode = $data['searchCode'];
			if ($this->type == 'commission'){
				$items = $this->CommissionItemsSelManager;
			}elseif ($this->type == 'deliveryNote'){
				$items = $this->DeliveryNoteItemsManager;
			}
			if ($onItem = $items->findAll()->where('pricelist.ean_code = ? OR pricelist.identification = ?', $searchCode, $searchCode)->fetch()){
				$onItem->update(array('quantity_checked' => $onItem->quantity_checked + 1));
			}
			$this->redrawControl('itemsContainer');
		}
	}
 
	public function handleFinished($packages){
		if ($this->type == 'commission'){
			//make giveout from store
			$arrDataItemsSel = $this->CommissionItemsSelManager->findAll()->where('cl_commission_id = ?', $this->id)->fetchPairs('id', 'id');
			$arrDataItems = array();
			$docId = $this->commissionManager->createOut($this->id, $arrDataItemsSel, $arrDataItems);


			//make delivery note
            $arrRet = $this->DeliveryNoteManager->createDelivery($docId);
            if (self::hasError($arrRet)) {
                $this->flashMessage($this->translator->translate('Dodací_list_nebyl_vytvořen'), 'warning');
            }else{
                //TODO: connection with cl_transport

                //mark cl_commission as expedition_ok and set correct cl_status_id
                $tmpStatus = $this->StatusManager->findAll()->where('status_use = 1 AND s_exp = 1')->fetch();
                if ($tmpStatus){
                    $statExpOk = $tmpStatus['id'];
                }else{
                    $statExpOk = NULL;
                }
                if (!is_null($statExpOk)){
                    $this->commissionManager->update(['id' => $this->id, 'expedition_ok' => 1, 'cl_status_id' => $statExpOk, 'exp_packages' => $packages]);
                }else{
                    $this->commissionManager->update(['id' => $this->id, 'expedition_ok' => 1, 'exp_packages' => $packages]);
                }

                $this->pairedDocsManager->insertOrUpdate(array('cl_commission_id' => $this->id, 'cl_delivery_note_id' => $arrRet['deliveryN_id']));
                $this->flashMessage($this->translator->translate('Dodací_list_byl_vytvořen'), 'success');
                $this->payload->id = $arrRet['deliveryN_id'];
            }

		}
        $this->searchTxt = "";
		$this->redirect('Expedition:default', ['id' => NULL, 'type' => '', 'dataId' => NULL]);
	}
 
    public function handleResetQc()
    {
	    $tmpData = $this->CommissionItemsSelManager->findAll()->where('cl_commission_id = ?', $this->id);
	    foreach($tmpData as $key => $one)
        {
            $this->CommissionItemsSelManager->update(['id' => $key, 'quantity_checked' => 0]);
        }
        $this->redrawControl('itemsContainer');
    }

    public function handlePrint()
    {
        $dataOther = array();
        $dataSettings = array();

        $dataReport = $this->commissionManager->findAll()->where('id = ? ', $this->id)->fetch();

        $arrPlaces = [];
        foreach ($dataReport->related('cl_commission_items_sel') as $key => $one) {
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

            $arrPlaces[$one['id']] = $storage_places;
        }
//        bdump($arrPlaces);
        $dataOther['places'] = $arrPlaces;
        $dataOther['settings'] = $this->settings;

        $template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/Expedition/rptExpedition.latte', $dataOther, $dataSettings, 'Expediční list');
        $tmpDate1 = new \DateTime();
        $this->pdfCreate($template, 'Expedice ' . $dataReport['cm_number'] . date_format($tmpDate1, 'd.m.Y'));

    }

}
