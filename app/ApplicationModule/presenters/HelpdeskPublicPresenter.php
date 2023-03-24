<?php

namespace App\ApplicationModule\Presenters;

use App\Controls;
use Nette\Application\UI\Form,
    Nette\Image;
use Nette\Mail\Message,
    Nette\Utils\Strings;
use Nette\Mail\SendmailMailer;

class HelpdeskPublicPresenter extends \App\Presenters\BaseAppPresenter {

    /** @persistent */
   public $partner_id;
   
    /** @persistent */   
   public $center_id;
   
    /** @persistent */   
   public $cl_partners_book_id;   
   
    /** @persistent */    
   public $cl_company_id;
   
    /** @persistent */
   public $public_event_token;
   
    /** @persistent */
   public $public_center_token;   
 
    /**
    * @inject
    * @var \App\Model\PartnersManager
    */
    public $PartnersManager;    
	
    /**
    * @inject
    * @var \App\Model\PartnersBookWorkersManager
    */
    public $PartnersBookWorkersManager;    	
    
    /**
    * @inject
    * @var \App\Model\PartnersEventManager
    */
    public $DataManager;        
    
    

    /**
    * @inject
    * @var \App\Model\PartnersBranchManager
    */
    public $PartnersBranchManager;         
    
    /**
    * @inject
    * @var \App\Model\FilesManager
    */
    public $FilesManager;        

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
    * @var \App\Model\PartnersCategoryManager
    */
    public $PartnersCategoryManager;      		

    /**
    * @inject
    * @var \App\Model\CenterManager
    */
    public $CenterManager;          

    /**
    * @inject
    * @var \App\Model\CompaniesAccessManager
    */
    public $CompaniesAccessManager;   
    
