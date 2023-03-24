<?php

namespace App\IntranetModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;
use App\Controls;
use Nette\Utils\DateTime;

class RentalPresenter extends \App\Presenters\BaseListPresenter {

    

    
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
     * @var \App\Model\RentalManager
     */
    public $DataManager;


    /**
     * @inject
     * @var \App\Model\RentalEstateManager
     */
    public $RentalEstateManager;

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
     * @var \App\Model\CenterManager
     */
    public $CenterManager;

    /**
     * @inject
     * @var \App\Model\EstateManager
     */
    public $EstateManager;

    /**
     * @inject
     * @var \App\Model\EstateDiaryManager
     */
    public $EstateDiaryManager;


    protected function startup()
    {
        parent::startup();
        $this->formName = "Půjčovna";
        $this->mainTableName = 'in_rental';
        $this->dataColumns = array( 'rnt_number' => array('Číslo zápůjčky', 'size' => 10),
                                    'cl_status.status_name' => array('Stav', 'size' => 20,'format' => 'colortag'),
                                    'dtm_rent' => array('Zapůjčeno', 'format' => 'datetime', 'size' => 10),
                                    'dtm_return' => array('Vráceno', 'format' => 'datetime', 'size' => 10),
                                    'counter__' => array('Počet', 'format' => 'integer', 'size' => 10, 'function' => 'getCounter', 'function_param' => array('id')),
                                    'in_staff.personal_number' => array('Osobní číslo', 'format' => 'text', 'size' => 20),
                                    'in_staff.surname' => array('Příjmení', 'format' => 'text', 'size' => 20),
                                    'in_staff.name' => array('Jméno', 'format' => 'text', 'size' => 20),
                                    'cl_commission.cm_number' => array('Zakázka', 'format' => 'text', 'size' => 10),
                                    'cl_center.name' => array('Středisko', 'format' => 'text'),
                                    'created' => array('Vytvořeno','format' => 'datetime'),'create_by' => 'Vytvořil','changed' => array('Změněno','format' => 'datetime'),'change_by' => 'Změnil');

        $this->filterColumns = array('rnt_number' => 'autocomplete' , 'dtm_rent', 'dtm_return', 'cl_commission.cm_number' => 'autocomplete', 'in_staff.personal_number' => 'autocomplete',
                                     'in_staff.surname' => 'autocomplete', 'in_staff.name' => 'autocomplete');
        $this->userFilterEnabled = TRUE;
        $this->userFilter = array('rnt_number', 'cl_commission.cm_number', 'in_staff.personal_number','in_staff.surname');

        $this->DefSort = 'rnt_number';
        $this->numberSeries = array('use' => 'rental', 'table_key' => 'cl_number_series_id', 'table_number' => 'rnt_number');
        //$this->readOnly = array('identification' => TRUE);
        //$settings = $this->CompaniesManager->getTable()->fetch();
        $now = new DateTime();
        $this->defValues = array('dtm_rent' => $now);
        $this->toolbar = array(1 => array('url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => 'Nový záznam', 'class' => 'btn btn-primary'),
                                2 => array('group' =>
                                        array(0 => array('url' => $this->link('report!', array('index' => 1)), 'rightsFor' => 'report', 'label' => 'Přehled zápůjček', 'title' => 'Přehled zápůjček',
                                                         'class' => 'ajax', 'data' => array('data-ajax="true"', 'data-history="false"'), 'icon' => 'iconfa-print'),
                                        ),
                                        'group_settings' => array('group_label' => 'Tisk', 'group_class' => 'btn btn-primary dropdown-toggle btn-sm', 'group_title' => 'tisk', 'group_icon' => 'iconfa-print')
                                        )
        );

        $this->report = array(1 => array('reportLatte' => __DIR__ . '/../templates/Rental/rptRentalSet.latte',
                                            'reportName' => 'Přehled zápůjček'),
                                );

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
        $form->addText('rnt_number', 'Číslo zápůjčky', 30, 50)
            ->setHtmlAttribute('readonly', TRUE)
			->setHtmlAttribute('placeholder','Číslo zápůjčky');

      /*  $arrStaff = array();
        $arrStaff['Aktivní'] = $this->StaffManager->findAll()->select('CONCAT(in_staff.surname," ",in_staff.name) AS fullname, in_staff.id AS id')->
                            order('cl_center.location,cl_center.name,in_staff.surname')->
                            where('in_staff.end = 0')->
                            fetchPairs('id','fullname');
        $arrStaff['Neaktivní'] =  $this->StaffManager->findAll()->select('CONCAT(in_staff.surname," ",in_staff.name) AS fullname, in_staff.id AS id')->
                            order('cl_center.location,cl_center.name,in_staff.surname')->
                            where('in_staff.end = 1')->
                            fetchPairs('id','fullname');
        $form->addSelect('in_staff_id','Zaměstnanec', $arrStaff)
                            ->setPrompt('Žádná')
                            ->setHtmlAttribute('placeholder','Zaměstnanec');
        */
        $arrStatus = $this->StatusManager->findAll()->where('status_use = ?', 'rental')->fetchPairs('id', 'status_name');
        $form->addSelect('cl_status_id', 'Stav', $arrStatus)
                        ->setHtmlAttribute('placeholder','Stav');


        $arrCommission = array();
        $arrCommission['Aktivní'] = $this->CommissionManager->findAll()->
                            select('cl_commission.id, CONCAT(cm_number," ", IFNULL(cl_center.name,""), " ", cm_title) AS name2')->
                            where('delivery_date IS NULL OR delivery_date >= NOW()')->order('cm_number ASC')->fetchPairs('id', 'name2');
        $arrCommission['Neaktivní'] = $this->CommissionManager->findAll()->
                            select('cl_commission.id, CONCAT(cm_number," ", IFNULL(cl_center.name,""), " ", cm_title) AS name2')->
                            where('delivery_date IS NULL OR delivery_date < NOW()')->order('cm_number ASC')->fetchPairs('id', 'name2');
        $form->addSelect('cl_commission_id','Zakázka', $arrCommission)
                            ->setPrompt('Žádná')
                            ->setRequired('Zakázka musí být vybrána')
                            ->setHtmlAttribute('placeholder','Zakázka');

        $arrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addSelect('cl_center_id', "Středisko", $arrCenter)
            ->setHtmlAttribute('data-placeholder','Zvolte středisko')
            ->setRequired('Středisko musí být vybráno')
            ->setPrompt('Zvolte středisko');

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
	    $data = $this->removeFormat($data);
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

    protected function createComponentRentalEstate()
    {
        $arrEstate = array();
        $arrEstate['Dostupné'] = $this->EstateManager->findAll()
                                    ->select('CONCAT(est_number, " ", est_name, " ", s_number) AS name, in_estate.id')
                                    ->where('cl_status.s_new = 1')
                                    ->order('est_name')->fetchPairs('id', 'name');
        $arrEstate['Vypůjčeno'] = $this->EstateManager->findAll()
                                    ->select('CONCAT(est_number, " ", est_name, " ", s_number) AS name, in_estate.id')
                                    ->where('cl_status.s_fin = 1')
                                    ->order('est_name')->fetchPairs('id', 'name');
        $arrEstate['Oprava'] = $this->EstateManager->findAll()
                                    ->select('CONCAT(est_number, " ", est_name, " ", s_number) AS name, in_estate.id')
                                    ->where('cl_status.s_repair = 1')
                                    ->order('est_name')->fetchPairs('id', 'name');
        $arrEstate['Vyřazeno'] = $this->EstateManager->findAll()
                                ->select('CONCAT(est_number, " ", est_name, " ", s_number) AS name, in_estate.id')
                                ->where('cl_status.s_storno = 1')
                                ->order('est_name')->fetchPairs('id', 'name');

        $arrStaff = array();
        $arrStaff['Aktivní'] = $this->StaffManager->findAll()->select('CONCAT(in_staff.surname," ",in_staff.name) AS fullname, in_staff.id AS id')->
                order('cl_center.location,cl_center.name,in_staff.surname')->
                where('in_staff.end = 0')->
                fetchPairs('id','fullname');
        $arrStaff['Neaktivní'] =  $this->StaffManager->findAll()->select('CONCAT(in_staff.surname," ",in_staff.name) AS fullname, in_staff.id AS id')->
                order('cl_center.location,cl_center.name,in_staff.surname')->
                where('in_staff.end = 1')->
                fetchPairs('id','fullname');
        //bdump($arrStaff);
         $arrData = array(
            'in_estate.est_number' => array('Ev. číslo', 'format' => 'chzn-select', 'size' => 7, 'values' => $arrEstate),
            'in_estate.est_name' => array('Název', 'format' => 'text', 'size' => 10, 'readonly' => true),
            'in_estate.s_number' => array('Sériové číslo', 'format' => 'text', 'size' => 8, 'readonly' => true),
            'in_staff.personal_number' => array('Osobní číslo', 'format' => 'chzn-select', 'size' => 7, 'values' => $arrStaff),
            'in_staff.surname' => array('Příjmení', 'format' => 'text', 'size' => 10, 'readonly' => true),
            'in_staff.name' => array('Jméno', 'format' => 'text', 'size' => 10, 'readonly' => true),
            'dtm_rent' => array('Vypůjčeno od', 'format' => 'datetime', 'size' => 8),
            'dtm_return' => array('Vypůjčeno do', 'format' => 'datetime', 'size' => 8),
            'returned' => array('Vráceno', 'format' => 'boolean', 'size' => 7),
             'description' => array('Poznámka', 'format' => 'textarea', 'size' => 100, 'rows' => 3, 'newline' => true),
             'arrTools' => array('tools', array(1 => array('url' => 'customFunction!', 'type' => 'returnItem', 'rightsFor' => 'write', 'label' => 'Vrátit', 'class' => 'btn btn-success btn-sm',
                                                         'data' => array('data-ajax="true"', 'data-history="false"'), 'icon' => 'glyphicon glyphicon-backward'
                                                          )
                                                )
                                 ),
        );
        $tmpNow = new DateTime();
        $control = new \App\Controls\ListgridControl(
            $this->translator,
            $this->RentalEstateManager, //data manager
            $arrData, //data columns
            array(), //row conditions
            $this->id, //parent Id
            array('dtm_rent' => $tmpNow), //default data
            $this->DataManager, //parent data manager
            NULL, //pricelist manager
            NULL, //pricelist partner manager
            TRUE, //enable add empty row
            array(), //custom links
            FALSE, //movableRow
            '', //orderColumn
            FALSE, //selectMode
            array(), //quickSearchm
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE, //pricelistbottom
            FALSE, //readonly
            FALSE, //nodelete
            TRUE, //enablesearch
            'in_estate.est_number LIKE ? OR in_estate.est_name LIKE ? OR in_estate.s_number ?', //txtSEarchcondition
            array(), //toolbar
            FALSE, //forceEnable
            TRUE //paginator off
        );
        //$control->setFilter('dt_detectors_id')
        $control->setContainerHeight('auto');

        $control->onChange[] = function () {
            $this->updateSum();
        };

        $control->onCustomFunction[] = function ($itemId, $type)
        {
            if ($type == 'returnItem') {
                $this->returnItem($itemId);
            }
        };

        return $control;
    }

    public function returnItem($itemId){
        $tmpNow = new DateTime();
        $this->RentalEstateManager->update(array('id' => $itemId, 'returned' => 1, 'dtm_return' => $tmpNow));
        $tmpItem = $this->RentalEstateManager->find($itemId);
        if ($tmpItem) {
            $this->EstateManager->updateStatus($tmpItem['in_estate_id']);
            $this->EstateDiaryManager->newRecord(array('id' => $tmpItem['in_estate_id'],
                                'event_type' => 0,
                                'description' => 'Vrácení <<< ' . $tmpItem->in_rental->in_staff['personal_number'] . ' ' . $tmpItem->in_rental->in_staff['surname'] . ' ' . $tmpItem->in_rental->in_staff['name']));
        }
        $this->updateSum();
    }

    public function DataProcessListGrid($data){
        if (is_null($data['dtm_return'])){
            $data['returned'] = 0;
        }

        $tmpData = $this->RentalEstateManager->find($data['id']);
        if ($tmpData && $tmpData['dtm_rent'] != $data['dtm_rent'] && is_null($data['dtm_return'])) {
            $this->EstateDiaryManager->newRecord(array('id' => $tmpData['in_estate_id'],
                                        'event_type' => 0,
                                        'description' => 'Zápůjčka >>> ' . $tmpData->in_rental->in_staff['personal_number'] . ' ' . $tmpData->in_rental->in_staff['surname'] . ' ' . $tmpData->in_rental->in_staff['name']));
        }
        if ($tmpData && $tmpData['dtm_return'] != $data['dtm_return']) {
            $this->EstateDiaryManager->newRecord(array('id' => $tmpData['in_estate_id'],
                                        'event_type' => 0,
                                        'description' => 'Vrácení <<< ' . $tmpData->in_rental->in_staff['personal_number'] . ' ' . $tmpData->in_rental->in_staff['surname'] . ' ' . $tmpData->in_rental->in_staff['name']));
        }

        return $data;
    }

    public function updateSum(){
        $this->DataManager->updateStatus($this->id);
        parent::UpdateSum();
        $this->redrawControl('content');
    }

    public function afterDataSaveListGrid($id, $name = NULL){
        if ($tmpData = $this->RentalEstateManager->find($id)){
            $this->EstateManager->updateStatus($tmpData['in_estate_id']);
        }
    }

    public function afterDelete($line){
        $this->EstateManager->updateStatus($line['in_estate_id']);
    }

    protected function createComponentReportRental($name)
    {
        $form = new Form($this, $name);
        $form->addHidden('id', NULL);

        $now = new \Nette\Utils\DateTime;
        $form->addText('date_from', 'Vypůjčeno od', 0, 16)
            ->setDefaultValue('01.' . $now->format('m.Y'))
            ->setHtmlAttribute('placeholder', 'Vypůjčeno od');

        $form->addText('date_to', 'Vypůjčeno do', 0, 16)
            ->setDefaultValue($now->format('d.m.Y'))
            ->setHtmlAttribute('placeholder', 'Vypůjčeno do');

        $tmpArrCenter = $this->CommissionManager->findAll()->order('cm_number')->fetchPairs('id', 'cm_number');
        $form->addMultiSelect('cl_commission_id', 'Zakázky', $tmpArrCenter)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder','Vyberte zakázky pro tisk');

        $tmpArrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addMultiSelect('cl_center_id', 'Střediska', $tmpArrCenter)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder','Vyberte střediska pro tisk');

        $tmpArrStaff = $this->StaffManager->findAll()->select('CONCAT(personal_number, " ", surname, " ", name) AS name, id AS id')->order('surname')->fetchPairs('id', 'name');
        $form->addMultiSelect('in_staff_id', 'Zaměstnanci', $tmpArrStaff)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder','Vyberte zaměstnance pro tisk');

        $form->addSubmit('save', 'Tisk')->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('save_xls', 'XLS')->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('back', 'Návrat')
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBackReportCommission');
        $form->onSuccess[] = array($this, 'SubmitReportCommissionSubmitted');
        //$form->getElementPrototype()->target = '_blank';
        return $form;
    }

    public function handleReport($index = 0)
    {
        $this->rptIndex = $index;
        $this->reportModalShow = TRUE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    public function stepBackReportCommission()
    {
        $this->rptIndex = 0;
        $this->reportModalShow = FALSE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    public function SubmitReportCommissionSubmitted(Form $form)
    {
        $data = $form->values;

        if ($form['save']->isSubmittedBy() || $form['save_xls']->isSubmittedBy()) {

            if ($data['date_to'] == "")
                $data['date_to'] = NULL;
            else {
                $data['date_to'] = date('Y-m-d H:i:s', strtotime($data['date_to']) + 86400 - 1);
            }

            if ($data['date_from'] == "")
                $data['date_from'] = NULL;
            else
                $data['date_from'] = date('Y-m-d H:i:s', strtotime($data['date_from']));

            $dataReport = $this->RentalEstateManager->findAll()->
                            where('(DATE(in_rental_estate.dtm_rent) >= DATE(?) OR in_rental_estate.returned = 0) AND DATE(in_rental_estate.dtm_rent) <= DATE(?) ', $data['date_from'], $data['date_to'])->
                            order('in_rental.cl_commission.cm_number, in_rental_estate.dtm_rent');

            if (count($data['cl_commission_id']) > 0) {
                $dataReport = $dataReport->where(array('in_rental.cl_commission_id' => $data['cl_commission_id']));
            }
            if (count($data['in_staff_id']) > 0) {
                $dataReport = $dataReport->where(array('in_rental.in_staff_id' => $data['in_staff_id']));
            }

            if (count($data['cl_center_id']) > 0) {
                $dataReport = $dataReport->where(array('in_rental.cl_center_id' => $data['cl_center_id']));
            }

            //bdump($data);
            $dataOther = array();//$this->CommissionTaskManager->find($itemId);
            $dataSettings = $data;
            $dataOther['dataSettingsCommission'] = $this->CommissionManager->findAll()->where(array('id' => $data['cl_commission_id']))->order('cm_number');
            $dataOther['dataSettingsCenter'] = $this->CenterManager->findAll()->where(array('id' => $data['cl_center_id']))->order('name');
            $dataOther['dataSettingsStaff'] = $this->StaffManager->findAll()->where(array('id' => $data['in_staff_id']))->order('surname, name');
            $dataOther['settings'] = $this->settings;
            $template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/Rental/rptRental.latte', $dataOther, $dataSettings, 'Přehled zápůjček');
            $tmpDate1 = new \DateTime($data['date_from']);
            $tmpDate2 = new \DateTime($data['date_to']);
            if ($form['save']->isSubmittedBy()) {
                $this->pdfCreate($template, 'Přehled zápůjček ' . date_format($tmpDate1, 'd.m.Y') . ' - ' . date_format($tmpDate2, 'd.m.Y'));
            }elseif ($form['save_xls']->isSubmittedBy()) {
                //bdump('ted');
                /*$dataReport = $this->DataManager->findAll()->
                select(':in_rental_estate.dtm_rent AS datum_vypujcky, :in_rental_estate.dtm_return AS datum_vraceni, :in_rental_estate.in_estate.est_number AS cislo_majetku, :in_rental_estate.in_estate.est_name AS nazev_majetku, :in_rental_estate.in_estate.s_number AS ser_cislo, cl_commission.cm_number AS zakazka, in_staff.personal_number AS os_cislo,
                                    in_staff.surname AS prijmeni, in_staff.name AS jmeno')->
                where('(:rental_estate.dtm_return >= ? OR :rental_estate.dtm_return IS NULL) AND :in_rental_estate.dtm_rent <= ? ', $data['date_from'], $data['date_to'])->
                order(':in_rental_estate.dtm_rent');*/

                $dataReport = $this->RentalEstateManager->findAll()->
                                select('in_rental_estate.dtm_rent AS datum_vypujcky, in_rental_estate.dtm_return AS datum_vraceni, in_estate.est_number AS cislo_majetku, in_estate.est_name AS nazev_majetku, 
                                                    in_estate.s_number AS ser_cislo, in_rental.cl_commission.cm_number AS zakazka, in_rental.cl_center.name AS stredisko, in_rental.in_staff.personal_number AS os_cislo,
                                                    in_rental.in_staff.surname AS prijmeni, in_rental.in_staff.name AS jmeno, DATEDIFF(IFNULL(?, in_rental_estate.dtm_return), IF(in_rental_estate.dtm_rent < ?, ?, in_rental_estate.dtm_rent)) AS pocet_dni,
                                                    in_estate.rent_price AS cena_za_den', $data['date_to'], $data['date_from'], $data['date_from'])->
                                where('(DATE(in_rental_estate.dtm_rent) >= DATE(?) OR in_rental_estate.returned = 0) AND DATE(in_rental_estate.dtm_rent) <= DATE(?) ', $data['date_from'], $data['date_to'])->
                                order('in_rental.cl_commission.cm_number, in_rental_estate.dtm_rent');

                if (count($data['cl_commission_id']) > 0) {
                    $dataReport = $dataReport->where(array('in_rental.cl_commission_id' => $data['cl_commission_id']));
                }
                if (count($data['in_staff_id']) > 0) {
                    $dataReport = $dataReport->where(array('in_rental.in_staff_id' => $data['in_staff_id']));
                }
                if ( $dataReport->count() > 0)
                {
                    $filename ="Zápůjčky";
                    //bdump($filename);
                    $this->sendResponse(new \XlsResponse\NXlsResponse($dataReport, $filename."-" .date('Ymd-Hi').".xls",true));
                }else{
                    $this->flashMessage($this->translator->translate('Data_nebyly_do_CSV_uloženy._Zadaným_podmínkám_nevyhověl_žádný_záznam.'), 'danger');
                    $this->redirect('default');
                }
            }
        }
    }

    public function getCounter($arr)
    {
        $result = $this->RentalEstateManager->findAll()->where('in_rental_id = ?', $arr['id'])->count('id');
        return $result;
    }

}
