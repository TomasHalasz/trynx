<?php

namespace MainServices;



//require_once __DIR__ . '/helpers.php';
use FilipSedivy\EET\Certificate;
use FilipSedivy\EET\Dispatcher;
use FilipSedivy\EET\Exceptions\Certificate\CertificateNotFoundException;
use FilipSedivy\EET\Exceptions\EET\ClientException;
use FilipSedivy\EET\Exceptions\EET\ErrorException;
use FilipSedivy\EET\Exceptions\EetException;
use FilipSedivy\EET\Receipt;
use Nette\Utils\DateTime;
use Ramsey\Uuid\Uuid;
use Tracy\Debugger;




/**
 * EET service
 *
 * @author     Tomas Halasz
 * @package    Klienti
 * @property-read \SystemContainer $context
 */
class EETService {

    /** @var \App\Model\CompaniesManager*/
    private $CompaniesManager;

    /** @var \App\Model\EETManager*/
    private $EETManager;

    /** @var \App\Model\RatesVatManager*/
    private $RatesVatManager;

    /** @var Nette\Database\Context */
    public $user;

    public $parameters;
    public $settings;

    function __construct( \App\Model\CompaniesManager $companiesManager, \App\Model\EETManager $eetManager,
                          \App\Model\RatesVatManager $RatesVatManager, \Nette\Security\User $user, \Nette\DI\Container $container)
    {

        $this->CompaniesManager = $companiesManager;
        $this->EETManager 		= $eetManager;
        $this->RatesVatManager	= $RatesVatManager;
        $this->user 			= $user;
        $this->parameters 		= $container->getParameters();
    }


    public function sendEET($tmpData, $dataForSign)
    {
        $dataToSend = $this->prepareDataToSend($tmpData);
        $this->settings = $this->CompaniesManager->getTable()->fetch();
        $dataFolder = $this->CompaniesManager->getDataFolder($this->settings->id);
        $company = $this->CompaniesManager->find($this->settings->id);

        try {
            //bdump($dataFolder . '/pfx/' . $dataForSign['eet_pfx']);
            $certificate = new Certificate($dataFolder . '/pfx/' . $dataForSign['eet_pfx'], $dataForSign['eet_pass']);
        }
        catch (\Exception $e)
        {
            throw new \Exception($e->getMessage());
        }

        //bdump($this->parameters['eet_mode']);
        if ($this->parameters['eet_mode'] == "Production") {
            $dispatcher = new Dispatcher($certificate, Dispatcher::PRODUCTION_SERVICE);

        }else {
            $dispatcher = new Dispatcher($certificate, Dispatcher::PLAYGROUND_SERVICE);

        }
        //$dispatcher->setProductionService();

        // Vytvoření receiptu
        $r = new Receipt;
        $r->uuid_zpravy     = Uuid::uuid4()->toString();
        $r->id_provoz       = $dataForSign['eet_id_provoz'];
        $r->id_pokl         = $dataForSign['eet_id_poklad'];
        $r->dic_popl        = $dataForSign['dic_popl'];
        //$r->dic_popl        = 'CZ25398989';
        $r->porad_cis       = $dataToSend['porad_cis'];
        $r->dat_trzby       = $dataToSend['dat_trzby'];
        $r->celk_trzba      = $dataToSend['celk_trzba'];


        if ($dataToSend['prvni_zaslani'] == 0){
            $r->prvni_zaslani   = false;
        }
        $r->rezim = 0;
        if ($tmpData['vat_active'] == 1) {
            $r->zakl_nepodl_dph = $dataToSend['zakl_nepodl_dph'];
            $r->zakl_dan1 = $dataToSend['zakl_dan1'];
            $r->dan1 = $dataToSend['dan1'];
            $r->zakl_dan2 = $dataToSend['zakl_dan2'];
            $r->dan2 = $dataToSend['dan2'];
            $r->zakl_dan3 = $dataToSend['zakl_dan3'];
            $r->dan3 = $dataToSend['dan3'];
            if ($dataToSend['cest_sluz'] != 0)
                $r->cest_sluz = $dataToSend['cest_sluz'];

            if ($dataToSend['pouzit_zboz1'] != 0 || $dataToSend['pouzit_zboz2'] != 0 || $dataToSend['pouzit_zboz3'] != 0) {
                $r->pouzit_zboz1 = $dataToSend['pouzit_zboz1'];
                $r->pouzit_zboz2 = $dataToSend['pouzit_zboz2'];
                $r->pouzit_zboz3 = $dataToSend['pouzit_zboz3'];
            }
        }
        if ($dataToSend['urceno_cerp_zuct'] != 0)
            $r->urceno_cerp_zuct = $dataToSend['urceno_cerp_zuct'];
        if ($dataToSend['cerp_zuct'] != 0)
            $r->cerp_zuct = $dataToSend['cerp_zuct'];


        $arrRet = array();
        $arrRet['id']           = $dataToSend['id'];
        $arrRet['UUID']         = $r->uuid_zpravy;
        $arrRet['FIK']          = "";
        $arrRet['BKP']          = "";
        $arrRet['PKP']          = "";
        $arrRet['Error']        = "";
        $arrRet['Warnings']     = "";
        $arrRet['eet_id']       = $dataForSign['eet_id_provoz'];
        $arrRet['eet_idpokl']   = $dataForSign['eet_id_poklad'];
        $arrRet['dat_trzby']    = $dataToSend['dat_trzby'];
        try
        {
            //throw new ClientException($r, null, null, new \Exception());

            if ($company->eet_test == 1){
                $dispatcher->check($r);
            }else {
                $dispatcher->send($r);
            }
            $arrRet['FIK'] = $dispatcher->getFik();
            $arrRet['BKP'] = $dispatcher->getBkp();
            $arrRet['PKP'] = $dispatcher->getPkp();
            $arrRet['Warnings'] = $dispatcher->getWarnings();
            bdump($r,'r');
            bdump($arrRet,'EET ret');
            //Debugger::log($arrRet['PKP']);
            return $arrRet;
        }
            //catch (FilipSedivy\EET\Exceptions\EET\ClientException $e)
        catch (ClientException $e)
        {
            $arrRet['Error'] = $e->getMessage();
            $arrRet['BKP'] = $dispatcher->getBkp();
            $arrRet['PKP'] = $dispatcher->getPkp();
            $arrRet['Warnings'] = array();
            return $arrRet;
        }
        catch (ErrorException $e)
        {
            //bdump($e->getMessage(), 'Error: ');
            $arrRet = array();
            $arrRet['FIK'] =  '';
            $arrRet['BKP'] =  '';
            $arrRet['PKP'] = '';
            $arrRet['Error'] = $e->getMessage();
            $arrRet['Warnings'][] = [
                'code' => 999,
                'message' => 'Chyba v certifikátu, zkontrolujte platnost a heslo.'
            ];
            return $arrRet;
        }
    }


