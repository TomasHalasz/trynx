<?php

namespace App\ApplicationModule\Presenters;

use App\Controls;
use http\Exception;
use Nette\Application\UI\Form,
    Nette\Image;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PaymentOrderPresenter extends \App\Presenters\BaseListPresenter {

    public $pairedDocsShow = FALSE;

    
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



    public $paymentModalShow = FALSE;
    
    /**
    * @inject
    * @var \App\Model\PaymentOrderManager
    */
    public $DataManager;

    /**
     * @inject
     * @var \App\Model\PaymentOrderItemsManager
     */
    public $PaymentOrderItemsManager;

    /**
     * @inject
     * @var \App\Model\PairedDocsManager
     */
    public $PairedDocsManager;


    /**
     * @inject
     * @var \App\Model\BankAccountsManager
     */
    public $BankAccountsManager;

    /**
     * @inject
     * @var \App\Model\InvoiceManager
     */
    public $InvoiceManager;

    /**
     * @inject
     * @var \App\Model\InvoiceArrivedManager
     */
    public $InvoiceArrivedManager;

    /**
    * @inject
    * @var \App\Model\ArraysManager
    */
    public $ArraysManager;




    /**
     * @inject
     * @var \App\Model\ChatManager
     */
    public $ChatManager;

    /**
     * @inject
     * @var \App\Model\EmailingManager
     */
    public $EmailingManager;
   
   
    protected function startup()
    {
	parent::startup();
    $this->formName = $this->translator->translate("Platební_příkazy");
    $this->mainTableName = 'cl_payment_order';

	$this->dataColumns = [
        'po_number' => [$this->translator->translate('Číslo_příkazu'), 'size' => 10, 'format' => 'text'],
        'cl_status.status_name' => [$this->translator->translate('Stav'), 'format' => 'colortag'],
        'cl_bank_accounts.bank_name' => [$this->translator->translate('Název_banky'), 'size' => 10, 'format' => 'text'],
        'cl_bank_accounts.account_number' => [$this->translator->translate('Číslo_účtu'), 'size' => 20, 'format' => 'text'],
        'cl_bank_accounts.bank_code' => [$this->translator->translate('Kód_banky'), 'size' => 10, 'format' => 'text'],
        'cl_bank_accounts.cl_currencies.currency_code' => [$this->translator->translate('Měna'), 'size' => 10, 'format' => 'text'],
        'pay_date' => [$this->translator->translate('Datum_splatnosti'), 'size' => 10, 'format' => 'date'],
        'exported' => [$this->translator->translate('Exportováno'), 'size' => 8, 'format' => 'boolean'],
        'storno' => [$this->translator->translate('Stornováno'), 'size' => 8, 'format' => 'boolean'],
        'cl_users.name' => [$this->translator->translate('Pracovník'), 'size' => 20, 'format' => 'text'],
        'amount__' => [$this->translator->translate('Celkem_k_platbě'), 'size' => 20, 'format' => 'decimal', 'function' => 'getAmount', 'function_param' => ['id']],
        'created' => [$this->translator->translate('Vytvořeno'),'format' => 'datetime'],
        'create_by' => $this->translator->translate('Vytvořil'),
        'changed' => [$this->translator->translate('Změněno'),'format' => 'datetime'],
        'change_by' => $this->translator->translate('Změnil')];

	//$this->FilterC = 'UPPER(currency_name) LIKE ?';
	//$this->filterColumns = array();	
	$this->DefSort = 'po_number DESC';
    $this->filterColumns = ['cl_bank_accounts.bank_name' => 'autocomplete','cl_bank_accounts.account_number' => 'autocomplete', 'cl_bank_accounts.bank_code' => 'autocomplete', 'pay_date' => 'none',
                            'cl_users.name' => 'autocomplete'];

    $this->userFilterEnabled = TRUE;
    $this->userFilter = ['po_number', 'cl_bank_accounts.bank_name', 'cl_bank_accounts.account_number', 'cl_users.name', 'cl_bank_accounts.bank_code'];

    $this->defValues = ['pay_date' => new \Nette\Utils\DateTime,
            'cl_users_id' => $this->user->getId()];

	$this->numberSeries = ['use' => 'payment_order', 'table_key' => 'cl_number_series_id', 'table_number' => 'po_number'];

    $this->bscOff = FALSE;
    $this->bscEnabled = $this->getUser()->getIdentity()->bsc_enabled;
    $this->bscPages = ['card' => ['active' => false, 'name' => $this->translator->translate('karta'), 'lattefile' => $this->getLattePath() . 'PaymentOrder\card.latte'],
            'items' => ['active' => true, 'name' => $this->translator->translate('položky_platebního_příkazu'), 'lattefile' => $this->getLattePath() . 'PaymentOrder\items.latte'],
        ];

        $this->bscSums = ['lattefile' => $this->getLattePath() . 'Invoice\sums.latte'];
        $this->bscToolbar = [
            1 => ['url' => 'exportGPC!', 'rightsFor' => 'write', 'label' => $this->translator->translate('export_gpc'), 'class' => 'btn btn-success',
                  'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-list-alt'],
            2 => ['url' => 'showPairedDocs!', 'rightsFor' => 'write', 'label' => $this->translator->translate('doklady'), 'class' => 'btn btn-success',
                  'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-list-alt'],
        ];
        $this->bscTitle = ['po_number' => $this->translator->translate('Číslo_příkazu'), 'cl_bank_accounts.account_number' => $this->translator->translate('Účet')];


	//$this->readOnly = array('identification' => TRUE);	
	//$settings = $this->CompaniesManager->getTable()->fetch();	
	$this->toolbar = [1 => ['url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nový_záznam'), 'class' => 'btn btn-primary'],
                        2 => ['url' => $this->link('ImportData:', ['modal' => $this->modal, 'target' => $this->name]), 'rightsFor' => 'write', 'label' => 'Import', 'class' => 'btn btn-primary'],];

        /*predefined filters*/
      /*  $pdCount = count($this->pdFilter);
        $pdCount2 = $pdCount;
        $this->pdFilter[++$pdCount2] = ['url' => $this->link('pdFilter!', ['index' => $pdCount2, 'pdFilterIndex' => $pdCount2]),
            'filter' => 'finished = 1',
            'sum' => [],
            'rightsFor' => 'read',
            'label' => $this->translator->translate('Hotové_úkoly'),
            'title' => $this->translator->translate('Hotové_úkoly'),
            'data' => ['data-ajax="true"', 'data-history="true"'],
            'class' => 'ajax', 'icon' => 'iconfa-filter'];

        $this->pdFilter[++$pdCount2] = ['url' => $this->link('pdFilter!', ['index' => $pdCount2, 'pdFilterIndex' => $pdCount2]),
            'filter' => 'finished = 0',
            'sum' => [],
            'rightsFor' => 'read',
            'label' => $this->translator->translate('Nehotové_úkoly'),
            'title' => $this->translator->translate('Nehotové_úkoly'),
            'data' => ['data-ajax="true"', 'data-history="true"'],
            'class' => 'ajax', 'icon' => 'iconfa-filter'];
*/
  /*      $this->previewLatteFile = '../../../' . $this->getLattePath() . 'Task\previewContent.latte';
        $this->enabledPreviewDoc = TRUE;

        $this->chatEnabled = true;
*/

    }



    
    public function renderDefault($page_b = 1,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal, $cxs)  {
	    parent::renderDefault($page_b,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal,$cxs);

    }
    
    public function renderEdit($id,$copy,$modal){
	    parent::renderEdit($id,$copy,$modal);
        $this->template->exportedFiles = $this->FilesManager->findAll()->where('cl_payment_order_id = ?', $this->id);
    }

    protected function createComponentHelpbox()
    {
        return new Controls\HelpboxControl($this->translator, "");
    }



    protected function createComponentPairedDocs()
    {
        return new PairedDocsControl($this->DataManager, $this->id, $this->PairedDocsManager, $this->translator);
    }


    protected function createComponentSumOnDocs()
    {
        $data = $this->DataManager->findAll()
                            ->where('cl_payment_order.id = ?', $this->id)
                            ->select('SUM(:cl_payment_order_items.amount) AS amount, :cl_payment_order_items.cl_currencies.currency_code AS currency_code')
                            ->group(':cl_payment_order_items.cl_currencies.currency_code');
        $dataArr = [];
        foreach ($data as $key => $one) {
            bdump($one);
            $dataArr[$key] =
                ['name' => $one['currency_code'], 'value' => $one['amount']];
        }
        return new SumOnDocsControl($this->translator,
            $this->DataManager, $this->id, $this->settings, $dataArr);
    }



    protected function createComponentPreviewContent()
    {
        return new \Controls\PreviewContent($this->previewLatteFile, $this->DataManager, NULL, NULL);
    }

    
    protected function createComponentEdit($name)
    {	
        $form = new Form($this, $name);
	    $form->addHidden('id',NULL);

        $form->addText('po_number', $this->translator->translate('Číslo_příkazu'), 20, 20)
            ->setHtmlAttribute('readonly', true);

        $arrBank = $this->BankAccountsManager->findAll()->
        select('cl_bank_accounts.id, CONCAT(cl_currencies.currency_code, " ", account_number) AS account_number')->
        order('cl_currencies.currency_code')->fetchPairs('id', 'account_number');

        $form->addSelect('cl_bank_accounts_id', $this->translator->translate('Účet'), $arrBank)
            ->setPrompt($this->translator->translate('Zvolte_účet'))
            ->setTranslator(NULL)
            ->setHtmlAttribute('data-type', 'account')
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_účet'))
            ->setHtmlAttribute('class', 'form-control chzn-select input-sm');

        /*$arrCurrencies = $this->CurrenciesManager->findAll()->order('currency_code')->fetchPairs('id', 'currency_code');
        $form->addSelect('cl_currencies_id', $this->translator->translate('Měna'), $arrCurrencies)
            ->setTranslator(NULL)
            ->setHtmlAttribute('data-placeholder', 'Zvolte')
            ->setHtmlAttribute('class', 'form-control chzn-select input-sm')
            ->setHtmlAttribute('data-type', 'currency')
            ->setPrompt($this->translator->translate('Zvolte_měnu'));*/

        $tmpArrStatus = $this->StatusManager->findAll()->where('status_use = ?', 'payment_order')->order('status_name')->fetchPairs('id', 'status_name');
        $form->addSelect('cl_status_id', $this->translator->translate('Stav'), $tmpArrStatus)
            ->setTranslator(NULL)
            ->setHtmlAttribute('placeholder', 'Vyberte stav');


        $form->addCheckbox('pay_date_fixed', $this->translator->translate('Společná_splatnost'))
            ->setDefaultValue(FALSE)
            ->setHtmlAttribute('class', 'items-show');

        $form->addCheckbox('storno', $this->translator->translate('Storno'))
            ->setDefaultValue(FALSE)
            ->setHtmlAttribute('class', 'items-show');

        $form->addCheckbox('exported', $this->translator->translate('Exportováno'))
            ->setDefaultValue(FALSE)
            ->setHtmlAttribute('class', 'items-show');



        $form->addTextArea('description_txt', $this->translator->translate('Poznámka'), 40, 4)
			->setHtmlAttribute('placeholder',$this->translator->translate('Poznámka'));


/*        $arrUsers = [];
        $arrUsers['Aktivní'] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 0')->fetchPairs('id', 'name');
        $arrUsers['Neaktivní'] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 1')->fetchPairs('id', 'name');

        $form->addSelect('cl_users_id', $this->translator->translate("Pracovník"), $arrUsers)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate("Pracovník"))
            ->setPrompt("");
*/
        $form->addText('pay_date', $this->translator->translate('Datum_splatnosti'), 10, 10)
            ->setHtmlAttribute('placeholder','Zadání');

        $form->addSubmit('send', $this->translator->translate('Uložit'))->setAttribute('class','btn btn-success');
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
		    ->setHtmlAttribute('class','btn btn-warning')
		    ->setValidationScope([])
		    ->onClick[] = array($this, 'stepBack');	    	    
		$form->onSuccess[] = array($this,'SubmitEditSubmitted');
            return $form;
    }

    public function stepBack()
    {	    
	    $this->redirect('default');
    }		

    public function SubmitEditSubmitted(Form $form)
    {
        $data=$form->values;
        if ($form['send']->isSubmittedBy())
        {
            $tmpOldData = $this->DataManager->find($this->id);
            $data = $this->removeFormat($data);
            if (!empty($data->id)){
                $this->DataManager->update($data, TRUE);
                if ($data['pay_date_fixed'] == 1){
                    $this->PaymentOrderItemsManager->findAll()->where('cl_payment_order_id = ?', $this->id)->update(['pay_date' => $data['pay_date']]);
                }
            }else
                $this->DataManager->insert($data);

        }
        $this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
        //$this->redirect('default');
        $this->redrawControl('content');
    }	    

    public function getAmount($arrData){
        $retVal = 0;
        if (!is_null($arrData['id'])) {
            $retVal = $this->PaymentOrderItemsManager->findAll()->where('cl_payment_order_id = ?',$arrData['id'])->sum('amount');
        }
        return $retVal;
    }


    public function createComponentPaymentOrderItems()
    {
        $tmpParentData = $this->DataManager->find($this->id);
        $arrCurrencies = $this->CurrenciesManager->findAll()->order('currency_code')->fetchPairs('id','currency_code');
        $arrData = [
            'amount' => [$this->translator->translate('Částka'),'format' => 'currency', 'class' => 'focus'],
            'cl_currencies.currency_code' => [$this->translator->translate('Měna'),'format' => 'chzn-select', 'values' => $arrCurrencies],
            'var_symb' => [$this->translator->translate('V.symbol'),'format' => 'text'],
            'pay_date' => [$this->translator->translate('Splatnost'),'format' => 'date'],
            'description_txt' => [$this->translator->translate('Popis'),'format' => 'text'],
            //'date_to' => array('Konec','format' => 'datetime2'),
            'account_code' => [$this->translator->translate('Protiúčet'),'format' => 'text'],
            'bank_code' => [$this->translator->translate('Kód_banky'),'format' => 'text'],
            'iban_code' => [$this->translator->translate('IBAN'),'format' => 'text'],
            'swift_code' => [$this->translator->translate('SWIFT'),'format' => 'text'],
            'spec_symb' => [$this->translator->translate('Spec_symbol'),'format' => 'text'],
            'konst_symb' => [$this->translator->translate('Konst_symbol'),'format' => 'text'],
            'company_name__' => [$this->translator->translate('Partner'), 'format' => 'text', 'function' => 'getPartner', 'function_param' => ['id']],
            'cl_invoice_arrived.rinv_number' => [$this->translator->translate('Faktura_přijatá'),  'format' => "url", 'size' => 12, 'url' => 'invoicearrived', 'value_url' => 'cl_invoice_arrived_id'],
            'cl_invoice.inv_number' => [$this->translator->translate('Faktura_vydaná'),  'format' => "url", 'size' => 12, 'url' => 'invoice', 'value_url' => 'cl_invoice_id']
        ];

        $now = new \Nette\Utils\DateTime;
        $control =  new Controls\ListgridControl(
            $this->translator,
            $this->PaymentOrderItemsManager, //model manager for showed data
            $arrData, //array(columnName,array(Name,Format))
            [], //condition rows
            $tmpParentData['id'], //parent ID for constraints
            ['cl_users_id' => $this->user->getId(), 'cl_currencies_id' => $tmpParentData->cl_currencies_id
            ], //default data for new record
            $this->DataManager, //parent data model manager
            FALSE, //pricelist model manager $this->PriceListManager
            FALSE, //pricelistpartner model manager $this->PriceListPartnerManager
            TRUE, //TRUE/FALSE enable add empty row without selecting from pricelist
            [
            ], //'pricelist2' => $this->link('RedrawPriceList2!'),
            //'duedate' => $this->link('RedrawDueDate2!')
            //array of urls in presenter which are used from component
            FALSE, //movable row
            'id', //ordercolumn
            FALSE, //selectmode
            [], //quicksearch
            "", //fontsize
            null, //parentcolumnname
            FALSE //pricelistbottom
        );

        $control->onChange[] = function ()
        {
            $this->UpdateSum();

        };
        $control->setContainerHeight('auto');

        $control->setToolbar([
            1 => ['url' => $this->link('InvoiceArrivedShow!'), 'rightsFor' => 'write', 'data' => ['data-history=false'],
                'label' => $this->translator->translate('Faktury_přijaté'), 'title' => $this->translator->translate('Zobrazí_výběr_faktur_přijatých_k_úhradě'), 'class' => 'btn btn-primary', 'icon' => 'iconfa-add'],
            2 => ['url' => $this->link('InvoiceShow!'), 'rightsFor' => 'write', 'data' => ['data-history=false'],
                'label' => $this->translator->translate('Opravné_doklady'), 'title' => $this->translator->translate('Zobrazí_výběr_opravných_dokladů_k_úhradě'), 'class' => 'btn btn-primary', 'icon' => 'iconfa-add'],
            3 => ['url' => $this->link('DeleteAll!'), 'rightsFor' => 'write', 'data' => ['data-history=false'],
                'label' => $this->translator->translate('Vymazat_obsah'), 'title' => $this->translator->translate('Vymaže_obsah_platebního_příkazu'), 'class' => 'btn btn-danger', 'icon' => 'iconfa-add'],

        ]);


        return $control;

    }

    public function createComponentInvoiceSelectorControl()
    {
        $tmpParentData = $this->DataManager->find($this->id);

        $arrCurrencies = $this->CurrenciesManager->findAll()->order('currency_code')->fetchPairs('id','currency_code');
        $arrData = [
            'price_e2_vat' => [$this->translator->translate('K_úhradě'),'format' => 'currency'],
            'price_payed' => [$this->translator->translate('Uhrazebno'),'format' => 'currency'],
            'cl_currencies.currency_code' => [$this->translator->translate('Měna'),'format' => 'text'],
            'var_symb' => [$this->translator->translate('V.symbol'),'format' => 'text'],
            'inv_number' => [$this->translator->translate('Doklad'), 'format' => 'url', 'size' => 12, 'url' => 'invoice', 'value_url' => 'id',],
            'due_date' => [$this->translator->translate('Splatnost'),'format' => 'date'],
            'cl_partners_book.company' => [$this->translator->translate('Dodavatel'),'format' => 'text'],
            'description_txt' => [$this->translator->translate('Popis'),'format' => 'text'],
            'cl_partners_account.account_code' => [$this->translator->translate('Protiúčet'),'format' => 'text'],
            'cl_partners_account.bank_code' => [$this->translator->translate('Kód_banky'),'format' => 'text'],
            'cl_partners_account.iban_code' => [$this->translator->translate('IBAN'),'format' => 'text'],
            'cl_partners_account.swift_code' => [$this->translator->translate('SWIFT'),'format' => 'text'],
            'cl_partners_account.spec_symb' => [$this->translator->translate('Spec_symbol'),'format' => 'text']
        ];

        $now = new \Nette\Utils\DateTime;
        $control =  new Controls\MasterselectorControl(
            $this->translator,
            $this->InvoiceManager, //model manager for showed data
            $arrData, //array(columnName,array(Name,Format))
            $this->id,
            ['cl_users_id' => $this->user->getId(), 'cl_currencies_id' => $tmpParentData->cl_currencies_id
            ], //default data for new record
            $this->DataManager //parent data model manager
        );

        $control->onChange[] = function ()
        {
            $this->UpdateSum();
        };
        $control->onInsertBtn[] = function ($dataItems, $name)
        {
            $this->handleInsertItems($dataItems, $name);
        };
        $control->setEnableSearch('var_symb LIKE ? OR description_txt LIKE ? OR cl_partners_book.company LIKE ?');
        $control->setFilter(['filter' => '(ABS(price_payed) < ABS(price_e2_vat) AND price_e2_vat < 0 AND cl_payment_types.payment_type = 0 AND cl_payment_order_id IS NULL)']);
        $control->setOrder('due_date DESC');
        $control->setContainerHeight('auto');
        return $control;
    }

    public function createComponentInvoiceArrivedSelectorControl()
    {
        $tmpParentData = $this->DataManager->find($this->id);
        $arrCurrencies = $this->CurrenciesManager->findAll()->order('currency_code')->fetchPairs('id','currency_code');
        $arrData = [
            'price_e2_vat' => [$this->translator->translate('K_úhradě'), 'format' => 'currency'],
            'price_payed' => [$this->translator->translate('Uhrazeno'), 'format' => 'currency'],
            'cl_currencies.currency_code' => [$this->translator->translate('Měna'),'format' => 'text'],
            'var_symb' => [$this->translator->translate('V.symbol'), 'format' => 'text'],
            'inv_number' => [$this->translator->translate('Doklad'), 'format' => 'url', 'size' => 12, 'url' => 'invoicearrived', 'value_url' => 'id',],
            'due_date' => [$this->translator->translate('Splatnost'), 'format' => 'date'],
            'cl_partners_book.company' => [$this->translator->translate('Dodavatel'), 'format' => 'text'],
            'description_txt' => [$this->translator->translate('Popis'), 'format' => 'text'],
            'cl_partners_account.account_code' => [$this->translator->translate('Protiúčet'), 'format' => 'text'],
            'cl_partners_account.bank_code' => [$this->translator->translate('Kód_banky'), 'format' => 'text'],
            'cl_partners_account.iban_code' => [$this->translator->translate('IBAN'), 'format' => 'text'],
            'cl_partners_account.swift_code' => [$this->translator->translate('SWIFT'), 'format' => 'text'],
            'cl_partners_account.spec_symb' => [$this->translator->translate('Spec_symbol'), 'format' => 'text']
        ];

        $now = new \Nette\Utils\DateTime;
        $control =  new Controls\MasterselectorControl(
            $this->translator,
            $this->InvoiceArrivedManager, //model manager for showed data
            $arrData, //array(columnName,array(Name,Format))
            $this->id,
            ['cl_users_id' => $this->user->getId(), 'cl_currencies_id' => $tmpParentData->cl_currencies_id
            ], //default data for new record
            $this->DataManager //parent data model manager
        );

        $control->onChange[] = function ()
        {
            $this->UpdateSum();

        };

        $control->onInsertBtn[] = function ($dataItems, $name)
        {
            $this->handleInsertItems($dataItems, $name);
        };
        $control->setEnableSearch('var_symb LIKE ? OR inv_title LIKE ? OR cl_partners_book.company LIKE ? OR CAST(ROUND(price_e2_vat,2) AS CHAR(15)) LIKE ? ');
        $control->setContainerHeight('auto');
        $control->setFilter(['filter' => '(price_payed < price_e2_vat AND cl_payment_types.payment_type = 0 AND cl_payment_order_id IS NULL)']);
        $control->setOrder('due_date DESC');
        return $control;

    }


    public function UpdateSum(){
        $tmpParentData = $this->DataManager->find($this->id);
        //$this->redrawControl('showCommentMain');
    }


    public function afterDelete($line)
    {
        //return parent::afterDelete($line); // TODO: Change the autogenerated stub
        //$tmpParentData = $this->DataManager->find($this->id);

       // $this->PartnersEventManager->update(['id' => $tmpParentData['cl_partners_event_id']]);
        $this->UpdateSum();
        $this->redrawControl('content');
    }

    public function beforeDelete($lineId, $name = NULL){

        $tmpData = $this->PaymentOrderItemsManager->find($lineId);
        if ($tmpData) {
            if (!is_null($tmpData['cl_invoice_id']))
                $tmpData->cl_invoice->update(['cl_payment_order_id' => NULL]);
            if (!is_null($tmpData['cl_invoice_arrived_id']))
                $tmpData->cl_invoice_arrived->update(['cl_payment_order_id' => NULL]);
        }

        return TRUE;
    }


    public function afterDataSaveListGrid($dataId, $name = NULL)
    {
        parent::afterDataSaveListGrid($dataId, $name);
        $tmpData = $this->PaymentOrderItemsManager->find($dataId);
        if ($tmpData) {
            //bdump($tmpData);
            if (!is_null($tmpData['cl_invoice_id'])) {
                $tmpData->cl_invoice->update(['cl_payment_order_id' => $this->id]);
            }
            if (!is_null($tmpData['cl_invoice_arrived_id'])) {
              //  bdump('ted');
                $tmpData->cl_invoice_arrived->update(['cl_payment_order_id' => $this->id]);
            }
        }

    }

    //javascript call when changing cl_partners_book_id
    public function handleRedrawPriceList2($cl_partners_book_id)
    {
/*        $arrUpdate = [];
        $arrUpdate['id'] = $this->id;
        $arrUpdate['cl_partners_book_id'] = ($cl_partners_book_id == '' ? NULL:$cl_partners_book_id ) ;
        $this->DataManager->update($arrUpdate);*/
    }

    public function handleShowPairedDocs()
    {
        //bdump('ted');
        $this->pairedDocsShow = TRUE;
        /*$this->showModal('pairedDocsModal');
        $this->redrawControl('pairedDocs2');
        $this->redrawControl('bsc');*/

        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('pairedDocs2');
        $this->showModal('pairedDocsModal');

    }

    public function handleInvoiceShow()
    {
        $this->paymentModalShow = TRUE;
        $this->showModal('invoiceselectorModal');
        $this->redrawControl('masterSelectorControl');
    }

    public function handleInvoiceArrivedShow()
    {
        $this->paymentModalShow = TRUE;
        $this->showModal('invoiceArrivedselectorModal');
        $this->redrawControl('masterSelectorControl');
    }

    public function handleInsertArrivedItems($dataItems)
    {
        $arrRet = $this->DataManager->insertInvoicesArrived($dataItems, $this->id);
        //bdump($arrRet, 'arrRet');
        if ($arrRet['status'] == "OK") {
            $this->flashMessage($this->translator->translate('Vybrané_faktury_byly_vloženy_do_platebního_příkazu.'), 'success');
        }else{
            $this->flashMessage($this->translator->translate('Faktury_nebyly_vloženy_do_platebního_příkazu. Vloženo faktur: ') . $arrRet['data']['counter'], 'danger');
        }
        $this->redrawControl('content');
    }

    public function handleInsertItems($dataItems, $name)
    {
        if ($name == 'invoiceArrivedSelectorControl'){
            $arrRet = $this->DataManager->insertInvoicesArrived($dataItems, $this->id);
        }elseif ($name == 'invoiceSelectorControl') {
            $arrRet = $this->DataManager->insertInvoices($dataItems, $this->id);
        }
        //bdump($arrRet, 'arrRet');
        if ($arrRet['status'] == "OK") {
            $this->flashMessage($this->translator->translate('Vybrané_faktury_byly_vloženy_do_platebního_příkazu.'), 'success');
        }else{
            $this->flashMessage($this->translator->translate('Faktury_nebyly_vloženy_do_platebního_příkazu. Vloženo faktur: ') . $arrRet['data']['counter'], 'danger');
        }
        $this->redrawControl('content');
    }

    public function handleExportGPC(){

        try {
            $arrRet = $this->DataManager->exportGPC($this->id);
            if (self::hasError($arrRet)) {
                throw new \Exception();
            }
            //$strGPC = $arrRet['success']['data'];
            //$httpResponse = $this->getHttpResponse();
            //$httpResponse->setContentType('text/plain');
            //$httpResponse->sendAsFile('export.gpc');
            //echo($strGPC);
            $tmpNow = new \DateTime();
            $tmpData    = $this->DataManager->find($this->id);
            $fileName   = $tmpData->cl_bank_accounts['account_number'] . '-' .
                            $tmpData->cl_bank_accounts['bank_code'] . ' ' .
                            $tmpNow->format('d-m-Y-H-i') . '.gpc';
            $destFile   = NULL;
            $i          = 0;
            $arrFile    = str_getcsv($fileName, '.');
            $dataFolder = $this->CompaniesManager->getDataFolder($this->getUser()->getIdentity()->cl_company_id);
            $subFolder  = $this->ArraysManager->getSubFolder(['cl_payment_order_id' => TRUE]);
            while (is_null($destFile) || file_exists($destFile)) {
                if (!is_null($destFile)) {
                    $fileName = $arrFile[0] . '-' . $i . '.' . $arrFile[1];
                }
                $destFile = $dataFolder . '/' . $subFolder . '/' . $fileName;
                $i++;
            }
            file_put_contents($destFile, $arrRet['success']['data']);
            $dataF = [];
            $dataF['file_name']             = $fileName;
            $dataF['label_name']            = $fileName;
            $dataF['mime_type']             = 'text/gpc';
            $dataF['file_size']             = filesize($destFile);
            $dataF['create_by']             = $tmpData['create_by'];
            $dataF['created']               = new \Nette\Utils\DateTime;
            $dataF['cl_payment_order_id']   = $tmpData['id'];
            $dataF['cl_company_id']         = $tmpData['cl_company_id'];
            $tmpFile = $this->FilesManager->findAll()->
                            where('cl_payment_order_id = ?', $this->id)->fetch();
            if ($tmpFile){
                $tmpFile->update($dataF);
            }else{
                $this->FilesManager->insert($dataF);
            }

            $this->setStatus($this->id, ['status_use' => 'payment_order',
                                            's_fin'  => 1]);
            //$this->terminate();
            //$this->redirect('this');
            $this->redrawControl('bscAreaEdit');
            $this->redrawControl('exportedFiles');
            $this->redrawControl('formedit');
            $this->showModal('exportedFilesModal');
            //$this->redrawControl('');

        }catch(Exception $e){
            Debugger::log($e->getMessage(), 'GPCExport');

        }
    }

    public function handleDeleteAll()
    {
        foreach($this->PaymentOrderItemsManager->findAll()->where('cl_payment_order_id = ?', $this->id) as $key => $one)
        {
            $this->beforeDelete($key);
            $one->delete();
        }
        $this->flashMessage($this->translator->translate('Obsah_platebního_příkazu_byl_vymazán'), 'success');
        $this->redrawControl('content');
    }

    public function getPartner($arr){
        //bdump($arr);
        $tmpData = $this->PaymentOrderItemsManager->find($arr['id']);
        $tmpPartner = '';
        if ($tmpData){
            bdump($tmpData);
            if (!is_null($tmpData['cl_invoice_id']) && !is_null($tmpData->cl_invoice['cl_partners_book_id']))
                $tmpPartner = $tmpData->cl_invoice->cl_partners_book['company'];
            elseif (!is_null($tmpData['cl_invoice_arrived_id'])  && !is_null($tmpData->cl_invoice_arrived['cl_partners_book_id']))
                $tmpPartner = $tmpData->cl_invoice_arrived->cl_partners_book['company'];

        }
        return $tmpPartner;
    }

}
