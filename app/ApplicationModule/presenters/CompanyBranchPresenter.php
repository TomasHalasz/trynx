<?php

namespace App\ApplicationModule\Presenters;

use App\Controls;
use Nette\Application\UI\Form,
    Nette\Image;

class CompanyBranchPresenter extends \App\Presenters\BaseListPresenter {

   
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
    * @var \App\Model\CompanyBranchManager
    */
    public $DataManager;

    /**
     * @inject
     * @var \App\Model\CashDefManager
     */
    public $CashDefManager;

    /**
     * @inject
     * @var \App\Model\CompanyBranchUsersManager
     */
    public $CompanyBranchUsersManager;

    /**
     * @inject
     * @var \App\Model\CompaniesManager
     */
    public $CompaniesManager;
	
	/**
	 * @inject
	 * @var \App\Model\PriceListGroupManager
	 */
	public $PriceListGroupManager;

    /**
     * @inject
     * @var \App\Model\StorageManager
     */
    public $StorageManager;

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
     * @var \App\Model\UsersManager
     */
    public $UsersManager;
	
	/**
	 * @inject
	 * @var \App\Model\CenterManager
	 */
	public $CenterManager;
 
    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.CompanyBranch']);
        $this->dataColumns = array('name' => $this->translator->translate('Název_pobočky'),
                                   'b_name' => $this->translator->translate('Firma'),
                                   'b_street' => $this->translator->translate('Ulice'),
                                   'b_city' => $this->translator->translate('Město'),
                                   'b_zip' => $this->translator->translate('PSČ'),
                                   'b_email' => $this->translator->translate('email'),
                                   'b_dic' => $this->translator->translate('DIČ'),
                                   'discount' => $this->translator->translate('sleva_%'),
                                   'cl_storage.name' => $this->translator->translate('Sklad'),
                                   'cl_partners_book.company' => $this->translator->translate('Odběratel'),
                                   'cl_center.name' => $this->translator->translate('Středisko'),
								   'cl_pricelist_group.name' => $this->translator->translate('Položková_skupina'),
                                   'branch_key' => $this->translator->translate('Klíč_pro_externí_spojení'),
                                   'eet_pfx' => $this->translator->translate('PFX_soubor'),
                                   'eet_active' => array($this->translator->translate('EET_aktivní'), 'format' => 'boolean'),
                                   'eet_test' => array($this->translator->translate('Testovací_režim'), 'format' => 'boolean'),
                                   'eet_id_provoz' => $this->translator->translate('ID_provozovny'),
                                   'eet_id_poklad' => $this->translator->translate('ID_pokladny'),
                                   'cl_cash_def.name' => $this->translator->translate('Hotovostní_pokladna'),
                                   'created' => array($this->translator->translate('Vytvořeno'),'format' => 'datetime'),
                                   'create_by' => $this->translator->translate('Vytvořil'),
                                   'changed' => array($this->translator->translate('Změněno'),'format' => 'datetime'),
                                   'change_by' => $this->translator->translate('Změnil'));
        $this->FilterC = ' ';
        $this->filterColumns = array(	'name' => 'autocomplete' , 'b_street' => 'autocomplete', 'b_city' => 'autocomplete',
            'b_email' => 'autocomplete', 'cl_storage.name' => 'autocomplete',
            'cl_partners_book.company' => 'autocomplete');
        $this->DefSort = 'name';
        $this->defValues = array();
        $this->toolbar = array(1 => array('url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nový_záznam'), 'class' => 'btn btn-primary'));
    }	
    
    public function renderDefault($page_b = 1,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal, $cxs)  {
	parent::renderDefault($page_b,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal,$cxs);
    }
    
    public function renderEdit($id,$copy,$modal){
	    parent::renderEdit($id,$copy,$modal);
        $this->template->activeTab = 1;

    }


    protected function createComponentUsersListGrid()
    {
        $arrData = array(
            'cl_users.name'		=>  array($this->translator->translate('Uživatel'),'format' => 'text','size' => 25,
                                            'values' => $this->UsersManager->findAll()->order('name')->fetchPairs('id','name')),
            'cl_users.nick_name' => array($this->translator->translate('Přezdívka'), 'format' => 'text','size' => 25, 'readonly' =>TRUE ),
            'cl_users.email' => array($this->translator->translate('E-mail'), 'format' => 'text','size' => 25, 'readonly' =>TRUE ),
            'cl_users.phone' => array($this->translator->translate('Telefon'), 'format' => 'text','size' => 25, 'readonly' =>TRUE ),
            'default_branch' => array($this->translator->translate('Výchozí_pobočka'), 'format' => 'boolean', 'size' => 10)

        );
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->CompanyBranchUsersManager, //data manager
            $arrData, //data columns
            array(), //row conditions
            $this->id, //parent Id
            array(), //default data
            $this->DataManager, //parent data manager
            NULL, //pricelist manager
            NULL, //pricelist partner manager
            TRUE, //enable add empty row
            array() //custom links
        );

        $control->onChange[] = function ()
        {
            //$this->updateSum();

        };
        return $control;

    }



    protected function createComponentEdit($name)
    {	
            $form = new Form($this, $name);
	    //$form->setMethod('POST');
	    $form->addHidden('id',NULL);
		$form->addText('name', $this->translator->translate('Název_pobočky'), 32, 32)
			->setRequired($this->translator->translate('Zadejte_prosím_název_pobočky'))
			->setHtmlAttribute('placeholder',$this->translator->translate('název_pobočky'));
        $form->addText('b_name', $this->translator->translate('Firma'), 120, 120)
            ->setHtmlAttribute('placeholder',$this->translator->translate('název_firmy'));
        $form->addText('b_street', $this->translator->translate('Ulice'), 120, 120)
            ->setHtmlAttribute('placeholder',$this->translator->translate('ulice'));
        $form->addText('b_city', $this->translator->translate('Město'), 60, 60)
            ->setHtmlAttribute('placeholder',$this->translator->translate('město'));
        $form->addText('b_dic', $this->translator->translate('DIČ'), 20, 20)
            ->setHtmlAttribute('class','form-control input-sm')
            ->setHtmlAttribute('placeholder',$this->translator->translate('DIČ'));
        $form->addText('b_ico', $this->translator->translate('IČO'), 20, 20)
            ->setHtmlAttribute('class','form-control input-sm')
            ->setHtmlAttribute('placeholder',$this->translator->translate('IČO'));
        $form->addText('b_zip', $this->translator->translate('PSČ'), 10, 10)
            ->setHtmlAttribute('placeholder',$this->translator->translate('PSČ'));
        $form->addText('b_email', $this->translator->translate('E-mail'), 30, 60)
            ->setHtmlAttribute('placeholder',$this->translator->translate('e-mail'));
        $form->addText('b_phone', $this->translator->translate('Telefon'), 30, 60)
            ->setHtmlAttribute('placeholder',$this->translator->translate('telefon'));
        $form->addText('b_www', $this->translator->translate('WWW'), 30, 60)
            ->setHtmlAttribute('placeholder',$this->translator->translate('web'));
        $form->addText('discount', $this->translator->translate('Sleva_%'), 10, 10)
            ->setHtmlAttribute('placeholder',$this->translator->translate('sleva_v_%'));
        $form->addText('branch_key', $this->translator->translate('Klíč_pro_externí_spojení'), 30, 64)
            ->setHtmlAttribute('readonly', 'readonly')
            ->setHtmlAttribute('placeholder',$this->translator->translate('Klíč_pro_externí_spojení'));

        $form->addSelect('cl_storage_id', $this->translator->translate('Výchozí_sklad'), $this->StorageManager->getStoreTreeNotNested());
        $form->addSelect('cl_partners_book_id', $this->translator->translate('Výchozí_odběratel'), $this->PartnersManager->findAll()->order('company')->fetchPairs('id', 'company'))
            ->setHtmlAttribute('data-urlajax', $this->link('getPartners!'));

        $form->addSelect('cl_cash_def_id', $this->translator->translate('Hotovostní_pokladna'), $this->CashDefManager->findAll()->order('name')->fetchPairs('id', 'name'))
                ->setPrompt($this->translator->translate('Zvolte_pokladnu'));

        //->setAttribute('data-url-update-partner-in-form', $this->link('updatePartnerInForm!'))
        $arrNumberSeries                = $this->NumberSeriesManager->findAll()->where('form_use = ?','sale')->order('form_name')->fetchPairs('id','form_name');
        $arrNumberSeriesCashIn          = $this->NumberSeriesManager->findAll()->where('form_use = ? OR form_use = ?','cash', 'cash_in')->order('form_name')->fetchPairs('id','form_name');
        $arrNumberSeriesCashOut         = $this->NumberSeriesManager->findAll()->where('form_use = ? OR form_use = ?','cash', 'cash_out')->order('form_name')->fetchPairs('id','form_name');
        $arrNumberSeriesInvoice         = $this->NumberSeriesManager->findAll()->where('form_use = ?','invoice')->order('form_name')->fetchPairs('id','form_name');
        $arrNumberSeriesInvoiceArrived  = $this->NumberSeriesManager->findAll()->where('form_use = ?','invoice_arrived')->order('form_name')->fetchPairs('id','form_name');
        $arrNumberSeriesAdvance         = $this->NumberSeriesManager->findAll()->where('form_use = ?','advance')->order('form_name')->fetchPairs('id','form_name');
        $arrNumberSeriesOrder           = $this->NumberSeriesManager->findAll()->where('form_use = ?','order')->order('form_name')->fetchPairs('id','form_name');
        $arrNumberSeriesInternal        = $this->NumberSeriesManager->findAll()->where('form_use = ?','invoice_internal')->order('form_name')->fetchPairs('id','form_name');
        $form->addSelect('cl_number_series_id', $this->translator->translate('Číslování_prodejek'), $arrNumberSeries);
        $form->addSelect('cl_number_series_id_correction', $this->translator->translate('Číslování_opravných_dokladů'), $arrNumberSeries);
        $form->addSelect('cl_number_series_id_cashin', $this->translator->translate('Číslování_příjmu_do_pokladny'), $arrNumberSeriesCashIn);
        $form->addSelect('cl_number_series_id_cashout', $this->translator->translate('Číslování_výdeje_z_pokladny'), $arrNumberSeriesCashOut);
        $form->addSelect('cl_number_series_id_invoice', $this->translator->translate('Číslování_vydaných_faktur'), $arrNumberSeriesInvoice);
        $form->addSelect('cl_number_series_id_invoicearrived', $this->translator->translate('Číslování_přijatých_faktur'), $arrNumberSeriesInvoiceArrived);
        $form->addSelect('cl_number_series_id_advance', $this->translator->translate('Číslování_zálohových_faktur'), $arrNumberSeriesAdvance);
        $form->addSelect('cl_number_series_id_invoice_internal', $this->translator->translate('Číslování_interních_dokladů'), $arrNumberSeriesInternal);
        $form->addSelect('cl_number_series_id_order', $this->translator->translate('Číslování_objednávek'), $arrNumberSeriesOrder);
	
		$arrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id','name');
		$form->addSelect('cl_center_id', $this->translator->translate("Středisko"),$arrCenter)
			->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_středisko'))
			->setPrompt($this->translator->translate('Zvolte_středisko'));
	
		$arrGroup = $this->PriceListGroupManager->findAll()->order('name')->fetchPairs('id','name');
		$form->addSelect('cl_pricelist_group_id', $this->translator->translate("Položková_skupina"),$arrGroup)
			->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_skupinu'))
			->setPrompt($this->translator->translate('Zvolte_skupinu'));
	
		$form->addUpload('upload_pfx', $this->translator->translate('PFX_soubor_s_certifikátem'))
            ->setHtmlAttribute('class','form-control input-sm')
            ->addRule(Form::MAX_FILE_SIZE, $this->translator->translate('Maximální_velikost_souboru_je_64_kB'), 64 * 1024 /* v bytech */);
        $form->addPassword('eet_pass',$this->translator->translate('Heslo'))
            ->setHtmlAttribute('class','form-control input-sm')
            ->setHtmlAttribute('autocomplete', 'off')
            ->setHtmlAttribute('placeholder',$this->translator->translate('Heslo'))
            ->setRequired(FALSE);
        $form->addCheckbox('eet_active', $this->translator->translate('EET_aktivováno'));
        $form->addCheckbox('eet_test', $this->translator->translate('Testovací_režim'));
        $form->addText('eet_pfx', $this->translator->translate('Nahraný_soubor'))
            ->setHtmlAttribute('class','form-control input-sm')
            ->setHtmlAttribute('readonly');
        $form->addText('eet_id_provoz', $this->translator->translate('ID_provozovny'))
            ->setHtmlAttribute('class','form-control input-sm');
        $form->addText('eet_id_poklad', $this->translator->translate('ID_pokladny'))
            ->setHtmlAttribute('class','form-control input-sm');



		$form->addSubmit('send', $this->translator->translate('Uložit'))->setAttribute('class','btn btn-success');
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
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

    public function SubmitEditSubmitted(Form $form)
    {
        $data=$form->values;
        //dump($data);
        //	die;
        if ($form['send']->isSubmittedBy())
        {

            $company_id = $this->UserManager->getCompany($this->getUser()->id)->id;
            //$data['id'] = $company_id;

            unset($data['eet_pfx']);
            if (empty($data['eet_pass']))
                unset($data['eet_pass']);

            //upload pfx file to company folder
            $file = $form->getHttpData($form::DATA_FILE, 'upload_pfx');
            if ($file && $file->isOk()) {
                //$fileName = \Nette\Utils\Random::generate(64, 'A-Za-z0-9');
                $fileName = $file->getSanitizedName();
                $dataFolder = $this->CompaniesManager->getDataFolder($company_id);
                $destFile =  $dataFolder . '/pfx/' . $fileName;
                $file->move($destFile);
                $data['eet_pfx'] = $fileName;
            }
            unset($data['submit']);
            unset($data['upload_pfx']);

            //bdump($values);


            if (!empty($data->id))
                $this->DataManager->update($data, TRUE);
            else
                $this->DataManager->insert($data);
        }
        $this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
        $this->redirect('default');
    }	    

    public function handleBranchKeyGen($id)
    {
        $tmpData = $this->DataManager->find($id);
        if ($tmpData){
            $newToken = '';
            while ($this->DataManager->findAllTotal()->where(array('branch_key' => $newToken))->fetch() || $newToken == '')
            {
                $newToken = \Nette\Utils\Random::generate(64,'A-Za-z0-9');
            }
            $arrData['id'] = $tmpData->id;
            $arrData['branch_key'] = $newToken;
            $tmpData->update($arrData);
            $this->redrawControl('branchKey');
        } else {
            $arrData['branch_key'] = "";
        }


    }

}
