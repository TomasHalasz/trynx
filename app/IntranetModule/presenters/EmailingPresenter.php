<?php

namespace App\IntranetModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;
use App\Controls;
use Nette\Utils\DateTime;

class EmailingPresenter extends \App\Presenters\BaseListPresenter {

    

    
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
     * @var \App\Model\EmailingManager
     */
    public $emailingManager;
    
    /**
    * @inject
    * @var \App\Model\InEmailingManager
    */
    public $DataManager;

    /**
     * @inject
     * @var \App\Model\InEmailingLangManager
     */
    public $inEmailingLangManager;

    /**
     * @inject
     * @var \App\Model\InEmailingAddrManager
     */
    public $inEmailingAddrManager;

    /**
     * @inject
     * @var \App\Model\ArraysIntranetManager
     */
    public $ArraysIntranetManager;

    /**
     * @inject
     * @var \App\Model\CountriesManager
     */
    public $CountriesManager;

    /**
     * @inject
     * @var \App\Model\CenterManager
     */
    public $CenterManager;

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
     * @var \App\Model\StaffRoleManager
     */
    public $StaffRoleManager;

    /**
     * @inject
     * @var \App\Model\StaffManager
     */
    public $staffManager;

    /**
     * @inject
     * @var \App\Model\InstructionsStaffRoleManager
     */
    public $InstructionsStaffRoleManager;

    /**
     * @inject
     * @var \App\Model\InstructionsPlacesManager
     */
    public $InstructionsPlacesManager;


    /**
     * @inject
     * @var \App\Model\PlacesManager
     */
    public $PlacesManager;

    /**
     * @inject
     * @var \App\Model\PartnersManager
     */
    public $PartnersManager;

    /**
     * @inject
     * @var \App\Model\PartnersGroupsManager
     */
    public $partnersGroupsManager;


