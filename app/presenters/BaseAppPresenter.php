<?php
namespace App\Presenters;

use App\Controls;
use App\Model\ChatManager;
use Netpromotion\Profiler\Profiler;
use Nette\Application\UI\Form;
use Nette\Mail\SmtpMailer;
use Nette\Utils\Image;
use Nette\Utils\Strings;
use Tracy\Debugger;
use Halasz;
use Nette\Caching;

abstract class BaseAppPresenter extends \App\Presenters\BasePresenter
{
    public $settings,$tmpLogo,$tmpStamp,$eventNewId, $formName, $chatEnabled = FALSE;
    public $chatMode = 'top'; // top or card

    public $globalSaveForms = FALSE;


    public $report = array(),  $rptIndex,$reportModalShow = FALSE, $forceRO = FALSE,$bscEnabled = TRUE;

    public $enableAutoPaging = true;
    
    public $unMoHandler = array(); //was NULL //universalModalHandler will content id_modal = ID of modal window, status = TRUE/FALSE for visible/hidden


    /**
     * @var string
     */
    public $partnerComment = '', $partnerCommentName = '';


    /**
    * @inject
    * @var \App\Model\UserManager
    */
    public $UserManager;      
	


    /**
    * @inject
    * @var \App\Model\DocumentsManager
    */
    public $DocumentsManager;


    /**
     * @inject
     * @var \App\Model\PartnersEventManager
     */
    public $PartnersEventManager;
	
    /**
    * @inject
    * @var \App\Model\CompaniesManager
    */
    public $CompaniesManager;

    /**
     * @inject
     * @var \App\Model\CompanyBranchUsersManager
     */
    public $CompanyBranchUsersManager;

    /**
     * @inject
     * @var \App\Model\CompanyBranchManager
     */
    public $CompanyBranchManager;


    /**
    * @inject
    * @var \App\Model\MessagesManager
    */
    public $MessagesManager;          
    
    /**
    * @inject
    * @var \App\Model\PartnersManager
    */
    public $PartnersManager;              


    /**
    * @inject
    * @var \App\Model\NumberSeriesManager
    */
    public $NumberSeriesManager;
    
    /**
    * @inject
    * @var \App\Model\StatusManager
    */
    public $StatusManager;   

    /**
    * @inject
    * @var \App\Model\PartnersEventTypeManager
    */
    public $PartnersEventTypeManager;    
    
    /**
    * @inject
    * @var \App\Model\CommissionManager
    */
    public $CommissionManager;    
    
    /**
    * @inject
    * @var \App\Model\CommissionWorkManager
    */
    public $CommissionWorkManager;        

    
    /**
    * @inject
    * @var \App\Model\EmailingTextManager
    */
    public $EmailingTextManager;        

    /**
    * @inject
    * @var \App\Model\FilesManager
    */
    public $FilesManager;

    /**
     * @inject
     * @var \App\Model\FilesAgreementsManager
     */
    public $FilesAgreementsManager;


	
	/**
	 * @inject
	 * @var \App\Model\ReportManager
	 */
	public $ReportManager;

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
     * @var \App\Model\PartnersAccountManager
     */
    public $PartnersAccountManager;


	/**
	 * @inject
	 * @var \App\Model\WorkplacesManager
	 */
	public $WorkplacesManager;

    /**
     * @inject
     * @var \App\Model\ArchiveManager
     */
    public $ArchiveManager;

	public $supportForm;
	
	public $cache;
	
	
    public function __construct(\Nette\DI\Container $container, Halasz\Support\Support\ISupportFormFactory $SupportFormFactory, Caching\IStorage $storage) {
	    parent::__construct($container);
		//, Caching\IStorage $storage
       // //profiler::start();
       // $this->parameters = $container->getParameters();
        $this->supportForm = $SupportFormFactory;
		//$journal = new \Nette\Caching\Storages\SQLiteJournal(__DIR__ . '/../../temp/journal');
		//$storage->clean() = new \Caching\IStorage(__DIR__ . '/../../temp/', $journal);
		$this->cache = $storage;
        //$this->cache = new Caching\Cache($storage);
        ////profiler::finish('construct base app');
    }
	
	/*public function __construct(\Nette\DI\Container $container, Halasz\Support\Support\ISupportFormFactory $SupportFormFactory) {
		parent::__construct();
		$this->parameters = $container->getParameters();
		$this->supportForm = $SupportFormFactory;
		//$this->cache = new Caching\Cache($storage);
	}*/

    /**
     * @param string $newLocale
     */
    public function handleChangeLocale(string $newLocale): void
    {
        (empty($newLocale)) ? $lang = 'cs' : $lang = $newLocale;
        //bdump($lang);
        $tmpUser = $this->UserManager->getUserById($this->getUser()->getId());
        $tmpUser->update(array('lang' => $lang));
        $this->getUser()->getIdentity()->lang = $lang;
        $this->translatorSessionResolver->setLocale($lang);
        $this->redirect('this');
    }
	
