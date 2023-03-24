<?php

namespace App\ApplicationModule\Presenters;


use http\Exception;
use Nette\Application\UI\Form;
use Nette\Utils\DateTime;
use \Nette\Http\IResponse;
use Nette\Utils\Json;
use App\Controls;
use Tracy\Debugger;





class SettingsPresenter extends \App\Presenters\BaseAppPresenter
{
	
	public $activeTab, $activeTab2, $archives = array();

	//needed for listgrid components
	public $myReadOnly = FALSE;
	
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
	 * @var \App\Model\CompaniesManager
	 */
	public $CompaniesManager;

    /**
     * @inject
     * @var \App\Model\OrderManager
     */
    public $OrderManager;

    /**
	 * @inject
	 * @var \App\Model\PriceListManager
	 */
	public $PriceListManager;
	
	/**
	 * @inject
	 * @var \App\Model\PriceListLimitsManager
	 */
	public $PriceListLimitsManager;

    /**
     * @inject
     * @var \App\Model\InvoicePaymentsManager
     */
    public $InvoicePaymentsManager;

    /**
     * @inject
     * @var \App\Model\InvoiceArrivedPaymentsManager
     */
    public $InvoiceArrivedPaymentsManager;

    /**
     * @inject
     * @var \App\Model\InvoiceAdvancePaymentsManager
     */
    public $InvoiceAdvancePaymentsManager;

	/**
	 * @inject
	 * @var \App\Model\InvoiceManager
	 */
	public $InvoiceManager;

    /**
     * @inject
     * @var \App\Model\InvoiceAdvanceManager
     */
    public $InvoiceAdvanceManager;

    /**
     * @inject
     * @var \App\Model\InvoiceAdvanceItemsManager
     */
    public $InvoiceAdvanceItemsManager;

    /**
     * @inject
     * @var \App\Model\InvoiceItemsManager
     */
    public $InvoiceItemsManager;


    /**
     * @inject
     * @var \App\Model\InvoiceArrivedManager
     */
    public $InvoiceArrivedManager;
	
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
	 * @var \App\Model\StoreOutManager
	 */
	public $StoreOutManager;
	
	/**
	 * @inject
	 * @var \App\Model\RatesVatManager
	 */
	public $RatesVatManager;
	
	/**
	 * @inject
	 * @var \App\Model\PaymentTypesManager
	 */
	public $PaymentTypesManager;
	
	/**
	 * @inject
	 * @var \App\Model\CountriesManager
	 */
	public $CountriesManager;
	
	/**
	 * @inject
	 * @var \App\Model\StorageManager
	 */
	public $StorageManager;
	
	/**
	 * @inject
	 * @var \App\Model\StoreManager
	 */
	public $StoreManager;
	
	/**
	 * @inject
	 * @var \App\Model\PartnersManager
	 */
	public $PartnersManager;
	
	
	/**
	 * @inject
	 * @var \App\Model\PartnersCoopManager
	 */
	public $PartnersCoopManager;
	
	/**
	 * @inject
	 * @var \App\Model\BankAccountsManager
	 */
	public $BankAccountsManager;

    /**
     * @inject
     * @var \App\Model\BankTransManager
     */
    public $BankTransManager;

	/**
	 * @inject
	 * @var \App\Model\CurrenciesManager
	 */
	public $CurrenciesManager;
	
	/**
	 * @inject
	 * @var \App\Model\UsersLicenseManager
	 */
	public $UsersLicenseManager;

    /**
     * @inject
     * @var \App\Model\ArchiveManager
     */
    public $ArchiveManager;


    /**
     * @inject
     * @var \App\Model\ArchivesManager
     */
    public $ArchivesManager;


    /**
     * @inject
     * @var \Nette\Http\IResponse
     */
    public $httpResponse;


    /**
	 * @inject
	 * @var \MainServices\smsService
	 */
	public $smsService;

