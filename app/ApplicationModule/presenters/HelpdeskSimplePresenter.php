<?php

namespace App\ApplicationModule\Presenters;

use App\Controls;
use Nette\Application\UI\Form,
    Nette\Image;
use Nette\Mail\Message,
    Nette\Utils\Strings;
use Nette\Mail\SendmailMailer;
use Nette\Utils\DateTime;

class HelpdeskSimplePresenter extends \App\Presenters\BaseAppPresenter {

    public $id;
    public $cl_partners_book_id;

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

    
    /**
    * @inject
    * @var \MainServices\smsService
    */
    public $smsService;     
    
    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.hdpublic']);
        $this->mainTableName = "cl_partners_event";
    }

    public function actionDefault()
    {
        $request = $this->getHttpRequest();
        $response = $this->getHttpResponse();

        if (!$request->getCookie('fileSession'))
        {
            $section = $this->getSession('helpdeskPublic');
            $section->fileSession = \Nette\Utils\Random::generate(64,'A-Za-z0-9');
            $response->setCookie('fileSession', 1, new \Nette\Utils\DateTime('+ 1 day')); // nova session zítra
        }

    }
    
    public function renderDefault()
    {
        $this->template->myReadOnly = false;

    }
    
    public function renderSend()
    {

    }    
    

  protected function createComponentEdit($name)
    {	
        $form = new Form($this, $name);
	    $form->addHidden('id',NULL);

		$form->addText('work_label', $this->translator->translate('work_label'), 50, 200)
            ->setRequired($this->translator->translate('Popis_je_nutné_vyplnit'))
			->setHtmlAttribute('placeholder',$this->translator->translate('work_labelPh'));

	    $form->addTextArea('description_original', $this->translator->translate('description_original'), 100,15 )
			->setHtmlAttribute('placeholder',$this->translator->translate('description_originalPh'));

	    $form->addRadioList('duration', 'Délka', [15 => '15 minut', 30 => '30 minut', 60 => '1 hodina', 120 => '2 hodiny', 180 => '3 hodiny', 240 => '4 hodiny'])
                ->setDefaultValue(30);
	   // dump($form['duration']->getValue());

	    if ($defData = $this->PartnersEventManager->find($this->id))
            $arrPartners = $this->PartnersManager->findAll()->where('id = ?', $defData['cl_partners_book_id'])->fetchPairs('id','company');
	    else
	        $arrPartners = [];

        $form->addSelect('cl_partners_book_id', $this->translator->translate('cl_partners_book'),$arrPartners)
            ->setHtmlAttribute('data-urlajax', $this->link('getPartners!'))
            ->setHtmlAttribute('lang','cs')
            ->setHtmlAttribute('data-url-update-partner-in-form', $this->link('updatePartnerInForm!'));

        $arrPartnersCategory = $this->getCategory();

        $arrPartnersCategoryNew = $this->PartnersCategoryManager->findAll()->order('def_cat DESC')->limit(1)->fetch();
        $form->addSelect('cl_partners_category_id',  $this->translator->translate('Důležitost'),$arrPartnersCategory)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_kategorii_důležitosti_požadavku'))
            ->setRequired( $this->translator->translate('Kategorie_důležitosti_musí_být_vybrána'))
            ->setHtmlAttribute('data-url-category',$this->link('getCategory!'))
            ->setHtmlAttribute('data-partners_book_id',$form['cl_partners_book_id']->getHtmlId())
            ->setDefaultValue($arrPartnersCategoryNew['id'])
            ->setHtmlAttribute('class','chzn-select');

        //            ->setHtmlAttribute('data-event_method_id',$form['cl_partners_event_method_id']->getHtmlId())

        $now = new DateTime();
        $form->addText('date_rcv', $this->translator->translate('Datum_přijetí:'), 0, 16)
            ->setDefaultValue($now->format('d.m.Y H:i'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Přijato'));

        $form->addCheckbox('public', $this->translator->translate('Veřejné'))
            ->setHtmlAttribute('title', $this->translator->translate('Klient_bude_informován_emailem_a_událost_pro_něj_bude_viditelná.'));
        $form->addCheckbox('finished', $this->translator->translate('Hotovo'));
        $form->addCheckbox('make_task', $this->translator->translate('Vytvořit_nový_úkol'));
        $form->addCheckbox('payment', $this->translator->translate('Placeně'));

        $arrTaskCategory = $this->TaskCategoryManager->findAll()->order('label')->fetchPairs('id','label');
        $form->addSelect('cl_task_category_id', $this->translator->translate('Druh_úkolu'),$arrTaskCategory)
            ->setHtmlAttribute('data-placeholder',$this->translator->translate('Druh_úkolu'))
            ->setHtmlAttribute('placeholder',$this->translator->translate('Druh_úkolu'));


        $arrUsers['Aktivní'] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 0')->fetchPairs('id','name');
        $arrUsers['Neaktivní'] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 1')->fetchPairs('id','name');
        $form->addSelect('cl_task_users_id',  $this->translator->translate('Pracovník:'), $arrUsers)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_pracovníka_úkolu'))
            ->setPrompt( $this->translator->translate('Zvolte_přiděleného_pracovníka'));


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
        $data = $form->values;
        $data = $this->updatePartnerId($data);
        //$this->redrawControl('content');
        if ($data['cl_partners_book_id'] == NULL || $data['cl_partners_book_id'] == 0)
        {
            $form->addError($this->translator->translate('Partner_musí_být_vybrán'));
        }
        $this->redrawControl('content');
    }           
    
    public function stepBack()
    {	    
		$this->presenter->redirect(substr($this->presenter->name,strpos($this->presenter->name, ":") + 1).":default");

    }		
    
    public function SubmitEditSubmitted(Form $form)
   {
       $data = $form->getHttpData();
      //dump($data);
      // die;
       if ($form['save']->isSubmittedBy()) {

           $tmpData = [];
           if ($tmpDef = $this->PartnersEventTypeManager->findAll()->where('default_event OR main_event')->order('main_event DESC')->fetch())
               $tmpDefId = $tmpDef->id;
           else
               $tmpDefId = NULL;

           $tmpData['cl_partners_event_type_id']    = $tmpDefId;
           $tmpData['date_rcv']                     = date('Y-m-d H:i',strtotime($data['date_rcv']));
           $tmpData['cl_partners_book_id']          = $data['cl_partners_book_id'];
           $tmpData['cl_task_category_id']          = $data['cl_task_category_id'];
           $tmpData['work_label']                   = $data['work_label'];
           $tmpData['description_original']         = $data['description_original'];
           $tmpData['work_time']                    = $data['duration'];
           if (isset($data['finished']))
                $tmpData['finished'] = $data['finished'] == 'on' ? 1 : 0;
           else
               $tmpData['finished'] = 0;

           if (isset($data['public']))
                $tmpData['public'] = $data['public'] == 'on' ? 1 : 0;
           else
               $tmpData['public']  = 0;

           if (isset($data['payment']))
               $tmpData['payment'] = $data['payment'] == 'on' ? 1 : 0;
           else
               $tmpData['payment'] = 0;


           $tmpTaskUserId = ($data['cl_task_users_id'] == '') ? NULL : $data['cl_task_users_id'];
           unset($data['cl_task_users_id']);

           $tmpData['cl_partners_category_id']      = $data['cl_partners_category_id'];
           $tmpData['cl_users_id']                  = $this->user->getId();

           $nSeries = $this->NumberSeriesManager->getNewNumber('partners_event',NULL,NULL, NULL);
           $tmpData['cl_number_series_id']      = $nSeries['id'];
           $tmpData['event_number']             = $nSeries['number'];

           //bdump($tmpData);
           $tmpStatus = 'partners_event';
           if ($nStatus = $this->StatusManager->findAll()->where('status_use = ? AND s_new = ?',$tmpStatus,1)->fetch())
               $tmpData['cl_status_id'] = $nStatus->id;

           //bdump($tmpData);

           $rowData = $this->DataManager->insert($tmpData);
           $tmpEvent['cl_partners_event_id']    = $rowData['id'];
           $tmpEvent['cl_users_id']             = $this->user->getId();
           $tmpEvent['date']                    = date('Y-m-d H:i',strtotime($data['date_rcv']));
           $tmpEvent['work_time']               = $data['duration'];
           $tmpEvent['work_time_hours']         = (int)($data['duration'] / 60);
           $tmpEvent['work_time_minutes']       = (($data['duration'] / 60) - (int)($data['duration'] / 60)) * 60 ;

           $rowData2 = $this->DataManager->insert($tmpEvent);

           //09.04.2017 - pair uploaded files with cl_partners_event_id by session
           $section = $this->getSession('helpdeskPublic');
           //je vloženo, teď musíme přesunout soubory přiřazené klientovi k události.
           //'cl_partners_book_id' => $this->partner_id,
           $tmpFiles = $this->FilesManager->findAllTotal()->
                               where(array('cl_users_id' => $this->user->getId(),
                                   'file_session' => $section->fileSession));
           foreach ($tmpFiles as $one) {
               $newData = array();
               $newData['cl_partners_book_id'] = NULL;
               $newData['cl_center_id'] = NULL;
               $newData['file_session'] = NULL;
               $newData['cl_partners_event_id'] = $rowData->id;

               //04,02,2022 - move physical files

               $dataFolder  = $this->CompaniesManager->getDataFolder($one['cl_company_id']);
               $subFolder   = $this->ArraysManager->getSubFolder([], 'cl_users_id');
               $subFolder2  = $this->ArraysManager->getSubFolder([], 'cl_partners_event_id');
               $srcFile     =  $dataFolder . '/' . $subFolder . '/' . $one['file_name'];
               $destFile    =  $dataFolder . '/' . $subFolder2 . '/' . $one['file_name'];
               copy($srcFile, $destFile);
               unlink($srcFile);
               //$file->move($destFile);
               $one->update($newData);
           }
           //28.02.2022 - TH make task
           if (isset($data['make_task']) && $data['make_task'] == 'on'){
                $newRow = $this->TaskManager->CreateTaskFromHD($rowData->id, $tmpTaskUserId);
                $this->sendWorkerEmail($newRow);
                $this->PairedDocsManager->insertOrUpdate(['cl_company_id' => $newRow['cl_company_id'], 'cl_task_id' => $newRow['id'], 'cl_partners_event_id' => $rowData['id']]);
           }

           $this->flashMessage('Záznam byl uložen.', 'success');
       }
       $this->redirect("HelpdeskSimple:default");
       $this->redrawControl('content');

	   		
   }

   private function sendWorkerEmail($dataSource){
       $tmpEmail = '';
       $tmpEvent = $this->TaskManager->find($dataSource['id']);
       if (!is_null($tmpEvent['cl_users_id']))
       {
           $tmpEmail = $this->validateEmail($tmpEvent->cl_users->email);
           if (empty($tmpEmail))
               $tmpEmail = $this->validateEmail($tmpEvent->cl_users->email2);

           if (empty($tmpEmail)){
               $this->flashMessage('Uživatel nemá zadán platný email.', 'warning');
               return;
           }

       }

       if ($tmpEmail != '')
       {
           $emailTo = [0 => $tmpEvent->cl_users->name.' <'.$tmpEmail.'>'];
           $data = [];
           $emails = implode(';', $emailTo);
           $data['singleEmailTo'] = $emails;
           $data['singleEmailFrom'] = $this->settings->name.' <'.$this->settings->email.'>';
           //$tmpEmlText = $this->EmailingTextManager->getEmailingText('','','',$this->settings->hd6_emailing_text_id);
           //$data['subject'] = '['.$dataSource['event_number'].']['.$tmpEmlText['subject'].'] '.$dataSource['work_label'];
           $template = $this->createTemplate()->setFile(__DIR__.'/../../templates/Emailing/email.latte');
           //$template->body = $tmpEmlText['body'];
           //$link = $this->link('//showBsc!', array('id' => $tmpEvent->id, 'copy' => false));
           $files = $this->FilesManager->findAll()->where('cl_task_id = ?', $tmpEvent['id'])->count();

           $link = $this->link('//:Application:Task:edit', ['id' => $tmpEvent->id, 'copy' => false]);
           $tmpCompanyName = (!is_null($tmpEvent->cl_partners_book)) ? $tmpEvent->cl_partners_book['company'] : '';
           $data['subject'] = 'Nový úkol ' . $tmpCompanyName;
           $template->body  = '<table><tr><td>Číslo úkolu: </td><td>' .  $tmpEvent['task_number'] . '</td></tr>' .
                              '<tr><td>Firma: </td><td>' . $tmpCompanyName . '</td></tr>' .
                              '<tr><td>Datum úkolu: </td><td>' . $tmpEvent['task_date']->format('d.m.Y') . '</td></tr>' .
                              '<tr><td>Počet příloh: </td><td>' . $files . '</td></tr>' .
                              '<tr><td>Zadání: </td><td>' . $tmpEvent['description'] . '</td></tr>' .
                              '<tr><td>Odkaz do hlepdesku:</td><td><a href="'.$link.'" title="Otevře záznam v helpdesku">'.$link.'</a></td></tr>' .
                              '</table>';

           $data['body']	= $template;
           //bdump($data);
           //die;
           //send email
           $this->emailService->sendMail2($data);

           //save to cl_emailing
           $this->EmailingManager->insert($data);


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


    protected function createComponentFiles()
    {
        $parent_value = $this->user->getId();
        $parent_data = 'cl_users_id';
        $parent_manager = $this->UsersManager;
        $user_id = $this->user->getId();
        $cl_company_id = $this->settings->id;
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
        }
    }

    /*
         * Set category in form
         */
    public function getCategory()
    {
        $curr_name = $this->settings->cl_currencies->currency_name;
        $arrPartnersCategoryNew = $this->PartnersCategoryManager->findAll()->select('id,CONCAT(category_name," (","sazba: ",hour_tax," '.$curr_name.' / hodina",")") AS name')->order('name')->fetchPairs('id','name');

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


}
