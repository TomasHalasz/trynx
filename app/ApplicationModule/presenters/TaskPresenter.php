<?php

namespace App\ApplicationModule\Presenters;

use App\Controls;
use Nette\Application\UI\Form,
    Nette\Image;

class TaskPresenter extends \App\Presenters\BaseListPresenter {

    

    
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
    * @var \App\Model\TaskManager
    */
    public $DataManager;

    /**
    * @inject
    * @var \App\Model\ArraysManager
    */
    public $ArraysManager;

    /**
     * @inject
     * @var \App\Model\PartnersEventMethodManager
     */
    public $PartnersEventMethodManager;



    /**
     * @inject
     * @var \App\Model\PartnersEventManager
     */
    public $PartnersEventManager;

    /**
     * @inject
     * @var \App\Model\CommissionTaskManager
     */
    public $CommissionTaskManager;

    /**
     * @inject
     * @var \App\Model\ProjectManager
     */
    public $ProjectManager;

    /**
     * @inject
     * @var \App\Model\TaskCategoryManager
     */
    public $TaskCategoryManager;

    /**
     * @inject
     * @var \App\Model\TaskWorkersManager
     */
    public $TaskWorkersManager;

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
    $this->formName = $this->translator->translate("Úkoly");
    $this->mainTableName = 'cl_task';

	$this->dataColumns = [
        'task_number' => [$this->translator->translate('Číslo_úkolu'), 'size' => 10, 'format' => 'text'],
        'task_date' => [$this->translator->translate('Datum_zápisu'), 'size' => 10, 'format' => 'date'],
        'cl_partners_book.company' => [$this->translator->translate('Klient'), 'size' => 50, 'format' => 'text', 'show_clink' => true],
        'cl_project.label' => [$this->translator->translate('Projekt'), 'size' => 40, 'format' => 'text'],
        'version' => [$this->translator->translate('Verze'), 'size' => 30, 'format' => 'text'],
        'chat_count' => [$this->translator->translate('Komentáře'), 'size' => 10],
        'description' => [$this->translator->translate('Text_úkolu'), 'size' => 80, 'format' => 'text'],
        'cl_task_category.label' => [$this->translator->translate('Druh_úkolu'), 'size' => 20, 'format' => 'text'],
        'cl_users.name' => [$this->translator->translate('Pracovník'), 'size' => 20, 'format' => 'text'],
        'priority' => [$this->translator->translate('Priorita'), 'size' => 8, 'format' => 'integer'],
        'target_date' => [$this->translator->translate('Ukončení_plánované'), 'size' => 10, 'format' => 'date'],
        'finished' => [$this->translator->translate('Hotovo'), 'size' => 10, 'format' => 'boolean'],
        'end_date' => [$this->translator->translate('Skutečné_ukončení'), 'size' => 10, 'format' => 'date'],
        'checked' => [$this->translator->translate('Zkontrolováno'), 'size' => 10, 'format' => 'boolean'],
        'payment' => [$this->translator->translate('Placeně'), 'size' => 10, 'format' => 'boolean'],
        'invoice' => [$this->translator->translate('Fakturováno'), 'size' => 10, 'format' => 'boolean'],
        'cl_users2__' => [$this->translator->translate('Kontrola'), 'size' => 20, 'format' => 'text', 'function' => 'getUserName', 'function_param' => ['cl_users2_id']],
        'cl_partners_event.event_number' => [$this->translator->translate('Helpdesk'), 'size' => 20, 'format' => 'text'],
        'created' => [$this->translator->translate('Vytvořeno'),'format' => 'datetime'],
        'create_by' => $this->translator->translate('Vytvořil'),
        'changed' => [$this->translator->translate('Změněno'),'format' => 'datetime'],
        'change_by' => $this->translator->translate('Změnil')];

	//$this->FilterC = 'UPPER(currency_name) LIKE ?';
	//$this->filterColumns = array();	
	$this->DefSort = 'task_date DESC';
    $this->filterColumns = ['priority' => 'autocomplete','cl_task_category.label' => 'autocomplete', 'cl_partners_book.company' => 'autocomplete', 'task_number' => 'autocomplete', 'cl_project.label' => 'autocomplete',
                            'version' => 'autocomplete', 'task_date' => 'none','cl_users.name' => 'autocomplete',
                            'description' => '', 'target_date' => 'none', 'end_date' => ''];