    /**
    * @inject
    * @var \MainServices\smsService
    */
    public $smsService;     
    
    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.hdpublic']);
    }

    public function actionDefault($partner_id,$public_event_token,$public_center_token = NULL)
    {
	$this->partner_id = $partner_id;
	$this->public_event_token = $public_event_token;
	$this->public_center_token = $public_center_token;
	
	$request = $this->getHttpRequest();
	$response = $this->getHttpResponse();

	if (!$request->getCookie('fileSession'))
	{
	    $section = $this->getSession('helpdeskPublic');	
	    $section->fileSession = \Nette\Utils\Random::generate(64,'A-Za-z0-9');
	    $response->setCookie('fileSession', 1, new \Nette\Utils\DateTime('+ 15 minute')); // nova session po 15 minutach
	}	
	
	//!!! PAK ZRUSIT !!!
	//\Tracy\Debugger::$productionMode = FALSE;	
	//09.04.2017 - clean up mess files. Mess is file older then 20 minutes from now and with not NULL file_session
	$tmpFile = $this->FilesManager->findAllTotal()->where('file_session IS NOT NULL AND `created` <= DATE_SUB(NOW(), INTERVAL 20 MINUTE)');
	foreach ($tmpFile as $one )
	{
	   // dump($one->file_name);
		$fileDel = __DIR__."/../../../../data/files/".$one->file_name;
		if (file_exists($fileDel)) 
			unlink ($fileDel);

		$one->delete();
	}

	
	//$session = $this->getSession(); // returns the whole Session

	
    }
    
    public function renderDefault()
    {

	//$section = $this->getSession('helpdeskPublic');	
	//dump($section->fileSession);
		//dump($this->public_token);	
		if (!is_null($this->public_center_token))
		{
		    //07.03.2017 - we have public token for center, we work with list of companies
		    $arrPartners = $this->PartnersManager->findAllTotal()
					->where('cl_center.public_event_token = ? AND cl_center.public_event = 1', $this->public_center_token)
					->fetchPairs('id', 'company');

		    $this['eventPublicForm']['cl_partners_book_id']
				->setItems($arrPartners);

		    //dump(array_keys($arrPartners)[0]);
		    if ($this->partner_id == 0)
		    {
			if (isset(array_keys($arrPartners)[0]))
			{
			    $partnerId = array_keys($arrPartners)[0];
			}else{
			    $partnerId = NULL;
			}
			
		    }else{
			$partnerId = $this->partner_id;
		    }
		    $partner = $this->PartnersManager->findAll()->where(array('id' => $partnerId));
		    
			

		    $this->template->logged = FALSE;
		    $this->template->center = TRUE;
		    $this->template->centerData = $this->CenterManager->findAllTotal()
							    ->where('cl_center.public_event_token = ? AND cl_center.public_event = 1', $this->public_center_token)
							    ->limit(1)->fetch();
		    
		    $this->cl_company_id = $this->template->centerData->cl_company_id;		    
		    //dump($this->cl_company_id);
		    $this->center_id = $this->template->centerData->id;
		    
		    $this['eventPublicForm']['cl_partners_category_id']->setPrompt($this->translator->translate('Zvolte kategorii důležitosti'))
				->setItems(array());	
		    $this['eventPublicForm']['cl_partners_book_workers_id']->setPrompt($this->translator->translate('Zvolte kontaktní osobu'))
				->setItems(array());										
					    
		    
		    
		}else{
		    ///07.03.2017 - we haven't public token for center, we work only with one company
		    if (!$this->getUser()->isLoggedIn()) {	
			    //if user is not loggedin, we must find partner by public_token and id
			    $partner = $this->PartnersManager->findAllTotal()->where(array('id' => $this->partner_id, 'public_event_token' => $this->public_event_token));
			    $this->template->logged = FALSE;
		    }else{
			    //otherwise is enough only by id
			    $partner = $this->PartnersManager->findAll()->where(array('id' => $this->partner_id));	    
			    $this->template->logged = TRUE;
		    }
		    $this->template->center = FALSE;

		    
		    
		}
		if ($partnerData = $partner->fetch())
		{
			$this->template->partner = $partnerData;
			$this->cl_partners_book_id = $partnerData->id;
			
			$this->cl_company_id = $partnerData->cl_company_id;
			
			$arrPartnerWorkers = $this->PartnersBookWorkersManager->findAllTotal()->
								where('cl_partners_book_id = ?',$partnerData->id)->
								fetchPairs('id','worker_name');

			$this['eventPublicForm']['cl_partners_book_workers_id']->setPrompt($this->translator->translate('Zvolte kontaktní osobu'))
				->setItems($arrPartnerWorkers);										
			
			$curr_name = $partnerData->cl_company->cl_currencies->currency_name;
			$arrPartnersCategory= $this->PartnersCategoryManager->findAllTotal()->
						where('cl_company_id = ? AND deactive = 0',$partnerData->cl_company_id)
						->select('id,category_name AS name')
                                                ->fetchPairs('id','name');
                        //->select('id,CONCAT(category_name," (","sazba: ",hour_tax," '.$curr_name.' / hodina",")") AS name')
			$this['eventPublicForm']['cl_partners_category_id']->setPrompt($this->translator->translate('Zvolte kategorii důležitosti'))
				->setItems($arrPartnersCategory);													
			

			$arrPartnersBranch = $this->PartnersBranchManager->findAllTotal()->
						where('cl_partners_book_id = ?',$this->partner_id)
						->select('id,b_name AS name')->order('b_name')
                                                ->fetchPairs('id','name');
			$this['eventPublicForm']['cl_partners_branch_id']->setPrompt($this->translator->translate('Zvolte pobočku'))
				->setItems($arrPartnersBranch);
			
			//26.05.2016 - add prices to cl_partners_category_id - defined on partner 
			$tmpTaxes = json_decode($partnerData->cl_partners_category_taxes, TRUE);
			if (!is_null($tmpTaxes))
			{
				$curr_name = $partnerData->cl_company->cl_currencies->currency_name;
				$arrPartnersCategoryNew = array();
				$arrPartnersCategory= $this->PartnersCategoryManager->findAllTotal()->where('cl_company_id = ? AND deactive = 0',$partnerData->cl_company_id);
				foreach($arrPartnersCategory as $key => $one)
				{
					$tmpTax = $tmpTaxes['categremote'.$key];
					if ($tmpTax == 0)
						$tmpTax = $one->hour_tax_remote;

					//$arrPartnersCategoryNew[$key] = $one->category_name." (sazba: ".$tmpTax." ".$curr_name." / hodina)";
                                        $arrPartnersCategoryNew[$key] = $one->category_name;
				}
				$this['eventPublicForm']['cl_partners_category_id']->setPrompt($this->translator->translate('Zvolte kategorii důležitosti'))
						->setItems($arrPartnersCategoryNew);
			}			
			
			
			if (isset($partnerData->cl_partners_category['id']))
				$this['eventPublicForm']['cl_partners_category_id']->setValue($partnerData->cl_partners_category_id);
			
		}
		else
			$this->template->partner = false;

		$this->template->eventsModalShow = FALSE;
    }
    
    public function renderSend()
    {
	//dump($this->public_event_token);
	//die;
	if (!$this->getUser()->isLoggedIn()) {	
	    //if user is not loggedin, we must find partner by public_token and id
	    $partner = $this->PartnersManager->findAllTotal()->where(array('id' => $this->partner_id));
	    ///08.04.2017 - for now switched off, 'public_event_token' => $this->public_event_token
	    $this->template->logged = FALSE;
	}else{
	    //otherwise is enough only by id
	    $partner = $this->PartnersManager->findAll()->where(array('id' => $this->partner_id));	    
	    $this->template->logged = TRUE;
	}
	if ($partnerData = $partner->fetch())
	   $this->template->partner = $partnerData;
	else
	    $this->template->partner = false;
		
	$this->template->eventsModalShow = FALSE;
    }    
    
    protected function createComponentFiles()
     {
		
        if (!is_null($this->public_center_token))
        {
            $parent_value = $this->center_id;
            $parent_data = 'cl_center_id';
            $parent_manager = $this->CenterManager;
        }else{
            $parent_value = $this->cl_partners_book_id;
            $parent_data = 'cl_partners_book_id';
            $parent_manager = $this->PartnersManager;
        }
        //dump($this->public_center_token);
        //dump($parent_value);
        //die;
        if ($this->getUser()->isLoggedIn()){
            $user_id = $this->user->getId();
            $cl_company_id = $this->settings->id;
        }else{
            if ($tmpData = $parent_manager->findAllTotal()->where('id = ?',$parent_value)->limit(1)->fetch())
            {
            $cl_company_id = $tmpData->cl_company_id;
            if ($tmpUser = $this->CompaniesAccessManager->findAllTotal()->where('cl_company_id = ?', $cl_company_id)->limit(1)->fetch())
            {
                $user_id = $tmpUser->cl_users_id;
            }
            }

        }
        // $translator = clone $this->translator->setPrefix([]);
        return new Controls\FilesControl($this->translator,
            $this->FilesManager,$this->UserManager,$parent_value,$parent_data,$parent_manager, $cl_company_id, $user_id,
            $this->CompaniesManager, $this->ArraysManager);

     }                
         
   
    public function handleGetFile($id)
    {
        if ($file = $this->FilesManager->find($id))
        {
            $fileSend = __DIR__."/../../../data/files/".$file->file_name;
            $this->presenter->sendResponse(new \Nette\Application\Responses\FileResponse($fileSend, $file->label_name, $file->mime_type));
            //, 'contenttype'
            //$this->

        }
    }
    
    
  protected function createComponentEventPublicForm($name)
    {	
            $form = new Form($this, $name);
            //$form->setTranslator(//$this->translator->setPrefix(['applicationModule.hdpublic']));
	    //$form->setMethod('POST');
	    $form->addHidden('id',NULL);

	    $arrPartnerWorkers = $this->PartnersBookWorkersManager->findAllTotal()->fetchPairs('id','worker_name');
	    $form->addSelect('cl_partners_book_workers_id', $this->translator->translate('cl_partners_book_workers'),$arrPartnerWorkers)
		    ->setTranslator(NULL)
		    ->setHtmlAttribute('data-placeholder', $this->translator->translate('cl_partners_book_workersPh'))
		    ->setPrompt($this->translator->translate('cl_partners_book_workersPh'));

            $arrPartnersCategory= $this->PartnersCategoryManager->findAllTotal()->select('id,category_name AS name')->fetchPairs('id','name');
	    $form->addSelect('cl_partners_category_id', $this->translator->translate('cl_partners_category'),$arrPartnersCategory)
			->setTranslator(NULL)
			->setHtmlAttribute('data-placeholder',$this->translator->translate('cl_partners_categoryPh'))
			->setHtmlAttribute('class','chzn-select')
			->setPrompt($this->translator->translate('cl_partners_categoryPh'));
		
	    $arrBranch = $this->PartnersBranchManager->findAllTotal()->select('id,b_name AS name')->fetchPairs('id','name');
	    $form->addSelect('cl_partners_branch_id',  $this->translator->translate('cl_partners_branch'), $arrBranch)
		    ->setTranslator(NULL)
		    ->setPrompt($this->translator->translate('cl_partners_branch'))
		    ->setHtmlAttribute('data-placeholder',$this->translator->translate('cl_partners_branchPh'));
	    
	    $arrPartners = $this->PartnersManager->findAll()->fetchPairs('id','company');
	    $form->addSelect('cl_partners_book_id', $this->translator->translate('cl_partners_book'),$arrPartners)
		    ->setTranslator(NULL)
		    ->setHtmlAttribute('data-placeholder',$this->translator->translate('cl_partners_bookPh'))
		    ->setPrompt($this->translator->translate('cl_partners_bookPh'));
	    
	    $form['cl_partners_book_id']
				->setHtmlAttribute('data-slave_workers',$form['cl_partners_book_workers_id']->getHtmlId())								
				->setHtmlAttribute('data-slave_categories',$form['cl_partners_category_id']->getHtmlId())										    
				->setHtmlAttribute('data-urlajax',$this->link('ChangePartner!'));
	    
	    
	    
				
		
		
		$form->addText('work_label', $this->translator->translate('work_label'), 50, 200)
			->setHtmlAttribute('placeholder',$this->translator->translate('work_labelPh'));

		/*$form->addSelect('priority', 'Důležitost:', $this->getPriority())
			->setDefaultValue(1)
		    ->setPrompt('Zvolte důležitost')
		    ->setHtmlAttribute('data-placeholder','Zvolte důležitost')
		    ->setHtmlAttribute('class','chzn-select');		
		 */
		
		//$curr_name = $this->settings->cl_currencies->currency_name;
		//$arrPartnersCategory= $this->PartnersCategoryManager->findAllTotal()->select('id,CONCAT(category_name," (","sazba: ",hour_tax,"  / hodina",")") AS name')->fetchPairs('id','name');

		
	    $form->addTextArea('description_original', $this->translator->translate('description_original'), 100,15 )
			->setHtmlAttribute('placeholder',$this->translator->translate('description_originalPh'));
	    
	    $form->onValidate[] = array($this, 'FormValidate');	   
	    $form->addSubmit('save', $this->translator->translate('Uložit'))->setHtmlAttribute('class','btn btn-sm btn-primary');
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
		    ->setHtmlAttribute('class','btn btn-sm btn-primary')
		    ->setValidationScope([])
		    ->onClick[] = array($this, 'stepBack');	    	    
	//	    ->onClick[] = callback($this, 'stepSubmit');

		//$form->onSuccess[] = $this->SubmitEditSubmitted;
		$form->onSuccess[] = array($this, 'SubmitEditSubmitted');
		return $form;
    }

    public function FormValidate(Form $form)
    {
	$data=$form->values;
	
	$arrTmpData = $this->PartnersBookWorkersManager->findAllTotal()->
						where('cl_partners_book_id = ?',$this->partner_id)->
						fetchPairs('id','worker_name');	
	if ($arrTmpData && $data['cl_partners_book_workers_id'] == NULL)
	{
	    $form->addError('cl_partners_book_workersReq');
	}
	
	$arrTmpData = $this->PartnersBranchManager->findAllTotal()->
						where('cl_partners_book_id = ?',$this->partner_id)->
						fetchPairs('id','b_name');	
	if ($arrTmpData && $data['cl_partners_branch_id'] == NULL)
	{
	    $form->addError('cl_partners_branchReq');
	}	

	if ($data['cl_partners_category_id'] == NULL)
	{
	    $form->addError('cl_partners_categoryReq');
	}		
	
	$this->redrawControl('content');			    
		
    }           
    
    public function stepBack()
    {	    
	//$this->presenter->redirect(':Application:PartnersEvent:');
	//if (!$this->eventNew)
		$this->presenter->redirect(substr($this->presenter->name,strpos($this->presenter->name, ":")+1).":default");	

    }		
    
    public function SubmitEditSubmitted(Form $form)
       {
	   //$data =$form->values;	
	   $data = $form->getHttpData();
	   //dump($data);
	   //die;
	   if ($form['save']->isSubmittedBy())
	   { 
	       
	      // dump($data);
	       //dump(isset($data['cl_partners_book_workers_id']));
	       if (isset($data['cl_partners_book_id']))
	       {
		   $this->partner_id = $data['cl_partners_book_id'];
	       }
		if ($tmpPartner = $this->PartnersManager->findAllTotal()->where(array('id' => $this->partner_id))->fetch())	       
		{
		    $tmpData = array();
		    if (isset($data['cl_partners_category_id']) && $data['cl_partners_category_id'] != '')
		    {
			    $tmpData['cl_partners_category_id'] = $data['cl_partners_category_id'];
		    }else{
			    $tmpData['cl_partners_category_id'] = NULL;
		    }
		    
		    if (isset($data['cl_partners_branch_id']) && $data['cl_partners_branch_id'] != '')
		    {
			    $tmpData['cl_partners_branch_id'] = $data['cl_partners_branch_id'];
		    }else{
			    $tmpData['cl_partners_branch_id'] = NULL;
		    }		    
		    
		    $tmpData['work_label'] = $data['work_label'];
		    $tmpData['description_original'] = $data['description_original'];
		    //dump($data['cl_partners_book_workers_id']);
		    if (isset( $data['cl_partners_book_workers_id'])  && $data['cl_partners_book_workers_id'] != '')
		    {
			    $tmpData['cl_partners_book_workers_id'] = $data['cl_partners_book_workers_id'];
		    }else{
			    $tmpData['cl_partners_book_workers_id'] = NULL;
		    }
            $tmpData['cl_company_id'] = $tmpPartner->cl_company_id;
		    $tmpData['cl_partners_book_id'] = $this->partner_id;
		    $tmpData['date_rcv'] = new \Nette\Utils\DateTime;
		    $tmpData['created'] = new \Nette\Utils\DateTime;
		    $tmpData['create_by'] = $tmpPartner->person;
			
			//hour tax. If is set on cl_partners_book use it, or use cl_partners_category.hour_tax 
			//only if it is main request

			$tmpTaxes = json_decode($tmpPartner->cl_partners_category_taxes, TRUE);
			if (!is_null($tmpTaxes))
			{
				//always remote hour_tax
				$tmpData['hour_tax'] = $tmpTaxes['categremote'.$data['cl_partners_category_id']];								
			}  else {
				$tmpData['hour_tax'] = 0;
			}
			
			if ($tmpData['hour_tax'] == 0)
			{
				if ($tmpCateg = $this->PartnersCategoryManager->find($data['cl_partners_category_id']))
					$tmpData['hour_tax'] = $tmpCateg->hour_tax_remote;
				else
					$tmpData['hour_tax'] = 0;
			}
			
		    if ($tmpDef = $this->PartnersEventTypeManager->findAllTotal()->where(array('cl_company_id' => $tmpPartner->cl_company_id))
										->where('default_event OR main_event')->order('main_event DESC')->fetch())
			    $tmpDefId = $tmpDef->id;
		    else
		    	$tmpDefId = NULL;

		    $tmpData['cl_partners_event_type_id'] = $tmpDefId;
		    
		    $nSeries = $this->NumberSeriesManager->getNewNumber('partners_event',NULL,NULL,$tmpPartner->cl_company_id);

		    $tmpData['cl_number_series_id'] = $nSeries['id'];
		    $tmpData['event_number'] = $nSeries['number'];
		    
		    $tmpStatus = 'partners_event';

		    if ($nStatus= $this->StatusManager->findAllTotal()->where(array('cl_company_id' => $tmpPartner->cl_company_id))
									->where('status_use = ? AND s_new = ?',$tmpStatus,1)->fetch())
			$tmpData['cl_status_id'] = $nStatus->id;		    
		    
		    
		    if (isset($tmpPartner->cl_partners_category->react_time))
		    {
				$defDatarcv =  $tmpData['date_rcv']->getTimestamp();
				$tmpData['date_end'] = new \Nette\Utils\DateTime;
				$tmpData['date_end']->setTimestamp($defDatarcv);
				$tmpData['date_end'] = $tmpData['date_end']->modify('+'.($tmpPartner->cl_partners_category->react_time).' hours');
		    }
		    
		    
		}
		
		$rowData = $this->DataManager->insertPublic($tmpData);
		//09.04.2017 - pair uploaded files with cl_partners_event_id by session
		$section = $this->getSession('helpdeskPublic');	
		
		//je vloženo, teď musíme přesunout soubory přiřazené klientovi k události. 
		//'cl_partners_book_id' => $this->partner_id,
		$tmpFiles = $this->FilesManager->findAllTotal()->
			    where(array('cl_company_id' => $tmpPartner->cl_company_id,
					'file_session' => $section->fileSession));
		foreach($tmpFiles as $one)
		{
		    $newData = array();
		    $newData['cl_partners_book_id'] = NULL;
		    $newData['cl_center_id'] = NULL;
		    $newData['file_session'] = NULL;
		    $newData['cl_partners_event_id'] = $rowData->id;
		    $one->update($newData);
		}
		

			   //$this->presenter->redirect(':Application:PartnersEvent:');
		//now, send email with confirmation back to user
		$this->sendCustomerEmail($rowData,$tmpPartner);
		
		//now, send email to all helpdesk administrator cl_users.event_manager
		$this->sendAdminEmail($rowData,$tmpPartner);
		
		//now, send SMS
		$this->sendSMS($rowData,$tmpPartner);

		$this->presenter->redirect("HelpdeskPublic:send");

	   }
	   		
       }

	   private function sendAdminEmail($tmpData,$tmpPartner)
	   {
			if ($emailTo  = $this->UsersManager->findAllTotal()->
								where(array('event_manager' => 1, 'cl_company_id' => $tmpPartner->cl_company_id))->select("id, CONCAT(name,' <',email,'>') AS user")->fetchPairs('id','user'))
			{

			}else{
				$emailTo  = $this->UsersManager->findAllTotal()->
								where(array('cl_company_id' => $tmpPartner->cl_company_id))->select("id, CONCAT(name,' <',email,'>') AS user")->limit(1)->fetchPairs('id','user');
			}
            $tmpData2 = [];

           if ($tmpPartner->cl_company->email_income != '') {
               $tmpData2['singleEmailFrom'] = $tmpPartner->cl_company->name . ' <' . $tmpPartner->cl_company->email_income . '>';
           }else {
               $tmpData2['singleEmailFrom'] = $tmpPartner->cl_company->name . ' <' . $tmpPartner->cl_company->email . '>';
           }


			//$tmpData2 = array();
			$singleEmailTo = '';
			foreach($emailTo as $one) 
			{ 
			    if ($singleEmailTo != ''){
				$singleEmailTo .= ';';
			    }
				$singleEmailTo .= $one;
			    
			}
			$tmpData2['singleEmailTo'] = $singleEmailTo;
			//$tmpData2['singleEmailFrom'] = $tmpPartner->cl_company->name.' <'.$replyEmail.'>';

			if ($tmpPartner->cl_company->hd3_emailing_text_id !== NULL)
			{
				$tmpEmlText = $this->EmailingTextManager->getEmailingText('','','',$tmpPartner->cl_company->hd3_emailing_text_id, $tmpPartner->cl_company_id);
				$tmpData2['subject'] = '['.$tmpData['event_number'].']['.$tmpEmlText['subject'].'] '.$tmpData['work_label'];
				$template = $this->createTemplate()->setFile(__DIR__.'/../../templates/Emailing/email.latte');
				if ($tmpData['cl_partners_book_workers_id'] != NULL)
				{
				    
					if ($tmpPartnersBookWorker = $this->PartnersBookWorkersManager->findAllTotal()->
							where(array('cl_company_id' => $tmpPartner->cl_company_id))->where('id = ?', $tmpData['cl_partners_book_workers_id'])->fetch())
					{
						if ($tmpPartnersBookWorker->worker_email == ''){
						    $tmpEmailTo = $tmpPartner->email;
						}else{
						    $tmpEmailTo = $tmpPartnersBookWorker->worker_email;
						}					    
						$tmpKontakt = '<tr><td>'.$this->translator->translate('Kontakt:').' </td><td>'.$tmpPartnersBookWorker->worker_name.', Email:'. $tmpEmailTo.', Tel.:'.$tmpPartnersBookWorker->worker_phone.'</td></tr>';
					}
				}else{
					$tmpKontakt = '<tr><td>'.$this->translator->translate('Kontakt:').'</td><td>'.$tmpPartner->person.', Email:'. $tmpPartner->email.', Tel.:'.$tmpPartner->phone.'</td></tr>';
				}								
				if ($tmpData['cl_partners_branch_id'] != NULL){
				    $tmpBranch = '<tr><td>'.$this->translator->translate('Pobočka:').'</td><td>'.$tmpData->cl_partners_branch->b_name.'</td></tr>';
				}else{
				    $tmpBranch = '<tr><td>'.$this->translator->translate('Pobočka:').'</td><td> </td></tr>';
				}
				
				$template->body = $tmpEmlText['body'].
										'<table>'.
										'<tr><td>'.$this->translator->translate('Odesílatel:').'</td><td>'. $tmpPartner->company.'</td></tr>'.
										$tmpBranch.
										$tmpKontakt.    
										'<tr><td>'.$this->translator->translate('Předmět:').'</td><td>'.$tmpData['work_label'].'</td></tr>'.
										'<tr><td>'.$this->translator->translate('Důležitost:').'</td><td>'.$tmpData->cl_partners_category->category_name.'</td></tr>'.
										'<tr><td>'.$this->translator->translate('Zpráva:').'</td><td>'.$tmpData['description_original'].'</td></tr>'.
										'</table>';
				$tmpData2['body']	= $template;			
				$tmpData2['create_by'] = 'automat';

				/*$mail = new \Nette\Mail\Message;
				$mail->setFrom('mailer@klienti.cz',$tmpPartner->cl_company->name)
					->addReplyTo($replyEmail)
					->setSubject($tmpData2['subject'])
					->setHtmlBody($tmpData2['body']);
				foreach($emailTo as $one) 
				{
					$mail->addTo($one);
				}
				$mailer = new SendmailMailer;
				$mailer->send($mail);		*/

				//send email
				$this->emailService->sendMail2($tmpData2,$tmpPartner->cl_company_id);			

				//save to cl_emailing
				$this->EmailingManager->insertForeign($tmpData2);				
			}		   
	   }
    
	   private function sendCustomerEmail($tmpData,$tmpPartner)
	   {
		   
			if ($tmpPartner->cl_company->hd1_emailing_text_id !== NULL && $tmpPartner->email != '')
			{
				$tmpData2 = array();


                if ($tmpPartner->cl_company->email_income != '') {
                    $tmpData2['singleEmailFrom'] = $tmpPartner->cl_company->name . ' <' . $tmpPartner->cl_company->email_income . '>';
                }else {
                    $tmpData2['singleEmailFrom'] = $tmpPartner->cl_company->name . ' <' . $tmpPartner->cl_company->email . '>';
                }



                //$tmpData2 = array();

				//$tmpData2['singleEmailFrom'] = $tmpPartner->cl_company->name.' <'.$replyEmail.'>';

				
				$tmpEmlText = $this->EmailingTextManager->getEmailingText('','','',$tmpPartner->cl_company->hd1_emailing_text_id, $tmpPartner->cl_company_id);
				$tmpData2['subject'] = '['.$tmpData['event_number'].']['.$tmpEmlText['subject'].'] '.$tmpData['work_label'];
				$template = $this->createTemplate()->setFile(__DIR__.'/../../templates/Emailing/email.latte');
				if ($tmpData['cl_partners_book_workers_id'] != NULL)
				{
					if ($tmpPartnersBookWorker = $this->PartnersBookWorkersManager->findAllTotal()->
							where(array('cl_company_id' => $tmpPartner->cl_company_id))->where('id = ?', $tmpData['cl_partners_book_workers_id'])->fetch())
					{
					    if ($tmpPartnersBookWorker->worker_email == ''){
						$tmpEmailTo = $tmpPartner->email;
					    }else{
						$tmpEmailTo = $tmpPartnersBookWorker->worker_email;
					    }
					    $tmpKontakt = '<tr><td>'.$this->translator->translate('Kontakt:').' </td><td>'.$tmpPartnersBookWorker->worker_name.', Email:'. $tmpEmailTo.', Tel.:'.$tmpPartnersBookWorker->worker_phone.'</td></tr>';
					    $tmpData2['singleEmailTo'] = $tmpPartnersBookWorker->worker_name.' <'.$tmpEmailTo.'>';
					}
				}else{
					$tmpKontakt = '<tr><td>'.$this->translator->translate('Kontakt:').'</td><td>'.$tmpPartner->person.', Email:'. $tmpPartner->email.', Tel.:'.$tmpPartner->phone.'</td></tr>';
					$tmpData2['singleEmailTo'] = $tmpPartner->company.' <'.$tmpPartner->email.'>';
				}	
				if ($tmpData['cl_partners_branch_id'] != NULL){
				    $tmpBranch = '<tr><td>'.$this->translator->translate('Pobočka:').'</td><td>'.$tmpData->cl_partners_branch->b_name.'</td></tr>';
				}else{
				    $tmpBranch = '<tr><td>'.$this->translator->translate('Pobočka:').'</td><td> </td></tr>';
				}				
				
				$template->body = $tmpEmlText['body'].
										'<table>'.
										'<tr><td>'.$this->translator->translate('Odesílatel:').'</td><td>'. $tmpPartner->company.'</td></tr>'.
										$tmpBranch.
										$tmpKontakt.
										'<tr><td>'.$this->translator->translate('Předmět:').'</td><td>'.$tmpData['work_label'].'</td></tr>'.
										'<tr><td>'.$this->translator->translate('Důležitost:').'</td><td>'.$tmpData->cl_partners_category->category_name.'</td></tr>'.
										'<tr><td>'.$this->translator->translate('Zpráva:').'</td><td>'.$tmpData['description_original'].'</td></tr>'.
										'</table>';
				$tmpData2['body']	= $template;			
				$tmpData2['create_by'] = 'automat';

				//send email
				$this->emailService->sendMail2($tmpData2,$tmpPartner->cl_company_id);			

				//save to cl_emailing
				$this->EmailingManager->insertForeign($tmpData2);				
			}		   
	   }
	   
    public function handleChangePartner($cl_partners_book_id)
    {
	$this->partner_id = $cl_partners_book_id;
	$partnerData = $this->PartnersManager->findAllTotal()->where('id = ?',$cl_partners_book_id)->fetch();	
	
	$arrSend = [];
	$arrSend['worker']['arrData'] = $this->PartnersBookWorkersManager->findAllTotal()->
					where('cl_partners_book_id = ? AND cl_company_id = ?',$cl_partners_book_id,$partnerData->cl_company_id)
					    ->fetchPairs('id','worker_name');
						    

	//$curr_name = $partnerData->cl_company->cl_currencies->currency_name;
	$arrPartnersCategory= $this->PartnersCategoryManager->findAllTotal()->
				where('cl_company_id = ?',$partnerData->cl_company_id)
				->select('id,category_name AS name')
				->fetchPairs('id','name');
	$arrSend['categories']['arrData'] = $arrPartnersCategory;	
	
	//26.05.2016 - add prices to cl_partners_category_id - defined on partner 
	$tmpTaxes = json_decode($partnerData->cl_partners_category_taxes, TRUE);
	if (!is_null($tmpTaxes))
	{
	//	$curr_name = $partnerData->cl_company->cl_currencies->currency_name;
		$arrPartnersCategoryNew = array();
		$arrPartnersCategory= $this->PartnersCategoryManager->findAllTotal()->where('cl_company_id = ? ',$partnerData->cl_company_id);
		foreach($arrPartnersCategory as $key => $one)
		{
			$tmpTax = $tmpTaxes['categremote'.$key];
			if ($tmpTax == 0)
				$tmpTax = $one->hour_tax_remote;

			//$arrPartnersCategoryNew[$key] = $one->category_name." (sazba: ".$tmpTax." ".$curr_name." / hodina)";
			$arrPartnersCategoryNew[$key] = $one->category_name;
		}
		$arrSend['categories']['arrData'] = $arrPartnersCategoryNew;			
	}			
	

	

	$this->sendJson($arrSend); 
			
	
	//$this->redrawControl('worker');
	//$this->redrawControl('category');	
    }

    public function sendSMS($tmpData,$tmpPartner)
    {
	
	    $userTmpSMSManager = json_decode($tmpPartner->cl_company->sms_manager, true);		
	
	    if ($userTmpSMSManager['hd_recieved'])
	    {
		if ($phoneTo  = $this->UsersManager->findAllTotal()->
				    where(array('event_manager' => 1, 'cl_company_id' => $tmpPartner->cl_company_id))->select("id, phone")->fetchPairs('id','phone'))
		{

		}else{
		    $phoneTo  = $this->UsersManager->findAllTotal()->
					where(array('cl_company_id' => $tmpPartner->cl_company_id))->select("id, phone")->limit(1)->fetchPairs('id','phone');
		}

		$this->smsService->sendSMS($this->translator->translate('Helpdesk přijal nový požadavek od ').$tmpPartner->company.' > '.$tmpData['work_label'], 
						$phoneTo, 
						$tmpPartner->cl_company, 1);
	    }
    }
    
	    
    
    
    
}
