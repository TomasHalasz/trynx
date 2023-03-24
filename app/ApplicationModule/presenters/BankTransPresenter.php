<?php

namespace App\ApplicationModule\Presenters;

use App\Controls;
use Nette\Application\UI\Form,
    Nette\Image;
use App\BankCom;
use Pohoda;

class BankTransPresenter extends \App\Presenters\BaseListPresenter {

    public $importType=0;
   
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
     * @var \App\Model\InvoiceAdvanceManager
     */
    public $InvoiceAdvanceManager;


    /**
     * @inject
     * @var \App\Model\InvoicePaymentsManager
     */
    public $InvoicePaymentsManager;

    /**
     * @inject
     * @var \App\Model\InvoiceAdvancePaymentsManager
     */
    public $InvoiceAdvancePaymentsManager;

    /**
     * @inject
     * @var \App\Model\InvoiceArrivedPaymentsManager
     */
    public $InvoiceArrivedPaymentsManager;

    /**
     * @inject
     * @var \App\Model\BankTransItemsManager
     */
    public $BankTransItemsManager;

    /**
     * @inject
     * @var \App\Model\BankTransManager
     */
    public $DataManager;

    /**
     * @inject
     * @var \App\Model\CurrenciesManager
     */
    public $CurrenciesManager;

    /**
     * @inject
     * @var \App\Model\PaymentTypesManager
     */
    public $PaymentTypesManager;

    protected function createComponentTransPairs()
    {
        $tmpParentData = $this->DataManager->find($this->id);
        if ($tmpParentData) {
            $tmpInvId = is_null($tmpParentData->cl_invoice_id) ? 0 : $tmpParentData->cl_invoice_id;
            $tmpInvAdvanceId = is_null($tmpParentData->cl_invoice_advance_id) ? 0 : $tmpParentData->cl_invoice_advance_id;
            $tmpInvArrivedId = is_null($tmpParentData->cl_invoice_arrived_id) ? 0 : $tmpParentData->cl_invoice_arrived_id;
        }else{
            $tmpInvId = 0;
            $tmpInvAdvanceId = 0;
            $tmpInvArrivedId = 0;
        }
        $tmpTransItems = $this->BankTransItemsManager->findAll()->where('cl_bank_trans_id = ?', $this->id);
        $arrInv = [];
        $arrInvAdv = [];
        $arrInvArr = [];

        foreach($tmpTransItems as $key => $one){
            $arrInv[] = (!is_null($one['cl_invoice_id'])) ? $one['cl_invoice_id'] : 0;
            $arrInvAdv[] = (!is_null($one['cl_invoice_advance_id'])) ? $one['cl_invoice_advance_id'] : 0;
            $arrInvArr[] = (!is_null($one['cl_invoice_arrived_id'])) ? $one['cl_invoice_arrived_id'] : 0;
        }
        $arrInvoice = $this->InvoiceManager->findAll()->where('cl_payment_types.payment_type IN (0,2,3) AND (pay_date IS NULL OR (cl_invoice.id != ? AND cl_invoice.id IN ?))', $tmpInvId, $arrInv)->
                                                    select('cl_invoice.id, CONCAT(inv_number," ",cl_partners_book.company," ","vs: ",var_symb," ","uhradit: ",IF(price_e2_vat!=0,price_e2_vat, price_e2)-price_payed, " ", cl_currencies.currency_code) AS inv_number')->
                                                    order('inv_number')->fetchPairs('id','inv_number');
        $arrInvoiceAdvance = $this->InvoiceAdvanceManager->findAll()->where('cl_payment_types.payment_type IN (0,2,3) AND (pay_date IS NULL OR (cl_invoice_advance.id != ? AND cl_invoice_advance.id IN ?))', $tmpInvAdvanceId, $arrInvAdv)->
                                                    select('cl_invoice_advance.id, CONCAT(inv_number," ",cl_partners_book.company," ","vs: ",var_symb," ","uhradit: ",IF(price_e2_vat!=0,price_e2_vat, price_e2)-price_payed, " ", cl_currencies.currency_code) AS inv_number')->
                                                    order('inv_number')->fetchPairs('id','inv_number');
        $arrInvoiceArrived = $this->InvoiceArrivedManager->findAll()->where('cl_payment_types.payment_type IN (0,2,3) AND (pay_date IS NULL OR (cl_invoice_arrived.id != ? AND cl_invoice_arrived.id IN ?))', $tmpInvArrivedId, $arrInvArr)->
                                                    select('cl_invoice_arrived.id, CONCAT(rinv_number," ",cl_partners_book.company," ","vs: ",var_symb," ","uhradit: ",IF(price_e2_vat!=0,price_e2_vat, price_e2)-price_payed, " ", cl_currencies.currency_code) AS inv_number')->
                                                    order('inv_number')->fetchPairs('id','inv_number');

        $arrData = [
                        'cl_invoice.inv_number' => [$this->translator->translate('Faktura'), 'format' => 'chzn-select-req', 'size' => 25, 'values' => $arrInvoice, 'roCondition' => '$defData["cl_invoice_arrived_id"] != NULL || $defData["cl_invoice_advance_id"] != NULL'],
                        'cl_invoice_advance.inv_number' => [$this->translator->translate('Zálohová_faktura'), 'format' => 'chzn-select-req', 'size' => 25, 'values' => $arrInvoiceAdvance, 'roCondition' => '$defData["cl_invoice_arrived_id"] != NULL || $defData["cl_invoice_id"] != NULL'],
                        'cl_invoice_arrived.rinv_number' => [$this->translator->translate('Přijatá_faktura'), 'format' => 'chzn-select-req', 'size' => 25, 'values' => $arrInvoiceArrived, 'roCondition' => '$defData["cl_invoice_id"] != NULL || $defData["cl_invoice_advance_id"] != NULL'],
                        'amount_paired' => [$this->translator->translate('Částka'), 'format' => 'number', 'size' => 10]
        ];

        $control = new Controls\ListgridControl(
            $this->translator,
            $this->BankTransItemsManager,
            $arrData,
            [],
            $this->id,
            [],
            $this->DataManager,
            NULL,
            NULL,
            TRUE,
            [] //custom links
        );

        $control->onChange[] = function () {
            $this->updateSum();
        };

        return $control;

    }


    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.BankTrans']);
        $this->formName = $this->translator->translate("Bankovní_transakce");