    protected function startup()
    {
        parent::startup();

        //bdump($this->presenter->getAction());

        if ($this->getUser()->isInRole('admin'))
        {
            \Tracy\Debugger::$editor = 'phpstorm://open?file=%file&line=%line';
            //\Tracy\Debugger::$productionMode = TRUE;
            \Tracy\Debugger::$strictMode = FALSE;
            \Tracy\Debugger::$dumpTheme = 'dark';
//                Debugger::enable(Debugger::DEVELOPMENT);
        }else{
            \Tracy\Debugger::$productionMode = TRUE;
            \Tracy\Debugger::$strictMode = FALSE;
        }


        //dump($this);
        if ($this->presenter->getAction() == "downloadLastPdf")
        {
          //  $this->session->destroy();
            return;
        }
        if(true === Debugger::$productionMode){
            Debugger::$showBar = false;
        }
        if ($this->getUser()->isLoggedIn() && !is_null($this->user->getIdentity())) {
            //$this->translatorSessionResolver->setLocale($this->user->getIdentity()->lang);
            $this->locale = $this->user->getIdentity()->lang;
        }

 //       \Tracy\Debugger::$productionMode = TRUE;
//        \Tracy\Debugger::$strictMode = FALSE;
//        dump($this->name);
//        die;
        if ($this->user->isInRole('b2b') && $this->presenter->name != "Application:Documents"){
            $this->redirect(':B2B:Mainpage:default');
        }

        //if ($this->parameters['maintenance_mode'] == 'SET_ON'  && !$this->parameters['debugMode']){
        if ($this->getMntMode() == 'SET_ON'){
            $this->redirect(':Login:Homepage:maintenance');
        }else{
            if ($this->presenter->name != 'Application:HelpdeskReview' && $this->presenter->name != 'Application:HelpdeskPublic'
                    && $this->presenter->name != 'Application:StoreReview' && $this->presenter->name != 'Application:SaleReview'
                    && $this->presenter->name != 'Application:Documents')
            {
                if (!$this->getUser()->isLoggedIn() || !$this->WorkplacesManager->checkCurrent()) {
                    $this->disallowAjax();
                    $this->redirect(':Login:Homepage:default', array('backlink' => $this->storeRequest()));
                }else{
					
                    if (!$this->UserManager->isAllowed($this->name, 'enabled', $this->getUser()->id))
                    {
                       $this->template->setFile(__DIR__ . '/../templates/denny.latte');
                    }

                    if ($this->presenter->name != 'Application:Homepage')
                    {
                        $isEnabled = $this->UserManager->trfModuleEnable($this->name, $this->getUser()->id);
                        if (!$isEnabled['result'])
                        {
                            //dump($this->name);
                            //die;
                            $this->redirect(':Application:Homepage:changeTariff', array('module_name' => $this->name, 'message' => $isEnabled['reason']));
                        }
                    }

                    $this->settings = $this->CompaniesManager->getTable()->fetch();
                }
            }else{
                if ($this->getUser()->isLoggedIn()) {
                    $this->settings = $this->CompaniesManager->getTable()->fetch();
                }
            }
        }
		

    }




    protected function createComponentSupportForm()
    {
        $translator = $this->translatorMain;
        $translator->setPrefix(array('supportForm' => 'supportForm'));
        $supportForm = $this->supportForm->create($translator);
        return $supportForm;
    }

    protected function createComponentMessages()
     {
        //$translator =clone $this->translator;
	    return new Controls\MessagesControl($this->translator,
                            $this->MessagesManager->findAll()->where('cl_users_id = ? AND closed = ?', $this->getUser()->id, 0)->order('created DESC'));
     }


    public function beforeRender() {
      //  \Tracy\Debugger::$editor = 'phpstorm://open?file=%file&line=%line';
      //  \Tracy\Debugger::$productionMode = FALSE;
      //  \Tracy\Debugger::$strictMode = FALSE;
      //  \Tracy\Debugger::$dumpTheme = 'dark';
        //dump($this);
        //die;

        $this->template->partnerComment     = $this->partnerComment;
        $this->template->partnerCommentName = $this->partnerCommentName;

        //$this->template->partnerComment     = '';
        //$this->template->partnerCommentName = '';
        $this->template->commentWindow      = str_getcsv($this->getUser()->getIdentity()->comment_window,';');
        $arrDefaultReport                   = json_decode($this->getUser()->getIdentity()->default_report ?? '', true );
        $this->template->defaultReport      = $arrDefaultReport[$this->presenter->name] ?? '0';



        $this->template->globalSaveForms    = $this->globalSaveForms;
        $this->template->chatEnabled        = $this->chatEnabled;
        $this->template->chatMode           = $this->chatMode;
        $this->template->enableAutoPaging   = $this->enableAutoPaging;
       // bdump($this->unMoHandler,'unMoHandler');
        $this->template->bscEnabled         = $this->bscEnabled;
		$this->template->unMoHandler        = $this->unMoHandler;
       // bdump($this->pdfPreviewData,'pdf preview data');
		$this->template->pdfPreviewData     = $this->pdfPreviewData;
        $this->template->pdfPreviewId       = $this->pdfPreviewId;

        $this->template->locale             = $this->translatorMain->getLocale();
        //$this->template->locale = 'cs';
		$this->template->logged             = $this->getUser()->isLoggedIn();
		$this->template->modal              = FALSE;
		$this->template->lattePath          = $this->getLattePath();
        if ($this->getUser()->isLoggedIn())
		{
			$this->template->userCompany = $this->CompaniesManager->find($this->UserManager->getCompany($this->getUser()->id));
			$this->template->bkg_color_hex = $this->template->userCompany['color_hex'];
			$this->template->userIsCompaniesManager = $this->UserManager->getUserById($this->user->getId())->companies_manager;
            if ($this->CompaniesManager->companyAdmin($this->user->getIdentity()->cl_company_id, $this->user->getId()))
            {
                $result = TRUE;
            }else{
                $result = FALSE;
            }
            $this->template->userIsCompaniesAdmin = $result;

            $tmpBranches = json_decode($this->user->getIdentity()->company_branches, TRUE);
            if (is_null($tmpBranches))
                $tmpBranches = array();

            $this->template->companyBranches = $this->CompanyBranchManager->findAll()->where('id IN ?',$tmpBranches)->order('name');
            $this->template->activeBranch = $this->CompanyBranchManager->find($this->user->getIdentity()->cl_company_branch_id);

            $this->template->archivesMenu = $this->ArchiveManager->getActiveArchives();

            $mySection = $this->session->getSection('company');
            //if (is_null($mySection['dbName'])) {
            //    $mySection['dbName'] = "dbCurrent";
            //}
            if (!isset($mySection['dbName']) || is_null($mySection['dbName'])) {
                //$mySection['dbName'] = 'dbCurrent';
                $dbName = 'dbCurrent';
            }else{
                $dbName = $mySection['dbName'];
            }
            $GLOBALS['DBNAME'] = $dbName;
           // $this->session->close();

            $this->template->activeArchive = $dbName;
            //bdump($this->dbName, 'dbName');

            $this->template->userCanWrite   =  $this->isAllowed($this->name,'write');
            $this->template->userCanErase   =  $this->isAllowed($this->name,'erase');
            $this->template->userCanEdit    =  $this->isAllowed($this->name,'edit');
            $this->template->userCanReport  =  $this->isAllowed($this->name,'report');

		}else{
            $this->template->userCanWrite   = false;
            $this->template->userCanErase   = false;
            $this->template->userCanEdit    = false;
            $this->template->userCanReport  = false;
            $this->template->bkg_color_hex = '';
        }
                    
		$this->template->eventsModalShow = $this->eventsModalShow;	
		$this->template->eventsCount = $this->PartnersEventManager->findBy(array('cl_status.s_new' => 1))->count();
		$this->template->eventsCount2 = $this->PartnersEventManager->findAll()->where('cl_partners_event.cl_partners_event_id IS NOT NULL AND finished = 0')->count();
		$this->template->commissionCount = $this->CommissionManager->findBy(array('cl_status.s_new' => 1))->count();                
		//$this->template->eventNewId = $this->eventNewId;

		// \Tracy\Debugger::$productionMode = FALSE;

        //bdump($this->action);
        //bdump($this->template->formName);

        //if ($this->action != 'edit')



		$this->template->reportModalShow = $this->reportModalShow;
		$this->template->rptIndex = $this->rptIndex;
		$this->template->report = $this->report;
		$this->template->version = $this->parameters['app_version'];
		$this->template->debugMode = $this->parameters['debugMode'];
		$user_id = $this->getUser()->id;
		//$this->template->enabledModules = array('helpdesk' => $this->UserManager->trfModuleEnable('helpdesk', $user_id),
		//										'commission' => $this->UserManager->trfModuleEnable('commission', $user_id));
		//\Nette\Diagnostics\Debugger::$bar = FALSE;
        if($this->isAjax()){
            $this->template->formName = $this->formName;
            $title = $this->formName . " .::Trynx::.";
            if (isset($this->template->activeBranch)){
                $title = $this->template->activeBranch->name . ' ' . $title;
            }

                $this->payload->title = $title;
        }

    }

