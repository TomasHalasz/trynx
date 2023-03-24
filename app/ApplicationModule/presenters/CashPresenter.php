<?php

namespace App\ApplicationModule\Presenters;

use App\Controls;
use Nette\Application\UI\Form,
    Nette\Image;
use Nette\Mail\Message,
    Nette\Utils\Strings;
use Nette\Mail\SendmailMailer;

use Tracy\Debugger;

class CashPresenter extends \App\Presenters\BaseListPresenter
{


    const
        DEFAULT_STATE = 'Czech Republic';

    public $createDocShow = FALSE, $branchNumberSeriesCorrectionId, $pairedDocsShow = FALSE;


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
     * @var \App\Model\CashManager
     */
    public $DataManager;


    /**
     * @inject
     * @var \App\Model\InvoiceTypesManager
     */
    public $InvoiceTypesManager;


    /**
     * @inject
     * @var \App\Model\CountriesManager
     */
    public $CountriesManager;

    /**
     * @inject
     * @var \App\Model\CurrenciesManager
     */
    public $CurrenciesManager;

    /**
     * @inject
     * @var \App\Model\InvoiceManager
     */
    public $InvoiceManager;

    /**
     * @inject
     * @var \App\Model\InvoicePaymentsManager
     */
    public $InvoicePaymentsManager;

    /**
     * @inject
     * @var \App\Model\InvoiceArrivedManager
     */
    public $InvoiceArrivedManager;

    /**
     * @inject
     * @var \App\Model\InvoiceArrivedPaymentsManager
     */
    public $InvoiceArrivedPaymentsManager;

    /**
     * @inject
     * @var \App\Model\InvoiceAdvanceManager
     */
    public $InvoiceAdvanceManager;

    /**
     * @inject
     * @var \App\Model\InvoiceAdvancePaymentsManager
     */
    public $InvoiceAdvancePaymentsManager;

    /**
     * @inject
     * @var \App\Model\PaymentTypesManager
     */
    public $PaymentTypesManager;

    /**
     * @inject
     * @var \App\Model\SaleItemsManager
     */
    public $SaleItemsManager;

    /**
     * @inject
     * @var \App\Model\RatesVatManager
     */
    public $RatesVatManager;

    /**
     * @inject
     * @var \App\Model\ArraysManager
     */
    public $ArraysManager;

    /**
     * @inject
     * @var \App\Model\PriceListPartnerManager
     */
    public $PriceListPartnerManager;

    /**
     * @inject
     * @var \App\Model\EmailingManager
     */
    public $EmailingManager;

    /**
     * @inject
     * @var \App\Model\StoreDocsManager
     */
    public $StoreDocsManager;


    /**
     * @inject
     * @var \App\Model\StoreManager
     */
    public $StoreManager;

    /**
     * @inject
     * @var \App\Model\StorageManager
     */
    public $StorageManager;

    /**
     * @inject
     * @var \App\Model\CompanyBranchManager
     */
    public $CompanyBranchManager;

    /**
     * @inject
     * @var \App\Model\CompanyBranchUsersManager
     */
    public $CompanyBranchUsersManager;

    /**
     * @inject
     * @var \MainServices\EETService
     */
    public $EETService;

    /**
     * @inject
     * @var \App\Model\PairedDocsManager
     */
    public $PairedDocsManager;


    /**
     * @inject
     * @var \App\Model\EETManager
     */
    public $EETManager;

    /**
     * @inject
     * @var \App\Model\CenterManager
     */
    public $CenterManager;

    protected function createComponentPairedDocs()
    {
        // $translator = clone $this->translator;
        // $translator->setPrefix([]);
        return new PairedDocsControl(
            $this->DataManager, $this->id, $this->PairedDocsManager, $this->translator);
    }

    protected function createComponentEmail()
    {
        //$translator = clone $this->translator->setPrefix([]);
        return new Controls\EmailControl($this->translator,
            $this->EmailingManager, $this->mainTableName, $this->id);
    }

    protected function createComponentEditTextDescription()
    {
        return new Controls\EditTextControl($this->translator,
            $this->DataManager, $this->id, 'description_txt');
    }


    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.Cash']);

        $this->mainTableName = 'cl_cash';
        $this->formName = $this->translator->translate("Pokladna");
        //$settings = $this->CompaniesManager->getTable()->fetch();

