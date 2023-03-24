<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;
use App\Controls;

class UsersPresenter extends \App\Presenters\BaseListPresenter {


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
    * @var \App\Model\UsersManager
    */
    public $DataManager;    

    /**
    * @inject
    * @var \App\Model\ArraysManager
    */
    public $ArraysManager;        
    
    /**
    * @inject
    * @var \App\Model\UserManager
    */
    public $UserManager;       

    /**
    * @inject
    * @var \App\Model\UsersRoleManager
    */
    public $UsersRoleManager;


    /**
     * @inject
     * @var \App\Model\UsersGroupsManager
     */
    public $UsersGroupsManager;

    /**
     * @inject
     * @var \App\Model\UsersLogManager
     */
    public $UsersLogManager;
    
    /**
    * @inject
    * @var \App\Model\CompaniesAccessManager
    */
    public $CompaniesAccessManager;   

    /**
    * @inject
    * @var \App\Model\CompaniesManager
    */
    public $CompaniesManager;    
        
    /**
    * @inject
    * @var \App\Model\TablesSettingManager
    */
    public $TablesSettingManager;       
    
    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.Users']);
        $this->mainTableName = 'cl_users';
        $this->dataColumns = ['name' => $this->translator->translate('Jméno_a_příjmení'),
                        'nick_name' => $this->translator->translate('Přezdívka'),
                        'email' => $this->translator->translate('Email_/_přihlašovací_jméno'),
                        'email2' => $this->translator->translate('Alternativní_email'),
                        'phone' => $this->translator->translate('Telefon'),
                        'cl_users_role.name' => $this->translator->translate('Práva'),
                        'cl_users_groups_id' => [$this->translator->translate('Počet_skupin'), 'size' => 5, 'format' => 'integer',  'function' => 'getGroupsCount',  'function_param' => ['id']],
                        'after_login' => [$this->translator->translate('Po_přihlášení'), 'function' => 'getAfterLogin'],
                        'event_manager' => [$this->translator->translate('Správce_helpdesku'), 'format' => 'boolean'],
                        'store_manager' => [$this->translator->translate('Správce_skladu'), 'format' => 'boolean'],
                        'b2b_manager' => [$this->translator->translate('Správce_B2B'), 'format' => 'boolean'],
                        'estate_manager' => [$this->translator->translate('Správce_majetku'), 'format' => 'boolean'],
                        'not_active' => [$this->translator->translate('Neaktivní'), 'format' => 'boolean'],
                        'email_confirmed' => [$this->translator->translate('Email_ověřen'), 'format' => 'boolean'],
                        'lang' => [$this->translator->translate('Jazyk'), 'format' => 'text', 'function' => 'getLang'],
                        'id' => [$this->translator->translate('Admin_firmy'),'format' => 'boolean', 'function' => 'isCompanyAdmin'],
                        'main_company_id' => [$this->translator->translate('Vlastní_firma'),'format' => 'text', 'function' => 'getMainCompanyName',  'function_param' => ['main_company_id']],
                        'last_login' => [$this->translator->translate('Poslední_přihlášení'),'format' => 'datetime'],
                        'count_login' => [$this->translator->translate('Počet_přihlášení'),'format' => 'integer'],
                        'work_rate' => [$this->translator->translate('Hodinová_sazba'),'format' => 'currency'],
                        'created' => [$this->translator->translate('Vytvořeno'), 'format' => 'datetime'],'create_by' => $this->translator->translate('Vytvořil'),
                        'changed' => [$this->translator->translate('Změněno'), 'format' => 'datetime'],'change_by' => $this->translator->translate('Změnil')];
        $this->FilterC = 'UPPER(email) LIKE ? OR UPPER(name) LIKE ?';
        //$this->formatColumns = array('last_login' => "datetime");
        $this->filterColumns = ['nick_name' => 'autocomplete', 'email' => 'autocomplete', 'email2' => 'autocomplete',
                'phone' => 'autocomplete', 'cl_users_role.name' => 'autocomplete', 'cl_users_groups.name' => 'autocomplete'];
        $this->DefSort = 'email';
        $this->toolbar = [1 => ['url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nový_záznam'), 'class' => 'btn btn-primary']];
        $this->userFilterEnabled = TRUE;
        $this->userFilter = ['cl_users.name', 'nick_name', 'email', 'email2', 'phone', 'cl_users_role.name'];
        $this->defValues = ['main_company_id' => $this->UserManager->getCompany($this->user->getId())->id];
        $this->actionList = [
                            1 => ['type' => 'handle', 'url' => $this->link('copySettings!'), 'label' => 'Přepsat_nastavení', 'title' => 'Přepíše_nastavení_tabulek_nastavením_vybraného_uživatele', 'class' => ''],
        ];
    }	
    
    public function renderDefault($page_b = 1,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal, $cxs)  {
	    parent::renderDefault($page_b,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal,$cxs);

    }
        
    public function renderEdit($id,$copy,$modal){
        $defData = $this->DataManager->findOneBy(['id' => $id]);

        if ($defData['cl_company_id'] != $defData['main_company_id'] && !is_null($defData['main_company_id'])) {
            //$this->forceRO = true;
            //}
            foreach ($this['edit']->getControls() as $control) {
                if ($control->name != 'back' && $control->name != 'send' && $control->name != 'cl_users_role_id' && $control->name != 'id') {
                    $control->controlPrototype->readonly = 'readonly';
                    if ($control->controlPrototype->attrs['type'] == 'submit' ||
                            $control->controlPrototype->attrs['type'] == NULL ||
                            $control->controlPrototype->attrs['type'] == 'checkbox' ) {
                        $control->setDisabled(TRUE);
                    }
                }
            }


        }

		parent::renderEdit($id,$copy,$modal);	
		if ($defData)
        {
            $defDataNew = $defData->toArray();
            if ($tmpCA = $this->CompaniesAccessManager->findAll()->where('cl_users_id = ? AND cl_company_id = ?', $defDataNew['id'], $defDataNew['cl_company_id'])->fetch())
            {
                $defDataNew['admin_company'] =  $tmpCA->admin;
            }
            $arrUsersGroups = json_decode($defData['cl_users_groups_id'], true);
//            dump(count($arrUsersGroups));

            if (count($arrUsersGroups) > 0)
                $defDataNew['cl_users_groups_id'] = json_decode($defData['cl_users_groups_id'], true);
            else
               unset($defDataNew['cl_users_groups_id']);

            $this['edit']->setValues($defDataNew);


        }

    }
    
    
    protected function createComponentEdit($name)
    {
        $defData = $this->DataManager->findOneBy(array('id' => $this->id));
        $form = new Form($this, $name);
	    //$form->setMethod('POST');
	    $form->addHidden('id',NULL);
		$form->addText('email', $this->translator->translate('Email_nebo_login'), 40, 40)
			->setHtmlAttribute('placeholder',$this->translator->translate('Email'));

		$form->addText('email2', $this->translator->translate('Alternativní_email'), 40, 40)
			->setHtmlAttribute('placeholder',$this->translator->translate('Email'));
		$form->addText('work_rate', $this->translator->translate('Hodinová_sazba'), 5, 5)
			->setHtmlAttribute('placeholder',$this->translator->translate('Sazba_práce'));
		$form->addText('name', $this->translator->translate('Jméno_a_příjmení'), 40, 40)
			->setHtmlAttribute('placeholder',$this->translator->translate('Jméno_a_příjmení'));
		$form->addText('nick_name', $this->translator->translate('Přezdívka'), 40, 40)
			->setHtmlAttribute('placeholder',$this->translator->translate('Přezdívka_uživatele'));
		$form->addText('phone', $this->translator->translate('Telefon'), 40, 40)
			->setHtmlAttribute('placeholder',$this->translator->translate('Telefon'));
		$form->addText('authorize_pin', $this->translator->translate('Autorizační_PIN'), 6, 6)
			->setHtmlAttribute('placeholder',$this->translator->translate('PIN'));
		$form->addCheckbox('event_manager',$this->translator->translate('Správce_helpdesku'))
				->setHtmlAttribute('class', 'items-show');
        $form->addCheckbox('estate_manager',$this->translator->translate('Správce_majetku'))
            ->setHtmlAttribute('class', 'items-show');
        $form->addCheckbox('email_confirmed',$this->translator->translate('Email_ověřen'))
            ->setHtmlAttribute('class', 'items-show');

        $form->addCheckbox('b2b_manager',$this->translator->translate('Správce_B2B'))
            ->setHtmlAttribute('class', 'items-show');
        $form->addCheckbox('store_manager',$this->translator->translate('Správce_skladu'))
				->setHtmlAttribute('class', 'items-show');			
        $form->addCheckbox('admin_company',$this->translator->translate('Administrátor_firmy'))
              ->setHtmlAttribute('class', 'items-show');
        $form->addCheckbox('not_active',$this->translator->translate('Neaktivní_uživatel'))
				->setHtmlAttribute('class', 'items-show');			                		
        $form->addCheckbox('quick_sums',$this->translator->translate('Zobrazovat_rychlé_součty'))
				->setHtmlAttribute('class', 'items-show');
        $form->addCheckbox('companies_manager',$this->translator->translate('Správce_firem'))
                ->setHtmlAttribute('class', 'items-show');

        $form->addCheckbox('bsc_enabled',$this->translator->translate('Nahoře_seznam_dole_obsah_dokladu'))
            ->setHtmlAttribute('class', 'items-show');

        $form->addSelect('lang', $this->translator->translate('Jazyk'), $this->ArraysManager->getLanguages())
            ->setHtmlAttribute('data-placeholder',$this->translator->translate('Jazyk_uživatele'))
            ->setPrompt($this->translator->translate('Zvolte_jazyk'));

		$form->addSelect('after_login', $this->translator->translate('Po_přihlášení'), $this->ArraysManager->getAfterLoginArr())
			    ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_modul_který_se_otevře_po_přihlášení'))
			    ->setPrompt($this->translator->translate('Zvolte_modul'));
	    $form->addPassword('password',$this->translator->translate('Heslo'))
		    ->setHtmlAttribute('class','form-control')
		    ->setHtmlAttribute('autocomplete', 'new-password')
		    ->setHtmlAttribute('placeholder',$this->translator->translate('Heslo'))
		    ->addCondition($form::FILLED)
		    ->addRule($form::MIN_LENGTH, $this->translator->translate('Heslo_je_příliš_krátké_Musí_mít_alespoň_%d_znaků'), 5)
		    ->addRule($form::PATTERN, $this->translator->translate('Heslo_je_příliš_jednoduché_Musí_obsahovat_číslici'), '.*[0-9].*')
		    ->addRule($form::PATTERN, $this->translator->translate('Heslo_je_příliš_jednoduché_Musí_obsahovat_malé_písmeno'), '.*[a-z].*');
	    $form->addPassword('password2', $this->translator->translate('Heslo_znovu'))
		    ->setHtmlAttribute('class','form-control')
		    ->setHtmlAttribute('autocomplete', 'new-password')
		    ->setHtmlAttribute('placeholder',$this->translator->translate('Heslo_znovu'));

        if ($defData['cl_company_id'] == $defData['main_company_id'] || is_null($defData['main_company_id'])) {
            $form['email']->setRequired($this->translator->translate('Zadejte_prosím_přihlašovací_email_nebo_jméno'));
            $form['email']->setRequired($this->translator->translate('Zadejte_prosím_jméno_a_příjmení'));
            $form['password2']->addConditionOn($form['password'],$form::FILLED)
                            ->addRule($form::EQUAL, $this->translator->translate('Hesla_se_neshodují'),$form['password'])
                            ->setRequired($this->translator->translate('Prosím_zadejte_znovu_heslo'));
        }



	    //$arrRoles = array('' => 'host', 'admin' => 'administrátor', 'user' => 'uživatel');
	    $arrRoles = $this->UsersRoleManager->findAll()->fetchPairs('id', 'name');
	    $form->addSelect('cl_users_role_id', $this->translator->translate("Skupina_oprávnění"),$arrRoles)
		    ->setRequired($this->translator->translate('Skupina_oprávnění_musí_být_zvolena'))
		    ->setPrompt($this->translator->translate('Zvolte_skupinu'))
		    ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_skupinu_oprávnění_uživatele'))
		    ->setHtmlAttribute('class','chzn-select');

        $arrGroups = $this->UsersGroupsManager->findAll()->fetchPairs('id', 'name');
        $form->addMultiSelect('cl_users_groups_id', $this->translator->translate("Skupina_uživatelů"),$arrGroups)
            ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_skupinu_uživatele'))
            ->setHtmlAttribute('class','chzn-select');


		$form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class','btn btn-success');
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
		    ->setHtmlAttribute('class','btn btn-warning')
		    ->setValidationScope([])
		    ->onClick[] = array($this, 'stepBack');	    	    
	//	    ->onClick[] = callback($this, 'stepSubmit');
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

            $defData = $this->DataManager->findOneBy(array('id' => $this->id));
            if ($defData['cl_company_id'] != $defData['main_company_id'] && !is_null($defData['main_company_id'])) {
                $defData->update(array('cl_users_role_id' => $data['cl_users_role_id']));
                $this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
                $this->redrawControl('content');
                $this->redirect('default');
            }else {
                //if user email exists on different record, make error on form
                if ($this->UserManager->getAll()->where('email = ? AND id != ?', $data['email'], $data['id'])->fetch() != NULL) {
                    $form['email']->addError($this->translator->translate('Uživatel_se_zadaným_emailem_/_loginem_již_existuje'));
                    $this->redrawControl('content');
                    return;
                }

                if (empty($data['password']))
                    unset($data['password']);

                unset($data['password2']);
                $tmpAdminCompany = $data['admin_company'];
                unset($data['admin_company']);

                try {
                    $data['cl_company_id'] = $this->UserManager->getCompany($this->user->getId())->id;
                    $data['cl_users_groups_id'] = json_encode($data['cl_users_groups_id']);

                    //$this->DataManager->update($data);
                    $this->UserManager->updateUser($data);
                    if ($this->user->identity->id == $data['id']) {
                        $this->user->identity->quick_sums = $data['quick_sums'];
                        $this->user->identity->bsc_enabled = $data['bsc_enabled'];
                    }

                    //reset all users to not be an admin
                    if ($tmpAdminCompany == 1) {
                        $tmpCA = $this->CompaniesAccessManager->findAll();
                        foreach ($tmpCA as $one) {
                            $one->update(array('admin' => 0));
                        }
                    }
                    $dataCA = array();
                    $dataCA['cl_company_id'] = $data['cl_company_id'];
                    $dataCA['cl_users_id'] = $data['id'];
                    $dataCA['admin'] = $tmpAdminCompany;

                    if ($row = $this->CompaniesAccessManager->findAll()->where('cl_users_id = ? AND cl_company_id = ?', $data['id'], $data['cl_company_id'])->fetch()) {
                        $dataCA['id'] = $row->id;
                        $this->CompaniesAccessManager->update($dataCA);
                    } else {

                        //$dataCA['role'] = $data['role'];
                        $this->CompaniesAccessManager->insert($dataCA);
                    }

                    $this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
                    $this->redrawControl('content');
                    $this->redirect('default');

                } catch (Exception $e) {
                    $errorMess = $e->getMessage();
                    $this->flashMessage($errorMess, 'danger');
                    $this->redrawControl('flash');
                }
            }
        }

    }
    
    public function actionEmailConfirmation($key,$email)
    {
	//dump($key);
	//die;
	
	if ($this->UserManager->confirmEmail($email, $key))
	{
	    $this->flashMessage($this->translator->translate('Email_byl_úspěšně_potvrzen'), 'success');
	    $this->getUser()->getIdentity()->email_confirmed = 1;

	}
	else
	    $this->flashMessage($this->translator->translate('Bohužel_email_nebyl_potvrzen'), 'danger');
	
	//$this->redirect(':Login:Homepage:default');	
	$this->redirect(':Application:Homepage:default');	
    }
	
	/*check if actual tariff enable new user
	 * 
	 */
	public function beforeNew()
	{

	    /* not used anymore number of allowed users is in cl_users_license.license
		if ($tmpUser = $this->DataManager->find($this->getUser()->id))
		{
			if (!is_null($tmpUser->cl_users_license_id))
			{
				$tmpLicense = $this->DataManager->findAll()->where('cl_users_license_id = ?', $tmpUser->cl_users_license_id)->count();
				if ($tmpLicense < $this->UserManager->trfUsers($this->getUser()->id))
				{
					$result = TRUE;
				}else{
					$result = FALSE;
					$this->flashMessage($this->translator->translate('Tarif') . $this->UserManager->trfName($this->getUser()->id) . $this->translator->translate('nepovoluje_více_uživatelů'), 'danger' );
				}
			}  else {
					$result = FALSE;
					$this->flashMessage($this->translator->translate('Tarif ') . $this->UserManager->trfName($this->getUser()->id) . $this->translator->translate('nepovoluje_více_uživatelů'), 'danger' );
			}
		}
		*/

        $result = TRUE;
		$this->redrawControl('content');
		return $result;
	}

	/**
	 * prepare default values especialy cl_users_license.id
	 * @param type $defValues
	 * @param type $data
	 */
	public function DataProcessMain($defValues, $data) 
	{

	    //bdump($data,'data');
		if ($tmpUser = $this->DataManager->find($this->getUser()->id))
		{
		//    bdump($tmpUser,'tmpUser');
		    $defValues['cl_users_license_id'] = $tmpUser['cl_users_license_id'];
		}
		//bdump($data,'datanew');
		return $defValues;
	}

	
  /**User image form
     * @return Form
     */
    protected function createComponentUploadImageForm()
    {
		$form = new \Nette\Application\UI\Form();
		$form->getElementPrototype()->class = 'dropzone';
		$form->getElementPrototype()->id = 'imageDropzone';
		$form->addHidden('type',0); //image
		$form->onSuccess[] = [$this, 'processUploadImageForm'];
		return $form;
    }    

  /**User stamp form
     * @return Form
     */
    protected function createComponentUploadStampForm()
    {
		$form = new \Nette\Application\UI\Form();
		$form->getElementPrototype()->class = 'dropzone';
		$form->getElementPrototype()->id = 'stampDropzone';
		$form->addHidden('type',1); //stamp
		$form->onSuccess[] = [$this, 'processUploadImageForm'];
		return $form;
    }        
    
    /**upload method for process image
     * @param Form $form
     */
    public function processUploadImageForm(Form $form)
    {
		$formValues = $form->getValues();
		$file = $form->getHttpData($form::DATA_FILE, 'file');
			//try {	
		// spracovanie obrazku 
		$image = \Nette\Utils\Image::fromFile($file);
		$image->resize(200, NULL);

		
		$this->processImage($image, $formValues->type);

			//} catch (\Exception $e) {
			//     $this->flashMessage($e->getMessage());
			//}
    }    
	
	private function processImage($image, $type)
	{
		//if company have already logo set, delete file with this logo
		$tmpUser=$this->UserManager->getUserById($this->id);
		if ($type == 0){
		    $storedName = $tmpUser->user_image;
		}elseif ($type == 1){
		    $storedName = $tmpUser->picture_stamp;
		}
		
		if ($storedName != '')
		{
			$fileDel = __DIR__."/../../../data/pictures/".$storedName;
			if (file_exists($fileDel)) 
			{
				unlink ($fileDel);
			}
		}
		//next check if file exists, if yes generate new filename
		$destFile=NULL;	
		while(file_exists($destFile) || is_null($destFile))
		{
		  $fileName = \Nette\Utils\Random::generate(64,'A-Za-z0-9');
			  $destFile=__DIR__."/../../../data/pictures/".$fileName.'.jpg';		    
		}
		$image->save($destFile);
		
		$value['id'] = $this->id;
		if ($type == 0){
		    $value['user_image'] = $fileName.'.jpg';	
		}elseif ($type == 1){
		    $value['picture_stamp'] = $fileName.'.jpg';	
		}
		$this->UserManager->updateUser($value);		
		
	}	
	
   public function handleRemoveUserImage()
    {
		$tmpUser=$this->UserManager->getUserById($this->id);
		$storedName = $tmpUser->user_image;
		if ($storedName != '')
		{
			$fileDel = __DIR__."/../../../data/pictures/".$storedName;
			if (file_exists($fileDel)) 
			{
				unlink ($fileDel);
				$value = array();
				$value['id'] = $this->id;
				$value['user_image'] = '';
				$this->UserManager->updateUser($value);	    
			}
		}
		$this->redrawControl('imageLogo');
    }		

   public function handleRemoveUserStamp()
    {
		$tmpUser=$this->UserManager->getUserById($this->id);
		$storedName = $tmpUser->picture_stamp;
		if ($storedName != '')
		{
			$fileDel = __DIR__."/../../../data/pictures/".$storedName;
			if (file_exists($fileDel)) 
			{
				unlink ($fileDel);
				$value = array();
				$value['id'] = $this->id;
				$value['picture_stamp'] = '';
				$this->UserManager->updateUser($value);	    
			}
		}
		$this->redrawControl('imageStamp');
    }		    
	
    
    public function isCompanyAdmin($user_id)
    {
       if ($this->CompaniesManager->companyAdmin($this->user->getIdentity()->cl_company_id, $user_id[0]))
       {
           $result = TRUE;
       }else{
           $result = FALSE;
       }
       return $result;
    }

    public function getMainCompanyName($arr)
    {
        if (!is_null($arr['main_company_id']) && $data = $this->CompaniesManager->findAllTotal()->where('id = ?',$arr['main_company_id'])->fetch())
        {
            $result = $data['name'];
        }else{
            $result = '';
        }
        return $result;
    }
    
    public function getAfterLogin($type)
    {
        $result = $this->ArraysManager->getAfterLoginName($type);
        return $result;
    }

    public function getLang($lang)
    {
        $result = $this->ArraysManager->getLangName($lang);
        return $result;
    }

    protected function createComponentCompaniesGrid()
    {
        $tmpParentData = $this->DataManager->find($this->id);
        $adminId = $this->CompaniesManager->getAdminId($this->user->getIdentity()->cl_company_id);
        $arrCompanies = $this->CompaniesAccessManager->findAllTotal()->where('cl_users_id = ?', $adminId )->fetchPairs('cl_company_id','cl_company_id');
        $arrData = array('cl_company.name' => array($this->translator->translate('Firma'), 'format' => 'select', 'size' => 10,
                                                    'values' => $this->CompaniesManager->findAllTotal()
                                                        ->select('id,name')
                                                        ->where('id IN (?)', $arrCompanies)
                                                        ->order('name')->fetchPairs('id', 'name'))
        );

        //,
        //'company_name' => array('', 'format' => 'text', 'size' => 15, 'readonly' => true)
        //$tmpPrice = $tmpParentData->price_e2_vat;

        //$translator = clone $this->translator->setPrefix([]);
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->CompaniesAccessManager,
            $arrData,
            array(),
            $this->id,
            array('cl_users_id' => $this->id),//default values
            $this->DataManager,
            NULL,
            NULL,
            TRUE,
            array() //custom links
        );
        $control->setPaginatorOff();
        $control->setPricelistEnabled(false);
        $control->setEnableAddEmptyRow(true);
       // $control->setReadOnly(false);
        $control->setOrder('cl_company.name ASC');
        $control->setFilter(array('filter' => 'cl_users_id = '.$this->id));
        $control->setFindTotalEnabled();
        $adminId = $this->CompaniesManager->getAdminId($this->user->getIdentity()->cl_company_id);
        if ($adminId == $this->id){
            $control->setReadOnly();
        }
        $control->onChange[] = function () {
            //$this->updateSum();
        };
        return $control;
    }

    public function beforeDelete($lineId){
        if ($data = $this->CompaniesAccessManager->findAllTotal()->where('id = ?', $lineId)->fetch()){
            $adminId = $this->CompaniesManager->getAdminId($this->user->getIdentity()->cl_company_id);
            $arrCompanies = $this->CompaniesAccessManager->findAllTotal()->where('cl_users_id = ?', $adminId )->fetchPairs('cl_company_id','cl_company_id');
            if (array_key_exists($data['cl_company_id'], $arrCompanies)){
                $data->delete();
                return TRUE;
            }else{
                return FALSE;
            }

        }else{
            return FALSE;
        }

    }

    public function DataProcessListGrid($data){
        $adminId = $this->CompaniesManager->getAdminId($this->user->getIdentity()->cl_company_id);
        $arrCompanies = $this->CompaniesAccessManager->findAllTotal()->where('cl_users_id = ?', $adminId )->fetchPairs('cl_company_id','cl_company_id');
        if (array_key_exists($data['cl_company_id'], $arrCompanies)){
                $this->CompaniesAccessManager->findAllTotal()->where('id = ?', $data['id'])->update($data);
        }else{
            //force kill in case of threat
            die;
        }

        return($data);
    }

    public function handleCopySettings($dataId){
        $this->id = $dataId;
        $this->showModal('copySettings');
        $this->redrawControl('createDocs');
        //$this->redrawControl('content');
    }

    protected function createComponentCopySettingsForm($name)
    {
        $form = new Form($this, $name);
        //$form->setMethod('POST');
        //$form->addHidden('id', NULL);
        $arrUsers = $this->UserManager->getUsersAN($this->user->getIdentity()->cl_company_id);
        unset($arrUsers['Aktivní'][$this->id]);
        unset($arrUsers['Neaktivní'][$this->id]);
        $form->addSelect('cl_users_id', $this->translator->translate("Zdrojový_uživatel"), $arrUsers)
            ->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_zdrojového_uživatele'))
            ->setPrompt($this->translator->translate('Zvolte_zdrojového_uživatele'));
        $form->addSubmit('send', 'Zkopírovat nastavení')->setHtmlAttribute('class','btn btn-success');
        $form->addSubmit('back', 'Zpět')
            ->setHtmlAttribute('class','btn btn-warning')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBack2');
        $form->onSuccess[] = array($this,'SubmitCopySubmitted');
        return $form;
    }

    public function stepBack2()
    {
        $this->redirect('default');
    }

    public function SubmitCopySubmitted(Form $form)
    {
       // bdump($this->id);
       // die;
        $data=$form->values;
        //$data = $this->removeFormat($data);
        if ($form['send']->isSubmittedBy())
        {
            $source = $this->TablesSettingManager->findAll()->where('cl_users_id = ?', $data->cl_users_id);
            foreach ($source as $key => $one){
                $arrSource = $one->toArray();
                $tmpDest = $this->TablesSettingManager->findAll()->where('cl_users_id = ? AND table_name = ?', $this->id, $one['table_name'])->fetch();
                if ($tmpDest){
                    $arrSource['id'] = $tmpDest['id'];
                    $arrSource['cl_users_id'] = $this->id;
                    $this->TablesSettingManager->update($arrSource);
                }else{
                    unset($arrSource['id']);
                    $arrSource['cl_users_id'] = $this->id;
                    $this->TablesSettingManager->insert($arrSource);
                }
            }
            $this->flashMessage('Nastavení bylo zkopírováno.', 'success');
        }
        $this->hideModal('copySettings');
        $this->redrawControl('createDocs');
    }



    protected function createComponentHistoryLogin()
    {
        $tmpParentData = $this->DataManager->find($this->id);

        $arrData = array(
            'login_date_time' => array('Datum a čas přihlášení','format' => 'datetime','size' => 15,'readonly' => TRUE),
            'login_ip' => array('IP adresa','format' => 'text', 'size' => 10, 'readonly' => TRUE));

        $control = new Controls\ListgridControl(
            $this->translator,
            $this->UsersLogManager,
            $arrData,
            array(),
            $this->id,
            array(), //default data
            $this->DataManager,
            FALSE,
            FALSE,
            FALSE,
            array(), //custom links
            FALSE, //movable row
            NULL, //ordercolumn
            FALSE, //selectmode
            array(), //quicksearch
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE, //pricelistbottom
            TRUE, //readonly
            TRUE,  //nodelete
            TRUE, //enableSearch
            'login_date_time LIKE ? OR login_ip LIKE ?',
            NULL, //toolbar
            FALSE, //forceEnable
            FALSE, //paginatorOff
            array(), //colours conditions
            40, //pagelength
            'auto' //$containerHeight
        );
        $control->setPaginatorOn();
        // $control->setReadOnly(false);
        $control->setOrder('cl_users_log.login_date_time DESC');
        $control->setFindTotalEnabled();
        $control->setHideTimestamps(TRUE);
        $control->showHistory(FALSE);
        $control->onChange[] = function () {
            //$this->updateSum();
        };
        return $control;
    }


    protected function createComponentHistoryWork()
    {
        $tmpParentData = $this->DataManager->find($this->id);

        $arrData = array(
            'created' => array('Datum a čas změny','format' => 'datetime','size' => 15,'readonly' => TRUE),
            'table_name' => array('Změna v tabulce','format' => 'text', 'size' => 15, 'readonly' => TRUE),
            'parent_id' => array($this->translator->translate('Odkaz_na_záznam'), 'format' => "url", 'size' => 9, 'url' => 'from_value2', 'value_url' => 'parent_id', 'value_url2' => 'caller_info'),
            'caller_info' => array('Zdroj změny','format' => 'text', 'size' => 20, 'readonly' => TRUE),

        );

        $control = new Controls\ListgridControl(
            $this->translator,
            $this->HistoryManager,
            $arrData,
            array(),
            $this->id,
            array(), //default data
            $this->DataManager,
            FALSE,
            FALSE,
            FALSE,
            array(), //custom links
            FALSE, //movable row
            NULL, //ordercolumn
            FALSE, //selectmode
            array(), //quicksearch
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE, //pricelistbottom
            TRUE, //readonly
            TRUE,  //nodelete
            FALSE, //enableSearch
            '',
            NULL, //toolbar
            FALSE, //forceEnable
            FALSE, //paginatorOff
            array(), //colours conditions
            40, //pagelength
            'auto' //$containerHeight
        );
        $control->setPaginatorOn();
        // $control->setReadOnly(false);
        $control->setOrder('created DESC');
        $control->setFindTotalEnabled();
        $control->setHideTimestamps(TRUE);
        $control->showHistory(FALSE);
        $control->setFilter(array('filter' => 'caller_info LIKE ?', 'values' => '%presenter%'));
        $control->onChange[] = function () {
            //$this->updateSum();
        };
        return $control;
    }

    public function getGroupsCount($arrData){
        if (!is_null($arrData['id']) && $tmpData = $this->DataManager->find($arrData['id'])){
            $result = count(json_decode($tmpData['cl_users_groups_id'], true));
        }else{
            $result = '';
        }
        return $result;
    }




}