    public function handleAgree($id){
        $ret = $this->FilesAgreementsManager->makeAgree($id, $this->user->getId());
        if ($ret){
            $this->flashMessage('Dokument byl odsouhlasen', 'success');
        }
        $this->hideModal('pdfModal');
        $this->redrawControl('flash');
        $this->redrawControl('filesAgreement');
    }

    public function handleShowAgreement(){
        if ($this->user->isLoggedIn()) {
            $tmpAgreeFiles = $this->FilesAgreementsManager->findAll()
                ->where('cl_files_agreements.cl_users_id = ? AND cl_files.after_login = 1  AND dtm_agreement IS NULL', $this->user->getId())
                ->order('cl_files.created ASC')
                ->fetch();
            if ($tmpAgreeFiles) {
                //bdump($tmpAgreeFiles);
                $this->template->showAgreement = TRUE;
                $this->getPDF($tmpAgreeFiles['cl_files_id']);
            } else {
            }
        }
        $this->redrawControl('flash');

    }

    public function getPDF($id)
    {
        $type = 1; //1 for PDF
        if ($tmpFile = $this->FilesManager->find($id))
        {
            $dataFolder = $this->CompaniesManager->getDataFolder($this->presenter->getUser()->getIdentity()->cl_company_id);
            $subFolder = $this->ArraysManager->getSubFolder($tmpFile);
            $fileName = $dataFolder . '/' . $subFolder . '/' . $tmpFile->file_name;
            if (is_file($fileName)) {
                $this->pdfPreviewData = file_get_contents($fileName);
                $this->pdfPreviewId = $id;
            }else {
                $this->pdfPreviewData = NULL;
            }
        }
        $this->redrawControl('pdfPreview');
        $this->showModal('pdfModal');
    }
    
    public function handleLogout()
    {
       // $mySection = $this->session->getSection('company');
       // $mySection['cl_company_id'] = NULL;
	    $this->user->logout(TRUE);
	    $this->redirect(':Login:Homepage:default');
    }


    public function handleChangeBranch($id)
    {
        $allowedBranches = json_decode($this->user->getIdentity()->company_branches, TRUE);
        //bdump($allowedBranches);
        //bdump(array_search($id, $allowedBranches));
        //bdump(array_search(10, $allowedBranches));
        if ( array_search($id, $allowedBranches) != FALSE)
        {
            $this->UserManager->getUserById($this->user->getIdentity()->id)->update(array('cl_company_branch_id' => $id));
            $this->user->getIdentity()->cl_company_branch_id = $id;
            $this->flashMessage('Pobočka byla změněna.', 'success');
            //$this->redrawControl('content');
            $this->redirect('default',  array('id' => NULL));
        }else{
            $this->flashMessage('Pobočka nebyla změněna. Nemáte oprávnění', 'danger');
            $this->redrawControl('content');
        }
    }

    public function handleChangeArchive($dbName){
        $mySection = $this->session->getSection('company');
        $mySection['dbName'] = $dbName;
        $this->user->getIdentity()->db_active = $dbName;
        $this->UserManager->getUserById($this->getUser()->id)->update(array('db_active' => $dbName));
        if ($dbName == 'dbCurrent'){
            $this->flashMessage('Pracujete s aktuálními daty', 'success');
        }else{
            $this->flashMessage('Pozor!! Pracujete s archivem ' . substr($dbName,2), 'danger');
        }
        //$this->session->close();
        $this->redirect(':Application:Homepage:default');
    }


    public function handleNewPartnerUrl()
    {
        
    }
    
    protected function createComponentSelectCompany($name)
    {	
            $form = new Form($this, $name);
	    //$form->setMethod('POST');
	    $userId = $this->getUser()->id;
	    $arrCompanies = $this->UserManager->getUserCompanies($userId);
	    $form->addSelect('cl_company_id', "Firma:",$arrCompanies)
		   ->setDefaultValue($this->UserManager->getCompany($userId)->id);

            $form->addSubmit('send', 'Vybrat')->setHtmlAttribute('class','btn btn-primary');
	    $form->addButton('back', 'Zpět')
		    ->setHtmlAttribute('class','btn btn-default')
		    ->setHtmlAttribute('data-dismiss','modal');	    	    
            $form->onSuccess[] = $this->SubmitSelectSubmitted;
            return $form;
    }    
    
    public function SubmitSelectSubmitted(Form $form)
    {
	$data=$form->values;
        if ($form['send']->isSubmittedBy())
	{
	    unset($data['back']);
	    unset($data['send']);
	    $data['id'] = $this->getUser()->id;
	    $this->UserManager->updateUser($data);
	}
	$this->flashMessage('Firma byla změněna.', 'success');
	$this->redirect('default');	
    }
    
    public function getStatusName($status)
    {
		return $this->ArraysManager->getStatusName($status);
    }
	