        $arrData = array('cash_number' => array($this->translator->translate('Číslo_dokladu'), 'format' => 'text'),
            'inv_date' => array($this->translator->translate('Vystaveno'), 'format' => 'date'),
            'cl_invoice_types.name' => array($this->translator->translate('Typ_dokladu'), 'format' => 'text'),
            'cl_partners_book.company' => array($this->translator->translate('Odběratel_/_dodavatel'), 'format' => 'text', 'show_clink' => true),
            'title' => $this->translator->translate('Text_dokladu'),
            'cash' => array($this->translator->translate('Částka'), 'format' => 'currency'),
            'cl_currencies.currency_name' => $this->translator->translate('Měna'),
            'currency_rate' => $this->translator->translate('Kurz'),
            's_eml' => array('E-mail', 'format' => 'boolean'),
            'cl_center.name' => array($this->translator->translate('Středisko'), 'format' => 'text'),
            'cl_users.name' => $this->translator->translate('Přijal_/_Vydal'),
            'cl_invoice.inv_number' => $this->translator->translate('Faktura_vydaná'),
            'cl_invoice_arrive.inv_number' => $this->translator->translate('Faktura_přijatá'),
            'cl_sale.sale_number' => $this->translator->translate('Prodejka'),
            'created' => array($this->translator->translate('Vytvořeno'), 'format' => 'datetime'), 'create_by' => $this->translator->translate('Vytvořil'), 'changed' => array($this->translator->translate('Změněno'), 'format' => 'datetime'), 'change_by' => $this->translator->translate('Změnil'));

        $this->dataColumns = $arrData;
        $this->filterColumns = array('cash_number' => '', 'cl_partners_book.company' => 'autocomplete', 'cash' => 'autocomplete',
            'cash' => '', 'cl_invoice.inv_number' => 'autocomplete', 'cl_invoice_arrive.inv_number' => 'autocomplete', 'cl_sale.sale_number' => 'autocomplete',
            'title' => '', 'cl_users.name' => 'autocomplete', 'cl_invoice_types.name' => 'autocomplete');
        $this->DefSort = 'cl_cash.inv_date DESC';

        $defDueDate = new \Nette\Utils\DateTime;
        //07.07.2019 - select branch if there are defined
        //$tmpBranchId = $this->CompanyBranchUsersManager->getBranchForUser($this->getUser()->id);
        //$tmpBranchId = $this->getUser()->cl_company_branch_id;
        $tmpBranchId = $this->user->getIdentity()->cl_company_branch_id;


        //$this->numberSeries = 'commission';
        $this->numberSeries = array('use' => 'cash_in', 'table_key' => 'cl_number_series_id', 'table_number' => 'cash_number');
        $this->readOnly = array('cash_number' => TRUE,
            'created' => TRUE,
            'create_by' => TRUE,
            'changed' => TRUE,
            'change_by' => TRUE);
        //$this->toolbar = array(	1 => array('url' => $this->link('newIn!'), 'rightsFor' => 'write', 'label' => 'Nový příjem', 'class' => 'btn btn-primary'),
        //                        2 => array('url' => $this->link('newOut!'), 'rightsFor' => 'write', 'label' => 'Nový výdej', 'class' => 'btn btn-primary'));


        $this->toolbar = array(0 => array('group_start' => ''),
            1 => array('url' => $this->link('newIn!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nový_příjem'), 'class' => 'btn btn-primary', 'icon' => 'iconfa-plus'),
            2 => $this->getNumberSeriesArray('cash_in'),
            3 => array('group_end' => ''),
            4 => array('group_start' => ''),
            5 => array('url' => $this->link('newOut!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nový_výdej'), 'class' => 'btn btn-primary', 'icon' => 'iconfa-minus'),
            6 => $this->getNumberSeriesArray('cash_out'),
            7 => array('group_end' => ''),
            8 => array('group' =>
                array(0 => array('url' => $this->link('report!', array('index' => 1)),
                    'rightsFor' => 'report',
                    'label' => $this->translator->translate('Pokladní_kniha'),
                    'title' => $this->translator->translate('Pokladní_kniha_za_zvolené_období'),
                    'data' => array('data-ajax="true"', 'data-history="false"'),
                    'class' => 'ajax', 'icon' => 'iconfa-print'),
                ),
                'group_settings' =>
                    array('group_label' => $this->translator->translate('Tisk'),
                        'group_class' => 'btn btn-primary dropdown-toggle btn-sm',
                        'group_title' => $this->translator->translate('tisk'), 'group_icon' => 'iconfa-print')
            )
        );

        $this->report = array(1 => array('reportLatte' => __DIR__ . '/../templates/Cash/ReportCashSettings.latte',
            'reportName' => $this->translator->translate('Pokladní_kniha')));

        //$this->showChildLink = 'PartnersEvent:default';
        //Condition for color highlit rows
        //$testDate = new \Nette\Utils\DateTime;
        //$testDate = $testDate->modify('-30 day');
        //$this->conditionRows = array( 'cdate','<=',$testDate);

        $this->rowFunctions = array('copy' => 'disabled');