    protected function startup()
    {
        parent::startup();
        $this->formName = "Hromadná pošta";
        $this->mainTableName = "in_emailing";
        $this->dataColumns = array( 'em_number' => array('Číslo odeslání', 'size' => 20),
                                    'subject' => array('Předmět', 'format' => 'text', 'size' => 12),
                                    'dtm_from' => array('Platnost od', 'format' => 'datetime', 'size' => 10),
                                    'status__' => array('A / O', 'format' => 'center', 'size' => 4,  'function' => 'getStatEml', 'function_param' => array('id')),
                                    'created' => array('Vytvořeno','format' => 'datetime'),'create_by' => 'Vytvořil','changed' => array('Změněno','format' => 'datetime'),'change_by' => 'Změnil');

        $this->filterColumns = array('em_number' => 'autocomplete' , 'subject' => 'autocomplete');
        $this->userFilterEnabled = TRUE;
        $this->userFilter = array('em_number', 'subject');
        //$this->filterColumns = array();
        $this->DefSort = 'em_number DESC';
        //$this->numberSeries = array('use' => 'pricelist', 'table_key' => 'cl_number_series_id', 'table_number' => 'identification');
        //$this->readOnly = array('identification' => TRUE);
        //$settings = $this->CompaniesManager->getTable()->fetch();
        $this->defValues = array();
        $this->numberSeries = array('use' => 'emailing', 'table_key' => 'cl_number_series_id', 'table_number' => 'em_number');
        $this->toolbar = array(1 => array('url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => 'Nový záznam', 'class' => 'btn btn-primary')
                             );

    }	
    
    public function renderDefault($page_b = 1,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal, $cxs)  {
	        parent::renderDefault($page_b,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal,$cxs);

    }
    
    public function renderEdit($id,$copy,$modal){
            $this->template->setFile($this->getCMZTemplate( __DIR__ . '/../templates/Emailing/edit.latte'));
	        parent::renderEdit($id,$copy,$modal);
	        $this->template->staffRoles = $this->StaffRoleManager->findAll()->order('name');
	        $this->template->partnersGroups = $this->partnersGroupsManager->findAll()->order('name');

    }

    public function afterCopy($newLine, $oldLine)
    {
        $tmpLang = $this->inEmailingLangManager->findAll()->where('in_emailing_id = ?', $oldLine);
        foreach($tmpLang as $key => $one){
            $arrLang = $one->toArray();
            $arrLang['in_emailing_id'] = $newLine;
            unset($arrLang['id']);
            $this->inEmailingLangManager->insert($arrLang);
        }

        $tmpAddr = $this->inEmailingAddrManager->findAll()->where('in_emailing_id = ?', $oldLine);
        foreach($tmpAddr as $key => $one){
            $arrAddr = $one->toArray();
            $arrAddr['in_emailing_id'] = $newLine;
            unset($arrAddr['send']);
            unset($arrAddr['id']);
            $this->inEmailingAddrManager->insert($arrAddr);
        }

        $tmpFiles = $this->FilesManager->findAll()->where('in_emailing_id = ?', $oldLine);
        foreach($tmpFiles as $key => $one){
            $arrFiles = $one->toArray();
            $arrFiles['in_emailing_id'] = $newLine;
            unset($arrFiles['id']);
            $this->FilesManager->insert($arrFiles);
        }

        return true;
    }

    protected function createComponentEdit($name)
    {	
        $form = new Form($this, $name);
	    $form->addHidden('id',NULL);
        $form->addText('em_number', 'Číslo odeslání', 20, 20)
			    ->setHtmlAttribute('placeholder','Číslo odeslání')
                ->setHtmlAttribute('readonly', true);

        $form->addText('dtm_from', 'Platnost od' )
                ->setHtmlAttribute('placeholder','Platnost od');

        $form->addText('subject', 'Předmět', 100, 255)
            ->setHtmlAttribute('placeholder','Předmět');

        $form->addTextArea('message', 'Zpráva', 30, 8)
            ->setHtmlAttribute('placeholder','Zpráva');

        $form->addSubmit('save', 'Uložit')
                ->setHtmlAttribute('title', 'Uloží nastavení bez odeslání')
                ->setHtmlAttribute('class','btn btn-success');
        $form->addSubmit('send', 'Odeslat emaily')
                ->setHtmlAttribute('title', 'Uloží nastavení a odešle emaily podle nastavených parametrů')
                ->setHtmlAttribute('class','btn btn-success');
	    $form->addSubmit('back', 'Zpět')
            ->setHtmlAttribute('title', 'Vrátí se zpět bez uložení')
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
        if ($form['send']->isSubmittedBy() || $form['save']->isSubmittedBy())
        {
            $data = $this->removeFormat($data);

            if (!empty($data->id))
                $this->DataManager->update($data, TRUE);
            else
                $this->DataManager->insert($data);

            if (!$form['send']->isSubmittedBy()) {
                $this->flashMessage('Změny byly uloženy.', 'success');
            }

            if ($form['send']->isSubmittedBy()){

                $ret = $this->checkEmails();
                if (!$ret){
                    return;
                }

                $tmpData = $this->DataManager->find($this->id);
                $arrLang = array();
                foreach($tmpData->related('in_emailing_lang') as $key2 => $one2)
                {
                    $arrLang[$one2['lang']] = ['subject' => $one2['subject'],
                                                'message' => $one2['message']];
                }

               // bdump($arrLang);
                if ($tmpData){
                    session_write_close();
                   $counterEmails = 0;
                   $totalEmails = $tmpData->related('in_emailing_addr')->count('id');
                   foreach ($tmpData->related('in_emailing_addr')->where('send = 0') as $key => $one){
                       $this->UserManager->setProgressBar($counterEmails++, $totalEmails, $this->user->getId(), 'Odesílání emailů' . ' <br>' . $counterEmails . ' / ' . $totalEmails);
                       $subject = $data['subject'];
                       $body    = $data['message'];
                       if (isset($arrLang[$one['lang']])){
                           $subject = $arrLang[$one['lang']]['subject'];
                           $body    = $arrLang[$one['lang']]['message'];
                       }
                       if (empty($one['name'])){
                           $name = $one['email'];
                       }else{
                           $name = $one['name'];
                       }
                       $emailTo =[ $name . ' <' . $one['email'] . '>'];
                       $emailFrom = $this->settings->email;

                       $tmpAttachment = $this->FilesManager->findAll()->where('in_emailing_id = ?', $this->id);
                       $dataFolder = $this->CompaniesManager->getDataFolder($tmpData['cl_company_id']);
                       $attachment = array();
                       foreach($tmpAttachment as $keyF => $oneF){
                           $subFolder = $this->ArraysManager->getSubFolder($oneF);
                           $fileSend =  $dataFolder . '/' . $subFolder . '/' . $oneF['file_name'];
                           $attachment = [$fileSend];
                       }

                       $this->emailService->sendMail($this->settings, $emailFrom, $emailTo, $subject, $body, $attachment);

                       //prepare data for emailing archive
                       // connect parent table
                       $dataEml = array();
                       if ($one['cl_partners_book_id'] != null){
                           $dataEml['cl_partners_book_id'] = $one['cl_partners_book_id'];
                       }
                       if ($one['in_places_id'] != null){
                           $dataEml['in_places_id'] = $one['in_places_id'];
                       }
                       $dataEml['subject']          = $subject;
                       $dataEml['body']             = $body;
                       $dataEml['singleEmailFrom']  = $emailFrom;
                       $dataEml['singleEmailTo']    = $emailTo[0];
                       $dataEml['attachment']       = json_encode($attachment);
                       $dataEml['in_emailing_id']   = $this->id;
                       //save to cl_emailing
                       $this->emailingManager->insert($dataEml);

                       $one->update(['send' => 1]);
                   }
                    $this->UserManager->resetProgressBar( $this->user->getId());
                    $this->flashMessage('Emaily byly odeslány.', 'success');
                }


            }
        }

        //$this->redirect('default');
        $this->redrawControl('content');
    }

    private function checkEmails(){
        $tmpData = $this->DataManager->find($this->id);
        $ret = TRUE;
        foreach ($tmpData->related('in_emailing_addr')->where('send = 0') as $key => $one){
            if (!filter_var($one['email'], FILTER_VALIDATE_EMAIL)) {
                $emailErr = "Chyba v emailu " . $one['email'] . $one['name'];
                $this->flashMessage($emailErr, 'danger');
                $ret = FALSE;
            }
        }
        $this->redrawControl('flash');
        $this->redrawControl('content');
        return $ret;
    }

    /**Data processing before insert/update on listgrid
     * @param $arrData
     * @return mixed
     */
    public function DataProcessListGrid($arrData)
    {
        unset($arrData['next_date']);
        return $arrData;
    }

    protected function createComponentEmailingLangGrid()
    {
        $arrLang = $this->ArraysManager->getLanguages();
        $arrData = array(
            'lang' => array('Jazyk','format' => 'chzn-select', 'size' => 10,'values' =>  $arrLang, 'required' => 'Jazyk musí být vybrán'),
            'subject' => array('Předmět', 'format' => 'text', 'size' => 20),
            'message' => array('Zpráva', 'format' => 'textarea-formated', 'size' => 150, 'rows' => 10, 'newline' => true)
        );
        $control =  new Controls\ListgridControl(
            $this->translator,
            $this->inEmailingLangManager, //data manager
            $arrData, //data columns
            array(), //row conditions
            $this->id, //parent Id
            array(), //default data
            $this->DataManager, //parent data manager
            NULL, //pricelist manager
            NULL, //pricelist partner manager
            TRUE, //enable add empty row
            array(), //custom links
            FALSE, //movableRow
            'id', //orderColumn
            FALSE, //selectMode
            array(), //quickSearch
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE, //pricelistbottom
            FALSE, //readonly
            FALSE, //nodelete
            FALSE, //enablesearch
            '' //txtSEarchcondition
        );
        $control->setContainerHeight('auto');
        return $control;
    }


    protected function createComponentAddressBook()
    {

        $arrLang = $this->ArraysManager->getLanguages();
        $arrData = array(
                            'email' => array('Email', 'format' => 'text', 'size' => 20),
                            'name' => array('Jméno', 'format' => 'text', 'size' => 20),
                            'lang' => array('Jazyk', 'format' => 'text', 'size' => 10, 'values' =>  $arrLang, 'required' => 'Jazyk musí být vybrán'),
                            'cl_partners_book.company' => array('Firma', 'format' => 'text', 'size' => 20, 'readonly' => true),
                            'cl_partners_branch.b_name' => array('Pobočka', 'format' => 'text', 'size' => 15, 'readonly' => true),
                            'in_places.place_name' => array('Místo', 'format' => 'text', 'size' => 10, 'readonly' => true),
                            'send' => array('Odesláno', 'format' => 'boolean', 'readonly' => true)
        );

        $control =  new Controls\ListgridControl(
            $this->translator,
            $this->inEmailingAddrManager, //data manager
            $arrData, //data columns
            array(), //row conditions
            $this->id, //parent Id
            array(), //default data
            $this->DataManager, //parent data manager
            NULL, //pricelist manager
            NULL, //pricelist partner manager
            TRUE, //enable add empty row
            array(), //custom links
            TRUE, //movableRow
            '', //orderColumn
            FALSE, //selectMode
            array(), //quickSearch
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE, //pricelistbottom
            FALSE, //readonly
            FALSE, //nodelete
            TRUE, //enablesearch
            '' //txtSearchcondition
        );
        $control->setContainerHeight("auto");
        //$control->showHistory(false);
        $control->setEnableSearch('in_emailing_addr.email LIKE ? OR in_emailing_addr.name LIKE ? OR cl_partners_book.company LIKE ? OR in_places.place_name LIKE ? OR cl_partners_branch.b_name LIKE ?');

        return $control;
    }

    protected function createComponentEmailHistory()
    {

        $arrLang = $this->ArraysManager->getLanguages();
        $arrData = array(
            'singleEmailTo' => array('Adresát', 'format' => 'text', 'size' => 20),
            'subject' => array('Předmět', 'format' => 'text', 'size' => 20),
            'singleEmailFrom' => array('Odesílatel', 'format' => 'text', 'size' => 20),
            'in_emailing.cl_partners_book.company' => array('Firma', 'format' => 'text', 'size' => 20, 'readonly' => true),
            'in_emailing.cl_partners_branch.b_name' => array('Pobočka', 'format' => 'text', 'size' => 15, 'readonly' => true),
            'in_emailing.in_places.place_name' => array('Místo', 'format' => 'text', 'size' => 10, 'readonly' => true)
        );

        $control =  new Controls\ListgridControl(
            $this->translator,
            $this->emailingManager, //data manager
            $arrData, //data columns
            array(), //row conditions
            $this->id, //parent Id
            array(), //default data
            $this->DataManager, //parent data manager
            NULL, //pricelist manager
            NULL, //pricelist partner manager
            FALSE, //enable add empty row
            array(), //custom links
            FALSE, //movableRow
            'created DESC', //orderColumn
            FALSE, //selectMode
            array(), //quickSearch
            "", //fontsize
            FALSE, //parentcolumnname
            FALSE, //pricelistbottom
            TRUE, //readonly
            TRUE, //nodelete
            TRUE, //enablesearch
            '' //txtSearchcondition
        );
        $control->setContainerHeight("auto");
        $control->showHistory(false);
        $control->setEnableSearch('singleEmailTo LIKE ? OR singleEmailFrom LIKE ? OR in_emailing.cl_partners_book.company LIKE ? OR in_emailing.in_places.place_name LIKE ? OR in_emailing.cl_partners_branch.b_name LIKE ?');

        return $control;
    }



    protected function createComponentFiles()
    {
        $user_id = $this->user->getId();
        $cl_company_id = $this->settings->id;
        return new Controls\FilesControl(
            $this->translator,$this->FilesManager,$this->UserManager,$this->id,'in_emailing_id', NULL,$cl_company_id,$user_id,
            $this->CompaniesManager, $this->ArraysManager);
    }

    public function handleCustomersAdd(){
        $customers = $this->PartnersManager->findAll()->
                                select('company AS name, email, id, lang')->
                                where('customer = 1 AND email !=""')->
                                order('company')->
                                fetchAll();
        $order = $this->getMaxOrder();

        foreach($customers as $key => $one){
            $order++;
            $arrData['item_order']          = $order;
            $arrData['in_emailing_id']      = $this->id;
            $arrData['name']                = $one['name'];
            $arrData['email']               = $one['email'];
            $arrData['lang']                = $one['lang'];
            $arrData['cl_partners_book_id'] = $one['id'];
            $this->insertAddr($one['email'], $arrData);
        }
        $this->redrawControl('addresses');
    }

    public function handleRemoveAll()
    {
        $tmpErase = $this->inEmailingAddrManager->findAll()->where('in_emailing_id = ?', $this->id);
        foreach($tmpErase as $key => $one){
            $one->delete();
        }
        $this->redrawControl('addresses');
    }

    public function handleSuppliersAdd(){
        $suppliers = $this->PartnersManager->findAll()->
                            select('company AS name, email, id, lang')->
                            where('supplier = 1 AND email !=""')->
                            order('company')->
                            fetchAll();

        $order = $this->getMaxOrder();

        foreach($suppliers as $key => $one){
            $order++;
            $arrData['item_order']          = $order;
            $arrData['in_emailing_id']      = $this->id;
            $arrData['name']                = $one['name'];
            $arrData['email']               = $one['email'];
            $arrData['lang']                = $one['lang'];
            $arrData['cl_partners_book_id'] = $one['id'];
            $this->insertAddr($one['email'], $arrData);
        }
        $this->redrawControl('addresses');
    }

    public function handleWorkersAdd($type = "", $partner_type = "cust"){
        $workers = $this->PartnersBookWorkersManager->findAll()->
                                select('worker_name AS name, worker_email AS email, cl_partners_book_id, cl_partners_book.lang AS lang')->
                                where('worker_email != ""')->
                                order('worker_name');

        if ($partner_type == "cust"){
            $workers = $workers->where('cl_partners_book.customer = 1');
        }elseif ($partner_type == "sup"){
            $workers = $workers->where('cl_partners_book.supplier = 1');
        }

        if ($type == "cl_invoice"){
            $workers = $workers->where('cl_invoice = 1 OR cl_invoice_arrived = 1 OR cl_invoice_advance = 1');
        }elseif ($type == "cl_delivery"){
            $workers = $workers->where('cl_delivery = 1 OR cl_delivery_note = 1');
        }elseif ($type != "") {
            $cond = $type . " = 1";
            $workers = $workers->where($cond);
        }
        $workers = $workers->fetchAll();

        $order = $this->getMaxOrder();

        foreach($workers as $key => $one){
            $order++;
            $arrData['item_order']          = $order;
            $arrData['in_emailing_id']      = $this->id;
            $arrData['name']                = $one['name'];
            $arrData['email']               = $one['email'];
            $arrData['lang']                = $one['lang'];
            $arrData['cl_partners_book_id'] = $one['cl_partners_book_id'];
            $this->insertAddr($one['email'], $arrData);
        }
        $this->redrawControl('addresses');

    }

    public function handleBranchAdd($partner_type = "cust"){
        $branches = $this->PartnersBranchManager->findAll()->
                        select('b_name AS name, b_email AS email, cl_partners_book_id AS id, cl_partners_branch.id AS cl_partners_branch_id, cl_partners_book.lang AS lang')->
                        where('b_email !=""')->
                        order('b_name');

        if ($partner_type == "cust"){
            $branches = $branches->where('cl_partners_book.customer = 1');
        }elseif ($partner_type == "sup"){
            $branches = $branches->where('cl_partners_book.supplier = 1');
        }

        $branches = $branches->fetchAll();

        $order = $this->getMaxOrder();

        foreach($branches as $key => $one){
            $order++;
            $arrData['item_order']          = $order;
            $arrData['in_emailing_id']      = $this->id;
            $arrData['name']                = $one['name'];
            $arrData['email']               = $one['email'];
            $arrData['cl_partners_book_id'] = $one['id'];
            $arrData['lang']                = $one['lang'];
            $arrData['cl_partners_branch_id'] = $one['cl_partners_branch_id'];
            $this->insertAddr($one['email'], $arrData);
        }
        $this->redrawControl('addresses');
    }

    public function handlePlacesAdd(){
        $places = $this->PlacesManager->findAll()->
                            select('place_name AS name, email, id, lang')->
                            where('email !=""')->
                            order('place_name');

        $order = $this->getMaxOrder();
        $places = $places->fetchAll();

        foreach($places as $key => $one){
            $order++;
            $arrData['item_order']          = $order;
            $arrData['in_emailing_id']      = $this->id;
            $arrData['name']                = $one['name'];
            $arrData['email']               = $one['email'];
            $arrData['lang']                = $one['lang'];
            $arrData['in_places_id']        = $one['id'];
            $this->insertAddr($one['email'], $arrData);
        }
        $this->redrawControl('addresses');
    }


    public function handleStaffAdd($roleId = null){
        $staff = $this->staffManager->findAll()->
                                select('CONCAT(surname, " ", name) AS name, email, id, lang')->
                                where('email !=""')->
                                order('name');

        if ($roleId != null){
            $staff = $staff->where('in_staff_role_id = ?', $roleId);
        }

        $staff = $staff->fetchAll();
        $order = $this->getMaxOrder();

        foreach($staff as $key => $one){
            $order++;
            $arrData['item_order']          = $order;
            $arrData['in_emailing_id']      = $this->id;
            $arrData['name']                = $one['name'];
            $arrData['email']               = $one['email'];
            $arrData['lang']                = $one['lang'];
            $arrData['in_staff_id']         = $one['id'];
            $this->insertAddr($one['email'], $arrData);
        }
        $this->redrawControl('addresses');
    }

    public function handlePartnersGroupsAdd($groupId = null, $partner_type = 'cust'){
        $company = $this->PartnersManager->findAll()->
                                select('company AS name, email, id, lang')->
                                where('email !=""')->
                                order('company');

        if ($company != null && $groupId != null){
            $company = $company->where('cl_partners_groups_id = ?', $groupId);
        }

        if ($partner_type == "cust"){
            $company = $company->where('customer = 1');
        }elseif ($partner_type == "sup"){
            $company = $company->where('supplier = 1');
        }
        $company = $company->fetchAll();

        $order = $this->getMaxOrder();

        foreach($company as $key => $one){
            $order++;
            $arrData['item_order']          = $order;
            $arrData['in_emailing_id']      = $this->id;
            $arrData['name']                = $one['name'];
            $arrData['email']               = $one['email'];
            $arrData['lang']                = $one['lang'];
            $arrData['cl_partners_book_id'] = $one['id'];
            $this->insertAddr($one['email'], $arrData);
        }
        $this->redrawControl('addresses');
    }



    private function insertAddr($email, $arrData)
    {
        $find = $this->inEmailingAddrManager->findAll()->where('email = ? AND in_emailing_id = ?', $email, $this->id)->fetch();
        if (!$find) {
            $this->inEmailingAddrManager->insert($arrData);
        }
    }

    private function getMaxOrder(){
        return $this->inEmailingAddrManager->findAll()->where('id = ?', $this->id)->max('item_order');
    }

    public function getStatEml($arrData)
    {
        $numAddr = $this->DataManager->findAll()->where('in_emailing.id = ? ', $arrData['id'])->count(':in_emailing_addr.id');
        $numSend = $this->DataManager->findAll()->where('in_emailing.id = ? AND :in_emailing_addr.send = 1', $arrData['id'])->count(':in_emailing_addr.id');

        return $numAddr . " / " . $numSend;
    }


}