    /**
     * @inject
     * @var \App\Model\PairedDocsManager
     */
    public $pairedDocsManager;

    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.Settings']);
    }



    public function renderDefault($activeTab = NULL, $activeTab2 = NULL)
	{
		$userTmp = $this->UserManager->getUserById($this->getUser()->id);

		$userCompany1 = $this->CompaniesManager->getTable()->where('cl_company.id', $userTmp->cl_company_id)->fetch();
        //dump($userCompany1);
		$this->template->usersLicenseOrder = $this->UsersLicenseManager->findAll()->
					where(array('cl_users_id' => $this->getUser()->id))->
					where('license_start IS NULL')->order('created DESC');
		$this->template->usersLicenseActive = $this->UsersLicenseManager->findAll()->
					where(array('cl_users_id' => $this->getUser()->id))->
					where('license_end >= CURDATE()')->order('created DESC');
		$this->template->usersLicenseNotActive = $this->UsersLicenseManager->findAll()->
					where(array('cl_users_id' => $this->getUser()->id))->
					where('license_end < CURDATE()')->order('created DESC');
		
		$this->template->arrModules	= $this->ArraysManager->getModules2P();
		
		$this->template->admin_company = $this->CompaniesManager->companyAdmin($userTmp->cl_company_id);
		
		//05.01.2017 - manage values from table for selectboxes which aren't in  available list
		$userCompany = $userCompany1->toArray();
		$userCompany = $this->testSelectData($this['settingsCompanyForm'], $userCompany);
		$userCompany = $this->testSelectData($this['settingsForm'], $userCompany);
		$userCompany = $this->testSelectData($this['settingsHelpdeskForm'], $userCompany);
		//$userCompany = $this->testSelectData($this['settingsUserForm'],$userCompany);
		
		
		$userTmpAdapt = json_decode($userCompany1->own_names, true);
		$userTmpSMSManager = json_decode($userCompany1->sms_manager, true);
		unset($userTmpSMSManager['sms_password']);
		
		$this['settingsCompanyForm']->setDefaults($userCompany);
		$this['settingsForm']->setDefaults($userCompany);
		$this['settingsHelpdeskForm']->setDefaults($userCompany);
		$this['settingsEETForm']->setDefaults($userCompany);
		$this['settingsUserForm']->setDefaults($userTmp);
        $this['settingsSMTPForm']->setDefaults($userCompany);
        $this['settingsComplaintForm']->setDefaults($userCompany);
		if (!is_null($userTmpAdapt)) {
			$this['settingsAdaptForm']->setDefaults($userTmpAdapt);
			$this['settingsAdaptForm']->setDefaults($userCompany);
		}
		if (!is_null($userTmpSMSManager)) {
			$this['settingsSMSManagerForm']->setDefaults($userTmpSMSManager);
		}
		$this['settingsDigitooForm']->setDefaults($userCompany);		
		
		$tmpUser = json_decode($userTmp->homepage_boxes, TRUE);
		//dump($tmpUser);
		
		foreach ($tmpUser['col1'] as $one) {
			$arrHomepage[$one[0]] = $one[1];
		}
		foreach ($tmpUser['col2'] as $one) {
			$arrHomepage[$one[0]] = $one[1];
		}

		$this['settingsHomepageForm']->setDefaults($arrHomepage);
		
		
		$defaultAccount = $this->BankAccountsManager->findAll()->where('default_account = ?', 1)->fetch();
		if ($defaultAccount) {
			$this['settingsCompanyForm']->setDefaults(array('cl_bank_account_id' => $defaultAccount->id,
				'bank_name' => $defaultAccount->bank_name,
				'account_number' => $defaultAccount->account_number,
				'bank_code' => $defaultAccount->bank_code));
		}
		if ($activeTab !== NULL)
			$this->activeTab = $activeTab;
		else
			$this->activeTab = 1;
		
		if ($activeTab2 !== NULL)
			$this->activeTab2 = $activeTab2;
		else
			$this->activeTab2 = 1;
		
		$this->template->activeTab = $this->activeTab;
		$this->template->activeTab2 = $this->activeTab2;
		$this->template->userCompany = $userCompany1;
		
		$this->template->coopPartnersRequests = $this->CompaniesManager->findAllTotal()->select(':cl_partners_coop.id,cl_company.name,:cl_partners_coop.created')
			->where(':cl_partners_coop.status = 2 AND :cl_partners_coop.cl_company_id = ?', $userTmp->cl_company_id)
			->order(':cl_partners_coop.created');
		//->having('COUNT(:cl_partners_coop(master_cl_company).id)>0')
		
		$this->template->coopPartners = $this->CompaniesManager->findAllTotal()->select(':cl_partners_coop.id,cl_company.name,:cl_partners_coop.created')
			->where(':cl_partners_coop.status = 1 AND :cl_partners_coop.cl_company_id = ?', $userTmp->cl_company_id)
			->order(':cl_partners_coop.created');
		//->having('COUNT(:cl_partners_coop(master_cl_company).id)>0')
		
		/*$this->template->coopPartnersRequests = $this->CompaniesManager->findAllTotal()->select(':cl_partners_coop.id,cl_company.name,:cl_partners_coop.created')
											->where(':cl_partners_coop.status = 2 AND :cl_partners_coop.cl_company_id = ?', $this->getUser()->identity->cl_company_id)
											->having('COUNT(:cl_partners_coop(master_cl_company).id)>0')
											->order(':cl_partners_coop.created');
		$this->template->coopPartners= $this->CompaniesManager->findAllTotal()->select(':cl_partners_coop.id,cl_company.name,:cl_partners_coop.created')
											->where(':cl_partners_coop.status = 1 AND :cl_partners_coop.cl_company_id = ?', $this->getUser()->identity->cl_company_id)
											->having('COUNT(:cl_partners_coop(master_cl_company).id)>0')
											->order(':cl_partners_coop.created');
		 *
		 */


        $this->archives = $this->ArraysManager->getArchives();
        $archives = [];
        foreach($this->archives as $key => $one){
            $archives[$key]['name'] = $one;
            $archives[$key]['schema_name'] = $this->ArchiveManager->getSchemaName($one);
            $archives[$key]['tables'] = $this->ArchiveManager->getTablesCount($one);
           //$archives[$key]['size'] = $this->ArchiveManager->getTablesSize($one);
        }
        $this->template->archives = $archives;

        //$dir = APP_DIR.'/../data';
        $dir = $this->CompaniesManager->getDataFolder($userTmp->cl_company_id);
        //$subFolder = $this->ArraysManager->getSubFolder($file);
        //$fileSend =  $dataFolder . '/' . $subFolder . '/' . $file->file_name;
        //

        if (is_dir($dir)) {
            $objects = scandir($dir);
        //    dump($objects);
            $myFiles = [];
            foreach ($objects as $one) {
                $fName = $dir . '/' . $one;
          //      dump($fName);
                if (file_exists($fName) && $one != '.' && $one != '..' && !is_dir($fName) && substr($one,0.-3) == '.gz') {
                    $myFiles[$one]['datetime'] = filemtime($fName);
                    $myFiles[$one]['size'] = round(filesize($fName) / 1024 / 1024, 2);
                }
            }
            arsort($myFiles);
        }
        $this->template->dumps = $myFiles;
       // dump($myFiles);

	}

    //work with dumps
    //
    //
    public function actionGetFile($file)
    {
        //$dir = APP_DIR.'/../data';
        $userTmp = $this->UserManager->getUserById($this->getUser()->id);
        $dir = $this->CompaniesManager->getDataFolder($userTmp->cl_company_id);
        $fileSend = $dir . '/' . $file;
        if (is_file($fileSend))
        {
            if (file_exists($fileSend)) {
                $this->presenter->sendResponse(new \Nette\Application\Responses\FileResponse($fileSend, $file, 'application/gzip'));
            }
        }else{
            $this->redirect('Default');
        }
    }

    public function handleEraseFile($file)
    {
       // $dir = APP_DIR.'/../data';
        $userTmp = $this->UserManager->getUserById($this->getUser()->id);
        if($userTmp) {
            $dataFolder = $this->CompaniesManager->getDataFolder($userTmp->cl_company_id);
            $file = $dataFolder . '/' . $file;
            if (is_file($file))
            {
                if (unlink($file))
                    $this->flashMessage ( $this->translator->translate('Soubor_byl_vymazán.'),'success');
                else
                    $this->flashMessage ( $this->translator->translate('Soubor_nebyl_vymazán.'),'warning');
            }
        }
        $this->redirect(':Application:Settings:default', ['activeTab' => 6]);
    }

    public function handleBackup($db){

        $userTmp = $this->UserManager->getUserById($this->getUser()->id);
        if($userTmp) {
            $bkpName = $this->ArchiveManager->dump($db, TRUE, [], $userTmp->cl_company_id);
            //$dataFolder = APP_DIR . '/../data';
            $dataFolder = $this->CompaniesManager->getDataFolder($userTmp->cl_company_id);
            //$dataFolder = $this->CompaniesManager->getDataFolder($cl_company_id);
            //$subFolder = $this->ArraysManager->getSubFolder($file);
            //$fileSend =  $dataFolder . '/' . $subFolder . '/' . $file->file_name;
            $fileSend = $dataFolder . '/' . $bkpName;
            if (file_exists($fileSend)) {
               // $this->presenter->sendResponse(new \Nette\Application\Responses\FileResponse($fileSend, $bkpName, 'application/gzip'));
                $this->flashMessage( $this->translator->translate('Záloha_byla_provedena.'), 'success');
                $this->redirect(':Application:Settings:default', ['activeTab' => 6]);
            } else {

                $this->flashMessage( $this->translator->translate('Záloha_nebyla_provedena.'), 'warning');
                $this->redirect(':Application:Settings:default', ['activeTab' => 6]);
            }
        }
    }



    //
    //end work with dumps
	
	/*** 23.01.2017 Tomas Halasz
	 * test if selectboxes in form contains value from default values
	 */
	protected function testSelectData($form, $data)
	{
		foreach ($form->components as $one) {
			//if is type of form input selectbox
			if ($one->options['type'] == 'select') {
				//check if value is not string, in this case convert from any number to int
				//it's because we are searching in array's key
				if (isset($data[$one->name])) {
					$testValue = $data[$one->name];
					if (!is_string($testValue)) {
						$testValue = (int)$testValue;
					}
					if (!array_key_exists($testValue, $one->getItems())) {
						//if defaultvalue from row isn't in list ov available values, use NULL
						$data[$one->name] = NULL;
					}
				}


				
			}
		}
		return ($data);
		
	}
	
	/**
	 * Settings company form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSettingsCompanyForm()
	{
		$form = new Form;
		//$form->setTranslator($this->translator);
		
		$form->addText('name', $this->translator->translate('Název_firmy'))
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('placeholder', $this->translator->translate('Název_firmy'));
		$form->addText('street', $this->translator->translate('Ulice'))
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('placeholder', $this->translator->translate('Ulice'));
		$form->addText('zip', $this->translator->translate('PSČ'))
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('placeholder', $this->translator->translate('PSČ'));
		$form->addText('city', $this->translator->translate('Město'))
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('placeholder', $this->translator->translate('Město'));
		$arrCountries = $this->CountriesManager->findAllTotal()->order('name')->fetchPairs('id', 'name');
		$form->addSelect('cl_countries_id', $this->translator->translate("Stát"), $arrCountries)
			->setRequired($this->translator->translate('Stát_musí_být_vybrán.'))
			->setPrompt($this->translator->translate('Zvolte_stát'));
		$form->addText('ico', $this->translator->translate('IČO'))
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('placeholder', $this->translator->translate('IČO'));
		$form->addText('dic', $this->translator->translate('DIČ'))
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('placeholder', $this->translator->translate('DIČ'));
		$form->addCheckbox('platce_dph', $this->translator->translate('Plátce_DPH'));
		$form->addText('email', $this->translator->translate('E-mail'))
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('placeholder', $this->translator->translate('E-mail'));
		$form->addText('telefon', $this->translator->translate('Telefon'))
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('placeholder', $this->translator->translate('Telefon'));
        $form->addText('contact_person', $this->translator->translate('Kontaktní_osoba'))
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Kontaktní_osoba'));
		$form->addText('www', $this->translator->translate('Web'))
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('placeholder', $this->translator->translate('Web'));
		$form->addText('other', $this->translator->translate('Jiný_kontakt'))
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('placeholder', $this->translator->translate('Skype_ICQ_Viber'));
		$form->addTextArea('obch_rejstrik', $this->translator->translate('Obchodní_rejstřík'), 20, 4)
			->setHtmlAttribute('class', 'form-control myTextarea')
			->setHtmlAttribute('placeholder', $this->translator->translate('Firma_je_zapsána_v_obchodním_rejstříku_vedeném_u_xxxxxx'));
		$form->addHidden('cl_bank_account_id');
		$form->addText('bank_name', $this->translator->translate('Název_banky'))
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('placeholder', $this->translator->translate('Název_banky'));
		$form->addText('account_number', $this->translator->translate('Číslo_účtu'))
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('placeholder', $this->translator->translate('Číslo_účtu'));
		
		$form->addText('bank_code', $this->translator->translate('Kód_banky'))
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('placeholder', $this->translator->translate('Kód_banky'));
		
		$form->addSubmit('submit', $this->translator->translate('Uložit'))
			->setHtmlAttribute('class', 'form-control btn-sm btn-primary');
		
		$form->onSuccess[] = array($this, "SettingsCompanyFormSubmitted");
		return $form;
	}
	
	/**
	 * Settings company form submitted
	 * @param \Nette\Application\UI\Form
	 * @return void
	 */
	public function settingsCompanyFormSubmitted($form)
	{
		$values = $form->getValues();
		try {
			$values['id'] = $this->UserManager->getCompany($this->getUser()->id)->id;
			unset($values['submit']);
			$bankAccount = array();
			$bankAccount['id'] = $values['cl_bank_account_id'];
			$bankAccount['bank_name'] = $values['bank_name'];
			$bankAccount['bank_code'] = $values['bank_code'];
			$bankAccount['account_number'] = $values['account_number'];
			unset($values['cl_bank_account_id']);
			unset($values['bank_name']);
			unset($values['bank_code']);
			unset($values['account_number']);
			$this->CompaniesManager->update($values);
			if ($bankAccount['id'] == 0) {
				$bankAccount['default_account'] = 1;
				$this->BankAccountsManager->insert($bankAccount);
			} else
				$this->BankAccountsManager->update($bankAccount);
			
			$this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
			$this->activeTab = 1;
			$this->redrawControl('settings');
			$this->redrawControl('flash');
			$this->redirect('this', array('activeTab' => 1));
			//$this->redirect(this);
		} catch (\Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}
	
	/**
	 * Settings form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSettingsForm()
	{
		$form = new Form;
		//$form->setTranslator($this->translator);
		
		//$form->addText('def_mena','Měna:')
		//	->setHtmlAttribute('class','form-control input-sm')
		//	->setHtmlAttribute('placeholder','Kč, CZK, EUR, USD');
		$arrCurrencies = $this->CurrenciesManager->findAll()->order('currency_name')->fetchPairs('id', 'currency_name');
		$form->addSelect('cl_currencies_id', $this->translator->translate("Měna"), $arrCurrencies)
			->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_měnu'))
			->setHtmlAttribute('class', 'form-control chzn-select input-sm')
			->setPrompt($this->translator->translate('Zvolte_měnu'));
		
		//$arrStorage = $this->StorageManager->findAll()->fetchPairs('id','name');
		/*		$arrStorage = array();
				$tmpStorage = $this->StorageManager->findAll()->where('cl_storage_id IS NULL');
				foreach($tmpStorage as $key=>$one)
				{
					$arr2 = array();
					foreach($one->related('cl_storage') as $key2=>$one2)
					{
						$arr2[$key2] = $one2->name.' - '.$one2->description;
					}
					if (count($arr2) > 0)
					{
						$arrStorage[$one->name.' - '.$one->description] = $arr2;
					}
				}		*/
		$arrStorage = $this->StorageManager->getStoreTreeNotNested();
		
		if (count($arrStorage) > 0) {
			$form->addSelect('cl_storage_id', $this->translator->translate("Hlavní_sklad"), $arrStorage)
				->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_sklad'))
				->setHtmlAttribute('class', 'form-control chzn-select input-sm no-live-validation')
				->setRequired($this->translator->translate('Hlavní_sklad_musí_být_zvolen'))
				->setPrompt($this->translator->translate('Zvolte_sklad'));
			
			$form->addSelect('cl_storage_id_sale', $this->translator->translate("Sklad_pro_výdej_z_prodejny"), $arrStorage)
				->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_sklad'))
				->setHtmlAttribute('class', 'form-control chzn-select input-sm no-live-validation')
				->setRequired($this->translator->translate('Sklad_pro_výdej_z_prodejny_musí_být_zvolen'))
				->setPrompt($this->translator->translate('Zvolte_sklad'));
			
			$form->addSelect('cl_storage_id_back', $this->translator->translate("Sklad_pro_příjem_zpět_z_faktury"), $arrStorage)
				->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_sklad'))
				->setHtmlAttribute('class', 'form-control chzn-select input-sm no-live-validation')
				->setPrompt($this->translator->translate('Zvolte_sklad'));
			
			$form->addSelect('cl_storage_id_back_sale', $this->translator->translate("Sklad_pro_příjem_zpět_z_prodejny"), $arrStorage)
				->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_sklad'))
				->setHtmlAttribute('class', 'form-control chzn-select input-sm no-live-validation')
				->setPrompt($this->translator->translate('Zvolte_sklad'));
			
			$form->addSelect('cl_storage_id_commission', $this->translator->translate("Sklad_pro_výdej_ze_zakázky"), $arrStorage)
				->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_sklad'))
				->setHtmlAttribute('class', 'form-control chzn-select input-sm no-live-validation')
				->setRequired($this->translator->translate('Sklad_pro_výdej_ze_zakázky_musí_být_zvolen'))
				->setPrompt($this->translator->translate('Zvolte_sklad'));
			
			$form->addSelect('cl_storage_id_macro', $this->translator->translate("Sklad_pro_výdej_materiálu"), $arrStorage)
				->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_sklad'))
				->setHtmlAttribute('class', 'form-control chzn-select input-sm')
				->setRequired($this->translator->translate('Sklad_pro_výdej_materiálu_musí_být_zvolen'))
				->setPrompt($this->translator->translate('Zvolte_sklad'));
			
		} else {
			$form->addSelect('cl_storage_id', $this->translator->translate("Sklad_pro_výdej_z_faktury"), $arrStorage)
				->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_sklad'))
				->setHtmlAttribute('class', 'form-control chzn-select input-sm no-live-validation')
				->setPrompt($this->translator->translate('Zvolte_sklad'));
			
			$form->addSelect('cl_storage_id_sale', $this->translator->translate("Sklad_pro_výdej_z_prodejny"), $arrStorage)
				->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_sklad'))
				->setHtmlAttribute('class', 'form-control chzn-select input-sm no-live-validation')
				->setPrompt($this->translator->translate('Zvolte_sklad'));
			
			$form->addSelect('cl_storage_id_commission', $this->translator->translate("Sklad_pro_výdej_ze_zakázky"), $arrStorage)
				->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_sklad'))
				->setHtmlAttribute('class', 'form-control chzn-select input-sm no-live-validation')
				->setPrompt($this->translator->translate('Zvolte_sklad'));
			
			$form->addSelect('cl_storage_id_macro', $this->translator->translate("Sklad_pro_výdej_materiálu"), $arrStorage)
				->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_sklad'))
				->setHtmlAttribute('class', 'form-control chzn-select input-sm no-live-validation')
				->setPrompt($this->translator->translate('Zvolte_sklad'));
		}
		
		$form->addText('def_mj', $this->translator->translate('Jednotky'))
			->setRequired($this->translator->translate('Jednotky_musí_být_zadány'))
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('placeholder', 'ks,_kg,_litr,_hod.');
		$form->addText('des_cena', $this->translator->translate('Desetinné_místa_ceny'))
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('placeholder', '0');
		$form->addText('des_mj', $this->translator->translate('Desetinné_místa_jednotek'))
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('placeholder', '0');
		$form->addCheckbox('price_e_type', $this->translator->translate('Prodejní_cena_za_jednotku_je_včetně_DPH'));
		
		$form->addText('konst_symb', $this->translator->translate('Konstatní_symbol'), 5, 5)
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('placeholder', $this->translator->translate('Konst_symbol'));
		$dateToday = new \Nette\Utils\DateTime;
		//$arrVat = $this->validatorService->getValidVAT($dateToday);
		$arrVat = $this->RatesVatManager->findAllValid()->fetchPairs('rates', 'rates');
		$form->addSelect('def_sazba', $this->translator->translate('Sazba_DPH'), $arrVat)
			->setHtmlAttribute('class', 'form-control chzn-select input-sm no-live-validation')
			->setHtmlAttribute('placeholder', '21%')
			->setRequired($this->translator->translate('Výchozí_sazba_DPH_musí_být_zvolena'))
			->setHtmlAttribute('aria-describedby', 'basic-addon1');
		$form->addSelect('offer_vat_def', $this->translator->translate('Sazba_DPH_pro_nabídky'), $arrVat)
			->setHtmlAttribute('class', 'form-control chzn-select input-sm no-live-validation')
			->setHtmlAttribute('placeholder', '21%')
			->setHtmlAttribute('aria-describedby', 'basic-addon1');
		$form->addText('due_date', $this->translator->translate('Splatnost'))
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('placeholder', '0')
			->setHtmlAttribute('aria-describedby', 'basic-addon1');
		//$arrPay = $this->validatorService->getValidPayments();
		$arrPay = $this->PaymentTypesManager->findAll()->order('name')->fetchPairs('id', 'name');
		$form->addSelect('cl_payment_types_id', $this->translator->translate('Forma_úhrady'), $arrPay)
			->setRequired($this->translator->translate('Forma_úhrady_musí_být_zvolena'))
			->setPrompt($this->translator->translate('Vyberte_výchozí_formu_úhrady'))
			->setHtmlAttribute('class', 'form-control chzn-select input-sm');
		
		//$arrPay = $this->PaymentTypesManager->findAll()->order('name')->fetchPairs('id','name');
		$form->addSelect('cl_payment_types_id_sale', $this->translator->translate('Forma_úhrady_pro_prodejnu'), $arrPay)
			->setPrompt($this->translator->translate('Vyberte_výchozí_formu_úhrady'))
			->setHtmlAttribute('class', 'form-control chzn-select input-sm');
		
		
		$arrPay = $this->PartnersManager->findAll()->order('company')->fetchPairs('id', 'company');
		$form->addSelect('cl_partners_book_id_sale', $this->translator->translate('Odběratel_pro_prodejnu'), $arrPay)
			->setPrompt($this->translator->translate('Vyberte_odběratele'))
			->setHtmlAttribute('class', 'form-control chzn-select input-sm');
		
		$form->addCheckbox('header_show', $this->translator->translate('Vždy_tisknout_záhlaví'));
		$form->addCheckbox('footer_show', $this->translator->translate('Vždy_tisknout_zápatí'));
		$form->addTextArea('header_txt', $this->translator->translate('Záhlaví_faktur'), 20, 4)
			->setHtmlAttribute('class', 'form-control myTextarea')
			->setHtmlAttribute('placeholder', $this->translator->translate('Libovolný_text_pro_záhlaví_faktury'));
		$form->addTextArea('footer_txt', $this->translator->translate('Zápatí_faktur'), 20, 4)
			->setHtmlAttribute('class', 'form-control myTextarea')
			->setHtmlAttribute('placeholder', $this->translator->translate('Libovolný_text_pro_zápatí_faktury'));
		$form->addTextArea('header_txt_cm', $this->translator->translate('Záhlaví_zakázek'), 20, 4)
			->setHtmlAttribute('class', 'form-control myTextarea')
			->setHtmlAttribute('placeholder', $this->translator->translate('Libovolný_text_pro_záhlaví_zakázky'));
		$form->addTextArea('header_txt_ord', $this->translator->translate('Záhlaví_objednávek'), 20, 4)
			->setHtmlAttribute('class', 'form-control myTextarea')
			->setHtmlAttribute('placeholder', $this->translator->translate('Libovolný_text_pro_záhlaví_objednávek'));
		$form->addCheckbox('header_show_cm', $this->translator->translate('Vždy_tisknout_záhlaví'));
		$form->addCheckbox('header_show_ord', $this->translator->translate('Vždy_tisknout_záhlaví'));
		
		$form->addCheckbox('offer_vat_off', $this->translator->translate('Netisknout_ceny_s_DPH_na_nabídkách'));
        $form->addCheckbox('dn_price_off', $this->translator->translate('Netisknout_ceny_na_dodacích_listech'));
		
		$form->addCheckbox('print_qr', $this->translator->translate('Tisknout_kód_QR_faktura'));
		$form->addCheckbox('invoice_to_store', $this->translator->translate('Z_faktury_vytvářet_výdejku'));
        $form->addCheckbox('dn_from_commission', $this->translator->translate('Ze_zakázky_vytvořit_s_fakturou_také_dodací_list'));

		$form->addCheckbox('exp_on', $this->translator->translate('Povolena_práce_s_datem_expirace'));
		$form->addCheckbox('batch_on', $this->translator->translate('Povolena_práce_s_šaržemi'));
		$form->addCheckbox('use_package', $this->translator->translate('Povolena_práce_s_obaly'));
		$form->addCheckbox('order_package', $this->translator->translate('Nepřepočítávat_objednané_a_dodané_kusy_počtem_v_balení'));

        $form->addCheckbox('signature_enabled', $this->translator->translate('Povolen_tisk_textu_fakturu_prevzal_atd'));
        $form->addCheckbox('signature_date_enabled', $this->translator->translate('Povolen_tisk_datumu'));
		
		$form->addTextArea('pdp_text', $this->translator->translate('Text_pro_přenesenou_daňovou_povinnost'), 20, 4)
			->setHtmlAttribute('class', 'form-control myTextarea')
			->setHtmlAttribute('placeholder', $this->translator->translate('Dodání_je_v_režimu_přenesené_daňové_povinnosti_dle_§_92a_zákona_č_235/2004_Sb_o_DPH_Daň_odvede_zákazník'));
		
		$form->addSubmit('submit', $this->translator->translate('Uložit'))
			->setHtmlAttribute('class', 'form-control btn-sm btn-primary');
		
		$form->onSuccess[] = [$this, "SettingsFormSubmitted"];
		return $form;
	}
	
	/**
	 * Settings form submitted
	 * @param \Nette\Application\UI\Form
	 * @return void
	 */
	public function settingsFormSubmitted($form)
	{
		$values = $form->getValues();
		try {
			$values['id'] = $this->UserManager->getCompany($this->getUser()->id)->id;
			unset($values['submit']);
			$this->CompaniesManager->update($values);
			$this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
			$this->activeTab = 2;
			$this->activeTab2 = 1;
			$this->redrawControl('settings');
			$this->redrawControl('flash');
			$this->redirect('this', array('activeTab' => 2, 'activeTab2' => 1));
		} catch (\Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}
	
	
	/**
	 * Settings Helpdesk form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSettingsHelpdeskForm()
	{
		$form = new Form;
		//$form->setTranslator($this->translator);
		
		$form->addText('email_income', $this->translator->translate('Email_pro_příjem_požadavků'))
			->setRequired(FALSE)
			->addRule(Form::EMAIL, $this->translator->translate('Zadaný_email_nemá_platný_formát'))
			->addRule(array($this, 'validateEmail'), $this->translator->translate('Zvolte_jiný_email_tento_je_již_použit'))
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('placeholder', $this->translator->translate('E-mail'))
			->setHtmlAttribute('data-urlString', $this->link('checkEmail!'));
		$form->addText('email_income_exclude', $this->translator->translate('Na_tyto_emaily_neposílat_potvrzení_o_přijetí'))
			->setRequired(FALSE)
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('placeholder', $this->translator->translate('Emaily_oddělené_středníkem'));

//			->addRule(\FormValidator::EMAIL_HELPDESK_UNIQUE,'Zvolte jiný email, tento je již použit.')
		
		$arrEmails = $this->EmailingTextManager->findBy(array('email_use' => 'partners_event'))->order('email_name')->fetchPairs('id', 'email_name');
		$form->addSelect('hd1_emailing_text_id', $this->translator->translate('Potvrzení_přijetí_klientovi'), $arrEmails)
			->setPrompt($this->translator->translate('Vyberte_šablonu_emailu'))
			->setHtmlAttribute('class', 'form-control chzn-select input-sm');
		$form->addSelect('hd2_emailing_text_id', $this->translator->translate('Oznámení_nepřijetí_klientovi'), $arrEmails)
			->setPrompt($this->translator->translate('Vyberte_šablonu_emailu'))
			->setHtmlAttribute('class', 'form-control chzn-select input-sm');
		$form->addSelect('hd3_emailing_text_id', $this->translator->translate('Oznámení_přijetí_pro_správce_helpdesku'), $arrEmails)
			->setPrompt($this->translator->translate('Vyberte_šablonu_emailu'))
			->setHtmlAttribute('class', 'form-control chzn-select input-sm');
		$form->addSelect('hd4_emailing_text_id', $this->translator->translate('Oznámení_o_přidělení_technikovi'), $arrEmails)
			->setPrompt($this->translator->translate('Vyberte_šablonu_emailu'))
			->setHtmlAttribute('class', 'form-control chzn-select input-sm');
		$form->addSelect('hd5_emailing_text_id', $this->translator->translate('Oznámení_o_přijetí_odpovědi_pro_správce_helpdesku'), $arrEmails)
			->setPrompt($this->translator->translate('Vyberte_šablonu_emailu'))
			->setHtmlAttribute('class', 'form-control chzn-select input-sm');
		$form->addSelect('hd7_emailing_text_id', $this->translator->translate('Oznámení_o_přijetí_odpovědi_pro_klienta'), $arrEmails)
			->setPrompt($this->translator->translate('Vyberte_šablonu_emailu'))
			->setHtmlAttribute('class', 'form-control chzn-select input-sm');
		$form->addSelect('hd6_emailing_text_id', $this->translator->translate('Oznámení_o_ukončení_události_klientovi'), $arrEmails)
			->setPrompt($this->translator->translate('Vyberte_šablonu_emailu'))
			->setHtmlAttribute('class', 'form-control chzn-select input-sm');
		
		$form->addCheckbox('hd_anonymous', $this->translator->translate('Povolit_příjem_anonymních_požadavků'));
		
		$arrHDPartners = $this->PartnersManager->findAll()->order('company')->fetchPairs('id', 'company');
		$form->addSelect('hd_cl_partners_book_id', $this->translator->translate('Výchozí_klient_anonymních_požadavků'), $arrHDPartners)
			->setPrompt($this->translator->translate('Vyberte_klienta'))
			->setHtmlAttribute('class', 'form-control chzn-select input-sm');
		
		$form->addCheckbox('hd_ending', $this->translator->translate('Ukončovat_požadavky_helpdesku_automaticky_jakmile_jsou_ukončena_všechna_podřízená_řešení'));
		
		$arrVat = $this->RatesVatManager->findAllValid()->fetchPairs('rates', 'rates');
		$form->addSelect('hd_vat', $this->translator->translate('Sazba_DPH_pro_práci'), $arrVat)
			->setHtmlAttribute('class', 'form-control chzn-select input-sm no-live-validation')
			->setHtmlAttribute('placeholder', '21%')
			->setRequired($this->translator->translate('Sazba_DPH_musí_být_zvolena'))
			->setHtmlAttribute('aria-describedby', 'basic-addon1');
		
		$form->addSubmit('submit', $this->translator->translate('Uložit'))
			->setHtmlAttribute('class', 'form-control btn-sm btn-primary');
		
		$form->onSuccess[] = array($this, "SettingsHelpdeskFormSubmitted");
		return $form;
	}
	
	public function validateEmail($input)
	{
		// validace emailu pro prijem helpdesku
		return !$this->CheckEmail($input->getValue());
	}

    /** check validity of email, return empty string if it is not valid
     * @param $eml
     * @return mixed|string
     */
    public function validateEmail2($eml)
    {
        if (!filter_var($eml, FILTER_VALIDATE_EMAIL)) {
            $eml = "";
        }
        return $eml;
    }
	
	public function CheckEmail($email)
	{
		if ($this->CompaniesManager->findAllTotal()->where('email_income = ? AND id != ?', $email, $this->settings->id)->fetch())
			return TRUE;
		else
			return FALSE;
	}
	
	public function handleCheckEmail($email)
	{
		$result = array('result' => $this->CheckEmail($email));
		//dump(json_encode($result));
		echo(json_encode($result));
		$this->terminate();
		
	}
	
	
	/**
	 * Helpdesk settings form submitted
	 * @param \Nette\Application\UI\Form
	 * @return void
	 */
	public function settingsHelpdeskFormSubmitted($form)
	{
		$values = $form->getValues();
		try {
			$values['id'] = $this->UserManager->getCompany($this->getUser()->id)->id;
			unset($values['submit']);
			
			$this->CompaniesManager->update($values);
			$this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
			$this->activeTab = 2;
			$this->activeTab2 = 2;
			$this->redrawControl('settings');
			$this->redrawControl('flash');
			$this->redirect('this', array('activeTab' => 2, 'activeTab2' => 2));
		} catch (\Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}
	
	
	/**
	 * Settings Homepage form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSettingsHomepageForm()
	{
		$form = new Form;
		//$form->setTranslator($this->translator);
        $form->addCheckbox('infoVat', $this->translator->translate('Info_DPH'));
		$form->addCheckbox('graphVolume', $this->translator->translate('Graf_finančních_objemů'));
		$form->addCheckbox('graphHelpdesk', $this->translator->translate('Graf_událostí_helpdesku'));
		$form->addCheckbox('infoBox', $this->translator->translate('Box_s_informacemi'));
		$form->addCheckbox('orders', $this->translator->translate('Objednávky'));
        $form->addCheckbox('commissionBox', $this->translator->translate('Dnešní_zakázky'));
        $form->addCheckbox('limits', $this->translator->translate('Podlimitní_stavy'));
        $form->addCheckbox('invoices', $this->translator->translate('Vydané_faktury_po_splatnosti'));
        $form->addCheckbox('invoicearrived', $this->translator->translate('Přijaté_faktury_po_splatnosti'));
		$form->addCheckbox('eventsList', $this->translator->translate('Box_s_posledními_událostmi'));
        $form->addCheckbox('notifications', $this->translator->translate('Oznámení'));
		$form->addSubmit('submit', $this->translator->translate('Uložit'))
			->setHtmlAttribute('class', 'form-control btn-sm btn-primary');
		
		$form->onSuccess[] = [$this, "SettingsHomepageFormSubmitted"];
		return $form;
	}
	
	/**
	 * Helpdesk settings form submitted
	 * @param \Nette\Application\UI\Form
	 * @return void
	 */
	public function settingsHomepageFormSubmitted($form)
	{
		$values = $form->getValues();
		try {
			$userId = $this->getUser()->id;
			$userTmp = $this->UserManager->getUserById($this->getUser()->id);
			//$tmpUser= json_decode($userTmp->homepage_boxes, TRUE);
			//if (is_null($tmpUser))
			//{
			$tmpUser['col1'] = [['infoVat', 1], ['graphVolume', 1], ['infoBox', 1], ['notifications',1], ['orders', 1], ['invoices', 1], ['invoicearrived', 1], ['commissionBox', 1]];
			$tmpUser['col2'] = [['graphHelpdesk', 1], ['eventsList', 1], ['limits',1]];
			//}
			//dump($tmpUser['col1']);
			if (!isset($tmpUser['col1']))
				$tmpUser['col1'] = [];
			
			foreach ($tmpUser['col1'] as $key => $one) {
				
				if ($values[$one[0]] == TRUE)
					$values[$one[0]] = 1;
				else
					$values[$one[0]] = 0;
				
				$arrHomepage['col1'][$key] = [$one[0], $values[$one[0]]];
			}
			if (count($tmpUser['col1']) == 0) {
				$arrHomepage['col1'] = [];
			}
			//dump($arrHomepage);
			//die;
			if (!isset($tmpUser['col2']))
				$tmpUser['col2'] = [];
			
			foreach ($tmpUser['col2'] as $key => $one) {
				
				if ($values[$one[0]] == TRUE)
					$values[$one[0]] = 1;
				else
					$values[$one[0]] = 0;
				
				$arrHomepage['col2'][$key] = [$one[0], $values[$one[0]]];
			}
			if (count($tmpUser['col2']) == 0) {
				$arrHomepage['col2'] = [];
			}
			//dump(json_encode($arrHomepage));
			//die;
			$this->UserManager->updateUser(['id' => $userId, 'homepage_boxes' => json_encode($arrHomepage)]);
			
			$this->redrawControl('settings');
			$this->redrawControl('flash');
			$this->redirect('this', ['activeTab' => 2, 'activeTab2' => 3]);
		} catch (\Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}
	
	
	/**Logo company form
	 * @return Form
	 */
	protected function createComponentUploadLogoForm()
	{
		$form = new \Nette\Application\UI\Form();
		$form->getElementPrototype()->class = 'dropzone';
		$form->getElementPrototype()->id = 'logoDropzone';
		$form->addHidden('type', 0); //logo
		$form->onSuccess[] = [$this, 'processUploadLogoForm'];
		return $form;
	}
	
	/**Stamp company form
	 * @return Form
	 */
	protected function createComponentUploadStampForm()
	{
		$form = new \Nette\Application\UI\Form();
		$form->getElementPrototype()->class = 'dropzone';
		$form->getElementPrototype()->id = 'stampDropzone';
		$form->addHidden('type', 1); //stamp
		$form->onSuccess[] = [$this, 'processUploadLogoForm'];
		return $form;
	}
	
	/**upload method for process logo
	 * @param Form $form
	 */
	public function processUploadLogoForm(Form $form)
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
		$company = $this->UserManager->getCompany($this->getUser()->id);
		if ($type == 0) {
			$storedName = $company->picture_logo;
		}
		if ($type == 1) {
			$storedName = $company->picture_stamp;
		}
		
		if ($storedName != '') {
			$fileDel = __DIR__ . "/../../../data/pictures/" . $storedName;
			if (file_exists($fileDel)) {
				unlink($fileDel);
			}
		}
		//next check if file exists, if yes generate new filename
		$destFile = NULL;
		while (file_exists($destFile) || is_null($destFile)) {
			$fileName = \Nette\Utils\Random::generate(64, 'A-Za-z0-9');
			$destFile = __DIR__ . "/../../../data/pictures/" . $fileName . '.jpg';
		}
		$image->save($destFile);
		$value['id'] = $company->id;
		if ($type == 0) {
			$value['picture_logo'] = $fileName . '.jpg';
		}
		if ($type == 1) {
			$value['picture_stamp'] = $fileName . '.jpg';
		}
		
		$this->CompaniesManager->update($value);
	}
	
	public function handleLogoboxGet($ico)
	{
		$urlAres = "https://www.logobox.cz/getimage.ashx?size=medium&id=CZ";
		//dump($urlAres.$ico);
		//$file = @file_get_contents(ARES.$ico);
		//dump($file);
		if ($curl = curl_init($urlAres . $ico)) {
			//dump($curl);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$content = curl_exec($curl);
			//dump($content);
			//$info = curl_getinfo($curl);
			curl_close($curl);
			$image = \Nette\Utils\Image::fromString($content);
			$image->resize(200, NULL);
			$this->processImage($image, 0);
			
			$this->redrawControl('imageLogo');
			//$xml = @simplexml_load_string($content);
		}
	}
	
	public function handleRemoveLogoStamp($type = 0)
	{
		$company = $this->UserManager->getCompany($this->getUser()->id);
		if ($type == 0)
			$storedName = $company->picture_logo;
		if ($type == 1)
			$storedName = $company->picture_stamp;
		
		if ($storedName != '') {
			$fileDel = __DIR__ . "/../../../data/pictures/" . $storedName;
			if (file_exists($fileDel))
				unlink($fileDel);
			$value = array();
			$value['id'] = $company->id;
			if ($type == 0)
				$value['picture_logo'] = '';
			
			if ($type == 1)
				$value['picture_stamp'] = '';
			
			$this->CompaniesManager->update($value);
			
		}
		if ($type == 0)
			$this->redrawControl('imageLogo');
		if ($type == 1)
			$this->redrawControl('imageStamp');
		//$this->redrawControl('settings');
		//$this->redrawControl('flash');
	}
	
	public function handleSwitchTab($activeTab)
	{
		$this->activeTab = $activeTab;
		$this->redrawControl('card');
		$this->redrawControl('card1');
		$this->redrawControl('card2');
		$this->redrawControl('card3');
		$this->redrawControl('flash');
	}
	
	
	/**
	 * Settings user form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSettingsUserForm()
	{
		$form = new Form;
		//$form->setTranslator($this->translator);
		
		$form->addText('name', $this->translator->translate('Jméno_a_příjmení'))
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('placeholder', $this->translator->translate('Jméno_a_příjmení_uživatele'));
		$form->addText('nick_name', $this->translator->translate('Přezdívka'))
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('placeholder', $this->translator->translate('Přezdívka_uživatele'));
		$form->addText('email', $this->translator->translate('Email_pro_přihlášení'))
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('placeholder', $this->translator->translate('Emailová_adresa'));

		$form->addSelect('lang', $this->translator->translate('Jazyk'), $this->ArraysManager->getLanguages())
            ->setTranslator(NULL)
			->setHtmlAttribute('class', 'form-control chzn-select input-sm')
			->setHtmlAttribute('placeholder', $this->translator->translate('Jazyk'));
       /*$form->addCheckbox('bsc_enabled',$this->translator->translate('Nahoře seznam, dole obsah dokladu'))
            ->setHtmlAttribute('class', 'items-show');*/
		$form->addPassword('password', $this->translator->translate('Heslo'))
			->setHtmlAttribute('class', 'form-control')
			->setHtmlAttribute('autocomplete', 'off')
			->setHtmlAttribute('placeholder', $this->translator->translate('Heslo'))
			->setRequired(FALSE)
			->addCondition($form::FILLED)
			->addRule($form::MIN_LENGTH, $this->translator->translate('Heslo_je_příliš_krátké_Musí_mít_alespoň_%d_znaků'), 5)
			->addRule($form::PATTERN, $this->translator->translate('Heslo_je_příliš_jednoduché_Musí_obsahovat_číslici'), '.*[0-9].*')
			->addRule($form::PATTERN, $this->translator->translate('Heslo_je_příliš_jednoduché_Musí_obsahovat_malé_písmeno'), '.*[a-z].*');
		$form->addPassword('password2')
			->setHtmlAttribute('class', 'form-control')
			->setHtmlAttribute('autocomplete', 'off')
			->setHtmlAttribute('placeholder', $this->translator->translate('Heslo_znovu'))
			->addConditionOn($form['password'], $form::FILLED)
			->addRule($form::EQUAL, $this->translator->translate('Hesla_se_neshodují'), $form['password'])
			->setRequired($this->translator->translate('Prosím_zadejte_znovu_své_heslo'));
		
		
		$form->addSubmit('submit', $this->translator->translate('Uložit'))
			->setHtmlAttribute('class', 'form-control btn-sm btn-primary');
		
		$form->onSuccess[] = array($this, "SettingsUserFormSubmitted");
		return $form;
	}
	
	/**
	 * Settings user form submitted
	 * @param \Nette\Application\UI\Form
	 * @return void
	 */
	public function settingsUserFormSubmitted($form)
	{
		$values = $form->getValues();
		try {
			$values['id'] = $this->getUser()->id;
			unset($values['submit']);
			unset($values['password2']);
			if ($values['password'] === "")
				unset($values['password']);
			
			if ($values['email'] != $this->getUser()->getIdentity()->email) {
				$values['email_confirmed'] = 0;
				$this->getUser()->getIdentity()->email = $values['email'];
				$this->getUser()->getIdentity()->email_confirmed = 0;
			}
            $this->getUser()->getIdentity()->lang = $values['lang'];
            //$this->user->identity->bsc_enabled = $values['bsc_enabled'];
			$this->UserManager->updateUser($values);
			$this->activeTab = 3;
			$this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
			//$this->redrawControl('settings');
			//$this->redrawControl('flash');
			$this->redirect('this', array('activeTab' => 3));
		} catch (\Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}
	
	
	public function handleConfirmEmail()
	{
		//send confirmation email
		$emlSend = $this->getUser()->getIdentity()->email;
		$confirmKey = \Nette\Utils\Random::generate(64, 'A-Za-z0-9');
		$values = array();
		$values['id'] = $this->getUser()->id;
		$values['eml_confirm_key'] = $confirmKey;
		$this->UserManager->updateUser($values);
		
		$activateLink = $this->link(':Application:Users:EmailConfirmation', $values['eml_confirm_key'], $emlSend);
		
		$this->emailService->sendRegistrationEmail($emlSend, $activateLink);
		
		$this->flashMessage($this->translator->translate('Na adresu') . $emlSend . $this->translator->translate('jsme_vám_poslali_potvrzovací_email'), 'success');
		$this->redrawControl('settings');
		$this->redrawControl('flash');
	}
	
	
	public function handleCoopPartnersEnable($cl_partners_coop_id)
	{
		$arrData = array();
		$arrData['id'] = $cl_partners_coop_id;
		$arrData['status'] = 1;
		$update = $this->PartnersCoopManager->update($arrData);
		$update = $this->PartnersCoopManager->find($cl_partners_coop_id);
		
		if ($tmpUpdate = $this->PartnersManager->findAllTotal()->where(array('cl_company_id' => $update->master_cl_company_id,
			'coop_cl_company_id' => $update->cl_company_id,
			'ico' => $update->cl_company->ico))->fetch()) {
			$this->PartnersManager->updateForeign(array('coop_enable' => 1, 'id' => $tmpUpdate->id));
			$this->PartnersCoopManager->createCoopData($update); //vytvori data v cl_partners_book, cl_pricelist z master company
		}
		
		
		$this->flashMessage($this->translator->translate('Partnerství_bylo_nastaveno'), 'success');
		$this->redrawControl('settings');
		$this->redrawControl('flash');
	}
	
	public function handleCoopPartnersDisable($cl_partners_coop_id)
	{
		$update = $this->PartnersCoopManager->find($cl_partners_coop_id);
		if ($tmpUpdate = $this->PartnersManager->findAllTotal()->where(array('cl_company_id' => $update->master_cl_company_id,
			'coop_cl_company_id' => $update->cl_company_id,
			'ico' => $update->cl_company->ico))->fetch())
			$this->PartnersManager->updateForeign(array('coop_enable' => 0, 'id' => $tmpUpdate->id));
		
		$delete = $this->PartnersCoopManager->delete($cl_partners_coop_id);
		
		
		$this->flashMessage($this->translator->translate('Partnerství_bylo_zrušeno'), 'success');
		//$this->redrawControl('settings');
		//$this->redrawControl('flash');
		$this->redirect('this');
	}
	
	
	public function handleGetAres($ico)
	{
		$ares = new \halasz\Ares\Ares();
		$result = $ares->loadData($ico); // return object \halasz\Ares\Data

		$arrResult = $result->toArray();
		
		if ($tmpCountries = $this->CountriesManager->findAllTotal()->where(array('name' => 'Česko'))->fetch()) {
			
			$arrResult['cl_countries_id'] = $tmpCountries->id;
		}
		
		
		$this->sendJson($arrResult);
	}
	
	
	/**
	 * Settings Helpdesk form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSettingsAdaptForm()
	{
		$form = new Form;
		//  $form->setTranslator($this->translator);
		
		$form->addText('cl_invoice_items__description1', $this->translator->translate('Název_pole_Poznámka_1'));
		$form->addText('cl_invoice_items__description2', $this->translator->translate('Název_pole_Poznámka_2'));
		$form->addText('cl_commission_items__description1', $this->translator->translate('Název_pole_Poznámka_1'));
		$form->addText('cl_commission_items__description2', $this->translator->translate('Název_pole_Poznámka_2'));
		$form->addCheckbox('order_group_label', $this->translator->translate('Pořadí_položek_dokladů_podle_pořadí_skupin_a_poté_podle_názvu_položek'));
		$form->addCheckbox('order_storage_places', $this->translator->translate('Pořadí_položek_na_dodacím_listu_je_určeno_výchozí_pozicí_zadanou_v_číselníku_položek'));
        $form->addCheckbox('items_grouping', $this->translator->translate('Seskupovat_položky_stejných_názvu_a_cen'));
        $form->addCheckbox('cust_eml_off', $this->translator->translate('Netisknout_na_fakturách_emaily_odběratelů'));

        $form->addHidden('color_hex');

        $form->addSelect('pdf_name_type', $this->translator->translate('Název_PDF_dokladů'), $this->ArraysManager->getPdfNameTypes())
            ->setTranslator(NULL)
            ->setHtmlAttribute('class', 'form-control chzn-select input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Název_PDF_dokladů'));


        $form->addSubmit('submit', $this->translator->translate('Uložit'))
			->setHtmlAttribute('class', 'form-control btn-sm btn-primary');
		
		$form->onSuccess[] = array($this, "SettingsAdaptFormSubmitted");
		return $form;
	}
	
	
	/**
	 * Settings Adapt form submitted
	 * @param \Nette\Application\UI\Form
	 * @return void
	 */
	public function settingsAdaptFormSubmitted($form)
	{
		$values = $form->getValues();
		try {
			$arrValues = [];
			
			$arrValues['id'] = $this->UserManager->getCompany($this->getUser()->id)->id;
			$arrValues['order_group_label'] = $values['order_group_label'];
            $arrValues['order_storage_places'] = $values['order_storage_places'];
            $arrValues['items_grouping'] = $values['items_grouping'];
            $arrValues['pdf_name_type'] = $values['pdf_name_type'];
            $arrValues['cust_eml_off'] = $values['cust_eml_off'];
			unset($values['submit']);
			unset($values['order_group_label']);
			
			$arrValues['own_names'] = json_encode($values);
			$arrValues['color_hex'] = $values['color_hex'];
			$this->CompaniesManager->update($arrValues);
			
			$this->activeTab2 = 4;
			$this->activeTab = 2;
			$this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
            //$this->redrawControl('content');
			$this->redirect('this', array('activeTab2' => 4, 'activeTab' => 2));
		} catch (\Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}
	
	/**
	 * Settings EET form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSettingsEETForm()
	{
		$form = new Form;
		//  $form->setTranslator($this->translator);
		
		$form->addUpload('upload_pfx', $this->translator->translate('PFX_soubor_s_certifikátem:'))
			->setHtmlAttribute('class', 'form-control input-sm')
			->addRule(Form::MAX_FILE_SIZE, $this->translator->translate('Maximální_velikost_souboru_je_64_kB'), 64 * 1024 /* v bytech */);
		$form->addPassword('eet_pass', $this->translator->translate('Heslo'))
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('autocomplete', 'off')
			->setHtmlAttribute('placeholder', '...........')
			->setRequired(FALSE);
		$form->addCheckbox('eet_active', $this->translator->translate('EET_aktivováno'));
		$form->addCheckbox('eet_test', $this->translator->translate('Testovací_režim'));
		$form->addText('eet_pfx', $this->translator->translate('Nahraný_soubor'))
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('readonly');
		$form->addText('eet_id_provoz', $this->translator->translate('ID_provozovny'))
			->setHtmlAttribute('class', 'form-control input-sm');
		$form->addText('eet_id_poklad', $this->translator->translate('ID_pokladny'))
			->setHtmlAttribute('class', 'form-control input-sm');
		
		$form->addSubmit('submit', $this->translator->translate('Uložit'))
			->setHtmlAttribute('class', 'form-control btn-sm btn-primary');
		
		$form->onSuccess[] = array($this, "SettingsEETFormSubmitted");
		return $form;
	}
	
	/**
	 * Settings EET form submitted
	 * @param \Nette\Application\UI\Form
	 * @return void
	 */
	public function settingsEETFormSubmitted($form)
	{
		$values = $form->getValues();
		try {
			$company_id = $this->UserManager->getCompany($this->getUser()->id)->id;
			$values['id'] = $company_id;
			
			unset($values['eet_pfx']);
			//upload pfx file to company folder
			$file = $form->getHttpData($form::DATA_FILE, 'upload_pfx');
			if ($file && $file->isOk()) {
				//$fileName = \Nette\Utils\Random::generate(64, 'A-Za-z0-9');
				$fileName = $file->getSanitizedName();
				$dataFolder = $this->CompaniesManager->getDataFolder($company_id);
				$destFile = $dataFolder . '/pfx/' . $fileName;
				$file->move($destFile);
				$values['eet_pfx'] = $fileName;
			}
			unset($values['submit']);
			unset($values['upload_pfx']);
			if (empty($values['eet_pass'])) {
				unset($values['eet_pass']);
			}
			
			//bdump($values);
			$this->CompaniesManager->update($values);
			$this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
			
			$this->activeTab2 = 7;
			$this->activeTab = 2;
			
			$this->redirect('this', array('activeTab2' => 7, 'activeTab' => 2));
			
		} catch (\Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}


    /**
     * Settings Archive functions form factory.
     * @return Nette\Application\UI\Form
     */
    protected function createComponentSettingsArchiveForm()
    {
        $form   = new Form;

        //$from   = new \Nette\Utils\DateTime;
        $to     = new \Nette\Utils\DateTime;
        /*$form->addText('date_from', $this->translator->translate('Archivovat od'), 0, 16)
            ->setDefaultValue('01.01.'.$from->modify('-1 year')->format('Y'))
            ->setHtmlAttribute('placeholder',$this->translator->translate('Archivovat od'));
        */
        $form->addText('date_to', $this->translator->translate('Archivovat_do'), 0, 16)
            ->setDefaultValue($to->modify('-1 year')->format('d.m.Y'))
            ->setHtmlAttribute('placeholder',$this->translator->translate('Archivovat_do'));

        $archives = $this->ArraysManager->getArchives();
        unset($archives['current']);
        $form->addSelect('dbName', $this->translator->translate('Dostupné_archívy'), $archives)
            ->setRequired($this->translator->translate("Archiv_musí_být_vybrán"))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_cílový_archiv_pro_převod_dat.'));

        $form->addSubmit('submit', $this->translator->translate('Archivovat'))
            ->setHtmlAttribute('class', 'form-control btn-sm btn-primary');

        $form->onSuccess[] = array($this, "SettingsArchiveFormSubmitted");
        return $form;
    }

    /**
     * Settings Archive form submitted
     * @param \Nette\Application\UI\Form
     * @return void
     */
    public function settingsArchiveFormSubmitted($form)
    {
        $values = $form->getValues();
    //    try {
     /*  if ($values['date_from'] == "")
            $values['date_from'] = NULL;
        else
            $values['date_from'] = date('Y-m-d H:i:s',strtotime($values['date_from']));*/

        if ($values['date_to'] == "")
            $values['date_to'] = NULL;
        else
            $values['date_to'] = date('Y-m-d H:i:s',strtotime($values['date_to']));

            $result = $this->ArchiveManager->moveToArchive( $values['date_to'], $values['dbName'], [], $this);
            //dump($result);
            $arrArchives = [];
            if(self::hasError($result)) {
                 $this->flashMessage($this->translator->translate('Došlo_k_chybě_při_archivaci'), 'warning');
                 $arrArchives['status'] = "Chyba při archivaci";
            } else {
                $this->flashMessage($this->translator->translate('Data_byly_archivovány'), 'success');
                $arrArchives['status'] = "OK";
            }
            $arrArchives['cl_company_id']   = $this->UserManager->getCompany($this->user->getId());
            $arrArchives['cl_users_id']     = $this->user->getId();
            $arrArchives['db_name']         = $values['dbName'];
            $arrArchives['to_date']         = $values['date_to'];
            $arrArchives['rec_count']       = $result['data']['records'];
            $this->ArchivesManager->insert($arrArchives);
            $this->activeTab = 7;

            $this->redirect('this', ['activeTab' => 7]);

      //  } catch (\Nette\Security\AuthenticationException $e) {
        //    $form->addError($e->getMessage());
        //}
    }


    protected function createComponentArchivesGrid()
    {
        $arrData = [
            'to_date' => [$this->translator->translate('Archivační_datum'), 'format' => "date", 'size' => 12],
            'db_name' => [$this->translator->translate('Název_archívu'), 'format' => 'text', 'size' => 15],
            'status' => [$this->translator->translate('Stav'), 'format' => 'text', 'size' => 10],
            'rec_count'  => [$this->translator->translate('Počet_záznamů'), 'format' => 'integer', 'size' => 10]
        ];
        $cl_company_id = $this->UserManager->getCompany($this->user->getId())->id;
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->ArchivesManager, //data manager
            $arrData, //data columns
            [], //row conditions
            $cl_company_id, //parent Id
            [], //default data
            $this->CompaniesManager, //parent data manager
            [], //pricelist manager
            [],
            TRUE, //enable add empty row
            [] //custom links
        );
        $control->setPaginatorOff();
        $control->setPricelistEnabled(false);
        $control->setEnableAddEmptyRow(false);
        $control->setReadOnly();
        $control->setOrder('to_date DESC');
        $control->onChange[] = function () {
            //$this->updateSum();
        };
        return $control;
    }
	
	/**
	 * Settings Helpdesk form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSettingsSMSManagerForm()
	{
		$form = new Form;
		//  $form->setTranslator($this->translator);
		
		$form->addText('sms_username', $this->translator->translate('Uživatelské_jméno'))
			->setHtmlAttribute('class', 'form-control input-sm');
		$form->addPassword('sms_password', $this->translator->translate('Heslo'))
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('autocomplete', 'off')
			->setHtmlAttribute('placeholder', $this->translator->translate('Heslo'))
			->setRequired(FALSE);
		$form->addPassword('sms_password2')
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('autocomplete', 'off')
			->setHtmlAttribute('placeholder', $this->translator->translate('Heslo_znovu'))
			->addConditionOn($form['sms_password'], $form::FILLED)
			->addRule($form::EQUAL, $this->translator->translate('Hesla_se_neshodují'), $form['sms_password'])
			->setRequired(FALSE);
		
		$form->addSelect('sms_type', $this->translator->translate('Typ_SMS'), [\SMSManager\SMS::REQUEST_TYPE_LOW => $this->translator->translate('Nízké_náklady'),
			\SMSManager\SMS::REQUEST_TYPE_HIGH => $this->translator->translate('Vysoká_kvalita'),
			\SMSManager\SMS::REQUEST_TYPE_DIRECT => $this->translator->translate('Přímá_SMS')]);
		$form->addText('sms_sender', $this->translator->translate('Odesílatel'))
			->setHtmlAttribute('class', 'form-control input-sm');
		
		$form->addCheckbox('hd_recieved', $this->translator->translate('SMS_správcům_helpdesku_při_externím_zápisu_do_helpdesku_přes_webový_formulář'));
		$form->addCheckbox('hd_recieved2', $this->translator->translate('SMS_správcům_helpdesku_při_příjmu_nového_požadavku_emailem'));
		
		$form->addSubmit('submit', $this->translator->translate('Uložit'))
			->setHtmlAttribute('class', 'form-control btn-sm btn-primary');
		
		$form->onSuccess[] = [$this, "SettingsSMSManagerFormSubmitted"];
		return $form;
	}
	
	
	/**
	 * Settings SMSManager form submitted
	 * @param \Nette\Application\UI\Form
	 * @return void
	 */
	public function settingsSMSManagerFormSubmitted($form)
	{
		$values = $form->getValues();
		try {
			$arrValues = array();
			
			$arrValues['id'] = $this->UserManager->getCompany($this->getUser()->id)->id;
			$tmpUserCompany = $this->CompaniesManager->find($arrValues['id']);
			$userTmpSMSManager = json_decode($tmpUserCompany->sms_manager, true);
			
			unset($values['submit']);
			
			if ($values['sms_password'] === $values['sms_password2']) {
				$values['sms_password'] = SHA1($values['sms_password']);
			} elseif (isset($userTmpSMSManager['sms_password'])) {
				$values['sms_password'] = $userTmpSMSManager['sms_password'];
			}
			unset($values['sms_password2']);
			
			$arrValues['sms_manager'] = json_encode($values);
			$this->CompaniesManager->update($arrValues);
			$this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
			
			$this->activeTab2 = 5;
			$this->activeTab = 2;
			
			$this->redirect('this', array('activeTab2' => 5, 'activeTab' => 2));
			
		} catch (\Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}
	
	
	/**
	 * Settings Helpdesk form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSettingsSMSTestForm()
	{
		$form = new Form;
		//  $form->setTranslator($this->translator);
		
		$form->addText('sms_phone', $this->translator->translate('Testovací_příjemce'))
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('placeholder', $this->translator->translate('Telefonní_číslo'));
		$form->addText('sms_message', $this->translator->translate('Testovací_zpráva'), 60, 60)
			->setHtmlAttribute('class', 'form-control input-sm');
		
		$form->addSubmit('sendSMS', $this->translator->translate('Odeslat_testovací_SMS'))
			->setHtmlAttribute('class', 'form-control btn-sm btn-primary');
		
		
		$form->onSuccess[] = array($this, "SettingsSMSTestFormSubmitted");
		return $form;
	}
	
	/**
	 * Settings SMSManager form submitted
	 * @param \Nette\Application\UI\Form
	 * @return void
	 */
	public function settingsSMSTestFormSubmitted($form)
	{
		$values = $form->getValues();
		try {
			
			if ($form['sendSMS']->isSubmittedBy()) {
				$arrValues = array();
				$arrValues['id'] = $this->UserManager->getCompany($this->getUser()->id)->id;
				$tmpUserCompany = $this->CompaniesManager->find($arrValues['id']);
				$userTmpSMSManager = json_decode($tmpUserCompany->sms_manager, true);
				
				if (!isset($userTmpSMSManager['sms_password']) || empty($userTmpSMSManager['sms_password'])) {
					$this->flashMessage($this->translator->translate('Testovací_SMS_nebyla_odeslána_protože_není_zadáno_heslo'), 'danger');
					
				} elseif (!isset($userTmpSMSManager['sms_username']) || empty($userTmpSMSManager['sms_username'])) {
					$this->flashMessage($this->translator->translate('Testovací_SMS_nebyla_odeslána_protože_není_zadáno_přihlašovací_jméno'), 'danger');
					
				} elseif (!isset($values['sms_phone']) || empty($values['sms_phone'])) {
					$this->flashMessage($this->translator->translate('Testovací_SMS_nebyla_odeslána_protože_není_zadáno_telefonní_číslo_příjemce'), 'danger');
				} elseif (!isset($values['sms_message']) || empty($values['sms_message'])) {
					$this->flashMessage($this->translator->translate('Testovací_SMS_nebyla_odeslána_protože_není_zadadná_zpráva'), 'danger');
				} else {
					
					if ($this->smsService->sendSMS($values['sms_message'], array($this->getUser()->id => $values['sms_phone']), $tmpUserCompany)) {
						$this->flashMessage($this->translator->translate('Testovací_SMS_byla_odeslána'), 'success');
					} else {
						$this->flashMessage($this->translator->translate('SMS_nebyla_odeslána'), 'danger');
					}
				}
				$this->activeTab2 = 5;
				$this->activeTab = 2;
				$this->redirect('this', array('activeTab2' => 5, 'activeTab' => 2));
			}
		} catch (\SMSManager\SMSHttpException $e) {
			$this->activeTab2 = 5;
			$this->activeTab = 2;
			$this->flashMessage($this->translator->translate('SMS_nebyla_odeslána'), 'danger');
			$this->flashMessage($e->getMessage(), 'danger');
			$this->redirect('this', array('activeTab2' => 5, 'activeTab' => 2));
			
		}
	}


	/**
	 * Settings Digitoo form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSettingsDigitooForm()
	{
		$form = new Form;
		//  $form->setTranslator($this->translator);
		
		$form->addTextArea('digitoo_token', $this->translator->translate('Digitoo_token'), 40,5)
			->setHtmlAttribute('class', 'form-control input-sm');

		$form->addSubmit('submit', $this->translator->translate('Uložit'))
			->setHtmlAttribute('class', 'form-control btn-sm btn-primary');
		
		$form->onSuccess[] = [$this, "SettingsDigitooFormSubmitted"];
		return $form;
	}
	
	
	/**
	 * Settings Digitoo form submitted
	 * @param \Nette\Application\UI\Form
	 * @return void
	 */
	public function settingsDigitooFormSubmitted($form)
	{
		$values = $form->getValues();
		try {
			$arrValues = array();
			$arrValues['id'] = $this->UserManager->getCompany($this->getUser()->id)->id;
			$arrValues['digitoo_token'] = $values['digitoo_token'];
 			unset($values['submit']);
			$this->CompaniesManager->update($arrValues);
			$this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
			
			$this->activeTab2 = 5;
			$this->activeTab = 2;
			
			$this->redirect('this', array('activeTab2' => 12, 'activeTab' => 2));
			
		} catch (\Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}


	
	public function handleRepairEANDuplicity()
	{
		$result = $this->PriceListManager->repairEANDuplicity();
		
		if (empty($result)) {
			$this->flashMessage($this->translator->translate('Bylo_opraveno'), 'success');
		} else {
			$this->flashMessage($this->translator->translate('Došlo_k_chybě_a_nebylo_opraveno') . $result->getMessage(), 'error');
		}
		$this->redrawControl('content');
	}
	
	/**
	 * recalc of cl_invoice_items.price_s, cl_store_move.price_s, cl_store_move.profit, cl_store_docs.price_s, cl_store_docs.profit
	 *
	 */
	public function handleRepairPriceS()
	{
		//1. repair of cl_store_move.price_s and cl_store_move.profit for outcome
		//2. repair of cl_store_docs.price_s and cl_store_docs.profit, cl_store_docs.profit_abs
		//3. repair of cl_invoice_items.price_s
		//4. repair of cl_delivery_note_items.price_S
		$result = $this->StoreManager->repairOutcomePriceS();
		if (empty($result)) {
			$this->flashMessage($this->translator->translate('Byly_přepočítány_výdejní_ceny'), 'success');
		} else {
			$this->flashMessage($this->translator->translate('Došlo_k_chybě_a_nebyly_přepočítány_výdejní_ceny') . $result->getMessage(), 'error');
		}
		$this->redrawControl('content');
	}


    /**
     * update cl_pricelist.price_s
     *
     */
    public function handleUpdatePriceS()
    {
        $result = $this->PriceListManager->updatePriceS(NULL, TRUE);
        if ($result > 0) {
            $this->flashMessage($this->translator->translate('Byly_aktualizovány_skladové_ceny_v_ceníku') . $result . $this->translator->translate('položek'), 'success');
        } else {
            $this->flashMessage($this->translator->translate('Došlo_k_chybě_a_nebyly_aktualizovány_skladové_ceny'), 'error');
        }

        $this->redrawControl('content');

    }

    /**
     * fill missing price_s into cl_order_items
     *
     */
    public function handleRepairOrderS()
    {

        $result = $this->OrderManager->repairOrderPriceS();
        if (empty($result)) {
            $this->flashMessage($this->translator->translate('Byly_doplněny_chybějící_výdejní_ceny_do_nehotových_objednávek'), 'success');
        } else {
            $this->flashMessage($this->translator->translate('Došlo_k_chybě_a_nebyly_doplněny_výdejní_ceny.') . $result->getMessage(), 'error');
        }

        $this->redrawControl('content');

    }
	
	/**
	 * check if every outcome is writen from specific income
	 *
	 */
	public function handleRepairMinuseS()
	{
		$result = $this->StoreManager->repairMinuses();
		if (empty($result)) {
			$this->flashMessage($this->translator->translate('Byly_zkontrolovány_mínusové_výdeje_a_doklady_byly_označeny'), 'success');
		} else {
			$this->flashMessage($this->translator->translate('Došlo_k_chybě_a_nebyly_zkontrolovány_mínusové_výdeje') . $result->getMessage(), 'error');
		}
		$this->redrawControl('content');
	}

    public function handleUpdateZeroPriceSOut()
    {
        $result = $this->StoreManager->updateZeroPriceSOut();
        if (empty($result)) {
            $this->flashMessage($this->translator->translate('Byla_doplněnea_skladová_cena_výdejů_tam_kde_chyběla'), 'success');
        } else {
            $this->flashMessage($this->translator->translate('Došlo_k_chybě: ') . $result->getMessage(), 'error');
        }
        $this->redrawControl('content');
    }

    public function handleRepairCommission()
    {
        session_write_close();
        $arrDocs = $this->CommissionManager->findAll()->where('locked = 0');
        $total = $arrDocs->count('id');
        $i = 0;
        foreach($arrDocs as $key => $one) {
            $this->UserManager->setProgressBar($i, $total, $this->user->getId());
            $this->CommissionManager->UpdateSum($key);
            $i++;
        }
        $this->UserManager->resetProgressBar($this->user->getId());
        $this->flashMessage($this->translator->translate('Součty_na_nezamčených_zakázkách_byly_zaktualizovány.'), 'success');
        $this->redrawControl('content');
    }

	
	public function handleRepairIncomeDocs()
	{
        session_write_close();
		$arrDocs = $this->StoreDocsManager->findAll()->where('doc_type = 0');
		$total = $arrDocs->count('id');
		$i = 0;
		foreach($arrDocs as $key => $one) {
			$this->UserManager->setProgressBar($i, $total, $this->user->getId());
			$this->StoreManager->UpdateSum($key);
			$i++;
		}
        $this->UserManager->resetProgressBar($this->user->getId());
		$this->flashMessage($this->translator->translate('Součty_příjmových_dokladů_byly_zaktualizován.'), 'success');
		$this->redrawControl('content');
	}
	
	/**Fill cl_pricelist_limits with data from cl_store
	 * @throws \Exception
	 */
	public function handleFillLimits()
	{
        session_write_close();
		$tmpStore = $this->StoreManager->findAll()->select('cl_storage_id, cl_pricelist_id, quantity_min, quantity_req')->
											where('(quantity_min > 0 OR quantity_req > 0) AND cl_storage_id IS NOT NULL AND cl_pricelist_id IS NOT NULL')->
											order('cl_pricelist_id, quantity_min DESC, quantity_req DESC')->
											group('cl_pricelist_id,cl_storage_id');
		$total = $tmpStore->count('cl_storage_id');
		$i = 0;
		foreach($tmpStore as $key => $one)
		{
			$this->UserManager->setProgressBar($i, $total, $this->user->getId());
			$arr = array();
			$arr['cl_pricelist_id'] = $one['cl_pricelist_id'];
			$arr['cl_storage_id'] = $one['cl_storage_id'];
			$arr['quantity_min'] = $one['quantity_min'];
			$arr['quantity_req'] = $one['quantity_req'];
			$arr['changed']	=	new DateTime();
			$arr['created']	=	new DateTime();
			$arr['change_by'] = $this->user->getIdentity()->name;
			$tmpLimit = $this->PriceListLimitsManager->findAll()->where('cl_pricelist_id = ? AND cl_storage_id = ?', $one['cl_pricelist_id'], $one['cl_storage_id'])->fetch();
			if ($tmpLimit){
				$arr['id'] = $tmpLimit['id'];
				$this->PriceListLimitsManager->update($arr);
			}else {
				$tmpLimit = $this->PriceListLimitsManager->insert($arr);
			}
			$tmpStore2U = $this->StoreManager->findAll()->where('cl_pricelist_id = ? AND cl_storage_id = ?', $one['cl_pricelist_id'], $one['cl_storage_id']);
			$tmpStore2U->update(array('cl_pricelist_limits_id' => $tmpLimit['id']));
			$i++;
		}
        $this->UserManager->resetProgressBar($this->user->getId());
		$this->flashMessage($this->translator->translate('Byly vytvořeny limity ceníku podle staré verze'), 'success');
		$this->redrawControl('content');
		
	}


    public function handleInvoiceArrivedSumUpdate()
    {
        //session_write_close();
        $tmpIA = $this->InvoiceArrivedManager->findAll()->where('price_payed < 0');
        foreach($tmpIA as $key => $one) {
            $this->InvoiceArrivedManager->updateInvoiceSum($key);
        }
        $this->flashMessage($this->translator->translate('Součty_přijatých_faktur_byly_aktualizovány'), 'success');
        //$tmpI = $this->InvoiceManager->findAll()->where('price_payed < 0');
        $tmpI = $this->InvoiceManager->findAll()->where('price_payed != price_e2_vat AND price_payed !=0');
        foreach($tmpI as $key => $one) {
            $this->InvoiceManager->updateInvoiceSum($key);
        }
        $this->flashMessage($this->translator->translate('Součty_vydaných_faktur_byly_aktualizovány'), 'success');
        $this->redrawControl('content');
    }

    public function handleBankTransSum()
    {
        //session_write_close();
        $tmpIA = $this->BankTransManager->findAll()->where('amount_paired != 0');
        foreach($tmpIA as $key => $one) {
            $tmpOne = false;
            if (!is_null($one['cl_invoice_id'])){
                $tmpOne = $this->InvoicePaymentsManager->findAll()->where('cl_invoice_id = ?', $one['cl_invoice_id'])->fetch();
            }
            if (!is_null($one['cl_invoice_arrived_id'])){
                $tmpOne = $this->InvoiceArrivedPaymentsManager->findAll()->where('cl_invoice_arrived_id = ?', $one['cl_invoice_arrived_id'])->fetch();
            }
            if (!is_null($one['cl_invoice_advance_id'])){
                $tmpOne = $this->InvoiceAdvancePaymentsManager->findAll()->where('cl_invoice_advance_id = ?', $one['cl_invoice_advance_id'])->fetch();
            }
            if($tmpOne){
                bdump($tmpOne);
                $tmpOne->update(array('cl_bank_trans_id' => $key));
            }

            $this->BankTransManager->updateSum($one);
        }
        $this->flashMessage($this->translator->translate('Součty_spárovaných_faktur_byly_aktualizovány'), 'success');
        $this->redrawControl('content');
    }


	public function handleFillSupplierSum()
    {
        session_write_close();
        $tmpCompanyBranchId = $this->user->getIdentity()->cl_company_branch_id;
        $cl_storage_id = NULL;
        if (!is_null($tmpCompanyBranchId)) {
            if ($tmpBranch = $this->CompanyBranchManager->findAll()->where('id = ?', $tmpCompanyBranchId)->limit(1)->fetch())
                $cl_storage_id = $tmpBranch->cl_storage_id;
        } else {
            $cl_storage_id = $this->settings->cl_storage_id;
        }

        $this->StoreManager->updateSupplierSum($cl_storage_id);

        $this->flashMessage($this->translator->translate('Byly_naplněny_součty_nedodaných_položek_dodavatelů'), 'success');
        $this->redrawControl('content');
    }

    /**
     * update cl_invoice.pay_date
     *
     */
    public function handleRepairInvoicePayment()
    {
        $result = $this->InvoiceManager->RepairInvoicePayment();
        if (!self::hasError($result)) {
            $this->flashMessage($this->translator->translate('Byly_zaktualizovány_datumy_úhrad_u_faktur_Počet:'). $result['success'], 'success');
        } else {
            $this->flashMessage($this->translator->translate('Došlo_k_chybě_a_nebyly_zaktualizovány_datumy_úhrad_faktur') . $result['error'], 'error');
        }
        $this->redrawControl('content');

    }

    public function handleUpdateZeroPriceS()
    {
        $result = $this->StoreManager->UpdateZeroPriceS();
        if (!self::hasError($result)) {
            $this->flashMessage($this->translator->translate('Byly_zaktualizovány_nákupní_ceny_v_příjmech_na_sklad_Počet:'). $result['success'], 'success');
            //$result = $this->handleRepairPriceS();
            $result = $this->StoreManager->repairOutcomePriceS();
            if (empty($result)) {
                $this->flashMessage($this->translator->translate('Byly_přepočítány_výdejní_ceny'), 'success');
            } else {
                $this->flashMessage($this->translator->translate('Došlo_k_chybě_a_nebyly_přepočítány_výdejní_ceny') . $result->getMessage(), 'error');
            }
        } else {
            $this->flashMessage($this->translator->translate('Došlo_k_chybě_a_nebyly_zaktualizovány_nákupní_ceny_v_příjmech_na_sklad') . $result['error'], 'error');
        }
        $this->redrawControl('content');

    }

    /**
     * Settings SMTP email form factory.
     * @return Nette\Application\UI\Form
     */
    protected function createComponentSettingsSMTPForm()
    {
        $form = new Form;
        $form->addText('smtp_host', $this->translator->translate('SMTP_server'))
            ->setHtmlAttribute('class', 'form-control input-sm');

        $form->addText('email_name', $this->translator->translate('Jméno_odesilatele'))
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Pokud_není_vyplněno_použije_se_název_firmy'));
        $form->addText('smtp_username', $this->translator->translate('Přihlašovací_jméno'))
            ->setHtmlAttribute('autocomplete', 'off')
            ->setHtmlAttribute('class', 'form-control input-sm');
        $form->addPassword('smtp_password', $this->translator->translate('Heslo'))
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('autocomplete', 'new-password')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Heslo'));

        $form->addText('smtp_port', $this->translator->translate('Port'))
            ->setHtmlAttribute('class', 'form-control input-sm');

        $form->addRadioList('smtp_secure', $this->translator->translate('Šifrovaná_komunikace'),
                            [0 => $this->translator->translate('nezabezpečeno'),
                             1 => $this->translator->translate('ssl'),
                             2 => $this->translator->translate('tls')]);

        $form->addCheckbox('smtp_email_global', $this->translator->translate('Globální_emailová_adresa_odchozí_pošty'))
            ->setHtmlAttribute('title', $this->translator->translate('Použít_přihlašovací_email_k_SMTP_serveru_nebo_email_firmy_jako_globální_pro_všechny_odchozí_emaily'))
            ->setHtmlAttribute('class', 'form-control input-sm');

        //dump($form['smtp_email_global']->getControlPrototype()->attrs['title']);
        //die;
        $form->addSubmit('submit', $this->translator->translate('Uložit'))
            ->setHtmlAttribute('class', 'form-control btn-sm btn-primary');

        $form->onSuccess[] = [$this, "SettingsSMTPFormSubmitted"];
        return $form;
    }


    /**
     * Settings SMTPform submitted
     * @param \Nette\Application\UI\Form
     * @return void
     */
    public function settingsSMTPFormSubmitted($form)
    {
        $values = $form->getValues();
        try {
            $arrValues = [];

            $arrValues['id']                    = $this->UserManager->getCompany($this->getUser()->id)->id;
            $arrValues['smtp_host']             = $values['smtp_host'];
            $arrValues['smtp_username']         = $values['smtp_username'];
            if ($values['smtp_password'] != '')
                $arrValues['smtp_password']     = $values['smtp_password'];

            $arrValues['smtp_secure']           = $values['smtp_secure'];

            $arrValues['smtp_email_global']     = $values['smtp_email_global'];
            $arrValues['smtp_port']             = $values['smtp_port'];
            $arrValues['email_name']            = $values['email_name'];

            $this->CompaniesManager->update($arrValues);
            $this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');

            $this->activeTab2 = 9;
            $this->activeTab = 2;

            $this->redirect('this', ['activeTab2' => 9, 'activeTab' => 2]);


        } catch (\Nette\Security\AuthenticationException $e) {
            $form->addError($e->getMessage());
        }
    }


    /**
     * Settings SMTP Test form factory.
     * @return Nette\Application\UI\Form
     */
    protected function createComponentSettingsSMTPTestForm()
    {
        $form = new Form;
        //  $form->setTranslator($this->translator);

        $form->addText('email', $this->translator->translate('Testovací_příjemce'))
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Emailová_adresa'));
        $form->addText('test_subject', $this->translator->translate('Testovací_zpráva'), 60, 60)
            ->setHtmlAttribute('class', 'form-control input-sm');
        $form->addSubmit('sendEmail', $this->translator->translate('Odeslat_testovací_email'))
            ->setHtmlAttribute('class', 'form-control btn-sm btn-primary');

        $form->onSuccess[] = array($this, "SettingsSMTPTestFormSubmitted");
        return $form;
    }

    /**
     * Settings SMTP Test form submitted
     * @param \Nette\Application\UI\Form
     * @return void
     */
    public function settingsSMTPTestFormSubmitted($form)
    {
        $values = $form->getValues();
        try {

            if ($form['sendEmail']->isSubmittedBy()) {
                $arrValues = [];
                $arrValues['id'] = $this->UserManager->getCompany($this->getUser()->id)->id;
                $tmpUserCompany = $this->CompaniesManager->find($arrValues['id']);
                //$userTmpSMSManager = json_decode($tmpUserCompany->sms_manager, true);

                if (empty($tmpUserCompany['smtp_password'])) {
                    $this->flashMessage($this->translator->translate('Email_nebyl_odeslán_chybí_heslo_smtp_serveru'), 'danger');

                } elseif (empty($tmpUserCompany['smtp_username'])) {
                    $this->flashMessage($this->translator->translate('Email_nebyl_odeslán_protože_není_zadáno_přihlašovací_jméno'), 'danger');

                } elseif (empty($tmpUserCompany['smtp_host'])) {
                    $this->flashMessage($this->translator->translate('Email_nebyl_odeslán_protože_není_zadána_adresa_smtp_serveru'), 'danger');
                } elseif ($tmpUserCompany['smtp_port'] == 0) {
                    $this->flashMessage($this->translator->translate('Email_nebyl_odeslán_protože_není_zadán_port_smtp_serveru'), 'danger');
                } else {

                    $emailTo = [$values['email'], $values['email']];
                    $emailFrom = $this->settings->email;
                    if ($tmpUserCompany['smtp_email_global'] == 1)
                    {
                        $emailFrom = $this->validateEmail2($tmpUserCompany['smtp_username']);
                        if (empty($emailFrom)){
                            $emailFrom = $this->validateEmail2($tmpUserCompany['email']);
                        }
                        $emailFrom = (empty($tmpUserCompany['email_name']) ? $tmpUserCompany['name'] : $tmpUserCompany['email_name']) . ' <' . $emailFrom . '>';
                    }

                    $subject = $values['test_subject'];
                    $body = $this->translator->translate('Toto_je_testovací_zpráva_ze_serveru_klienti_cz');
                    $this->emailService->sendMail($this->settings, $emailFrom, $emailTo, $subject, $body);

                    $this->flashMessage($this->translator->translate('Testovací_email_byl_odeslán'), 'success');

                }
                $this->activeTab2 = 9;
                $this->activeTab = 2;
                $this->redrawControl('content');
               // $this->redirect('this', array('activeTab2' => 9, 'activeTab' => 2));
            }
        } catch (\Exception $e) {
            $this->activeTab2 = 9;
            $this->activeTab = 2;
            $this->flashMessage($this->translator->translate('Testovací_email_nebyl_odeslán'), 'danger');
            $this->flashMessage($e->getMessage(), 'danger');
            $this->redrawControl('content');
            //$this->redirect('this', array('activeTab2' => 9, 'activeTab' => 2));

        }
    }


    public function actionQrConfig($data){
        $tmpData = $this->CompaniesManager->getTable()->where('cl_company.id', $this->UserManager->getCompany($this->getUser()->id)->id)->fetch();
        //outside
        //$qrCode = $this->qrService->getQrConfig(['sync_token' => $tmpData['sync_token'], 'url' => 'https://detekce.isstar.cz']);
        //inside
        //$qrCode = $this->qrService->getQrConfig(['sync_token' => $tmpData['sync_token'], 'url' => 'http://192.168.1.10:8080']);
        $srvName = $_SERVER['HTTP_HOST'];
        $srvProt = "https://";
        if (substr_count($srvName, ".") == 0 ){
            $srvName = $_SERVER['SERVER_ADDR'];
            $srvProt = "http://";
        }
        $srvPort = $_SERVER['SERVER_PORT'];
        if ($srvPort === "80"){
            $srvPort = "";
        }else{
            $srvPort = ":" . $srvPort;
        }
        $srvFullName = $srvProt . $srvName . $srvPort;
        $qrCode = $this->qrService->getQrConfig(['url' => $srvFullName, 'sync_token' => $tmpData['sync_token']], '- Trynx -');
        $this->httpResponse->setContentType('image/jpg');
        echo($qrCode);

        $this->terminate();
    }


    public function handleGenCode(){
        $arrData['id'] = $this->UserManager->getCompany($this->getUser()->id)->id;;
        $arrData['sync_token'] = \Nette\Utils\Random::generate(32, 'A-Za-z0-9');
        $this->CompaniesManager->update($arrData);
        $this->flashMessage('Nový kód byl vytvořen.', 'success');
        $this->redrawControl('synctoken');
        $this->redrawControl('flash');
    }

    public function handleEraseCode(){
        $arrData['id']          = $this->UserManager->getCompany($this->getUser()->id)->id;
        $arrData['sync_token']  = '';
        $this->CompaniesManager->update($arrData);
        $this->flashMessage('Kód byl vymazán.', 'success');
        $this->redrawControl('synctoken');
        $this->redrawControl('flash');

    }

    public function handleMoveInvoice2Advance(){
        session_write_close();
        $tmpData = $this->InvoiceManager->findAll()->where('cl_invoice_types.inv_type = 3');
        $i = 0;
        $total = $tmpData->count();
        foreach($tmpData as $key => $one){
            try {
                $this->UserManager->setProgressBar($i, $total, $this->user->getId());
                $oneArr = $one->toArray();
                unset($oneArr['id']);
                unset($oneArr['cl_store_docs_id']);
                $newAdvance = $this->InvoiceAdvanceManager->insert($oneArr);
                foreach ($one->related('cl_invoice_items') as $key2 => $one2) {
                    $itemArr = $one2->toArray();
                    unset($itemArr['id']);
                    unset($itemArr['cl_invoice_id']);
                    unset($itemArr['cl_delivery_note_items_id']);

                    $itemArr['cl_invoice_advance_id'] = $newAdvance['id'];
                    $newItem = $this->InvoiceAdvanceItemsManager->insert($itemArr);
                }
                foreach ($one->related('cl_invoice_payments') as $key2 => $one2) {
                    $itemArr = $one2->toArray();
                    unset($itemArr['id']);
                    unset($itemArr['cl_invoice_id']);
                    unset($itemArr['cl_transport_docs_id']);

                    $itemArr['cl_invoice_advance_id'] = $newAdvance['id'];
                    $newItem = $this->InvoiceAdvancePaymentsManager->insert($itemArr);
                }

                foreach ($one->related('cl_paired_docs') as $key2 => $one2) {
                    $one2->update(['cl_invoice_id' => NULL, 'cl_invoice_advance_id' => $newAdvance['id']]);
                }

                foreach ($one->related('cl_files') as $key2 => $one2) {
                    $one2->update(['cl_invoice_id' => NULL, 'cl_invoice_advance_id' => $newAdvance['id']]);
                }

                if ($items2Delete = $this->InvoiceItemsManager->findAll()->where('cl_invoice_id = ?', $key)) {
                    $items2Delete->delete();
                }
                if ($payments2Delete = $this->InvoicePaymentsManager->findAll()->where('cl_invoice_id = ?', $key)) {
                    $payments2Delete->delete();
                }

                $one->delete();
            }catch(\Exception $e){
                $this->flashMessage('Došlo_k_chybě', 'warning');
                $this->flashMessage($e->getMessage());
                Debugger::log('MoveInvoice2Advance . ' . $e->getMessage());
            }
            $i++;
        }
        $this->UserManager->resetProgressBar($this->user->getId());
        $this->redrawControl('content');
    }

    public function handleRemoveDuplicateInvoice(){
        //session_write_close();
        $tmpData = $this->InvoiceManager->findAll();
        //SELECT id, inv_number FROM `cl_invoice` WHERE cl_company_id=2201 AND EXISTS(SELECT id FROM cl_invoice AS xx WHERE xx.inv_number=cl_invoice.inv_number AND xx.id != cl_invoice.id AND cl_company_id=2201)
        $tmpData = $this->InvoiceManager->getDuplicateInvoices();
        //bdump($tmpData->fetchAll());
        //die;
        $i = 0;
        $total = count($tmpData);
        foreach($tmpData as $key => $one){
            try {
                $this->UserManager->setProgressBar($i, $total, $this->user->getId());

                $tmpDuplicate = $this->InvoiceManager->findAll()->where('inv_number = ?', $one['inv_number'])->order('pay_date ASC')->limit(1);
                foreach($tmpDuplicate as $key2 => $one2)
                {
                    if ($items2Delete = $this->InvoiceItemsManager->findAll()->where('cl_invoice_id = ?', $key2)) {
                        $items2Delete->delete();
                    }
                    if ($payments2Delete = $this->InvoicePaymentsManager->findAll()->where('cl_invoice_id = ?', $key2)) {
                        $payments2Delete->delete();
                    }

                    $one2->delete();
                }

            }catch(\Exception $e){
                $this->flashMessage($this->translator->translate('Došlo_k_chybě'), 'warning');
                $this->flashMessage($e->getMessage());
                Debugger::log('MoveInvoice2Advance . ' . $e->getMessage());
            }
            $i++;
        }
        $tmpData = $this->InvoiceManager->getDuplicateInvoices();
        $total = count($tmpData) - 1;
        $this->UserManager->resetProgressBar($this->user->getId());
        $this->flashMessage($this->translator->translate('Bylo_odstraněno_') . $i . $this->translator->translate('_faktur'), 'success');
        if ($total > 0){
            $this->flashMessage($this->translator->translate('Ještě_zbývá_') . $total . $this->translator->translate('_duplicit_k_odstranění'), 'danger');
        }
        $this->redrawControl('content');
    }


    /**
     * Settings Helpdesk form factory.
     * @return Nette\Application\UI\Form
     */
    protected function createComponentSettingsComplaintForm()
    {
        $form = new Form;
        $form->addTextArea('enabled_observers_emails', $this->translator->translate('Povolené_emailové_adresy_pro_pozorovatele'),100,7)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder',$this->translator->translate('emailové_adresy_oddělené_čárkou_nebo_domény_oddělené_čárkou'));
        $form->addSubmit('submit', $this->translator->translate('Uložit'))
            ->setHtmlAttribute('class', 'form-control btn-sm btn-primary');
        $form->onSuccess[] = [$this, "SettingsComplaintFormSubmitted"];
        return $form;
    }


    /**
     * Settings Adapt form submitted
     * @param \Nette\Application\UI\Form
     * @return void
     */
    public function settingsComplaintFormSubmitted($form)
    {
        $values = $form->getValues();
        try {
            $arrValues = [];
            $arrValues['id'] = $this->UserManager->getCompany($this->getUser()->id)->id;
            $arrValues['enabled_observers_emails'] = $values['enabled_observers_emails'];
            $this->CompaniesManager->update($arrValues);
            $this->activeTab2 = 11;
            $this->activeTab = 2;
            $this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
            $this->redirect('this', array('activeTab2' => 11, 'activeTab' => 2));
        } catch (\Nette\Security\AuthenticationException $e) {
            $form->addError($e->getMessage());
        }
    }



}
