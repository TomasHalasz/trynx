<?php

namespace App\ApplicationModule\Presenters;

use Contributte\Translation\PrefixedTranslator;
use Nette\Application\UI\Form;
use Nette\Application\UI\Control;
use Nette,
	App\Model;
use Tracy\Debugger;

class InvoicesControl extends Control
{

    private $showData,$displayName,$templateFile;
    private $mode = 'worst';  //min - for quantity under minimum, req - for quantity under required

    /** @var \App\Model\InvoiceManager*/
    private $InvoiceManager;
	
	/** @var \App\Model\Base*/
	private $ArraysManager;

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;



    public function __construct($showData, $displayName, $templateFile, \App\Model\Base $invoiceManager, Nette\Localization\Translator $translator)
    {
       // parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
        $this->showData = $showData;
        $this->displayName = $displayName;
        $this->templateFile = $templateFile;
        $this->InvoiceManager = $invoiceManager;
        $this->translator = $translator;
	
    }        
    
    public function render()
    {
        $this->template->setFile(__DIR__ . '/' . $this->templateFile);
        //dump($this->showData);
        $this->template->data = $this->showData;
        $tmpPrice = ($this->presenter->settings['platce_dph'] == 1) ? 'price_e2_vat' : 'price_e2';
        if ($this->showData['invoices'] ){
            if ($this->mode == "worst"){
                $tmpData = $this->InvoiceManager->findAll()->
                                select('cl_partners_book.company AS company, SUM((price_e2_vat - price_payed) * currency_rate) AS price_order, 
                                        SUM(price_payed * currency_rate) AS price_payed, SUM(price_e2_vat * currency_rate) AS price_e2_vat, SUM(price_e2 * currency_rate) AS price_e2, cl_partners_book.cl_company_id')->
                                where('price_e2 > 0 AND cl_invoice.due_date < NOW() AND pay_date IS NULL AND storno = 0')->
                                order('price_order DESC')->
                                limit(50)->
                                group('cl_partners_book.id');
            }elseif ($this->mode == "oldest"){
                $tmpData = $this->InvoiceManager->findAll()->
                                select('cl_partners_book.company AS company, SUM(price_payed * currency_rate) AS price_payed, inv_number, cl_invoice.due_date AS due_date, price_e2_vat, price_e2, cl_invoice.cl_currencies_id')->
                                where('price_e2 > 0 AND cl_invoice.due_date < NOW() AND pay_date IS NULL AND storno = 0')->
                                order('due_date ASC')->
                                limit(50)->
                                group('cl_partners_book_id');
            }else{
                $tmpData = [];
            }
            $tmpTotal = $this->InvoiceManager->findAll()->
                            select('SUM(price_payed * currency_rate) AS price_payed, SUM(price_e2_vat * currency_rate) AS price_e2_vat, SUM(price_e2 * currency_rate) AS price_e2')->
                            where('price_e2 > 0 AND due_date < NOW() AND pay_date IS NULL AND storno = 0')->fetch();

            $this->template->totalSum = $tmpTotal[$tmpPrice] - $tmpTotal['price_payed'];
            $this->template->totalCurrencyCode = $this->presenter->settings->cl_currencies['currency_code'];
            $this->template->dataInvoices = $tmpData;

        }elseif ($this->showData['invoicearrived']){
            if ($this->mode == "worst"){
                $tmpData = $this->InvoiceManager->findAll()->
                                select('cl_partners_book.company AS company, SUM((price_e2_vat - price_payed) * currency_rate) AS price_order, 
                                                        SUM(price_payed * currency_rate) AS price_payed, SUM(price_e2_vat * currency_rate) AS price_e2_vat, SUM(price_e2 * currency_rate) AS price_e2, cl_partners_book.cl_company_id')->
                                where('price_e2 > 0 AND cl_invoice_arrived.due_date < NOW() AND pay_date IS NULL')->
                                order('price_order DESC')->
                                limit(50)->
                                group('cl_partners_book.id');
            }elseif ($this->mode == "oldest"){
                $tmpData = $this->InvoiceManager->findAll()->
                                select('cl_partners_book.company AS company, SUM(price_payed * currency_rate) AS price_payed, inv_number, cl_invoice_arrived.due_date AS due_date, price_e2_vat, price_e2, cl_invoice_arrived.cl_currencies_id')->
                                where('price_e2 > 0 AND cl_invoice_arrived.due_date < NOW() AND pay_date IS NULL')->
                                order('due_date ASC')->
                                limit(50)->
                                group('cl_partners_book_id');
            }else{
                $tmpData = [];
            }
            $tmpTotal = $this->InvoiceManager->findAll()->
                                select('SUM(price_payed * currency_rate) AS price_payed, SUM(price_e2_vat * currency_rate) AS price_e2_vat, SUM(price_e2 * currency_rate) AS price_e2')->
                                where('price_e2 > 0 AND due_date < NOW() AND pay_date IS NULL')->fetch();

            $this->template->totalSum = $tmpTotal[$tmpPrice] - $tmpTotal['price_payed'];
            $this->template->totalCurrencyCode = $this->presenter->settings->cl_currencies['currency_code'];
            $this->template->dataInvoices = $tmpData;
        }else{
            $this->template->dataInvoices = [];
        }
        $this->template->mode = $this->mode;
        $this->template->displayName = $this->displayName;
        $this->template->settings = $this->presenter->settings;
        $this->template->render();
    }


    public function handleChangeMode($mode = 'min')
    {
        $this->mode = $mode;
        $this->redrawControl('orderscontent');
    }
       

}