    public function getStatusAll()
    {
        return $this->ArraysManager->getStatusAll();
    }
	
	public function getReportName($name)
	{
		return $this->ArraysManager->getReportName($name);
	}
	
	public function getReportAll()
	{
		return $this->ArraysManager->getReportAll();
	}
	
	public function getReportFileName($name)
	{
		return $this->ArraysManager->getReportFileName($name);
	}
	
	public function getReportFileAll()
	{
		return $this->ArraysManager->getReportFileAll();
	}
	
	public function getInvoiceTypeName($type)
    {

		return $this->ArraysManager->getInvoiceTypeName($type);
    }    
	
    public function getInvoiceTypes()
    {
		return $this->ArraysManager->getInvoiceTypes();
    }
	
	
    public function getPriorityName($type)
    {
		return $this->ArraysManager->getPriorityName($type);
    }    
	
    public function getPriority()
    {
        return $this->ArraysManager->getPriorty();
    }

    public function getPaymentTypeAppName($type)
    {
        return $this->ArraysManager->getPaymentTypeAppName($type);
    }

    public function getPaymentTypesApp()
    {
        return $this->ArraysManager->getPaymentTypesApp();
    }


    public function getCountriesCodes() {    
        return $this->ArraysManager->getCountriesCodes();
    }
    
    public function getCountries() {
        return $this->ArraysManager->getCountries();
    }

    public function getSaleTypeName($type)
    {
        return $this->ArraysManager->getSaleTypeName($type);
    }

     public function handleSearch($term,$acSource)
    {
	$result = $this->DataManager->findAll()
			->select($acSource." AS source1, ".$acSource." AS source2")
			->where('UPPER('.$acSource.') LIKE ?','%'.strtoupper($term).'%')
			->order($acSource)->fetchPairs('source1','source2');
/*	switch($acSource)
	{
	    case 'cl_partners_book.company':
		$result = $this->DataManager->findAll()
				->select("cl_partners_book.company AS company, cl_partners_book.company AS company2")
				->where('UPPER(cl_partners_book.company) LIKE ?','%'.strtoupper($term).'%')
				->order('cl_partners_book.company')->fetchPairs('company','company2');
		break;	    	    
	    case 'cl_partners_book.city':
		$result = $this->DataManager->findAll()
				->select("city AS city, city AS city2")
				->where('UPPER(city) LIKE ?','%'.strtoupper($term).'%')
				->order('city')->fetchPairs('city','city2');
		break;	    
	    case 'cl_zip_codes':
		$result = $this->ZipCodesManager->findAll()->where('cl_countries_acronym = ?','CZ')
				->select("nazpost AS nazpost, CONCAT(nazpost,' (',zip,')') AS nazpost2")
				->where('UPPER(nazpost) LIKE ?','%'.strtoupper($term).'%')
				->order('nazpost')->fetchPairs('nazpost2','nazpost');
		break;
	    case 'cl_regions.region_name':
		$result = $this->RegionsManager->findAll()
				->select("region_name AS region_name, region_name AS region_name2")
				->where('UPPER(region_name) LIKE ?','%'.strtoupper($term).'%')
				->order('region_name')->fetchPairs('region_name','region_name2');
		break;	    
	    case 'cl_users.name':
		$result = $this->UserManager->getAll()
				->select("name AS name, name AS name2")
				->where('UPPER(name) LIKE ? AND :cl_access_company.cl_company_id =  ?','%'.strtoupper($term).'%', $this->UserManager->getCompany($this->user->getId()))
				->order('name')->fetchPairs('name','name2');
		break;	    	    
	    default:
		$result=array();
		break;
	}*/
	$i=0;
        $result2=array();
	foreach ($result as $key => $one)
	{
	    $result2[$i]['label'] = $key;
	    $result2[$i]['value'] = $one;
	    $i++;
	}

	$result2 = json_encode($result2);
	echo($result2);
	$this->terminate();
    }   
    
    
     public function handleSearchIco($ico)
    {
	if ($result = $this->DataManager->findAll()->where('ico = ?',$ico)->fetch())
	    $result = FALSE;
	else
	    $result = TRUE;
	
	//dump($result);
	echo(json_encode($result));
	$this->terminate();
    }       
    
    /** returns company picture of given type
     * 0 = logo, 1 = stamp
     * if is stamp required we must look to cl_users.picture_stamp
     * @param type $type
     */
    public function actionGetCompanyPicture($type= 0 ,$user_id = NULL)
	{
	    $row = $this->CompaniesManager->getTable()->fetch();
	    if ($type == 0){
		$img = $row['picture_logo'];
	    }

	    if ($type == 1){
		if ($user_id == NULL)
		{
		    $user_id = $this->user->getId();
		}
		$rowUser = $this->UserManager->getUserById($user_id);		
		if ($rowUser['picture_stamp'] != '')
		{
		    $img = $rowUser['picture_stamp'];
		}else{
		    $img = $row['picture_stamp'];
		}
	    }
	    
	    if ($img != '')
	    {
		$file= __DIR__ . "/../../data/pictures/" .$img;
		if (is_file($file))
		{
		    $image = Image::fromFile($file);
		    $image->send(Image::JPEG);
		}

	    }

	    $this->terminate();		

	}    
	


    

    public function handleShowEventModal()
    {
	//$data = $this->getNumberSeries($data);
	/*if there is defined number series, we must use it
	 * 
	 */
	$defValues = [];
	$nSeries = $this->NumberSeriesManager->getNewNumber('partners_event');
	$defValues['cl_number_series_id'] = $nSeries['id'];
	$defValues['event_number'] = $nSeries['number'];
	$defValues['date'] = new \Nette\Utils\DateTime;
	$defValues['date_to'] = new \Nette\Utils\DateTime;
	$defValues['date_end'] = new \Nette\Utils\DateTime;	
	$tmpStatus = 'partners_event';
	if ($nStatus= $this->StatusManager->findAll()->where('status_use = ? AND s_new = ?',$tmpStatus,1)->fetch())
	    $defValues['cl_status_id'] = $nStatus->id;

	if ($tmpDef = $this->PartnersEventTypeManager->findAll()->where('default_event')->fetch())
	    $tmpDefId = $tmpDef->id;
	else
	    $tmpDefId = NULL;

	$defValues['cl_partners_event_type_id'] = $tmpDefId;

	
	$row=$this->PartnersEventManager->insert($defValues);
	//$this->redirect('edit',array('id' => $row->id,'copy'=>FALSE,'filterValue'=> '', 'filterString' => ''));

	$this->eventsModalShow = TRUE;
	$this->eventNewId = $row->id;
	$this['events']->setEventId($row->id);	
	$this->redrawControl('eventsEdit');
    }	
	
