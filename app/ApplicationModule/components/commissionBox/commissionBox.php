<?php

namespace App\ApplicationModule\Presenters;

use Contributte\Translation\PrefixedTranslator;
use Nette\Application\UI\Form;
use Nette\Application\UI\Control;
use Nette,
	App\Model;
use Tracy\Debugger;

class CommissionBoxControl extends Control
{
    private $showData,$displayName,$templateFile;

    /** @var \App\Model\CommissionManager*/
    private $CommissionManager;
	
	/** @var \App\Model\Base*/
	private $ArraysManager;

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;



    public function __construct($showData, $displayName, $templateFile, \App\Model\Base $commissionManager, Nette\Localization\Translator $translator)
    {
       // parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
        $this->showData = $showData;
        $this->displayName = $displayName;
        $this->templateFile = $templateFile;
        $this->CommissionManager = $commissionManager;
        $this->translator = $translator;
	
    }        
    
    public function render()
    {
        $this->template->setFile(__DIR__ . '/' . $this->templateFile);
        $this->template->data = $this->showData;
        $tmpPrice = ($this->presenter->settings['platce_dph'] == 1) ? 'price_e2_vat' : 'price_e2';

        $tmpData = $this->CommissionManager->findAll()->
                        select('cl_partners_book.company AS company, start_date, cl_commission.cm_title, cl_commission.cm_number, cl_commission.cl_currencies_id, cl_commission.id')->
                        where('(start_date = DATE(NOW()) OR :cl_b2b_order.cl_commission_id IS NOT NULL) AND cl_status.s_fin != 1')->
                        order('cl_partners_book.company ASC')->
                        limit(50);

        $this->template->totalCurrencyCode = $this->presenter->settings->cl_currencies['currency_code'];
        $this->template->dataCommission = $tmpData;

        $this->template->displayName = $this->displayName;
        $this->template->settings = $this->presenter->settings;
        $this->template->render();
    }




}