    $this->userFilterEnabled = TRUE;
    $this->userFilter = ['cl_project.label', 'task_number', 'cl_task_category.label', 'version', 'description', 'cl_users.name', 'cl_partners_book.company'];

    //$this->cxsEnabled = TRUE;
    //$this->userCxsFilter = [':cl_invoice_items.item_label', ':cl_invoice_items.cl_pricelist.identification', ':cl_invoice_items.cl_pricelist.item_label', ':cl_invoice_items.description1', ':cl_invoice_items.description2',
    //   ':cl_invoice_items_back.item_label', ':cl_invoice_items_back.description1', ':cl_invoice_items_back.description2'];


    $this->defValues = ['task_date' => new \Nette\Utils\DateTime,
            'cl_users_id' => $this->user->getId()];

	$this->numberSeries = ['use' => 'task', 'table_key' => 'cl_number_series_id', 'table_number' => 'task_number'];

    $this->bscOff = FALSE;
    $this->bscEnabled = $this->getUser()->getIdentity()->bsc_enabled;
    $this->bscPages = ['card' => ['active' => false, 'name' => $this->translator->translate('karta'), 'lattefile' => $this->getLattePath() . 'Task\card.latte'],
            'popis' => ['active' => true, 'name' => $this->translator->translate('Popis'), 'lattefile' => $this->getLattePath() . 'Task\description.latte'],
            'work' => ['active' => false, 'name' => $this->translator->translate('Práce'), 'lattefile' => $this->getLattePath() . 'Task\work.latte'],
            'workers' => ['active' => false, 'name' => $this->translator->translate('Pracovníci'), 'lattefile' => $this->getLattePath() . 'Task\workers.latte'],
            'files' => ['active' => false, 'name' => $this->translator->translate('Soubory'), 'lattefile' => $this->getLattePath() . 'Task\files.latte']
    ];

    $this->bscSums = [];
    $this->bscToolbar = [

    ];
//    $this->bscTitle = ['oznameni_za_den' => $this->translator->translate('Oznámení_za_den')];