	/* prepare company logo to tmp folder for next use typicaly in mpdf
	 *
	 */
	public function tmpLogo()
	{
		$tmpDir = __DIR__ . "/../../www/images/tmp/";
		$fileName = "";
		if (!is_dir($tmpDir))
			mkdir ($tmpDir);

		if ($this->settings->picture_logo != '')
		{
			$srcLogo  = __DIR__ . "/../../data/pictures/" .$this->settings->picture_logo;
			if (file_exists($srcLogo))
			{
				$this->tmpLogo  = $tmpDir.$this->settings->picture_logo;
				$fileName = $this->settings->picture_logo;
				copy($srcLogo, $this->tmpLogo);
                //$fi
			}
			else{
				$this->tmpLogo  = NULL;
			}
		}else{
			$this->tmpLogo  = NULL;
		}	
		return $fileName;
	}

	/* prepare company stamp to tmp folder for next use typicaly in mpdf
	 *
	 */	
	public function tmpStamp()
	{
		$tmpDir = __DIR__ . "/../../www/images/tmp/";
		$fileName = "";
		if (!is_dir($tmpDir))
			mkdir ($tmpDir);
		
		
		$user_id = $this->user->getId();
		$rowUser = $this->UserManager->getUserById($user_id);		
		if ($rowUser['picture_stamp'] != '')
		{
		    $img = $rowUser['picture_stamp'];
		}else{
		    $img = $this->settings->picture_stamp;
		}
		
		
		if ($img != '')
		{
			$srcStamp  = __DIR__ . "/../../data/pictures/" .$img;
			if (file_exists($srcStamp))
			{
				$this->tmpStamp  = $tmpDir.$img;
				$fileName = $img;
				copy($srcStamp, $this->tmpStamp);		
			}else{
				$this->tmpStamp  = NULL;		
			}
		}  else {
			$this->tmpStamp  = NULL;
		}
		
		return $fileName;		
	}

	public function getStamp()
    {
       return $this->CompaniesManager->getStamp();
    }

    public function getLogo()
    {
        return $this->CompaniesManager->getLogo();
    }

	public function GetQr($stringToEncode)
	{
		return $this->qrService->getQrImage($stringToEncode);
	}
	
