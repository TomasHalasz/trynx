<?php

namespace App\B2BModule\Presenters;


use App\Model\Passwords;
use App\Model\UserManager;
use App\Controls;
use Nette\Application\UI\Form;
use Nette\Utils\DateTime;
use Nette\Utils\Image;
use Tracy\Debugger;


class SettingsPresenter extends \App\B2BModule\Presenters\BasePresenter
{
	
	public $activeTab = 1;
    public $myReadOnly = false, $forceRO = false;
    public $id;

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
	 * @var \App\Model\RatesVatManager
	 */
	public $RatesVatManager;

	/**
	 * @inject
	 * @var \App\Model\CountriesManager
	 */
	public $CountriesManager;

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
     * @var \App\Model\PartnersBranchManager
     */
    public $PartnersBranchManager;



	/**
	 * @inject
	 * @var \App\Model\CurrenciesManager
	 */
	public $CurrenciesManager;

	public function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['B2BModule.Settings']);
    }

    public function actionDefault($activeTab = NULL)
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->flashMessage($this->translator->translate('Zadali_jste_neplatný_odkaz'), 'danger');
            $this->redirect(':B2B:Mainpage:default');
        }
        parent::actionDefault();
        $this->template->activeTab = is_null($activeTab) ? $this->activeTab : $activeTab;
    }

    public function renderDefault()
	{

        $cl_partners_book_id = $this->user->getIdentity()->cl_partners_book_id;
        $cl_partners_branch_id = $this->user->getIdentity()->cl_partners_branch_id;
		$userCompanyTmp = $this->PartnersManager->findAll()->where('id = ?', $cl_partners_book_id)->fetch();
		$userCompany = $userCompanyTmp->toArray();
		if (!is_null($cl_partners_branch_id)){
		    $tmpBranch = $this->PartnersBranchManager->findAll()->where('id = ?', $cl_partners_branch_id)->fetch();
		    if ($tmpBranch) {
                $userCompany['company2'] = $tmpBranch['b_name'];
                $userCompany['street2'] = $tmpBranch['b_street'];
                $userCompany['city2'] = $tmpBranch['b_city'];
                $userCompany['zip2'] = $tmpBranch['b_zip'];
                $userCompany['country2'] = (!is_null($tmpBranch['cl_countries_id']) ? $tmpBranch->cl_countries['name'] : NULL);
           }
        }

        $this->id = $cl_partners_book_id;
		$this['settingsCompanyForm']->setValues($userCompany);
		if ($this->user->isInRole('b2b')) {
            $userTmp = $this->PartnersBookWorkersManager->findAll()->where('id = ?', $this->user->getId())->fetch();
            $this['settingsUserForm']->setValues($userTmp);
        }

		$this->template->userCompany = $userCompanyTmp;
        $this->template->role = $this->user->isInRole('b2b') ? 'b2b' : 'user';
		//bdump($this->activeTab);

	}

    protected function createComponentPartnersBranchGrid()
    {
        $arrData = array('use_as_main' => array($this->translator->translate('Fakturační_adresa'), 'format' => 'boolean', 'size' => 7),
            'b_name' => array($this->translator->translate('Název'), 'format' => "text", 'size' => 20),
            'b_street' => array($this->translator->translate('Ulice'), 'format' => 'text', 'size' => 15),
            'b_city' => array($this->translator->translate('Město'), 'format' => "text", 'size' => 15),
            'b_zip' => array($this->translator->translate('PSČ'), 'format' => 'text', 'size' => 15),
            'b_ico' => array($this->translator->translate('IČO'), 'format' => 'text', 'size' => 15),
            'b_dic' => array($this->translator->translate('DIČ'), 'format' => 'text', 'size' => 15),
            'cl_countries.name' => array($this->translator->translate('Stát'), 'format' => 'text', 'size' => 15,
                'values' => $this->CountriesManager->findAllTotal()->order('name')->fetchPairs('id', 'name')),
            'b_person' => array($this->translator->translate('Osoba'), 'format' => "text", 'size' => 15),
            'b_email' => array($this->translator->translate('Email'), 'format' => 'text', 'size' => 10),
            'b_phone' => array($this->translator->translate('Telefon'), 'format' => 'text', 'size' => 10)
        );
        //$userTmp = $this->PartnersBookWorkersManager->findAll()->where('id = ?', $this->user->getId())->fetch();
        $cl_partners_book_id = $this->user->getIdentity()->cl_partners_book_id;
        $tmpData = $this->PartnersManager->findAll()->where('id = ?', $cl_partners_book_id)->fetch();

       // $tmpData = $this->PartnersManager->find($this->id);
        $arrDefData = array('use_as_main' => 1, 'b_name' => $tmpData['company'], 'b_street' => $tmpData['street'],
            'b_city' => $tmpData['city'], 'b_zip' => $tmpData['zip'], 'b_ico' => $tmpData['ico'],
            'b_dic' => $tmpData['dic'], 'cl_countries_id' => $tmpData['cl_countries_id']);
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->PartnersBranchManager, //data manager
            $arrData, //data columns
            array(), //row conditions
            $tmpData->id, //parent Id
            $arrDefData, //default data
            $this->PartnersManager, //parent data manager
            NULL, //pricelist manager
            NULL, //pricelist partner manager
            TRUE, //enable add empty row
            array() //custom links
        );

        $control->setPaginatorOff();
        $control->showHistory(false);
        //$control->setForceEnable(TRUE);
        //$control->onChange[] = function () {
        //    $this->updateSum();
        //};
        return $control;
    }

    protected function createComponentWorkersListGrid()
    {
        $cl_partners_book_id = $this->user->getIdentity()->cl_partners_book_id;
        $arrData = array(
            'worker_name' => array($this->translator->translate('Jméno_a_příjmení'), 'format' => "text", 'size' => 20),
            'worker_position' => array($this->translator->translate('Pozice'), 'format' => 'text', 'size' => 15),
            'worker_phone' => array($this->translator->translate('Telefon'), 'format' => "text", 'size' => 15),
            'worker_email' => array($this->translator->translate('Email'), 'format' => 'email', 'size' => 15),
            'worker_skype' => array($this->translator->translate('Skype'), 'format' => 'text', 'size' => 15),
            'worker_other' => array($this->translator->translate('Jiný_kontakt'), 'format' => "text", 'size' => 15),
            'cl_partners_branch.b_name' => array($this->translator->translate('Pobočka'), 'format' => 'select', 'size' => 10,
                                    'values' => $this->PartnersBranchManager->findAll()->
                                    where('cl_partners_book_id = ?', $cl_partners_book_id)->
                                    order('b_name ASC')
                                        ->fetchPairs('id', 'b_name')),
            'b2b_enabled' => array('B2B', 'format' => 'boolean', 'size' => 7),
            'password2' => array($this->translator->translate('B2B_heslo'), 'format' => "text", 'size' => 15),
            'b2b_master' => array($this->translator->translate('Správce_firmy'), 'format' => 'boolean', 'size' => 7)
        );
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->PartnersBookWorkersManager, //data manager
            $arrData, //data columns
            array(), //row conditions
            $cl_partners_book_id, //parent Id
            array('use_cl_invoice' => 1, 'use_cl_invoice_advance' => 1, 'use_cl_invoice_arrived' => 1, 'use_cl_offer' => 1, 'use_cl_commission' => 1, 'use_cl_order' => 1, 'use_cl_delivery_note' => 1,
                'use_cl_partners_event' => 1, 'use_cl_delivery' => 1, 'use_cl_store_docs' => 1, 'use_cl_sale' => 1, 'use_cl_cash' => 1), //default data
            $this->PartnersManager, //parent data manager
            NULL, //pricelist manager
            NULL, //pricelist partner manager
            TRUE, //enable add empty row
            array() //custom links
        );
        $control->setPaginatorOff();

        $control->onChange[] = function () {
          //  $this->updateSum();

        };
        return $control;

    }


    /**
	 * Settings company form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSettingsCompanyForm()
	{
		$form = new Form;
		//$form->setTranslator($this->translator);
		$form->addHidden('id', NULL);
		$form->addText('company', $this->translator->translate('Název_firmy'))
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
            ->setHtmlAttribute('data-validation-mode', 'live')
			->setRequired($this->translator->translate('Stát_musí_být_vybrán'))
			->setPrompt($this->translator->translate('Zvolte_stát'));

        $form->addText('company2', $this->translator->translate('Název_firmy'))
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Název_firmy'));
        $form->addText('street2', $this->translator->translate('Ulice'))
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Ulice'));
        $form->addText('zip2', $this->translator->translate('PSČ'))
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('PSČ'));
        $form->addText('city2', $this->translator->translate('Město'))
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Město'));
        $arrCountries2 = $this->CountriesManager->findAllTotal()->order('name')->fetchPairs('name', 'name');
        $form->addSelect('country2', $this->translator->translate("Stát"), $arrCountries2)
            ->setHtmlAttribute('data-validation-mode', 'live')
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
			->setHtmlAttribute('placeholder', $this->translator->translate('Email'));
		$form->addText('phone', $this->translator->translate('Telefon'))
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('placeholder', $this->translator->translate('Telefon'));
		$form->addText('web', $this->translator->translate('Web'))
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('placeholder', $this->translator->translate('web'));
		$form->addText('account_code', $this->translator->translate('Číslo_účtu'))
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
	public function settingsCompanyFormSubmitted(Form $form)
	{
        $data = $form->values;
        if ($form['submit']->isSubmittedBy()) {
            $data = $this->removeFormat($data);
            try {
                $cl_partners_branch_id = $this->user->getIdentity()->cl_partners_branch_id;
                if (!is_null($cl_partners_branch_id)) {
                    $tmpBranch = $this->PartnersBranchManager->findAll()->where('id = ?', $cl_partners_branch_id)->fetch();
                    if ($tmpBranch) {
                        $arrBranch = array();
                        $arrBranch['b_name']          = $data['company2'];
                        $arrBranch['b_street']          = $data['street2'];
                        $arrBranch['b_city']            = $data['city2'];
                        $arrBranch['b_zip']             = $data['zip2'];
                        $tmpCountries = $this->CountriesManager->findAll()->where('name = ?', $data['country2'])->fetch();
                        if ($tmpCountries){
                            $cl_countries_id = $tmpCountries->id;
                        }
                        $tmpBranch->update($arrBranch);
                    }
                    unset($data['company2']);
                    unset($data['street2']);
                    unset($data['city2']);
                    unset($data['zip2']);
                    unset($data['country2']);
                }
                $this->PartnersManager->update($data);
                $this->flashMessage($this->translator->translate('Změny_byly_uloženy.'), 'success');
                $this->activeTab = 1;

                //$this->redirect(this);
            } catch (\Exception $e) {
                $this->flashMessage($this->translator->translate('Změny_nebyly_uloženy.'), 'warning');
               // $form->addError($e->getMessage());
            }

        }
        $this->redrawControl('settings');
        $this->redrawControl('flash');
        //$this->redirect('this', array('activeTab' => 1));
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
		$form->addHidden('id', NULL);
		$form->addText('worker_name', $this->translator->translate('Jméno_a_příjmení'))
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('placeholder', $this->translator->translate('Jméno_a_příjmení_uživatele'));
		$form->addText('worker_email', $this->translator->translate('Email_pro_přihlášení'))
            ->setHtmlAttribute('data-validation-mode', 'live')
			->setHtmlAttribute('class', 'form-control input-sm')
			->setHtmlAttribute('placeholder', $this->translator->translate('Emailová_adresa'))
            ->setRequired($this->translator->translate('Email_musí_být_zadán.'));
		$form->addPassword('password2', $this->translator->translate('Heslo'))
            ->setHtmlAttribute('data-validation-mode', 'live')
			->setHtmlAttribute('class', 'form-control')
			->setHtmlAttribute('autocomplete', 'off')
			->setHtmlAttribute('placeholder', $this->translator->translate('Heslo'))
			->setRequired(FALSE)
			->addCondition($form::FILLED)
			->addRule($form::MIN_LENGTH, $this->translator->translate('Heslo_je_příliš_krátké_Musí_mít_alespoň_%d_znaků.'), 5)
			->addRule($form::PATTERN, $this->translator->translate('Heslo_je_příliš_jednoduché_Musí_obsahovat_číslici.'), '.*[0-9].*')
			->addRule($form::PATTERN, $this->translator->translate('Heslo_je_příliš_jednoduché_Musí_obsahovat_malé_písmeno.'), '.*[a-z].*');
		$form->addPassword('password22')
            ->setHtmlAttribute('data-validation-mode', 'live')
			->setHtmlAttribute('class', 'form-control')
			->setHtmlAttribute('autocomplete', 'off')
			->setHtmlAttribute('placeholder', $this->translator->translate('Heslo_znovu'))
			->addConditionOn($form['password2'], $form::FILLED)
			->addRule($form::EQUAL, $this->translator->translate('Hesla_se_neshodují.'), $form['password2'])
			->setRequired($this->translator->translate('Prosím_zadejte_znovu_své_heslo.'));

        $form->onValidate[] = array($this, 'FormValidate');
        $form->addSubmit('submit', $this->translator->translate('Uložit'))
			->setHtmlAttribute('class', 'form-control btn-sm btn-primary');
		
		$form->onSuccess[] = array($this, "SettingsUserFormSubmitted");
		return $form;
	}

    public function FormValidate(Form $form)
    {
        $data=$form->values;
        $tmpUsers = $this->PartnersBookWorkersManager->findAll()->where('worker_email = ?', $data['worker_email'])->fetch();
        if ($tmpUsers)
        {
            if ($tmpUsers->id != $data['id']){
                $form->addError($this->translator->translate('Email_') . $data['worker_email'] . $this->translator->translate('používá_jiný_uživatel_Zadejte_jiný_email.'));
            }
            if (empty($tmpUsers->password) && empty($data['password2'])){
                $form->addError($this->translator->translate('Uživatel_zatím_nemá_vytvořeno_heslo_Pro_uložení_formuláře_je_nutné_heslo_zadat.'));
            }

        }
        $this->redrawControl('userForm');

    }

    /**
	 * Settings user form submitted
	 * @param \Nette\Application\UI\Form
	 * @return void
	 */
	public function settingsUserFormSubmitted(Form $form)
	{
		$values = $form->values;
		try {
			$values['id'] = $this->getUser()->id;
			unset($values['password22']);
			if ($values['password2'] === ""){
                unset($values['password2']);
            }else{
                $values['b2b_password']  = Passwords::hash(UserManager::removeCapsLock($values['password2']));
                unset($values['password2']);
            }


			$this->PartnersBookWorkersManager->update($values);
			$this->activeTab = 2;
			$this->flashMessage($this->translator->translate('Změny_byly_uloženy.'), 'success');

		} catch (\Exception $e) {
            $this->flashMessage($this->translator->translate('Změny_nebyly_uloženy.'), 'danger');
            $form->addError($e->getMessage());
		}
        $this->redrawControl('content');
        $this->redirect('this', array('activeTab' => 2));
	}

	public function handleGetAres($ico)
	{
		$ares = new  \halasz\Ares\Ares();
		$result = $ares->loadData($ico); // return object \halasz\Ares\Data

		$arrResult = $result->toArray();
		
		if ($tmpCountries = $this->CountriesManager->findAllTotal()->where(array('name' => $this->translator->translate('Česko')))->fetch()) {
			
			$arrResult['cl_countries_id'] = $tmpCountries->id;
		}
		
		
		$this->sendJson($arrResult);
	}

    public function DataProcessListGridValidate($data)
    {
       // bdump($data);
        $result = NULL;
        //21.05.2020 send from cl_partners_book_workers
        if (isset($data['b2b_enabled'])) {
            if ($data['b2b_enabled']) {
                (empty($data['worker_email'])) ? $result = $this->translator->translate("Email_musí_být_vyplněn.") : $result = NULL;

                $tmpUsers = $this->PartnersBookWorkersManager->findAllTotal()->where('worker_email = ?', $data['worker_email'])->fetch();
                if ($tmpUsers)
                {
                    if ($tmpUsers->id != $data['id']){
                        $result = $this->translator->translate('Email_') . $data['worker_email'] . $this->translator->translate('používá_jiný_uživatel_Zadejte_jiný_email.');
                    }
                }
                if (is_null($result)) {
                    $tmpData = $this->PartnersBookWorkersManager->find($data['id']);
                    if ($tmpData) {
                        (empty($tmpData['b2b_password']) && empty($data['password2'])) ? $result = $this->translator->translate("Heslo_musí_být_zadáno.") : $result = NULL;
                    }
                }

            }
        }

        return $result;
    }

    public function DataProcessListGrid($data)
    {
        //21.05.2020 send from cl_partners_book_workers
        if (isset($data['password2'])) {
            if (!empty($data['password2'])) {
                $data['b2b_password'] = Passwords::hash(UserManager::removeCapsLock($data['password2']));
            }
            unset($data['password2']);
        }
        return $data;

    }

    //aditional processing data after save in listgrid
    public function afterDataSaveListGrid($dataId, $name = NULL)
    {

    }
    //aditional control before addline from listgrid
    public function beforeAddLine($data)
    {
        return $data;
    }

    //aditional control before delete from listgrid
    public function beforeDelete($lineId)
    {
        return TRUE;
    }



}
