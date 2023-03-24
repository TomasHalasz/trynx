<?php

/* 
 * KlientiCZ
 * Tomáš Halász - 2H C.S. s.r.o.
 * 30.6.2016 - 21:08:15
 * 
 */

namespace App\ApplicationModule\Presenters;


use Nette\Forms\Container;
use Nette\Application\UI\Form;

//use Markette\Gopay;
//use Markette\GopayInline\Client;
//use Markette\GopayInline\Api\Lists\Scope;
		
use Nette\Mail\Message,
    Nette\Utils\Strings,
    Nette\Utils\Html;
use Nette\Mail\SendmailMailer;
/**
 * User Order presenter.
 */
class UserOrderPresenter extends \App\Presenters\BaseAppPresenter
{

    private $order,$itemAction;

    private $arrServiceId,$priceList,$priceListMulti,$priceListNames,$discount,$mySet,$arrServiceList,$arrServicePrice;
	


    /**
    * @inject
    * @var \App\Model\ArraysManager
    */
    public $ArraysManager;   	
	
    /**
    * @inject
    * @var \App\Model\CurrenciesManager
    */
    public $CurrenciesManager;

    /**
    * @inject
    * @var \App\Model\UserManager
    */
    public $UserManager;    
	
    /**
    * @inject
    * @var \App\Model\UsersLicenseManager
    */
    public $UsersLicenseManager;        	
	
    /**
    * @inject
    * @var \App\Model\EmailingManager
    */
    public $EmailingManager;

    protected function startup()
    {
        parent::startup();
        //$this->translator->setPrefix(['applicationModule.UserOrder']);
    }
	public function renderDefault($type, $tariff = 1, $total_users = 1, $total_duration = 1, $newOrder = FALSE,$modal = FALSE)
	{
	    $this->template->modal = $modal;
        $arrDef = array();
		//$arrDef['tariff_type'] = $tariff;
		//$arrDef['total_users'] = $total_users;
		//$arrDef['total_duration'] = $total_duration;
		
		//$tmpAmount = $this->getAmount($tariff, $total_users, $total_duration);
		//$arrDef['amount'] = $tmpAmount['amount'];
		//$arrDef['amount_total'] = $tmpAmount['amount_total'];
		//$arrDef['discount'] = $tmpAmount['discount'];
		if ($type == "new") {
			$arrDef['total_duration'] = 12;
			$arrDef['payment_type'] = 0; // 0 - převod 1 - gopay
			$arrDef['currency'] = 'CZK';
		}elseif ($type == "repeat") {
			$tmpLicense = $this->UsersLicenseManager->findAll()->order('created DESC')->limit(1)->fetch();
			if ($tmpLicense){
				$arrDef['total_duration'] = $tmpLicense['total_duration'];
				$arrDef['payment_type'] =  $tmpLicense['payment_type']; // 0 - převod 1 - gopay
				$arrDef['currency'] =  $tmpLicense['currency'];
				$arrDef['amount_before'] = $tmpLicense['amount_before'];
				$arrDef['amount'] = $tmpLicense['amount'];
				$arrDef['amount_total'] = $tmpLicense['amount_total'];
				$arrDef['discount'] = $tmpLicense['discount'];
                $arrDef['currency'] = 'CZK';
				$arrJson = json_decode($tmpLicense['license'], true);

				if (!is_null($arrJson)) {
					$arrModules = $this->ArraysManager->getModules2P();

					foreach ($arrModules as $key => $one) {
						if (isset($arrJson[$key])) {
						    $key2 = str_replace(':', '_', $key);

                            $arrDef['mo_' . $key2] = 1;
							$arrDef['quant_' . $key2] = $arrJson[$key]['quant'];
						}
					}
					//dump($arrDef);

				}
				
			}
		}
		
		$this['orderForm']->setValues($arrDef);

		$this->template->newOrder	= $newOrder;
		$this->template->settings	= $this->settings;
		$this->template->type		= $type;
		$this->template->arrModules	= $this->ArraysManager->getModules2P(TRUE);
		$this->template->arrPrice = $this->ArraysManager->getMoPrices(TRUE);
	}
        
