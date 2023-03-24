<?php

namespace App\IntranetModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;
use App\Controls;

class ComplaintPresenter extends \App\Presenters\BaseListPresenter {

    
    PUBLIC $createDocShow = FALSE;
    
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

    public 	$filterStaffUsed = [];
    
    /**
    * @inject
    * @var \App\Model\InComplaintManager
    */
    public $DataManager;

    /**
     * @inject
     * @var \App\Model\InComplaintUsersManager
     */
    public $inComplaintUsersManager;



    /**
     * @inject
     * @var \App\Model\InComplaintItemsManager
     */
    public $inComplaintItemsManager;

    /**
     * @inject
     * @var \App\Model\PriceListManager
     */
    public $priceListManager;



    /**
     * @inject
     * @var \App\Model\FilesManager
     */
    public $FilesManager;

    /**
     * @inject
     * @var \App\Model\UserManager
     */
    public $UserManager;

    /**
     * @inject
     * @var \App\Model\UsersManager
     */
    public $usersManager;

    /**
     * @inject
     * @var \App\Model\CenterManager
     */
    public $centerManager;

    /**
     * @inject
     * @var \App\Model\ArraysManager
     */
    public $ArraysManager;

    /**
     * @inject
     * @var \App\Model\ArraysIntranetManager
     */
    public $ArraysIntranetManager;

    /**
     * @inject
     * @var \App\Model\CompaniesManager
     */
    public $CompaniesManager;

    /**
     * @inject
     * @var \App\Model\InvoiceTypesManager
     */
    public $invoiceTypesManager;

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



    protected function createComponentEmail()
    {
        //$translator = clone $this->translator->setPrefix([]);
        return new Controls\EmailControl($this->translator,
            $this->EmailingManager, $this->mainTableName, $this->id);
    }

    protected function createComponentFiles()
    {
        $user_id = $this->user->getId();
        $cl_company_id = $this->settings->id;
        return new Controls\FilesControl(
            $this->translator,$this->FilesManager,$this->UserManager,$this->id,'in_complaint_id', NULL,$cl_company_id,$user_id,
            $this->CompaniesManager, $this->ArraysManager);
    }

    public function createComponentChat()
    {
        $user_id = $this->user->getId();
        $cl_company_id = $this->settings->id;
        $arrEmlSendTo = [];
        $tmpObservers = $this->inComplaintUsersManager->findAll()->where('in_complaint_id = ?', $this->id);
        foreach($tmpObservers as $key => $one){
            if (!is_null($one['cl_users_id'])) {
                $tmpEmail = $this->UserManager->getEmail($one['cl_users_id']);
                if ($tmpEmail != '')
                    $arrEmlSendTo[] = $one->cl_users['name'] . ' <' . $tmpEmail . '>';
            }
        }
        return new Controls\ChatControl($this->translator,$this->ChatManager, $this->DataManager, $this->ArraysManager, $this->UserManager, $this->EmailingManager,
                                        $this->id, $cl_company_id, $user_id, $arrEmlSendTo);
    }



