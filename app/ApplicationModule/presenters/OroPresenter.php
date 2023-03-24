<?php

namespace App\ApplicationModule\Presenters;

use App\Controls;
use Nette\Application\UI\Form,
    Nette\Image;

use Nette\Mail\Message,
    Nette\Utils\Strings;
use Nette\Mail\SendmailMailer;
use Nette\Utils\DateTime;
use Ramsey\Uuid\Uuid;
use Tracy\Debugger;

class OroPresenter extends \App\Presenters\BaseListPresenter
{

    const
        DEFAULT_STATE = 'Czech Republic';


    public $newId = NULL;
    public $paymentModalShow = FALSE, $headerModalShow = FALSE, $footerModalShow = FALSE, $pairedDocsShow = FALSE, $createDocShow = FALSE, $checkedValues = FALSE;


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

    public $filterDeliveryNoteUsed = array();

    /**
     * @inject
     * @var \App\Model\OroManager
     */
    public $DataManager;


    /**
     * @inject
     * @var \App\Model\OroItemsManager
     */
    public $OroItemsManager;

    /**
     * @inject
     * @var \App\Model\OroProductsManager
     */
    public $OroProductsManager;

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
     * @var \App\Model\PartnersBranchManager
     */
    public $PartnersBranchManager;

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
     * @var \App\Model\InvoiceItemsBackManager
     */
    public $InvoiceItemsBackManager;

    /**
     * @inject
     * @var \App\Model\InvoiceTypesManager
     */
    public $InvoiceTypesManager;

    /**
     * @inject
     * @var \App\Model\InvoicePaymentsManager
     */
    public $InvoicePaymentsManager;


    /**
     * @inject
     * @var \App\Model\StoreDocsManager
     */
    public $StoreDocsManager;


    /**
     * @inject
     * @var \App\Model\StoreMoveManager
     */
    public $StoreMoveManager;

    /**
     * @inject
     * @var \App\Model\StoreManager
     */
    public $StoreManager;

    /**
     * @inject
     * @var \App\Model\StoreOutManager
     */
    public $StoreOutManager;

    /**
     * @inject
     * @var \App\Model\StorageManager
     */
    public $StorageManager;

    /**
     * @inject
     * @var \App\Model\EmailingManager
     */
    public $EmailingManager;

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
     * @var \App\Model\PriceListBondsManager
     */
    public $PriceListBondsManager;



    public function createComponentOroProductslistgrid()
    {

        $tmpParentData = $this->DataManager->find($this->id);
            $arrData = [
                        'id_vyrobku' => [$this->translator->translate('ID_výrobku'), 'format' => 'text', 'size' => 10],
                        'ean' => [$this->translator->translate('EAN'), 'format' => 'text', 'size' => 10],
                        'nazev' => [$this->translator->translate('Název'), 'format' => 'text', 'size' => 10],
                        'vyrobce' => [$this->translator->translate('Výrobce'), 'format' => 'text', 'size' => 10],
                        'objem' => [$this->translator->translate('Objem'), 'format' => 'number', 'size' => 6],
                        'procento_lihu' => [$this->translator->translate('%_lihu'), 'format' => 'number', 'size' => 10],
                        'sarze' => [$this->translator->translate('Šarže'), 'format' => 'text', 'size' => 10]];

        $control = new Controls\ListgridControl(
            $this->translator,
            $this->OroProductsManager,
            $arrData,
            [],
            $this->id,
            [],
            $this->DataManager,
            FALSE,
            FALSE,
            FALSE,
            [
            ], //custom links
            FALSE, //movable row
            NULL, //ordercolumn
            FALSE, //selectmode
            [], //quicksearch
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE //pricelistbottom
        );
        $control->setContainerHeight("auto");
        $control->setEnableSearch('cl_pricelist.identification LIKE ? OR cl_pricelist.item_label LIKE ? OR cl_pricelist.ean_code LIKE ? OR vyrobce LIKE ? OR ean LIKE ? OR id_vyrobku LIKE ?');
        $control->onChange[] = function () {
            $this->updateSum();

        };
        return $control;

    }

