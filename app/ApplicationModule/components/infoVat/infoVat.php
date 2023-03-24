<?php

namespace App\ApplicationModule\Presenters;

use Contributte\Translation\PrefixedTranslator;
use Nette\Application\UI\Form;
use Nette\Application\UI\Control;
use Nette,
	App\Model;
use Tracy\Debugger;

class InfoVatControl extends Control
{

    private $showData,$displayName,$templateFile;
    private $fromDate = null, $toDate = null;


    /** @var \App\Model\InvoiceManager*/
    private $InvoiceManager;

    /** @var \App\Model\InvoiceArrivedManager*/
    private $InvoiceArrivedManager;

    /** @var \App\Model\SaleManager*/
    private $SaleManager;

	/** @var \App\Model\Base*/
	private $ArraysManager;

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;



    public function __construct($showData, $displayName, $templateFile, \App\Model\InvoiceManager $invoiceManager,\App\Model\InvoiceArrivedManager $invoiceArrivedManager,
                                \App\Model\SaleManager $saleManager, Nette\Localization\Translator $translator)
    {
       // parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
        $this->showData = $showData;
        $this->displayName = $displayName;
        $this->templateFile = $templateFile;
        $this->SaleManager = $saleManager;
        $this->InvoiceManager = $invoiceManager;
        $this->InvoiceArrivedManager = $invoiceArrivedManager;
        $this->translator = $translator;
	
    }        
    
    public function render()
    {
        $this->template->setFile(__DIR__ . '/' . $this->templateFile);
        //dump($this->showData);
        $this->template->data = $this->showData;
        $this->template->displayName = $this->displayName;
        $this->template->settings = $this->presenter->settings;
        $this->template->totalSum = 0;
        $this->template->totalCurrencyCode = $this->presenter->settings->cl_currencies['currency_code'];

        if ($this->fromDate == null || $this->toDate == null) {
            $wrkPeriod = $this->presenter->user->getIdentity()->work_period;
            if (is_null($wrkPeriod)){
                $wrkPeriod = 'cq1';
            }
            $wrkYear = new Nette\Utils\DateTime();
            $arrPeriod = $this->getPeriod($wrkPeriod, $wrkYear->format('Y'));
            $this->fromDate = $arrPeriod['from'];
            $this->toDate = $arrPeriod['to'];
        }
        bdump($this->fromDate);
        bdump($this->toDate);

        $tmpVat = $this->getVatRates();
        $this->template->vatRates = $tmpVat;
        $prices = [];

        bdump($tmpVat);
        foreach ($tmpVat as $key => $one) {
            bdump($one);
            $tmpPrice1 = $this->InvoiceManager->findAll()->where('vat_date >=? AND vat_date <=? AND vat1 = ?', $this->fromDate, $this->toDate, $one)->
                                            select('SUM(price_vat1) AS price_vat, SUM(price_base1) AS price_base')->fetch();
            $tmpPrice2 = $this->InvoiceManager->findAll()->where('vat_date >=? AND vat_date <=? AND vat2 = ?', $this->fromDate, $this->toDate, $one)->
                                            select('SUM(price_vat2) AS price_vat, SUM(price_base2) AS price_base')->fetch();
            $tmpPrice3 = $this->InvoiceManager->findAll()->where('vat_date >=? AND vat_date <=? AND vat3 = ?', $this->fromDate, $this->toDate, $one)->
                                            select('SUM(price_vat3) AS price_vat, SUM(price_base3) AS price_base')->fetch();

            $tmpSale1 = $this->SaleManager->findAll()->where('vat_date >=? AND vat_date <=? AND vat1 = ?', $this->fromDate, $this->toDate, $one)->
                                            select('SUM(price_vat1) AS price_vat, SUM(price_base1) AS price_base')->fetch();
            $tmpSale2 = $this->SaleManager->findAll()->where('vat_date >=? AND vat_date <=? AND vat2 = ?', $this->fromDate, $this->toDate, $one)->
                                            select('SUM(price_vat2) AS price_vat, SUM(price_base2) AS price_base')->fetch();
            $tmpSale3 = $this->SaleManager->findAll()->where('vat_date >=? AND vat_date <=? AND vat3 = ?', $this->fromDate, $this->toDate, $one)->
                                            select('SUM(price_vat3) AS price_vat, SUM(price_base3) AS price_base')->fetch();

            $tmpOut1 = $this->InvoiceArrivedManager->findAll()->where('vat_date >=? AND vat_date <=? AND vat1 = ?', $this->fromDate, $this->toDate, $one)->
                                            select('SUM(price_vat1) AS price_vat, SUM(price_base1) AS price_base')->fetch();
            $tmpOut2 = $this->InvoiceArrivedManager->findAll()->where('vat_date >=? AND vat_date <=? AND vat2 = ?', $this->fromDate, $this->toDate, $one)->
                                            select('SUM(price_vat2) AS price_vat, SUM(price_base2) AS price_base')->fetch();
            $tmpOut3 = $this->InvoiceArrivedManager->findAll()->where('vat_date >=? AND vat_date <=? AND vat3 = ?', $this->fromDate, $this->toDate, $one)->
                                            select('SUM(price_vat3) AS price_vat, SUM(price_base3) AS price_base')->fetch();

            $prices[$one] = ['in_price_base' => $tmpPrice1['price_base'] + $tmpPrice2['price_base'] + $tmpPrice3['price_base'] +
                                                $tmpSale1['price_base'] + $tmpSale2['price_base'] + $tmpSale3['price_base'],
                                'in_price_vat' => $tmpPrice1['price_vat'] + $tmpPrice2['price_vat'] + $tmpPrice3['price_vat'] +
                                                    $tmpSale1['price_vat'] + $tmpSale2['price_vat'] + $tmpSale3['price_vat'],
                                'out_price_base' => $tmpOut1['price_base'] + $tmpOut2['price_base'] + $tmpOut3['price_base'],
                                'out_price_vat' => $tmpOut1['price_vat'] + $tmpOut2['price_vat'] + $tmpOut3['price_vat']
                            ];
        }
        bdump($prices);
        $this->template->prices = $prices;
        $this->template->render();
    }

