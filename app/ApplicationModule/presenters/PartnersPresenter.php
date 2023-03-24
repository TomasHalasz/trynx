<?php

namespace App\ApplicationModule\Presenters;

use App\Controls;
use App\Model\Passwords;
use App\Model\UserManager;
use App\Model\UsersManager;
use halasz\Ares\IdentificationNumberNotFoundException;
use Nette\Application\UI\Form,
    Nette\Image;
use Nette\Mail\Message,
    Nette\Utils\Strings;
use Nette\Mail\SendmailMailer;
use DragonBe\Vies\Vies;
use DragonBe\Vies\ViesException;
use DragonBe\Vies\ViesServiceException;

use Nette\Utils\DateTime;
use SoapClient;
use Tracy\Debugger;

class PartnersPresenter extends \App\Presenters\BaseListPresenter
{

    private $showPartnerDocs = FALSE;

    const
        DEFAULT_STATE = 'Czech Republic';


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
     * @var \App\Model\PartnersManager
     */
    public $DataManager;

    /**
     * @inject
     * @var \App\Model\InvoiceManager
     */
    public $InvoiceManager;


    /**
     * @inject
     * @var \App\Model\CountriesManager
     */
    public $CountriesManager;

    /**
     * @inject
     * @var \App\Model\PaymentTypesManager
     */
    public $PaymentTypesManager;

    /**
     * @inject
     * @var \App\Model\RegionsManager
     */
    public $RegionsManager;

    /**
     * @inject
     * @var \App\Model\RegCompaniesManager
     */
    public $RegCompaniesManager;

    /**
     * @inject
     * @var \App\Model\CompaniesAccessManager
     */
    public $CompaniesAccessManager;

    /**
     * @inject
     * @var \App\Model\PartnersCategoryManager
     */
    public $PartnersCategoryManager;

    /**
     * @inject
     * @var \App\Model\PartnersCoopManager
     */
    public $PartnersCoopManager;

    /**
     * @inject
     * @var \App\Model\PartnersBookWorkersManager
     */
    public $PartnersBookWorkersManager;

    /**
     * @inject
     * @var \App\Model\UserManager
     */
    public $userManager;

    /**
     * @inject
     * @var \App\Model\UsersManager
     */
    public $UsersManager;


    /**
     * @inject
     * @var \App\Model\PriceListPartnerManager
     */
    public $PriceListPartnerManager;

    /**
     * @inject
     * @var \App\Model\PriceListManager
     */
    public $PriceListManager;

    /**
     * @inject
     * @var \App\Model\CurrenciesManager
     */
    public $CurrenciesManager;

    /**
     * @inject
     * @var \App\Model\StorageManager
     */
    public $StorageManager;

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
     * @var \App\Model\CalendarPlaneManager
     */
    public $CalendarPlaneManager;

    /**
     * @inject
     * @var \App\Model\ArraysManager
     */
    public $ArraysManager;

    /**
     * @inject
     * @var \App\Model\PricesGroupsManager
     */
    public $PricesGroupsManager;

    /**
     * @inject
     * @var \App\Model\PartnersBranchManager
     */
    public $PartnersBranchManager;

    /**
     * @inject
     * @var \App\Model\PartnersGroupsManager
     */
    public $PartnersGroupsManager;

    /**
     * @inject
     * @var \App\Model\PriceListGroupManager
     */
    public $PriceListGroupManager;

    /**
     * @inject
     * @var \App\Model\PriceListPartnerGroupManager
     */
    public $PriceListPartnerGroupManager;

    /**
     * @inject
     * @var \App\Model\PartnersAccountManager
     */
    public $PartnersAccountManager;