    private function prepareDataToSend($tmpData)
    {
        $arrRet = array();
        if (isset($tmpData['sale_number'])){
            $arrRet['porad_cis'] = $tmpData['sale_number'];
        }elseif (isset($tmpData['inv_number'])){
            $arrRet['porad_cis'] = $tmpData['inv_number'];
        }
        //23.05.2019 - check if it is first try
        if ( !is_null($tmpData->cl_eet_id) && $tmpData->cl_eet->fik != '' && $tmpData->cl_eet->pkp != '' && $tmpData->cl_eet->bkp != ''){
            $arrRet['dat_trzby']        = $tmpData->cl_eet['dat_eet'];
            $arrRet['bkp']              = $tmpData->cl_eet['bkp'];
            $arrRet['pkp']              = base64_encode($tmpData->cl_eet['pkp']);
            $arrRet['prvni_zaslani']    = 0;
            $arrRet['id']               = $tmpData->cl_eet_id;
        }else{
            //first try to send EET => set dat_trzby do now
            $arrRet['dat_trzby']        = new \DateTime();
            $arrRet['prvni_zaslani']    = 1;
            $arrRet['id']               = NULL;
        }
        if ($tmpData['currency_rate'] == 0)
            $tmpData['currency_rate'] == 1;

        if ($tmpData['vat_active'] == 0) {
            $arrRet['celk_trzba'] = $tmpData['price_e2'] * $tmpData['currency_rate']; //neplatce DPH
        }else {
            //platce DPH
            $arrRet['celk_trzba']       = $tmpData['price_e2_vat'] * $tmpData['currency_rate'];
            $arrRet['cest_sluz']        = 0;
            $arrRet['zakl_dan1']		= 0;
            $arrRet['zakl_dan2']		= 0;
            $arrRet['zakl_dan3']		= 0;
            $arrRet['dan1']				= 0;
            $arrRet['dan2']				= 0;
            $arrRet['dan3']				= 0;
            $arrRet['zakl_nepodl_dph'] 	= 0;

            $RatesVatValid = $this->RatesVatManager->findAllValid($tmpData->vat_date);
            foreach($RatesVatValid as $key => $one)
            {
                /// 21.02.2020 - * $tmpData['currency_rate']  remove, because price_base is allready multiplied with currency_rate
                if ($one['rates'] == 0 && $tmpData['price_base0'] != 0){
                    $arrRet['zakl_nepodl_dph']  = $tmpData['price_base0'] ;

                }elseif ($one['rates'] == 0 && $tmpData['vat1'] == $one['rates']){
                    $arrRet['zakl_nepodl_dph']  = $tmpData['price_base1'];

                }elseif ($one['rates'] == 0 && $tmpData['vat2'] == $one['rates']){
                    $arrRet['zakl_nepodl_dph']  = $tmpData['price_base2'];

                }elseif ($one['rates'] == 0 && $tmpData['vat3'] == $one['rates']) {
                    $arrRet['zakl_nepodl_dph'] 	= $tmpData['price_base3'];
                }

                if ($one['rates'] == 21 && $tmpData['vat1'] == $one['rates']){
                    $arrRet['zakl_dan1']	= $tmpData['price_base1'];
                    $arrRet['dan1']			= $tmpData['price_vat1'];

                }elseif ($one['rates'] == 21 && $tmpData['vat2'] == $one['rates']){
                    $arrRet['zakl_dan1']  	= $tmpData['price_base2'];
                    $arrRet['dan1']        	= $tmpData['price_vat2'];

                }elseif ($one['rates'] == 21 && $tmpData['vat3'] == $one['rates']) {
                    $arrRet['zakl_dan1'] 	= $tmpData['price_base3'];
                    $arrRet['dan1']         = $tmpData['price_vat3'];
                }

                if ($one['rates'] == 15 && $tmpData['vat1'] == $one['rates']){
                    $arrRet['zakl_dan2']	= $tmpData['price_base1'];
                    $arrRet['dan2']			= $tmpData['price_vat1'];

                }elseif ($one['rates'] == 15 && $tmpData['vat2'] == $one['rates']){
                    $arrRet['zakl_dan2']  	= $tmpData['price_base2'];
                    $arrRet['dan2']        	= $tmpData['price_vat2'];

                }elseif ($one['rates'] == 15 && $tmpData['vat3'] == $one['rates']) {
                    $arrRet['zakl_dan2'] 	= $tmpData['price_base3'];
                    $arrRet['dan2']         = $tmpData['price_vat3'];
                }

                if ($one['rates'] == 10 && $tmpData['vat1'] == $one['rates']){
                    $arrRet['zakl_dan3']	= $tmpData['price_base1'];
                    $arrRet['dan3']			= $tmpData['price_vat1'];

                }elseif ($one['rates'] == 10 && $tmpData['vat2'] == $one['rates']){
                    $arrRet['zakl_dan3']  	= $tmpData['price_base2'];
                    $arrRet['dan3']        	= $tmpData['price_vat2'];

                }elseif ($one['rates'] == 10 && $tmpData['vat3'] == $one['rates']) {
                    $arrRet['zakl_dan3'] 	= $tmpData['price_base3'];
                    $arrRet['dan3']         = $tmpData['price_vat3'];
                }
            }


            /*$arrRet['zakl_dan1']        = $tmpData['price_base1'] * $tmpData['currency_rate'];
            $arrRet['zakl_dan2']        = $tmpData['price_base2'] * $tmpData['currency_rate'];
            $arrRet['zakl_dan3']        = $tmpData['price_base3'] * $tmpData['currency_rate'];
            $arrRet['dan1']             = $tmpData['price_vat1'] * $tmpData['currency_rate'];
            $arrRet['dan2']             = $tmpData['price_vat2'] * $tmpData['currency_rate'];
            $arrRet['dan3']             = $tmpData['price_vat3'] * $tmpData['currency_rate'];*/

        }
        $arrRet['urceno_cerp_zuct'] = 0;
        $arrRet['cerp_zuct']        = 0;
        $arrRet['pouzit_zboz1']     = 0;
        $arrRet['pouzit_zboz2']     = 0;
        $arrRet['pouzit_zboz3']     = 0;
        //TODO: dořešit EET a záloh
        //TODO: dořešit EET u použitého zboží

        return $arrRet;
    }

}