	public function renderOrderSend()
	{
		//$this->template->emailData = $this->EmailingManager->find($idEmail);
        $this->template->emailData = false;
	}

	
   /**
     * Order form factory.
     * @return Nette\Application\UI\Form
     */
    protected function createComponentOrderForm() {
        $form = new Form;

		$form->addText('total_duration',$this->translator->translate('Počet_měsíců'))
				->setRequired($this->translator->translate('Počet_měsíců_musí_být_zadán'))
				->addRule(Form::INTEGER, $this->translator->translate('Počet_měsíců_musí_být_číslo'))
				->addRule(Form::MIN, $this->translator->translate('Počet_měsíců_musí_být_větší_než_%d'),1)
				->addRule(Form::MAX, $this->translator->translate('Počet_měsíců_musí_být_menší_než_%d'),999)
				->setHtmlAttribute('class','form-control input-sm');
		
		$arrModules = $this->ArraysManager->getModules2P(TRUE);

		foreach($arrModules as $key => $one)
		{
			$form->addCheckbox('mo_'.$key, $one);
			
			$form->addText( 'quant_'.$key,'')
				->setHtmlAttribute('class','form-control input-sm cuInput');
		}
	
		$form->addText('amount_before',$this->translator->translate('Před_slevou'))
			->setHtmlAttribute('readonly','readonly')
			->setHtmlAttribute('class','form-control input-sm number');
		
		
		$form->addText('discount',$this->translator->translate('Sleva'))
				->setHtmlAttribute('readonly','readonly')
				->setHtmlAttribute('class','form-control input-sm number');						
		
		$form->addText('amount',$this->translator->translate('Celkem_bez_DPH'))
				->setHtmlAttribute('readonly','readonly')
				->setHtmlAttribute('class','form-control input-sm number');				

		$form->addText('amount_total',$this->translator->translate('Celkem_k_úhradě'))
				->setHtmlAttribute('readonly','readonly')
				->setHtmlAttribute('class','form-control input-sm number');						
		
		$form->addHidden('currency');	
		
		$form->addSelect('payment_type',$this->translator->translate('Způsob_platby'),$this->ArraysManager->getPaymentType())
				->setHtmlAttribute('placeholder',$this->translator->translate('Způsob_platby'))
				->setHtmlAttribute('class','form-control chzn-select input-sm');		
	
        $form->addSubmit('submit', $this->translator->translate('Objednat'))
			->setHtmlAttribute('class','form-control btn-sm btn-primary');

		//$sourceLink = $this->link('getAmount!');
		//$form['tariff_type']
		//	->setHtmlAttribute('data-url_string', $sourceLink);

//			->setHtmlAttribute('data-total_duration', $form['total_duration']->getHtmlName())				
		
        $form->onSuccess[] = array($this, "SettingsHelpdeskFormSubmitted");
        return $form;
    }	
	
	/**
     * Helpdesk settings form submitted
     * @param \Nette\Application\UI\Form
     * @return void
     */
    public function settingsHelpdeskFormSubmitted($form) {
		$values = $form->getValues();
		
		$arrModules	= $this->ArraysManager->getModules2P(TRUE);
		$arrJson = array();
		foreach($arrModules as $key => $one){
			if ($values['mo_'.$key]) {
			    $key2 = str_replace('_',':', $key);
				$arrJson[$key2]['quant'] = $values['quant_' . $key];
			}
			unset($values['mo_'.$key]);
			unset($values['quant_'.$key]);
		}
		//bdump($values);
		//bdump($arrJson);
		//die;
		
        //try {
			$values['license'] = json_encode($arrJson);
			$values['cl_users_id'] = $this->getUser()->id;
			$values['discount'] = (float)$values['discount'];
			$values['amount_before'] = (float)str_replace(' ','',$values['amount_before']);
			$values['amount'] = (float)str_replace(' ','',$values['amount']);
			$values['amount_total'] = (float)str_replace(' ','',$values['amount_total']);			
			$tmpDate = new \Nette\Utils\DateTime;
			$values['v_symb'] = $this->user->getId() . $tmpDate->format('dm');
			//dump($values);
			//die;
			$data = $this->UsersLicenseManager->insert($values);
			
			if ($values['payment_type'] == 0)
			{
				//prevodem
				$this->sendPaymentInfo($data);
				
			}elseif ($values['payment_type'] == 1){ 
				//gopay
				$this->madeGoPay($data);				
			}
			
		//}catch (\Exception $e){
			
//		}
	}
	
	public function getCurrency($currency_code){
		if ($result = $this->CurrenciesManager->findAll()->where('currency_code = ?', $currency_code)->fetch())
		{
			$retVal = $result->currency_name;
		}else{
			$retVal = '';
		}
		return $retVal;
	}
	
	public function getAmount($tariff = 1, $total_users = 1, $total_duration = 1)
	{
		$arrPricelist = array(1 => 160, 2 => 250, 3 => 350);
		$arrDiscount = array(1 => 3, 2 => 5, 4 => 7);
		

		if ($total_duration >= 6)
		{
			$tmpIndex = (int)($total_duration / 6);
			if (isset($arrDiscount[$tmpIndex]))
			{
				$discount = $arrDiscount[$tmpIndex];
			}else{
				$discount = end($arrDiscount);
			}
				
		}else{
			$discount = 0;
		}
		$amount = $arrPricelist[$tariff]*$total_users*$total_duration*( 1 - ($discount / 100) );
		$amount_total = $amount + round($amount * 0.21,0);
		
		$arrReturn = array('amount' => $amount, 'amount_total' => $amount_total, 'discount' => $discount);
		
		return ($arrReturn);
		
	}
	