    protected function createComponentCalendarPlaneListGrid()
    {
        //$this->translator->setPrefix(['applicationModule.partners']);
        $arrData = ['event_title' => [$this->translator->translate('Název_události'), 'format' => 'text', 'size' => 20],
            'start_date' => [$this->translator->translate('Začátek'), 'format' => 'datetime2', 'size' => 10],
            'end_date' => [$this->translator->translate('Konec'), 'format' => 'datetime2', 'size' => 10],
            'repeat_days' => [$this->translator->translate('Opakovat_po_dnech'), 'format' => "integer", 'size' => 8],
            'repeat_weeks' => [$this->translator->translate('Opakovat_po_týdnech'), 'format' => "integer", 'size' => 8],
            'repeat_months' => [$this->translator->translate('Opakovat_po_měsících'), 'format' => "integer", 'size' => 8],
            'type' => [$this->translator->translate('Typ_události'), 'format' => 'text', 'size' => 10,
                'values' => $this->ArraysManager->getCalendarPlaneType()],
            'email_enabled' => [$this->translator->translate('Upozornit_emailem'), 'format' => 'boolean', 'size' => 10]
        ];
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->CalendarPlaneManager, //data manager
            $arrData, //data columns
            [], //row conditions
            $this->id, //parent Id
            [], //default data
            $this->DataManager, //parent data manager
            NULL, //pricelist manager
            NULL, //pricelist manager
            TRUE, //enable add empty row
            [] //custom links
        );
        $control->onChange[] = function () {
            $this->updateSum();

        };
        return $control;

    }


    protected function createComponentPartnerPriceListGrid()
    {
        //$this->translator->setPrefix(['applicationModule.partners']);
        $arrData = ['cl_pricelist.identification' => [$this->translator->translate('Kód'), 'format' => 'text', 'size' => 15, 'readonly' => TRUE],
            'cl_pricelist.item_label' => [$this->translator->translate('Název'), 'format' => 'text', 'size' => 30, 'readonly' => TRUE],
            'price_change' => [$this->translator->translate('Sleva_z_prodejní_ceny_v_%'), 'format' => "number", 'size' => 12],
            'fix_price' => [$this->translator->translate('Pevná_cena'), 'format' => "boolean", 'size' => 8],
            'price' => [$this->translator->translate('Cena_bez_DPH'), 'format' => "number", 'size' => 12],
            'vat' => [$this->translator->translate('DPH_%'), 'format' => 'number', 'readonly' => TRUE, 'size' => 8],
            'price_vat' => [$this->translator->translate('Cena_s_DPH'), 'format' => "number", 'size' => 12],
            'cl_currencies.currency_name' => [$this->translator->translate('Měna'), 'format' => 'text', 'size' => 10,
            'values' => [$this->CurrenciesManager->findAll()
                        ->fetchPairs('id', 'currency_name')]]
        ];
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->PriceListPartnerManager, //data manager
            $arrData, //data columns
            [], //row conditions
            $this->id, //parent Id
            ['units' => $this->settings->def_mj], //default data
            $this->DataManager, //parent data manager
            $this->PriceListManager, //pricelist manager
            FALSE, //pricelistpartner manager
            FALSE, //enable add empty row
            [], //customlinks
            FALSE, //movable row
            NULL, //ordercolumn
            FALSE, //selectmode
            [], //quicksearch
            "", //fontsize
            'cl_pricelist_partner.cl_partners_book_id', //parentcolumnname
            FALSE //pricelistbottom
        );
        $control->setPaginatorOff();
        $control->setEnableSearch('cl_pricelist.identification LIKE ? OR cl_pricelist.item_label LIKE ? OR cl_pricelist.ean_code LIKE ?');
        $control->setPricelistEnabled(false);
        $control->onChange[] = function () {
            $this->updateSum();

        };
        return $control;

    }


    protected function createComponentPricelistPartnerGroupGrid()
    {
        //$this->translator->setPrefix(['applicationModule.partners']);
        $arrData = [
            'cl_pricelist_group.name' => [$this->translator->translate('Skupina'), 'format' => 'select', 'size' => 10,
                'values' => $this->PriceListGroupManager->findAll()->order('name ASC')
                    ->fetchPairs('id', 'name')],
            'price_surcharge' => [$this->translator->translate('Přirážka_k_nákupní_ceně'), 'format' => "number", 'size' => 12],
            'price_change' => [$this->translator->translate('Sleva_z_prodejní_ceny_v_%'), 'format' => "number", 'size' => 12],
            'valid_date' => [$this->translator->translate('Platnost_do'), 'format' => 'date', 'size' => 8]];
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->PriceListPartnerGroupManager, //data manager
            $arrData, //data columns
            [], //row conditions
            $this->id, //parent Id
            [], //default data
            $this->DataManager, //parent data manager
            [], //pricelist manager
            [],
            TRUE, //enable add empty row
            [] //custom links
        );
        $control->setPaginatorOff();
        $control->setPricelistEnabled(false);
        $control->setEnableAddEmptyRow(true);
        $control->onChange[] = function () {
            $this->updateSum();

        };
        return $control;

    }


    protected function createComponentWorkersListGrid()
    {
        //$this->translator->setPrefix(['applicationModule.partners']);
        $arrData = [
            'worker_name' => [$this->translator->translate('Jméno_a_příjmení'), 'format' => "text", 'size' => 20],
            'worker_position' => [$this->translator->translate('Pozice'), 'format' => 'text', 'size' => 15],
            'worker_phone' => [$this->translator->translate('Telefon'), 'format' => "text", 'size' => 15],
            'worker_email' => [$this->translator->translate('Email'), 'format' => 'email', 'size' => 15],
            'worker_skype' => [$this->translator->translate('Skype'), 'format' => 'text', 'size' => 15],
            'worker_other' => [$this->translator->translate('Jiný_kontakt'), 'format' => "text", 'size' => 15],
            'cl_partners_branch.b_name' => [$this->translator->translate('Pobočka'), 'format' => 'select', 'size' => 10,
                'values' => $this->PartnersBranchManager->findAll()->
                where('cl_partners_book_id = ?', $this->id)->
                order('b_name ASC')
                    ->fetchPairs('id', 'b_name')],
            'b2b_enabled' => [$this->translator->translate('B2B'), 'format' => 'boolean', 'size' => 7],
            'password2' => [$this->translator->translate('B2B_heslo'), 'format' => "text", 'size' => 15],
            'b2b_master' => [$this->translator->translate('Správce_firmy'), 'format' => 'boolean', 'size' => 7],
            'b2b_cash' => [$this->translator->translate('B2B_-_hotovost'), 'format' => 'boolean', 'size' => 7],
            'b2b_transfer' => [$this->translator->translate('B2B_-_převod'), 'format' => 'boolean', 'size' => 7],
            'use_cl_invoice' => [$this->translator->translate('Faktury_vydané'), 'format' => 'boolean', 'size' => 7],
            'use_cl_invoice_arrived' => [$this->translator->translate('Faktury_přijaté'), 'format' => 'boolean', 'size' => 7],
            'use_cl_offer' => [$this->translator->translate('Nabídky'), 'format' => 'boolean', 'size' => 7],
            'use_cl_commission' => [$this->translator->translate('Zakázky'), 'format' => 'boolean', 'size' => 7],
            'use_cl_order' => [$this->translator->translate('Objednávky'), 'format' => 'boolean', 'size' => 7],
            'use_cl_delivery_note' => [$this->translator->translate('Dodací_listy'), 'format' => 'boolean', 'size' => 7],
            'use_cl_partners_event' => [$this->translator->translate('Helpdesk'), 'format' => 'boolean', 'size' => 7],
            'use_cl_delivery' => [$this->translator->translate('Doprava'), 'format' => 'boolean', 'size' => 7],
            'use_cl_store_docs' => [$this->translator->translate('Sklad'), 'format' => 'boolean', 'size' => 7],
            'use_cl_sale' => [$this->translator->translate('Prodejky'), 'format' => 'boolean', 'size' => 7],
            'use_cl_cash' => [$this->translator->translate('Pokladna'), 'format' => 'boolean', 'size' => 7],
            'description_txt' => [$this->translator->translate('Poznámka'), 'format' => "textarea", 'size' => 70, 'rows' => 3, 'newline' => TRUE],

        ];
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->PartnersBookWorkersManager, //data manager
            $arrData, //data columns
            [], //row conditions
            $this->id, //parent Id
            ['use_cl_invoice' => 1, 'use_cl_invoice_advance' => 1, 'use_cl_invoice_arrived' => 1, 'use_cl_offer' => 1, 'use_cl_commission' => 1, 'use_cl_order' => 1, 'use_cl_delivery_note' => 1,
                'use_cl_partners_event' => 1, 'use_cl_delivery' => 1, 'use_cl_store_docs' => 1, 'use_cl_sale' => 1, 'use_cl_cash' => 1], //default data
            $this->DataManager, //parent data manager
            NULL, //pricelist manager
            NULL, //pricelist partner manager
            TRUE, //enable add empty row
            [] //custom links
        );
        $control->setPaginatorOff();

        $control->onChange[] = function () {
            $this->updateSum();

        };
        return $control;

    }


    protected function createComponentPartnersBranchGrid()
    {
        //$this->translator->setPrefix(['applicationModule.partners']);
        $arrData = ['use_as_main' => [$this->translator->translate('Fakturační_adresa'), 'format' => 'boolean', 'size' => 7],
            'b_name' => [$this->translator->translate('Název'), 'format' => "text", 'size' => 20],
            'b_street' => [$this->translator->translate('Ulice'), 'format' => 'text', 'size' => 15],
            'b_city' => [$this->translator->translate('Město'), 'format' => "text", 'size' => 15],
            'b_zip' => [$this->translator->translate('PSČ'), 'format' => 'text', 'size' => 15],
            'b_ico' => [$this->translator->translate('IČO'), 'format' => 'text', 'size' => 15],
            'b_dic' => [$this->translator->translate('DIČ'), 'format' => 'text', 'size' => 15],
            'cl_countries.name' => [$this->translator->translate('Stát'), 'format' => 'text', 'size' => 15,
                'values' => $this->CountriesManager->findAllTotal()->order('name')->fetchPairs('id', 'name')],
            'b_person' => [$this->translator->translate('Osoba'), 'format' => "text", 'size' => 15],
            'b_email' => [$this->translator->translate('Email'), 'format' => 'text', 'size' => 10],
            'b_phone' => [$this->translator->translate('Telefon'), 'format' => 'text', 'size' => 10],
            'use_cl_invoice' => [$this->translator->translate('Faktury_vydané'), 'format' => 'boolean', 'size' => 7],
            'use_cl_offer' => [$this->translator->translate('Nabídky'), 'format' => 'boolean', 'size' => 7],
            'use_cl_commission' => [$this->translator->translate('Zakázky'), 'format' => 'boolean', 'size' => 7],
            'use_cl_delivery' => [$this->translator->translate('Dodací_listy'), 'format' => 'boolean', 'size' => 7],
            'use_cl_partners_event' => [$this->translator->translate('Helpdesk'), 'format' => 'boolean', 'size' => 7]
        ];
        $tmpData = $this->DataManager->find($this->id);
        $arrDefData = ['use_as_main' => 1, 'b_name' => $tmpData['company'], 'b_street' => $tmpData['street'],
            'b_city' => $tmpData['city'], 'b_zip' => $tmpData['zip'], 'b_ico' => $tmpData['ico'],
            'b_dic' => $tmpData['dic'], 'cl_countries_id' => $tmpData['cl_countries_id']];
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->PartnersBranchManager, //data manager
            $arrData, //data columns
            [], //row conditions
            $this->id, //parent Id
            $arrDefData, //default data
            $this->DataManager, //parent data manager
            NULL, //pricelist manager
            NULL, //pricelist partner manager
            TRUE, //enable add empty row
            [] //custom links
        );

        $control->setPaginatorOff();
        $control->onChange[] = function () {
            $this->updateSum();

        };
        return $control;
    }

    protected function createComponentPartnersAccount()
    {
        $arrData = [
            'cl_currencies.currency_code' => [$this->translator->translate('Měna'), 'format' => 'select', 'size' => 10,
                    'values' => $this->CurrenciesManager->findAll()->order('currency_code ASC')
                    ->fetchPairs('id', 'currency_code')],
            'account_code' => [$this->translator->translate('Účet'), 'format' => "text", 'size' => 12],
            'bank_code' => [$this->translator->translate('Kód'), 'format' => "text", 'size' => 12],
            'iban_code' => [$this->translator->translate('IBAN'), 'format' => "text", 'size' => 12],
            'swift_code' => [$this->translator->translate('SWIFT'), 'format' => "text", 'size' => 12],
            'spec_symb' => [$this->translator->translate('spec_symb'), 'format' => "text", 'size' => 12],
            'date_from' => [$this->translator->translate('Datum_zveřejnění'), 'format' => "date", 'readonly' => true]];
        $control = new Controls\ListgridControl(
            $this->translator,
            $this->PartnersAccountManager, //data manager
            $arrData, //data columns
            [], //row conditions
            $this->id, //parent Id
            [], //default data
            $this->DataManager, //parent data manager
            [], //pricelist manager
            [],
            TRUE, //enable add empty row
            [] //custom links
        );
        $control->setPaginatorOff();
        $control->setPricelistEnabled(false);
        $control->setEnableAddEmptyRow(true);
        $control->onChange[] = function () {
            //$this->updateSum();

        };
        return $control;

    }


    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.partners']);

        $this->mainTableName = 'cl_partners_book';
        $this->dataColumns = [
            'active' => [$this->translator->translate('Aktivní'), 'size' => 3, 'format' => 'boolean'],
            'company' => $this->translator->translate('Název'),
            'cl_center.name' => $this->translator->translate('Středisko'),
            'street' => $this->translator->translate('Ulice'),
            'city' => $this->translator->translate('Město'),
            'zip' => $this->translator->translate('PSČ'),
            'cl_countries.name' => $this->translator->translate('Stát'),
            'supplier' => [$this->translator->translate('Dodavatel'), 'size' => 5, 'format' => 'boolean'],
            'customer' => [$this->translator->translate('Zákazník'), 'size' => 5, 'format' => 'boolean'],
            'producer' => [$this->translator->translate('Výrobce'), 'size' => 5, 'format' => 'boolean'],
            'ico' => $this->translator->translate('IČ'),
            'dic' => $this->translator->translate('DIČ'),
            'partner_code' => [$this->translator->translate('Kód_partnera'), 'size' => 16, 'format' => 'text'],
            'discount' => [$this->translator->translate('Sleva'), 'size' => 5, 'format' => 'number'],
            'person' => $this->translator->translate('Osoba'),
            'email' => $this->translator->translate('E-mail'),
            'phone' => $this->translator->translate('Telefon'),
            'web' => $this->translator->translate('Web'), 'contract' => [$this->translator->translate('Smlouva'), 'format' => 'boolean'],
            'pricelist_partner' => [$this->translator->translate('Vlastní_ceník'), 'format' => 'boolean'],
            'coop_enable' => [$this->translator->translate('Partner'), 'format' => 'boolean'],
            'same_address' => [$this->translator->translate('Shodná_doručovací_adr.'), 'format' => 'boolean'],
            'company2' => $this->translator->translate('Název_2'),
            'street2' => $this->translator->translate('Ulice_2'),
            'city2' => $this->translator->translate('Město_2'),
            'zip2' => $this->translator->translate('PSČ_2'),
            'country2' => $this->translator->translate('Stát_2'),
            'cl_currencies.currency_code' => $this->translator->translate('Výchozí_měna'),
            'cl_partners_groups.name' => $this->translator->translate('Skupina'),
            'cl_regions.region_name' => $this->translator->translate('Region'),
            'cl_partners_category.category_name' => $this->translator->translate('Kategorie'),
            'cl_users.name' => $this->translator->translate('Obchodník'),
            'cl_storage.name' => $this->translator->translate('Výchozí_sklad'),
            'cl_prices_groups.name' => $this->translator->translate('Cenová_skupina'),
            'min_order' => $this->translator->translate('Minimální_objednávka'),
            'subject' => $this->translator->translate('Obor'), 'size' => $this->translator->translate('Velikost'), 'potential' => $this->translator->translate('Potenciál'),
            'comment' => [$this->translator->translate('Poznámka'), 'format' => 'text'],
            'created' => [$this->translator->translate('Vytvořeno'), 'format' => 'datetime'], 'create_by' => $this->translator->translate('Vytvořil'), 'changed' => [$this->translator->translate('Změněno'), 'format' => 'datetime'], 'change_by' => $this->translator->translate('Změnil')];
        //$this->formatColumns = array('cdate' => "date",'created' => "datetime",'changed' => "datetime");
        //'cdate' => array($this->translator->translate('Poslední_událost'), 'format' => 'datetime'),
        //$this->agregateColumns = 'cl_partners_book.*,MAX(:cl_partners_event.date) AS cdate';
        //'MAX(cl_partners_event.date)' => 'Poslední událost';
        $this->FilterC = 'UPPER(company) LIKE ? OR UPPER(street) LIKE ? OR UPPER(city) LIKE ? OR UPPER(:cl_partners_event.tags) LIKE ? OR ico LIKE ?';
        $this->filterColumns = ['cl_partners_book.company' => 'autocomplete',
            'cl_center.name' => 'autocomplete',
            'cl_partners_book.ico' => 'autocomplete',
            'cl_partners_book.dic' => 'autocomplete',
            'cl_partners_book.person' => 'autocomplete',
            'cl_partners_book.email' => 'autocomplete',
            'cl_partners_book.city' => 'autocomplete',
            'cl_regions.region_name' => 'autocomplete',
            'cl_partners_book.zip' => 'autocomplete',
            'cl_users.name' => 'autocomplete'];

        $this->userFilterEnabled = TRUE;
        $this->userFilter = ['company', 'city', 'person', 'email', 'partner_code', 'ico'];

        $this->DefSort = 'company';
        //$this->showChildLink = 'PartnersEvent:default';
        $this->toolbar = [1 => ['url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nový_záznam'), 'class' => 'btn btn-primary'],
            2 => ['group' => [
                0 => ['url' => $this->link('notActiveSet!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nastavit_neaktivní'), 'icon' => 'iconfa-screenshot', 'data' => ['data-ajax="true"', 'data-history="false"'], 'class' => 'ajax']],
                'group_settings' => ['group_label' => $this->translator->translate('Nástroje'),
                    'group_class' => 'btn btn-primary dropdown-toggle btn-sm',
                    'group_title' => $this->translator->translate('Nástroje'), 'group_icon' => 'iconfa-wrench']],
            3 => ['url' => $this->link('ImportData:', ['modal' => $this->modal, 'target' => $this->name]), 'rightsFor' => 'write', 'label' => 'Import', 'class' => 'btn btn-primary'],
        ];
        //date('Y-m-d H:i:s', strtotime($data['date_to'].' 23:59:59'))  date('Y-m-d H:i:s')
        //$testDate = new \Nette\Utils\DateTime;
        //$testDate = $testDate->modify('-30 day');
        //$this->conditionRows = array( 'cdate','<=',$testDate);
        $arrUsers = [];
        $arrUsers[$this->translator->translate('Aktivní')] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 0')->fetchPairs('id', 'name');
        $arrUsers[$this->translator->translate('Neaktivní')] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 1')->fetchPairs('id', 'name');
        $arrPricesGroups = $this->PricesGroupsManager->findAll()->order('name')->fetchPairs('id', 'name');
        $this->quickFilter = ['cl_center.name' => ['name' => $this->translator->translate('Zvolte_středisko'),
            'values' => $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name')],
            'cl_users.name' => ['name' => $this->translator->translate('Zvolte_obchodníka'),
                'values' => $arrUsers],
            'cl_prices_groups.name' => ['name' => $this->translator->translate('Zvolte_cenovou_skupinu'),
                'values' => $arrPricesGroups]
        ];

        /*predefined filters*/
        $this->pdFilter = [0 => ['url' => $this->link('pdFilter!', ['index' => 0, 'pdFilterIndex' => 0]),
            'filter' => '(cl_partners_book.active = 1)',
            'sum' => [],
            'rightsFor' => 'read',
            'label' => $this->translator->translate('Aktivní_partneři'),
            'title' => $this->translator->translate('Všichni_aktivní_partneři'),
            'data' => ['data-ajax="true"', 'data-history="true"'],
            'class' => 'ajax', 'icon' => 'iconfa-filter'],
            1 => ['url' => $this->link('pdFilter!', ['index' => 1, 'pdFilterIndex' => 1]),
                'filter' => '(cl_partners_book.active = 0)',
                'sum' => [],
                'rightsFor' => 'read',
                'label' => $this->translator->translate('Neaktivní_partneři'),
                'title' => $this->translator->translate('Všechni_neaktivní_partneři'),
                'data' => ['data-ajax="true"', 'data-history="true"'],
                'class' => 'ajax', 'icon' => 'iconfa-filter']
        ];

    }

    public function renderDefault($page_b = 1, $idParent, $filter, $sortKey, $sortOrder, $filterColumn, $filterValue, $modal, $cxs)
    {
        parent::renderDefault($page_b, $idParent, $filter, $sortKey, $sortOrder, $filterColumn, $filterValue, $modal, $cxs);
        //dump($this->conditionRows);
        //die;

    }

    protected function createComponentFiles()
    {
        if ($this->getUser()->isLoggedIn()) {
            $user_id = $this->user->getId();
            $cl_company_id = $this->settings->id;
        }
        return new Controls\FilesControl(
            $this->translator, $this->FilesManager, $this->UserManager, $this->id, 'cl_partners_book_main_id', NULL, $cl_company_id, $user_id,
            $this->CompaniesManager, $this->ArraysManager);
    }


    public function renderEdit($id, $copy, $modal, $dataGiven = array())
    {
        parent::renderEdit($id, $copy, $modal);
        if ($defData = $this->DataManager->findOneBy(array('id' => $id))) {
            $this['edit']->setValues($defData);
            if (count($dataGiven) > 0) {
                $this['edit']->setValues($dataGiven);
            }
            $this['editSettings']->setValues($defData);
            $this['headersFooters']->setValues($defData);
            if ($this->user->isInRole('user')) {
                foreach ($this['edit']->getControls() as $control) {
                    //if ($control->name == 'ico')
                    //$control->controlPrototype->readonly = 'readonly';
                }
            }
            if (isset($defData['ico']) && $defData->ico != '') {
                $this->template->coop_cl_company_exist = $this->CompaniesManager->findAllTotal()->where(array('ico' => $defData->ico))->fetch();
                if (isset($this['coopRequest']) && isset($this->template->coop_cl_company_exist->id))
                    $this['coopRequest']->setValues(array('id_company' => $this->template->coop_cl_company_exist->id,
                        'child_cl_partners_book_id' => $defData->id));
            } else {
                $this->template->coop_cl_company_exist = FALSE;
            }
        } else {
            $this['edit']->setvalues(array('same_address' => 1, 'country' => self::DEFAULT_STATE, 'country2' => self::DEFAULT_STATE));
            $this->template->coop_cl_company_exist = FALSE;
        }
        //if ($copy)
        //	$this['edit']->setValues(array('id' => ''));

        $this->template->coop_cl_company = $this->CompaniesManager->findAllTotal()->where(array('id' => $defData->coop_cl_company_id))->fetch();

        $data = array();
        $dataUsr = $this->UserManager->getUserById($this->getUser()->id);
        $userSettings = json_decode($dataUsr->event_settings, TRUE);
        //	   dump($userSettings);
        //$userSettings->cl_partners_book_id = $id;
        $userSettings['cl_partners_book_id'] = $id;


        //	dump($userSettings);
        //	die;
        $this->UserManager->updateUser(array('id' => $dataUsr->id, 'event_settings' => json_encode($userSettings)));
        $this->template->tmpCategories = $this->PartnersCategoryManager->findAll();
        $this['categoriesTaxes']->setValues(array('id' => $id));
        $jsonVal = json_decode($defData['cl_partners_category_taxes'], TRUE);
        //dump($jsonVal);
        if (!is_null($jsonVal))
            $this['categoriesTaxes']->setValues($jsonVal);

        //nastaveni readonly poli
        if (!$this->isAllowed($this->name, 'edit')) {
            foreach ($this['categoriesTaxes']->getControls() as $control) {
                if ($control->controlPrototype->attrs['type'] == 'submit' || $control->controlPrototype->attrs['type'] == NULL || $control->controlPrototype->attrs['type'] == 'checkbox') {
                    $control->setDisabled(TRUE);
                } else {
                    $control->controlPrototype->readonly = 'readonly';
                }
            }
        }

        $partnerDocs = [];
        $partnerDocs2 = [];
        if ($this->showPartnerDocs){
            /*$partnerDocs = $this->InvoiceManager->findAll()->select('cl_invoice.id, inv_number AS number, "invoice" AS type, inv_date AS date, cl_partners_book.company AS company, price_e2 AS price, cl_currencies.currency_code AS currency_code')
                                                    ->where('cl_partners_book_id = ?', $this->id)->order('date DESC');*/
            $database = $this->InvoiceManager->getDatabase();
            $partnerDocs = $database->query('(SELECT cl_invoice.id, inv_number AS number, "invoice" AS type, inv_date AS date, 
                                                    cl_partners_branch.b_name AS b_name, price_e2 AS price, cl_currencies.currency_code AS currency_code, cl_users.name AS user_name,
                                                    cl_invoice.create_by AS create_by, cl_invoice.change_by AS change_by,
                                                    cl_invoice.created AS created, cl_invoice.changed AS changed, cl_center.name AS center_name
                                                     FROM cl_invoice LEFT JOIN cl_partners_branch ON cl_partners_branch.id = cl_invoice.cl_partners_branch_id
                                                     LEFT JOIN cl_currencies ON cl_currencies.id = cl_invoice.cl_currencies_id
                                                     LEFT JOIN cl_users ON cl_users.id = cl_invoice.cl_users_id
                                                     LEFT JOIN cl_center ON cl_center.id = cl_invoice.cl_center_id 
                                                     WHERE cl_invoice.cl_partners_book_id = ' . $this->id . ') UNION
                                                 (SELECT cl_invoice_advance.id, inv_number AS number, "invoice_advance" AS type, inv_date AS date, 
                                                    cl_partners_branch.b_name AS b_name, price_e2 AS price, cl_currencies.currency_code AS currency_code, cl_users.name AS user_name,
                                                    cl_invoice_advance.create_by AS create_by, cl_invoice_advance.change_by AS change_by,
                                                    cl_invoice_advance.created AS created, cl_invoice_advance.changed AS changed, cl_center.name AS center_name
                                                     FROM cl_invoice_advance LEFT JOIN cl_partners_branch ON cl_partners_branch.id = cl_invoice_advance.cl_partners_branch_id
                                                     LEFT JOIN cl_currencies ON cl_currencies.id = cl_invoice_advance.cl_currencies_id
                                                     LEFT JOIN cl_users ON cl_users.id = cl_invoice_advance.cl_users_id
                                                     LEFT JOIN cl_center ON cl_center.id = cl_invoice_advance.cl_center_id 
                                                     WHERE cl_invoice_advance.cl_partners_book_id = ' . $this->id . ') UNION
                                                 (SELECT cl_invoice_arrived.id, rinv_number AS number, "invoice_arrived" AS type, inv_date AS date, 
                                                    cl_partners_branch.b_name AS b_name, price_e2 AS price, cl_currencies.currency_code AS currency_code, cl_users.name AS user_name,
                                                    cl_invoice_arrived.create_by AS create_by, cl_invoice_arrived.change_by AS change_by,
                                                    cl_invoice_arrived.created AS created, cl_invoice_arrived.changed AS changed, cl_center.name AS center_name
                                                     FROM cl_invoice_arrived LEFT JOIN cl_partners_branch ON cl_partners_branch.id = cl_invoice_arrived.cl_partners_branch_id
                                                     LEFT JOIN cl_currencies ON cl_currencies.id = cl_invoice_arrived.cl_currencies_id
                                                     LEFT JOIN cl_users ON cl_users.id = cl_invoice_arrived.cl_users_id
                                                     LEFT JOIN cl_center ON cl_center.id = cl_invoice_arrived.cl_center_id 
                                                     WHERE cl_invoice_arrived.cl_partners_book_id = ' . $this->id . ') UNION
                                                 (SELECT cl_delivery_note.id, dn_number AS number, "delivery_note" AS type, issue_date AS date, 
                                                    cl_partners_branch.b_name AS b_name, price_e2 AS price, cl_currencies.currency_code AS currency_code, cl_users.name AS user_name,
                                                    cl_delivery_note.create_by AS create_by, cl_delivery_note.change_by AS change_by,
                                                    cl_delivery_note.created AS created, cl_delivery_note.changed AS changed, cl_center.name AS center_name
                                                     FROM cl_delivery_note LEFT JOIN cl_partners_branch ON cl_partners_branch.id = cl_delivery_note.cl_partners_branch_id
                                                     LEFT JOIN cl_currencies ON cl_currencies.id = cl_delivery_note.cl_currencies_id
                                                     LEFT JOIN cl_users ON cl_users.id = cl_delivery_note.cl_users_id
                                                     LEFT JOIN cl_center ON cl_center.id = cl_delivery_note.cl_center_id 
                                                     WHERE cl_delivery_note.cl_partners_book_id = ' . $this->id . ') UNION
                                                 (SELECT cl_sale.id, sale_number AS number, "sale" AS type, inv_date AS date, 
                                                    cl_partners_branch.b_name AS b_name, price_e2 AS price, cl_currencies.currency_code AS currency_code, cl_users.name AS user_name,
                                                    cl_sale.create_by AS create_by, cl_sale.change_by AS change_by,
                                                    cl_sale.created AS created, cl_sale.changed AS changed, "" AS center_name
                                                     FROM cl_sale LEFT JOIN cl_partners_branch ON cl_partners_branch.id = cl_sale.cl_partners_branch_id
                                                     LEFT JOIN cl_currencies ON cl_currencies.id = cl_sale.cl_currencies_id
                                                     LEFT JOIN cl_users ON cl_users.id = cl_sale.cl_users_id 
                                                     WHERE cl_sale.cl_partners_book_id = ' . $this->id . ') UNION
                                                 (SELECT cl_cash.id, cash_number AS number, "cash" AS type, inv_date AS date, 
                                                    "" AS bname, cash AS price, cl_currencies.currency_code AS currency_code, cl_users.name AS user_name,
                                                    cl_cash.create_by AS create_by, cl_cash.change_by AS change_by,
                                                    cl_cash.created AS created, cl_cash.changed AS changed, cl_center.name AS center_name
                                                     FROM cl_cash 
                                                     LEFT JOIN cl_currencies ON cl_currencies.id = cl_cash.cl_currencies_id
                                                     LEFT JOIN cl_users ON cl_users.id = cl_cash.cl_users_id
                                                     LEFT JOIN cl_center ON cl_center.id = cl_cash.cl_center_id 
                                                     WHERE cl_cash.cl_partners_book_id = ' . $this->id . ') UNION
                                                 (SELECT cl_commission.id, cm_number AS number, "commission" AS type, cm_date AS date, 
                                                    cl_partners_branch.b_name AS b_name, price_e2 AS price, cl_currencies.currency_code AS currency_code, cl_users.name AS user_name,
                                                    cl_commission.create_by AS create_by, cl_commission.change_by AS change_by,
                                                    cl_commission.created AS created, cl_commission.changed AS changed, cl_center.name AS center_name
                                                     FROM cl_commission LEFT JOIN cl_partners_branch ON cl_partners_branch.id = cl_commission.cl_partners_branch_id
                                                     LEFT JOIN cl_currencies ON cl_currencies.id = cl_commission.cl_currencies_id
                                                     LEFT JOIN cl_users ON cl_users.id = cl_commission.cl_users_id
                                                     LEFT JOIN cl_center ON cl_center.id = cl_commission.cl_center_id 
                                                     WHERE cl_commission.cl_partners_book_id = ' . $this->id . ') UNION
                                                 (SELECT cl_offer.id, cm_number AS number, "commission" AS type, offer_date AS date, 
                                                    cl_partners_branch.b_name AS b_name, price_e2 AS price, cl_currencies.currency_code AS currency_code, cl_users.name AS user_name,
                                                    cl_offer.create_by AS create_by, cl_offer.change_by AS change_by,
                                                    cl_offer.created AS created, cl_offer.changed AS changed, cl_center.name AS center_name
                                                     FROM cl_offer LEFT JOIN cl_partners_branch ON cl_partners_branch.id = cl_offer.cl_partners_branch_id
                                                     LEFT JOIN cl_currencies ON cl_currencies.id = cl_offer.cl_currencies_id
                                                     LEFT JOIN cl_users ON cl_users.id = cl_offer.cl_users_id
                                                     LEFT JOIN cl_center ON cl_center.id = cl_offer.cl_center_id 
                                                     WHERE cl_offer.cl_partners_book_id = ' . $this->id . ') UNION
                                                 (SELECT cl_order.id, od_number AS number, "order" AS type, od_date AS date, 
                                                    cl_partners_branch.b_name AS b_name, price_e2 AS price, cl_currencies.currency_code AS currency_code, cl_users.name AS user_name,
                                                    cl_order.create_by AS create_by, cl_order.change_by AS change_by,
                                                    cl_order.created AS created, cl_order.changed AS changed, "" AS center_name
                                                     FROM cl_order LEFT JOIN cl_partners_branch ON cl_partners_branch.id = cl_order.cl_partners_branch_id
                                                     LEFT JOIN cl_currencies ON cl_currencies.id = cl_order.cl_currencies_id
                                                     LEFT JOIN cl_users ON cl_users.id = cl_order.cl_users_id 
                                                     WHERE cl_order.cl_partners_book_id = ' . $this->id . ')                                                                                                                   
                                                     ORDER BY date DESC');

            $partnerDocs2 = $database->query('(SELECT cl_partners_event.id, event_number AS number, "helpdesk" AS type, date_rcv AS date, 
                                                    cl_partners_branch.b_name AS b_name, 0 AS price, "" AS currency_code, cl_users.name AS user_name,
                                                    cl_partners_event.create_by AS create_by, cl_partners_event.change_by AS change_by,
                                                    cl_partners_event.created AS created, cl_partners_event.changed AS changed, "" AS center_name
                                                     FROM cl_partners_event LEFT JOIN cl_partners_branch ON cl_partners_branch.id = cl_partners_event.cl_partners_branch_id
                                                     LEFT JOIN cl_users ON cl_users.id = cl_partners_event.cl_users_id 
                                                     WHERE cl_partners_event.cl_partners_book_id = ' . $this->id . ') UNION
                                                 (SELECT cl_task.id, task_number AS number, "task" AS type, task_date AS date, 
                                                    "" AS b_name, 0 AS price, "" AS currency_code, cl_users.name AS user_name,
                                                    cl_task.create_by AS create_by, cl_task.change_by AS change_by,
                                                    cl_task.created AS created, cl_task.changed AS changed, "" AS center_name
                                                     FROM cl_task 
                                                     LEFT JOIN cl_users ON cl_users.id = cl_task.cl_users_id 
                                                     WHERE cl_task.cl_partners_book_id = ' . $this->id . ')                                                                                                                    
                                                     ORDER BY date DESC');

        }
        $this->template->partnerDocs = $partnerDocs;
        $this->template->partnerDocs2 = $partnerDocs2;

    }

    public function handleShowPartnerDocs(){
        $this->showPartnerDocs = true;
        $this->redrawControl('partdoc');
    }

    public function handleShowPartnerDocsType($type){

    }

    protected function createComponentEditSettings($name)
    {
        $form = new Form($this, $name);
        $form->addHidden('id', NULL);
        $form->addText('partner_code', $this->translator->translate('partner_code'), 16, 16)
            ->setHtmlAttribute('placeholder', $this->translator->translate('partner_codePh'));

        $form->addText('min_order', $this->translator->translate('min_order'), 10, 14)
            ->setHtmlAttribute('placeholder', $this->translator->translate('min_orderPh'));

        $arrCenter = $this->CenterManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addSelect('cl_center_id', $this->translator->translate("center"), $arrCenter)
            ->setTranslator(NULL)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate("center"))
            ->setPrompt('');

        $arrRegions = $this->RegionsManager->findAll()->fetchPairs('id', 'region_name');
        $form->addSelect('cl_regions_id', $this->translator->translate("region"), $arrRegions)
            ->setTranslator(NULL)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate('region'))
            ->setPrompt('');

        $form->addSelect('subject', $this->translator->translate("subject"), array('' => 'zvolte obor', 'výroba' => 'výroba', 'služby' => 'služby', 'zemědělství' => 'zemědělství'))
            ->setTranslator(NULL)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate("subjectPh"))
            ->setPrompt("subjectPh");

        $form->addSelect('size', $this->translator->translate("size"), array('' => 'zvolte velikost', 'malá' => 'malá', 'střední' => 'střední', 'velká' => 'velká'))
            ->setTranslator(NULL)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate("size"))
            ->setPrompt("sizePh");

        $form->addText('potential', $this->translator->translate('potential'), 30, 50)
            ->setHtmlAttribute('placeholder', $this->translator->translate('potential'));


        $form->addText('due_date', $this->translator->translate('duedate'), 30, 50)
            ->setHtmlAttribute('placeholder', $this->translator->translate('duedate'));

        $arrPay = $this->PaymentTypesManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addSelect('cl_payment_types_id', $this->translator->translate("payment"), $arrPay)
            ->setTranslator(NULL)
            ->setPrompt($this->translator->translate("payment"))
            ->setHtmlAttribute('class', 'form-control input-sm');

        $arrStore = $this->StorageManager->getStoreTreeNotNested();
        $form->addSelect('cl_storage_id', $this->translator->translate("storage"), $arrStore)
            ->setTranslator(NULL)
            ->setPrompt($this->translator->translate("storagePh"))
            ->setHtmlAttribute('class', 'form-control input-sm');

        $arrPricesGroups = $this->PricesGroupsManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addSelect('cl_prices_groups_id', $this->translator->translate("pricesgroups"), $arrPricesGroups)
            ->setTranslator(NULL)
            ->setPrompt($this->translator->translate("pricesgroupsPh"))
            ->setHtmlAttribute('class', 'form-control input-sm');

        $arrCurrencies = $this->CurrenciesManager->findAll()->order('currency_code')->fetchPairs('id', 'currency_code');
        $form->addSelect('cl_currencies_id', $this->translator->translate("Výchozí_měna"), $arrCurrencies)
            ->setTranslator(NULL)
            ->setPrompt($this->translator->translate("Zvolte_výchozí_měnu"))
            ->setHtmlAttribute('class', 'form-control input-sm');

        $form->addText('discount', $this->translator->translate('discount'), 4, 4)
            ->setHtmlAttribute('placeholder', $this->translator->translate('discountPh'));

        $form->addSelect('lang', $this->translator->translate('lang'), $this->ArraysManager->getLanguages())
            ->setTranslator(NULL)
            ->setHtmlAttribute('class', 'form-control chzn-select input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('lang'));

        $form->addCheckbox('contract', $this->translator->translate("contract"))
            ->setDefaultValue(FALSE)
            ->setHtmlAttribute('class', 'items-show');

        $form->addCheckbox('no_invoice_from_dn', $this->translator->translate("no_invoice_from_dn"))
            ->setDefaultValue(FALSE)
            ->setHtmlAttribute('class', 'items-show');

        $form->addCheckbox('send_dn_from_app', $this->translator->translate("send_dn_from_app"))
            ->setDefaultValue(FALSE)
            ->setHtmlAttribute('class', 'items-show');


        $form->addSubmit('send', $this->translator->translate('send'))->setHtmlAttribute('class', 'btn btn-success');
        $form->addSubmit('back', $this->translator->translate('back'))
            ->setHtmlAttribute('class', 'btn btn-warning')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBackSettings');

        $form->onSuccess[] = array($this, 'SubmitEditSettingsSubmitted');
        return $form;

    }

    public function stepBackSettings()
    {
        $this->redirect('default');
    }

    public function SubmitEditSettingsSubmitted(Form $form)
    {
        $data = $form->values;
        if ($form['send']->isSubmittedBy()) {
            if (!empty($data->id)) {
                $this->DataManager->update($data, TRUE);
                $this->flashMessage('Změny byly uloženy.', 'success');
            } else {
                $this->DataManager->insert($data);
                $this->flashMessage('Nový záznam byl uložen.', 'success');
            }
            $this->redirect('edit');
        } else {
            $this->flashMessage('Změny nebyly uloženy.', 'warning');
            $this->redirect('edit');
        }
    }


    protected function createComponentEdit($name)
    {
        $form = new Form($this, $name);

        $form->addHidden('id', NULL);
        $form->addText('company', $this->translator->translate('company'), 120, 120)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('companyPh'));

        $form->addText('street', $this->translator->translate('street'), 120, 120)
            ->setHtmlAttribute('class', 'form-control input-sm')
            ->setHtmlAttribute('placeholder', $this->translator->translate('streetPh'));

        $form->addText('city', $this->translator->translate('city'), 60, 60)
            ->setHtmlAttribute('placeholder', $this->translator->translate('cityPh'));

        $form->addText('zip', $this->translator->translate('zip'), 11, 11)
            ->setHtmlAttribute('placeholder', $this->translator->translate('zipPh'));

        $form->addText('company2', $this->translator->translate('company2'), 120, 120)
            ->setHtmlAttribute('placeholder', $this->translator->translate('company2Ph'));

        $form->addText('street2', $this->translator->translate('street2'), 120, 120)
            ->setHtmlAttribute('placeholder', $this->translator->translate('street2Ph'));

        $form->addText('city2', $this->translator->translate('city2'), 60, 60)
            ->setHtmlAttribute('placeholder', $this->translator->translate('city2Ph'));

        $form->addText('zip2', $this->translator->translate('zip2'), 11, 11)
            ->setHtmlAttribute('placeholder', $this->translator->translate('zip2Ph'));

        $form->addCheckbox('platce_dph', $this->translator->translate('platceDph'))
            ->setDefaultValue(TRUE)
            ->setHtmlAttribute('class', 'items-show');

        $form->addText('ico', $this->translator->translate('ico'), 15, 15)
            ->setHtmlAttribute('autocomplete', 'off')
            ->setHtmlAttribute('data-url-string', $this->link('SearchIco!'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('icoPh'));

        $form->addText('dic', $this->translator->translate('dic'), 15, 15)
            ->setHtmlAttribute('autocomplete', 'off')
            ->setHtmlAttribute('placeholder', $this->translator->translate('dicPh'));

        $form->addTextArea('comment', $this->translator->translate('comment'), 70, 7)
            ->setHtmlAttribute('placeholder', $this->translator->translate('commentPh'));
        $form->addCheckbox('show_comment', $this->translator->translate('Zobrazovat_všude'))
            ->setHtmlAttribute('title', $this->translator->translate('Nepřehlédnutelná_poznámka_bude_zobrazována_všude_při_práci_s_partnerem'))
            ->setDefaultValue(TRUE)
            ->setHtmlAttribute('class', 'items-show');

        $arrCountries = $this->CountriesManager->findAllTotal()->order('name')->fetchPairs('id', 'name');
        $form->addSelect('cl_countries_id', $this->translator->translate("countries"), $arrCountries)
            ->setTranslator(NULL)
            ->setPrompt("");

        $arrCountries2 = $this->CountriesManager->findAllTotal()->fetchPairs('name', 'name');
        $form->addSelect('country2', $this->translator->translate("countries2"), $arrCountries2)
            ->setTranslator(NULL)
            ->setPrompt("");

        $arrGroups = $this->PartnersGroupsManager->findAll()->order('name')->fetchPairs('id', 'name');
        $form->addSelect('cl_partners_groups_id', $this->translator->translate("Skupina"), $arrGroups)
            ->setTranslator(NULL)
            ->setPrompt("");


        $arrUsers = array();
        $arrUsers['Aktivní'] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 0')->fetchPairs('id', 'name');
        $arrUsers['Neaktivní'] = $this->UserManager->getUsersInCompany($this->user->getIdentity()->cl_company_id)->where('not_active = 1')->fetchPairs('id', 'name');

        $form->addSelect('cl_users_id', $this->translator->translate("seller"), $arrUsers)
            ->setTranslator(NULL)
            ->setHtmlAttribute('data-placeholder', $this->translator->translate("seller"))
            ->setPrompt("");

        $form->addText('email', $this->translator->translate('email'), 30, 50)
            ->setHtmlAttribute('autocomplete', 'off')
            ->setHtmlAttribute('placeholder', $this->translator->translate('emailPh'));

        $form->addText('web', $this->translator->translate('web'), 30, 50)
            ->setHtmlAttribute('placeholder', $this->translator->translate('webPh'));
        $form->addText('phone', $this->translator->translate('phone'), 30, 50)
            ->setHtmlAttribute('placeholder', $this->translator->translate('phonePh'));
        $form->addText('person', $this->translator->translate('person'), 30, 50)
            ->setHtmlAttribute('placeholder', $this->translator->translate('personPh'));

        $form->addCheckbox('same_address', $this->translator->translate('sameaddress'))
            ->setHtmlAttribute('class', 'items-show');

        $form->addCheckbox('supplier', $this->translator->translate("supplier"))
            ->setHtmlAttribute('class', 'items-show');

        $form->addCheckbox('producer', $this->translator->translate("producer"))
            ->setHtmlAttribute('class', 'items-show');

        $form->addCheckbox('customer', $this->translator->translate("customer"))
            ->setHtmlAttribute('class', 'items-show');

        $form->addText('account_code', $this->translator->translate('account'), 17, 17)
            ->setHtmlAttribute('placeholder', $this->translator->translate('accountPh'));
        $form->addText('bank_code', $this->translator->translate('bank'), 4, 4)
            ->setHtmlAttribute('placeholder', $this->translator->translate('bankPh'));
        $form->addText('iban_code', $this->translator->translate('iban'), 35, 35)
            ->setHtmlAttribute('placeholder', $this->translator->translate('ibanPh'));
        $form->addText('swift_code', $this->translator->translate('swift'), 11, 11)
            ->setHtmlAttribute('placeholder', $this->translator->translate('swiftPh'));
        $form->addText('spec_symb', $this->translator->translate('specsymb'), 20, 20)
            ->setHtmlAttribute('placeholder', $this->translator->translate('specsymbPh'));

        $form->addSubmit('send', $this->translator->translate('send'))->setHtmlAttribute('class', 'btn btn-success');
        $form->addSubmit('back', $this->translator->translate('back'))
            ->setHtmlAttribute('class', 'btn btn-warning')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBack');

        $form->onSuccess[] = array($this, 'SubmitEditSubmitted');
        return $form;
    }

    public function stepBack()
    {
        $this->redirect('default');
    }

    public function SubmitEditSubmitted(Form $form)
    {


        $data = $form->values;

        if ($form['send']->isSubmittedBy()) {
            //dump($data->id);
            //die;
            if (!empty($data->id)) {
                $this->DataManager->update($data, TRUE);
                $this->flashMessage('Změny byly uloženy.', 'success');
            } else {
                //kontrola existence podle IC
                if ($this->DataManager->findOneBy(array('ico' => $data->ico)))
                    $this->flashMessage('Nový záznam nebyl uložen. Zadané IČO již existuje', 'warning');
                else {
                    $this->DataManager->insert($data);
                    $this->flashMessage('Nový záznam byl uložen.', 'success');
                }
            }

            $this->redirect('default');
        } else {
            $this->flashMessage('Změny nebyly uloženy.', 'warning');
            $this->redirect('default');
        }

    }

    protected function createComponentCategoriesTaxes($name)
    {
        $form = new Form($this, $name);
        //$form->setMethod('POST');
        $form->addHidden('id', NULL);
        $curr_name = $this->settings->cl_currencies->currency_name;
        $arrCategory = $this->PartnersCategoryManager->findAll()->
        select('id,CONCAT(category_name," - ",hour_tax," ' . $curr_name . '"," - ",hour_tax_remote' . '," ' . $curr_name . '") AS category_name')->
        fetchPairs('id', 'category_name');
        $form->addSelect('cl_partners_category_id', "Výchozí kategorie:", $arrCategory)
            ->setHtmlAttribute('data-placeholder', 'Zvolte výchozí kategorii')
            ->setPrompt('Zvolte kategorii');

        $tmpCategories = $this->PartnersCategoryManager->findAll();
        foreach ($tmpCategories as $one) {
            $form->addText('categ' . $one->id, $one->category_name, 10, 10)
                ->setRequired(FALSE)
                ->addRule(Form::INTEGER, 'Musí být zadáno číslo.')
                ->setDefaultValue('0');
            $form->addText('categremote' . $one->id, $one->category_name, 10, 10)
                ->setRequired(FALSE)
                ->addRule(Form::INTEGER, 'Musí být zadáno číslo.')
                ->setDefaultValue('0');
        }


        $form->addSubmit('send', 'Uložit')->setHtmlAttribute('class', 'btn btn-primary');
        $form->onSuccess[] = array($this, 'SubmitCategoriesTaxes');
        return $form;
    }

    public function SubmitCategoriesTaxes(Form $form)
    {
        $dataForm = $form->values;
        $id = $dataForm['id'];
        unset($dataForm['id']);
        $jsonData = json_encode($dataForm);
        //dump($jsonData);
        //die;
        $data = array();
        $data['id'] = $id;
        $data['cl_partners_category_taxes'] = $jsonData;
        $data['cl_partners_category_id'] = $dataForm['cl_partners_category_id'];
        $this->DataManager->update($data);
        $this->flashMessage('Změny byly uloženy.', 'success');
        $this->redirect('Partners:default');

    }

    public function ListGridInsert($sourceData)
    {
        //new record into cl_pricelist_partner
        $arrData = array();
        $arrData[$this->DataManager->tableName . '_id'] = $this->id;
        $arrData['cl_pricelist_id'] = $sourceData->id;
        $arrData['item_order'] = $this->PriceListPartnerManager->findAll()->where($this->DataManager->tableName . '_id = ?', $arrData[$this->DataManager->tableName . '_id'])->max('item_order') + 1;
        $tmpParentData = $this->DataManager->find($this->id);
        //dump($this->id);
        //die;
        $arrData['vat'] = $sourceData->vat;
        $arrData['price'] = $sourceData->price;
        $arrData['cl_currencies_id'] = $sourceData->cl_currencies_id;
        $arrData['price_vat'] = $sourceData->price_vat;
        $row = $this->PriceListPartnerManager->insert($arrData);
        return ($row);
    }

    //control method to determinate if we can delete
    public function beforeDelete($lineId)
    {
        //$tmpParentData = $this->DataManager->find($this->id);
        $result = TRUE;
        //Debugger::fireLog($result);
        return $result;
    }

    //new sums on parent records cl_store, cl_pricelist after delete
    public function afterDelete($line)
    {


    }

    public function handleSnippetsUpdate()
    {
        //update called after closing modal windows
        $this->redrawControl('cl_currencies');
        $this->redrawControl('cl_storage');
        $this->redrawControl('cl_status');
        $this->redrawControl('cl_partners_book');
        $this['storeListgrid']->redrawControl('pricelist2');

    }

    public function DataProcessListGridValidate($data)
    {
        bdump($data);
        $result = NULL;
        //21.05.2020 send from cl_partners_book_workers
        if (isset($data['b2b_enabled'])) {
            if ($data['b2b_enabled']) {
                (empty($data['worker_email'])) ? $result = "Email musí být vyplněn." : $result = NULL;

                $tmpUsers = $this->PartnersBookWorkersManager->findAllTotal()->where('worker_email = ?', $data['worker_email'])->fetch();
                if ($tmpUsers) {
                    if ($tmpUsers->id != $data['id']) {
                        $result = 'Email ' . $data['worker_email'] . ' používá jiný uživatel. Zadejte jiný email.';
                    }
                }
                if (is_null($result)) {
                    $tmpData = $this->PartnersBookWorkersManager->find($data['id']);
                    if ($tmpData) {
                        (empty($tmpData['b2b_password']) && empty($data['password2'])) ? $result = "Heslo musí být zadáno." : $result = NULL;
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

    public function UpdateSum()
    {

        $this['workersListGrid']->redrawControl('editLines');
        $this['partnerPriceListGrid']->redrawControl('editLines');
        $this['calendarPlaneListGrid']->redrawControl('editLines');
        $this['partnersBranchGrid']->redrawControl('editLines');

        return;
    }


    public function handlePricelistPartner($value)
    {
        $arrData = array();
        $arrData['id'] = $this->id;
        $arrData['pricelist_partner'] = $value;
        //Debugger::fireLog($arrData);
        $this->DataManager->update($arrData);
    }

    public function handlePricelistPartnerOnly($value)
    {
        $arrData = array();
        $arrData['id'] = $this->id;
        $arrData['pricelist_partner_only'] = $value;
        $this->DataManager->update($arrData);
    }


    protected function createComponentCoopRequest($name)
    {
        $form = new Form($this, $name);
        $form->addHidden('id_company', 0);
        $form->addHidden('child_cl_partners_book_id', 0);
        $form->addTextArea('message', '', 20, 4)
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Zde můžete vepsat svou zprávu. Např.: "Toto je nabídka partnerské spolupráce na Trynx. Prosím klikněte na níže uvedený odkaz pro aktivaci partnerství."');

        $form->addSubmit('send', 'Odeslat nabídku partnerství')->setHtmlAttribute('class', 'btn btn-primary btn-sm');
        $form->onSuccess[] = array($this, 'CoopSend');
        return $form;
    }

    /*
     * send request for cooprequest
     */
    public function CoopSend(Form $form)
    {
        $data = $form->values;
        if ($form['send']->isSubmittedBy()) {
            if ($company = $this->CompaniesManager->findAllTotal()->where(array('id' => $data->id_company))->fetch()) {
                $this->CoopRequest($data);
            } else {
                $this->CoopCreate($data);
            }

        }
    }

    /*
     * send request for cooprequest
     */
    public function CoopRequest($data)
    {
        //$data=$form->values;
        //if ($form['send']->isSubmittedBy())
        //{
        //send email
        try {
            if ($company = $this->CompaniesManager->findAllTotal()->where(array('id' => $data->id_company))->fetch()) {
                //$this->handleCoopRequestSend($company); //send email with request
                $this->handleCoopRequestSend($data->message); //send email with request

                //save system message

                //save data
                $arrData = array();
                $arrData['id'] = $this->id;
                $arrData['coop_enable'] = 2;  //2-zadost odeslana, 1-spoluprace povolena, 0-spoluprace nepovolena
                $arrData['coop_type'] = 2;  //1-coopCreated , 2-coopRequested
                $arrData['coop_cl_company_id'] = $data->id_company;
                //Debugger::fireLog($arrData);
                $this->DataManager->update($arrData);

                //insert new to cl_partners_coop
                $arrData = array();
                $arrData['cl_company_id'] = $data->id_company;
                $arrData['child_cl_partners_book_id'] = $data->child_cl_partners_book_id;
                $arrData['master_cl_company_id'] = $this->settings->id;
                $arrData['status'] = 2;
                //$arrData['confirm_key'] = $template->confirmKey;
                $expDate = new \Nette\Utils\DateTime;
                $arrData['confirm_exp'] = $expDate->modify('+3 hour');
                if ($data = $this->PartnersCoopManager->findAllTotal()->where(array('master_cl_company_id' => $this->settings->id,
                    'cl_company_id' => $data->id_company))->fetch()) {
                    //Debugger::fireLog($data);
                    $arrData['id'] = $data->id;
                    //Debugger::fireLog($arrData);
                    $this->PartnersCoopManager->updateForeign($arrData);
                } else
                    $this->PartnersCoopManager->insertForeign($arrData);

                //$this->flashMessage('Nabídka partnerství byla odeslána', 'info');
                //$this->redrawControl('flash');
            }
        } catch (Exception $e) {
            $errorMess = $e->getMessage();
            $this->flashMessage($errorMess, 'danger');
            $this->redrawControl('flash');
        }
        // }

    }


    /*
     * send request for cooprequest
     */
    public function CoopCreate($data)
    {
        //$data=$form->values;
        //if ($form['send']->isSubmittedBy())
        //{
        //send email
        try {

            if ($dataComp = $this->DataManager->find($this->id)) {
                //try to find user by email, if exists, we cannot add new company with this email
                if ($tmpUser = $this->UserManager->getUser($dataComp->email)) {
                    $this->flashMessage('Nabídka partnerství nebyla odeslána. Zadaný email odběratele je již registrován.', 'danger');
                    $this->redrawControl('flash');
                    $this->redrawControl('snippet');

                } else {
                    //save system message


                    //prepare new company in cl_company
                    $arrData = array();
                    $arrData['name'] = $dataComp->company;
                    $arrData['street'] = $dataComp->street;
                    $arrData['zip'] = $dataComp->zip;
                    $arrData['city'] = $dataComp->city;
                    $arrData['ico'] = $dataComp->ico;
                    $arrData['dic'] = $dataComp->dic;
                    $arrData['email'] = $dataComp->email;
                    $tmpNewCompany = $this->CompaniesManager->insertForeign($arrData);

                    //also prepare new user for login in cl_users
                    $arrData = array();
                    $password = \Nette\Utils\Random::generate(10, 'A-Za-z0-9');
                    $tmpNewUser = $this->UserManager->add($dataComp->person, $password);
                    //update login key
                    $arrData = array();
                    $arrData['id'] = $tmpNewUser->id;
                    $arrData['email'] = $dataComp->email;
                    $arrData['cl_company_id'] = $tmpNewCompany->id;
                    //$arrData['order_key'] = \Nette\Utils\Strings::random(64,'A-Za-z0-9');
                    //$tmpOrderKey = $arrData['order_key'];
                    $this->UserManager->updateUser($arrData);
                    //prepare linkem for change password
                    $confirm = $this->UserManager->genConfirmation($tmpNewUser->id);
                    $confirmUrl = $this->link(':Login:LostPassword:NewPassword', $confirm['urlKey'], $dataComp->email);


                    //prepare acces to company cl_access_company
                    $arrData = array();
                    $arrData['cl_users_id'] = $tmpNewUser->id;
                    $arrData['cl_company_id'] = $tmpNewCompany->id;
                    $arrData['role'] = 'user';
                    $this->CompaniesAccessManager->insertForeign($arrData);


                    //insert new to cl_partners_coop
                    $arrData = array();
                    $arrData['cl_company_id'] = $tmpNewCompany->id;
                    $arrData['child_cl_partners_book_id'] = $dataComp->id;
                    $arrData['master_cl_company_id'] = $this->settings->id;
                    $arrData['status'] = 1;
                    //$arrData['confirm_key'] = $template->confirmKey;
                    $expDate = new \Nette\Utils\DateTime;
                    $arrData['confirm_exp'] = $expDate->modify('+3 hour');
                    if ($dataCoop = $this->PartnersCoopManager->findAllTotal()->where(array('master_cl_company_id' => $this->settings->id,
                        'cl_company_id' => $tmpNewCompany->id))->fetch()) {
                        //Debugger::fireLog($data);
                        $arrData['id'] = $dataCoop->id;
                        //Debugger::fireLog($arrData);
                        $this->PartnersCoopManager->updateForeign($arrData);
                    } else {
                        $dataCoop = $this->PartnersCoopManager->insertForeign($arrData);
                    }

                    //save data
                    $arrData = array();
                    $arrData['id'] = $this->id;
                    $arrData['coop_enable'] = 1;  //2-zadost odeslana, 1-spoluprace povolena, 0-spoluprace nepovolena
                    $arrData['coop_type'] = 1;  //1-coopCreated , 2-coopRequested
                    $arrData['coop_cl_company_id'] = $tmpNewCompany->id;
                    //Debugger::fireLog($arrData);
                    $this->DataManager->update($arrData);

                    $update = $this->PartnersCoopManager->findAllTotal()->where(array('id' => $dataCoop->id))->fetch();
                    $this->PartnersCoopManager->createCoopData($update); //vytvori data v cl_partners_book, cl_pricelist z master company

                    //create default values in some tables
                    $this->RegCompaniesManager->createDefaultData($tmpNewCompany->id);

                    ///send email
                    $this->handleCoopCreateSend($data->message);

                    //$this->flashMessage('Nabídka partnerství byla odeslána', 'info');
                    //$this->redrawControl('flash');
                    //$this->redrawControl('snippet');

                }
            }
        } catch (Exception $e) {
            $errorMess = $e->getMessage();
            $this->flashMessage($errorMess, 'danger');
            $this->redrawControl('flash');
            $this->redrawControl('snippet');
        }
    }

    //}

    public function handleCoopStop()
    {
        $tmpPartner = $this->DataManager->find($this->id);
        $tmpPartner->update(array('id' => $this->id, 'coop_enable' => 0, 'coop_cl_company_id' => NULL));
        $this->flashMessage('Partnerství je zrušeno', 'info');
        $this->redrawControl('flash');
        $this->redrawControl('snippet');
    }

    public function handleCoopCreateSend($message = "")
    {
        $tmpEmail = $this->DataManager->find($this->id);
        if ($tmpUser = $this->UserManager->getAll()->where(array('email' => $tmpEmail->email))->fetch()) {
            //prepare linkem for change password
            $confirm = $this->UserManager->genConfirmation($tmpUser->id);
            $confirmUrl = $this->link(':Login:LostPassword:NewPassword', $confirm['urlKey'], $tmpEmail->email);

            ///send email
            $template = $this->createTemplate();
            $template->setFile(__DIR__ . '/../templates/Partners/coopcreate.latte');
            //prepare of email content
            $subjectX = 'Nabídka partnerské spolupráce na Trynx';
            $template->today = new \Nette\Utils\DateTime;
            $template->body = $message;
            $template->confirmUrl = $confirmUrl;
            $template->settings = $this->settings;
            //$mail = new Message;

            //$mail->setFrom('mailer@Trynx',$this->settings->name)
            //  ->addReplyTo($this->settings->email)
            //->addTo($tmpEmail->email,$tmpEmail->company)
            //->setSubject($subject)
            //->setHtmlBody($template);
            //				->addCc('info@faktury.cz','2HCS Fakturace')
            //$mailer = new SendmailMailer;
            //$mailer->send($mail);

            $emailTo = array($tmpEmail->email, $tmpEmail->company);
            $emailFrom = $this->settings->email;
            $subject = $subjectX;
            $body = $template;
            $this->emailService->sendMail($this->settings, $emailFrom, $emailTo, $subject, $body);

            $this->flashMessage('Nabídka partnerství byla odeslána', 'info');
            $this->redrawControl('flash');
            $this->redrawControl('snippet');
        }

    }

    public function handleCoopRequestSend($message = "")
    {
        $data = $this->DataManager->find($this->id);
        if ($company = $this->CompaniesManager->findAllTotal()->where(array('id' => $data->coop_cl_company_id))->fetch()) {
            $template = $this->createTemplate();
            $template->setFile(__DIR__ . '/../templates/Partners/cooprequest.latte');
            //prepare of email content
            $subjectX = 'Nabídka partnerské spolupráce na Trynx';
            $template->today = new \Nette\Utils\DateTime;
            $template->body = $message;
            $template->settings = $this->settings;
            //$template->confirmKey = \Nette\Utils\Strings::random(64,'A-Za-z0-9');
            //$mail = new Message;
            //$mail->setFrom('mailer@Trynx',$this->settings->name)
            //->addReplyTo($this->settings->email)
            //->addTo($company->email,$company->name)
            //->setSubject($subject)
            //->setHtmlBody($template);

            //$mailer = new SendmailMailer;
            //$mailer->send($mail);

            $emailTo = array($company->email, $company->name);
            $emailFrom = $this->settings->email;
            $subject = $subjectX;
            $body = $template;
            $this->emailService->sendMail($this->settings, $emailFrom, $emailTo, $subject, $body);

            $this->flashMessage('Nabídka partnerství byla odeslána', 'info');
            $this->redrawControl('flash');
            $this->redrawControl('snippet');
        }
    }


    public function handlePublicEvent($value)
    {
        $arrData = array();
        $arrData['id'] = $this->id;
        $arrData['public_event'] = $value;
        if ($value == 1) {
            $newToken = '';
            while ($this->DataManager->findAllTotal()->where(array('public_event_token' => $newToken))->fetch() || $newToken == '') {
                $newToken = \Nette\Utils\Random::generate(16, 'A-Za-z0-9');
            }
            $arrData['public_event_token'] = $newToken;
        } else
            $arrData['public_event_token'] = "";

        //Debugger::fireLog($arrData);
        $this->DataManager->update($arrData);
        $this->redrawControl('publiceventlink');
    }

    public function handleGetAres($ico)
    {
        //const URL = 'http://2hcssro.savana-hosting.cz/aresproxy/getares.php';
        $ares = new \halasz\Ares\Ares();
        try {
            $result = $ares->loadData($ico); // return object \halasz\Ares\Data
            //bdump($result);
            $arrResult = $result->toArray();
            $this->handleGetPlatceDPH($arrResult['tin']);

        } catch (\halasz\Ares\IdentificationNumberNotFoundException $e) {
            $this->flashMessage('IČ nebylo nalezeno.');
            $arrResult = [];
        }

        if ($tmpCountries = $this->CountriesManager->findAllTotal()->where(array('name' => 'Česko'))->fetch()) {

            $arrResult['cl_countries_id'] = $tmpCountries->id;
        }
        //bdump($arrResult);

        $this->sendJson($arrResult);
    }


    public function handleGetAccounts(){
        $this->redrawControl('partnersAccountMain');
        //$this->sendPayload();
    }

    public function handleGetPlatceDPH($dic){
        //$dic = 'CZ25398989';
        $client = new SoapClient("http://adisrws.mfcr.cz/adistc/axis2/services/rozhraniCRPDPH.rozhraniCRPDPHSOAP?wsdl", ['trace' => true]);
        $response = $client->__call("getStatusNespolehlivyPlatce", [0 => [$dic]]);
        $arrResponse = $response->statusPlatceDPH->zverejneneUcty;
        $tmpResponse = json_decode(json_encode($arrResponse->ucet), true);
        //bdump($tmpResponse);
        if (array_key_exists('standardniUcet', $tmpResponse))
        {
            $arrResponse2[] = $tmpResponse;
        }else{
            $arrResponse2 = $tmpResponse;
        }
        //die;
        //bdump($arrResponse2);
        //die;
        if (is_array($arrResponse2)) {
            $this->PartnersAccountManager->updateAccounts($arrResponse2, $this->id, $this->settings->cl_currencies_id);
        }
        $this->redrawControl('partnersAccountMain');

    }


    public function handleGetVies($dic)
    {
        $vies = new Vies();
        if (false === $vies->getHeartBeat()->isAlive()) {
            $this->flashMessage('Service is not available at the moment, please try again later.', 'danger');
            $this->redrawControl('flash');
        }

        try {
            $req_CC = substr($this->settings['dic'], 0, 2);
            $req_ID = substr($this->settings['dic'], 2);
            $trd_CC = substr($dic, 0, 2);
            $trd_ID = substr($dic, 2);
            $vatResult = $vies->validateVat(
                $trd_CC,        // Trader country code
                $trd_ID,        // Trader VAT ID
                $req_CC,        // Requester country code (your country code)
                $req_ID         // Requester VAT ID (your VAT ID)
            );

            /*
             *
DIČ: SK2023432136
name: 'PRECISTEC GROUP s.r.o.'
address:
'Zárieč-Keblov 74
01332 Svederník
Slovensko'

DIČ:CZ25398989
name: '2H C.S. s.r.o.'
address:
'Haškova 996/6
KOPŘIVNICE
742 21  KOPŘIVNICE 1'

DIČ: SK2020091997
name: 'Zoznam, s.r.o.'
address:
'Viedenská cesta 3-7
85101 Bratislava - mestská časť Petržalka
Slovensko'
             */
            bdump($vatResult);
            $arrResult['company'] = $vatResult->getName();
            bdump($vatResult->getAddress());
            $address = explode(PHP_EOL, $vatResult->getAddress());
            if ($trd_CC == 'CZ') {
                $arrResult['street'] = $address[0];
                $arrResult['city'] = $address[1];
                $part = explode(' ', $address[1]);
                $pos = strpos($address[2], $part[0]);
                $arrResult['zip'] = trim(substr($address[2], 0, $pos));
            } elseif ($trd_CC == 'SK') {
                $arrResult['street'] = $address[0];
                $part = explode(' ', $address[1]);
                $arrResult['zip'] = $part[0];
                unset($part[0]);
                $arrResult['city'] = implode(' ', $part);

            }
            $arrResult['valid'] = $vatResult->isValid();
            $tmpCountry = $this->CountriesManager->findAllTotal()->where('vat_code = ?', $trd_CC)->fetch();
            if ($tmpCountry) {
                $arrResult['cl_countries_id'] = $tmpCountry['id'];
            } else {
                $arrResult['cl_countries_id'] = NULL;
            }


            //  bdump($arrResult);
            //$arrResult['street'] = $vatResult->get
            //$arrResult = $result->toArray();
        } catch (ViesException $viesException) {
            $this->flashMessage('Ověření DIČ nebylo možné: ' . $viesException->getMessage());
            $arrResult = [];
        } catch (ViesServiceException $viesServiceException) {
            $this->flashMessage('Ověření DIČ nebylo možné: ' . $viesServiceException->getMessage());
            $arrResult = [];
        }


        $this->sendJson($arrResult);
    }


    /*check if actual tariff enable new partner
     *
     */
    public function beforeNew()
    {

        if ($tmpUser = $this->UsersManager->find($this->getUser()->id)) {
            $tmpLicense = $this->DataManager->findAll()->count();
            //dump($tmpLicense);
            //die;
            if ($tmpLicense < $this->UserManager->trfRecords($this->getUser()->id)) {
                $result = TRUE;
            } else {
                $result = FALSE;
                $this->flashMessage($this->translator->translate('nepovoluje_více_dodavatelů_a_odběratelů'), 'danger');
            }
        }


        return $result;
    }


    protected function createComponentHeadersFooters($name)
    {
        $form = new Form($this, $name);

        $form->addTextArea('header_txt', 'Text v záhlaví:', 40, 7)
            ->setHtmlAttribute('placeholder', 'Text v záhlaví dokladů');
        $form->addTextArea('footer_txt', 'Text v zápatí:', 40, 7)
            ->setHtmlAttribute('placeholder', 'Text v zápatí dokladů');
        $form->addCheckbox('header_app', 'Přidávat za výchozí')
            ->setTranslator(NULL)
            ->setDefaultValue(FALSE)
            ->setHtmlAttribute('class', 'items-show');
        $form->addCheckbox('footer_app', 'Přidávat za výchozí')
            ->setTranslator(NULL)
            ->setDefaultValue(FALSE)
            ->setHtmlAttribute('class', 'items-show');

        $form->addSubmit('back', 'Zpět')
            ->setHtmlAttribute('class', 'btn btn-primary')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBackHF');
        $form->addSubmit('send', 'Uložit')->setHtmlAttribute('class', 'btn btn-primary');
        $form->onSuccess[] = array($this, 'SubmitHeadersFooters');
        return $form;
    }

    public function stepBackHF()
    {
        $this->flashMessage('Změny nebyly uloženy.', 'warning');
        $this->redrawControl('flash');
        $this->redrawControl('hf_snippet');
    }

    public function SubmitHeadersFooters(Form $form)
    {
        $dataForm = $form->values;
        if ($form['send']->isSubmittedBy()) {
            $dataForm['id'] = $this->id;
            //bdump($dataForm);
            $this->DataManager->update($dataForm, TRUE);
            //bdump('ted');
            $this->flashMessage('Změny byly uloženy.', 'success');

            //$this->redirect('Partners:default');
        }
        $this->redrawControl('flash');
        $this->redrawControl('hf_snippet');

    }

    public function handleFillPricelist()
    {
        $retVal = $this->PriceListPartnerManager->autoFill($this->id);
        $this->flashMessage('Do vlastního ceníku bylo přidáno ' . $retVal . ' položek.', 'success');
        $this['partnerPriceListGrid']->redrawControl('editLines');
    }

    public function handleEmptyPricelist()
    {
        $this->PriceListPartnerManager->findAll()->where('cl_partners_book_id = ?', $this->id)->delete();
        $this->flashMessage('Všechny položky vlastního ceníku byly odstraněny.', 'success');
        $this['partnerPriceListGrid']->redrawControl('editLines');
    }

    public function handleFillPricelistGroup()
    {
        $retVal = $this->PriceListPartnerGroupManager->autoFill($this->id);
        $this->flashMessage('Do skupin vlastního ceníku bylo přidáno ' . $retVal . ' skupin.', 'success');
        $this['pricelistPartnerGroupGrid']->redrawControl('editLines');
    }

    public function handleEmptyPricelistGroup()
    {
        $this->PriceListPartnerGroupManager->findAll()->where('cl_partners_book_id = ?', $this->id)->delete();
        $this->flashMessage('Všechny skupiny z vlastního ceníku byly odstraněny.', 'success');
        $this['pricelistPartnerGroupGrid']->redrawControl('editLines');
    }

    public function handleB2bPublic($value)
    {
        $tmpData = $this->DataManager->find($this->id);
        if ($tmpData) {
            $b2b_public = ($value != 1) ? 0 : 1;
            $b2b_key = ($value != 1) ? "" : \Nette\Utils\Random::generate(128, 'A-Za-z0-9');
            $tmpData->update(array('b2b_public' => $b2b_public, 'b2b_key' => $b2b_key));
        }
        $this->redrawControl('b2bPublicLink');
    }


    public function handleNotActiveSet()
    {
        //$this->PriceListManager->setNotActiveReset();

        $this->showModal('notActiveSet');
        $this->redrawControl('bscAreaEdit');
        $this->redrawControl('createDocs');
    }

    protected function createComponentNotActiveSetForm()
    {
        $form = new Form;

        $now = new DateTime();
        $form->addText('date_to', $this->translator->translate('Bez_použití_po'), 0, 16)
            ->setDefaultValue($now->format('d.m.Y'))
            ->setHtmlAttribute('placeholder', $this->translator->translate('Datum_použití'));

        $form->addSubmit('submit', $this->translator->translate('Nastavit_neaktivní'))
            ->setHtmlAttribute('title', $this->translator->translate('Nastaví_partnery_kteří_jsou_od_zadaného_data_bez_dokladů_jako_neaktivní'))
            ->setHtmlAttribute('class', 'form-control btn-success');
        $form->onSuccess[] = array($this, "NotActiveSetFormSubmited");
        return $form;
    }

    /**
     * ImportTrans form submitted
     * @param \Nette\Application\UI\Form
     * @return void
     */
    public function NotActiveSetFormSubmited($form)
    {
        $values = $form->getValues();
        if ($form['submit']->isSubmittedBy()) {
            if ($values['date_to'] == "") {
                $values['date_to'] = NULL;
            } else {
                $values['date_to'] = date('Y-m-d H:i:s', strtotime($values['date_to']) + 86400 - 10);
            }
            $arrRet = $this->DataManager->setNotActive($values);
            $this->flashMessage($this->translator->translate('Aktivních_je_nyní') . $arrRet['active'] . ' ' . $this->translator->translate('neaktivních') . $arrRet['notactive'], 'success');

            $this->hideModal('notActiveSet');
            $this->redrawControl('flash');
            $this->redrawControl('content');

            //$this->redrawControl('content');
        }
    }

    protected function createComponentSalesGraph()
    {
        $tmpshowData['sales'] = $this->DataManager->findAll()->where('cl_partners_book.id = ?', $this->id)->select('DISTINCT DATE(CONCAT(YEAR(:cl_invoice.inv_date),"/",MONTH(:cl_invoice.inv_date),"/","01")) AS doc_date2, SUM(:cl_invoice.price_e2) AS price_e2')
            ->where(':cl_invoice.inv_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)')
            ->order('doc_date2 DESC')
            ->group('doc_date2')->limit(12)->fetchPairs('doc_date2', 'price_e2');

        $showDataPricelist = array();
        foreach ($tmpshowData['sales'] as $key => $one) {
            $showDataPricelist[] = array($key, $one);
        }

        $showData['sales'] = json_encode($showDataPricelist);
        $name = $this->translator->translate("Faktury_vydané");
        //$translator = clone $this->translator;
        //$translator->setPrefix([]);
        return new GraphControl($this->translator, $showData, $name, 'graphSales', 'pricelist.latte');
    }

    protected function createComponentSalesArrivedGraph()
    {
        $tmpshowData['sales'] = $this->DataManager->findAll()->where('cl_partners_book.id = ?', $this->id)->select('DISTINCT DATE(CONCAT(YEAR(:cl_invoice_arrived.inv_date),"/",MONTH(:cl_invoice_arrived.inv_date),"/","01")) AS doc_date2, SUM(:cl_invoice_arrived.price_e2) AS price_e2')
            ->where(':cl_invoice_arrived.inv_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)')
            ->order('doc_date2 DESC')
            ->group('doc_date2')->limit(12)->fetchPairs('doc_date2', 'price_e2');

        $showDataPricelist = array();
        foreach ($tmpshowData['sales'] as $key => $one) {
            $showDataPricelist[] = array($key, $one);
        }

        $showData['sales'] = json_encode($showDataPricelist);
        $name = $this->translator->translate("Faktury_přijaté");
        //$translator = clone $this->translator;
        //$translator->setPrefix([]);
        return new GraphControl($this->translator, $showData, $name, 'graphArrivedSales', 'pricelist.latte');
    }

    public function handleShowPartDoc()
    {
        $this->redrawControl('partdoc');
    }


}