    public function createComponentItemslistgrid()
    {
        $arrPohyb = $this->ArraysManager->getOroPohybTypes();
        $arrData = [
                    'odberatel' => [$this->translator->translate('Odběratel'), 'format' => 'text', 'size' => 10, 'function' => 'getOdberatel', 'function_param' => ['odberatel']],
                    'id_vyrobku' => [$this->translator->translate('ID_výrobku'), 'format' => 'text', 'size' => 7],
                    'cl_pricelist.identification' => [$this->translator->translate('Kód_výrobku'), 'format' => 'text', 'size' => 10],
                    'cl_pricelist.item_label' => [$this->translator->translate('Název'), 'format' => 'text', 'size' => 10],
                    'pocet' => [$this->translator->translate('Počet'), 'format' => 'number', 'size' => 8],
                    'typ' => [$this->translator->translate('Pohyb'), 'format' => 'chzn-select-req',
                                            'values' => $arrPohyb, 'size' => 8, 'function' => 'getTypPohyb', 'function_param' => ['typ']]];

        $control = new Controls\ListgridControl(
            $this->translator,
            $this->OroItemsManager,
            $arrData,
            [],
            $this->id,
            [],
            $this->DataManager,
            FALSE,
            FALSE,
            FALSE,
            [
            ], //custom links
            FALSE, //movable row
            NULL, //ordercolumn
            FALSE, //selectmode
            [], //quicksearch
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE //pricelistbottom
        );
        $control->setContainerHeight("auto");
        $control->setEnableSearch('odberatel LIKE ?');

        $control->setToolbar([
            1 => ['url' => $this->link('Generate!'), 'rightsFor' => 'write', 'data' => ['data-history=false'],
                'label' => $this->translator->translate('Generovat_obsah'), 'title' => $this->translator->translate('Generovat_obsah'), 'class' => 'btn btn-warning', 'icon' => 'iconfa-filter'],
            2 => ['url' => $this->link('DeleteGenerated!'), 'rightsFor' => 'write', 'data' => ['data-history=false'],
                'label' => $this->translator->translate('Smazat_obsah'), 'title' => $this->translator->translate('Smaže_obsah'), 'class' => 'btn btn-danger', 'icon' => 'iconfa-filter'],

        ]);


        $control->onChange[] = function () {
            $this->updateSum();
        };
        return $control;

    }

    public function handleRedrawDueDate2(){

    }

   protected function startup()
    {
        parent::startup();
        $this->formName = $this->translator->translate("ORO_Celní_správa");
        $this->mainTableName = 'cl_oro';

        $arrData = ['oznameni_za_den'   => [$this->translator->translate('Oznámení_za_den'), 'format' => 'date'],
            'cl_status.status_name'     => [$this->translator->translate('Stav'), 'format' => 'colortag'],
            'id_oznameni'               => [$this->translator->translate('ID_oznámení'), 'format' => 'text'],
            'typ_podani'                => [$this->translator->translate('Typ_podání'), 'format' => 'text'],
            'odesilatel_dic'            => [$this->translator->translate('DIČ_odesílatele'), 'format' => 'text'],
            'description_txt'           => [$this->translator->translate('Poznámka'), 'format' => 'text'],
            'created'                   => [$this->translator->translate('Vytvořeno'), 'format' => 'datetime'],
            'create_by'                 => $this->translator->translate('Vytvořil'),
            'changed'                   => [$this->translator->translate('Změněno'), 'format' => 'datetime'],
            'change_by'                 => $this->translator->translate('Změnil')];

        $this->dataColumns = $arrData;
        //$this->formatColumns = array('cm_date' => "date",'created' => "datetime",'changed' => "datetime");
        //$this->agregateColumns = 'cl_partners_book.*,MAX(:cl_partners_event.date) AS cdate';
        //$this->FilterC = 'UPPER(company) LIKE ? OR UPPER(street) LIKE ? OR UPPER(city) LIKE ? OR UPPER(:cl_partners_event.tags) LIKE ?';
        $this->filterColumns = ['oznameni_za_den' => '', 'id_oznameni' => 'autocomplete',
            'typ_podani' => 'autocomplete',
            'odesilatel_dic' => 'autocomplete'];

        $this->userFilterEnabled = TRUE;
        $this->userFilter = ['typ_podani', 'id_oznameni', 'description_txt'];

        $this->cxsEnabled = TRUE;
        $this->userCxsFilter = [':cl_oro_products.id_vyrobku', ':cl_oro_products.vyrobce', ':cl_oro_products.nazev',
            ':cl_oro_items.odberatel'];

        $this->DefSort = 'id DESC';

        /*$testDate = new \Nette\Utils\DateTime;
        $testDate = $testDate->modify('-1 day');
        $this->conditionRows = array( array('due_date','<=',$testDate, 'color:red', 'lastcond'), array('price_payed','<=','price_e2_vat', 'color:green'));
         *
         */
        $testDate = new \Nette\Utils\DateTime;
        //$testDate = $testDate->modify('-1 day');
        $testDate->setTime(0, 0, 0);

        /*$this->conditionRows = array( array('due_date','<',$testDate, 'color:red', 'notlastcond'),
                          array('pay_date','==',NULL, 'color:red', 'lastcond'),
                          array('due_date','>=',$testDate, 'color:green', 'notlastcond'),
                          array('pay_date','==',NULL, 'color:green', 'lastcond'));	*/


        //if (!($currencyRate = $this->CurrenciesManager->findOneBy(array('currency_name' => $settings->def_mena))->fix_rate))
//		$currencyRate = 1;

        //08.10.2019 - default storage for company branch

        $defDueDate = new \Nette\Utils\DateTime;

        $tmpDefStatus	= null;
        $tmpStatus = 'oro';
        if ($nStatus = $this->StatusManager->findAll()->
                 where('status_use = ? AND s_new = ?',$tmpStatus, 1)->fetch())
        {
             $tmpDefStatus	= $nStatus->id;
        }

        $this->defValues = ['oznameni_za_den' => new \Nette\Utils\DateTime,
            'id_oznameni' =>  Uuid::uuid4()->toString(),
            'odesilatel_dic' => $this->settings->dic,
            'typ_podani' => 'Radne',
            'cl_status_id' => $tmpDefStatus,
            'cl_users_id' => $this->user->getId()];
        //$this->numberSeries = 'commission';
        $this->numberSeries = [];
        $this->readOnly = ['dn_number' => TRUE,
            'created' => TRUE,
            'create_by' => TRUE,
            'changed' => TRUE,
            'change_by' => TRUE];


        $this->toolbar = [0 => ['group_start' => ''],
            1 => ['url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nové_podání'), 'class' => 'btn btn-primary'],
            2 => $this->getNumberSeriesArray('delivery_note_in'),
            3 => ['group_end' => ''],
        ];