        $this->bscOff = FALSE;
        $this->bscEnabled = $this->getUser()->getIdentity()->bsc_enabled;
        $this->bscPages = array('card' => array('active' => false, 'name' => $this->translator->translate('karta'), 'lattefile' => $this->getLattePath() . 'Cash\card.latte'),
            'memos' => array('active' => true, 'name' => $this->translator->translate('poznámky'), 'lattefile' => $this->getLattePath() . 'Cash\description.latte'),
            'files' => array('active' => false, 'name' => $this->translator->translate('soubory'), 'lattefile' => $this->getLattePath() . 'Cash\files.latte')
        );
        /*$this->bscPages = array(
                    'header' => array('active' => false, 'name' => 'záhlaví', 'lattefile' => $this->getLattePath(). 'Invoice\header.latte')
                    );	*/
        /*if ($this->settings->invoice_to_store == 0){
            unset($this->bscPages['itemsback']);
        }*/

        $this->bscSums = array();
        $this->bscToolbar = array(
            1 => array('url' => 'showPairedDocs!', 'rightsFor' => 'write', 'label' => 'doklady', 'class' => 'btn btn-success',
                'data' => array('data-ajax="true"', 'data-history="false"'), 'icon' => 'glyphicon glyphicon-list-alt'),
            2 => array('url' => 'savePDF!', 'rightsFor' => 'enable', 'label' => 'Tisk', 'class' => 'btn btn-success',
                'data' => array('data-ajax="false"', 'data-history="false"'), 'icon' => 'glyphicon glyphicon-print'),
            3 => array('url' => 'downloadPDF!', 'rightsFor' => 'enable', 'label' => 'PDF', 'class' => 'btn btn-success',
                'data' => array('data-ajax="false"', 'data-history="false"'), 'icon' => 'glyphicon glyphicon-save'),
            4 => array('url' => 'sendDoc!', 'rightsFor' => 'write', 'label' => 'E-mail', 'class' => 'btn btn-success', 'icon' => 'glyphicon glyphicon-send'),

        );
        $this->bscTitle = array('cash_number' => $this->translator->translate('Číslo_dokladu'), 'cl_partners_book.company' => $this->translator->translate('Odběratel_/_dodavatel'));
        $this->userFilterEnabled = TRUE;
        $this->userFilter = array('cash_number', 'cash', 'cl_partners_book.company', 'title');


        //17.08.2018 - settings for documents saving and emailing
        $this->docTemplate[1] = __DIR__ . '/../templates/Cash/cashdoc.latte';
        $this->docTemplate[2] = __DIR__ . '/../templates/Cash/cashdoc.latte';

        $this->docAuthor = $this->user->getIdentity()->name . " z " . $this->settings->name;
        $this->docTitle[1] = array($this->translator->translate("Pokladní_doklad"), "cash_number");
        $this->docTitle[2] = array($this->translator->translate("Výdajový_pokladní_doklad"), "cash_number");

        //17.08.2018 - settings for sending doc by email
        $this->docEmail = array('template' => __DIR__ . '/../templates/Cash/emailCash.latte',
            'emailing_text' => 'cash');

        /*$this->quickFilter = array('cl_invoice_types.name' => array('name' => 'Zvolte filtr zobrazení',
            'values' =>  $this->InvoiceTypesManager->findAll()->where('inv_type != ?',4)->fetchPairs('id','name'))
        );*/

        //13.05.2019 - select branch if there are defined
        //$tmpBranchId = $this->CompanyBranchUsersManager->getBranchForUser($this->getUser()->id);
        //$tmpBranchId = $this->getUser()->cl_company_branch_id;
        $tmpBranchId = $this->user->getIdentity()->cl_company_branch_id;
        $tmpBranch = $this->CompanyBranchManager->find($tmpBranchId);
        if ($tmpBranch) {
            $tmpBranchDiscount = $tmpBranch->discount;
            $tmpBranchNumberSeries = $tmpBranch->cl_number_series_id_correction;
            $tmpBranchPartnersBook = $tmpBranch->cl_partners_book_id;
            $tmpBranchPartnersStorage = $tmpBranch->cl_storage_id;
            $tmpCenterId = $tmpBranch->cl_center_id;
        } else {
            $tmpBranchDiscount = 0;
            $tmpBranchNumberSeries = NULL;
            $tmpCenterId = NULL;
            $tmpBranchPartnersBook = $this->settings->cl_partners_book_id_sale;
            $tmpBranchPartnersStorage = $this->settings->cl_storage_id_sale;
        }
        $this->branchNumberSeriesCorrectionId = $tmpBranchNumberSeries;
        $this->defValues = array('inv_date' => new \Nette\Utils\DateTime,
            'cl_company_branch_id' => $tmpBranchId,
            'cl_center_id' => $tmpCenterId,
            'cl_currencies_id' => $this->settings->cl_currencies_id,
            'currency_rate' => $this->settings->cl_currencies->fix_rate);

