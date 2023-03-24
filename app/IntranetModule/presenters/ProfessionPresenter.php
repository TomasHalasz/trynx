<?php

namespace App\IntranetModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;
use App\Controls;

class ProfessionPresenter extends \App\Presenters\BaseListPresenter {

    

    
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
    * @var \App\Model\ProfessionManager
    */
    public $DataManager;

    /**
     * @inject
     * @var \App\Model\ArraysIntranetManager
     */
    public $ArraysIntranetManager;


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
     * @var \App\Model\ArraysManager
     */
    public $ArraysManager;

    /**
     * @inject
     * @var \App\Model\CompaniesManager
     */
    public $CompaniesManager;


    protected function createComponentFiles()
    {
        $user_id = $this->user->getId();
        $cl_company_id = $this->settings->id;
        return new Controls\FilesControl(
            $this->translator,$this->FilesManager,$this->UserManager,$this->id,'in_profession_id', NULL,$cl_company_id,$user_id,
            $this->CompaniesManager, $this->ArraysManager);
    }


    protected function startup()
    {
        parent::startup();
        $this->mainTableName = 'in_profession';
        $this->dataColumns = array( 'name' => array('Název profese', 'size' => 60, 'format' => 'text'),
                                    'active' => array('Aktivní', 'format' => 'boolean'),
                                    'noise' => array('Hluk', 'format' => 'boolean'),
                                    'noise_cat' => array('Hluk kat.', 'format' => 'integer'),
                                    'dust' => array('Prach', 'format' => 'boolean'),
                                    'dust_cat' => array('Prach kat.', 'format' => 'integer'),
                                    'chemicals' => array('Chem.', 'format' => 'boolean'),
                                    'chemicals_cat' => array('Chem. kat.', 'format' => 'integer'),
                                    'vibrations' => array('Vibrace', 'format' => 'boolean'),
                                    'vibrations_cat' => array('Vibrace kat.', 'format' => 'integer'),
                                    'fields' => array('Zář.Pole', 'format' => 'boolean'),
                                    'fields_cat' => array('Zář.kat.', 'format' => 'integer'),
                                    'physics' => array('Fyz.', 'format' => 'boolean'),
                                    'physics_cat' => array('Fyz. kat.', 'format' => 'integer'),
                                    'position' => array('Poloha', 'format' => 'boolean'),
                                    'position_cat' => array('Poloha kat.', 'format' => 'integer'),
                                    'heat' => array('Teplo', 'format' => 'boolean'),
                                    'heat_cat' => array('Teplo kat.', 'format' => 'integer'),
                                    'cold' => array('Chlad', 'format' => 'boolean'),
                                    'cold_cat' => array('Chlad kat.', 'format' => 'integer'),
                                    'psycho' => array('Psych.', 'format' => 'boolean'),
                                    'psycho_cat' => array('Psych. kat.', 'format' => 'integer'),
                                    'sight' => array('Zrak', 'format' => 'boolean'),
                                    'sight_cat' => array('Zrak kat.', 'format' => 'integer'),
                                    'bio' => array('Bio', 'format' => 'boolean'),
                                    'bio_cat' => array('Bio kat.', 'format' => 'integer'),
                                    'pressure' => array('Tlak', 'format' => 'boolean'),
                                    'pressure_cat' => array('Tlak kat.', 'format' => 'integer'),
                                    'created' => array('Vytvořeno','format' => 'datetime'),'create_by' => 'Vytvořil','changed' => array('Změněno','format' => 'datetime'),'change_by' => 'Změnil');

       // 'cl_number_series.form_name' => array('Číselná řada', 'size' => 30),
         //               'default_type' => array('format' => 'boolean','Výchozí',TRUE),
           //             'inv_type' => array('Typ dokladu', 'size' => 20, 'arrValues' => $this->getInvoiceTypes()),
             //           'form_use' => array('Použití',FALSE,'function' => 'getStatusName'),

//        $this->FilterC = 'UPPER(currency_name) LIKE ?';
        $this->filterColumns = array(	'name' => 'autocomplete');
        $this->userFilterEnabled = TRUE;
        $this->userFilter = array('name');

       // $this->filterColumns = array();
        $this->DefSort = 'name';
        //$this->numberSeries = array('use' => 'pricelist', 'table_key' => 'cl_number_series_id', 'table_number' => 'identification');
        //$this->readOnly = array('identification' => TRUE);
        //$settings = $this->CompaniesManager->getTable()->fetch();
        $this->defValues = array('active' => 1);
        $this->toolbar = array(1 => array('url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => 'Nový záznam', 'class' => 'btn btn-primary'));
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

        $arrCategory = $this->ArraysIntranetManager->getProfessionCategories();
	    $form->addSelect('category', "Kategorie práce:",$arrCategory)
		    ->setHtmlAttribute('data-placeholder','Kategorie práce')
		    ->setHtmlAttribute('class','form-control chzn-select input-sm')
            ->setRequired('Zvolte kategorii')
		    ->setPrompt('Zvolte kategorii práce');

        $form->addText('name', 'Název profese:', 0, 50)
			->setHtmlAttribute('placeholder','Název profese');
	    $form->addCheckbox('active', 'Aktivní')
		    ->setHtmlAttribute('class', 'items-show');

        $arrCat = $this->ArraysIntranetManager->getRisksCategories();
        $form->addCheckbox('noise', 'Hluk')
            ->setHtmlAttribute('class', 'items-show');
        $form->addSelect('noise_cat', "Kategorie:",$arrCat)
            ->setPrompt('zvolte kategorii')
            ->setHtmlAttribute('data-placeholder','Kategorie')
            ->setHtmlAttribute('class','form-control chzn-select input-sm')
            ->addConditionOn($form['noise'], Form::EQUAL, true)
            ->setRequired('Zvolte kategorii');


        $form->addCheckbox('dust', 'Prach')
            ->setHtmlAttribute('class', 'items-show');
        $form->addSelect('dust_cat', "Kategorie:",$arrCat)
            ->setPrompt('zvolte kategorii')
            ->setHtmlAttribute('data-placeholder','Kategorie')
            ->setHtmlAttribute('class','form-control chzn-select input-sm')
            ->addConditionOn($form['dust'], Form::EQUAL, true)
            ->setRequired('Zvolte kategorii');

        $form->addCheckbox('chemicals', 'Chemické látky')
            ->setHtmlAttribute('class', 'items-show');
        $form->addSelect('chemicals_cat', "Kategorie:",$arrCat)
            ->setPrompt('zvolte kategorii')
            ->setHtmlAttribute('data-placeholder','Kategorie')
            ->setHtmlAttribute('class','form-control chzn-select input-sm')
            ->addConditionOn($form['chemicals'], Form::EQUAL, true)
            ->setRequired('Zvolte kategorii');

        $form->addCheckbox('vibrations', 'Vibrace')
            ->setHtmlAttribute('class', 'items-show');
        $form->addSelect('vibrations_cat', "Kategorie:",$arrCat)
            ->setPrompt('zvolte kategorii')
            ->setHtmlAttribute('data-placeholder','Kategorie')
            ->setHtmlAttribute('class','form-control chzn-select input-sm')
            ->addConditionOn($form['vibrations'], Form::EQUAL, true)
            ->setRequired('Zvolte kategorii');

        $form->addCheckbox('fields', 'Neionizující záření a elektromagnetické pole')
            ->setHtmlAttribute('class', 'items-show');
        $form->addSelect('fields_cat', "Kategorie:",$arrCat)
            ->setPrompt('zvolte kategorii')
            ->setHtmlAttribute('data-placeholder','Kategorie')
            ->setHtmlAttribute('class','form-control chzn-select input-sm')
            ->addConditionOn($form['fields'], Form::EQUAL, true)
            ->setRequired('Zvolte kategorii');

        $form->addCheckbox('physics', 'Fyzická zátěž')
            ->setHtmlAttribute('class', 'items-show');
        $form->addSelect('physics_cat', "Kategorie:",$arrCat)
            ->setPrompt('zvolte kategorii')
            ->setHtmlAttribute('data-placeholder','Kategorie')
            ->setHtmlAttribute('class','form-control chzn-select input-sm')
            ->addConditionOn($form['physics'], Form::EQUAL, true)
            ->setRequired('Zvolte kategorii');

        $form->addCheckbox('position', 'Pracovní poloha')
            ->setHtmlAttribute('class', 'items-show');
        $form->addSelect('position_cat', "Kategorie:",$arrCat)
            ->setPrompt('zvolte kategorii')
            ->setHtmlAttribute('data-placeholder','Kategorie')
            ->setHtmlAttribute('class','form-control chzn-select input-sm')
            ->addConditionOn($form['position'], Form::EQUAL, true)
            ->setRequired('Zvolte kategorii');

        $form->addCheckbox('heat', 'Zátěž teplem')
            ->setHtmlAttribute('class', 'items-show');
        $form->addSelect('heat_cat', "Kategorie:",$arrCat)
            ->setPrompt('zvolte kategorii')
            ->setHtmlAttribute('data-placeholder','Kategorie')
            ->setHtmlAttribute('class','form-control chzn-select input-sm')
            ->addConditionOn($form['heat'], Form::EQUAL, true)
            ->setRequired('Zvolte kategorii');

        $form->addCheckbox('cold', 'Zátěž chladem')
            ->setHtmlAttribute('class', 'items-show');
        $form->addSelect('cold_cat', "Kategorie:",$arrCat)
            ->setPrompt('zvolte kategorii')
            ->setHtmlAttribute('data-placeholder','Kategorie')
            ->setHtmlAttribute('class','form-control chzn-select input-sm')
            ->addConditionOn($form['cold'], Form::EQUAL, true)
            ->setRequired('Zvolte kategorii');

        $form->addCheckbox('psycho', 'Psychická zátěž')
            ->setHtmlAttribute('class', 'items-show');
        $form->addSelect('psycho_cat', "Kategorie:",$arrCat)
            ->setPrompt('zvolte kategorii')
            ->setHtmlAttribute('data-placeholder','Kategorie')
            ->setHtmlAttribute('class','form-control chzn-select input-sm')
            ->addConditionOn($form['psycho'], Form::EQUAL, true)
            ->setRequired('Zvolte kategorii');

        $form->addCheckbox('sight', 'Zraková zátěž')
            ->setHtmlAttribute('class', 'items-show');
        $form->addSelect('sight_cat', "Kategorie:",$arrCat)
            ->setPrompt('zvolte kategorii')
            ->setHtmlAttribute('data-placeholder','Kategorie')
            ->setHtmlAttribute('class','form-control chzn-select input-sm')
            ->addConditionOn($form['sight'], Form::EQUAL, true)
            ->setRequired('Zvolte kategorii');

        $form->addCheckbox('bio', 'Biologické činitele')
            ->setHtmlAttribute('class', 'items-show');
        $form->addSelect('bio_cat', "Kategorie:",$arrCat)
            ->setPrompt('zvolte kategorii')
            ->setHtmlAttribute('data-placeholder','Kategorie')
            ->setHtmlAttribute('class','form-control chzn-select input-sm')
            ->addConditionOn($form['bio'], Form::EQUAL, true)
            ->setRequired('Zvolte kategorii');

        $form->addCheckbox('pressure', 'Práce ve zvýšeném tlaku vzduchu')
            ->setHtmlAttribute('class', 'items-show');
        $form->addSelect('pressure_cat', "Kategorie:",$arrCat)
            ->setPrompt('zvolte kategorii')
            ->setHtmlAttribute('data-placeholder','Kategorie')
            ->setHtmlAttribute('class','form-control chzn-select input-sm')
            ->addConditionOn($form['pressure'], Form::EQUAL, true)
            ->setRequired('Zvolte kategorii');



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

    public function SubmitEditSubmitted(Form $form)
    {
	    $data=$form->values;
        if ($form['send']->isSubmittedBy())
        {
            if (!empty($data->id))
            $this->DataManager->update($data, TRUE);
            else
            $this->DataManager->insert($data);
        }
        $this->flashMessage('Změny byly uloženy.', 'success');
        $this->redrawControl('content');
    }	    



}