        $this->rowFunctions = ['copy' => 'disabled'];


        //settings for CSV attachments
        $this->csv_h = ['columns' => 'dn_number,issue_date,delivery_date,dn_title,cl_partners_book.company,cl_partners_book_workers.worker_name,cl_currencies.currency_code,price_e2,price_e2_vat,price_correction,price_base0,price_base1,price_base2,price_base3,
                                            price_vat1,price_vat2,price_vat3,vat1,vat2,vat3,price_payed,cl_delivery_note_in.header_txt,cl_delivery_note_in.footer_txt,storno'];
        $this->csv_i = ['columns' => 'item_order,cl_pricelist.ean_code,cl_pricelist.order_code,cl_pricelist.identification,cl_delivery_note_in_items.item_label,cl_pricelist.order_label,cl_delivery_note_in_items.quantity,cl_delivery_note_in_items.units,cl_storage.name AS storage_name,cl_delivery_note_in_items.price_e,cl_delivery_note_in_items.discount,cl_delivery_note_in_items.price_e2,cl_delivery_note_in_items.price_e2_vat,cl_delivery_note_in_items.vat',
            'datasource' => 'cl_delivery_note_in_items'];

        $this->bscOff = FALSE;
        $this->bscEnabled = $this->getUser()->getIdentity()->bsc_enabled;
        $this->bscPages = ['card' => ['active' => false, 'name' => $this->translator->translate('karta'), 'lattefile' => $this->getLattePath() . 'Oro\card.latte'],
            'items' => ['active' => true, 'name' => $this->translator->translate('Položky'), 'lattefile' => $this->getLattePath() . 'Oro\items.latte'],
            'itemsback' => ['active' => false, 'name' => $this->translator->translate('Produkty'), 'lattefile' => $this->getLattePath() . 'Oro\products.latte']
        ];

        $this->bscSums = [];
        $this->bscToolbar = [
            3 => ['url' => 'saveORO!', 'rightsFor' => 'read', 'label' => $this->translator->translate('ORO_XML'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="false"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-save'],
            4 => ['url' => 'savePDF!', 'rightsFor' => 'enable', 'label' => $this->translator->translate('Náhled'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="false"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-print'],
            5 => ['url' => 'downloadPDF!', 'rightsFor' => 'enable', 'label' => $this->translator->translate('PDF'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="false"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-save']
        ];
        $this->bscTitle = ['oznameni_za_den' => $this->translator->translate('Oznámení_za_den')];

        /*8 => array('url' => 'createInvoiceModalWindow!', 'rightsFor' => 'write', 'label' => $this->translator->translate('Faktura'), 'title' => $this->translator->translate('vytvoří_nebo_zaktualizuje_fakturu'), 'class' => 'btn btn-success',
        'data' => array('data-ajax="true"', 'data-history="false"'), 'icon' => 'glyphicon glyphicon-edit'),
        */
        //17.08.2018 - settings for documents saving and emailing
        //$this->docTemplate  =  __DIR__ . '/../templates/DeliveryNote/DeliveryNotev1.latte';
       $this->docTemplate = $this->ReportManager->getReport(__DIR__ . '/../templates/Oro/DayReport.latte'); //Precistec
       $this->docAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;
       $this->docTitle = ["Oro_denní_hlášení"];

    }

    public function renderDefault($page_b = 1, $idParent, $filter, $sortKey, $sortOrder, $filterColumn, $filterValue, $modal, $cxs)
    {
        parent::renderDefault($page_b, $idParent, $filter, $sortKey, $sortOrder, $filterColumn, $filterValue, $modal, $cxs);

    }

    public function renderEdit($id, $copy, $modal)
    {
        parent::renderEdit($id, $copy, $modal);
        $tmpData = $this->DataManager->find($this->id);
        $this->checkedValues = $this->DataManager->findAll()->where('id = ? ', $this->id)->select('id')->fetchPairs('id', 'id');
    }

    protected function createComponentEdit($name)
    {
        $form = new Form($this, $name);
        //$form->setMethod('POST');
        $form->addHidden('id', NULL);
        $form->addText('oznameni_za_den', $this->translator->translate('Oznámení_za_den'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm');
        $arrStatus = $this->StatusManager->findAll()->where('status_use = ?', 'oro')->fetchPairs('id', 'status_name');
        $form->addSelect('cl_status_id', $this->translator->translate("Stav"), $arrStatus)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_stav_podání'))
            ->setRequired($this->translator->translate('Vyberte_prosím_stav_podání'))
            ->setPrompt($this->translator->translate('Zvolte_stav_podání'));

        $arrType = ['Radne' => 'řádné', 'Opakovane' => 'opakované'];
        $form->addSelect('typ_podani', $this->translator->translate("Typ_podání"), $arrType)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_typ_podání'))
            ->setRequired($this->translator->translate('Vyberte_prosím_typ_podání'))
            ->setPrompt($this->translator->translate('Zvolte_typ_podání'));

        $form->addTextArea('description_txt', $this->translator->translate('Poznámka'), 40,5)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Prostor_pro_vaši_poznámku'));
        $form->addText('odesilatel_dic', $this->translator->translate('DIČ_odesílatele'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm');

        $form->onValidate[] = [$this, 'FormValidate'];
        $form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('back', $this->translator->translate('Zpět'))
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = [$this, 'stepBack'];
        $form->onSuccess[] = [$this, 'SubmitEditSubmitted'];
        return $form;
    }

    public function stepBack()
    {
        $this->redirect('default');
    }

    public function FormValidate(Form $form)
    {
        $data = $form->values;
        /*02.12.2020 - cl_partners_book_id required and prepare data for just created partner
        */
        //$form->addError('Partner musí být vybrán');
        $this->redrawControl('content');
    }

    public function SubmitEditSubmitted(Form $form)
    {
        $data = $form->values;

        //later there must be another condition for user rights, admin can edit everytime
        if ($form['send']->isSubmittedBy() ) {

            $data = $this->RemoveFormat($data);

            $myReadOnly = isset($this->DataManager->find($data['id'])->cl_status_id) && $this->DataManager->find($data['id'])->cl_status->s_fin == 1;
            $myReadOnly = false;
            if (!($myReadOnly)) {//if record is not marked as finished, we can save edited data
                if (!empty($data->id)) {

                    $this->DataManager->update($data, TRUE);

                    $this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
                } else {
                    //$row=$this->DataManager->insert($data);
                    //$this->newId = $row->id;
                    //$this->flashMessage('Nový záznam byl uložen.', 'success');
                }
            } else {
                $this->flashMessage($this->translator->translate('Změny_nebyly_uloženy'), 'warning');
            }

            $this->redrawControl('flash');
            $this->redrawControl('formedit');
            $this->redrawControl('timestamp');
            $this->redrawControl('items');
            $this->redrawControl('content');

        } else {
            $this->flashMessage($this->translator->translate('Změny_nebyly_uloženy'), 'warning');
            $this->redrawControl('flash');
            $this->redrawControl('formedit');
            $this->redrawControl('timestamp');
            $this->redrawControl('items');
            $this->redirect('default');

            //$this->redirect('default');
        }
    }


    public function UpdateSum()
    {
//        $this->DeliveryNoteInManager->updateSum($this->id);
        parent::UpdateSum();
//        $this['deliveryNotelistgrid']->redrawControl('editLines');
    }

    public function beforeAddLine($data)
    {
        /*if ($data['control_name'] == "deliveryNotelistgrid") {
            $data['price_e_type'] = $this->settings->price_e_type;
        }*/
        return $data;
    }

    public function ListGridInsert($sourceData, $dataManager)
    {

        return (FALSE);

    }


    //control method to determinate if we can delete
    public function beforeDelete($lineId, $name = "")
    {
        $result = TRUE;
        return $result;
    }


    //aditional control before delete from baseList
    public function beforeDeleteBaseList($id)
    {
        return TRUE;
    }

    public function workSetStatus()
    {
        $this->setStatus($this->id, ['status_use' => 'oro',
            's_new' => 0,
            's_work' => 1]);
    }

    public function DataProcessMain($defValues, $data)
    {

        return $defValues;
    }


    //aditional processing data after save in listgrid
    //23.11.2018 - there must be giveout from store and receiving backitems
    public function afterDataSaveListGrid($dataId, $name = NULL)
    {
        parent::afterDataSaveListGrid($dataId, $name);

    }


    public function afterCopy($newLine, $oldLine)
    {

    }




    //validating of data from listgrid
    public function DataProcessListGridValidate($data)
    {
        $retVal = NULL;
        /*if (isset($data['cl_pricelist_id']) && isset($data['quantity'])) {
            if ($data['cl_pricelist_id'] > 0 && $data['quantity'] < 0 && $this->settings->invoice_to_store == 1) {
                $retVal = $this->translator->translate('Množství_pro_výdej_nesmí_být_záporné,_pokud_jde_o_položku_ceníku.');
            }
        }*/

        return $retVal;
    }

    public function handleDeleteGenerated(){
        $tmpData = $this->DataManager->find($this->id);
        if ($tmpData && $tmpData->cl_status['s_fin'] == 0) {
            $this->OroItemsManager->findAll()->where('cl_oro_id = ?', $this->id)->delete();
            $this->OroProductsManager->findAll()->where('cl_oro_id = ?', $this->id)->delete();
            $this->flashMessage($this->translator->translate('Obsah_byl_vymazán'),'success');
        }else{
            $this->flashMessage($this->translator->translate('Doklad_je_uzavřen_mazání_není_možné'),'danger');
        }

        $this->redrawControl('content');
    }


    public function handleGenerate(){
        $tmpData = $this->DataManager->find($this->id);
        if ($tmpData && $tmpData->cl_status['s_fin'] == 0){
            $sourceProducts = $this->InvoiceManager->findAll()->
                                select(':cl_invoice_items.cl_pricelist_id, :cl_invoice_items.cl_pricelist.item_label, :cl_invoice_items.cl_pricelist.identification, :cl_invoice_items.cl_pricelist.cl_producer_id,
                                                :cl_invoice_items.cl_pricelist.ean_code, :cl_invoice_items.cl_pricelist.volume, :cl_invoice_items.cl_pricelist.volume_unit, :cl_invoice_items.cl_pricelist.percent')->
                                where('vat_date = ? AND :cl_invoice_items.cl_pricelist.cl_pricelist_categories.alcohol_oro =1 AND 
                                    ((cl_partners_branch_id IS NOT NULL AND cl_partners_branch.b_ico != "" AND cl_partners_branch.use_as_main = 1) OR
                                    (cl_partners_branch_id IS NULL AND cl_invoice.cl_partners_book_id IS NOT NULL AND cl_partners_book.ico != "")) ', $tmpData['oznameni_za_den'])->
                                group(':cl_invoice_items.cl_pricelist_id')->
                                order(':cl_invoice_items.cl_pricelist.identification');

            $sourceProducts2 = $this->InvoiceManager->findAll()->
                                select(':cl_invoice_items_back.cl_pricelist_id, :cl_invoice_items_back.cl_pricelist.item_label, :cl_invoice_items_back.cl_pricelist.identification, :cl_invoice_items_back.cl_pricelist.cl_producer_id,
                                                                    :cl_invoice_items_back.cl_pricelist.ean_code, :cl_invoice_items_back.cl_pricelist.volume, :cl_invoice_items_back.cl_pricelist.volume_unit, :cl_invoice_items_back.cl_pricelist.percent')->
                                where('vat_date = ? AND :cl_invoice_items_back.cl_pricelist.cl_pricelist_categories.alcohol_oro =1 AND 
                                                        ((cl_partners_branch_id IS NOT NULL AND cl_partners_branch.b_ico != "" AND cl_partners_branch.use_as_main = 1) OR
                                                        (cl_partners_branch_id IS NULL AND cl_invoice.cl_partners_book_id IS NOT NULL AND cl_partners_book.ico != "")) ', $tmpData['oznameni_za_den'])->
                                group(':cl_invoice_items_back.cl_pricelist_id')->
                                order(':cl_invoice_items_back.cl_pricelist.identification');

            $sourceData = $this->InvoiceManager->findAll()->
                                select(':cl_invoice_items.cl_pricelist_id, :cl_invoice_items.cl_pricelist.identification, cl_invoice.cl_partners_book_id, cl_invoice.cl_partners_branch_id, SUM(:cl_invoice_items.quantity) AS pocet')->
                                where('vat_date = ? AND :cl_invoice_items.cl_pricelist.cl_pricelist_categories.alcohol_oro =1 AND :cl_invoice_items.cl_pricelist_id IS NOT NULL AND
                                                        ((cl_partners_branch_id IS NOT NULL AND cl_partners_branch.b_ico != "" AND cl_partners_branch.use_as_main = 1) OR
                                                        (cl_partners_branch_id IS NULL AND cl_invoice.cl_partners_book_id IS NOT NULL AND cl_partners_book.ico != "")) ', $tmpData['oznameni_za_den'])->
                                group('cl_partners_book_id, cl_partners_branch_id, :cl_invoice_items.cl_pricelist_id')->
                                order('cl_partners_book.company, :cl_invoice_items.cl_pricelist.identification');

            $sourceData2 = $this->InvoiceManager->findAll()->
                                select(':cl_invoice_items_back.cl_pricelist_id, :cl_invoice_items_back.cl_pricelist.identification, cl_invoice.cl_partners_book_id, cl_invoice.cl_partners_branch_id, SUM(:cl_invoice_items_back.quantity) AS pocet')->
                                where('vat_date = ? AND :cl_invoice_items_back.cl_pricelist.cl_pricelist_categories.alcohol_oro = 1 AND :cl_invoice_items_back.cl_pricelist_id IS NOT NULL AND
                                                                            ((cl_partners_branch_id IS NOT NULL AND cl_partners_branch.b_ico != "" AND cl_partners_branch.use_as_main = 1) OR
                                                                            (cl_partners_branch_id IS NULL AND cl_invoice.cl_partners_book_id IS NOT NULL AND cl_partners_book.ico != "")) ', $tmpData['oznameni_za_den'])->
                                group('cl_partners_book_id, cl_partners_branch_id, :cl_invoice_items_back.cl_pricelist_id')->
                                order('cl_partners_book.company, :cl_invoice_items_back.cl_pricelist.identification');

            //products
            $arrProducts = [];
            //$itemOrder = $this->DataManager->findAll()->where('cl_oro_id = ?', $this->id)->max('item_order');
            $itemOrder = 1;
            foreach($sourceProducts as $key => $one){
                $arrProducts['cl_oro_id'] = $this->id;
                $arrProducts['item_order'] = $itemOrder;
                $arrProducts['cl_pricelist_id'] = $one['cl_pricelist_id'];
                $arrProducts['id_vyrobku'] = $one['identification'];
                $arrProducts['nazev'] = $one['item_label'];

                $tmpUnit = $this->ArraysManager->getVolumeRatesToBase($one['volume_unit']);
                $tmpVolume = $one['volume'] / $tmpUnit;
                $arrProducts['objem'] = $tmpVolume * 1000; //from another units to mililiters
                $arrProducts['ean'] = $one['ean_code'];
                $arrProducts['procento_lihu'] = $one['percent'];
                $tmpVyrobce = $this->PartnersManager->find($one['cl_producer_id'])->company;
                $arrProducts['vyrobce'] = is_null($tmpVyrobce) ? '' : $tmpVyrobce;
                $this->OroProductsManager->insert($arrProducts);
                $itemOrder++;
            }

            $arrProducts = [];
            foreach($sourceProducts2 as $key => $one){
                //try to find
                $tmpProducts = $this->OroProductsManager->findAll()->where('cl_pricelist_id = ? AND cl_oro_id = ?', $one['cl_pricelist_id'], $this->id)->fetch();
                if (!$tmpProducts) {
                    $arrProducts['cl_oro_id'] = $this->id;
                    $arrProducts['item_order'] = $itemOrder;
                    $arrProducts['cl_pricelist_id'] = $one['cl_pricelist_id'];
                    $arrProducts['id_vyrobku'] = $one['identification'];
                    $arrProducts['nazev'] = $one['item_label'];

                    $tmpUnit = $this->ArraysManager->getVolumeRatesToBase($one['volume_unit']);
                    $tmpVolume = $one['volume'] / $tmpUnit;
                    $arrProducts['objem'] = $tmpVolume * 1000; //from another units to mililiters
                    $arrProducts['ean'] = $one['ean_code'];
                    $arrProducts['procento_lihu'] = $one['percent'];
                    $tmpVyrobce = $this->PartnersManager->find($one['cl_producer_id'])->company;
                    $arrProducts['vyrobce'] = is_null($tmpVyrobce) ? '' : $tmpVyrobce;
                    $this->OroProductsManager->insert($arrProducts);
                    $itemOrder++;
                }
            }




            //items out
            $arrItemsOut = [];
            $itemOrder = 1;
            foreach($sourceData as $key => $one){
                if ($one['pocet'] > 0) {
                    $arrItemsOut['cl_oro_id'] = $this->id;
                    $arrItemsOut['item_order'] = $itemOrder;
                    $arrItemsOut['cl_partners_book_id'] = $one['cl_partners_book_id'];
                    $arrItemsOut['cl_pricelist_id'] = $one['cl_pricelist_id'];
                    $arrItemsOut['typ'] = 0;
                    $arrItemsOut['id_vyrobku'] = $one['identification'];
                    $arrItemsOut['pocet'] = $one['pocet'];
                    if (!is_null($one['cl_partners_book_id']) && !is_null($one['cl_partners_branch_id']))
                        $arrItemsOut['odberatel'] = json_encode(['company' => $one->cl_partners_branch['b_name'], 'ico' => $one->cl_partners_branch['b_ico'], 'dic' => $one->cl_partners_branch['b_dic']]);
                    elseif (!is_null($one['cl_partners_book_id']) && is_null($one['cl_partners_branch_id']) )
                        $arrItemsOut['odberatel'] = json_encode(['company' => $one->cl_partners_book['company'], 'ico' => $one->cl_partners_book['ico'], 'dic' => $one->cl_partners_book['dic']]);

                    $this->OroItemsManager->insert($arrItemsOut);
                    $itemOrder++;
                }
            }
            //items in
            $arrItemsIn = [];
            foreach($sourceData2 as $key => $one){
                if ($one['pocet'] > 0) {
                    $arrItemsIn['cl_oro_id'] = $this->id;
                    $arrItemsIn['item_order'] = $itemOrder;
                    $arrItemsIn['cl_partners_book_id'] = $one['cl_partners_book_id'];
                    $arrItemsIn['cl_pricelist_id'] = $one['cl_pricelist_id'];
                    $arrItemsIn['typ'] = 1;
                    if (!is_null($one['identification'])) {
                        $arrItemsIn['id_vyrobku'] = $one['identification'];
                        $arrItemsIn['pocet'] = $one['pocet'];
                        if (!is_null($one['cl_partners_book_id']) && !is_null($one['cl_partners_branch_id']))
                            $arrItemsIn['odberatel'] = json_encode(['company' => $one->cl_partners_branch['b_name'], 'ico' => $one->cl_partners_branch['b_ico'], 'dic' => $one->cl_partners_branch['b_dic']]);
                        elseif (!is_null($one['cl_partners_book_id']) && is_null($one['cl_partners_branch_id']) )
                            $arrItemsIn['odberatel'] = json_encode(['company' => $one->cl_partners_book['company'], 'ico' => $one->cl_partners_book['ico'], 'dic' => $one->cl_partners_book['dic']]);

                    }
                    $this->OroItemsManager->insert($arrItemsIn);
                    $itemOrder++;
                }
            }

            $tmpItems = $this->OroItemsManager->findAll()->where('cl_oro_id = ?', $this->id);
            foreach($tmpItems as $key => $one){
                $tmpProd  = $this->OroProductsManager->findall()->where('cl_oro_id = ? AND cl_pricelist_id = ?', $this->id, $one['cl_pricelist_id'])->fetch();
                if ($tmpProd){
                    $one->update(['id_vyrobku' => $tmpProd['item_order']]);
                }
            }

            $this->flashMessage($this->translator->translate('Obsah_byl_vygenerován'),'success');
        }else{
            $this->flashMessage($this->translator->translate('Doklad_je_uzavřen_generování_není_možné'),'danger');
        }
        $this->redrawControl('content');
    }

    public function getOdberatel($arrData){
        $tmpArr = json_decode($arrData['odberatel'], true);
        $strRet = '';
        if ($tmpArr) {
            $strRet = $tmpArr['company'] . ', IČ: ' . $tmpArr['ico'] . ', DIČ: ' . $tmpArr['dic'];
        }
        return $strRet;

    }

    public function getTypPohyb($arrData){

        return $this->ArraysManager->getOroPohybName($arrData['typ']);
    }

    public function safeXML($string){
        return htmlspecialchars($string, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    public function handleSaveORO()
    {
        $tmpData = $this->DataManager->find($this->id);
        if ($tmpData){
            if (!$this->ValidateOro($tmpData))
                {
                    $this->redrawControl('this');
                    return;
                }
            $tmpStr = '<?xml version="1.0" encoding="utf-8"?>
<CZL002 xmlns ="http://www.celnisprava.cz/XMLSchema/RZL/Oznameni/CZL002/1.3.1"
xmlns:t ="http://www.celnisprava.cz/XMLSchema/RZL/Oznameni/Typy/1.3.1"> <Hlavicka>
<t:IDOznameni>' . $tmpData['id_oznameni']. '</t:IDOznameni> <t:Vytvoreno>' . $tmpData['created']->format('c'). '</t:Vytvoreno> <t:OznameniZaDen>' . $tmpData['oznameni_za_den']->format('Y-m-d') .
                '</t:OznameniZaDen> <t:TypPodani>' . $tmpData['typ_podani'] . '</t:TypPodani> <t:OdesilatelDIC>' . $tmpData['odesilatel_dic'] . '</t:OdesilatelDIC>
  </Hlavicka>';


            $tmpStr .= '<Vyrobky>';
            foreach ($tmpData->related('cl_oro_products') as $key => $one)
            {
                $tmpStr .= '<t:Vyrobek>';
                $tmpStr .= '<t:IDVyrobku>' . $one['item_order'] . '</t:IDVyrobku>';
                $tmpStr .= '<t:Vyrobce>' . $this->safeXML($one['vyrobce']) . '</t:Vyrobce>';
                $tmpStr .= '<t:Nazev>' . $this->safeXML($one['nazev']) . '</t:Nazev>';
                $tmpStr .= '<t:Objem>' . $this->safeXML($one['objem']) . '</t:Objem>';
                $tmpStr .= '<t:EAN>' . $this->safeXML($one['ean']) . '</t:EAN>';
                $tmpStr .= '<t:ProcentoLihu>' . number_format($one['procento_lihu'],1) . '</t:ProcentoLihu>';
                $tmpStr .= '</t:Vyrobek>';
            }
            $tmpStr .= '</Vyrobky>';

            $tmpItems = $this->OroItemsManager->findAll()->where('cl_oro_id = ?', $this->id);
            foreach($tmpItems as $key => $one){
                $tmpProd  = $this->OroProductsManager->findAll()->where('cl_oro_id = ? AND cl_pricelist_id = ?', $this->id, $one['cl_pricelist_id'])->fetch();
                if ($tmpProd){
                    $one->update(['id_vyrobku' => $tmpProd['item_order']]);
                }
            }


            $tmpStr .= '<Polozky>';
            $arrPartners = $tmpData->related('cl_oro_items')->select('cl_partners_book_id')->order('cl_partners_book_id')->fetchPairs('cl_partners_book_id','cl_partners_book_id');
            $tmpPartners = $this->OroItemsManager->findAll()->where('cl_oro_id = ? AND cl_partners_book_id IN (?)', $this->id, $arrPartners)->order('cl_partners_book_id')->group('cl_partners_book_id');
            foreach ($tmpPartners as $key => $one)
            {
                $tmpStr .= '<Polozka>';
                $tmpStr .= '<Odberatel>';
                $arrOdberatel = json_decode($one['odberatel'], true);
                $tmpStr .= '<t:IC>' . $this->safeXML($arrOdberatel['ico']) . '</t:IC>';
                $tmpStr .= '<t:DIC>' . $this->safeXML($arrOdberatel['dic']) . '</t:DIC>';
                $tmpStr .= '<t:Nazev>' . $this->safeXML($arrOdberatel['company']) . '</t:Nazev>';
                $tmpStr .= '</Odberatel>';
                $tmpItems = $tmpData->related('cl_oro_items')->where('typ = ? AND cl_partners_book_id = ?', 0, $one['cl_partners_book_id'])->order('id_vyrobku');
                if ($tmpItems->count() > 0)
                {
                    $tmpStr .= '<VolnyObeh>';
                    foreach ($tmpItems as $key2 => $one2)
                    {
                        $tmpStr .= '<t:PohybLihu>';
                        $tmpStr .= '<t:IDVyrobku>' . $one2['id_vyrobku'] . '</t:IDVyrobku>';
                        $tmpStr .= '<t:Pocet>' . $one2['pocet'] . '</t:Pocet>';
                        $tmpStr .= '</t:PohybLihu>';
                    }
                    $tmpStr .= '</VolnyObeh>';
                }

                $tmpItems = $tmpData->related('cl_oro_items')->where('typ = ? AND cl_partners_book_id = ?', 1, $one['cl_partners_book_id'])->order('id_vyrobku');
                if ($tmpItems->count() > 0)
                {
                    $tmpStr .= '<VraceniVolnehoObehu>';
                    foreach ($tmpItems as $key2 => $one2)
                    {
                        $tmpStr .= '<t:PohybLihu>';
                        $tmpStr .= '<t:IDVyrobku>' . $one2['id_vyrobku'] . '</t:IDVyrobku>';
                        $tmpStr .= '<t:Pocet>' . $one2['pocet'] . '</t:Pocet>';
                        $tmpStr .= '</t:PohybLihu>';
                    }
                    $tmpStr .= '</VraceniVolnehoObehu>';
                }
                $tmpStr .= '</Polozka>';

            }
            $tmpStr .= '</Polozky>';
            $tmpStr .= '</CZL002>';

            $httpResponse = $this->getHttpResponse();
            $httpResponse->setContentType('text/xml');
            $dtmNow = new DateTime();
            $httpResponse->setHeader('Content-Disposition', 'attachment; filename="ORO denní hlášení ' . $dtmNow->format('d-m-Y') . '.xml"');
            $httpResponse->setHeader('Content-Length', strlen($tmpStr));
            print $tmpStr;
            $this->terminate();
        }
    }

    private function ValidateOro($tmpData){
        $tmpPartners = $tmpData->related('cl_oro_items')->select('cl_partners_book.ico, cl_partners_book.dic, cl_partners_book.company')->where('cl_partners_book.ico = ? OR cl_partners_book.dic = ? OR cl_partners_book.company = ? ', "", "", "");
        $count = 0;
        foreach($tmpPartners as $key => $one){
            $this->flashMessage('Neúplné zadání odběratele. ' . $one['company'] . ' IČO: ' . $one['ico'] . ' &nbsp; Chybí název, DIČ nebo IČO', 'error');
            $count++;
        }
        return $count == 0;
    }
}

    


