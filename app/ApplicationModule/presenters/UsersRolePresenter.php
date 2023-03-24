<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class UsersRolePresenter extends \App\Presenters\BaseListPresenter {


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
    * @var \App\Model\UsersRoleManager
    */
    public $DataManager;    

    /**
    * @inject
    * @var \App\Model\UserManager
    */
    public $UserManager;       

    
    /**
    * @inject
    * @var \App\Model\CompaniesAccessManager
    */
    public $CompaniesAccessManager;   
    
    /**
    * @inject
    * @var \App\Model\TablesSettingManager
    */
    public $TablesSettingManager;       
    
    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.UsersRole']);
        $this->dataColumns = ['name' => $this->translator->translate('Název_skupiny'),
                        'created' => [$this->translator->translate('Vytvořeno'), 'format' => 'datetime'],'create_by' => $this->translator->translate('Vytvořil'),'changed' => [$this->translator->translate('Změněno'), 'format' => 'datetime'],'change_by' => $this->translator->translate('Změnil')];
        $this->FilterC = 'UPPER(name) LIKE ?';
        //$this->formatColumns = array('last_login' => "datetime");
        $this->DefSort = 'name';

        $this->toolbar = [1 => ['url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nový_záznam'), 'class' => 'btn btn-primary']];
    }	
    
    public function renderDefault($page_b = 1,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal, $cxs)  {
	    parent::renderDefault($page_b,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal,$cxs);

    }
        
    public function renderEdit($id,$copy,$modal){
		parent::renderEdit($id,$copy,$modal);
		$this->template->arrModules = $this->ArraysManager->getModNames4Rights();
		if ($defData = $this->DataManager->findOneBy(['id' => $id]))
		{
			$this['edit']->setDefaults($defData);
			$defData2 = (array)json_decode($defData['map_role']);
            unset($defData2['id']);
			$this['edit']->setDefaults($defData2);
		}
		//if ($copy)
		//	$this['edit']->setValues(array('id' => ''));

		//dump($this->presenter->name);
		//die;
    }
    
    
    protected function createComponentEdit3($name)
    {	
            $form = new Form($this, $name);
	    //$form->setMethod('POST');
	    $form->addHidden('id',NULL);
        $form->addText('name', $this->translator->translate('Název_skupiny'), 40, 40)
			->setAttribute('placeholder',$this->translator->translate('Název_skupiny'));
	    
	    $form->addCheckbox('application_homepage_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_homepage_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_homepage_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_homepage_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_homepage_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
	    //$form->addCheckbox('cl_users_role','Jen_vlastní_záznamy')->setDefaultValue(TRUE);


        $form->addCheckbox('application_settings_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_settings_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_settings_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_settings_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_settings_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('cl_company',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(TRUE);
	    
	    $form->addCheckbox('application_usersrole_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_usersrole_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_usersrole_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_usersrole_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_usersrole_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('cl_users_role',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(TRUE);
	    
	    $form->addCheckbox('application_partners_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_partners_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_partners_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_partners_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_partners_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('cl_partners_book',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(TRUE);

	    $form->addCheckbox('application_partnerscategory_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_partnerscategory_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_partnerscategory_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_partnerscategory_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
		$form->addCheckbox('application_partnerscategory_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('cl_partners_category',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(TRUE);
	    
	    $form->addCheckbox('application_bankaccounts_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_bankaccounts_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_bankaccounts_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_bankaccounts_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_bankaccounts_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('cl_bank_accounts',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(TRUE);
	    
	    $form->addCheckbox('application_commission_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_commission_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_commission_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_commission_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_commission_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('cl_commission',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(TRUE);

        $form->addCheckbox('application_offer_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_offer_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_offer_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_offer_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_offer_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('cl_offer',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(TRUE);


        $form->addCheckbox('application_currencies_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_currencies_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_currencies_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_currencies_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_currencies_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('cl_currencies',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(TRUE);

	    $form->addCheckbox('application_eventtypes_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_eventtypes_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_eventtypes_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_eventtypes_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_eventtypes_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('cl_partners_event_type',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(TRUE);

        $form->addCheckbox('application_eventmethod_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_eventmethod_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_eventmethod_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_eventmethod_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_eventmethod_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);

        $form->addCheckbox('application_center_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_center_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_center_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_center_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_center_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);

        $form->addCheckbox('application_emailingtext_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_emailingtext_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_emailingtext_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_emailingtext_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_emailingtext_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);


        $form->addCheckbox('application_headersfooters_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_headersfooters_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_headersfooters_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_headersfooters_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_headersfooters_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);

        $form->addCheckbox('application_texts_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_texts_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_texts_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_texts_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_texts_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);

        $form->addCheckbox('application_saleshorts_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_saleshorts_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_saleshorts_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_saleshorts_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_saleshorts_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);


	    $form->addCheckbox('application_invoice_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_invoice_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_invoice_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_invoice_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_invoice_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('cl_invoice',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);
	    
	    $form->addCheckbox('application_invoice_paymentlistgrid_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_invoice_paymentlistgrid_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_invoice_paymentlistgrid_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_invoice_paymentlistgrid_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_invoice_paymentlistgrid_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('cl_invoice_payment',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);
	    

	    $form->addCheckbox('application_invoicearrived_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_invoicearrived_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_invoicearrived_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_invoicearrived_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_invoicearrived_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('cl_invoice_arrived',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        //Application:InvoiceArrived:paymentListGrid
        $form->addCheckbox('application_invoicearrived_paymentlistgrid_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_invoicearrived_paymentlistgrid_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_invoicearrived_paymentlistgrid_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_invoicearrived_paymentlistgrid_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_invoicearrived_paymentlistgrid_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('cl_invoice_arrived_payment',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('application_invoiceadvance_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_invoiceadvance_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_invoiceadvance_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_invoiceadvance_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_invoiceadvance_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('cl_invoice_advance',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);
	    
	    //Application:InvoiceArrived:paymentListGrid	    
	    $form->addCheckbox('application_invoiceadvance_paymentlistgrid_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_invoiceadvance_paymentlistgrid_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_invoiceadvance_paymentlistgrid_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_invoiceadvance_paymentlistgrid_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_invoiceadvance_paymentlistgrid_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('cl_invoice_advance_payment',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);
	    
	    $form->addCheckbox('application_deliverynote_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_deliverynote_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_deliverynote_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_deliverynote_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_deliverynote_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('cl_delivery_note',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

	    
	    $form->addCheckbox('application_sale_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_sale_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_sale_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_sale_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_sale_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_sale_edit_price',$this->translator->translate('Úpravy cen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('cl_sale',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

	    $form->addCheckbox('application_salereview_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_salereview_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_salereview_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_salereview_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_salereview_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);

	    

	    $form->addCheckbox('application_invoicetypes_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_invoicetypes_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_invoicetypes_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_invoicetypes_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_invoicetypes_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('cl_invoice_types',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);
	    
	    $form->addCheckbox('application_numberseries_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_numberseries_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_numberseries_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_numberseries_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_numberseries_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('cl_number_series',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);
	    
	    $form->addCheckbox('application_order_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_order_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_order_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_order_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_order_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('cl_order',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);
	    
	    /*$form->addCheckbox('application_events_enabled','Přístup_povolen')->setDefaultValue(TRUE);
	    $form->addCheckbox('application_events_write','Zápis')->setDefaultValue(TRUE);
	    $form->addCheckbox('application_events_erase','Mazání')->setDefaultValue(TRUE);
	    $form->addCheckbox('application_events_edit','Úpravy')->setDefaultValue(TRUE);
	    $form->addCheckbox('cl_events','Jen_vlastní_záznamy')->setDefaultValue(TRUE);	    
	    */
	    
	    $form->addCheckbox('application_helpdesk_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_helpdesk_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_helpdesk_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_helpdesk_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_helpdesk_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('cl_partners_event',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);
	    
	    $form->addCheckbox('application_helpdeskbilling_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_helpdeskbilling_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_helpdeskbilling_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_helpdeskbilling_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_helpdeskbilling_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
	    
	    $form->addCheckbox('application_paymenttypes_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_paymenttypes_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_paymenttypes_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_paymenttypes_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_paymenttypes_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('cl_payment_types',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);
	    
	    $form->addCheckbox('application_pricelistgroup_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_pricelistgroup_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_pricelistgroup_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_pricelistgroup_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_pricelistgroup_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('cl_pricelist_group',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

	    $form->addCheckbox('application_pricesgroups_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_pricesgroups_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_pricesgroups_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_pricesgroups_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_pricesgroups_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('cl_prices_groups',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

	    $form->addCheckbox('application_pricelist_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_pricelist_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_pricelist_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_pricelist_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_pricelist_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('cl_pricelist',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('application_pricelistview_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_pricelistview_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_pricelistview_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_pricelistview_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_pricelistview_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        //$form->addCheckbox('cl_pricelist','Jen_vlastní_záznamy')->setDefaultValue(TRUE);

	    $form->addCheckbox('application_status_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_status_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_status_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_status_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_status_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('cl_status',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

	    $form->addCheckbox('application_storage_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_storage_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_storage_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_storage_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_storage_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('cl_storage',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);
	    
	    $form->addCheckbox('application_store_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_store_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_store_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_store_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_store_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('cl_store',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('application_storereview_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_storereview_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_storereview_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_storereview_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_storereview_edit_price',$this->translator->translate('Nákupní ceny'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_storereview_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);


        $form->addCheckbox('application_kdb_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_kdb_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_kdb_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_kdb_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_kdb_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('cl_kdb',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('application_companybranch_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_companybranch_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_companybranch_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_companybranch_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_companybranch_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('cl_company_branch',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('application_cash_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_cash_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_cash_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_cash_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_cash_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('cl_cash',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);


        $form->addCheckbox('application_kdbcategory_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_kdbcategory_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_kdbcategory_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_kdbcategory_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_kdbcategory_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('cl_kdb_category',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

	    $form->addCheckbox('application_filemanager_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_filemanager_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_filemanager_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_filemanager_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_filemanager_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('cl_files',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('application_inventory_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_inventory_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_inventory_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_inventory_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_inventory_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('cl_inventory',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('application_banktrans_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_banktrans_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_banktrans_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_banktrans_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_banktrans_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('cl_bank_trans',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('application_transport_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_transport_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_transport_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_transport_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_transport_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('cl_transport',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('application_expedition_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_expedition_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_expedition_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_expedition_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_expedition_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('cl_expedition',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('application_planecalendar_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_planecalendar_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_planecalendar_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_planecalendar_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_planecalendar_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('cl_calendar_plane',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('application_users_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_users_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_users_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_users_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('application_users_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
	    $form->addCheckbox('cl_users',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('application_reportmanager_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_reportmanager_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_reportmanager_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_reportmanager_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_reportmanager_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('cl_reports',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('application_workplaces_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_workplaces_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_workplaces_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_workplaces_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_workplaces_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('cl_workplaces',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('application_transporttypes_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_transporttypes_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_transporttypes_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_transporttypes_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_transporttypes_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('cl_transport_types',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('application_cashdef_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_cashdef_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_cashdef_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_cashdef_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_cashdef_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('cl_cash_def',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('application_eshops_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_eshops_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_eshops_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_eshops_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('application_eshops_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('cl_eshops',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);


        $form->addCheckbox('intranet_commission_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_commission_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_commission_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_commission_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_commission_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
       // $form->addCheckbox('cl_commission',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('intranet_complaint_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_complaint_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_complaint_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_complaint_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_complaint_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('in_complaint',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('intranet_estate_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_estate_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_estate_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_estate_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_estate_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('in_estate',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('intranet_estatetype_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_estatetype_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_estatetype_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_estatetype_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_estatetype_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('in_estate_type',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('intranet_folders_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_folders_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_folders_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_folders_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_folders_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('in_folders',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('intranet_staffplan_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_staffplan_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_staffplan_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_staffplan_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_staffplan_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);


        $form->addCheckbox('intranet_lectors_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_lectors_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_lectors_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_lectors_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_lectors_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('in_lectors',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('intranet_nations_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_nations_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_nations_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_nations_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_nations_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('in_nations',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('intranet_network_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_network_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_network_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_network_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_network_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('in_network',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('intranet_places_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_places_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_places_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_places_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_places_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('in_places',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('intranet_profession_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_profession_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_profession_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_profession_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_profession_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('in_profession',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('intranet_staff_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_staff_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_staff_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_staff_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_staff_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('in_staff',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('intranet_staffrole_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_staffrole_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_staffrole_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_staffrole_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_staffrole_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('in_staff_role',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('intranet_training_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_training_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_training_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_training_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_training_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('in_training',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('intranet_trainingtypes_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_trainingtypes_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_trainingtypes_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_trainingtypes_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_trainingtypes_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('in_training_types',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);


        $form->addCheckbox('intranet_workstypes_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_workstypes_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_workstypes_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_workstypes_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_workstypes_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('in_works_types',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('intranet_rental_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_rental_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_rental_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_rental_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_rental_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('in_rental',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('intranet_reservation_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_reservation_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_reservation_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_reservation_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_reservation_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('in_estate_reservation',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('intranet_emailing_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_emailing_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_emailing_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_emailing_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_emailing_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('in_emailing',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addCheckbox('intranet_instructions_enabled',$this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_instructions_write',$this->translator->translate('Zápis'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_instructions_erase',$this->translator->translate('Mazání'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_instructions_edit',$this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
        $form->addCheckbox('intranet_instructions_report',$this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
        $form->addCheckbox('in_instructions',$this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);

        $form->addSubmit('send', $this->translator->translate('Uložit'))->setAttribute('class','btn btn-success');
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
		    ->setAttribute('class','btn btn-warning')
		    ->setValidationScope([])
			->onClick[] = array($this, 'stepBack');	    	    
	//	    ->onClick[] = callback($this, 'stepSubmit');
		$form->onSuccess[] = array($this, 'SubmitEditSubmitted');
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
			$data['map_role'] = json_encode($data);
			foreach ($data as $key => $one)
			{
				if (substr($key,0,11) == 'application' || substr($key,0,3) == 'cl_' || substr($key,0,8) == 'intranet' || substr($key,0,3) == 'in_'  )
					unset($data[$key]);
			}
			if (!empty($data->id))
			{
			    $this->DataManager->update($data);
			}


		}
        $this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
        $this->redirect('default');
    }

    protected function createComponentEdit($name)
    {
        $form = new Form($this, $name);
        $form->addHidden('id', NULL);
        $form->addText('name', $this->translator->translate('Název_skupiny'), 40, 40)
                ->setHtmlAttribute('placeholder',$this->translator->translate('Název_skupiny'));

        foreach($this->ArraysManager->getModNames4Rights() as $keyGroup => $oneGroup){
            foreach($oneGroup['items'] as $key => $one){
                $name = $one['presenter'];
                $form->addText( $name.'_name', $this->translator->translate('Název_skupiny'), 40,40)
                    ->setHtmlAttribute('readonly', 'readonly');
                $form->addCheckbox($name.'_enabled', $this->translator->translate('Přístup_povolen'))->setDefaultValue(TRUE);
                $form->addCheckbox($name.'_write', $this->translator->translate('Zápis'))->setDefaultValue(TRUE);
                $form->addCheckbox($name.'_erase', $this->translator->translate('Mazání'))->setDefaultValue(TRUE);
                $form->addCheckbox($name.'_edit', $this->translator->translate('Úpravy'))->setDefaultValue(TRUE);
                $form->addCheckbox($name.'_report', $this->translator->translate('Sestavy'))->setDefaultValue(TRUE);
                if (!is_null($one['data_table'])){
                    $form->addCheckbox($one['data_table'], $this->translator->translate('Jen_vlastní_záznamy'))->setDefaultValue(FALSE);
                }
            }
        }


        $form->addSubmit('send', $this->translator->translate('Uložit'))->setAttribute('class','btn btn-success');
        $form->addSubmit('back', $this->translator->translate('Zpět'))
            ->setHtmlAttribute('class','btn btn-warning')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBack');
        //	    ->onClick[] = callback($this, 'stepSubmit');
        $form->onSuccess[] = array($this, 'SubmitEditSubmitted');
        return $form;
    }


    //aditional function called before insert copied record
    public function beforeCopy($data)
    {
        return $data;
    }

    //aditional function called after inserted copied record
    public function afterCopy($newLine, $oldLine)
    {
        $tmpOld = $this->DataManager->find($oldLine);
        $tmpNew = $this->DataManager->find($newLine);
        if ($tmpOld && $tmpNew) {

        }

        return TRUE;
    }


}