    private function getVatRates(){
        $tmpVat = $this->InvoiceManager->findAll()->where('vat_date >=? AND vat_date <=? AND cl_company.platce_dph = 1', $this->fromDate, $this->toDate)->
                        select('vat1, vat2, vat3');
        $arrRet = [];
        foreach($tmpVat as $key => $one){
            $arrRet[$one['vat1']] = $one['vat1'];
            $arrRet[$one['vat2']] = $one['vat2'];
            $arrRet[$one['vat3']] = $one['vat3'];
        }

        $tmpVat = $this->InvoiceArrivedManager->findAll()->where('vat_date >=? AND vat_date <=? AND cl_company.platce_dph = 1', $this->fromDate, $this->toDate)->
                        select('vat1, vat2, vat3');
        foreach($tmpVat as $key => $one){
            $arrRet[$one['vat1']] = $one['vat1'];
            $arrRet[$one['vat2']] = $one['vat2'];
            $arrRet[$one['vat3']] = $one['vat3'];
        }

        $tmpVat = $this->SaleManager->findAll()->where('vat_date >=? AND vat_date <=?  AND cl_company.platce_dph = 1', $this->fromDate, $this->toDate)->
                    select('vat1, vat2, vat3');
        foreach($tmpVat as $key => $one){
            $arrRet[$one['vat1']] = $one['vat1'];
            $arrRet[$one['vat2']] = $one['vat2'];
            $arrRet[$one['vat3']] = $one['vat3'];
        }

        rsort($arrRet);
        return $arrRet;
    }


    protected function createComponentEdit($name)
    {
        $form = new Form($this, $name);

        $arrPeriod = [
                        'cq1' => $this->translator->translate('aktuální_čtvrtletí'),
                        'q01' => $this->translator->translate('1_čtvrtletí'),
                        'q02' => $this->translator->translate('2_čtvrtletí'),
                        'q03' => $this->translator->translate('3_čtvrtletí'),
                        'q04' => $this->translator->translate('4_čtvrtletí'),
                        'y01' => $this->translator->translate('Celý_rok'),
                        'm01' => $this->translator->translate('Leden'),
                        'm02' => $this->translator->translate('Únor'),
                        'm03' => $this->translator->translate('Březen'),
                        'm04' => $this->translator->translate('Duben'),
                        'm05' => $this->translator->translate('Květen'),
                        'm06' => $this->translator->translate('Červen'),
                        'm07' => $this->translator->translate('Červenec'),
                        'm08' => $this->translator->translate('Srpen'),
                        'm09' => $this->translator->translate('Září'),
                        'm10' => $this->translator->translate('Říjen'),
                        'm11' => $this->translator->translate('Listopad'),
                        'm12' => $this->translator->translate('Prosinec')
                    ];
        $tmpNow = new Nette\Utils\DateTime();
        $i = $tmpNow->format('Y');
        while($i > 2010){
            $arrYear[$i] = $i;
            $i--;
        }

        $period = $this->presenter->user->getIdentity()->work_period;
        $period = ($period == '' ? 'cq1' : $period);
        $form->addSelect('period', $this->translator->translate("Období:"), $arrPeriod)
            ->setDefaultValue($period)
            ->setPrompt($this->translator->translate('Zvolte_období'));
        $form->addSelect('year', $this->translator->translate("Rok:"), $arrYear)
            ->setDefaultValue($tmpNow->format('Y'))
            ->setPrompt($this->translator->translate('Zvolte_rok'));

        $form->addSubmit('send', $this->translator->translate('Zobrazit'))->setHtmlAttribute('class','btn btn-success');
        $form->onSuccess[] = [$this, 'SubmitEditSubmitted'];
        return $form;
    }