    protected function startup()
    {
        parent::startup();
        $this->formName = 'Reklamace';
        $this->mainTableName = 'in_complaint';
        $this->dataColumns = ['co_number' => ['Číslo reklamace', 'size' => 20, 'format' => 'text'],
                            'cl_invoice_types.name' => ['Typ reklamace', 'size' => 20],
                            'cl_status.status_name' => ['Stav', 'size' => 20],
                            'cl_partners_book.company' => ['Dodavatel', 'size' => 30],
                            'inv_number' => ['Faktura', 'size' => 20],
                            'dn_number' => ['Dodací list', 'size' => 20],
                            'dtm_income' => ['Datum přijetí', 'size' => 10, 'format' => 'date'],
                            'dtm_finding' => ['Datum zjištění', 'size' => 10, 'format' => 'date'],
                            'cl_center.name' => ['Středisko', 'size' => 10],
                            'items_count' => ['Počet položek', 'size' => 10, 'function' => 'getItemsCount', 'function_param' => ['id']],
                            'descr_detail' => ['Položky', 'size' => 15],
                            'cl_users.name' => ['Přijal', 'size' => 10],
                            'chat_count' => ['Komentáře', 'size' => 10],
                            'description' => ['Popis', 'size' => 10],
                            'cl_currencies.currency_code' => ['Měna', 'size' => 10, 'format' => 'text'],
                            'cl_partners_book_workers.worker_name' => ['Kontakt', 'size' => 10],
                            'created' => ['Vytvořeno','format' => 'datetime'],
                            'create_by' => 'Vytvořil',
                            'changed' => ['Změněno','format' => 'datetime'],
                            'change_by' => 'Změnil'];

        $this->filterColumns = ['co_number' => 'autocomplete', 'cl_invoice_types.name' => 'autocomplete', 'cl_status.status_name' => 'autocomplete',
                                    'cl_partners_book.company' => 'autocomplete', 'inv_number' => 'autocomplete', 'dn_number' => 'autocomplete'];
        $this->userFilterEnabled = TRUE;
        $this->userFilter = ['co_number', 'cl_partners_book.company', 'inv_number', 'dn_number', 'cl_status.status_name'];

        $this->cxsEnabled = TRUE;
        $this->userCxsFilter = [':in_complaint_users.final_email', ':in_complaint_items.item_label', ':in_complaint_items.description'];

        $this->readOnly = ['co_number' => TRUE,
            'created' => TRUE,
            'create_by' => TRUE,
            'changed' => TRUE,
            'change_by' => TRUE];

        $this->defValues = ['dtm_income' => new \Nette\Utils\DateTime,
            'dtm_finding' => new \Nette\Utils\DateTime,
            'cl_currencies_id' => $this->settings->cl_currencies_id,
            'cl_users_id' => $this->user->getId()];

       // $this->filterColumns = array();
        $this->DefSort = 'co_number DESC';
        $this->numberSeries = ['use' => 'complaint', 'table_key' => 'cl_number_series_id', 'table_number' => 'co_number'];
        //$this->readOnly = array('identification' => TRUE);
        //$settings = $this->CompaniesManager->getTable()->fetch();
        //$this->defValues = [];

        //settings for documents saving and emailing
        //$this->docTemplate = $this->ReportManager->getReport(__DIR__ . '/../templates/Complaint/complaintv1.latte');
        //$this->docAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;
        //$this->docTitle = ["", "cl_partners_book.company", "co_number"];

        $arrTemplates = [0 => ['latte' => __DIR__ . '/../templates/Complaint/complaintv2.latte', 'name' => 'Reklamační list'],
                         1 => ['latte' => __DIR__ . '/../templates/Complaint/complaintv1.latte', 'name' => 'Reklamační list interní']
        ];

        $this->docTemplate = $this->ReportManager->getReport2($arrTemplates);

        bdump($this->docTemplate);
        $this->docAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;
        $this->docTitle = [0 => ["cl_partners_book.company", "co_number"],
                           1 => ["cl_partners_book.company", "co_number"]
        ];


        //22.07.2018 - settings for sending doc by email
        $this->docEmail = ['template' => __DIR__ . '/../templates/Complaint/emailComplaint.latte',
            'emailing_text' => 'complaint'];

        $this->toolbar = [1 => ['url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => 'Nový záznam', 'class' => 'btn btn-primary']];

        $this->quickFilter = ['cl_invoice_types.name' => ['name' => 'Zvolte filtr zobrazení',
                                    'values' =>  $this->invoiceTypesManager->findAll()->where('inv_type IN ?', [7])->fetchPairs('id','name')]
        ];

        $this->bscOff = FALSE;
        $this->bscEnabled = FALSE;
        $this->bscPages = ['card' => ['active' => false, 'name' => 'Karta reklamace', 'lattefile' => $this->getLattePath() . 'Complaint\card.latte'],
                           'notab' => ['active' => true, 'name' => 'Položky', 'lattefile' => $this->getLattePath() . 'Complaint\items.latte'],
                           'observers' => ['active' => true, 'name' => 'Pozorovatelé', 'lattefile' => $this->getLattePath() . 'Complaint\observers.latte'],
                           'files' => ['active' => false, 'name' => 'Soubory', 'lattefile' => $this->getLattePath() . 'Complaint\files.latte']

        ];

        $this->bscToolbar = [
            1 => ['url' => 'selectReport!', 'rightsFor' => 'enable', 'label' => $this->translator->translate('Vyberte_tiskovou_sestavu'), 'class' => 'select', 'selectdata' => $this->docTemplate,
                'data' => ['data-ajax="true"', 'data-history="false"']],
            2 => ['url' => 'savePDF!', 'rightsFor' => 'enable', 'label' => $this->translator->translate('Náhled'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="true"', 'data-history="false"'],'icon' => 'glyphicon glyphicon-print'],
            3 => ['url' => 'downloadPDF!', 'rightsFor' => 'enable', 'label' => $this->translator->translate('PDF'), 'class' => 'btn btn-success',
                'data' => ['data-ajax="false"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-save'],
            4 => ['url' => 'sendDoc!', 'rightsFor' => 'write', 'label' => 'E-mail', 'class' => 'btn btn-success', 'icon' => 'glyphicon glyphicon-send'],

        ];
        //3 => array('url' => 'sendDoc!', 'rightsFor' => 'write', 'label' => $this->translator->translate('E-mail'), 'class' => 'btn btn-success', 'icon' => 'glyphicon glyphicon-send')
        $this->bscTitle = ['inv_number' => $this->translator->translate('Číslo_faktury'), 'cl_partners_book.company' => $this->translator->translate('Dodavatel')];

        //settings for documents saving and emailing


        $testDate = new \Nette\Utils\DateTime;
        //$testDate = $testDate->modify('-1 day');
        $testDate->setTime(0, 0, 0);
        $this->conditionRows = [['cl_status.s_fin', '==', 1, 'background-color:#C7C7C7', 'lastcond'], //gray - finished
            ['cl_status.s_fin', '==', 0, 'background-color:#7ECB20', 'notlastcond'], //orange - not finished with chat comments
            ['chat_count', '==', 0, 'background-color:#B2FF59', 'lastcond'], //red - not finished without chat comments
            ['chat_count', '>', 0, 'background-color:#7ECB20', 'lastcond']]; //orange - not finished with chat comments


        $this->chatEnabled = true;

    }	
    
    public function renderDefault($page_b = 1,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal, $cxs)  {
	        parent::renderDefault($page_b,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal,$cxs);

    }
    
    public function renderEdit($id,$copy,$modal){
	        parent::renderEdit($id,$copy,$modal);


    }
    
    
    protected function createComponentEdit($name)
    {	
        $form = new Form($this, $name);
	    $form->addHidden('id',NULL);
        $form->addText('dtm_income', 'Datum přijetí', 10, 10)
			->setHtmlAttribute('placeholder','Datum přijetí')
            ->setRequired('Datum musí být vyplněn');

        $form->addText('dtm_finding', 'Datum zjištění', 10, 10)
            ->setHtmlAttribute('placeholder','Datum zjištění')
            ->setRequired('Datum musí být vyplněn');

        $form->addText('co_number', 'Číslo reklamace', 10, 10)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', 'Číslo reklamace');

        $form->addText('inv_number', 'Číslo faktury', 10, 10)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', 'Číslo faktury');

        $form->addText('dn_number', 'Číslo dodacího listu', 10, 10)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', 'Číslo dodacího listu');

        $form->addTextArea('description', 'Poznámka', 40, 4)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', 'Poznámka');

        $arrCenter = $this->centerManager->findAll()->order('name')->fetchPairs('id','name');
        $form->addSelect('cl_center_id', 'Středisko', $arrCenter)
            ->setTranslator(NULL)
            ->setHtmlAttribute('data-placeholder','Zvolte středisko')
            ->setPrompt('Zvolte středisko');

        $arrStatus = $this->StatusManager->findAll()->where('status_use = ?','complaint')->order('status_name')->fetchPairs('id','status_name');
        $form->addSelect('cl_status_id', "Stav",$arrStatus)
            ->setHtmlAttribute('data-placeholder','Zvolte stav reklamace')
            ->setRequired('Vyberte prosím stav reklamace')
            ->setPrompt('Zvolte stav reklamace');

        if ($tmpComplaint = $this->DataManager->find($this->id)) {
            if (isset($tmpComplaint['cl_partners_book_id'])) {
                $tmpPartnersBookId = $tmpComplaint->cl_partners_book_id;
            } else {
                $tmpPartnersBookId = 0;
            }
        } else {
            $tmpPartnersBookId = 0;
        }

        $arrPartners = $this->PartnersManager->findAll()->where('id = ?', $tmpPartnersBookId)->fetchPairs('id','company');
        $form->addSelect('cl_partners_book_id', 'Dodavatel', $arrPartners)
            ->setTranslator(NULL)
            ->setHtmlAttribute('data-placeholder','Zvolte dodavatele')
            ->setHtmlAttribute('data-urlajax', $this->link('getPartners!'))
            ->setHtmlAttribute('data-url-update-partner-in-form', $this->link('updatePartnerInForm!'))
            ->setPrompt($this->translator->translate('Zvolte dodavatele'));

        $arrUsers[$this->translator->translate('Aktivní')] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 0')->fetchPairs('id','name');
        $arrUsers[$this->translator->translate('Neaktivní')] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 1')->fetchPairs('id','name');

        $form->addSelect('cl_users_id', 'Přijal',$arrUsers)
            ->setTranslator(NULL)
            ->setHtmlAttribute('data-placeholder','Zvolte zaměstnance')
            ->setPrompt('Zvolte zaměstnance');


        $arrWorkers = $this->PartnersBookWorkersManager->getWorkersGrouped($tmpPartnersBookId);
        $form->addSelect('cl_partners_book_workers_id', 'Kontakt', $arrWorkers)
            ->setTranslator(NULL)
            ->setHtmlAttribute('data-placeholder','Zvolte kontaktní osobu')
            ->setPrompt('Zvolte kontaktní osobu');


        $arrBranch = $this->PartnersBranchManager->findAll()->where('cl_partners_book_id = ?',$tmpPartnersBookId)->fetchPairs('id','b_name');
        $form->addSelect('cl_partners_branch_id', 'Pobočka',$arrBranch)
            ->setTranslator(NULL)
            ->setPrompt('Zvolte pobočku')
            ->setHtmlAttribute('data-placeholder','Pobočka');

        $arrInvoiceTypes = $this->invoiceTypesManager->findAll()->where('inv_type IN ?',array(7))->fetchPairs('id','name');
        $form->addSelect('cl_invoice_types_id', 'Typ reklamace',$arrInvoiceTypes)
            ->setTranslator(NULL)
            ->setPrompt('Zvolte typ reklamace')
            ->setHtmlAttribute('data-placeholder','typ reklamace');

        $arrCurrencies = $this->CurrenciesManager->findAll()->order('currency_code')->fetchPairs('id', 'currency_code');
        $form->addSelect('cl_currencies_id', $this->translator->translate("Měna"), $arrCurrencies)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte'))
            ->setHtmlAttribute('class', 'form-control chzn-select input-sm')
            ->setPrompt($this->translator->translate('Zvolte měnu'));

        $form->onValidate[] = array($this, 'FormValidate');
        $form->addSubmit('send', 'Uložit')->setHtmlAttribute('class','btn btn-success');
	    $form->addSubmit('back', 'Zpět')
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

    public function FormValidate(Form $form)
    {
        $data = $form->values;
        $data = $this->updatePartnerId($data);
        //$this->redrawControl('content');
        if ($data['cl_partners_book_id'] == NULL || $data['cl_partners_book_id'] == 0)
        {
            $form->addError($this->translator->translate('Partner_musí_být_vybrán'));
        }
        $this->redrawControl('content');
    }

    public function SubmitEditSubmitted(Form $form)
    {
        $data=$form->values;
        $tmpOldData = $this->DataManager->find($this->id);
        if ($form['send']->isSubmittedBy())
        {

            $data = $this->removeFormat($data);

            if (!empty($data->id))
                $this->DataManager->update($data, TRUE);
            else
                $this->DataManager->insert($data);
        }
        $tmpNewData = $this->DataManager->find($this->id);
        //$tmpChange = $this->isChange($tmpOldData, $tmpNewData);
        $tmpChange = $this->HistoryManager->getChanges($this->DataManager->getTableName());
        //12.05.2022 - add observers according to cl_invoice_types.users_list
        $this->inComplaintUsersManager->removeUsers($this->id);
        $this->inComplaintUsersManager->addUsers($this->id);
        if ($tmpChange)
        {
            $arrObservers = [];
            $tmpObservers = $this->inComplaintUsersManager->findAll()->where('in_complaint_id = ?', $this->id);
            foreach($tmpObservers as $key => $one){
                if (!is_null($one['cl_users_id'])) {
                    $tmpEmail = $this->UserManager->getEmail($one['cl_users_id']);
                    if ($tmpEmail != '')
                        $arrObservers[] = $one->cl_users['name'] . ' <' . $tmpEmail . '>';
                }
            }
            $retArr = $this->emlChangeNotify($tmpChange, $tmpNewData, $arrObservers,  '[' . $tmpNewData['co_number'] . ']');
            if (self::hasError($retArr)) {
                $this->flashMessage($this->translator->translate('Notifikace_o_změnách_nebyla_odeslána'), 'danger');
            }else {
                $this->flashMessage($this->translator->translate('Notifikace_o_změnách_odeslána'), 'success');
                $this->HistoryManager->changesSend($tmpChange);
            }
        }

        $this->flashMessage($this->translator->translate('Změny_byly_uloženy.'), 'success');
        $this->redrawControl('content');
    }


    protected function createComponentObservers()
    {
        $arrUsers[$this->translator->translate('Aktivní')] = $this->UserManager->
                                                                                getUsersInCompany($this->user->getIdentity()->cl_company_id)->
                                                                                where('not_active = 0')->order('name')->fetchPairs('id','name');
        $arrUsers[$this->translator->translate('Neaktivní')] = $this->UserManager->
                                                                                getUsersInCompany($this->user->getIdentity()->cl_company_id)->
                                                                                where('not_active = 1')->order('name')->fetchPairs('id','name');

        $arrData = [
            'cl_users.name' => ['Uživatel','format' => 'chzn-select-req', 'size' => 20, 'values' => $arrUsers,  'required' => 'Uživatel musí být vybrán'],
            'final_email' => ['Email','format' => 'text', 'size' => 20, 'readonly' => false]
        ];
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->inComplaintUsersManager, //data manager
            $arrData, //data columns
            [], //row conditions
            $this->id, //parent Id
            [], //default data
            $this->DataManager, //parent data manager
            NULL, //pricelist manager
            NULL, //pricelist partner manager
            TRUE, //enable add empty row
            [], //custom links
            FALSE, //movableRow
            'cl_users.name', //orderColumn
            FALSE, //selectMode
            [], //quickSearch
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE, //pricelistbottom
            FALSE, //readonly
            FALSE, //nodelete
            FALSE, //enablesearch
            '' //txtSEarchcondition
        );
        $control->setNewLineName('Pozorovatel');
        $control->setNewLineTitle('Přidá_pozorovatele');
        return $control;
    }



    public function createComponentItemslistgrid()
    {
        $tmpParentData = $this->DataManager->find($this->id);
        //$this->translator->setPrefix(['applicationModule.Complaint']);

        //29.12.2017 - adaption of names
        $userTmp = $this->UserManager->getUserById($this->getUser()->id);
        $userCompany1 = $this->CompaniesManager->getTable()->where('cl_company.id', $userTmp->cl_company_id)->fetch();
        $userTmpAdapt = json_decode($userCompany1->own_names, true);
        if (!isset($userTmpAdapt['cl_invoice_items__description1'])) {
            $userTmpAdapt['cl_invoice_items__description1'] = "Poznámka 1";

        }
        if (!isset($userTmpAdapt['cl_invoice_items__description2'])) {
            $userTmpAdapt['cl_invoice_items__description2'] = "Poznámka 2";
        }
//'cl_pricelist.identification' => array('Kód', 'format' => 'text', 'size' => 10, 'readonly' => TRUE),
            $arrData = [
                'item_label' => ['Položka', 'format' => 'text-number', 'size' => 20, 'roCondition' => '$this["editLine"]["cl_pricelist_id"]->value != 0',
                                'rules' => [0 => ['rule' => [form::LENGTH, 'musí zapsáno %d míst', [7,7] ]]]],
                'cl_pricelist_id' => ['Položka_ceníku', 'format' => 'hidden'],
                'quantity' => ['Počet', 'format' => 'number', 'size' => 8, 'decplaces' => $this->settings->des_mj],
                'units' => ['', 'format' => 'text', 'size' => 4,'e100p' => "false"],
                'quantity_control' => ['Ke kontrole', 'format' => 'number', 'size' => 6, 'decplaces' => $this->settings->des_mj],
                'max_wrong' => ['Max. chyba', 'format' => 'number', 'size' => 6, 'decplaces' => $this->settings->des_mj],
                'quantity_checked' => ['Vadných', 'format' => 'number', 'size' => 6, 'decplaces' => $this->settings->des_mj],
                'quantity_wrong' => ['Rozdíl +/-', 'format' => 'number', 'size' => 6, 'decplaces' => $this->settings->des_mj],
                'description' => ['Poznámka', 'format' => "textarea", 'size' => 70,  'rows' => 5, 'newline' => TRUE,'e100p' => "false"],
            ];

        $control = new Controls\ListgridControl(
            $this->translator,
            $this->inComplaintItemsManager,
            $arrData,
            [],
            $this->id,
            [], //defaultvalues
            $this->DataManager,
            NULL,
            FALSE,
            TRUE,
            [], //custom links
            FALSE, //movable row
            NULL, //ordercolumn
            FALSE, //selectmode
            [], //quicksearch
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE //pricelistbottom
        );
        $control->setContainerHeight("auto");
        $control->setEnableSearch('item_label LIKE ? OR description LIKE ?');
        $control->setNewLineName('Artikl');
        $control->setNewLineTitle('Přidá_artikl');
        $control->onChange[] = function () {
            $this->updateSumItems();
        };
        return $control;

    }


    public function ListGridInsert($sourceData, $dataManager)
    {
        $row = false;
        if ($dataManager->tableName == 'cl_commission_items') {
            $arrPrice = new \Nette\Utils\ArrayHash;
            $arrPrice['id'] = $sourceData['id'];
            $sourcePriceData = $this->PriceListManager->find($sourceData->id);

            $arrData = new \Nette\Utils\ArrayHash;
            $arrData[$this->DataManager->tableName . '_id'] = $this->id;
            $arrData['cl_pricelist_id'] = $sourcePriceData->id;
            $arrData['item_order'] = $dataManager->findAll()->where($this->DataManager->tableName . '_id = ?', $arrData[$this->DataManager->tableName . '_id'])->max('item_order') + 1;
            $arrData['item_label'] = $sourcePriceData->item_label;
            $arrData['quantity'] = 1;
            $arrData['units'] = $sourcePriceData->unit;
            $row = $dataManager->insert($arrData);
        }
        return ($row);
    }

    //$retData = $this->parent->DataProcessListGridValidate($data);
    public function DataProcessListGridValidate($data){
        $retVal = '';
        $tmpData = $this->usersManager->find($data['cl_users_id']);
        if ($tmpData) {
            if (isset($tmpData['email']) && $tmpData['email'] != '' && !$this->checkEmailEnabled($tmpData['email']))
                $retVal = $this->translator->translate('Email_není_pro_pozorovatele_povolen');
            if (isset($tmpData['email2']) && $tmpData['email2'] != '' && !$this->checkEmailEnabled($tmpData['email2']))
                $retVal = $this->translator->translate('Email2_není_pro_pozorovatele_povolen');
            if ($data['final_email'] != '' && !$this->checkEmailEnabled($data['final_email']))
                $retVal = $this->translator->translate('Zadaný_email_není_pro_pozorovatele_povolen');
        }
        return ($retVal);
    }

    //aditional processing data after save in listgrid
    public function afterDataSaveListGrid($dataId, $name = NULL)
    {
       // bdump($name);
        if ($name == 'observers'){
            $this->inComplaintUsersManager->makeFinalEmail($dataId);
        }
        if ($name == 'itemslistgrid'){
            //$item = $this->inComplaintItemsManager->find($dataId);


        }

    }
    public function updateSumItems()
    {
        $items = $this->inComplaintItemsManager->findAll()->where('in_complaint_id = ?', $this->id)->select('id, item_label')->fetchPairs('id','item_label');
        $strItems = implode(', ', $items);
        bdump($strItems);
        $this->DataManager->update(['id' => $this->id, 'descr_detail' => $strItems]);
    }


    private function checkEmailEnabled($email){
        return $this->inComplaintUsersManager->checkEmailEnabled($email);
    }


    public function getItemsCount($function_param){
        $tmpCount = $this->inComplaintItemsManager->findAll()->where('in_complaint_id = ?', $function_param['id'])->count();
        return $tmpCount;
    }

    public function handleSendDoc($id, $latteIndex = NULL, $arrData = [], $recepients = [], $emailingTextIndex = NULL)
    {
        $arrObservers = [];
        $tmpObservers = $this->inComplaintUsersManager->findAll()->select('cl_users.name, cl_users.email, cl_users.email2')->
                                where('in_complaint_id = ?', $this->id);
        foreach($tmpObservers as $key => $one){
            $tmpEmail = $this->validateEmail($one['email']);

            if (empty($tmpEmail))
                $tmpEmail = $this->validateEmail($one['email2']);

            if (!empty($tmpEmail))
                $arrObservers[$one['name']] = $tmpEmail;
        }

        //dump($tmpObservers);
        //die;
        parent::handleSendDoc($id, NULL, [], $arrObservers );
    }


    public function handleSavePDF($id, $latteIndex = NULL, $arrData = array(), $noDownload = FALSE, $noPreview = FALSE)
    {
        $tmpData = $this->preparePDFData($id);

        return parent::handleSavePDF($id, $tmpData['latteIndex'], $tmpData, $noDownload, $noPreview);
    }

    public function handleDownloadPDF($id, $latteIndex = NULL, $arrData = array(), $noDownload = FALSE, $noPreview = FALSE)
    {
        $tmpData = $this->preparePDFData($id);

        return parent::handleSavePDF($id, $tmpData['latteIndex'], $tmpData, $noDownload, TRUE);
    }

    public function preparePDFData($id)
    {
        $data = $this->DataManager->find($id);
/*        if ($data->cl_invoice_types->inv_type == 5 || $data->cl_invoice_types->inv_type == 6) //příjmový/výdajový doklad
        {
            $latteIndex = 1;
        } else //jiný doklad
        {
            $latteIndex = 2;
        }*/
        $latteIndex = 1;

        $arrData = ['settings' => $this->settings,
                    'latteIndex' => $latteIndex
            ];

        return $arrData;
    }


}