        //$this->formName = $this->translator->translate("Bankovní transakce");
        $this->mainTableName = 'cl_bank_trans';
        $this->dataColumns = ['trans_date' => ['format' => 'date', $this->translator->translate('Datum')],
                                    'amount_to_pay' => ['format' => 'currency', $this->translator->translate('Částka')],
                                    'v_symbol' => $this->translator->translate('Var._symbol'),
                                    'description' => $this->translator->translate('Popis_transakce'),
                                    'cl_partners_book.company' => ['format' => 'text', $this->translator->translate('Firma')],
                                    'account_number_foreign' => ['format' => 'text', $this->translator->translate('Protiúčet')],
                                    'amount_paired' => ['format' => 'currency', $this->translator->translate('Celkem_spárováno')],
                                    'amount_left' => ['format' => 'currency', $this->translator->translate('Zbývá_spárovat'),'function' => 'getAmountLeft', 'function_param' => ['amount_paired', 'amount_to_pay']],
                                    'cl_invoice.inv_number' => $this->translator->translate('Faktura'),
                                    'cl_invoice_advance.inv_number' => $this->translator->translate('Zálohová_faktura'),
                                    'cl_invoice_arrived.rinv_number' => $this->translator->translate('Přijatá_faktura'),
                                    'paired_sum' => ['format' => 'text', $this->translator->translate('Další_spárované'),'function' => 'getAnotherPairedSum', 'function_param' => ['id']],
                                    'cl_bank_accounts.account_number' => $this->translator->translate('Číslo_účtu'),
                                    'cl_bank_accounts.bank_code' => $this->translator->translate('Kód_banky'),
                                    'cl_bank_accounts.cl_currencies.currency_code' => [$this->translator->translate('Měna_účtu'), 'format' => 'text'],
                                    's_symbol' => $this->translator->translate('Spec._symbol'),
                                    'k_symbol' => $this->translator->translate('Konst._symbol'),
                                    'created' => [$this->translator->translate('Vytvořeno'),'format' => 'datetime'],
                                    'create_by' => $this->translator->translate('Vytvořil'),
                                    'changed' => [$this->translator->translate('Změněno'),'format' => 'datetime'],
                                    'change_by' => $this->translator->translate('Změnil')];
        $this->FilterC = ' ';
        $this->DefSort = 'trans_date DESC';
        $this->defValues = [];
        $this->readOnly = ['account_number_foreign' => TRUE];
        $this->filterColumns = ['v_symbol' => 'autocomplete' , 'cl_partners_book.company' => 'autocomplete', 'cl_bank_Accounts.account_number' => 'autocomplete', 'account_number_foreign' => 'autocomplete',
                                        'cl_invoice.inv_number' => 'autocomplete', 'cl_invoice_arrived.rinv_number' => 'autocomplete',
                                        'cl_invoice_advance.inv_number' => 'autocomplete', 'amount_to_pay' => 'autocomplete', 'cl_bank_accounts.account_number' => 'autocomplete', 'cl_bank_accounts.bank_code' => 'autocomplete',
            'description' => 'autocomplete'];
        $this->userFilterEnabled = TRUE;
        $this->userFilter = ['v_symbol', 'description', 'amount_to_pay', 'account_number_foreign', 'cl_partners_book.company'];

        $testDate = new \Nette\Utils\DateTime;
        //$testDate = $testDate->modify('-1 day');
        $testDate->setTime(0, 0, 0);

        $this->conditionRows = [['amount_to_pay', '>', 'amount_paired', 'color:red', 'notlastcond'],
                                    ['cl_invoice_id', '==', NULL, 'color:red', 'notlastcond'],
                                    ['cl_invoice_arrived_id', '==', NULL, 'color:red', 'notlastcond'],
                                    ['cl_invoice_advance_id', '==', NULL, 'color:red', 'notlastcond'],
                                    ['amount_to_pay', '==', 'amount_paired', 'color:green', 'lastcond']];


