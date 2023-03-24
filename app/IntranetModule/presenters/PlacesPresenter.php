<?php

namespace App\IntranetModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;
use App\Controls;
use Nette\Utils\DateTime;

class PlacesPresenter extends \App\Presenters\BaseListPresenter {

    

    
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
     * @var \App\Model\PlacesManager
     */
    public $DataManager;


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

    /**
     * @inject
     * @var \App\Model\NetworkManager
     */
    public $NetworkManager;


    /**
     * @inject
     * @var \App\Model\StaffManager
     */
    public $StaffManager;

    /**
     * @inject
     * @var \App\Model\EstateManager
     */
    public $EstateManager;

    /**
     * @inject
     * @var \App\Model\CountriesManager
     */
    public $CountriesManager;

    protected function createComponentFiles()
    {
        $user_id = $this->user->getId();
        $cl_company_id = $this->settings->id;
        return new Controls\FilesControl(
            $this->translator,$this->FilesManager,$this->UserManager,$this->id,'in_places_id', NULL,$cl_company_id,$user_id,
            $this->CompaniesManager, $this->ArraysManager);
    }




    protected function startup()
    {
        parent::startup();
        $this->mainTableName = 'in_places';
        $this->dataColumns = ['place_name' => ['Název místa', 'size' => 30],
                                    'place_num' => ['Číslo místa', 'size' => 15],
                                    'place_description' => ['Popis', 'size' => 30],
                                    'street' => ['Ulice', 'size' => 20],
                                    'building_number' => ['Číslo popisné', 'size' => 20],
                                    'city' => ['Město', 'size' => 20],
                                    'zip' => ['PSČ', 'size' => 10],
                                    'cl_countries.name' => ['Stát', 'size' => 20],
                                    'in_network.network_adr' => ['Adresa sítě', 'size' => 20],
                                    'place_url' => ['Link', 'size' => 30],
                                    'coordinates_x' => ['Souřadnice X', 'size' => 10],
                                    'coordinates_y' => ['Souřadnice Y', 'size' => 10],
                                    'created' => ['Vytvořeno','format' => 'datetime'],'create_by' => 'Vytvořil','changed' => ['Změněno','format' => 'datetime'],'change_by' => 'Změnil'];
        $this->relatedTable = 'in_places';
        $this->dataColumnsRelated = [
                                    'place_name' => ['Název místa', 'size' => 30],
                                    'place_num' => ['Číslo místa', 'size' => 15],
                                    'place_description' => ['Popis', 'size' => 30],
                                    'street' => ['Ulice', 'size' => 20],
                                    'building_number' => ['Číslo popisné', 'size' => 20],
                                    'city' => ['Město', 'size' => 20],
                                    'zip' => ['PSČ', 'size' => 10],
                                    'cl_countries.name' => ['Stát', 'size' => 20],
                                    'in_network.network_adr' => ['Adresa sítě', 'size' => 20],
                                    'place_url' => ['Link', 'size' => 30],
                                    'coordinates_x' => ['Souřadnice X', 'size' => 10],
                                    'coordinates_y' => ['Souřadnice Y', 'size' => 10],
                                    'created' => ['Vytvořeno','format' => 'datetime'],'create_by' => 'Vytvořil','changed' => ['Změněno','format' => 'datetime'],'change_by' => 'Změnil'];
        $this->mainFilter = 'in_places.in_places_id IS NULL';

        $this->filterColumns = ['place_name' => 'autocomplete' , 'place_num' => 'autocomplete' , 'street' => 'autocomplete' , 'place_description' => 'autocomplete',
                                        'city' => 'autocomplete' ,'zip' => 'autocomplete' ,'cl_countries.name' => 'autocomplete' ,
                                        'building_number' => 'autocomplete' ,'place_description', 'place_address' => 'autocomplete'];
        $this->userFilterEnabled = TRUE;
        $this->userFilter = ['place_name', 'place_num', 'city'];


        $this->DefSort = 'place_name';
        //$this->numberSeries = array('use' => 'pricelist', 'table_key' => 'cl_number_series_id', 'table_number' => 'identification');
        //$this->readOnly = array('identification' => TRUE);
        //$settings = $this->CompaniesManager->getTable()->fetch();
        $this->defValues = [];
        $this->toolbar = [1 => ['url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => 'Nový záznam', 'class' => 'btn btn-primary']];
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
        $form->addText('place_num', 'Číslo místa:', 15, 15)
            ->setHtmlAttribute('placeholder','číslo místa');
        $form->addText('place_name', 'Název:', 30, 50)
			->setHtmlAttribute('placeholder','Název');
        $form->addTextArea('place_description', 'Popis:', 40, 5)
            ->setHtmlAttribute('placeholder','doplňte popis');
        $form->addTextArea('place_address', 'Adresa:', 40, 5)
            ->setHtmlAttribute('placeholder','');
        $form->addText('street', 'Ulice:', 60, 60)
            ->setHtmlAttribute('placeholder','Ulice');
        $form->addText('building_number', 'Číslo popisné:', 10, 10)
            ->setHtmlAttribute('placeholder','Číslo popisné');
        $form->addText('city', 'Město:', 60, 60)
            ->setHtmlAttribute('placeholder','Město');
        $form->addText('zip', 'PSČ:', 60, 60)
            ->setHtmlAttribute('placeholder','PSČ');

        $arrCountries = $this->CountriesManager->findAllTotal()->order('name')->fetchPairs('id','name');
        $form->addSelect('cl_countries_id', 'Stát:', $arrCountries)
            ->setPrompt('Vyberte stát')
            ->setHtmlAttribute('placeholder','stát');

        $form->addText('place_url', 'Link:', 30, 200)
            ->setHtmlAttribute('placeholder','url');
        $form->addText('coordinates_x', 'Souřadnice X:', 30, 30)
            ->setHtmlAttribute('placeholder','GPS X');
        $form->addText('coordinates_y', 'Souřadnice Y:', 30, 30)
            ->setHtmlAttribute('placeholder','GPS Y');

        $arrPlaces = $this->DataManager->findAll()->where('in_places_id IS NULL AND place_name != ""')->
                                    select('place_name, id')->order('place_name')->fetchPairs('id','place_name');
        $form->addSelect('in_places_id','Nadřazené místo', $arrPlaces)
            ->setPrompt('Žádné')
            ->setHtmlAttribute('placeholder','Nadřazené místo');

        $arrNetwork = $this->NetworkManager->findAll()->select('CONCAT(domain_name, " ", network_adr) AS name, id')->order('name')->fetchPairs('id','name');
        $form->addSelect('in_network_id','Adresa sítě', $arrNetwork)
            ->setPrompt('Žádná')
            ->setHtmlAttribute('placeholder','Adresa sítě');

        $arrStaff['aktivní'] = $this->StaffManager->findAll()->
                        select('id, CONCAT(surname," ", name, " ", personal_number) AS name')->
                        where('end_date IS NULL')->order('surname ASC')->fetchPairs('id', 'name');
        $arrStaff['neaktivní'] = $this->StaffManager->findAll()->
                        select('id, CONCAT(surname," ", name, " ", personal_number) AS name')->
                        where('end_date IS NOT NULL')->order('surname ASC')->fetchPairs('id', 'name');

        $form->addSelect('in_staff_id','Vedoucí', $arrStaff)
            ->setPrompt('vyberte')
            ->setHtmlAttribute('placeholder','Vedoucí pracovník');
        $form->addSelect('in_staff_id2','Zástupce', $arrStaff)
            ->setPrompt('vyberte')
            ->setHtmlAttribute('placeholder','Zástupce vedoucího');


        $arrLang = $this->ArraysManager->getLanguages();
        $form->addSelect('lang','Jazyk místa', $arrLang)
            ->setPrompt('vyberte')
            ->setHtmlAttribute('placeholder','Jazyk');

        $form->addText('email', 'E-mail:', 30, 50)
            ->setHtmlAttribute('placeholder','emailová adresa');

        $form->addSubmit('send', 'Uložit')->setHtmlAttribute('class','btn btn-success');
	    $form->addSubmit('back', 'Zpět')
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
	    if (!empty($data->id))
		$this->DataManager->update($data, TRUE);
	    else
		$this->DataManager->insert($data);
	}
	$this->flashMessage('Změny byly uloženy.', 'success');
        $this->redrawControl('content');
    }

    protected function createComponentEstate()
    {
         $arrData = [
            'est_number' => ['Ev. číslo', 'format' => 'text', 'size' => 10, 'readonly' => true],
            'est_name' => ['Název', 'format' => 'text', 'size' => 10, 'readonly' => true],
            's_number' => ['Sériové číslo', 'format' => 'text', 'size' => 10, 'readonly' => true],
            'dtm_purchase' => ['Datum nákupu', 'format' => 'date', 'size' => 10, 'readonly' => true],
            'est_price' => ['Nákupní cena', 'format' => 'currency', 'size' => 10, 'readonly' => true],
            'invoice' => ['Doklad', 'format' => 'text', 'size' => 10, 'readonly' => true],
            'cl_center.name' => ['Středisko', 'format' => 'text', 'size' => 15, 'readonly' => true],
         ];
        $tmpNow = new DateTime();
        $control = new \App\Controls\ListgridControl(
            $this->translator,
            $this->EstateManager, //data manager
            $arrData, //data columns
            [], //row conditions
            $this->id, //parent Id
            [], //default data
            $this->DataManager, //parent data manager
            NULL, //pricelist manager
            NULL, //pricelist partner manager
            FALSE, //enable add empty row
            [], //custom links
            FALSE, //movableRow
            'est_name', //orderColumn
            FALSE, //selectMode
            [], //quickSearchm
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE, //pricelistbottom
            TRUE, //readonly
            FALSE, //nodelete
            TRUE, //enablesearch
            'est_number LIKE ? OR est_name LIKE ? OR s_number ? OR invoice LIKE ?', //txtSEarchcondition
            [], //toolbar
            FALSE, //forceEnable
            FALSE //paginator off
        );
        //$control->setFilter('dt_detectors_id')
        $control->setContainerHeight('auto');

        return $control;
    }


}