    public function SubmitEditSubmitted(Form $form)
    {
        $data=$form->values;
        if ($form['send']->isSubmittedBy())
        {
            $arrPeriod = $this->getPeriod($data['period'], $data['year']);
            $this->fromDate = $arrPeriod['from'];
            $this->toDate = $arrPeriod['to'];
            $this->presenter->user->getIdentity()->work_period = $data['period'];
            $this->presenter->UserManager->updateUser(['id' => $this->presenter->user->getId(), 'work_period' => $data['period']]);
        }
        $this->redrawControl('infoVatContent');
    }


    private function getPeriod($period, $year){
        $period = (is_null($period) ? 'cq1' : $period);
        $period = ($period == '' ? 'cq1' : $period);
        $arr = [
                'cq1' => [],
                'q01' => ['from' => $year.'-01-01', 'to' => $year.'-03-31'],
                'q02' => ['from' => $year.'-04-01', 'to' => $year.'-06-30'],
                'q03' => ['from' => $year.'-07-01', 'to' => $year.'-09-30'],
                'q04' => ['from' => $year.'-10-01', 'to' => $year.'-12-31'],
                'm01' => ['from' => $year.'-01-01', 'to' => $year.'-01-31'],
                'm02' => ['from' => $year.'-02-01', 'to' => $year.'-02-28'],
                'm03' => ['from' => $year.'-03-01', 'to' => $year.'-03-31'],
                'm04' => ['from' => $year.'-04-01', 'to' => $year.'-04-30'],
                'm05' => ['from' => $year.'-05-01', 'to' => $year.'-05-31'],
                'm06' => ['from' => $year.'-06-01', 'to' => $year.'-06-30'],
                'm07' => ['from' => $year.'-07-01', 'to' => $year.'-07-31'],
                'm08' => ['from' => $year.'-08-01', 'to' => $year.'-08-31'],
                'm09' => ['from' => $year.'-09-01', 'to' => $year.'-09-30'],
                'm10' => ['from' => $year.'-10-01', 'to' => $year.'-10-31'],
                'm11' => ['from' => $year.'-11-01', 'to' => $year.'-11-30'],
                'm12' => ['from' => $year.'-12-01', 'to' => $year.'-12-31'],
                'y01' => ['from' => $year.'-01-01', 'to' => $year.'-12-31'],
            ];
        $tmpDate =  date('L', strtotime($arr['m02']['from']));
        if ($tmpDate == 1)
            $arr['m02'] = ['from' => $year.'-02-01', 'to' => $year.'-02-29'];

        $tmpNow = new Nette\Utils\DateTime();
        $tmpYear = $tmpNow->format('Y');
        $month = $tmpNow->format('m');
        if ($month >= 1 && $month <= 3)
            $arr['cq1'] = ['from' => $tmpYear. '-01-01', 'to' => $tmpYear.'-03-31'];
        elseif ($month >= 4 && $month <= 6)
            $arr['cq1'] = ['from' => $tmpYear. '-04-01', 'to' => $tmpYear.'-06-30'];
        elseif ($month >= 7 && $month <= 9)
            $arr['cq1'] = ['from' => $tmpYear. '-07-01', 'to' => $tmpYear.'-09-30'];
        else
            $arr['cq1'] = ['from' => $tmpYear. '-10-01', 'to' => $tmpYear.'-12-31'];


        return $arr[$period];
    }

}