        $this->toolbar = [1 => ['url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nový_záznam'), 'class' => 'btn btn-primary'],
                                'export' => ['group' =>
                                    [0 => ['url' => $this->link('exportTrans!', ['type' => 0]),
                                        'rightsFor' => 'write',
                                        'label' => $this->translator->translate('Export_XML_Pohoda'),
                                        'title' => $this->translator->translate('Export_transakcí_do_formátu_XML_Pohoda'),
                                        'data' => ['data-ajax="true"', 'data-history="false"'],
                                        'class' => 'ajax', 'icon' => 'iconfa-file']
                                    ],
                                    'group_settings' => ['group_label' => $this->translator->translate('Export'),
                                        'group_class' => 'btn btn-primary dropdown-toggle btn-sm',
                                        'group_title' =>  $this->translator->translate('Export'), 'group_icon' => 'iconfa-file']
                                ],
                                3 => ['group' =>
                                    [0 => ['url' => $this->link('importTrans!', ['type' => 0]),
                                                    'rightsFor' => 'write',
                                                    'label' => $this->translator->translate('Import_GPC/ABO'),
                                                    'title' => $this->translator->translate('Import_z_formátu_GPC/ABO'),
                                                    'data' => ['data-ajax="true"', 'data-history="false"'],
                                                    'class' => 'ajax', 'icon' => 'iconfa-file'],
                                        1 => ['url' => $this->link('importTrans!', ['type' => 4]),
                                            'rightsFor' => 'write',
                                            'label' => $this->translator->translate('Import_JSON_CS'),
                                            'title' => $this->translator->translate('Import_z_formátu_JSON_CS'),
                                            'data' => ['data-ajax="true"', 'data-history="false"'],
                                            'class' => 'ajax', 'icon' => 'iconfa-file'],
                                    ],
                                        'group_settings' => ['group_label' => $this->translator->translate('Import'),
                                                                    'group_class' => 'btn btn-primary dropdown-toggle btn-sm',
                                                                    'group_title' =>  $this->translator->translate('tisk'), 'group_icon' => 'iconfa-file']
                                ]
        ];

        /*predefined filters*/
        $this->pdFilter = [0 => ['url' => $this->link('pdFilter!', ['index' => 0, 'pdFilterIndex' => 0]),
                                            'filter' => '(amount_to_pay > 0)',
                                            'sum' => ['amount_to_pay' => ''],
                                            'rightsFor' => 'read',
                                            'label' => $this->translator->translate('příchozí'),
                                            'title' => $this->translator->translate('Všechny_příchozí_platby'),
                                            'data' => ['data-ajax="true"', 'data-history="true"'],
                                            'class' => 'ajax', 'icon' => 'iconfa-filter'],
                                        1 => ['url' => $this->link('pdFilter!', ['index' => 1, 'pdFilterIndex' => 1]),
                                            'filter' => '(amount_to_pay < 0)',
                                            'sum' => ['amount_to_pay' => ''],
                                            'rightsFor' => 'read',
                                            'label' => $this->translator->translate('odchozí'),
                                            'title' => $this->translator->translate('Všechny_odchozí_platby'),
                                            'data' => ['data-ajax="true"', 'data-history="true"'],
                                            'class' => 'ajax', 'icon' => 'iconfa-filter'],
                                        2 => ['url' => $this->link('pdFilter!', ['index' => 2, 'pdFilterIndex' => 2]),
                                            'filter' => '(amount_to_pay > 0 AND amount_to_pay > amount_paired)',
                                            'sum' => ['amount_to_pay' => ''],
                                            'rightsFor' => 'read',
                                            'label' => $this->translator->translate('nespárované_příchozí'),
                                            'title' => $this->translator->translate('Nespárované_příchozí_platby'),
                                            'data' => ['data-ajax="true"', 'data-history="true"'],
                                            'class' => 'ajax', 'icon' => 'iconfa-filter'],
                                        3 => ['url' => $this->link('pdFilter!', ['index' => 3]),
                                            'filter' => '(amount_to_pay < 0 AND ABS(amount_to_pay) > amount_paired)',
                                            'sum' => ['amount_to_pay' => ''],
                                            'rightsFor' => 'read',
                                            'label' => $this->translator->translate('nespárované_odchozí'),
                                            'title' => $this->translator->translate('Nespárované_odchozí_platby'),
                                            'data' => ['data-ajax="true"', 'data-history="true"'],
                                            'class' => 'ajax', 'icon' => 'iconfa-filter']
        ];


        $this->bscEnabled = $this->getUser()->getIdentity()->bsc_enabled;

    }	
    
    public function renderDefault($page_b = 1,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal, $cxs)  {
	    parent::renderDefault($page_b,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal,$cxs);
	    $this->template->importTypeName = $this->ArraysManager->getImportTypeName($this->importType);
	    $this->template->importType = $this->importType;
        $this->template->exportTypeName = $this->ArraysManager->getExportTypeName($this->exportType);
        $this->template->exportType = $this->exportType;
    }
    
    public function renderEdit($id,$copy,$modal){
	    parent::renderEdit($id,$copy,$modal);

    }
    
    
    protected function createComponentEdit($name)
    {	
		$form = new Form($this, $name);
	    //$form->setMethod('POST');
	    $form->addHidden('id',NULL);
        $form->addText('trans_date', $this->translator->translate('Datum_transakce'), 30, 30)
            ->setHtmlAttribute('placeholder',$this->translator->translate('Datum_transakce'));
		$form->addText('account_number_foreign', $this->translator->translate('Číslo_protiúčtu'), 30, 30)
			->setHtmlAttribute('placeholder',$this->translator->translate('Číslo_protiúčtu'));
		$form->addText('amount_to_pay', $this->translator->translate('Částka'), 10, 10)
			->setHtmlAttribute('placeholder',$this->translator->translate('Částka'));
		$form->addText('v_symbol', $this->translator->translate('Var._symbol'), 15, 15)
			->setHtmlAttribute('placeholder',$this->translator->translate('variabilní_symb.'));
        $form->addText('k_symbol', $this->translator->translate('Konst._symbol'), 10, 10)
            ->setHtmlAttribute('placeholder',$this->translator->translate('konst._s.'));
        $form->addText('s_symbol', $this->translator->translate('Spec._symbol'), 10, 10)
            ->setHtmlAttribute('placeholder',$this->translator->translate('spec._s.'));
		$form->addText('description', $this->translator->translate('Popis'), 60, 250)
			->setHtmlAttribute('placeholder',$this->translator->translate('Popis_transakce'));
        $form->addTextArea('description_txt', $this->translator->translate('Poznámka'), 50,5);

        $arrPartners = $this->PartnersManager->findAll()->order('company')->fetchPairs('id','company');
        $form->addSelect('cl_partners_book_id', $this->translator->translate("Firma"),$arrPartners)
                        ->setHtmlAttribute('data-urlajax', $this->link('getPartners!'))
                        ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte'))
                        ->setHtmlAttribute('class','form-control chzn-select input-sm')
                        ->setPrompt($this->translator->translate('Zvolte_firmu'));


        $arrAccounts = $this->BankAccountsManager->findAll()->order('account_number, bank_code')->fetchPairs('id','account_number');
        $form->addSelect('cl_bank_accounts_id', $this->translator->translate("Účet"),$arrAccounts)
                        ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte'))
                        ->setHtmlAttribute('class','form-control chzn-select input-sm')
                        ->setPrompt($this->translator->translate('Zvolte_účet'));

        $tmpData = $this->DataManager->find($this->id);
        if ($tmpData) {
            $tmpInvId = is_null($tmpData->cl_invoice_id) ? 0 : $tmpData->cl_invoice_id;
            $tmpInvAdvanceId = is_null($tmpData->cl_invoice_advance_id) ? 0 : $tmpData->cl_invoice_advance_id;
            $tmpInvArrivedId = is_null($tmpData->cl_invoice_arrived_id) ? 0 : $tmpData->cl_invoice_arrived_id;
        }else{
            $tmpInvId = 0;
            $tmpInvAdvanceId = 0;
            $tmpInvArrivedId = 0;
        }

        $arrInvoice = $this->InvoiceManager->findAll()->where('cl_payment_types.payment_type IN (0,2,3) AND (pay_date IS NULL OR cl_invoice.id = ?)', $tmpInvId)->
                                                select('cl_invoice.id, CONCAT(inv_number," ",cl_partners_book.company," ","vs: ",var_symb," ","uhradit: ",IF(price_e2_vat!=0,price_e2_vat, price_e2)-price_payed, " ", cl_currencies.currency_code) AS inv_number')->
                                                order('inv_number')->fetchPairs('id','inv_number');
        $form->addSelect('cl_invoice_id', $this->translator->translate("Faktura_vydaná"), $arrInvoice)
                        ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte'))
                        ->setHtmlAttribute('class','form-control chzn-select input-sm')
                        ->setPrompt($this->translator->translate('Zvolte_fakturu'));

        $arrInvoiceAdvance = $this->InvoiceAdvanceManager->findAll()->where('cl_payment_types.payment_type IN (0,2,3) AND (pay_date IS NULL OR cl_invoice_advance.id = ?)', $tmpInvAdvanceId)->
                                                select('cl_invoice_advance.id, CONCAT(inv_number," ",cl_partners_book.company," ","vs: ",var_symb," ","uhradit: ",IF(price_e2_vat!=0,price_e2_vat, price_e2)-price_payed, " ", cl_currencies.currency_code) AS inv_number')->
                                                order('inv_number')->fetchPairs('id','inv_number');
        $form->addSelect('cl_invoice_advance_id', $this->translator->translate("Faktura_zálohová"), $arrInvoiceAdvance)
            ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte'))
            ->setHtmlAttribute('class','form-control chzn-select input-sm')
            ->setPrompt($this->translator->translate('Zvolte_fakturu'));

        $arrInvoiceArrived = $this->InvoiceArrivedManager->findAll()->where('cl_payment_types.payment_type IN (0,2,3) AND (pay_date IS NULL OR cl_invoice_arrived.id = ?)', $tmpInvArrivedId)->
                                                select('cl_invoice_arrived.id, CONCAT(rinv_number," ",cl_partners_book.company," ","vs: ",var_symb," ","uhradit: ",IF(price_e2_vat!=0,price_e2_vat, price_e2)-price_payed, " ", cl_currencies.currency_code) AS inv_number')->
                                                order('inv_number')->fetchPairs('id','inv_number');
        $form->addSelect('cl_invoice_arrived_id', $this->translator->translate("Faktura_přijatá"), $arrInvoiceArrived)
            ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte'))
            ->setHtmlAttribute('class','form-control chzn-select input-sm')
            ->setPrompt($this->translator->translate('Zvolte_fakturu'));

        $form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class','btn btn-success');
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
		    ->setHtmlAttribute('class','btn btn-warning')
		    ->setValidationScope([])
		    ->onClick[] = array($this, 'stepBack');	    	    
		$form->onSuccess[] = array($this, 'SubmitEditSubmitted');
        $form->onValidate[] = array($this, 'FormValidate');
        return $form;
    }

    public function FormValidate(Form $form)
    {
        $data = $form->values;
        $data = $this->updatePartnerId($data);
        //$this->redrawControl('content');
       // if ($data['cl_partners_book_id'] == NULL || $data['cl_partners_book_id'] == 0)
       // {
            //$form->addError($this->translator->translate('Partner musí být vybrán'));
       // }
        $this->redrawControl('content');
    }

    public function stepBack()
    {	    
	    $this->redirect('default');
    }		

    public function SubmitEditSubmitted(Form $form)
    {
        $data=$form->values;
        $data = $this->removeFormat($data);

        if ($form['send']->isSubmittedBy())
        {
            //dump($data->id);
            //die;
            if (!empty($data->id)) {
                $tmpOldData = $this->DataManager->find($data->id);
                $this->DataManager->update($data, TRUE);
                $line = $this->DataManager->find($data['id']);
                if ( ($line['cl_invoice_id'] != $tmpOldData['cl_invoice_id'] || $line['cl_invoice_advance_id'] != $tmpOldData['cl_invoice_advance_id'] || $line['cl_invoice_arrived_id'] != $tmpOldData['cl_invoice_arrived_id']) &&
                    (!is_null($tmpOldData['cl_invoice_id']) || !is_null($tmpOldData['cl_invoice_advance_id']) || !is_null($tmpOldData['cl_invoice_arrived_id']))){

                    if ($line['cl_invoice_id'] != $tmpOldData['cl_invoice_id'] && !is_null($tmpOldData['cl_invoice_id']) ){
                        $tmpOldData2 = $tmpOldData->toArray();
                        $tmpOldData2['cl_invoice_advance_id'] = NULL;
                        $tmpOldData2['cl_invoice_arrived_id'] = NULL;
                        $this->afterDelete($tmpOldData2);
                    }
                    if ($line['cl_invoice_arrived_id'] != $tmpOldData['cl_invoice_arrived_id'] && !is_null($tmpOldData['cl_invoice_arrived_id']) ){
                        $tmpOldData2 = $tmpOldData->toArray();
                        $tmpOldData2['cl_invoice_advance_id'] = NULL;
                        $tmpOldData2['cl_invoice_id'] = NULL;
                        $this->afterDelete($tmpOldData2);
                    }
                    if ($line['cl_invoice_advance_id'] != $tmpOldData['cl_invoice_advance_id'] && !is_null($tmpOldData['cl_invoice_advance_id']) ){
                        $tmpOldData2 = $tmpOldData->toArray();
                        $tmpOldData2['cl_invoice_id'] = NULL;
                        $tmpOldData2['cl_invoice_arrived_id'] = NULL;
                        $this->afterDelete($tmpOldData2);
                    }
                    $this->updatePair($data['id'], NULL, $data['cl_invoice_id'], $data['cl_invoice_advance_id'], $data['cl_invoice_arrived_id']);

                }else{
                   // bdump($tmpOldData);
                   // bdump($data);
                    $this->updatePair($data['id'], NULL, $data['cl_invoice_id'], $data['cl_invoice_advance_id'], $data['cl_invoice_arrived_id']);
                }

            }else {
                $id = $this->DataManager->insert($data);
                $this->updatePair($id, NULL, $data['cl_invoice_id'], $data['cl_invoice_advance_id'], $data['cl_invoice_arrived_id']);
            }
        }

        $this->DataManager->updateSum($data);
        $this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
      // die;
        $this->redirect('this');
    }	    

    public function handleImportTrans($type){
        $this->importType = $type;
        $this->showModal('importTrans');
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('createDocs');
    }

    public function handleExportTrans($type){
        $this->exportType = $type;
        $this->showModal('exportTrans');
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('createDocs');
    }

    protected function createComponentImportTransForm()
    {
        $form = new Form;
        $form->addHidden('importType')
            ->setDefaultValue($this->importType);
        $arrAccounts = $this->BankAccountsManager->findAll()->select('CONCAT(account_number, "/", bank_code, " - ", bank_name) AS account_number, id AS id')->order('account_number, bank_code')->fetchPairs('id','account_number');

        if ($this->importType == 4){
            $form->addSelect('cl_bank_accounts_id', $this->translator->translate("Účet"),$arrAccounts)
                ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte'))
                ->setHtmlAttribute('class','form-control chzn-select input-sm');
            $form['cl_bank_accounts_id']->setRequired('Účet_musí_být_zvolen');
        }else{

        }

        $form->addUpload('upload_file', $this->translator->translate('Importní_soubor'))
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setRequired('Vyberte_prosím_soubor_s_transakcemi')
            ->addRule(Form::MAX_FILE_SIZE, $this->translator->translate('Maximální_velikost_souboru_je_512_kB'), 1024 * 1024 /* v bytech */);
        $form->addSubmit('submit', 'Importovat')
            ->setHtmlAttribute('class', 'form-control btn-sm btn-primary');
        $form->onSuccess[] = array($this, "ImportTransFormSubmited");
        return $form;
    }

    /**
     * ImportTrans form submitted
     * @param \Nette\Application\UI\Form
     * @return void
     */
    public function importTransFormSubmited($form)
    {
        $values = $form->getValues();
        //dump($values);
        //die;
        try {
            $file = $form->getHttpData($form::DATA_FILE, 'upload_file');
            if ($file && $file->isOk()) {
                //$xml = $file->getContents();
                if ($values['importType'] == 0)
                    $this->importGPC($file);
                elseif ($values['importType'] == 3)
                    $this->importOFX($file);
                elseif ($values['importType'] == 4)
                    $this->importJSONCS($file, $values['cl_bank_accounts_id']);

                //$this->importXML($xml, $values->id);
                //bdump($xml);
                $this->DataManager->pairTrans();
            }
            //$this->redrawControl('content');
            $this->hideModal('importTrans');
            //$this->redrawControl('baselistArea');
            //$this->redrawControl('baselist');
            //$this->redrawControl('paginator_top');
        } catch (\Nette\Security\AuthenticationException $e) {
            $form->addError($e->getMessage());
        }
    }

    public function afterDeleteBaseList($line)
    {
        //return parent::afterDeleteBaseList($line);
        if (!is_null($line->cl_invoice_id))
        {
            $this->InvoiceManager->paymentUpdate($line->cl_invoice_id);
        }
        if (!is_null($line->cl_invoice_advance_id))
        {
            $this->InvoiceAdvanceManager->paymentUpdate($line->cl_invoice_advance_id);
        }
        if (!is_null($line->cl_invoice_arrived_id))
        {
            $this->InvoiceArrivedManager->paymentUpdate($line->cl_invoice_arrived_id);
        }
    }


    private function importGPC($file){
        $json_result = BankCom\GPC::import($file);
        $arrResult = json_decode($json_result, TRUE);
        //dump($arrResult);
        //die;
        foreach($arrResult['success'] as $key => $one){
            if ($one[1] == '074') {
                //header
            }elseif ($one[1] == '075'){
                //record
                $arrTrans = array();
                //account_number
                $tmpStr = (string)$one[2];
                //bdump($tmpStr);
                $ownAccount_pre =  substr($tmpStr,10,6);
                $ownAccount_main = substr($tmpStr,4,5).
                                    substr($tmpStr,3,1).
                                    substr($tmpStr,9,1).
                                    substr($tmpStr,1,1).
                                    substr($tmpStr,2,1).
                                    substr($tmpStr,0,1);

                $ownAccount_main 	= ltrim($ownAccount_main,"0"); //odstranit nuly na zacatku
                $ownAccount		    = $ownAccount_pre . $ownAccount_main; //slozit predcisli a cislo uctu
                $ownAccount = ltrim($ownAccount, '0');
                $acType = 1;  //normal format of bank account
                $ownAccount2 = ltrim($tmpStr, '0');
                //bdump($ownAccount);
                //bdump($ownAccount2);
                $tmpAccount = $this->BankAccountsManager->findAll()->where('REPLACE(account_number,"-","") = ? OR REPLACE(account_number,"-","") = ?', $ownAccount, $ownAccount2)->fetch();
                if ($tmpAccount){
                    if (str_replace("-","",$tmpAccount->account_number == $ownAccount)){
                        $acType = 2; //internal format of bank account
                    }
                    $arrTrans['cl_bank_accounts_id'] = $tmpAccount->id;
                }else{
                    $arrTrans['cl_bank_accounts_id'] = NULL;
                }

                //foreginAccount
                $tmpStr = (string)$one[3];
                if ($acType == 2) {
                    $tmpStr_pre  =  substr($tmpStr, 10, 6);
                    $tmpStr_main =  substr($tmpStr, 4, 5) .
                                    substr($tmpStr, 3, 1) .
                                    substr($tmpStr, 9, 1) .
                                    substr($tmpStr, 1, 1) .
                                    substr($tmpStr, 2, 1) .
                                    substr($tmpStr, 0, 1);
                    $tmpStr_main 	= ltrim($tmpStr_main,"0"); //odstranit nuly na zacatku
                    $tmpStr		    = $tmpStr_pre . $tmpStr_main; //slozit predcisli a cislo uctu
                }
                $frnAccount = ltrim($tmpStr, '0');
                $frnBank = (string)$one[9];
                $arrTrans['account_number_foreign'] = $frnAccount . '/' . $frnBank;
                $tmpCompany = $this->PartnersManager->findAll()->where('REPLACE(account_code,"-","") = ? AND bank_code = ?', $frnAccount, $frnBank)->fetch();
                if ($tmpCompany){
                    $arrTrans['cl_partners_book_id'] = $tmpCompany->id;
                }else{
                    $arrTrans['cl_partners_book_id'] = NULL;
                }
                $arrTrans['trans_id'] = (string)$one[4];
                $tmpStr = (string)$one[15];
                $tmpStr = '20' . substr($tmpStr,4,2) . '-' . substr($tmpStr,2,2) . '-' . substr($tmpStr,0,2);
                $arrTrans['trans_date'] = $tmpStr;

                $arrTrans['description'] = (string)$one[13];

                $arrTrans['v_symbol'] = ltrim((string)$one[7], '0');
                $arrTrans['k_symbol'] = ltrim((string)$one[10], '0');
                $arrTrans['s_symbol'] = ltrim((string)$one[12], '0');

                $typTrans = (int)$one[6];
                $value = $one[5]/100;

                $arrTrans['amount_to_pay'] = ($typTrans == 1 || $typTrans == 4 ) ? 0 - $value : $value;  //outgoing 1,4  income 2,3

                //bdump($arrTrans);
                //bdump($ownAccount);
                //die;
                //$tmpImport = $this->DataManager->findAll()->where('trans_id = ? AND (REPLACE(cl_bank_accounts.account_number,"-","") = ? OR REPLACE(cl_bank_accounts.account_number,"-","") = ?)', $arrTrans['trans_id'], $ownAccount, $ownAccount2)->fetch();
                $tmpImport = $this->DataManager->findAll()
                                ->where('trans_id = ? AND v_symbol = ? AND amount_to_pay = ? AND account_number_foreign = ? AND trans_date = ? AND (REPLACE(cl_bank_accounts.account_number,"-","") = ? OR REPLACE(cl_bank_accounts.account_number,"-","") = ?)',
                                    $arrTrans['trans_id'], $arrTrans['v_symbol'], $arrTrans['amount_to_pay'], $arrTrans['account_number_foreign'], $arrTrans['trans_date'], $ownAccount, $ownAccount2)
                                ->fetch();
                if (!$tmpImport) {
                    $this->DataManager->insert($arrTrans);
                }else{
                    $this->flashMessage($this->translator->translate("Duplicitní_transakce_nebyla_importována") . " vs: " . $arrTrans['v_symbol'] ." ". $this->translator->translate('Částka') . " " . $arrTrans['amount_to_pay'], "danger");
                }
            }
        }
    }

    public function updateSum()
    {

    }

    //aditional processing data after save in listgrid
    //23.11.2018 - there must be giveout from store and receiving backitems
    public function afterDataSaveListGrid($dataId, $name = NULL)
    {
        parent::afterDataSaveListGrid($dataId, $name);
        if ($name == "transPairs") {
            //make
            $tmpTransItem = $this->BankTransItemsManager->find($dataId);
            if ($tmpTransItem) {
                $this->updatePair($tmpTransItem['cl_bank_trans_id'], $dataId, $tmpTransItem['cl_invoice_id'], $tmpTransItem['cl_invoice_advance_id'], $tmpTransItem['cl_invoice_arrived_id']);
                $this->DataManager->updateSum($tmpTransItem->cl_bank_trans);
            }

        }
    }

    public function updatePair($id, $cl_trans_item_id = NULL, $cl_invoice_id = NULL, $cl_invoice_advance_id = NULL, $cl_invoice_arrived_id = NULL){
        $tmpPayments = $this->PaymentTypesManager->findAll()->where('payment_type IN (0)')->limit(1)->fetch();
        if (!$tmpPayments){
            $paymentId = $tmpPayments->id;
        }else{
            $paymentId = NULL;
        }
        //22.08.2020 - at first main transaction
        if (is_null($cl_trans_item_id)){
            $trans = $this->DataManager->find($id);
            //dump($trans);
            $this->DataManager->pairCheck($trans, $paymentId, $cl_invoice_id, $cl_invoice_advance_id, $cl_invoice_arrived_id);
            $this->DataManager->updateSum($trans);
        }
        //at second another paired invoices
        $one   = $this->BankTransItemsManager->find($cl_trans_item_id); //$cl_trans_item_id
        //bdump($one);
        if ($one) {
            $this->DataManager->pairCheck($one, $paymentId, $cl_invoice_id, $cl_invoice_advance_id , $cl_invoice_arrived_id);
            $this->DataManager->updateSum($one);
        }
    }


    public function updatePair_old($id, $cl_trans_item_id = NULL, $cl_invoice_id = NULL, $cl_invoice_advance_id = NULL, $cl_invoice_arrived_id = NULL){

        $arrPayment     = array();
        $tmpData        = $this->DataManager->find($id);
        $tmpTransItem   = $this->BankTransItemsManager->find($cl_trans_item_id);
        if ($tmpTransItem){
            $cl_invoice_id              = $tmpTransItem->cl_invoice_id;
            $cl_invoice_arrived_id      = $tmpTransItem->cl_invoice_arrived_id;
            $cl_invoice_advance_id      = $tmpTransItem->cl_invoice_advance_id;
        }
        $maxCount = null;
        if (!is_null($cl_invoice_id)) {
            $tmpInvoice                     = $this->InvoiceManager->find($cl_invoice_id);
            $tmpInvoicePayment              = $this->InvoicePaymentsManager->findAll()->where('cl_invoice_id = ? AND cl_bank_trans_id = ?', $tmpInvoice->id, $id)->limit(1)->fetch();
            $maxCount                       = $this->InvoicePaymentsManager->findAll()->where('cl_invoice_id = ?', $tmpInvoice->id)->max('item_order');
            $arrPayment['cl_invoice_id']    = $cl_invoice_id;
        }
        if (!is_null($cl_invoice_advance_id)) {
            $tmpInvoice                             = $this->InvoiceAdvanceManager->find($cl_invoice_advance_id);
            $tmpInvoicePayment                      = $this->InvoiceAdvancePaymentsManager->findAll()->where('cl_invoice_advance_id = ? AND cl_bank_trans_id = ?', $tmpInvoice->id, $id)->limit(1)->fetch();
            $maxCount                               = $this->InvoiceAdvancePaymentsManager->findAll()->where('cl_invoice_advance_id = ?', $tmpInvoice->id)->max('item_order');
            $arrPayment['cl_invoice_advance_id']    = $tmpInvoice->id;
        }
        if (!is_null($cl_invoice_arrived_id)) {
            $tmpInvoice                             = $this->InvoiceArrivedManager->find($cl_invoice_arrived_id);
            $tmpInvoicePayment                      = $this->InvoiceArrivedPaymentsManager->findAll()->where('cl_invoice_arrived_id = ? AND cl_bank_trans_id = ?', $tmpInvoice->id, $id)->limit(1)->fetch();
            $maxCount                               = $this->InvoiceArrivedPaymentsManager->findAll()->where('cl_invoice_arrived_id = ?', $tmpInvoice->id)->max('item_order');
            $arrPayment['cl_invoice_arrived_id']    = $tmpInvoice->id;
        }

        //$cl_bank_trans_id = $tmpInvoice->cl_bank_trans_id;  // NULL = we have to create new record to cl_invoice_payment, cl_invoice_advance_payment, cl_invoice_arrived_payment

        $tmpPayments = $this->PaymentTypesManager->findAll()->where('payment_type IN (0)')->limit(1)->fetch();
        if (!$tmpPayments){
            $paymentId = $tmpPayments->id;
        }else{
            $paymentId = NULL;
        }

        if (!is_null($maxCount)) {
            $arrPayment['item_order'] = $maxCount + 1;
        }

        if (!is_null($tmpData->cl_bank_accounts_id)) {
            $arrPayment['cl_currencies_id'] = $tmpData->cl_bank_accounts->cl_currencies_id;
        }
        $arrPayment['cl_payment_types_id']  = $paymentId;
        $arrPayment['cl_bank_trans_id']     = $tmpData->id;
        $arrPayment['pay_date']             = $tmpData->trans_date;
        $arrPayment['pay_price']            = abs(($tmpTransItem) ? $tmpTransItem->amount_paired : $tmpData->amount_to_pay);
        $arrPayment['pay_doc']              = $this->translator->translate('import_z_banky');
        $arrPayment['pay_type']             = 0;
        $arrPayment['change_by']            = '';
        $arrPayment['changed']              = NULL;

        if (!is_null($cl_invoice_id) && (!$tmpInvoicePayment)) {
            //new payment to invoice
            $this->InvoicePaymentsManager->insert($arrPayment);
            $this->InvoiceManager->paymentUpdate($cl_invoice_id);
        }elseif (!is_null($cl_invoice_id) && ($tmpInvoicePayment)) {
            //update paymento to invoice
            $tmpInvoicePayment->update($arrPayment);
            $this->InvoiceManager->paymentUpdate($cl_invoice_id);

        }elseif (!is_null($cl_invoice_advance_id) && (!$tmpInvoicePayment)) {
            //new payment to advance
            $this->InvoiceAdvancePaymentsManager->insert($arrPayment);
            $this->InvoiceAdvanceManager->paymentUpdate($cl_invoice_advance_id);
        }elseif (!is_null($cl_invoice_advance_id) && ($tmpInvoicePayment)) {
            //update payment to advance
            $tmpInvoicePayment->update($arrPayment);
            $this->InvoiceAdvanceManager->paymentUpdate($cl_invoice_advance_id);

        }elseif (!is_null($cl_invoice_arrived_id) && (!$tmpInvoicePayment)) {
            //new payment to arrived
            $this->InvoiceArrivedPaymentsManager->insert($arrPayment);
            $this->InvoiceArrivedManager->paymentUpdate($cl_invoice_arrived_id);
        }elseif (!is_null($cl_invoice_arrived_id) && ($tmpInvoicePayment)) {
            //update payment to arrived
            $tmpInvoicePayment->update($arrPayment);
            $this->InvoiceArrivedManager->paymentUpdate($cl_invoice_arrived_id);
        }

        return true;
    }



    public function afterDelete($line)
    {
     //   return parent::afterDelete($line); // TODO: Change the autogenerated stub
        if (isset($line['cl_bank_trans_id'])){
            $bankTransId = $line['cl_bank_trans_id'];
            //$cl_bank_trans = $line->cl_bank_trans;
            $cl_bank_trans = ['id' => $line['cl_bank_trans_id']];
        }else{
            $bankTransId = $line['id'];
            $cl_bank_trans = $line;
        }
        if (!is_null($line['cl_invoice_id'])) {
            $toDelete = $this->InvoicePaymentsManager->findAll()->where('cl_bank_trans_id = ? AND cl_invoice_id = ?', $bankTransId, $line['cl_invoice_id']);
            //bdump($toDelete);
            $toDelete->delete();
            $this->InvoiceManager->paymentUpdate($line['cl_invoice_id']);
        }
        if (!is_null($line['cl_invoice_advance_id'])) {
            $this->InvoiceAdvancePaymentsManager->findAll()->where('cl_bank_trans_id = ? AND cl_invoice_advance_id = ?', $bankTransId, $line['cl_invoice_advance_id'])->delete();
            $this->InvoiceAdvanceManager->paymentUpdate($line['cl_invoice_advance_id']);
        }
        if (!is_null($line['cl_invoice_arrived_id'])) {
            $this->InvoiceArrivedPaymentsManager->findAll()->where('cl_bank_trans_id = ? AND cl_invoice_arrived_id = ?', $bankTransId, $line['cl_invoice_arrived_id'])->delete();
            $this->InvoiceArrivedManager->paymentUpdate($line['cl_invoice_arrived_id']);
        }
        $this->DataManager->updateSum($cl_bank_trans);
    }

    public function getAmountLeft($arr)
    {
        return abs($arr['amount_to_pay']) - abs($arr['amount_paired']);
    }

    public function beforeAddLine($data)
    {
     //   return parent::beforeAddLine($data); // TODO: Change the autogenerated stub
        $tmpData = $this->DataManager->find($this->id);
        if ($tmpData) {
            $data['amount_paired'] = abs($tmpData['amount_to_pay']) - $tmpData['amount_paired'];
        }
        return $data;
    }

    public function DataProcessListGridValidate($data){
        //
        $error = NULL;
        $dataOld = $this->BankTransItemsManager->find($data['id']);
        $tmpData = $this->DataManager->find($this->id);
        if ($tmpData && $dataOld){
            //bdump($data);
            //
            bdump($tmpData['amount_paired']);
            bdump($dataOld['amount_paired']);
            $amountPaired = str_replace(" ","",$data['amount_paired']);
            bdump($amountPaired);
            bdump($tmpData['amount_paired'] - $dataOld['amount_paired'] + $amountPaired);
            if (($tmpData['amount_paired'] - $dataOld['amount_paired'] + $amountPaired) > abs($tmpData['amount_to_pay'])){
                $error = $this->translator->translate("Částka_je_příliš_vysoká_K_dispozici_je") . (abs($tmpData['amount_to_pay']) - ($tmpData['amount_paired'] - $dataOld['amount_paired']));
            }
        }
        return $error;
    }

    public function getAnotherPairedSum($arr)
    {
        return $this->BankTransItemsManager->findAll()->where('cl_bank_trans_id = ?', $arr['id'])->count();
    }

    private function importOFX($file)
    {

        $ofxParser = new \OfxParser\Parser();
        $ofx = $ofxParser->loadFromFile($file);
        $bankAccount = reset($ofx->bankAccounts);
        //dump(simplexml_load_string($bankAccount->agencyNumber));
        // Get the statement start and end dates
        $startDate = $bankAccount->statement->startDate;
        $endDate = $bankAccount->statement->endDate;
        // Get the statement transactions for the account
        $transactions = $bankAccount->statement->transactions;

        foreach($transactions as $key => $one) {
            $bankAccount1 = $bankAccount->accountNumber;
            $bankAccount2 = ltrim($bankAccount->accountNumber, '0');
            $tmpAccount = $this->BankAccountsManager->findAll()->where('REPLACE(account_number,"-","") = ? OR REPLACE(account_number,"-","") = ?', $bankAccount1, $bankAccount2)->fetch();
            if ($tmpAccount) {
                if (str_replace("-", "", $tmpAccount->account_number == $bankAccount)) {
                    $acType = 2; //internal format of bank account
                }
                $arrTrans['cl_bank_accounts_id'] = $tmpAccount->id;
            } else {
                $arrTrans['cl_bank_accounts_id'] = NULL;
            }
            //foreginAccount
            dump($one);
            $frnAccount = $one->bankAccount;
            $frnBank = $one->bankCode;
            $arrTrans['account_number_foreign'] = $frnAccount . '/' . $frnBank;
            $tmpCompany = $this->PartnersManager->findAll()->where('REPLACE(account_code,"-","") = ? AND bank_code = ?', $frnAccount, $frnBank)->fetch();
            if ($tmpCompany) {
                $arrTrans['cl_partners_book_id'] = $tmpCompany->id;
            } else {
                $arrTrans['cl_partners_book_id'] = NULL;
            }
            $arrTrans['trans_id'] = $one->uniqueId;

            $arrTrans['trans_date'] = $one->date;

            $arrTrans['description'] = $one->name;
            $arrTrans['description_txt'] = $one->memo;

            $arrTrans['v_symbol'] = $one->sic;
            $arrTrans['k_symbol'] = ltrim((string)$one[10], '0');
            $arrTrans['s_symbol'] = ltrim((string)$one[12], '0');

            $typTrans = (int)$one[6];
            $value = $one[5] / 100;

            $arrTrans['amount_to_pay'] = ($typTrans == 1 || $typTrans == 4) ? 0 - $value : $value;  //outgoing 1,4  income 2,3

            //bdump($arrTrans);
            //bdump($ownAccount);
            $tmpImport = $this->DataManager->findAll()->where('trans_id = ? AND (REPLACE(cl_bank_accounts.account_number,"-","") = ? OR REPLACE(cl_bank_accounts.account_number,"-","") = ?)', $arrTrans['trans_id'], $ownAccount, $ownAccount2)->fetch();
            if (!$tmpImport) {
                $this->DataManager->insert($arrTrans);
            } else {
                $this->flashMessage($this->translator->translate("Duplicitní_transakce_nebyla_importována") . " vs: " . $arrTrans['v_symbol'] . " " . $this->translator->translate('Částka') . " " . $arrTrans['amount_to_pay'], "danger");
            }
        }



        die;
    }

    private function importJSONCS($file, $cl_bank_accounts_id)
    {
        $strJson = file_get_contents($file);
        $arrData = json_decode($strJson, TRUE);
        foreach($arrData as $key => $one) {

            $tmpAccount = $this->BankAccountsManager->findAll()->where('id = ?', $cl_bank_accounts_id)->fetch();
            if ($tmpAccount) {
                $arrTrans['cl_bank_accounts_id'] = $tmpAccount->id;
            } else {
                $arrTrans['cl_bank_accounts_id'] = NULL;
                $this->flashMessage('Nebyl vybrán vlastní účet pro import', 'danger');
                return;
            }
            //foreingAccount
            $frnAccount = $one['partnerAccount']['number'];
            $frnBank =  $one['partnerAccount']['bankCode'];
            $arrTrans['account_number_foreign'] = $frnAccount . '/' . $frnBank;

            $tmpCompany = $this->PartnersManager->findAll()->where('company LIKE ?', (string)$one['partnerName'])->fetch();
            if ($tmpCompany) {
                $arrTrans['cl_partners_book_id'] = $tmpCompany->id;
                $arrTrans['description'] = (string)$one['note'];
            }else{
                $tmpNewComp = $this->PartnersManager->insert(array('company' => (string)$one['partnerName']));
                $arrTrans['cl_partners_book_id'] = $tmpNewComp->id;
                $arrTrans['description'] = (string)$one['note'];
                //$arrTrans['description']     = (string)$one['partnerName'];
                //$arrTrans['description_txt'] = (string)$one['note'];
            }
            //$tmpCompany = $this->PartnersManager->findAll()->where('REPLACE(account_code,"-","") = ? AND bank_code = ?', $frnAccount, $frnBank)->fetch();
            //if ($tmpCompany) {
            //    $arrTrans['cl_partners_book_id'] = $tmpCompany->id;
            //} else {
            //    $arrTrans['cl_partners_book_id'] = NULL;
            //}
            $arrTrans['trans_id'] = (string)$one['referenceNumber'];
            $arrTrans['trans_date'] = date($one['booking']);
            $arrTrans['v_symbol'] = (string)$one['variableSymbol'];
            $arrTrans['k_symbol'] = (string)$one['constantSymbol'];
            $arrTrans['s_symbol'] = (string)$one['specificSymbol'];

            $value = substr($one['amount']['value'], 0, strlen($one['amount']['value']) - $one['amount']['precision']);
            $arrTrans['amount_to_pay'] = (float)$value;

            //bdump($arrTrans);
            //bdump($ownAccount);
            if (!empty($arrTrans['trans_id'])){
                $tmpImport = $this->DataManager->findAll()->where('trans_id = ? AND cl_bank_accounts_id = ?)', $arrTrans['trans_id'], $tmpAccount->id)->fetch();
            }else{
                $tmpImport = FALSE;
            }

            if (!$tmpImport) {
                $this->DataManager->insert($arrTrans);
            } else {
                $this->flashMessage($this->translator->translate("Duplicitní_transakce_nebyla_importována") . " vs: " . $arrTrans['v_symbol'] . " " . $this->translator->translate('Částka') . " " . $arrTrans['amount_to_pay'], "danger");
            }
        }

    }

    protected function createComponentExportBankTrans($name)
    {
        $form = new Form($this, $name);

        $form->addHidden('id',NULL);

        $now = new \Nette\Utils\DateTime;
        $lcText1 = $this->translator->translate('Datum_od');
        $lcText2 = $this->translator->translate('Datum_do');

        $form->addText('date_from', $lcText1, 0, 16)
            ->setDefaultValue('01.'.$now->format('m.Y'))
            ->setHtmlAttribute('placeholder','Datum_začátek');

        $form->addText('date_to', $lcText2, 0, 16)
            ->setDefaultValue($now->format('d.m.Y'))
            ->setHtmlAttribute('placeholder',$this->translator->translate('Datum_konec'));

        $arrAccounts = $this->BankAccountsManager->findAll()->select('CONCAT(account_number, "/", bank_code, " - ", bank_name) AS account_number, id AS id')->order('account_number, bank_code')->fetchPairs('id','account_number');
        $form->addSelect('cl_bank_accounts_id', $this->translator->translate("Účet"),$arrAccounts)
            ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte'))
            ->setHtmlAttribute('class','form-control chzn-select input-sm');
        $form['cl_bank_accounts_id']->setRequired('Účet_musí_být_zvolen');

        $form->addSubmit('save_xml', $this->translator->translate('Exportovat_transakce'))->setHtmlAttribute('class','btn btn-sm btn-primary');

        $form->addSubmit('back', $this->translator->translate('Návrat'))
            ->setHtmlAttribute('class','btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBackExportBankTrans');
        $form->onSuccess[] = array($this, 'SubmitExportBankTransSubmitted');

        return $form;
    }

    public function stepBackExportBankTrans()
    {
        $this->rptIndex = 1;
        $this->reportModalShow = FALSE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    public function SubmitExportBankTransSubmitted(Form $form)
    {
        $data=$form->values;

        if ($form['save_xml']->isSubmittedBy() )
        {

            if ($data['date_to'] == "") {
                $data['date_to'] = NULL;
            }else{
                $data['date_to'] = date('Y-m-d H:i:s',strtotime($data['date_to']) + 86400 - 10);
            }

            if ($data['date_from'] == "") {
                $data['date_from'] = NULL;
            }else {
                $data['date_from'] = date('Y-m-d H:i:s', strtotime($data['date_from']));
            }

            $dataReport = $this->DataManager->findAll()->
                                            where('cl_bank_accounts_id = ? AND CHAR_LENGTH(account_number_foreign) > 5 ', $data['cl_bank_accounts_id'])->
                                            where('cl_bank_trans.trans_date >= ? AND cl_bank_trans.trans_date <= ?', $data['date_from'], $data['date_to']);

            if ($form['save_xml']->isSubmittedBy())
            {
                $this->exportPohoda($dataReport);
            }
        }
    }


    private function exportPohoda($dataReport){
        $customer = array();
        // zadejte ICO
        $pohoda = new Pohoda\Export($this->settings->ico);
        try {
            foreach($dataReport as $key => $one) {

                $bankTrans = new Pohoda\BankTransaction($one->id);
                if ($one['amount_to_pay'] < 0)
                    $bankTrans->setType(Pohoda\BankTransaction::EXPENSE_TYPE);
                else
                    $bankTrans->setType(Pohoda\BankTransaction::RECEIPT_TYPE);

                $bankTrans->setText($one['description']);
                //nulova sazba dph
                $bankTrans->setAmountPay($one['amount_to_pay']);
                $tmpAccount = str_getcsv($one['account_number_foreign'],"/");
                //dump($tmpAccount);
                //dump(count($tmpAccount));
                //die;




                if (count($tmpAccount) > 1) {
                    $bankTrans->setPaymentAccountNumber($tmpAccount[0]);
                    $bankTrans->setPaymentBankCode($tmpAccount[1]);
                }

                // variabil, spec, konst
                $bankTrans->setSymVar((int)$one['v_symbol']);
                $bankTrans->setSymConst((int)$one['k_symbol']);
                $bankTrans->setSymSpec((int)$one['s_symbol']);

                // datum platby
                $bankTrans->setDatePay($one['trans_date']);
                $bankTrans->setDateStatement($one['trans_date']);

                // nastaveni identity dodavatele
                $bankTrans->setProviderIdentity([
                    "company" => $one->cl_company['name'],
                    "city" => $one->cl_company['city'],
                    "street" => $one->cl_company['street'],
                    "number" => "",
                    "zip" => (int)str_replace(" ", "", $one->cl_company['zip']),
                    "ico" => $one->cl_company['ico'],
                    "dic" => $one->cl_company['dic']
                ]);

                // nastaveni identity prijemce
                if (!is_null($one['cl_partners_book_id'])) {
                    $tmpCustomer = [
                        "company"   => $one->cl_partners_book['company'],
                        "city"      => $one->cl_partners_book['city'],
                        "street"    => $one->cl_partners_book['street'],
                        "number"    => "",
                        "zip"       => (int)str_replace(" ", "", $one->cl_partners_book['zip']),
                        "ico"       => (int)str_replace(" ", "", $one->cl_partners_book['ico']),
                        "dic"       => $one->cl_partners_book['dic']
                    ];
                } else {
                    $tmpCustomer = array();
                }

                if (!is_null($one['cl_partners_book_id'])){
                    $customer[$tmpCustomer['company']] = $bankTrans->createCustomerAddress($tmpCustomer);
                }else{
                    $bankTrans->createCustomerAddress($tmpCustomer);
                }

                if ($bankTrans->isValid()) {
                    // pokud je faktura validni, pridame ji do exportu
                    //dump($bankTrans);
                    //die;
                    $pohoda->addBankTrans($bankTrans);
                } else {
                    var_dump($bankTrans->getErrors());
                }
            }
            //příprava adres pro import, řešíme až zde aby nebyly duplicity v adresáři
            foreach ($customer as $key => $oneCustomer){
                $pohoda->addAddress($oneCustomer);
            }


            // ulozeni do souboru
            $errorsNo = 0; // pokud si pocitate chyby, projevi se to v nazvu souboru

            $dataFolder = $this->CompaniesManager->getDataFolder($this->settings->id);
            $subFolder  = $this->ArraysManager->getSubFolder(array(), 'cl_bank_trans_id');
            $destFile   =  $dataFolder . '/' . $subFolder;  // . '/' . 'invoice_export.xml';
            $pohoda->setExportFolder($destFile); //mozno nastavit slozku, do ktere bude proveden export

            $file = $pohoda->exportToFile(time(), 'Trynx', 'bank_trans', $errorsNo, '', 'bank_trans');
            // vypsani na obrazovku jako XML s hlavickou
            //Debugger::$showBar = false;s
            //$pohoda->exportAsXml(time(), 'popis', date("Y-m-d_H-i-s"));

            $httpResponse = $this->getHttpResponse();
            $httpResponse->setHeader('Pragma', "public");
            $httpResponse->setHeader('Expires', 0);
            $httpResponse->setHeader('Cache-Control', "must-revalidate, post-check=0, pre-check=0");
            $httpResponse->setHeader('Content-Transfer-Encoding', "binary");
            $httpResponse->setHeader('Content-Description', "File Transfer");
            $httpResponse->setHeader('Content-Length', filesize($file));
            //$this->sendResponse(new DownloadResponse($file, basename($file) , array('application/octet-stream', 'application/force-download', 'application/download')));
            $this->sendResponse(new \Nette\Application\Responses\FileResponse($file, basename($file), 'application/download'));


        } catch (Pohoda\InvoiceException $e) {
            $this->flashMessage($e->getMessage(), 'error');
        } catch (\InvalidArgumentException $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }


}