        if ( $this->isAllowed($this->presenter->name,'report')) {
            $this->groupActions['pdf'] = 'stáhnout PDF';
        }
    }


    public function forceRO($data)
    {

        $ret = parent::forceRO($data);
        if ($data && (!is_null($data->cl_invoice_id) || !is_null($data->cl_invoice_arrived_id) || !is_null($data->cl_sale_id))) {
            $ret = $ret || TRUE;
        } else {
            $ret = $ret || FALSE;
        }


        return $ret;
    }


    public function renderDefault($page_b = 1, $idParent, $filter, $sortKey, $sortOrder, $filterColumn, $filterValue, $modal, $cxs)
    {
        parent::renderDefault($page_b, $idParent, $filter, $sortKey, $sortOrder, $filterColumn, $filterValue, $modal, $cxs);
        //dump($this->conditionRows);
        //die;
        $this->template->customUrl = array('pricelist2' => $this->link('RedrawPriceList2!')
        );
        $tmpCash = $this->dataForSums;
        if ($this->user->getIdentity()->quick_sums) {
            $now = new \Nette\Utils\DateTime;
            $today = date('Y-m-d', strtotime($now->format('d.m.Y')));
            $tmpCashC = clone $tmpCash;
            $tmpAmount = $tmpCashC->where('inv_date = ? AND cash >=0', $today)->sum('(cash * currency_rate)');
            if (is_null($tmpAmount)) $tmpAmount = 0;
            $tmpLbl = 'label-success';

            $tmpCashC = clone $tmpCash;
            $tmpAmount2 = $tmpCashC->where('inv_date = ? AND cash <0', $today)->sum('cash * currency_rate');
            if (is_null($tmpAmount2)) $tmpAmount2 = 0;
            $tmpLbl2 = 'label-warning';

            $tmpCashC = clone $tmpCash;
            $tmpAmount3 = $tmpCashC->where('inv_date < ?', $today)->sum('cash * currency_rate');
            if ($tmpAmount3 >= 0) {
                $tmpLbl3 = 'label-success';
            } else {
                $tmpLbl3 = 'label-danger';
            }

            $tmpCashC = clone $tmpCash;
            $tmpAmount4 = $tmpCashC->sum('cash * currency_rate');
            if ($tmpAmount4 >= 0) {
                $tmpLbl4 = 'label-success';
            } else {
                $tmpLbl4 = 'label-danger';
            }

            $this->template->headerText = array(
                0 => array($this->translator->translate('Včerejší_zůstatek:'), $tmpAmount3, $this->settings->cl_currencies->currency_name, -9999999, $tmpLbl3, 'rightsFor' => 'report'),
                1 => array($this->translator->translate('Dnes_přijato:'), $tmpAmount, $this->settings->cl_currencies->currency_name, -9999999, $tmpLbl, 'rightsFor' => 'report'),
                2 => array($this->translator->translate('Dnes_vydáno:'), $tmpAmount2, $this->settings->cl_currencies->currency_name, -9999999, $tmpLbl2, 'rightsFor' => 'report'),
                3 => array($this->translator->translate('Dnešní_zůstatek:'), $tmpAmount4, $this->settings->cl_currencies->currency_name, -9999999, $tmpLbl4, 'rightsFor' => 'report'));
        }

    }


    protected function createComponentFiles()
    {
        if ($this->getUser()->isLoggedIn()) {
            $user_id = $this->user->getId();
            $cl_company_id = $this->settings->id;
        }
        // $translator = clone $this->translator->setPrefix([]);
        return new Controls\FilesControl($this->translator,
            $this->FilesManager, $this->UserManager, $this->id, 'cl_cash_id', NULL, $cl_company_id, $user_id,
            $this->CompaniesManager, $this->ArraysManager);
    }


    public function renderEdit($id, $copy, $modal)
    {
        parent::renderEdit($id, $copy, $modal);
        $this->template->customUrl = array('pricelist2' => $this->link('RedrawPriceList2!')
        );
    }


    protected function createComponentEdit($name)
    {
        $form = new Form($this, $name);
        //$form->setMethod('POST');
        $form->addHidden('id', NULL);
        $form->addHidden('cl_invoice_types_id', NULL);
        $form->addText('cash_number', $this->translator->translate('Číslo_dokladu'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Číslo_dokladu'));
        $form->addText('title', $this->translator->translate('Popis'), 20, 50)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Popis'));
        $form->addText('cash', $this->translator->translate('Částka'), 20, 50)
            ->setHtmlAttribute('class', 'form-control input-sm number')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Částka'));
        $form->addText('inv_date', $this->translator->translate('Datum'), 20, 20)
            ->setHtmlAttribute('class', 'form-control input-sm datetimepicker')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum'));

        $arrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addSelect('cl_center_id', $this->translator->translate("Středisko"), $arrCenter)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_středisko'))
            ->setPrompt($this->translator->translate('Zvolte_středisko'));

        if ($tmpInvoice = $this->DataManager->find($this->id)) {
            if (isset($tmpInvoice['cl_partners_book_id'])) {
                $tmpPartnersBookId = $tmpInvoice->cl_partners_book_id;
            } else {
                $tmpPartnersBookId = 0;
            }

        } else {
            $tmpPartnersBookId = 0;
        }

        $arrPartners = $this->PartnersManager->findAll()->where('id = ?', $tmpPartnersBookId)->fetchPairs('id', 'company');

        $form->addSelect('cl_partners_book_id', $this->translator->translate("Odběratel_/_dodavatel"), $arrPartners)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_partnera'))
            ->setHtmlAttribute('data-url-update-partner-in-form', $this->link('updatePartnerInForm!'))
            ->setHtmlAttribute('data-urlajax', $this->link('getPartners!'))
            ->setPrompt($this->translator->translate('Zvolte_partnera'));

        $arrCurrencies = $this->CurrenciesManager->findAll()->order('currency_name')->fetchPairs('id', 'currency_name');
        $form->addSelect('cl_currencies_id', $this->translator->translate("Měna"), $arrCurrencies)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte'))
            ->setHtmlAttribute('class', 'form-control chzn-select input-sm')
            ->setHtmlAttribute('data-urlajax', $this->link('GetCurrencyRate!'))
            ->setHtmlAttribute('data-urlrecalc', $this->link('makeRecalc!'))
            ->setPrompt($this->translator->translate('Zvolte_měnu'));

        $form->addText('currency_rate', $this->translator->translate('Kurz'), 7, 7)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('data-urlrecalc', $this->link('makeRecalc!'))
            ->setHtmlAttribute('placeholder', 'Kurz');
        //$arrUsers = $this->UserManager->getAll()->fetchPairs('id','name');

        //$arrUsers = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->fetchPairs('id','name');
        $arrUsers = array();
        $arrUsers['Aktivní'] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 0')->fetchPairs('id', 'name');
        $arrUsers['Neaktivní'] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 1')->fetchPairs('id', 'name');
        $form->addSelect('cl_users_id', $this->translator->translate("Přijal_/_Vydal"), $arrUsers)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('Zvolte_osobu'))
            ->setPrompt($this->translator->translate('Zvolte_osobu'));

        $form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');

        $form->addSubmit('send_fin', $this->translator->translate('Odeslat'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('save_pdf', 'PDF')->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('back', $this->translator->translate('Zpět'))
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBack');
        //	    ->onClick[] = callback($this, 'stepSubmit');

        $form->onSuccess[] = array($this, 'SubmitEditSubmitted');
        $form->onValidate[] = array($this, 'FormValidate');
        return $form;
    }

    public function FormValidate(Form $form)
    {
        $data = $form->values;
        $data = $this->updatePartnerId($data);
        //$this->redrawControl('content');
        // if ($data['cl_partners_book_id'] == NULL || $data['cl_partners_book_id'] == 0)
        // {
        //$form->addError($this->translator->translate('Partner musí být vybrán'));
        // }
        $this->redrawControl('content');
    }

    public function stepBack()
    {
        $this->redirect('default');
    }

    public function SubmitEditSubmitted(Form $form)
    {
        $data = $form->values;
        //later there must be another condition for user rights, admin can edit everytime
        if ($form['send']->isSubmittedBy() || $form['send_fin']->isSubmittedBy() || $form['save_pdf']->isSubmittedBy() || $form['store_out']->isSubmittedBy()) {
            $data = $this->removeFormat($data);
            //$data['inv_date'] = date('Y-m-d H:i:s',strtotime($data['inv_date']));

            //24.05.2019 - set minus value for cash out
            $tmpType = $this->InvoiceTypesManager->find($data['cl_invoice_types_id']);
            if ($tmpType && $tmpType->inv_type == 6) {
                $data['cash'] = -(abs($data['cash']));
            } else {
                unset($data['cl_invoice_types_id']);
            }


            //$myReadOnly = isset($this->DataManager->find($data['id'])->cl_status_id) && $this->DataManager->find($data['id'])->cl_status->s_fin == 1;
            $myReadOnly = false;
            if (!($myReadOnly)) {//if record is not marked as finished, we can save edited data
                if (!empty($data->id)) {
                    $this->DataManager->update($data, TRUE);

                    if ($tmpData = $this->DataManager->find($data['id'])) {
                        //unvalidate document for downloading
                        $tmpDocuments = array();
                        $tmpDocuments['id'] = $tmpData->cl_documents_id;
                        $tmpDocuments['valid'] = 0;
                        $newDocuments = $this->DocumentsManager->update($tmpDocuments);
                    }

                    $this->flashMessage($this->translator->translate('Změny_byly_uloženy.'), 'success');
                } else {
                    //$row=$this->DataManager->insert($data);
                    //$this->newId = $row->id;
                    //$this->flashMessage('Nový záznam byl uložen.', 'success');
                }
            } else {
                //$this->flashMessage('Změny nebyly uloženy.', 'success');
            }


            if ($form['send_fin']->isSubmittedBy()) {

            } elseif ($form['save_pdf']->isSubmittedBy()) {

            } else {
                //$this->redirect('default');
                $this->redrawControl('flash');
                $this->redrawControl('formedit');
                $this->redrawControl('timestamp');
                $this->redrawControl('items');
                //$this->redirect('default');
                $this->redrawControl('content');
            }

        } else {
            $this->flashMessage($this->translator->translate('Změny_nebyly_uloženy.'), 'warning');
            $this->redrawControl('flash');
            $this->redrawControl('formedit');
            $this->redrawControl('timestamp');
            $this->redrawControl('items');
            $this->redirect('default');

            //$this->redirect('default');
        }


    }

    public function emailSetStatus()
    {
        $this->setStatus($this->id, array('status_use' => 'cash',
            's_new' => 0,
            's_eml' => 1));
    }


    public function handleReport($index = 0)
    {
        $this->rptIndex = $index;
        $this->reportModalShow = TRUE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }


    public function handleShowPairedDocs()
    {
        //bdump('ted');
        $this->pairedDocsShow = TRUE;
        /*$this->showModal('pairedDocsModal');
        $this->redrawControl('pairedDocs');
        $this->redrawControl('contents');*/

        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('pairedDocs2');
        $this->showModal('pairedDocsModal');
    }


    protected function createComponentReportCashBook($name)
    {
        $form = new Form($this, $name);
        //$form->setMethod('POST');
        $form->addHidden('id', NULL);
        //$this->translator->setPrefix(['applicationModule.Cash']);
        $now = new \Nette\Utils\DateTime;
        $lcText1 = $this->translator->translate('Datum_od');
        $lcText2 = $this->translator->translate('Datum_do');

        $form->addText('date_from', $lcText1, 0, 16)
            ->setDefaultValue($now->format('d.m.Y'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_začátek'));
        //->setDefaultValue('01.'.$now->format('m.Y'))

        $tmpArrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addMultiSelect('cl_center_id', $this->translator->translate('Středisko'), $tmpArrCenter)
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder', $this->translator->translate('Vyberte_středisko'));

        $form->addText('date_to', $lcText2, 0, 16)
            ->setDefaultValue($now->format('d.m.Y'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_konec'));

        $form->addText('title_filter', $this->translator->translate('Text'), 20, 20)
            ->setHtmlAttribute('placeholder', $this->translator->translate('zadejte_hledaný_text'));

        $form->addSubmit('save_csv', $this->translator->translate('uložit_do_CSV'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');
        $form->addSubmit('save_pdf', $this->translator->translate('Tisk'))->setHtmlAttribute('class', 'btn btn-sm btn-primary');

        $form->addSubmit('back', $this->translator->translate('Návrat'))
            ->setHtmlAttribute('class', 'btn btn-sm btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBackReportCashBook');
        $form->onSuccess[] = array($this, 'SubmitReportCashBookSubmitted');
        //$form->getElementPrototype()->target = '_blank';
        return $form;
    }

    public function stepBackReportCashBook()
    {
        $this->rptIndex = 0;
        $this->reportModalShow = FALSE;
        $this->redrawControl('baselistArea');
        $this->redrawControl('reportModal');
        $this->redrawControl('reportHandler');
    }

    public function SubmitReportCashBookSubmitted(Form $form)
    {
        $data = $form->values;
        //dump(count($data['cl_partners_book']));
        //die;
        if ($form['save_pdf']->isSubmittedBy() || $form['save_csv']->isSubmittedBy()) {
            if ($data['date_to'] == "")
                $data['date_to'] = NULL;
            else {
                //$tmpDate = new \Nette\Utils\DateTime;
                //$tmpDate = $tmpDate->setTimestamp(strtotime($data['date_to']));
                //date('Y-m-d H:i:s',strtotime($data['date_to']));
                $data['date_to'] = date('Y-m-d H:i:s', strtotime($data['date_to']) + 86400 - 10);
            }

            if ($data['date_from'] == "")
                $data['date_from'] = NULL;
            else
                $data['date_from'] = date('Y-m-d H:i:s', strtotime($data['date_from']));

            $dataReport = $this->DataManager->findAll()->
            where('inv_date >= ? AND inv_date <= ?', $data['date_from'], $data['date_to'])->
            order('inv_date ASC, cash_number ASC');

            if ($data['title_filter'] != '') {
                $dataReport = $dataReport->where('title LIKE ?', '%' . $data['title_filter'] . '%');
            }

            $tmpStartAmount = $this->DataManager->findAll()->where('inv_date < ?', $data['date_from']);
            if (count($data['cl_center_id']) > 0) {
                $dataReport = $dataReport->where(array('cl_cash.cl_center_id' => $data['cl_center_id']));
                $tmpStartAmount = $tmpStartAmount->where(array('cl_cash.cl_center_id' => $data['cl_center_id']));
            }

            if ($form['save_pdf']->isSubmittedBy()) {

                $dataOther = array();
                $dataSettings = $data;
                $dataOther['dataSettingsCenter'] = $this->CenterManager->findAll()->where(array('id' => $data['cl_center_id']))->order('name');
                $dataOther['startAmount'] = $tmpStartAmount->sum('cash * currency_rate');
                $template = $this->createMyTemplateWS($dataReport, __DIR__ . '/../templates/Cash/ReportCashBook.latte', $dataOther, $dataSettings, 'Pokladní kniha');
                $tmpDate1 = new \DateTime($data['date_from']);
                $tmpDate2 = new \DateTime($data['date_to']);
                $this->pdfCreate($template, $this->translator->translate('Pokladní_kniha_za_období ') . date_format($tmpDate1, 'd.m.Y') . ' - ' . date_format($tmpDate2, 'd.m.Y'));


            } elseif ($form['save_csv']->isSubmittedBy()) {
                if ($dataReport->count() > 0) {
                    $filename = $this->translator->translate("Pokladní_kniha");
                    $arrData = [];
                    $i = 0;
                    foreach ($dataReport as $key => $one) {
                        if ($i == 0) {
                            $arrData[0] = $one->toArray();
                            $arrData[0]['cash_number'] = '';
                            $arrData[0]['cl_number_series_id'] = NULL;
                            $arrData[0]['inv_date'] = NULL;
                            $arrData[0]['cl_invoice_id'] = NULL;
                            $arrData[0]['cl_delivery_note_id'] = NULL;
                            $arrData[0]['cl_sale_id'] = NULL;
                            $arrData[0]['cl_invoice_arrived_id'] = NULL;
                            $arrData[0]['cl_documents_id'] = NULL;
                            $arrData[0]['cl_status_id'] = NULL;
                            $arrData[0]['cl_transport_id'] = NULL;
                            $arrData[0]['s_eml'] = 0;
                            $arrData[0]['title'] = 'Počáteční zůstatek';
                            $arrData[0]['cash'] = $tmpStartAmount->sum('cash * currency_rate');
                        }
                        $arrData[$key] = $one->toArray();
                        $i++;
                    }
                    $this->sendResponse(new \CsvResponse\NCsvResponse($arrData, $filename . "-" . date('Ymd-Hi') . ".csv", true));
                } else {
                    $this->flashMessage($this->translator->translate('Data_nebyly_do_CSV_uloženy._Zadaným_podmínkám_nevyhověl_žádný_záznam.'), 'danger');
                    $this->redirect('default');
                }
            }

        }
    }


    public function handleMakeRecalc()
    {

    }

    public function handleSavePDF($id, $latteIndex = NULL, $arrData = array(), $noDownload = FALSE, $noPreview = FALSE)
    {
        $tmpData = $this->preparePDFData($id);
        return parent::handleSavePDF($id, $tmpData['latteIndex'], $tmpData, $noDownload, $noPreview);
    }

    public function handleDownloadPDF($id, $latteIndex = NULL, $arrData = array(), $noDownload = FALSE, $noPreview = FALSE)
    {
        $tmpData = $this->preparePDFData($id);
        return parent::handleSavePDF($id, $tmpData['latteIndex'], $tmpData, $noDownload, TRUE);
    }

    public function handleSendDoc($id, $latteIndex = NULL, $arrData = [], $recepients = [], $emailingTextIndex = NULL )
    {
        $tmpData = $this->preparePDFData($id);
        parent::handleSendDoc($id, $tmpData['latteIndex'], $tmpData, $emailingTextIndex);
    }


    public function preparePDFData($id)
    {
        $data = $this->DataManager->find($id);
        if ($data->cl_invoice_types->inv_type == 5 || $data->cl_invoice_types->inv_type == 6) //příjmový/výdajový doklad
        {
            //$tmpTemplateFile =  __DIR__ . '/../templates/Sale/saledoc.latte';
            $latteIndex = 1;
        } else //jiný doklad
        {
            //$tmpTemplateFile =  __DIR__ . '/../templates/Sale/correction.latte';
            $latteIndex = 2;
        }

        $arrData = ['settings' => $this->settings,
            'latteIndex' => $latteIndex];

        return $arrData;
    }

    //javascript call when changing cl_partners_book_id
    public function handleRedrawPriceList2($cl_partners_book_id)
    {
        //dump($cl_partners_book_id);
        $arrUpdate = array();
        $arrUpdate['id'] = $this->id;
        $arrUpdate['cl_partners_book_id'] = ($cl_partners_book_id == '' ? NULL : $cl_partners_book_id);

        //dump($arrUpdate);
        //die;
        $this->DataManager->update($arrUpdate);
        $this->sendJson(array());
        //$this['invoicelistgrid']->redrawControl('pricelist2');
    }


    public function handleNewIn()
    {
        $tmpType = $this->InvoiceTypesManager->findAll()->where('inv_type = ? AND default_type = 1', 5)->fetch();
        if ($tmpType) {
            $this->defValues['cl_invoice_types_id'] = $tmpType->id;
        }
        //07.07.2019 - select branch if there are defined
        //$tmpBranchId = $this->CompanyBranchUsersManager->getBranchForUser($this->getUser()->id);
        //$tmpBranchId = $this->getUser()->cl_company_branch_id;
        $tmpBranchId = $this->user->getIdentity()->cl_company_branch_id;
        $tmpBranch = $this->CompanyBranchManager->find($tmpBranchId);
        if ($tmpBranch) {
            $this->numberSeries['cl_number_series_id'] = $tmpBranch->cl_number_series_id_cashin;
        }
        // bdump($this->numberSeries);
        $this->numberSeries['use'] = 'cash_in';
        parent::handleNew('cash_in', []);
    }

    public function handleNewOut()
    {
        $tmpType = $this->InvoiceTypesManager->findAll()->where('inv_type = ? AND default_type = 1', 6)->fetch();
        if ($tmpType) {
            $this->defValues['cl_invoice_types_id'] = $tmpType->id;
        }
        //07.07.2019 - select branch if there are defined
        //$tmpBranchId = $this->CompanyBranchUsersManager->getBranchForUser($this->getUser()->id);
        //$tmpBranchId = $this->getUser()->cl_company_branch_id;
        $tmpBranchId = $this->user->getIdentity()->cl_company_branch_id;
        $tmpBranch = $this->CompanyBranchManager->find($tmpBranchId);
        if ($tmpBranch) {
            $this->numberSeries['cl_number_series_id'] = $tmpBranch->cl_number_series_id_cashout;
        }
        $this->numberSeries['use'] = 'cash_out';

        parent::handleNew('cash_out', []);

    }

    //aditional control before delete from baseList
    public function beforeDeleteBaseList($id)
    {
        //07.01.2023 - delete paired payments from cl_invoice_arrived_payments, cl_invoice_payments, cl_invoice_advance_payments
        $tmpDataCash = $this->DataManager->find($id);
        if (!is_null($tmpDataCash['cl_invoice_id'])) {
            $tmpData = $this->InvoicePaymentsManager->findAll()->where('cl_invoice_id = ?  AND cl_cash_id = ?', $tmpDataCash['cl_invoice_id'], $id)->fetch();
            if ($tmpData) {
                /*08.01.2022 - update sum of used amount for selected tax cl_invoice_advance*/
                $this->InvoiceManager->updateAdvancePriceE2Used($tmpData['used_cl_invoice_id']);

                $tmpData->delete();
                $this->InvoiceManager->updateInvoiceSum($tmpDataCash['cl_invoice_id']);
            }
        }

        if (!is_null($tmpDataCash['cl_invoice_advance_id'])) {
            $tmpData = $this->InvoiceAdvancePaymentsManager->findAll()->where('cl_invoice_advance_id = ?  AND cl_cash_id = ?', $tmpDataCash['cl_invoice_advance_id'], $id)->fetch();
            if ($tmpData) {
                $tmpData->delete();
                $this->InvoiceAdvanceManager->paymentUpdate($tmpDataCash['cl_invoice_advance_id']);
            }
        }

        if (!is_null($tmpDataCash['cl_invoice_arrived_id'])) {
            $tmpData = $this->InvoiceArrivedPaymentsManager->findAll()->where('cl_invoice_arrived_id = ?  AND cl_cash_id = ?', $tmpDataCash['cl_invoice_arrived_id'], $id)->fetch();
            if ($tmpData) {
                $tmpData->delete();
                $this->InvoiceArrivedManager->paymentUpdate($tmpDataCash['cl_invoice_arrived_id']);
            }
        }


        return TRUE;
    }


}