	public function handleGetAmount($tariff_type = 1, $total_users = 1, $total_duration = 1)
	{
		$result = $this->getAmount($tariff_type, $total_users, $total_duration);
		$this->sendJson($result);
	}
	
	
	private function sendPaymentInfo($data)
	{
		try
		{
                    if (isset($this->user->getIdentity()->email))
                    {
                            $emailTo = array(0 => $this->settings->name.' <'.$this->user->getIdentity()->email.'>');

                            $dataEml = array();
                            $emails = implode(';', $emailTo);
                            $dataEml['singleEmailTo'] = $emails;

                            $dataEml['singleEmailFrom'] = $this->parameters['system_mail_from'];
                            //'Klienti.cz <info@klienti.cz>';

                            //$tmpEmlText = $this->EmailingTextManager->getEmailingText('','','',$this->settings->hd6_emailing_text_id);

                            //$tmpDuration = ($data['total_duration'] >= 5) ? 'měsíců' : (($data['total_duration'] >= 2) ? 'měsíce' : 'měsíc');
                            //$tmpUsers = ($data['total_users'] >= 5) ? 'uživatelů' : (($data['total_users'] >= 2) ? 'uživatelé' : 'uživatel');

                            /*$dataEml['subject'] = 'Platební údaje pro tarif '.$this->ArraysManager->getTariffTypeName($data['tariff_type']).' / '.
                                                                    $data['total_duration'].' '.$tmpDuration . ' / ' .
                                                                    $data['total_users'].' '.$tmpUsers ;*/
							
                            $dataEml['subject'] = $this->translator->translate('Objednávka_služeb_klienti.cz_-_platební_údaje');

                            $template				= $this->createTemplate()->setFile(__DIR__.'/../../templates/Emailing/emailPayment.latte');
                            $template->data     	= $data;
							$template->arrModules	= $this->ArraysManager->getModules(TRUE);
                            $dataEml['body']		= $template;
                            //dump($dataEml);
                            //die;

                            //send email
                            $this->emailService->sendMailSystem($dataEml);

                            //dump($dataEml);
                            //die;

                            //save to cl_emailing
                            //$rowData = $this->EmailingManager->insert($dataEml);


                            //send email
                            $dataEml['singleEmailTo'] = '2H C.S. s.r.o. <info@faktury.cz>';			
                            /*$dataEml['subject'] = 'Objednávka tarifu '.$this->ArraysManager->getTariffTypeName($data['tariff_type']).' / '.
                                                                    $data['total_duration'].' '.$tmpDuration . ' / ' .
                                                                    $data['total_users'].' '.$tmpUsers ;*/
							$dataEml['subject'] = $this->translator->translate('Objednávka_služeb_klienti.cz');
                            $dataEml['body']	= $template;
                            
                            $this->emailService->sendMailSystem($dataEml);			
                            $this->redirect('UserOrder:orderSend');
                            $this->redrawControl('content');
                    }
		}catch (Exception $e) {
                        $errorMess = $e->getMessage(); 
                        $this->flashMessage($errorMess,'danger');
		}			
	}
	
	public function madeGoPay($data)
	{


	/*	$goId = 'GoID';
		$clientId = 'ClientID';
		$clientSecret = 'ClientSecret';

		// TEST MODE
		//$client = new Client(new Config($goId, $clientId, $clientSecret));
		$client = new Client(new \Markette\GopayInline\Config($goId, $clientId, $clientSecret, $mode = \Markette\GopayInline\Config::TEST));		
		
		//dump($data);
		
		// Payment data
		$payment = [
			'payer' => [
				'default_payment_instrument' => \Markette\GopayInline\Api\Lists\PaymentInstrument::BANK_ACCOUNT,
				'allowed_payment_instruments' => [\Markette\GopayInline\Api\Lists\PaymentInstrument::BANK_ACCOUNT],
				'default_swift' => \Markette\GopayInline\Api\Lists\SwiftCode::FIO_BANKA,
				'allowed_swifts' => [\Markette\GopayInline\Api\Lists\SwiftCode::FIO_BANKA, \Markette\GopayInline\Api\Lists\SwiftCode::MBANK],
				'contact' => [
					'first_name' => 'Zbynek',
					'last_name' => 'Zak',
					'email' => 'zbynek.zak@gopay.cz',
					'phone_number' => '+420777456123',
					'city' => 'C.Budejovice',
					'street' => 'Plana 67',
					'postal_code' => '373 01',
					'country_code' => 'CZE',
				],
			],
			'amount' => 200,
			'currency' => \Markette\GopayInline\Api\Lists\Currency::CZK,
			'order_number' => '001',
			'order_description' => 'pojisteni01',
			'items' => [
				['name' => 'item01', 'amount' => 50, 'count' => 2],
				['name' => 'item02', 'amount' => 100],
			],
			'additional_params' => [
				array('name' => 'invoicenumber', 'value' => '2015001003')
			],
			'return_url' => 'http://www.eshop.cz/return',
			'notify_url' => 'http://www.eshop.cz/notify',
			'lang' => \Markette\GopayInline\Api\Lists\Language::CZ,
		];
		//dump($payment);
		//die;
		// Create payment request

		//$token = $client->authenticate(['scope' => Scope::PAYMENT_CREATE]);
		//dump('$token');
		
		//die;
		$response = $client->payments->createPayment(\Markette\GopayInline\Api\Entity\PaymentFactory::create($payment));
		dump($response);
		$data = $response->getData();		
		dump($data);
		die;*/
	}

}