	//$this->readOnly = array('identification' => TRUE);	
	//$settings = $this->CompaniesManager->getTable()->fetch();	
	$this->toolbar = [1 => ['url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nový_záznam'), 'class' => 'btn btn-primary'],
                        2 => ['url' => $this->link('ImportData:', ['modal' => $this->modal, 'target' => $this->name]), 'rightsFor' => 'write', 'label' => 'Import', 'class' => 'btn btn-primary'],];

        /*predefined filters*/
        $pdCount = count($this->pdFilter);
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

        $this->pdFilter[++$pdCount2] = ['url' => $this->link('pdFilter!', ['index' => $pdCount2, 'pdFilterIndex' => $pdCount2]),
            'filter' => 'payment = 1 AND invoice = 0 AND finished = 1',
            'sum' => [],
            'rightsFor' => 'read',
            'label' => $this->translator->translate('Placené_hotové_bez_faktury'),
            'title' => $this->translator->translate('Placené_hotové_bez_faktury'),
            'data' => ['data-ajax="true"', 'data-history="true"'],
            'class' => 'ajax', 'icon' => 'iconfa-filter'];

        $this->pdFilter[++$pdCount2] = ['url' => $this->link('pdFilter!', ['index' => $pdCount2, 'pdFilterIndex' => $pdCount2]),
            'filter' => 'cl_users_id IS NULL',
            'sum' => [],
            'rightsFor' => 'read',
            'label' => $this->translator->translate('Nepřidělené_úkoly'),
            'title' => $this->translator->translate('Nepřidělené_úkoly'),
            'data' => ['data-ajax="true"', 'data-history="true"'],
            'class' => 'ajax', 'icon' => 'iconfa-filter'];


        $this->previewLatteFile = '../../../' . $this->getLattePath() . 'Task\previewContent.latte';
        $this->enabledPreviewDoc = TRUE;

        $this->chatEnabled = true;
        $this->globalSaveForms = true;


    }	
    
    public function renderDefault($page_b = 1,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal, $cxs)  {
	parent::renderDefault($page_b,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal,$cxs);

    }
    
    public function renderEdit($id,$copy,$modal){
	    parent::renderEdit($id,$copy,$modal);
        $tmpMyData = $this->DataManager->find($this->id);
        $myArr['id'] = $tmpMyData['id'];
        $myArr['description'] = $tmpMyData['description'];
        $this['editNext']->setDefaults($myArr);
    }


    public function createComponentChat()
    {
        $user_id = $this->user->getId();
        $cl_company_id = $this->settings->id;
        $arrEml = [];
        return new Controls\ChatControl($this->translator,$this->ChatManager, $this->DataManager, $this->ArraysManager, $this->UserManager, $this->EmailingManager,
            $this->id, $cl_company_id, $user_id, $arrEml);
    }



    protected function createComponentFiles()
    {
        if ($this->getUser()->isLoggedIn()) {
            $user_id = $this->user->getId();
            $cl_company_id = $this->settings->id;
        }
        // $translator = clone $this->translator->setPrefix([]);
        return new Controls\FilesControl($this->translator, $this->FilesManager, $this->UserManager, $this->id, 'cl_task_id', NULL, $cl_company_id, $user_id,
            $this->CompaniesManager, $this->ArraysManager);
    }



    protected function createComponentPreviewContent()
    {
        return new \Controls\PreviewContent($this->previewLatteFile, $this->DataManager, NULL, NULL);
    }


    protected function createComponentEditNext($name)
    {
        $form = new Form($this, $name);
        $form->addHidden('id', NULL);

        $form->addTextArea('description', $this->translator->translate('Popis'), 60, 20)
            ->setHtmlAttribute('placeholder',$this->translator->translate('Popis'));


        $form->addSubmit('send', $this->translator->translate('Uložit'))->
                        setHtmlAttribute('class','btn btn-primary')->
                        setHtmlAttribute('data-not-check', '1');

        $form->onSuccess[] = [$this,'SubmitEditNextSubmitted'];
        return $form;
    }

    public function SubmitEditNextSubmitted(Form $form)
    {
        $data = $form->values;
        if ($form['send']->isSubmittedBy())
        {
            $tmpOldData = $this->DataManager->find($this->id);
            $data = $this->removeFormat($data);
            if (!empty($data->id))
                $this->DataManager->update($data, TRUE);
            else
                $this->DataManager->insert($data);

            $tmpNewData = $this->DataManager->find($this->id);
            $tmpChange = $this->HistoryManager->getChanges($this->DataManager->getTableName());
            if ($tmpChange)
            {
                $this->saveNotifyEml($tmpChange, $tmpNewData, '[' . $tmpNewData['task_number'] . ']');
            }
            /*if ($tmpChange)
            {
                $tmpNewData = $this->DataManager->find($this->id);
                $arrObservers = [];
                if (!is_null($tmpNewData['cl_users2_id'])) {
                    $tmpUser2 = $this->UserManager->getUserById($tmpNewData['cl_users2_id']);
                    $tmpEmail = $this->UserManager->getEmail($tmpNewData['cl_users2_id']);
                    if ($tmpEmail != ''){
                        $arrObservers = $tmpUser2['name'] . ' <' . $tmpEmail . '>';
                    }
                }
                $this->emlChangeNotify($tmpChange, $tmpNewData, $arrObservers, '[' . $tmpNewData['task_number'] . ']');
                $this->HistoryManager->changesSend($tmpChange);
            }*/


        }
        $this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
        $this->redirect('default');
        //$this->redrawControl('content');
    }


    protected function createComponentEdit($name)
    {	
        $form = new Form($this, $name);
	    $form->addHidden('id',NULL);

        $form->addText('task_number', $this->translator->translate('Číslo_úkolu'), 20, 20)
            ->setHtmlAttribute('readonly', true);

        $form->addText('version', $this->translator->translate('Verze'), 60, 120)
			->setHtmlAttribute('placeholder','Verze');
	    
	    $arrProjects = $this->ProjectManager->findAll()->where('dtm_end IS NULL')->order('label')->fetchPairs('id','label');
	    $form->addSelect('cl_project_id', $this->translator->translate('Projekt'),$arrProjects)
		    ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_projekt'))
		    ->setHtmlAttribute('placeholder',$this->translator->translate('Projekt'));

        $arrTaskCategory = $this->TaskCategoryManager->findAll()->order('label')->fetchPairs('id','label');
        $form->addSelect('cl_task_category_id', $this->translator->translate('Druh_úkolu'),$arrTaskCategory)
            ->setHtmlAttribute('data-placeholder',$this->translator->translate('Druh_úkolu'))
            ->setHtmlAttribute('placeholder',$this->translator->translate('Druh_úkolu'));






        //28.12.2018 - have to set $tmpId for found right record it could be bscId or id
        if ($this->id == NULL)
            $tmpId = $this->bscId;
        else
            $tmpId = $this->id;

        if ($tmpTask = $this->DataManager->find($tmpId)) {
            if (isset($tmpTask['cl_partners_book_id']))
                $tmpPartnersBookId = $tmpTask->cl_partners_book_id;
            else
                $tmpPartnersBookId = 0;
        } else
            $tmpPartnersBookId = 0;

       // bdump($tmpPartnersBookId);

        $arrPartners = $this->PartnersManager->findAll()->where('id = ?', $tmpPartnersBookId)->fetchPairs('id', 'company');
        //$mySection = $this->getSession('selectbox'); // returns SessionSection with given name
        $form->addSelect('cl_partners_book_id', $this->translator->translate("Klient"), $arrPartners)
            ->setTranslator(NULL)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_klienta'))
            ->setHtmlAttribute('data-urlajax', $this->link('getPartners!'))
            ->setHtmlAttribute('data-url-update-partner-in-form', $this->link('updatePartnerInForm!'))
            ->setPrompt($this->translator->translate('Zvolte_klienta'));

        $arrUsers = [];
        $arrUsers['Aktivní'] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 0')->fetchPairs('id', 'name');
        $arrUsers['Neaktivní'] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 1')->fetchPairs('id', 'name');

        $form->addSelect('cl_users_id', $this->translator->translate("Pracovník"), $arrUsers)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate("Pracovník"))
            ->setPrompt("");

        $form->addSelect('cl_users2_id', $this->translator->translate("Kontrola"), $arrUsers)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate("Kontrola"))
            ->setPrompt("");


        $form->addText('task_date', $this->translator->translate('Datum_zadání'), 10, 10)
            ->setHtmlAttribute('placeholder','Zadání');

        $form->addText('target_date', $this->translator->translate('Ukončení_plánované'), 10, 10)
            ->setHtmlAttribute('placeholder','Ukončení');

        $form->addText('end_date', $this->translator->translate('Skutečné_ukončení'), 10, 10)
            ->setHtmlAttribute('placeholder','Ukončení');

        $form->addText('priority', $this->translator->translate('Priorita'), 1, 1)
            ->setHtmlAttribute('placeholder','Priorita');

        $form->addCheckbox('finished', $this->translator->translate('Hotovo'));
        $form->addCheckbox('checked', $this->translator->translate('Zkontrolováno'));
        $form->addCheckbox('payment', $this->translator->translate('Placeně'));
        $form->addCheckbox('invoice', $this->translator->translate('Fakturováno'));

        $form->addHidden('description', $this->translator->translate('Popis'));


        $form->addSubmit('send', $this->translator->translate('Uložit'))->setAttribute('class','btn btn-success');
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
		    ->setHtmlAttribute('class','btn btn-warning')
		    ->setValidationScope([])
		    ->onClick[] = [$this, 'stepBack'];
		$form->onSuccess[] = [$this,'SubmitEditSubmitted'];
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
            if (!empty($data->id))
                $this->DataManager->update($data, TRUE);
            else
                $this->DataManager->insert($data);

            $tmpNewData = $this->DataManager->find($this->id);
            //$tmpChange = $this->isChange($tmpOldData, $tmpNewData);
            $tmpChange = $this->HistoryManager->getChanges($this->DataManager->getTableName());
            if ($tmpChange)
            {
                $this->saveNotifyEml($tmpChange, $tmpNewData, '[' . $tmpNewData['task_number'] . ']');
            }


        }
        $this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
        $this->redirect('default');
        //$this->redrawControl('content');

        //$this->redrawControl('content');
    }

    private function saveNotifyEml($tmpChange, $tmpNewData, $taskNumber)
    {
        $arrObservers = [];
        foreach($tmpNewData->related('cl_task_workers')->where('cl_users_id IS NOT NULL') as $key => $one)
        {
            $arrObservers[] = $one->cl_users['name'] . ' <' . $one['final_email'] . '>';
        }
        if (!is_null($tmpNewData['cl_users2_id'])) {
            $tmpUser2 = $this->UserManager->getUserById($tmpNewData['cl_users2_id']);
            $tmpEmail = $this->UserManager->getEmail($tmpNewData['cl_users2_id']);
            if ($tmpEmail != ''){
                $arrObservers[] = $tmpUser2['name'] . ' <' . $tmpEmail . '>';
            }
        }
        $this->emlChangeNotify($tmpChange, $tmpNewData, $arrObservers, $taskNumber);
        $this->HistoryManager->changesSend($tmpChange);
    }

    public function getUserName($arrData){
        $retVal = '';
        if (!is_null($arrData['cl_users2_id'])) {
            $tmpUser = $this->UserManager->getUserById($arrData['cl_users2_id']);
            if ($tmpUser)
                $retVal = $tmpUser['name'];
        }
        return $retVal;
    }


    public function createComponentHelpdeskEventsgrid()
    {
        $tmpParentData = $this->DataManager->find($this->id);
        //$arrPartnerWorkers = $this->PartnersBookWorkersManager->findAll()->fetchPairs('id','worker_name');
        $arrUsers = [];
        $arrUsers[$this->translator->translate('Aktivní')] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 0')->fetchPairs('id','name');
        $arrUsers[$this->translator->translate('Neaktivní')] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 1')->fetchPairs('id','name');

        $arrEventType = $this->PartnersEventTypeManager->findAll()
            ->order('event_order')->fetchPairs('id','type_name');
        $arrEventMethod = $this->PartnersEventMethodManager->findAll()
            ->order('method_order')->fetchPairs('id','method_name');

       // if (!is_null($tmpParentData['cl_commission_id'])) {
       //     $arrTask['nehotové'] = $this->CommissionTaskManager->findAll()->select('name, id')
       //         ->where('done = 0 AND cl_commission_id = ?', $tmpParentData['cl_commission_id'])
       //         ->order('name')->fetchPairs('id', 'name');
       //     $arrTask['hotové'] = $this->CommissionTaskManager->findAll()->select('name, id')
       //         ->where('done = 1 AND cl_commission_id = ?', $tmpParentData['cl_commission_id'])
       //         ->order('name')->fetchPairs('id', 'name');
       // }else{
            $arrTask = [];
       // }

        $arrData = ['date' => [$this->translator->translate('Datum_řešení'),'format' => 'datetime2', 'class' => 'nofocus'],
            'cl_users.name' => [$this->translator->translate('Autor'),'format' => 'chzn-select', 'values' => $arrUsers],
            'cl_partners_event_type.type_name' => [$this->translator->translate('Typ_události'),'format' => 'chzn-select', 'values' => $arrEventType],
            //'date_to' => array('Konec','format' => 'datetime2'),
            'cl_partners_event_method.method_name' => [$this->translator->translate('Způsob'),'format' => 'chzn-select', 'values' => $arrEventMethod],
            'work_time_hours' => [$this->translator->translate('Hodin'),'format' => 'hours', 'size' => '10'],
            'work_time_minutes' => [$this->translator->translate('Minut'),'format' => 'minutes', 'size' => '10'],
            //'work_time' => array('Čas celkem','format' => 'hours', 'size' => '10', 'readonly' => TRUE),
            'public' => [$this->translator->translate('Veřejné'),'format' => 'boolean'],
            'finished' => [$this->translator->translate('Hotovo'),'format' => 'boolean'],
            'cl_commission_task.name' => [$this->translator->translate('Úkol'), 'format' => 'chzn-select', 'values' => $arrTask],
            'cl_partners_event_id' => ['','format' => 'hidden'],
            'description_original' => [$this->translator->translate('Podrobný_popis'),'format' => "textarea", 'readonly' => TRUE, 'size' => 100,  'rows' => 4,'newline' => TRUE,
                'hidCondition' => ['data' => 'description_original', 'condition' => '==', 'value' => '']],
            'description' => [$this->translator->translate('Podrobný_popis'),'format' => "textarea-formated",'size' => 100,  'rows' => 4,'newline' => TRUE,
                'hidCondition' => ['data' => 'description_original', 'condition' => '!=', 'value' => '']],
            'add_text' => [$this->translator->translate('Materiál_a_jiné_náklady'),'format' => "textarea",'size' => 100,  'rows' => 4,'newline' => TRUE],
        ];

        //if (is_null($tmpParentData['cl_commission_id']))
        //{
            unset($arrData['cl_commission_task.name']);
        //}
        $now = new \Nette\Utils\DateTime;
        $control =  new Controls\ListgridControl(
            $this->translator,
            $this->PartnersEventManager, //model manager for showed data
            $arrData, //array(columnName,array(Name,Format))
            [], //condition rows
            $tmpParentData['cl_partners_event_id'], //parent ID for constraints
            ['cl_users_id' => $this->user->getId(),
                'date' => $now, 'public' => false,
                'cl_partners_event_type_id' => $this->PartnersEventTypeManager->getNextType(0)
            ], //default data for new record
            $this->DataManager, //parent data model manager
            FALSE, //pricelist model manager $this->PriceListManager
            FALSE, //pricelistpartner model manager $this->PriceListPartnerManager
            TRUE, //TRUE/FALSE enable add empty row without selecting from pricelist
            ['pricelist2' => $this->link('RedrawPriceList2!')
            ], //'pricelist2' => $this->link('RedrawPriceList2!'),
            //'duedate' => $this->link('RedrawDueDate2!')
            //array of urls in presenter which are used from component
            FALSE, //movable row
            'date', //ordercolumn
            FALSE, //selectmode
            [], //quicksearch
            "", //fontsize
            'cl_partners_event.cl_partners_event_id', //parentcolumnname
            TRUE //pricelistbottom
        );

        $control->onChange[] = function ()
        {
            $this->UpdateSum();

        };
        $control->setContainerHeight('auto');
        $control->setDataSource($tmpParentData['cl_partners_event_id']);
        return $control;

    }

    public function UpdateSum(){
        $tmpParentData = $this->DataManager->find($this->id);
        $this->handleShowComment($tmpParentData['cl_partners_book_id']);
        //$this->redrawControl('showCommentMain');
    }


    public function afterDelete($line)
    {
        //return parent::afterDelete($line); // TODO: Change the autogenerated stub
        $tmpParentData = $this->DataManager->find($this->id);
        if (!is_null($tmpParentData['cl_partners_event_id'])) {
            $this->PartnersEventManager->update(['id' => $tmpParentData['cl_partners_event_id']]);
        }
        $this->UpdateSum();
    }


    public function afterDataSaveListGrid($dataId, $name = NULL)
    {
        parent::afterDataSaveListGrid($dataId, $name);

        // bdump($name);
        if ($name == 'workers'){
            $this->TaskWorkersManager->makeFinalEmail($dataId);
        }else {
            //23.2.2019 - there must be worktime solution
            $tmpData = $this->PartnersEventManager->find($dataId);
            if ($tmpData) {
                $arrData = [];
                $arrData['id'] = $dataId;
                $arrData['cl_partners_event_id'] = $tmpData->cl_partners_event_id;
                $arrData['work_time'] = ($tmpData->work_time_hours * 60) + $tmpData->work_time_minutes;
                $this->PartnersEventManager->update($arrData);
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

    protected function createComponentWorkers()
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
            $this->TaskWorkersManager, //data manager
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
        $control->setNewLineName('Pracovník');
        $control->setNewLineTitle('Přidá_pracovníka');
        return $control;
    }



}
