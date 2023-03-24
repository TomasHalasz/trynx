<?php

namespace MainServices;

use \Nette\Mail\Message,
    \Nette\Templating\FileTemplate,
    \Nette\Latte\Engine,
    \Nette\Utils\Validators;

/**
 * SMS service
 *
 * @author     Tomas Halasz
 * @package    Klienti
 */
class smsService {

    private $db;
    private $sms,$MessagesManager,$smsManager,$smsResponseManager;
    
    public function __construct(\Nette\Database\Context $database, \SMSManager\sms $sms, \App\Model\MessagesManager $messagesManager,
				\App\Model\SmsManager $smsManager, \App\Model\SmsResponseManager $smsResponseManager) {
        $this->db = $database;
	$this->sms = $sms;
	$this->MessagesManager = $messagesManager;
	$this->smsManager = $smsManager;
	$this->smsResponseManager = $smsResponseManager;
    }
    
    public function sendSMS($message, $recipients, $tmpUserCompany, $customId = NULL){
	try{
	    $userTmpSMSManager = json_decode($tmpUserCompany->sms_manager, true);	

	    $this->sms->user = $userTmpSMSManager['sms_username'];
	    $this->sms->pass = $userTmpSMSManager['sms_password'];
	    $this->sms->ssl = TRUE;
	    $count = 0;
	    foreach($recipients as $one)
	    {
		if (Validators::is($one, 'numericint') && !Validators::is($one, 'string:9..18')) {
				
		    } else if (!Validators::is($one, 'numericint') && !Validators::is(preg_replace('/\+/', '', $one), 'numericint')) {
				
		    }else{
			$this->sms->setRecipientNumber($one);
			$count++;
		    }		

	    }   
	    if ($count < 1) { return FALSE;} 

	    if ($customId != NULL) { $this->sms->setCustomId($customId);}

	    $this->sms->setMessage($message);
	    $this->sms->setType($userTmpSMSManager['sms_type']);

	    $this->sms->createRequest();

	    $data = new \Nette\Utils\ArrayHash;	   
	    $data['cl_company_id'] = $tmpUserCompany->id;
	    $data['sms_date'] = new \Nette\Utils\DateTime;
	    $data['recipients'] = '';
	    $data['created'] = new \Nette\Utils\DateTime;		
	    $data['create_by'] = 'SMS manager';	    
	    foreach($recipients as $one)
	    {
		$data['recipients'] .= $one.',';
	    }   	    
	    $data['message'] = $message;
	    $id = $this->smsManager->insertPublic($data);
	    $data['id'] = $id;
	    
	    $ret = $this->sms->send();	
	    
	    //$retXml = simplexml_load_string($ret);
	    //$data['response_type'] = $retXml->xpath('Result/Response Type');
	    //$data['response_id'] = $retXml->xpath('Result/ResponseRequestList/ResponseRequest/RequestID');
	    foreach($ret as $key=>$one)
	    {
		$dataResp = new \Nette\Utils\ArrayHash;	   
		$dataResp['cl_sms_id'] = $data['id'];
		$dataResp['cl_company_id'] = $tmpUserCompany->id;		
		$dataResp['request_id'] = $key;
		$dataResp['sms_count'] = $one['SmsCount'];
		$dataResp['sms_price'] = $one['SmsPrice'];		
		$dataResp['custom_id'] = $one['CustomID'];		
		$dataResp['status'] = $one['Status'];		
		$dataResp['created'] = new \Nette\Utils\DateTime;		
		$dataResp['create_by'] = 'SMS manager';
		
		$dataResp['numbers_list'] = "";
		foreach($one['NumbersList'] as $number)
		{
		    $dataResp['numbers_list'] .= $number.",";
		}
		$this->smsResponseManager->insertPublic($dataResp);
	    }

	    
	    return TRUE;
	} catch (\SMSManager\SMSHttpException $e) {
	    //$this->flashMessage($e->getMessage(), 'danger');

	    //$url = $this->link('Settings:default');
	    foreach($recipients as $key => $one)
	    {	    
		$dataMess=new \Nette\Utils\ArrayHash;		
		$dataMess['cl_company_id'] = $tmpUserCompany->id;
		$dataMess['cl_users_id'] = $key;
		$dataMess['message'] = 'Chyba při odesílání SMS: '.$e->getMessage();	    
		$dataMess['created'] = new \Nette\Utils\DateTime;		
		$dataMess['create_by'] = 'SMS manager';
		//dump($dataMess);
		$this->MessagesManager->insertMessagePublic($dataMess, $key);
		//die;
	    }
	    return FALSE;
        }	    

    }
}