	/** returns user picture 
     * @param type $type
     */
    public function actionGetUserImage($id)
	{
	    $row = $this->UserManager->getUserById($id);

		$img = $row['user_image'];
	    
	    if ($img != '')
	    {
			$file= __DIR__ . "/../../data/pictures/" .$img;
			if (is_file($file))
			{
				$image = Image::fromFile($file);
				$image->send(Image::JPEG);
				
			}
	    }
	    $this->terminate();
	}
	
	
 /**
     * called by ajax select2 component for selecting items from pricelist
     * @param type $q
     * @param type $page
     */
    public function handleGetPricelist($q,$page = 1)
    {
            if (strlen($q) == 0)
            {
                $result = ['items' => [],
                    'total_count' => 0];
                echo(json_encode($result));
                die;
            }
            $partners = $this->DataManager->find($this->id);
            //$numberDecimal = $this->dataSource->cl_company->des_cena;
            if ($this->settings->price_e_type == 1 && $this->settings->platce_dph == 1)
            {
                    $priceName = 'cl_pricelist.price_vat';
            }else{
                    $priceName = 'cl_pricelist.price';
            }
            //dump($partners->toArray());
            //if ( isset($partners->cl_partners_book_id) && $partners->cl_partners_book_id != NULL && $partners->cl_partners_book->pricelist_partner && $this->presenter->name != 'Application:Order')
            $arrQuant = NULL;
            if (array_key_exists('cl_partners_book_id', $partners->toArray()) && $partners->cl_partners_book_id != NULL && $partners->cl_partners_book->pricelist_partner && $this->presenter->name != 'Application:Order')
            {
                //(SELECT SUM(cl_pricelist:cl_store_move.s_in - cl_pricelist:cl_store_move.s_out) FROM cl_store_move WHERE cl_pricelist:cl_store_move.cl_pricelist_id = cl_pricelist_partner.cl_pricelist_id) AS quantity2')
                  /*  $arrPriceListCustom = $this->PriceListPartnerManager->findAll()
                                    ->select('cl_pricelist_partner.cl_pricelist_id AS id, identification, item_label, ean_code, cl_pricelist_partner.price, cl_pricelist_partner.price_vat, cl_pricelist_partner.vat, cl_pricelist.unit, cl_currencies.currency_name,
                                     (SELECT SUM(cl_store_move.s_in - cl_store_move.s_out) FROM cl_store_move WHERE cl_store_move.cl_pricelist_id = cl_pricelist_partner.cl_pricelist_id) AS quantity2')
                                    ->where('cl_pricelist_partner.cl_partners_book_id = ? ', $partners->cl_partners_book_id)
                                    ->where('cl_pricelist.not_active = 0 AND (cl_pricelist.item_label LIKE ? OR cl_pricelist.ean_code LIKE ? OR cl_pricelist.order_code LIKE ? OR cl_pricelist.identification LIKE ?)', '%'.$q.'%', '%'.$q.'%', '%'.$q.'%', '%'.$q.'%')
                                    ->group('cl_pricelist_partner.cl_pricelist_id');
*/
                    $arrPriceListCustom = $this->PriceListManager->findAll()
                                            ->select(':cl_pricelist_partner.cl_pricelist_id AS id, identification, item_label, ean_code, :cl_pricelist_partner.price, :cl_pricelist_partner.price_vat, :cl_pricelist_partner.vat, cl_pricelist.unit, cl_currencies.currency_name,
                                                        0 AS quantity2')
                                            ->where(':cl_pricelist_partner.cl_partners_book_id = ? ', $partners->cl_partners_book_id)
                                            ->where('cl_pricelist.not_active = 0 AND (item_label LIKE ? OR ean_code LIKE ? OR order_code LIKE ? OR identification LIKE ?)', '%'.$q.'%', '%'.$q.'%', '%'.$q.'%', '%'.$q.'%')
                                            ->group('cl_pricelist.id');                                    


                    $tmpArrPricelist = $arrPriceListCustom->fetchPairs('id');
                    $arrQuant = $this->StoreMoveManager->findAll()->select('cl_pricelist_id AS id, SUM(s_in - s_out) AS quantity2')
                                            ->where('cl_pricelist_id IN ?', $tmpArrPricelist)
                                            ->group('cl_store_move.cl_pricelist_id')
                                            ->fetchPairs('id', 'quantity2');

              //                       ->select('cl_pricelist.id,CONCAT(cl_pricelist.identification," ",cl_pricelist.item_label,?,FORMAT(cl_pricelist_partner.price,cl_company.des_cena)," ",cl_currencies.currency_name,?,FORMAT(cl_pricelist.quantity,cl_company.des_mj)," ",cl_pricelist.unit) AS name',' ',' / ')->order('name')->fetchPairs('id','name');
            }else{
                $arrPriceListCustom = false;
            }
            //else {
                    //$tmpBranchId = $this->user->getIdentity()->cl_company_branch_id;
                    //$tmpBranchId = $this->CompanyBranchUsersManager->getBranchForUser($this->getUser()->id);
                    $tmpBranchId = $this->user->getIdentity()->cl_company_branch_id;
                    $tmpStorageId = NULL;
                    //bdump($tmpBranchId,'branchId');
                    if (!is_null($tmpBranchId)) {
                    	$tmpBranch = $this->CompanyBranchManager->find($tmpBranchId);
                        $tmpStorageId = $tmpBranch->cl_storage_id;
						$tmpPricelistGroup = $tmpBranch->cl_pricelist_group_id;
                        
                        //(SELECT SUM(cl_store.quantity) FROM cl_store WHERE cl_store.cl_pricelist_id = ?cl_pricelist.id HAVING cl_store.cl_storage_id = ?  ) AS quantity2', $tmpStorageId

                        $arrPriceList = $this->PriceListManager->findAll()
															->select('cl_pricelist.id, identification, item_label, ean_code, price, price_vat, vat, unit, cl_currencies.currency_name, 0 AS quantity2')
															->where('cl_pricelist.not_active = 0 AND (item_label LIKE ? OR ean_code LIKE ? OR order_code LIKE ? OR identification LIKE ?)', '%'.$q.'%', '%'.$q.'%', '%'.$q.'%', '%'.$q.'%');
															
                        if (!is_null($tmpPricelistGroup)){
							$arrPriceList = $arrPriceList->where('cl_pricelist.cl_pricelist_group_id = ?', $tmpPricelistGroup);
						}
                        
                        $arrPriceList = $arrPriceList->group('cl_pricelist.id, identification, item_label, ean_code');
                        
                        $tmpArrPricelist = $arrPriceList->fetchPairs('id');
                        if (count($tmpArrPricelist) > 0) {
/*                            $arrQuant = $this->StoreManager->findAll()->select('cl_pricelist_id AS id, SUM(quantity) AS quantity2')
															->where('cl_pricelist_id IN ? AND cl_storage_id = ?', $tmpArrPricelist, $tmpStorageId)
															->fetchPairs('id', 'quantity2');*/
                            $arrQuant = $this->StoreMoveManager->findAll()->select('cl_pricelist_id AS id, SUM(s_in - s_out) AS quantity2')
                                ->where('cl_pricelist_id IN ? AND cl_storage_id = ?', $tmpArrPricelist, $tmpStorageId)
                                ->group('cl_store_move.cl_pricelist_id')
                                ->fetchPairs('id', 'quantity2');

                        }else{
                            $arrQuant = [];

                        }
                    }else{
/*                        $arrPriceList = $this->PriceListManager->findAll()
															->select('cl_pricelist.*, cl_pricelist.quantity AS quantity2,  cl_currencies.currency_name')
															->where('cl_pricelist.not_active = 0 AND (item_label LIKE ? OR ean_code LIKE ? OR order_code LIKE ? OR identification LIKE ?)', '%'.$q.'%', '%'.$q.'%', '%'.$q.'%', '%'.$q.'%');
*/

                        $arrPriceList = $this->PriceListManager->findAll()
                            ->select('cl_pricelist.*, cl_currencies.currency_name,
                            (SELECT SUM(:cl_store_move.s_in - :cl_store_move.s_out) FROM cl_store_move WHERE :cl_store_move.cl_pricelist_id = cl_pricelist.id) AS quantity2')
                            ->where('cl_pricelist.not_active = 0 AND (item_label LIKE ? OR ean_code LIKE ? OR order_code LIKE ? OR identification LIKE ?)', '%'.$q.'%', '%'.$q.'%', '%'.$q.'%', '%'.$q.'%')
                        ->group('cl_pricelist.id');

                       
                    }

                  //                   ->select('cl_pricelist.id,CONCAT(identification," ",item_label,?,FORMAT('.$priceName.',cl_company.des_cena)," ",cl_currencies.currency_name,?,FORMAT(quantity,cl_company.des_mj)," ",unit) AS name',' ',' / ')->order('name')->fetchPairs('id','name');		
           // }
        

        $tmpRet = [];
        $offset = ($page - 1) * 30;
        $resultCount = 0;
        if (!$arrPriceListCustom) {
            $resultCount = $arrPriceList->count();
            $result = $arrPriceList->order('cl_pricelist.quantity DESC,cl_pricelist.item_label ASC')
                                    ->limit(30, $offset);
        }
        //$tmpRet = $this->resultToArr($tmpRet, $result, $arrQuant);

        if ( $arrPriceListCustom) {
            $resultCountCustom = $arrPriceListCustom->count();
            $result = $arrPriceListCustom->order('cl_pricelist.quantity DESC,cl_pricelist.item_label ASC')
                   ->limit(30, $offset);
        }else{
            $resultCountCustom = 0;
        }
        $tmpRet = $this->resultToArr($tmpRet, $result, $arrQuant);

        //dump($result->fetchAll());
        //dump($tmpRet);
        $tmpRetNew = [];
        foreach($tmpRet as $key => $one){
            $tmpRetNew[] = $one;
        }
        $result = [ 'items' => $tmpRetNew,
                    'total_count' => $resultCount + $resultCountCustom];
        //dump($result);
        echo(json_encode($result));
        die;
       // $this->terminate();
    }            

    private function resultToArr($tmpRet, $result, $arrQuant)
    {
       // bdump($result->count(),'ttttt');
        foreach ($result as $key => $one)
        {
            //bdump($one);
            if (array_search('cl_pricelist_id', $one->toArray(), TRUE))
            {
                $tmpRet[$one->cl_pricelist_id] = array(  'id' => $one->cl_pricelist_id,
                    'text' => $one->cl_pricelist->identification.' '.$one->cl_pricelist->item_label ,
                    'item_label' => $one->cl_pricelist->item_label,
                    'identification' => $one->cl_pricelist->identification,
                    'ean_code' => $one->cl_pricelist->ean_code,
                    'price' => round($one->price,2),
                    'price_vat' => round($one->price_vat,2),
                    'vat' => $one->vat,
                    'quantity' => $one->quantity2,
                    'unit' => $one->cl_pricelist->unit,
                    'currency_name' => $one->cl_currencies->currency_name);

            }else{
                if (isset($arrQuant[$key]))
                {
                    $quant2 = $arrQuant[$key];
                }else{
                    $quant2 = $one->quantity2;
                }

                $tmpRet[$key] = array(  'id' => $key,
                    'text' => $one->identification.' '.$one->item_label ,
                    'item_label' => $one->item_label,
                    'identification' => $one->identification,
                    'ean_code' => $one->ean_code,
                    'price' => round($one->price,2),
                    'price_vat' => round($one->price_vat,2),
                    'vat' => $one->vat,
                    'quantity' => $quant2,
                    'unit' => $one->unit,
                    'currency_name' => $one->currency_name);
            }
        }
        return $tmpRet;
    }
        
    /*public function handleGetFile($id)
    {
	if ($file = $this->FilesManager->findAllTotal()->where('cl_company_id =? AND id = ?', $this->cl_company_id, $id)->fetch())
	{
	    $fileSend = __DIR__."/../../../../data/files/".$file->file_name;
	    $this->presenter->sendResponse(new Nette\Application\Responses\FileResponse($fileSend, $file->label_name, $file->mime_type));
	    //, 'contenttype'
	    //$this->
	    
	}
    } */


    public function handleGetFile($id)
    {
        if ($file = $this->FilesManager->findAllTotal()->where('cl_company_id =? AND id = ?', $this->settings->id, $id)->fetch())
        {
            if ($file->new_place == 0) {
                $fileSend = __DIR__ . "/../../../data/files/" . $file->file_name;
            }else{
                $dataFolder = $this->CompaniesManager->getDataFolder($this->settings->id);
                $subFolder = $this->ArraysManager->getSubFolder($file);
                $fileSend =  $dataFolder . '/' . $subFolder . '/' . $file->file_name;
            }
            if (file_exists($fileSend)) {
                $this->presenter->sendResponse(new \Nette\Application\Responses\FileResponse($fileSend, $file->label_name, $file->mime_type));
            }

        }
    }


    public function showModal($id_modal)
    {
        $this->unMoHandler['status'] = TRUE;
        $this->unMoHandler['id_modal'] = $id_modal;
        $this->redrawControl('unMoHandler');
    }
	
    public function hideModal($id_modal)
    {
        $this->unMoHandler['status'] = FALSE;
        $this->unMoHandler['id_modal'] = $id_modal;
        $this->redrawControl('unMoHandler');
    }
    
    /*20.12.2018 - return own names for description fields
     * 
     */
    public function getOwnNames()
    {
        $arrRet = json_decode($this->settings->own_names, true);
        //bdump($arrRet);
        return $arrRet;
    }


	
	public function getStoragePlaceName($arrData){
		$strPlace = $this->StoragePlacesManager->getStoragePlaceName($arrData);
		return ($strPlace);
	}

    /**06.07.2018 - TH
     * called by ajax select2 component for selecting partners from partners book
     * @param type $q
     * @param type $page
     */
    public function handleGetPartners($q, $page = 1)
    {
        $q2 = '%' . $q . '%';
        $resultCount = $this->PartnersManager->findAll()->where('company LIKE ? OR person LIKE ? OR :cl_partners_book_workers.worker_name LIKE ? OR city LIKE ? OR ico LIKE ?', $q2, $q2, $q2, $q2, $q2)->count();
        $offset = ($page - 1) * 30;

        $result = $this->PartnersManager->findAll()->where('company LIKE ? OR person LIKE ? OR :cl_partners_book_workers.worker_name LIKE ? OR city LIKE ? OR ico LIKE ?', $q2, $q2, $q2, $q2, $q2)
            ->order('company')
            ->limit(30, $offset)
            ->fetchAll();
        //->select('DISTINCT cl_partners_book.id AS m_id,cl_partners_book.company,:cl_partners_branch.id AS b_id,:cl_partners_branch.b_name')
        //            ->fetchPairs('id','company');
        $tmpRet = array();
        foreach ($result as $key => $one) {
            //bdump($one->m_id.' '.$one->company.' '.$one->b_name.' '.$one->b_id);
            //$tmpRet[] = array('id' => $one->m_id.'-'.$one->b_id, 'text' => $one->company.' '.$one->b_name, 'branch_id' => $one->b_id);
            $tmpRet[] = array('id' => $one->id, 'text' => $one->company . ' ' .  $one->partner_code );
        }
        $result = array('items' => $tmpRet,
            'total_count' => $resultCount);
        echo(json_encode($result));
        $this->terminate();
    }


    /*06.07.2018 - save selected partner to session selectbox. It's needed for successfull send form through validation
     * TH
     */
    public function handleUpdatePartnerInForm($cl_partners_book_id)
    {
        $tmpData = $this->PartnersManager->findAll()->where('id = ?', $cl_partners_book_id)->limit(1);
        $arrPartners = $tmpData->fetchPairs('id', 'company');
        $mySection = $this->getSession('selectbox'); // returns SessionSection with given name
        $mySection->cl_partners_book_id_values = $arrPartners;
        $mySection->setExpiration("60 minutes");

        foreach ($tmpData as $one) {
            $cl_users_id = $one->cl_users_id;

            $this->payload->cl_users_id = $cl_users_id;
            //bdump($tmpData);
            $this->PartnersManager->useHeaderFooter($this->id, $one->id, $this->DataManager);

        }


        if ($this->mainTableName != 'cl_delivery_note_in') {
            //27.08.2018 - we are sending cl_partners_book_workers which is assigned to current tablename
            $tmpWorkers = $this->PartnersBookWorkersManager
                ->findAll()
                ->where('cl_partners_book_id = ? AND use_' . $this->mainTableName . ' = ?', $cl_partners_book_id, 1)
                ->fetch();
            if (!$tmpWorkers) {
                $tmpWorkers = $this->PartnersBookWorkersManager
                    ->findAll()
                    ->where('cl_partners_book_id = ?', $cl_partners_book_id)
                    ->fetch();
            }
        }else
            $tmpWorkers = false;

        if ($tmpWorkers) {
            $sendWorker = $tmpWorkers->id;
        } else {
            $sendWorker = 0;
        }

        $this->payload->cl_partners_book_workers_id = $sendWorker;

        $arrWorkersAll = $this->PartnersBookWorkersManager
            ->getWorkersGrouped($cl_partners_book_id);

        //$arrWorkersAll = $this->PartnersBookWorkersManager->getUseRecords($cl_partners_book_id);
        $this->payload->cl_partners_book_workers_id_values = $arrWorkersAll;
        $mySection->cl_partners_book_workers_id_values = $arrWorkersAll;


        //27.12.2018 - we are sending cl_partners_branch  which is assigned to current tablename
        /*$tmpBranches = $this->PartnersBranchManager
                    ->findAll()
                    ->where('cl_partners_book_id = ? AND use_'.$this->mainTableName.' = ?', $cl_partners_book_id, 1 )
                    ->fetchPairs('id','b_name');
        if (!$tmpBranches){
            $tmpBranches = $this->PartnersBranchManager
                    ->findAll()
                    ->where('cl_partners_book_id = ?', $cl_partners_book_id)
                    ->fetchPairs('id','b_name');
        }*/
        $tmpBranches = $this->PartnersBranchManager->getUseRecords($cl_partners_book_id, $this->mainTableName);
        $this->payload->cl_partners_branch_id_values = $tmpBranches;
        $mySection->cl_partners_branch_id_values = $tmpBranches;


        $arrAccountsAll = $this->PartnersAccountManager->findAll()->
                            where('cl_partners_book_id = ?', $cl_partners_book_id)->
                            select('cl_partners_account.id, CONCAT(cl_currencies.currency_code, " ", account_code, "/", bank_code) AS account_number')->
                            order('cl_currencies.currency_code')->fetchPairs('id', 'account_number');

        //$arrAccountsAll = $this->PartnersAccountManager->findAll()->where('cl_partners_book_id = ?', $cl_partners_book_id)->fetchPairs('id','id');

        $this->payload->cl_partners_account_id_values = $arrAccountsAll;
        $mySection->cl_partners_account_id_values = $arrAccountsAll;

        $this->payload->partnerCard 	= $this->link(':Application:Partners:edit', ['id' => $cl_partners_book_id]);
        $this->payload->partnerCardData = $this->link(':Application:Partners:edit', ['id' => $cl_partners_book_id, 'modal' => 1, 'roModal' => 1]);

        $this->redrawControl('formedit');
        $this->sendPayload();
        //$this->redrawControl('content');
        //$this->redrawControl('openPartnerCard');

    }



    public function updatePartnerId($data){
        $mySection = $this->getSession('selectbox'); // returns SessionSection with given name
        $arrPartners = $mySection->cl_partners_book_id_values;
        //bdump($arrPartners,'arrPartners');
        $arrPar = array();
        if (!is_null($arrPartners)) {
            foreach ($arrPartners as $key => $one) {
                $data['cl_partners_book_id'] = $key;
                $arrPar['cl_partners_book_id'] = $key;
            }
            $this['edit']['cl_partners_book_id']->SetItems($arrPartners);
            $this['edit']['cl_partners_book_id']->setValue($arrPar['cl_partners_book_id']);
        }

        $mySection->cl_partners_book_id_values = NULL;
        return $data;
    }

    public function handleShowComment($cl_partners_book_id){
        bdump($cl_partners_book_id, 'TED');
        $tmpbscData = $this->PartnersManager->find($cl_partners_book_id);
        bdump($tmpbscData, 'TED');
        if ($tmpbscData && $tmpbscData['show_comment']){
            $tmpStrTime = '';
            $tmpStyle = '';
            if (!is_null($tmpbscData['cl_partners_groups_id']) && $tmpbscData->cl_partners_groups['helpdesk_fund'] > 0){
                $tmpHelpdesk = $this->PartnersEventManager->findAll()->where('cl_partners_event.cl_partners_book_id = ? AND cl_partners_event.cl_partners_event_id IS NULL AND YEAR(cl_partners_event.date_rcv) = YEAR(NOW())', $cl_partners_book_id)->
                                    where('(:cl_task.payment = 0)')->
                                    select('SUM(cl_partners_event.work_time)/60 AS work_time')->limit(1)->fetch();
                if ($tmpHelpdesk['work_time'] >=  $tmpbscData->cl_partners_groups['helpdesk_fund']){
                    $tmpStyle = 'color:red';
                }
                $tmpStrTime = '<strong style="' . $tmpStyle . '">Hodinový fond: ' . $tmpbscData->cl_partners_groups['helpdesk_fund'] . ' Vyčerpáno: ' . round($tmpHelpdesk['work_time'],1) . '</strong><br><br>';
            }
            $this->partnerComment =  $tmpStrTime . nl2br($tmpbscData['comment']);
            $this->partnerCommentName = $tmpbscData['company'];
        }else{
            $this->partnerComment = '';
            $this->partnerCommentName = '';
        }
        bdump($this->partnerComment, 'TED2');
        $this->redrawControl('showCommentMain');
    }

    public function handleSaveCommentW($posX, $posY){
        $this->UserManager->updateUser(['id' => $this->user->getId(), 'comment_window' => $posX . ';' . $posY]);
        $this->getUser()->getIdentity()->comment_window = $posX . ';' . $posY;
    }

    public function handleGetCommentW(){
        $arrData  = str_getcsv($this->getUser()->getIdentity()->comment_window,';');
        $this->payload->data = $arrData;
        $this->sendPayload();
    }


}
