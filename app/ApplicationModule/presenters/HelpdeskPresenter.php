<?php

namespace App\ApplicationModule\Presenters;

use App\Controls;
use Nette\Application\UI\Form;
use Nette\Application\UI\Control;
use Nette,
	App\Model;
use Nette\Mail\Message,
    Nette\Utils\Strings;
use Nette\Mail\SendmailMailer;
use Tracy\Debugger;

//use Nextras\Application\LinkFactory;


class HelpdeskPresenter extends \App\Presenters\BaseListPresenter {

  
    public  $pairedDocsShow = FALSE;
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
    
    /** @persistent */
    public $idParent;      

	
    public $cl_partners_book_id , $eventsModalShow, $eventNew, $cl_partners_event_type_id,$readOnly,$myReadOnly  ;
    public $cl_partners_category_id,$cl_partners_event_method_id;

    
    /** @persistent */
    public $event_id;
    
	
	public $id;
    
    /**
    * @inject
    * @var \App\Model\FilesManager
    */
    public $FilesManager;
    

    /**
    * @inject
    * @var \App\Model\StatusManager
    */
    public $StatusManager;        
    
    /**
    * @inject
    * @var \App\Model\PartnersEventUsersManager
    */
    public $PartnersEventUsersManager;        
    
    
    /**
    * @inject
    * @var \App\Model\UsersManager
    */
    public $UsersManager;        	
	
    /**
    * @inject
    * @var \App\Model\EmailingManager
    */
    public $EmailingManager;	

	/**
    * @inject
    * @var \App\Model\EmailingTextManager
    */
    public $EmailingTextManager;		
    
	
    /**
    * @inject
    * @var \App\Model\UserManager
    */
    public $UserManager;      
  
        
    
    /**
    * @inject
    * @var \App\Model\PartnersEventManager
    */
    public $DataManager;    

    /**
    * @inject
    * @var \App\Model\PartnersManager
    */
    public $PartnersManager;      
	
    /**
    * @inject
    * @var \App\Model\PartnersCategoryManager
    */
    public $PartnersCategoryManager;      	
	
    /**
    * @inject
    * @var \App\Model\PartnersBookWorkersManager
    */
    public $PartnersBookWorkersManager;   	
    
    /**
    * @inject
    * @var \App\Model\PartnersEventManager
    */
    public $PartnersEventManager;

    /**
    * @inject
    * @var \App\Model\PartnersBranchManager
    */
    public $PartnersBranchManager;    
    
    /**
    * @inject
    * @var \App\Model\CommissionManager
    */
    public $CommissionManager;


    /**
     * @inject
     * @var \App\Model\CommissionTaskManager
     */
    public $CommissionTaskManager;

    /**
    * @inject
    * @var \App\Model\CommissionWorkManager
    */
    public $CommissionWorkManager;         
    
    /**
    * @inject
    * @var \App\Model\PartnersEventTypeManager
    */
    public $PartnersEventTypeManager;         
	
    /**
    * @inject
    * @var \App\Model\PartnersEventMethodManager
    */
    public $PartnersEventMethodManager;         	
    
    /**
    * @inject
    * @var \App\Model\TagsManager
    */
    public $TagsManager;             
    
    /**
    * @inject
    * @var \App\Model\CenterManager
    */
    public $CenterManager;        
        
    /**
    * @inject
    * @var \App\Model\TextsManager
    */
    public $TextsManager;


    /**
     * @inject
     * @var \App\Model\TaskManager
     */
    public $TaskManager;

    /**
     * @inject
     * @var \App\Model\TaskCategoryManager
     */
    public $TaskCategoryManager;

    /**
     * @inject
     * @var \App\Model\PairedDocsManager
     */
    public $PairedDocsManager;



    protected function createComponentTextsUse() {
       // $translator = clone $this->translator;
       // $translator->setPrefix([]);
	    return new TextsUseControl(
            $this->DataManager, $this->id, 'partners_event', $this->TextsManager, $this->translator);
    }


    protected function createComponentPairedDocs()
    {
        return new PairedDocsControl(
            $this->DataManager, $this->id, $this->PairedDocsManager, $this->translator);
    }

    
  protected function createComponentSumOnDocs() {
      //$this->translator->setPrefix(['applicationModule.Helpdesk']);
	if ($data = $this->DataManager->findBy(array('cl_partners_event.id' => $this->id))->fetch())
	{
	    
		$dataArr = array(
				array('name' => $this->translator->translate('Celkem_hodin:'), 'value' => $data->work_time/60, 'currency' => 'hod.'),
				);

	}else{
	    $dataArr = array();
	}
	 
	return new SumOnDocsControl(
        $this->translator,$this->DataManager, $this->id, $this->settings, $dataArr);
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

        if (!is_null($tmpParentData['cl_commission_id'])) {
            $arrTask['nehotové'] = $this->CommissionTaskManager->findAll()->select('name, id')
                ->where('done = 0 AND cl_commission_id = ?', $tmpParentData['cl_commission_id'])
                ->order('name')->fetchPairs('id', 'name');
            $arrTask['hotové'] = $this->CommissionTaskManager->findAll()->select('name, id')
                ->where('done = 1 AND cl_commission_id = ?', $tmpParentData['cl_commission_id'])
                ->order('name')->fetchPairs('id', 'name');
        }else{
            $arrTask = [];
        }

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

         if (is_null($tmpParentData['cl_commission_id']))
         {
            unset($arrData['cl_commission_task.name']);
         }
         $now = new Nette\Utils\DateTime;
        $control =  new Controls\ListgridControl(
                    $this->translator,
				    $this->PartnersEventManager, //model manager for showed data
				    $arrData, //array(columnName,array(Name,Format))
				    [], //condition rows
				    $this->id, //parent ID for constraints
				    ['cl_users_id' => $this->user->getId(),
					    'date' => $now, 'public' => $tmpParentData['public'],
					    'cl_partners_event_type_id' => $this->PartnersEventTypeManager->getNextType($tmpParentData->cl_partners_event_type_id)
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
	    return $control;

     }              
    
    protected function startup() 
		{
		parent::startup();
        //$this->translator->setPrefix(['applicationModule.Helpdesk']);
		$this->formName = "Helpdesk";		
		$this->mainTableName = 'cl_partners_event';		
		$this->dataColumns = ['event_number' => [$this->translator->translate('Událost_č.'), 'packing' => ['cl_partners_event_id','==',NULL]],
						'date_rcv' => [$this->translator->translate('Přijato'),'format' => 'datetime'],
						'cl_partners_category.category_name' => [$this->translator->translate('Důležitost'),'format' => 'colortag'],
						'cl_partners_event_type.type_name' => [$this->translator->translate('Typ_události'),'format' => 'text'],
						'cl_partners_book.company' => [$this->translator->translate('cl_partners_bookPh'), 'show_clink' => true],
						'cl_center.name' => [$this->translator->translate('Středisko'), 'format' => 'text'],
						'email_rcv' => [$this->translator->translate('Přijato_z'), 'format' => 'text'],
						'work_label' => [$this->translator->translate('Zadání'),'format' => 'text'],
						'cl_status.status_name' => [$this->translator->translate('Stav'),'format' => 'colortag'],
						'date_end' => [$this->translator->translate('Řešit_do'),'format' => 'datetime'],
						//'date' => array('Začátek','format' => 'datetime'),
						'date_to' => [$this->translator->translate('Konec'),'format' => 'datetime'],
						'cl_partners_event_method.method_name' => [$this->translator->translate('Způsob'),'format' => 'text'],
						'finished' => [$this->translator->translate('Hotovo'),'format' => 'boolean'],
						'work_time' => [$this->translator->translate('Hodiny'),'format' => 'hours', 'decimal' => 2],
						'cl_invoice.inv_number' => [$this->translator->translate('Faktura'),'format' => 'text'],
						'cl_commission.cm_number' => [$this->translator->translate('Zakázka'),'format' => 'text'],
						'public' => [$this->translator->translate('Veřejné'),'format' => 'boolean'],
                        'payment' => [$this->translator->translate('Placeně'),'format' => 'boolean'],
						'cl_users.name' => [$this->translator->translate('Správce_/_autor'),'format' => 'text'],
						'create_by' => [$this->translator->translate('Vytvořil'), 'format' => 'text'], 'created' => [$this->translator->translate('Dne'),'format' => 'datetime'],
						'change_by' => [$this->translator->translate('Změnil'), 'format' => 'text'], 'changed' => [$this->translator->translate('Dne'),'format' => 'datetime']];
		$this->dataColumnsRelated = [];
		/*$this->dataColumnsRelated = array('0' => array('aa',  'colspan' => 3), 
						'cl_partners_event_type.type_name' => array('Typ události','format' => 'text'), 						
						'description' => array('Autor: ', 'format' =>'html', 'size' => 20, 'colspan' => 4, 'overflow' => 'scroll', 'plusdata' => 'description_original', 'plusdataO' => 'Klient: '),
						'date' => array('Začátek','format' => 'datetime2line'),
						'date_to' => array('Konec','format' => 'datetime2line'),
						'cl_partners_event_method.method_name' => array('Způsob','format' => 'text'), 
						'finished' => array('Hotovo','format' => 'boolean'), 	    
						'work_time' => array('Hodin celkem','format' => 'hours'),
						'public' => array('Veřejné','format' => 'boolean'), 	    
						'cl_users.name' => array('Správce / autor','format' => 'text'),	    
						'create_by' => array('Vytvořil', 'format' => 'text') , 'created' => array('Dne','format' => 'datetime2linesec'), 
						'change_by' => array('Změnil', 'format' => 'text') , 'changed' => array('Dne','format' => 'datetime2linesec') );		*/
		

		//'priority' => array('Důležitost',FALSE,'function' => 'getPriorityName'),		
		//'clfiles' => array('Přílohy', 'format' => 'decimal'),	
		//'description' => array('Text události','format' => 'html'), 
		//$this->formatColumns = array('description' => 'html', 'date' => "date",'created' => 'datetime', 'changed' => 'datetime');		
		//$this->FilterC = 'cl_partners_event_id == NULL AND (UPPER(description) LIKE ? OR UPPER(tags) LIKE ?)';
		//$this->agregateColumns = 'COUNT(:cl_files.cl_partners_event_id) AS clfiles';

        $this->userFilterEnabled = TRUE;
        $this->userFilter = ['event_number', 'work_label', 'cl_center.name', 'cl_status.status_name', 'cl_partners_category.category_name', 'cl_partners_book.company'];

        $this->cxsEnabled = TRUE;
        $this->userCxsFilter = [':cl_partners_event.work_label', ':cl_partners_event.description'];

		$this->filterColumns = ['event_number' => 'autocomplete','cl_partners_book.company' => 'autocomplete' , 'cl_partners_event_type.type_name' => 'autocomplete', 'email_rcv' => 'autocomplete',
						'work_label' => 'autocomplete', 'cl_users.name' => 'autocomplete','cl_status.status_name' => 'autocomplete', 'cl_center.name' => 'autocomplete',
						'date_rcv' => '', 'cl_partners_category.category_name' => 'autocomplete', 'date_end' => '', 'date' => '', 'date_to' => '',
						'work_time' => '', 'cl_invoice.inv_number' => 'autocomplete', 'cl_commission.cm_number' => ''];
		$this->numberSeries = ['use' => 'partners_event', 'table_key' => 'cl_number_series_id', 'table_number' => 'event_number'];
		$this->DefSort = 'cl_partners_event.finished, cl_partners_event.date_rcv DESC';
		$this->parentLink = '';
		//$this->relatedTable = 'cl_partners_event';
		//$this->relatedTableHide = array('packed','==','1');
		$this->mainFilter = 'cl_partners_event.cl_partners_event_id IS NULL';
		$testDate = new \Nette\Utils\DateTime;
		$testDate = $testDate->modify('-1 day');
		$this->conditionRows = [['date_end','<=',$testDate, 'color:red', 'notlastcond'], ['finished','==','0','color:red', 'lastcond']];
		$this->readOnly = ['event_number' => TRUE];
		//$this->rowFunctions = array('copy' => 'disabled');

		if ($tmpDef = $this->PartnersEventTypeManager->findAll()->where('default_event OR main_event')->order('default_event')->fetch())
			$tmpDefId = $tmpDef->id;
		else
			$tmpDefId = NULL;

		if ($tmpDef = $this->PartnersEventMethodManager->findAll()->where('default_method')->order('method_order')->fetch())
			$tmpDefMethodId = $tmpDef->id;
		else
			$tmpDefMethodId = NULL;	

		$now = new \Nette\Utils\DateTime;
		$this->defValues = ['date' => $now,
					 'date_rcv' => $now,
					 'cl_partners_event_type_id' => $tmpDefId,
					 'cl_partners_book_id' => $this->idParent,
					 'cl_partners_event_method_id' => $tmpDefMethodId,
					 'cl_users_id' => $this->getUser()->id];
			

		
		$this->toolbar = [0 => ['group_start' => ''],
								1 => ['url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nová_událost'), 'class' => 'btn btn-primary', 'icon' => 'iconfa-plus'],
								2 => $this->getNumberSeriesArray('partners_event'),
								3 => ['group_end' => ''],
								4 => ['group' =>
                                                    [0 => ['url' => $this->link('report!', ['index' => 1]), 'rightsFor' => 'report', 'label' => $this->translator->translate('report_clients'), 'title' => $this->translator->translate('report_clients_title'),
														'class' => 'ajax', 
														'data' => ['data-ajax="true"', 'data-history="false"'],
														'icon' => 'iconfa-print'],
                                                          1 => ['url' => $this->link('report!', ['index' => 2]),'rightsFor' => 'report', 'label' => $this->translator->translate('Pracovníci'),  'title' => $this->translator->translate('Práce_techniků_za_zvolené_období'),
														'class' => 'ajax', 
														'data' => ['data-ajax="true"', 'data-history="false"'],
														'icon' => 'iconfa-print']],
                                                        'group_settings' => ['group_label' => 'Tisk', 'group_class' => 'btn btn-primary dropdown-toggle btn-sm', 'group_title' =>  $this->translator->translate('tisk'), 'group_icon' => 'iconfa-print']
                                ]
        ];
        if (!$this->isAllowed($this->name, 'report')){
            unset($this->toolbar[4]);
        }

		$this->actionList = [];
			//1 => array('type' => 'show_child', 'url' => $this->link('newSub!'), 'label' => 'Zápis řešení', 'class' => ''),
			//2 => array('type' => 'show_review', 'url' => $this->link(':Application:HelpdeskReview:'), 'label' => 'Přehled události a řešení', 'class' => '') 
		$this->report = [1 => ['reportLatte' => __DIR__.'/../templates/Helpdesk/helpdeskReportClients.latte',
												'reportName' => $this->translator->translate('Přehled_podle_klientů')],
				                2 => ['reportLatte' => __DIR__.'/../templates/Helpdesk/helpdeskReportWorkers.latte',
												'reportName' => $this->translator->translate('Přehled_podle_techniků')]];
		//__DIR__.'/../templates/Emailing/
		
		$this->bscOff = FALSE;
        $this->bscEnabled = $this->getUser()->getIdentity()->bsc_enabled;
		$this->bscPages = ['card' => ['active' => false, 'name' => $this->translator->translate('karta'), 'lattefile' => $this->getLattePath(). 'Helpdesk\card.latte'],
					'items' => ['active' => true, 'name' => $this->translator->translate('zapsaná_řešení'), 'lattefile' => $this->getLattePath(). 'Helpdesk\events.latte'],
					'files' => ['active' => false, 'name' => $this->translator->translate('soubory'), 'lattefile' => $this->getLattePath(). 'Helpdesk\files.latte']
        ];
		$this->bscSums = ['lattefile' => $this->getLattePath(). 'Helpdesk\sums.latte'];
		$this->bscToolbar = [1 => ['url' => 'showTextsUse!', 'rightsFor' => 'write', 'label' => $this->translator->translate('časté_texty'), 'class' => 'btn btn-success showTextsUse',
								'data' => ['data-ajax="true"', 'data-history="false"', 'data-not-check="1"'],'icon' => 'glyphicon glyphicon-list'],
                            7 => ['url' => 'showPairedDocs!', 'rightsFor' => 'write', 'label' => $this->translator->translate('doklady'), 'class' => 'btn btn-success',
                                'data' => ['data-ajax="true"', 'data-history="false"'], 'icon' => 'glyphicon glyphicon-list-alt']];
		/*$this->bscToolbar = array(
					  1 => array('url' => 'showTextsUse!', 'rightsFor' => 'write', 'label' => 'časté texty', 'class' => 'btn btn-success showTextsUse', 
								'data' => array('data-ajax="true"', 'data-history="false"'),'icon' => 'glyphicon glyphicon-list'),	    
					  2 => array('url' => 'paymentShow!', 'rightsFor' => 'write', 'label' => 'úhrady a zálohy', 'class' => 'btn btn-success', 
								'data' => array('data-ajax="true"', 'data-history="false"'),'icon' => 'glyphicon glyphicon-edit'),	    
					  3 => array('url' => 'showPairedDocs!', 'rightsFor' => 'write', 'label' => 'doklady', 'class' => 'btn btn-success', 
								'data' => array('data-ajax="true"', 'data-history="false"'),'icon' => 'glyphicon glyphicon-list-alt'),
					  4 => array('url' => 'savePDF!', 'rightsFor' => 'enable', 'label' => 'uložit do PDF', 'class' => 'btn btn-success', 
							      'data' => array('data-ajax="false"', 'data-history="false"'),'icon' => 'glyphicon glyphicon-print'),
					  5 => array('url' => 'sendDoc!', 'rightsFor' => 'write', 'label' => 'odeslat emailem', 'class' => 'btn btn-success', 'icon' => 'glyphicon glyphicon-send'),	    

					);*/
		$this->bscTitle = ['event_number' => $this->translator->translate('Číslo_události'), 'cl_partners_book.company' => $this->translator->translate('Klient')];

		$this->quickFilter = ['cl_status.status_name' => ['name' => $this->translator->translate('Zvolte_filtr_zobrazení'),
									    'values' => $this->StatusManager->findAll()->where('status_use = ?','partners_event')->fetchPairs('id','status_name')]
        ];

    }	
    
    public function UpdateSum()
    {
        //$this->InvoiceManager->updateInvoiceSum($this->id);
        parent::UpdateSum();
        $this['helpdeskEventsgrid']->redrawControl('editLines');

    }    
        
    
    public function renderDefault($page_b = 1,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal, $cxs)  {
        parent::renderDefault($page_b,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal,$cxs);


    }

    public function renderEdit($id,$copy,$modal,$idParent = NULL,$roModal = FALSE ){
        parent::renderEdit($id,$copy,$modal);

        $existWork = $this->PartnersEventUsersManager->findBy(['cl_partners_event_id' => $this->id])->fetchPairs('cl_users_id','cl_users_id');
        $defDataNew = [];
        $defDataNew['workers'] = $existWork;
        //set status s_work if is defined and previous status was s_new
        //27.03.2017 - s_work status set only if work_label is not empty and previou status was s_new
        $tmpData = $this->PartnersEventManager->findAllTotal()->where('id = ?', $this->id)->fetch();
        if ($tmpData)
        {
            $nStatus= $this->StatusManager->findAll()->where('status_use = ? AND s_work = ?','partners_event',1)->fetch();
            if (!is_null($tmpData['cl_status_id']) && $tmpData->cl_status->s_new == 1 &&  !empty($tmpData->work_label) && $nStatus){
                $defDataNew['cl_status_id'] = $nStatus->id;
            }
        }
        //06.03.2019 - set flag for recognize if user can write child cl_partners_event
        $this->userAssigned = FALSE;
        if (in_array($this->user->getId(), $existWork)){
            $this->userAssigned = TRUE;
        }
        if (!is_null($tmpData['cl_partners_book_id'])) {
            $arrCommission['dostupné'] = $this->CommissionManager->findAll()->select('CONCAT(cm_number, " ", cm_title) AS text, cl_commission.id')
                ->where('(use_for_hd = 1 AND cl_status.s_fin = 0 AND cl_status.s_storno = 0 AND cl_partners_book_id = ?)', $tmpData['cl_partners_book_id'])
                ->order('text')->fetchPairs('id','text');
            if (!is_null($tmpData['cl_commission_id'])) {
                $arrCommission['vybráno'] = $this->CommissionManager->findAll()->select('CONCAT(cm_number, " ", cm_title) AS text, cl_commission.id')
                    ->where('id = ?', $tmpData['cl_commission_id'])
                    ->order('text')->fetchPairs('id', 'text');
            }
            //$defDataNew['cl_commission_id'] = $arrCommission;
            $this['edit']['cl_partners_book_id']->setItems([$tmpData['cl_partners_book_id'] => $tmpData->cl_partners_book['company']]);
            $this['edit']['cl_commission_id']->setItems($arrCommission);
        }else{
            $this['edit']['cl_partners_book_id']->setItems([]);
            $this['edit']['cl_commission_id']->setItems([]);
        }


        $this['edit']->setValues($defDataNew);
        $this->template->task = $this->TaskManager->findAll()->where('cl_partners_event_id = ?', $this->id);

    }

    public function actionNewSub($cl_partners_event_id,$cl_partners_book_id,$cl_partners_event_type_id,$cl_partners_event_method_id)
	{
		$data = array();
		//13.04.2016 - in case of subAction we don't need number for event
		//$data = $this->getNumberSeries();

		/*musime najít následující typ události pro daný hlavní typ*/
		if ($tmpTypes = $this->PartnersEventTypeManager->findAll()->order('event_order'))
		{
			$tmpArr = $tmpTypes->fetchPairs('id','id');
			//$tmpMain = array_search($cl_partners_event_type_id, $tmpArr);
			$tmpNext = (next($tmpArr));
		}  else {
			$tmpNext = NULL;
		}
		$data['cl_partners_event_id'] = $cl_partners_event_id;
		$data['cl_partners_book_id'] = $cl_partners_book_id;
		$data['cl_partners_event_type_id'] = $tmpNext;
		$data['cl_partners_event_method_id'] = $cl_partners_event_method_id;
		//22.05.2016 - subrequest nemá mít stav
		//if ($tmpStatus = $this->StatusManager->findAll()->where(array('status_use' => 'partners_event', 's_new' => 1))->fetch())
				//$data['cl_status_id'] = $tmpStatus->id;		
		
		$arrData = array_merge($this->defValues,$data); //if must be changes in default data, we can do it here
		$row=$this->DataManager->insert($arrData);
		$this->redirect('edit',array('id' => $row->id,'copy'=>FALSE,'filterValue'=> '', 'filterString' => ''));
    }
    
    protected function createComponentFiles()
     {
	    if ($this->getUser()->isLoggedIn()){
    		$user_id = $this->user->getId();
    		$cl_company_id = $this->settings->id;
	    }
        // $translator = clone $this->translator;
        // $translator->setPrefix([]);

		return new Controls\FilesControl($this->translator, $this->FilesManager,$this->UserManager,$this->id,'cl_partners_event_id', NULL, $cl_company_id,$user_id,
            $this->CompaniesManager, $this->ArraysManager);
     }                
	

	
	/*
	 * Set category in form
	 */
	public function getCategory()
	{
		$curr_name = $this->settings->cl_currencies->currency_name;
		$arrPartnersCategoryNew = $this->PartnersCategoryManager->findAll()->select('id,CONCAT(category_name," (","sazba: ",hour_tax," '.$curr_name.' / hodina",")") AS name')->fetchPairs('id','name');

		$tmpReact = $this->PartnersManager->find($this->cl_partners_book_id);
		if ($tmpReact)
		{
			if (isset($tmpReact->cl_partners_category->react_time))
				$tmpCategory = $tmpReact->cl_partners_category;

			//24.05.2016 - add prices to cl_partners_category_id - defined on partner 
			$tmpTaxes = json_decode($tmpReact->cl_partners_category_taxes, TRUE);

			if (!is_null($tmpTaxes))
			{
				$curr_name = $this->settings->cl_currencies->currency_name;
				$arrPartnersCategoryNew = array();
				$arrPartnersCategory= $this->PartnersCategoryManager->findAll();
				foreach($arrPartnersCategory as $key => $one)
				{
					//27.05.2016 - find partners event to determine local or remote  $cl_partners_event_method_id
					if ($tmpEventMethod = $this->PartnersEventMethodManager->find($this->cl_partners_event_method_id))
					{
						if ($tmpEventMethod->remote == 1)
						{
							if (isset($tmpTaxes['categremote'.$key]))
							{
								$tmpTax = $tmpTaxes['categremote'.$key];
								if ($tmpTax == 0)
								{
									$tmpTax = $one->hour_tax_remote;
								}
							}else{
								$tmpTax = $one->hour_tax_remote;
							}
						}else{
							if (isset($tmpTaxes['categ'.$key])) {
								$tmpTax = $tmpTaxes['categ'.$key];
								if ($tmpTax == 0) {
									$tmpTax = $one->hour_tax;																					
								}
							}else {
								$tmpTax = $one->hour_tax;																					
							}
						}
					}else{
						if (isset($tmpTaxes['categremote'.$key])) {
							$tmpTax = $tmpTaxes['categremote'.$key];
							if ($tmpTax == 0)
								$tmpTax = $one->hour_tax_remote;
						}else
							$tmpTax = $one->hour_tax_remote;
					}
				    $arrPartnersCategoryNew[$key] = $one->category_name." (sazba: ".$tmpTax." ".$curr_name." / hodina)";
				}
			}
		}
		return ($arrPartnersCategoryNew);
	}
     
     public function setEventId($id)
     {
		$this->event_id = $id;
     }
    
/*	 protected function createComponentEdit($name)
    {	
		 $form = new Form($this, $name);
		 return $form;
	 }*/
    
  protected function createComponentEdit($name)
    {	
	    $form = new Form($this, $name);
		//$form->setTranslator($this->translator);    
	    //$form->setMethod('POST');
	    $form->addHidden('id',NULL);
	    $form->addHidden('cl_partners_event_id',NULL);
	    $form->addText('event_number', 'Událost č.:', 20, 20)
		    ->setHtmlAttribute('class','form-control input-sm')
		    ->setHtmlAttribute('placeholder', $this->translator->translate('Číslo_události'));

	    $arrStatus= $this->StatusManager->findAll()->where('status_use = ?','partners_event')->fetchPairs('id','status_name');
	    $form->addSelect('cl_status_id',  $this->translator->translate("Stav:"),$arrStatus)
		    ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_stav_události'))
		    ->setPrompt( $this->translator->translate('Zvolte_stav_události'));
	    
	    $form->addText('date',  $this->translator->translate('Začátek:'), 0, 16)
		    ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_začátek'));

	    $form->addText('date_to',  $this->translator->translate('Konec_události:'), 0, 16)
		    ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_konec'));

	    $form->addText('date_end', $this->translator->translate('Reakce_do:'), 0, 16)
		    ->setHtmlAttribute('readonly','readonly')
		    ->setHtmlAttribute('placeholder', $this->translator->translate('Požadovaná_reakce_nejpozději_do'));

	    $form->addText('date_rcv', $this->translator->translate('Datum_přijetí:'), 0, 16)
		    ->setHtmlAttribute('data-url-ajax',$this->link('changeDatercv!'))
		    ->setHtmlAttribute('placeholder', $this->translator->translate('Přijato'));

	    $form->addText('work_label',  $this->translator->translate('Zadání:'), 50, 200)
		    ->setHtmlAttribute('placeholder', $this->translator->translate('Zadání'));
	    //$form->addCheckbox('start_now','Nyní');
	    //$form->addCheckbox('end_now','Nyní');
	    $form->addCheckbox('public', $this->translator->translate('Veřejné'))
                ->setHtmlAttribute('title', $this->translator->translate('Klient_bude_informován_emailem_a_událost_pro_něj_bude_viditelná.'));
	    $form->addCheckbox('finished', $this->translator->translate('Hotovo'));
        $form->addCheckbox('make_task', $this->translator->translate('Vytvořit_úkol'));
        $form->addCheckbox('payment', $this->translator->translate('Placeně'));

        $arrTaskCategory = $this->TaskCategoryManager->findAll()->order('label')->fetchPairs('id','label');
        $form->addSelect('cl_task_category_id', $this->translator->translate('Druh_úkolu'),$arrTaskCategory)
            ->setHtmlAttribute('data-placeholder',$this->translator->translate('Druh_úkolu'))
            ->setHtmlAttribute('placeholder',$this->translator->translate('Druh_úkolu'));
		
	    $arrEventType = $this->PartnersEventTypeManager->findAll()
				->order('event_order')->fetchPairs('id','type_name');	    

	    $form->addSelect('cl_partners_event_type_id', "Typ:",$arrEventType)
		    ->setPrompt( $this->translator->translate('Zvolte_typ_události'))
		    ->setHtmlAttribute('data-url-ajax',$this->link('changeEventType!'))		    
		    ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_typ_události'))
		    ->setHtmlAttribute('class','chzn-select');	    

	    $tmpId = NULL;
	    if ($this->id == NULL){
		    $tmpId = $this->bscId;
	    }else{
		    $tmpId = $this->id;
	    }
	    $tmpEvent = $this->DataManager->find($tmpId);
        if ($tmpEvent)
        {
            $arrPartnerWorkers = $this->PartnersBookWorkersManager->getWorkersGrouped($tmpEvent->cl_partners_book_id);
            $this->cl_partners_book_id = $tmpEvent->cl_partners_book_id;
	    }else{
            $arrPartnerWorkers = array();
            $this->cl_partners_book_id = 0;
	    }
	    //$arrPartnerWorkers = $this->PartnersBookWorkersManager->findAll()->fetchPairs('id','worker_name');
	    
	    $form->addSelect('cl_partners_book_workers_id',  $this->translator->translate("Kontakt:"),$arrPartnerWorkers)
	  	    ->setHtmlAttribute('data-url-ajax',$this->link('getWorker!'))		    				
	  	    ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_kontaktní_osobu'))
	  	    ->setPrompt( $this->translator->translate('Zvolte_kontaktní_osobu'));

	    

		
	    $arrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id','name');
	    $form->addSelect('cl_center_id',  $this->translator->translate("Středisko:"),$arrCenter)
		    ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_středisko'))
		    ->setPrompt( $this->translator->translate('Zvolte_středisko'));

	    $arrEventMethod = $this->PartnersEventMethodManager->findAll()
									->order('method_order')->fetchPairs('id','method_name');			
	    $form->addSelect('cl_partners_event_method_id',  $this->translator->translate("Způsob_řešení:"),$arrEventMethod)
		    ->setPrompt( $this->translator->translate('Zvolte_způsob_řešení'))
		    ->setHtmlAttribute('data-url-ajax',$this->link('changeEventMethod!'))		    
		    ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_způsob_řešení'))
		    ->setHtmlAttribute('class','chzn-select');	      		

		
		
            if ($tmpInvoice = $this->DataManager->find($tmpId)) 
            {
                if (isset($tmpInvoice['cl_partners_book_id']))
                {
                    $tmpPartnersBookId = $tmpInvoice->cl_partners_book_id;
                }else{
                    $tmpPartnersBookId = 0;
                }
               
            }else{
                $tmpPartnersBookId = 0;
            }
        $arrPartners = $this->PartnersManager->findAll()->where('id = ?', $tmpPartnersBookId)->fetchPairs('id','company');

	    /*$mySection = $this->getSession('selectbox'); // returns SessionSection with given name
	    //06.07.2018 - session selectbox is filled via baselist->handleUpdatePartnerInForm which is called by ajax from onchange event of selectbox
	    //this is necessary because Nette is controlling values of selectbox send in form with values which were in selectbox accesible when it was created.
	    if (isset($mySection->cl_partners_book_id_values ))
	    {
		    $arrPartners = 	$mySection->cl_partners_book_id_values;
	    }else{
		    $arrPartners = $this->PartnersManager->findAll()->where('id = ?', $tmpPartnersBookId)->fetchPairs('id','company');
	    }*/
	    
            //$arrPartners = $this->PartnersManager->findAll()->where('id = ?', $tmpPartnersBookId)->fetchPairs('id','company');              
	    //dump($arrPartners);
	    //$arrPartners = $this->PartnersManager->findAll()->fetchPairs('id','company');
	    $form->addSelect('cl_partners_book_id', $this->translator->translate('cl_partners_book'),$arrPartners)
                    ->setHtmlAttribute('data-urlajax', $this->link('getPartners!'))  
                    ->setHtmlAttribute('lang','cs')
		    ->setHtmlAttribute('data-url-update-partner-in-form', $this->link('updatePartnerInForm!'));	   

           
	    $form['cl_partners_book_id']
		    ->setHtmlAttribute('data-partnersbook',$form['cl_partners_book_id']->getHtmlId())
		    ->setHtmlAttribute('data-master_event_type_id',$form['cl_partners_event_type_id']->getHtmlId())
		    ->setHtmlAttribute('data-master_id',$form['id']->getHtmlId())
		    ->setHtmlAttribute('data-slave_workers',$form['cl_partners_book_workers_id']->getHtmlId())
		    ->setHtmlAttribute('data-placeholder',$this->translator->translate('cl_partners_bookPh'))
		    ->setHtmlAttribute('data-url-ajax',$this->link('changePartner!'));

		
	    $curr_name = $this->settings->cl_currencies->currency_name;
	    //$arrPartnersCategory= $this->PartnersCategoryManager->findAll()->select('id,CONCAT(category_name," (","sazba: ",hour_tax," '.$curr_name.' / hodina",")") AS name')->fetchPairs('id','name');
	    $arrPartnersCategory = $this->getCategory();
		    
	    $form->addSelect('cl_partners_category_id',  $this->translator->translate("Důležitost"),$arrPartnersCategory)
		    ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_kategorii_důležitosti_požadavku'))
		    ->setPrompt( $this->translator->translate('Zvolte_kategorii_důležitosti_požadavku'))
		    ->setRequired( $this->translator->translate('Kategorie_důležitosti_musí_být_vybrána'))
		    ->setHtmlAttribute('data-url-category',$this->link('getCategory!'))
		    ->setHtmlAttribute('data-partners_book_id',$form['cl_partners_book_id']->getHtmlId())				
		    ->setHtmlAttribute('data-event_method_id',$form['cl_partners_event_method_id']->getHtmlId())												
		    ->setHtmlAttribute('class','chzn-select');


        $arrCommission = $this->CommissionManager->findAll()->select('CONCAT(cm_number, " ", cm_title) AS text, id')->where('use_for_hd = 1')
                                        ->order('text')->fetchPairs('id','text');
        $form->addSelect('cl_commission_id',  $this->translator->translate("Zakázka"),$arrCommission)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_zakázku'))
            ->setHtmlAttribute('data-url-commission', $this->link('getCommission!'))
            ->setPrompt( $this->translator->translate('Zvolte_zakázku'));
		
	    $arrPartnerBranch = $this->PartnersBranchManager->findAll()->where('cl_partners_book_id = ?',$tmpPartnersBookId)->fetchPairs('id','b_name');		
	    //$arrPartnerBranch = $this->PartnersBranchManager->findAll()->fetchPairs('id','b_name');
	    $form->addSelect('cl_partners_branch_id', "Pobočka:",$arrPartnerBranch)
		    ->setHtmlAttribute('data-url-branch',$this->link('getBranch!'))		    
		    ->setHtmlAttribute('data-partners_book_id',$form['cl_partners_book_id']->getHtmlId())		    
		    ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_pobočku'))
		    ->setPrompt( $this->translator->translate('Zvolte_pobočku'));

		
	    $form['cl_partners_book_id']		
			    ->setHtmlAttribute('data-master_partners_category_id',$form['cl_partners_category_id']->getHtmlId());

	    $form['cl_partners_book_id']		
			    ->setHtmlAttribute('data-master_partners_branch_id',$form['cl_partners_branch_id']->getHtmlId());

	    $form['cl_partners_event_method_id']		
			    ->setHtmlAttribute('data-master_partners_category_id',$form['cl_partners_category_id']->getHtmlId());

	    $form['cl_partners_book_id']
			    ->setPrompt('Vyberte zákazníka');
	    //$this->translator->translate('applicationModule.hdpresenter.cl_partners_bookPh')
	    //$this->translator->translate('applicationModule.hdpresenter.cl_partners_bookRq')
	    
	    
	    //$arrUsers = $this->UserManager->getUsersInCompany($this->user->identity->cl_company_id)->order('name')->fetchPairs('id','name');

	    //24.02.2019 - if user is not event_manager and private for user is on and record is new -> then there must be only current user in selectbox with users
	    $isPrivate = $this->UserManager->isPrivate($this->DataManager->tableName,$this->settings->id,$this->getUser()->id);
	    $arrUsers = array();
	    if ($isPrivate && !$this->user->getIdentity()->event_manager && is_null($tmpEvent['changed'])){
		    $arrUsers['Aktivní'] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('cl_users.id = ?', $this->getUser()->id)->fetchPairs('id','name');
	    }else{
		    $arrUsers['Aktivní'] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 0')->fetchPairs('id','name');
		    $arrUsers['Neaktivní'] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 1')->fetchPairs('id','name');
	    }
	    $form->addSelect('cl_users_id',  $this->translator->translate("Správce:"),$arrUsers)
		    ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_správce_požadavku'))
		    ->setPrompt( $this->translator->translate('Zvolte_správce_požadavku'));

		
	    $form->addText('work_time', $this->translator->translate('Trvání:'));
		
	    $form->addText('work_time_hours',  $this->translator->translate('Trvání:'), 100,15 )
			->setRequired(FALSE)
			->setHtmlAttribute('placeholder', $this->translator->translate('Hodiny'))
			->addCondition(Form::FILLED)
			->addRule(Form::INTEGER, $this->translator->translate('Hodiny_musí_být_celé_číslo_od_0_-_999'));
		
	    $form->addText('work_time_minutes', '', 100,15 )
			->setRequired(FALSE)
			->setHtmlAttribute('placeholder', $this->translator->translate('Minuty'))
			->addCondition(Form::FILLED)				
			->addRule(Form::INTEGER, $this->translator->translate('Minuty_musí_být_celé_číslo_od_0_-_999'));

	    $form->addTextArea('description',  $this->translator->translate('Podrobné_zadání:'), 100,5 )
			->setHtmlAttribute('placeholder', $this->translator->translate('Podrobný_text_zadání'));
	    $form->addTextArea('description_original',  $this->translator->translate('Zadání_od_klienta:'), 100,6 )
			->setHtmlAttribute('readonly','readonly')
			->setHtmlAttribute('placeholder', $this->translator->translate('Text_události'));

	    $arrUsers2 = $this->PartnersEventUsersManager->findAll()->select('cl_partners_event_users.cl_users_id AS cl_users_id, cl_users.name')
                                    ->where('cl_partners_event_id = ?', $tmpId)->fetchPairs('cl_users_id', 'name');

	    $arrUsers3 = $this->UserManager->getUsersInCompany($this->user->identity->cl_company_id)->select('cl_users.id AS id, cl_users.name')
                            ->where('not_active = 0')
                            ->order('name')->fetchPairs('id','name');
        //bdump($arrUsers2);
        //bdump($arrUsers3);
        $arrUsers4 = $arrUsers2 + $arrUsers3;
        //bdump($arrUsers4);
	    $form->addCheckboxList('workers', $this->translator->translate('Pracovníci:'), $arrUsers4 );
	    $form->addSubmit('send',  $this->translator->translate('Uložit'))->setHtmlAttribute('class','btn btn-sm btn-primary');
	    $form->addSubmit('back',  $this->translator->translate('Zpět'))
		    ->setHtmlAttribute('class','btn btn-sm btn-primary')
		    ->setValidationScope([])
		    ->onClick[] = array($this, 'stepBack');	    	    
	//	    ->onClick[] = callback($this, 'stepSubmit');
	    $form->onValidate[] = array($this, 'FormValidate');
	    $form->onSuccess[] = array($this, 'SubmitEditSubmitted');
	    return $form;
    }
    
    //javascript call when changing cl_partners_book_id
    public function handleRedrawPriceList2($cl_partners_book_id)
    {
		//dump($cl_partners_book_id);
		$arrUpdate = array();
		$arrUpdate['id'] = $this->id;
		$arrUpdate['cl_partners_book_id'] = ($cl_partners_book_id == '' ? NULL:$cl_partners_book_id ) ;

		//dump($arrUpdate);
		//die;
		$this->DataManager->update($arrUpdate);

		//$this['invoicelistgrid']->redrawControl('pricelist2');
    }

    public function handleGetCommission($cl_partners_book_id){
      //bdump($cl_partners_book_id);
        $arrCommission = $this->CommissionManager->findAll()->select('CONCAT(cm_number, " ", cm_title) AS text, cl_commission.id')
                    ->where('use_for_hd = 1 AND cl_status.s_fin = 0 AND cl_status.s_storno = 0 AND cl_partners_book_id = ?', $cl_partners_book_id)
            ->order('text')->fetchPairs('id','text');
        $this->sendJson(array('arrData' => $arrCommission));

    }

    public function stepBack()
    {	    
	//$this->redirect(substr($this->name,strpos($this->name, ":")+1).":default");	
	$this->redirect('default');	

    }		
    
     public function FormValidate(Form $form)
    {
	    $data=$form->values;
        /*02.12.2020 - cl_partners_book_id required and prepare data for just created partner
        */
        $data = $this->updatePartnerId($data);
        if ($data['cl_partners_book_id'] == NULL || $data['cl_partners_book_id'] == 0)
        {
            $form->addError($this->translator->translate('Partner_musí_být_vybrán'));
        }
            $this->redrawControl('content');

    }       

    public function SubmitEditSubmitted(Form $form)
    {
		$data=$form->values;	
		//bdump($data);
		//die;
		if ($form['send']->isSubmittedBy())
		{
            $dataWorkers = $data['workers'];
			unset($data['workers']);
			if (is_null($data['cl_users_id']) || empty($data['cl_users_id']))
				$data['cl_users_id'] = $this->getUser()->id;

			$userSettings = ['cl_partners_book_id' => $data['cl_partners_book_id'],
						  'cl_partners_event_type_id' => $data['cl_partners_event_type_id']
            ];
			$this->UserManager->updateUser(['id' => $data['cl_users_id'], 'event_settings' => json_encode($userSettings)]);
			//$workTime = (strtotime($data['date_to'].' '.$data['time_to']) - strtotime($data['date'].' '.$data['time']))/60;

			if ($data['cl_partners_event_id'] == '')
				$data['cl_partners_event_id'] = NULL;
			
			if ($data['work_time'] == '')
				unset($data['work_time']);

			if ($data['work_time_hours'] == '')
				unset($data['work_time_hours']);			

			if ($data['work_time_minutes'] == '')
				unset($data['work_time_minutes']);						

			
			//dump($data);
			//die;
			if (isset($data['work_time_hours']) || isset($data['work_time_minutes']))
			{
				if (!isset($data['work_time_hours']))
					$data['work_time_hours'] = 0;
				
				if (!isset($data['work_time_minutes']))
					$data['work_time_minutes'] = 0;			

				
				$data['work_time'] = ($data['work_time_hours']*60) + $data['work_time_minutes'];
			}
			

			//datum začátku práce
			if ($data['date'] == "")
				$data['date'] = NULL;
			else
				$data['date'] = date('Y-m-d H:i:s',strtotime($data['date'])); 	

			//datum dokončení
			if ($data['date_to'] == "")
			{
				//$data['date_to'] = NULL; 			
				unset($data['date_to']);
			}
			else
                        {
				$data['date_to'] = date('Y-m-d H:i:s',strtotime($data['date_to'])); 			
                        }

			//datum požadované reakce
			if ($data['date_end'] == "")
				$data['date_end'] = NULL; 			
			else		
				$data['date_end'] = date('Y-m-d H:i:s',strtotime($data['date_end'])); 			

			//datum přijetí 
			if ($data['date_rcv'] == "")
				$data['date_rcv'] = new \Nette\Utils\DateTime;
			else		
				$data['date_rcv'] = date('Y-m-d H:i:s',strtotime($data['date_rcv'])); 					

			//if (!isset($data['cl_status_id']))
			
			
			//hour tax. If is set on cl_partners_book use it, or use cl_partners_category.hour_tax 
			//only if it is main request
			if (is_null($data['cl_partners_event_id']) && isset($data['cl_partners_category_id']))
			{
				if ($tmpPartner = $this->PartnersManager->find($data['cl_partners_book_id']))
				{
					$tmpTaxes = json_decode($tmpPartner->cl_partners_category_taxes, TRUE);
					$data['hour_tax'] = 0;
					//bdump($tmpTaxes, 'taxes');
					if (!is_null($tmpTaxes))
					{
                        //bdump($data['cl_partners_event_method_id'], 'cl_partners_event_method_id');
						if ($tmpEventMethod = $this->PartnersEventMethodManager->find($data['cl_partners_event_method_id']))
						{
                            //bdump($data['cl_partners_category_id'], '$partners_category_id');
						    //bdump($tmpEventMethod, '$tmpEventMethod');
							if ($tmpEventMethod->remote == 1 ) {
                                if (isset($tmpTaxes['categremote' . $data['cl_partners_category_id']]))
                                    $data['hour_tax'] = $tmpTaxes['categremote' . $data['cl_partners_category_id']];
                            }else {
                                if (isset($tmpTaxes['categ' . $data['cl_partners_category_id']]))
                                    $data['hour_tax'] = $tmpTaxes['categ' . $data['cl_partners_category_id']];
                            }
								
						}
					}
                    //bdump($data['hour_tax'], 'hour tax');
					if ($data['hour_tax'] == 0)
					{
						if ($tmpCateg = $this->PartnersCategoryManager->find($data['cl_partners_category_id']))
						{
							if ($tmpEventMethod = $this->PartnersEventMethodManager->find($data['cl_partners_event_method_id']))
							{
								if ($tmpEventMethod->remote == 1)													
									$data['hour_tax'] = $tmpCateg->hour_tax_remote;
								else
									$data['hour_tax'] = $tmpCateg->hour_tax;
							}
						}
						else
							$data['hour_tax'] = 0;
					}

				}
			}



			
			//set status of finished if finished
			if ($data['finished'] == 1) {
                $tmpData = $this->DataManager->find($data['id']);
                if (($tmpData && $tmpData->cl_status->s_fin == 0) || is_null($tmpData->cl_status_id)) {
                    if (($nStatus = $this->StatusManager->findAll()->where('status_use = ? AND s_fin = ?', 'partners_event', 1)->fetch())
                        && $data['cl_partners_event_id'] === NULL) {
                        $data['cl_status_id'] = $nStatus->id;
                    } else {
                        //this is subrequest so no status is required
                        $data['cl_status_id'] = NULL;
                    }
                }
								

				if (!empty($data->id))
				{
					$oldVal2 = $this->PartnersEventManager->find($data->id);
					$oldVal = $oldVal2->finished;
				}else{
					$oldVal = '';
				}

				//finished subrequest must have date_to
				if (is_null($oldVal2['date_to']) && !isset($data['date_to']) )
					$data['date_to'] = new \Nette\Utils\DateTime;	
				
				if ($oldVal == 0 && $data['cl_partners_event_id'] === NULL )
				{
					//send email to costumer, but only at first time when is finished
                    //05.09.2021 - send mail if event is set public
                    if ($data['public'] == 1) {
                        $this->sendCustomerEmailClosed($data);
                    }
				}
				//03.05.2016 - packing finished
				$data['packed'] = 1;
			}else{
				//03.05.2016 - unpack because not finished
				$data['packed'] = 0;

			}
			


			//25.02.2016 - set or unset public token
			if ($data['public'])
			{
				if (!empty($data->id))
				{
					$oldVal = $this->PartnersEventManager->find($data->id)->public_token;
				}else
					$oldVal = '';

				if (empty($oldVal))
					$data['public_token'] = \Nette\Utils\Random::generate(128,'A-Za-z0-9');
				
			}else{
				$data['public_token'] = '';
			}

            if (isset($data['make_task']) && $data['make_task'] == 'on'){
                $tmpMakeTask = TRUE;
            }else{
                $tmpMakeTask = FALSE;
            }
            $data['make_task'] = 0;

			$tmpOld = $this->PartnersEventManager->find($data->id);
			if (!empty($data->id))
			{
				$this->PartnersEventManager->update($data);
				$rowId = $data->id;
			}
			else
			{
				$rowId = $this->PartnersEventManager->insert($data)->id;
			}

            //24.04.2022 - send notification email for cl_center_id
            $this->centerNotify($data, $this->numberSeries['use']);
			
			//06.04.2017 - in case of settings hd_ending == 1  end main task if all subs are finished
			if ($this->settings->hd_ending == 1 && $data['cl_partners_event_id'] !== NULL && $data['finished'] == 1)
			{
			    $oldVal = $this->PartnersEventManager->findAll()
						->where('cl_partners_event_id = ? AND finished = 0 AND id != ?', $data['cl_partners_event_id'], $data['id']);
			    //dump($oldVal->count());
			    //die;
			    if ($oldVal->count() === 0)
			    {
				$parentData =
                    ['id' => $data['cl_partners_event_id'], 'finished' => 1];
			    }else{
				$parentData =
                    ['id' => $data['cl_partners_event_id'], 'finished' => 0];
			    }
			    //dump($parentData);
			    
			    $this->PartnersEventManager->update($parentData);
			    //die;
			}			
			
			//17.2.2016 - save workers
			//$dataWorkers
			//dump($dataWorkers);
			//die;
			$existWork = $this->PartnersEventUsersManager->findBy(['cl_partners_event_id' => $rowId]);
			foreach ($existWork as $one)
			{
				//delete no longer existed workers in event
				if (!in_array($one->cl_users_id,$dataWorkers))
					$one->delete();
			}
			$existWork = $existWork->fetchPairs('id','cl_users_id');
			foreach($dataWorkers as $one)
			{
				if (!in_array($one,$existWork))
				{
					$arrWork = array();
					$arrWork['cl_partners_event_id'] = $rowId;
					$arrWork['cl_users_id'] = $one;
					$this->PartnersEventUsersManager->insert($arrWork);
					//06.04.2016 - send email to new worker
					$this->sendWorkerEmail($arrWork,$data);
				}
			}

			//if it is not main event, we must send new message to helpdesk admin
			//but only if it is new message? or always when it is saved ?... for now always
			//dump($data['cl_partners_event_id'] !== NULL);
			//die;
			$data['cl_company_id'] = $this->settings->id;
			if ($data['cl_partners_event_id'] !== NULL )
			{
				$this->DataManager->sendAdminEmail($data);
				//28.03.2017 - now we are sending notification about answer for event also to customer
                //05.09.2021 - send mail if event is set public
                if ($data['public'] == 1) {
                    $this->sendCustomerEmail($data);
                }
				
			}else{
				//send info about new main request to client
				//dump($tmpOld->cl_partners_book_id);
				//dump($data['cl_partners_book_id']);
				
				//only if there is change of cl_partners_book_id
				//27.03.2017 - this is not correct way, because cl_partners_book_id is updated just when is selected by handleChangePartner
				//the better way is to control if cl_status is new then send email to customer
				//also we can control changes in work_label, but for now only send when cl_status is s_new
				//if ($tmpOld->cl_partners_book_id !== $data['cl_partners_book_id'])
				if ($tmpStatus = $this->StatusManager->findAll()->where('status_use = ? AND s_new = ? AND id = ?','partners_event',1, $data['cl_status_id'])->fetch())
				{
				 
					//$this->sendCustomerEmailNew($data);
                    //05.09.2021 - send mail if event is set public
                    if ($data['public'] == 1) {
                        $retVal = $this->DataManager->sendCustomerEmailNew($data);
                    }
				 
				}
			}


            //28.02.2022 - TH make task
            if ($tmpMakeTask){
                $newRow = $this->TaskManager->CreateTaskFromHD($rowId);
                $this->PairedDocsManager->insertOrUpdate(['cl_company_id' => $newRow['cl_company_id'], 'cl_task_id' => $newRow['id'], 'cl_partners_event_id' => $rowId]);
                $this->flashMessage($this->translator->translate('Úkol_byl_vytvořen'),'success');
            }
                        
			//22.02.2019
			//$this->redirect(substr($this->name,strpos($this->name, ":")+1).":default");
			//$this->updateSum();

			//$this->presenter->redirect(':Application:PartnersEvent:');

			
		    //$this->redirect('default');		
		    $this->redrawControl('flash');
		    $this->redrawControl('formedit');
		    $this->redrawControl('timestamp');						
		    $this->redrawControl('items');
            $this->redrawControl('content');
		  //  $this->redirect('default');

		}
    }
    

    
    public function handleChangePartner($cl_partners_book_id,$cl_partners_event_type_id,$id2,$cl_partners_category_id)
    {
		//07.03.2017 - we must update selected partner due to incorrect fillment of cl_parnters_book selectbox 
		$this->DataManager->find($id2)->update(array('cl_partners_book_id' => $cl_partners_book_id));
		$this->cl_partners_book_id = $cl_partners_book_id;
		$this->cl_partners_event_type_id = $cl_partners_event_type_id;
		$this->event_id = $id2;
		//$this->cl_partners_category_id = $cl_partners_category_id;
		//$this->redrawControl('events-bookworkers');	
		//$this->redrawControl('events-parent-events');		
		//$this->redrawControl('events-dates');			
		
		//bookworkers
		$arrSend = [];
		$arrSend['arrData'] = $this->PartnersBookWorkersManager->findAll()->
										where('cl_partners_book_id = ?',$this->cl_partners_book_id)->
											fetchPairs('id','worker_name');

		$arrSend['def'] = 0;	
		if (count($arrSend['arrData']) > 0 && $data = $this->PartnersBookWorkersManager->find(key($arrSend['arrData'])))
		{
			$arrSend['workerinfo'] = array(
						 'worker_position' => $data->worker_position,
						 'worker_email' => $data->worker_email,
						 'worker_phone' => $data->worker_phone,
						 'worker_skype' => $data->worker_skype,
						 'worker_other' => $data->worker_other
					);
		}else
		{
			$arrSend['workerinfo'] = [];
		}
		
		$this->sendJson($arrSend); 
		
    }
	
    public function handleGetCategory($cl_partners_book_id,$cl_partners_event_method_id)
    {
		
		$this->cl_partners_book_id = $cl_partners_book_id;
		$this->cl_partners_event_method_id = $cl_partners_event_method_id;
		//$this->cl_partners_event_type_id = $cl_partners_event_type_id;
		//$this->event_id = $id2;
		//$this->cl_partners_category_id = $cl_partners_category_id;
		$arrSend = [];
		$arrSend['arrData'] = $this->getCategory();
		if ($tmpData = $this->PartnersManager->find($cl_partners_book_id))
			$arrSend['def'] = $tmpData->cl_partners_category_id;
		else
			$arrSend['def'] = 0;
		
		$this->sendJson($arrSend); 
		
    }	
	
    
    public function handleChangeEventType($cl_partners_event_type_id,$cl_partners_book_id,$id2)
    {
		$this->cl_partners_event_type_id = $cl_partners_event_type_id;
		$this->cl_partners_book_id = $cl_partners_book_id;
		$this->event_id = $id2;
		$this->redrawControl('events-bookworkers');	
		$this->redrawControl('events-parent-events');		
		$this->redrawControl('events-parent-events2');		
		$this->redrawControl('events-dates');			
    }    
	
    public function handleChangeEventMethod($cl_partners_event_method_id,$cl_partners_book_id)
    {
		$this->cl_partners_event_method_id = $cl_partners_event_method_id;
		$this->cl_partners_book_id = $cl_partners_book_id;		
		$this->sendJson($this->getCategory()); 
		//$this->redrawControl('partners_category');
		//$this->redrawControl('chosen-refresh');			
    }    	
	
    
    public function handleChangeDatercv($cl_partners_book_id,$cl_partners_category_id)
    {
		if (($dataCategory = $this->PartnersCategoryManager->find($cl_partners_category_id)) 
					&& ($data = $this->PartnersManager->find($cl_partners_book_id))
				)
		{
			if (isset($dataCategory->react_time))
				$payload = array('react_time' => $dataCategory->react_time, 
						 'category_name' => (isset($data->cl_partners_category['id']) ? $data->cl_partners_category->category_name : ''),
						 'person' => $data->person,
						 'email' => $data->email,
						 'phone' => $data->phone,
						  'url' => $this->link('//Partners:edit', array('id' =>$data->id)),
						  'url-data' => $this->link('//Partners:edit', array('id' =>$data->id, 'modal' => 1, 'roModal' => 1)),
							);
			else
				$payload = array();
		}else{
			$payload = array();
		}
		$response = new \Nette\Application\Responses\JsonResponse($payload);
		$this->sendResponse($response);
    }

	
	
	/* Email nofitication to customer about closed request
	 * 
	 */
	private function sendCustomerEmailClosed($dataSource)
	{
		$tmpEvent = $this->PartnersEventManager->find($dataSource['id']);
		//$tmpAdmin = $this->UsersManager->find($arrWork['cl_users_id']);
		if ($tmpEvent->email_rcv != '')
		{
			$tmpEmail = $tmpEvent->email_rcv;
		}else {
			$tmpEmail = $tmpEvent->cl_partners_book->email;
		}
		
		/* 07.04.2017 - if is defined center email, we are sending email to this email*/
		if ($tmpEvent->cl_partners_book->cl_center_id !== NULL)
		{
		    $tmpEmail = $tmpEvent->cl_partners_book->cl_center->email;
		}
		
		
		if ($tmpEmail != '')
		{
			if ($tmpEvent->cl_partners_book_workers_id !== NULL)
			{
				//$tmpKontakt = '<tr><td>Kontakt: </td><td>'.$tmpEvent->cl_partners_book_workers->worker_name.', Email:'. $tmpEvent->cl_partners_book_workers->worker_email.', Tel.:'.$tmpEvent->cl_partners_book_workers->worker_phone.'</td></tr>';
				if ($tmpEvent->cl_partners_book_workers->worker_email == '')
				{
					$tmpEmail = $tmpEvent->cl_partners_book->email;
				}else{
					$tmpEmail = $tmpEvent->cl_partners_book_workers->worker_email;
				}
				
				$emailTo = array(0 => $tmpEvent->cl_partners_book_workers->worker_name.' <'.$tmpEmail.'>');
			}else {
				$emailTo = array(0 => $tmpEvent->cl_partners_book->company.' <'.$tmpEmail.'>');
			}
			$data = array();
			$emails = implode(';', $emailTo);
			$data['singleEmailTo'] = $emails;

			if ($this->settings->email_income != '')
			{
                $data['singleEmailReplyTo'] = $this->settings->name.' <'.$this->settings->email_income.'>';
                $data['singleEmailFrom'] = $this->settings->name.' <'.$this->settings->email.'>';
			}else{
				$data['singleEmailFrom'] = $this->settings->name.' <'.$this->settings->email.'>';
			}


			if ($this->settings->hd6_emailing_text_id !== NULL)
			{
				$tmpEmlText = $this->EmailingTextManager->getEmailingText('','','',$this->settings->hd6_emailing_text_id);
				$data['subject'] = '['.$dataSource['event_number'].']['.$tmpEmlText['subject'].'] '.$dataSource['work_label'];
				$template = $this->createTemplate()->setFile(__DIR__.'/../../templates/Emailing/email.latte');
				$template->body = $tmpEmlText['body'];
				$data['body']	= $template;

				$emailTo = str_getcsv($data['singleEmailTo'],';');


				//send email
				$this->emailService->sendMail2($data);
				
				//save to cl_emailing
				$this->EmailingManager->insert($data);		

			}
		}
	}	
	
	
	/* Email nofitication to customer about new request
	 * 
	 */
	private function sendCustomerEmailNew($dataSource)
	{
	    //dump($dataSource);
		$tmpEvent = $this->PartnersEventManager->find($dataSource['id']);
		//$tmpAdmin = $this->UsersManager->find($arrWork['cl_users_id']);
		if ($tmpEvent->email_rcv != '')
			$tmpEmail = $tmpEvent->email_rcv;
		else 
			$tmpEmail = $tmpEvent->cl_partners_book->email;
		
		/* 07.04.2017 - if is defined center email, we are sending email to this email*/
		if ($tmpEvent->cl_partners_book->cl_center_id !== NULL)
		{
		    $tmpEmail = $tmpEvent->cl_partners_book->cl_center->email;
		}
		
		
		
		if ($tmpEmail != '')
		{
			if ($tmpEvent->cl_partners_book_workers_id !== NULL)
			{
				//$tmpKontakt = '<tr><td>Kontakt: </td><td>'.$tmpEvent->cl_partners_book_workers->worker_name.', Email:'. $tmpEvent->cl_partners_book_workers->worker_email.', Tel.:'.$tmpEvent->cl_partners_book_workers->worker_phone.'</td></tr>';
				$tmpEmail = $tmpEvent->cl_partners_book_workers->worker_email;
				$emailTo = array(0 => $tmpEvent->cl_partners_book_workers->worker_name.' <'.$tmpEmail.'>');
			}else
			{
				$emailTo = array(0 => $tmpEvent->cl_partners_book->company.' <'.$tmpEmail.'>');
			}
			if (!empty($tmpEmail)){
			    $data = array();
			    $emails = implode(';', $emailTo);
			    $data['singleEmailTo'] = $emails;

			    if ($this->settings->email_income != '') {
                    $data['singleEmailReplyTo'] = $this->settings->name.' <'.$this->settings->email_income.'>';
                    $data['singleEmailFrom'] = $this->settings->name.' <'.$this->settings->email.'>';
                }else {
                    $data['singleEmailFrom'] = $this->settings->name . ' <' . $this->settings->email . '>';
                }

			    if ($this->settings->hd1_emailing_text_id !== NULL)
			    {
				    $tmpEmlText = $this->EmailingTextManager->getEmailingText('','','',$this->settings->hd1_emailing_text_id);
				    $data['subject'] = '['.$dataSource['event_number'].']['.$tmpEmlText['subject'].'] '.$dataSource['work_label'];
				    $template = $this->createTemplate()->setFile(__DIR__.'/../../templates/Emailing/email.latte');
				    if ($tmpEvent->cl_partners_book_workers_id !== NULL)
				    {
					    $tmpKontakt = '<tr><td>Kontakt: </td><td>'.$tmpEvent->cl_partners_book_workers->worker_name.', Email:'. $tmpEvent->cl_partners_book_workers->worker_email.', Tel.:'.$tmpEvent->cl_partners_book_workers->worker_phone.'</td></tr>';
				    }else{
					    $tmpKontakt = '<tr><td>Kontakt: </td><td>'.$tmpEvent->cl_partners_book->person.', Email:'. $tmpEvent->cl_partners_book->email.', Tel.:'.$tmpEvent->cl_partners_book->phone.'</td></tr>';
				    }				
				    $template->body = $tmpEmlText['body'].
									    '<table>'.
									    '<tr><td>'.$this->translator->translate('cl_partners_book').' </td><td>'.$tmpEvent->cl_partners_book->company.'</td></tr>'.
									    $tmpKontakt.
									    '<tr><td>'.$this->translator->translate('Datum_vytvoření:').'</td><td>'.$tmpEvent->created->format('d.m.Y H:i:s').'</td></tr>'.
									    '<tr><td>'.$this->translator->translate('Důležitost:').'</td><td>'.$this->translator->translate($tmpEvent->cl_partners_category->category_name).'</td></tr>'.
									    '<tr><td>'.$this->translator->translate('Nová_zpráva:').' </td><td>work_label</td></tr>'.
									    '<tr><td>'.$this->translator->translate('Obsah:').' </td><td>'.$dataSource['description'].'</td></tr>'.
									    '</table>';				
				    $data['body']	= $template;

				    $emailTo = str_getcsv($data['singleEmailTo'],';');
				      try{
					//send email
					$this->emailService->sendMail2($data);

					//save to cl_emailing
					$this->EmailingManager->insert($data);		
					$this->flashMessage($this->translator->translate('Informace_o_novém_zápisu_byla_klientovi_byl_odeslána.'),'success');
				      }catch (\Exception $e){
					  $this->flashMessage($e->getMessage(),'danger');
				      }

			    }
			}else{
			    $this->flashMessage($this->translator->translate('Klient_nemá_zapsán_email,_zpráva_nebyla_odeslána.'),'danger');
			}
		}
	}		
	
	
	
	/* Email nofitication to helpdesk admin about new submessage
	 * 
	 */
	private function sendAdminEmail($dataSource)
	{
		//we are sending email to admin only when the submessage is saved by another user then parent owner
	    
		$tmpEvent = $this->PartnersEventManager->find($dataSource['cl_partners_event_id']);
		//bdump($dataSource['cl_partners_event_id']);
		//bdump($tmpEvent->id,'cl_partners_event.id');
		//bdump($dataSource['cl_users_id'],'dataSource[cl_users_id]');
		//bdump($this->user->identity->id);
		//if ($dataSource['cl_users_id'] != $this->user->identity->id)
		//02.03.2019 - it was wrong because datasource[cl_users_id] is in most cases same as current user
		//we have to compare with parentevent[cl_users_id]
		if ($dataSource['cl_users_id'] != $tmpEvent['cl_users_id'])
		{
		   // bdump('ano');
			//find helpdesk admin if there is not one selected at parent event
			if ($tmpEvent->cl_users_id !== NULL)
			{
				$emailTo  = $this->UsersManager->findAll()->
								where(array('id' => $tmpEvent->cl_users_id))->
								select("id, CONCAT(name,' <',email,'>') AS user")->limit(1)->fetchPairs('id','user');
			}else{
				$emailTo  = $this->UsersManager->findAll()->
							where(array('event_manager' => 1))->
							select("id, CONCAT(name,' <',email,'>') AS user")->fetchPairs('id','user');
				if (!$emailTo)
				{
					$emailTo  = $this->UsersManager->findAll()->
								limit(1)->
								select("id, CONCAT(name,' <',email,'>') AS user")->fetchPairs('id','user');									
				}
			}							
			$data = array();
			$emails = implode(';', $emailTo);
			$data['singleEmailTo'] = $emails;
			if ($this->settings->email_income != '') {
                //$data['singleEmailFrom'] = $this->settings->name . ' <' . $this->settings->email_income . '>';
                $data['singleEmailReplyTo'] = $this->settings->name.' <'.$this->settings->email_income.'>';
                $data['singleEmailFrom'] = $this->settings->name.' <'.$this->settings->email.'>';
            }else {
                $data['singleEmailFrom'] = $this->settings->name . ' <' . $this->settings->email . '>';
            }
			//bdump($data);
			if ($this->settings->hd5_emailing_text_id !== NULL)
			{
				//prepare email data
				$tmpEmlText = $this->EmailingTextManager->getEmailingText('','','',$this->settings->hd5_emailing_text_id);
				$data['subject'] = '['.$tmpEvent->event_number.']'.'['.$tmpEmlText['subject'].'] '.$dataSource['work_label'];
				$template = $this->createTemplate()->setFile(__DIR__.'/../../templates/Emailing/email.latte');
				$link = $this->link('//showBsc!', array('id' => $tmpEvent->id, 'copy' => false));
				if ($tmpEvent->cl_partners_book_workers_id !== NULL)
				{
					$tmpKontakt = '<tr><td>'.$this->translator->translate('Kontakt:').' </td><td>'.$tmpEvent->cl_partners_book_workers->worker_name.', Email:'. $tmpEvent->cl_partners_book_workers->worker_email.', Tel.:'.$tmpEvent->cl_partners_book_workers->worker_phone.'</td></tr>';
				}else{
					$tmpKontakt = '<tr><td>'.$this->translator->translate('Kontakt:').' </td><td>'.$tmpEvent->cl_partners_book->person.', Email:'. $tmpEvent->cl_partners_book->email.', Tel.:'.$tmpEvent->cl_partners_book->phone.'</td></tr>';
				}				
				if (isset($tmpEvent->cl_partners_category['id']))
				{
					$tmpCategory = '<tr><td>'.$this->translator->translate('Důležitost:').'</td><td>'.$tmpEvent->cl_partners_category->category_name.'</td></tr>';
				}
				else
				{
					$tmpCategory = '';
				}

				$template->body = $tmpEmlText['body'].
									'<table>'.
									'<tr><td>'.$this->translator->translate('cl_partners_book').' </td><td>'.$tmpEvent->cl_partners_book->company.'</td></tr>'.
									$tmpKontakt.
									$tmpCategory.
									'<tr><td>'.$this->translator->translate('Datum_vytvoření:').' </td><td>'.$dataSource['date']->format('d.m.Y H:i:s').'</td></tr>'.
									'<tr><td>'.$this->translator->translate('Zadání:').' </td><td>'.$tmpEvent->work_label.'</td></tr>'.
									'<tr><td>'.$this->translator->translate('Obsah:').' </td><td>'.$tmpEvent->description_original.'</td></tr>'.
									'<tr><td>'.$this->translator->translate('Odkaz_do_helpdesku:').'</td><td><a href="'.$link.'" title="Otevře záznam helpdesku">'.$link.'</a>'.
									'<tr><td>'.$this->translator->translate('Nová_zpráva:').' </td><td>'.$dataSource['description'].'</td></tr>'.
									'</table>';
				$data['body']	= $template;
				//$data['body'] = html_entity_decode($data['body']);		
				$emailTo = str_getcsv($data['singleEmailTo'],';');
				//try{
    				    //send email
				    $this->emailService->sendMail2($data);

				    //save to cl_emailing
				    $this->EmailingManager->insert($data);		
				//}catch (\Exception $e){
				 //   $this->flashMessage($e->getMessage(),'danger');
				//}				
			}
		}
	}
	

	/* Email nofitication to customer about subrequest
	 * 
	 */
	private function sendCustomerEmail($dataSource)
	{
	    //dump($dataSource);
		//$tmpEvent = $this->PartnersEventManager->find($dataSource['id']);
		$tmpEvent = $this->PartnersEventManager->find($dataSource['cl_partners_event_id']);
		//$tmpAdmin = $this->UsersManager->find($arrWork['cl_users_id']);
		if ($tmpEvent->email_rcv != '')
		{
			$tmpEmail = $tmpEvent->email_rcv;
		}
		else 
		{
			$tmpEmail = $tmpEvent->cl_partners_book->email;
		}
		
		/* 07.04.2017 - if is defined center email, we are sending email to this email*/
		if ($tmpEvent->cl_partners_book->cl_center_id !== NULL)
		{
		    $tmpEmail = $tmpEvent->cl_partners_book->cl_center->email;
		}

		
		if ($tmpEmail != '')
		{
			if ($tmpEvent->cl_partners_book_workers_id !== NULL)
			{
				//$tmpKontakt = '<tr><td>Kontakt: </td><td>'.$tmpEvent->cl_partners_book_workers->worker_name.', Email:'. $tmpEvent->cl_partners_book_workers->worker_email.', Tel.:'.$tmpEvent->cl_partners_book_workers->worker_phone.'</td></tr>';
				$tmpEmail = $tmpEvent->cl_partners_book_workers->worker_email;
				if ($tmpEmail != ''){
				    $emailTo = array(0 => $tmpEvent->cl_partners_book_workers->worker_name.' <'.$tmpEmail.'>');
				}else{
				    $emailTo = '';
				}
			}else
			{
				$emailTo = array(0 => $tmpEvent->cl_partners_book->company.' <'.$tmpEmail.'>');
			}
			if (!empty($emailTo)){
			    
			    $data = array();
			    $emails = implode(';', $emailTo);
			    $data['singleEmailTo'] = $emails;

			    if ($this->settings->email_income != '') {
                    //$data['singleEmailFrom'] = $this->settings->name . ' <' . $this->settings->email_income . '>';
                    $data['singleEmailReplyTo'] = $this->settings->name.' <'.$this->settings->email_income.'>';
                    $data['singleEmailFrom'] = $this->settings->name.' <'.$this->settings->email.'>';
                }else {
                    $data['singleEmailFrom'] = $this->settings->name . ' <' . $this->settings->email . '>';
                }

			    if ($this->settings->hd7_emailing_text_id !== NULL)
			    {
				    $tmpEmlText = $this->EmailingTextManager->getEmailingText('','','',$this->settings->hd7_emailing_text_id);
				    $data['subject'] = '['.$tmpEvent['event_number'].']['.$tmpEmlText['subject'].'] '.$dataSource['work_label'];
				    $template = $this->createTemplate()->setFile(__DIR__.'/../../templates/Emailing/email.latte');
				    if ($tmpEvent->cl_partners_book_workers_id !== NULL)
				    {
					    $tmpKontakt = '<tr><td>'.$this->translator->translate('Kontakt:').' </td><td>'.$tmpEvent->cl_partners_book_workers->worker_name.', Email:'. $tmpEvent->cl_partners_book_workers->worker_email.', Tel.:'.$tmpEvent->cl_partners_book_workers->worker_phone.'</td></tr>';
				    }else{
					    $tmpKontakt = '<tr><td>'.$this->translator->translate('Kontakt:').' </td><td>'.$tmpEvent->cl_partners_book->person.', Email:'. $tmpEvent->cl_partners_book->email.', Tel.:'.$tmpEvent->cl_partners_book->phone.'</td></tr>';
				    }				
				    $template->body = $tmpEmlText['body'].
									    '<table>'.
									    '<tr><td>'.$this->translator->translate('cl_partners_book').' </td><td>'.$tmpEvent->cl_partners_book->company.'</td></tr>'.
									    $tmpKontakt.
									    '<tr><td>'.$this->translator->translate('Datum_vytvoření:').' </td><td>'.$dataSource['date']->format('d.m.Y H:i:s').'</td></tr>'.
									    '<tr><td>'.$this->translator->translate('Důležitost:').'</td><td>'.$tmpEvent->cl_partners_category->category_name.'</td></tr>'.
									    '<tr><td>'.$this->translator->translate('Zadání:').' </td><td>'.$tmpEvent->work_label.'</td></tr>'.
										'<tr><td>'.$this->translator->translate('Obsah:').' </td><td>'.$tmpEvent->description_original.'</td></tr>'.
									    '<tr><td>'.$this->translator->translate('Nová_odpověď:').' </td><td>'.$dataSource['description'].'</td></tr>'.
									    '</table>';				
				    $data['body']	= $template;

				    $emailTo = str_getcsv($data['singleEmailTo'],';');
				    try{
					//send email
					$this->emailService->sendMail2($data);

					//save to cl_emailing
					$this->EmailingManager->insert($data);		
					$this->flashMessage($this->translator->translate('Informace_o_nové_odpovědi_byla_klientovi_odeslána.'),'success');
				    }catch (\Exception $e){
					  $this->flashMessage($e->getMessage(),'danger');
				    }
			    }
			}else{
			    $this->flashMessage($this->translator->translate('Pracovník_nemá_zadanou_emailovou_adresu.'),'danger');
			}
		}
	}		
	
	
	
	private function sendWorkerEmail($arrWork,$dataSource)
	{
		$tmpEvent = $this->PartnersEventManager->find($arrWork['cl_partners_event_id']);
		$tmpWorker = $this->UserManager->getUserById($arrWork['cl_users_id']);
		$data = [];
		if ($tmpWorker->email != ''){
		    if ($tmpWorker->email2 != ''){
                $data['singleEmailTo'] = $tmpWorker->name.' <'.$tmpWorker->email2.'>';
            }else{
                $data['singleEmailTo'] = $tmpWorker->name.' <'.$tmpWorker->email.'>';
            }

		}else{
		    $data['singleEmailTo'] = '';
		}
		if (!empty($data['singleEmailTo'])){
		    if ($this->settings->email_income != '') {
                //$data['singleEmailFrom'] = $this->settings->name . ' <' . $this->settings->email_income . '>';
                $data['singleEmailReplyTo'] = $this->settings->name.' <'.$this->settings->email_income.'>';
                $data['singleEmailFrom'] = $this->settings->name.' <'.$this->settings->email.'>';
            }else {
                $data['singleEmailFrom'] = $this->settings->name . ' <' . $this->settings->email . '>';
            }



		    if ($this->settings->hd4_emailing_text_id !== NULL)
		    {
			    $tmpEmlText = $this->EmailingTextManager->getEmailingText('','','',$this->settings->hd4_emailing_text_id);
			    $data['subject'] = '['.$dataSource['event_number'].']['.$tmpEmlText['subject'].'] '.$dataSource['work_label'];
			    $template = $this->createTemplate()->setFile(__DIR__.'/../../templates/Emailing/email.latte');
			    $link = $this->link('//showBsc!', ['id' => $tmpEvent->id, 'copy' => false]);
			    if ($tmpEvent->cl_partners_book_workers_id !== NULL)
			    {
				    $tmpKontakt = '<tr><td>'.$this->translator->translate('Kontakt:').' </td><td>'.$tmpEvent->cl_partners_book_workers->worker_name.', Email:'. $tmpEvent->cl_partners_book_workers->worker_email.', Tel.:'.$tmpEvent->cl_partners_book_workers->worker_phone.'</td></tr>';
			    }else{
				    $tmpKontakt = '<tr><td>'.$this->translator->translate('Kontakt:').' </td><td>'.$tmpEvent->cl_partners_book->person.', Email:'. $tmpEvent->cl_partners_book->email.', Tel.:'.$tmpEvent->cl_partners_book->phone.'</td></tr>';
			    }							
			    $template->body = $tmpEmlText['body'].
								    '<table>'.
								    '<tr><td>'.$this->translator->translate('cl_partners_book').'</td><td>'.$tmpEvent->cl_partners_book->company.'</td></tr>'.
								    $tmpKontakt.
								    '<tr><td>'.$this->translator->translate('Datum_přijetí:').'</td><td>'.$tmpEvent['date_rcv']->format('d.m.Y H:i:s').'</td></tr>'.
								    '<tr><td>'.$this->translator->translate('Důležitost:').'</td><td>'.$tmpEvent->cl_partners_category->category_name.'</td></tr>'.
								    '<tr><td>'.$this->translator->translate('Odkaz_do_helpdesku:').'</td><td><a href="'.$link.'" title="Otevře záznam helpdesku">'.$link.'</a>'.
								    '<tr><td>'.$this->translator->translate('Zpráva:').'</td><td>'.$dataSource['description'].'</td></tr>'.
								    '</table>';
			    $data['body']	= $template;

			    $data['body'] = html_entity_decode($data['body']);		

			    //$data['subject']	= '['.$dataSource['event_number'].'] '.$dataSource['work_label'];
			    //$data['body']		= $dataSource['description'];		

			    $emailTo = str_getcsv($data['singleEmailTo'],';');

			    try{
				//send email
				$this->emailService->sendMail2($data);

				//save to cl_emailing
				$this->EmailingManager->insert($data);		
			    }catch (\Exception $e){
				    $this->flashMessage($e->getMessage(),'warning');
			    }
		    }
		}else{
		    $this->flashMessage($this->translator->translate('Klient_nemá_zadánu_emailovou_adresu,_email_nebyl_odeslán.'), 'danger');
		}
	}	
	
	
	
	
    /*
     * nová podřízená událost
     */
    public function handleNewSub($cl_partners_event_id,$cl_partners_book_id,$cl_partners_event_type_id,$cl_partners_event_method_id)
    {
		$this->redirect('newSub',array($cl_partners_event_id,$cl_partners_book_id,$cl_partners_event_type_id,$cl_partners_event_method_id));	

    }
	
    public function handleReport($index = 0)
    {
            $this->rptIndex = $index;
            $this->reportModalShow = TRUE;
            $this->redrawControl('baselistArea');
            $this->redrawControl('reportModal');
            $this->redrawControl('reportHandler');
    }
	
	

	protected function createComponentReportClients($name)
    {	
		$form = new Form($this, $name);
	    //$form->setMethod('POST');
	    $form->addHidden('id',NULL);
	    
		$now = new \Nette\Utils\DateTime;
		$form->addText('date_from', $this->translator->translate('Dokončeno_od:'), 0, 16)
			->setDefaultValue('01.'.$now->format('m.Y'))
			->setHtmlAttribute('placeholder',$this->translator->translate('Datum_začátek'));

		$form->addText('date_to', $this->translator->translate('Dokončeno_do:'), 0, 16)
			->setDefaultValue($now->format('d.m.Y'))
			->setHtmlAttribute('placeholder','Datum konec');

		$tmpArrPartners = $this->PartnersManager->findAll()->order('company')->fetchPairs('id','company');
		$form->addMultiSelect('cl_partners_book',$this->translator->translate('applicationModule.hdpresenter.report_clients') . ':', $tmpArrPartners)
				->setHtmlAttribute('multiple','multiple')
				->setHtmlAttribute('placeholder', $this->translator->translate('applicationModule.hdpresenter.report_clientsPh'));

		$tmpArrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id','name');
		$form->addMultiSelect('cl_center_id', $this->translator->translate('Střediska:'), $tmpArrCenter)
				->setHtmlAttribute('multiple','multiple')
				->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_střediska_pro_tisk'));

		$form->addCheckbox('detail',$this->translator->translate('Detailní_rozpis'))
			->setDefaultValue(true);		

		$form->addSubmit('save', $this->translator->translate('Tisk'))->setHtmlAttribute('class','btn btn-sm btn-primary');
        $form->addSubmit('save_csv', 'Export CSV')->setHtmlAttribute('class','btn btn-sm btn-primary');
        $form->addSubmit('save_xls', 'Export XLS')->setHtmlAttribute('class','btn btn-sm btn-primary');
	    $form->addSubmit('back', $this->translator->translate('Návrat'))
		    ->setHtmlAttribute('class','btn btn-sm btn-primary')
		    ->setValidationScope([])
		    ->onClick[] = array($this, 'stepBackReportClients');	    	    
		$form->onSuccess[] = array($this, 'SubmitReportClientsSubmitted');
		//$form->getElementPrototype()->target = '_blank';
		return $form;
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
		$data=$form->values;	
		//dump(count($data['cl_partners_book']));
		//die;
		if ($form['save']->isSubmittedBy() || $form['save_csv']->isSubmittedBy() || $form['save_xls']->isSubmittedBy()) {
            if ($data['date_to'] == "")
                $data['date_to'] = NULL;
            else {
                //$tmpDate = new \Nette\Utils\DateTime;
                //$tmpDate = $tmpDate->setTimestamp(strtotime($data['date_to']));
                //date('Y-m-d H:i:s',strtotime($data['date_to']));
                $data['date_to'] = date('Y-m-d H:i:s', strtotime($data['date_to']) + 86400 - 10);
            }

            if ($data['date_from'] == "")
                $data['date_from'] = NULL;
            else
                $data['date_from'] = date('Y-m-d H:i:s', strtotime($data['date_from']));


            $dataReport = $this->PartnersEventManager->findAll()->
                                    where('date_to >= ? AND date_to <= ? AND cl_partners_event.cl_partners_event_id IS NULL AND finished = 1', $data['date_from'], $data['date_to']);
            if (count($data['cl_partners_book']) == 0) {
                $dataReport = $dataReport->order('cl_partners_book.company ASC, date_rcv ASC, cl_partners_category_id ASC');
            } elseif (count($data['cl_partners_book']) == 1) {
                $dataReport = $dataReport->where(array('cl_partners_book_id' => $data['cl_partners_book']))->
                                                order('cl_center.name ASC, cl_partners_book.company ASC, date_rcv ASC, cl_partners_category_id ASC');
            } else {
                $dataReport = $dataReport->where(array('cl_partners_book_id' => $data['cl_partners_book']))->
                                                order('cl_partners_book.company ASC, date_rcv ASC, cl_partners_category_id ASC');
            }

            if (count($data['cl_center_id']) > 0) {
                $dataReport = $dataReport->where(array('cl_partners_event.cl_center_id' => $data['cl_center_id']));
            }


            $tmpAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;
            $tmpTitle = $this->translator->translate('applicationModule.hdpresenter.report_clients_title_report');

            $dataOther = array();
            $dataSettings = $data;
            $dataOther['dataSettingsPartners'] = $this->PartnersManager->findAll()->
            where(array('cl_partners_book.id' => $data['cl_partners_book']))->
            order('company');
            //$dataOther['dataSettingsStatus']	= $this->StatusManager->findAll()->where(array('id' =>$data['cl_status_id']))->order('status_name');
            $dataOther['dataSettingsCenter'] = $this->CenterManager->findAll()->where(array('id' => $data['cl_center_id']))->order('name');
            //$dataOther['dataSettingsUsers']		= $this->UserManager->getAll()->where(array('id' =>$data['cl_users_id']))->order('name');

            if ($form['save']->isSubmittedBy())
            {
			$template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/Helpdesk/ReportClients.latte', $dataOther, $dataSettings, $this->translator->translate('Přehled_helpdesku_podle_klientů'));


			$tmpDate1 = new \DateTime($data['date_from']);
			$tmpDate2 = new \DateTime($data['date_to']);
			$this->pdfCreate($template, $this->translator->translate('Přehled_helpdesku_podle_klientů ').date_format($tmpDate1,'d.m.Y').' - '.date_format($tmpDate2,'d.m.Y'));

            }elseif ($form['save_csv']->isSubmittedBy() || $form['save_xls']->isSubmittedBy())
            {
                if ( $dataReport->count() > 0)
                {
                    $arrFr = array('cl_partners_book_id' => array($this->translator->translate('Zákazník'), 'cl_partners_book','company'),
                                    'cl_partners_book_workers_id' => array($this->translator->translate('Pracovník_zákazníka'), 'cl_partners_book_workers','worker_name'),
                                    'cl_users_id' => array($this->translator->translate('Pracovník'), 'cl_users','name'),
                                    'cl_partners_event_type_id' => array($this->translator->translate('Typ_události'), 'cl_partners_event_type','type_name'),
                                    'cl_status_id' => array($this->translator->translate('Stav'), 'cl_status','status_name'),
                                    'cl_partners_category_id' => array($this->translator->translate('Kategorie'), 'cl_partners_category','category_name'),
                                    'cl_center_id' => array($this->translator->translate('Středisko'), 'cl_center','name'),
                                    'cl_partners_event_method_id' => array($this->translator->translate('Způsob_řešení'), 'cl_partners_event_method','method_name'),
                                    'cl_commission_id' => array($this->translator->translate('Zakázka'), 'cl_commission','cm_number'),
                                    'cl_invoice_id' => array($this->translator->translate('Faktura'), 'cl_invoice','inv_number'),
                                    'hour_tax' => array($this->translator->translate('Sazba')),
                                    'work_label' => array($this->translator->translate('Zadání')),
                                    'description' => array($this->translator->translate('Podrobné_zadání')),
                                    'description_original' => array($this->translator->translate('Podrobné_od_klienta')),
                                    'date_rcv' => array($this->translator->translate('Datum_přijetí')),
                                    'date_end' => array($this->translator->translate('Reakce_do')),
                                    'finished' => array($this->translator->translate('Hotovo')),
                                    'work_time' => array($this->translator->translate('Trvání_(minuty)')),
                                    'date_to'=> array($this->translator->translate('Konec_události')),
                                    'create_by' => array($this->translator->translate('Vytvořil')),
                                    'created' => array($this->translator->translate('Datum_a_čas_vzniku')),
                                    'id'  => array('ID'),
                    );

                    $datanew = array();
                    foreach($dataReport as $key => $oneRow){
                        foreach($oneRow as $keyC => $oneCol) {
                            if (key_exists($keyC, $arrFr)){
                                if (count($arrFr[$keyC]) > 1) {
                                    $datanew[$key][$keyC] = $oneRow[$arrFr[$keyC][1]][$arrFr[$keyC][2]];
                                }else{
                                    $datanew[$key][$keyC] = $oneCol;
                                }
                                //rename
                                $datanew[$key][$arrFr[$keyC][0]] = $datanew[$key][$keyC];
                                unset($datanew[$key][$keyC]);
                            }

                        }
                        $tmpDetail = "";
                        foreach($oneRow->related('cl_partners_event') as $keyCh => $oneCh){
                            $nl = preg_replace('#<br\s*/?>#i', "\n ", $oneCh['description']);
                            $tmpDetail .= strip_tags($nl);
                        }
                        $datanew[$key]['detail'] = $tmpDetail;
                    }
                    $filename = $this->translator->translate("Přehled_helpdesku_podle_klientů");
                    if ($form['save_csv']->isSubmittedBy()) {
                        $this->sendResponse(new \CsvResponse\NCsvResponse($datanew, $filename . "-" . date('Ymd-Hi') . ".csv", true, ';', 'UTF-8'));
                    }elseif($form['save_xls']->isSubmittedBy()){
                        $this->sendResponse(new \XlsResponse\NXlsResponse($datanew, $filename."-" .date('Ymd-Hi').".xls",true));
                    }

                }else{
                    $this->flashMessage($this->translator->translate('Data_nebyly_do_CSV_uloženy._Zadaným_podmínkám_nevyhověl_žádný_záznam.'), 'danger');
                    $this->redirect('default');
                }
            }
				
			
			
		}
	}
	
	
	protected function createComponentReportWorkers($name)
    {	
		$form = new Form($this, $name);
	    //$form->setMethod('POST');
	    $form->addHidden('id',NULL);
	    
		$now = new \Nette\Utils\DateTime;
		$form->addText('date_from', 'Dokončeno od:', 0, 16)
			->setDefaultValue('01.'.$now->format('m.Y'))
			->setHtmlAttribute('placeholder','Datum začátek');

		$form->addText('date_to', 'Dokončeno do:', 0, 16)
			->setDefaultValue($now->format('d.m.Y'))
			->setHtmlAttribute('placeholder','Datum konec');
		
		$tmpArrUsers = $this->UsersManager->findAll()->order('name')->fetchPairs('id','name');
		$form->addMultiSelect('cl_partners_event_users','Pracovníci:', $tmpArrUsers)
				->setHtmlAttribute('multiple','multiple')
				->setHtmlAttribute('placeholder','Vyberte pracovníka');	

		$tmpArrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id','name');
		$form->addMultiSelect('cl_center_id', 'Střediska:', $tmpArrCenter)
				->setHtmlAttribute('multiple','multiple')
				->setHtmlAttribute('placeholder', 'Vyberte střediska pro tisk');		

		$form->addSubmit('save', 'Tisk')->setHtmlAttribute('class','btn btn-sm btn-primary');
		
	    $form->addSubmit('back', 'Návrat')
		    ->setHtmlAttribute('class','btn btn-sm btn-primary')
		    ->setValidationScope([])
		    ->onClick[] = array($this, 'stepBackReportWorkers');	    	    
		$form->onSuccess[] = array($this, 'SubmitReportWorkersSubmitted');
		//$form->getElementPrototype()->target = '_blank';
		return $form;
    }

    public function stepBackReportWorkers()
    {	    
		$this->rptIndex = 0;
		$this->reportModalShow = FALSE;
		$this->redrawControl('baselistArea');
		$this->redrawControl('reportModal');
		$this->redrawControl('reportHandler');
    }		

    public function SubmitReportWorkersSubmitted(Form $form)
    {
		$data=$form->values;	
		if ($form['save']->isSubmittedBy())
		{    
			if ($data['date_to'] == "")
				$data['date_to'] = NULL; 			
			else
			{
				$data['date_to'] = date('Y-m-d H:i:s',strtotime($data['date_to']) + 86400 - 10);
			}

			if ($data['date_from'] == "")
				$data['date_from'] = NULL; 			
			else
				$data['date_from'] = date('Y-m-d H:i:s',strtotime($data['date_from'])); 						

			if (count($data['cl_partners_event_users']) == 0)
			{			
				$dataReport = $this->PartnersEventManager->findAll()->
									where('date_to >= ? AND date_to <= ? AND cl_partners_event.cl_partners_event_id IS NOT NULL AND finished = 1', $data['date_from'], $data['date_to'])->
									order('cl_users.name ASC, cl_users_role_id ASC, date_to ASC');
			}else
			{
				$dataReport = $this->PartnersEventManager->findAll()->
									where('date_to >= ? AND date_to <= ? AND cl_partners_event.cl_partners_event_id IS NOT NULL AND finished = 1', $data['date_from'], $data['date_to'])->
									where(array('cl_partners_event.cl_users_id' =>  $data['cl_partners_event_users']))->
									order('cl_users.name ASC, cl_users_role_id ASC, date_to ASC');				
			}
			if (count($data['cl_center_id']) > 0)
			{
			    $dataReport = $dataReport->where(array('cl_partners_event.cl_center_id' =>  $data['cl_center_id']));				
			}				
			//$tmpAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;	
			//$tmpTitle = 'Helpdesk za období dle pracovníků';
			//$template = $this->createTemplate()->setFile( __DIR__.'/../templates/Helpdesk/ReportWorkers.latte');
			//$template->data = $dataReport;  
			//$template->dataSettings = $data;
			//$template->dataSettingsUsers = $this->UsersManager->findAll()->where(array('id' =>$data['cl_partners_event_users']))->order('name');			
			
			$dataOther = array();
			$dataSettings = $data;
			//$dataOther['dataSettingsPartners']	= $this->PartnersManager->findAll()->
								    //where(array('cl_partners_book.id' =>$data['cl_partners_book']))->
								    //order('company');
			//$dataOther['dataSettingsStatus']	= $this->StatusManager->findAll()->where(array('id' =>$data['cl_status_id']))->order('status_name');			
			$dataOther['dataSettingsCenter']	= $this->CenterManager->findAll()->where(array('id' =>$data['cl_center_id']))->order('name');	
			$dataOther['dataSettingsUsers']		= $this->UserManager->getAll()->where(array('id' =>$data['cl_partners_event_users']))->order('name');			
			
			$template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/Helpdesk/ReportWorkers.latte', $dataOther, $dataSettings, 'Helpdesk za období dle pracovníků');
			$tmpDate1 = new \DateTime($data['date_from']);
			$tmpDate2 = new \DateTime($data['date_to']);
			$this->pdfCreate($template, 'Helpdesk za období dle pracovníků '.date_format($tmpDate1,'d.m.Y').' - '.date_format($tmpDate2,'d.m.Y'));				
			
			
			//foreach($template->dataSettingsUsers as $one)
				//dump($one->name);
			//die;
	
			
			
		}
	}	
	
  
    public function handleGetWorker($cl_partners_book_workers_id)
    {
		if ($data = $this->PartnersBookWorkersManager->find($cl_partners_book_workers_id))
		{
			$payload = array(
					 'worker_position' => $data->worker_position,
					 'worker_email' => $data->worker_email,
					 'worker_phone' => $data->worker_phone,
					 'worker_skype' => $data->worker_skype,
					 'worker_other' => $data->worker_other
				);
		}else{
			$payload = array();
		}
		$response = new \Nette\Application\Responses\JsonResponse($payload);
		$this->sendResponse($response);
    }
	

    public function handleGetBranch($cl_partners_book_id)
    {
	$arrSend = [];
	$arrSend['arrData'] = $this->PartnersBranchManager->findAll()->
							where('cl_partners_book_id = ?', $cl_partners_book_id)->
							select('id,b_name AS name')->fetchPairs('id','name');
	$this->sendJson($arrSend); 		
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
    
    public function handleShowTextsUse()
    {
	//bdump('ted');
	$this->redrawControl('textUseControl');
	$this->redrawControl('textsUse');	
	$this->pairedDocsShow = TRUE;
	$this->showModal('textsUseModal');

	//$this->redrawControl('contents');
    }        
    
    
    public function afterDataSaveListGrid($dataId, $name = NULL)
    {
        parent::afterDataSaveListGrid($dataId,$name);
        //23.2.2019 - there must be worktime solution
        $tmpData = $this->DataManager->find($dataId);
        if ($tmpData)
        {
            $arrData = [];
            $arrData['id'] = $dataId;
            $arrData['cl_partners_event_id'] = $tmpData->cl_partners_event_id;
            $arrData['work_time'] = ($tmpData->work_time_hours*60) + $tmpData->work_time_minutes;
            $this->DataManager->update($arrData);

            //06.04.2017 - in case of settings hd_ending == 1  end main task if all subs are finished
            if ($this->settings->hd_ending == 1 && $tmpData['cl_partners_event_id'] !== NULL && $tmpData['finished'] == 1)
            {
                $oldVal = $this->PartnersEventManager->findAll()
                            ->where('cl_partners_event.cl_partners_event_id = ? AND finished = 0 AND cl_partners_event.id != ?', $tmpData['cl_partners_event_id'], $tmpData['id']);

                if ($oldVal->count() === 0)
                {
                    $parentData = ['id' => $tmpData['cl_partners_event_id'], 'finished' => 1];
                }else{
                    $parentData = ['id' => $tmpData['cl_partners_event_id'], 'finished' => 0];
                }

                $this->PartnersEventManager->update($parentData);
                //die;
            }

            //06.04.2021 - create or update commission_work if is commission link enabled and active
            if (!is_null($tmpData->cl_partners_event['cl_commission_id']) && $tmpData->cl_partners_event->cl_commission['use_for_hd'] == 1  ){
                if ($tmpData['finished'] == 1) {
                    //06.04.2021 - mark cl_commission_task as finished
                    if (!is_null($tmpData['cl_commission_task_id'])){
                        $tmpTask = $this->CommissionTaskManager->find($tmpData['cl_commission_task_id']);
                        if ($tmpTask){
                            $tmpTask->update(array('id' => $tmpTask['id'], 'done' => 1));
                        }
                    }
                    $lastOrder = $this->CommissionWorkManager->findAll()->where('cl_commission_id = ?', $tmpData->cl_partners_event['cl_commission_id'])->max('item_order');
                    $newItem['cl_partners_event_id']    = $tmpData['id'];
                    $newItem['cl_commission_id']        = $tmpData->cl_partners_event['cl_commission_id'];
                    $newItem['item_order']              = $lastOrder + 1;
                    $newItem['work_label']              = "[" . $tmpData->cl_partners_event['event_number'] . "] " . $tmpData->cl_partners_event['work_label'] . " [" . $tmpData->cl_partners_event_type['type_name'] . "]";
                    $newItem['work_time']               = round($arrData['work_time'] / 60, 2);
                    $newItem['work_rate']               = $tmpData->cl_partners_event['hour_tax'];
                    $newItem['work_date_s']             = $tmpData['date'];
                    $newItem['note']                    = '[Úkol] ' . $tmpTask['name'] . PHP_EOL . '[Poznámka] ' . $tmpData['add_text'];
                    $newItem['work_time_s']             = date_format($tmpData['date'], 'H:i');
                    $newItem['work_date_e']             = $tmpData['date']->modifyClone('+' . $arrData['work_time'] . ' minute');
                    $newItem['work_time_e']             = date_format($newItem['work_date_e'], 'H:i');
                    $newItem['cl_users_id']             = $tmpData['cl_users_id'];
                    $newItem['cl_commission_task_id']   = $tmpData['cl_commission_task_id'];
                    if (is_null($tmpData['cl_commission_work_id'])){
                        $newRow = $this->CommissionWorkManager->insert($newItem);
                        $tmpData->update(array('cl_commission_work_id' => $newRow['id']));
                    }else{
                        $newItem['id'] = $tmpData['cl_commission_work_id'];
                        $this->CommissionWorkManager->update($newItem);
                    }

                }else{
                    if (!is_null($tmpData['cl_commission_work_id'])) {
                        $this->CommissionWorkManager->delete($tmpData['cl_commission_work_id']);
                    }
                }


            }


            $this->sendAdminEmail($tmpData);
            //28.03.2017 - now we are sending notification about answer for event also to costumer
            //05.09.2021 - send mail if event is set public
            if ($tmpData['public'] == 1) {
                $this->sendCustomerEmail($tmpData);
            }
        }

    }
    
    
    public function beforeCopy($data) {
        parent::beforeCopy($data);
        $tmpNow = new \Nette\Utils\DateTime;
        $data['date_rcv'] = $tmpNow;
        $data['date_to'] = NULL;
        $data['date_end'] = NULL;
        $data['cl_commission_id'] = NULL;
        $data['cl_commission_task_id'] = NULL;
        $data['finished'] = 0;
        return $data;
    }
    
    public function afterCopy($newId,$oldId) {
        //01.03.2019 - we have to copy child records
        $tmpEvents = $this->DataManager->findAll()->where('cl_partners_event_id = ?', $oldId);
        foreach ($tmpEvents as $key => $one){
            $data = $one->toArray();
            $tmpNow = new \Nette\Utils\DateTime;
            unset($data['id']);
            unset($data['cl_commission_id']);
            $data['date_rcv'] = $tmpNow;
            $data['date'] = NULL;
            $data['date_to'] = NULL;
            $data['date_end'] = NULL;
            $data['finished'] = 0;
            $data['cl_partners_event_id'] = $newId;
            $this->DataManager->insert($data);
        }
        return TRUE;
    }

    public function handleNew($data = '', $defData)
    {
        if (empty($data)){
            $data = $this->numberSeries['use'];
        }
        if (intval($data) > 0){
            $this->numberSeries['cl_number_series_id'] = $data;
            $data = '';
        }else{
            $this->numberSeries['use'] = $data;
        }
        parent::handleNew($data,$defData);
    }


}

