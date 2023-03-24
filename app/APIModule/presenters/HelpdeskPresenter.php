<?php
namespace App\APIModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Nette\Utils\Arrays;
use Tracy\Debugger;

class HelpdeskPresenter extends \App\APIModule\Presenters\BaseAPI
{
	
	
	/**
	 * @inject
	 * @var \App\Model\ArraysManager
	 */
	public $ArraysManager;
	
	
	/**
	 * @inject
	 * @var \App\Model\FilesManager
	 */
	public $FilesManager;
	
	/**
	 * @inject
	 * @var \App\Model\StatusManager
	 */
	public $StatusManager;
	
	
	/**
	 * @inject
	 * @var \App\Model\NumberSeriesManager
	 */
	public $NumberSeriesManager;
	
	/**
	 * @inject
	 * @var \App\Model\PartnersEventUsersManager
	 */
	public $PartnersEventUsersManager;
	
	/**
	 * @inject
	 * @var \App\Model\UsersManager
	 */
	public $UsersManager;
	
	/**
	 * @inject
	 * @var \App\Model\EmailingManager
	 */
	public $EmailingManager;
	
	/**
	 * @inject
	 * @var \App\Model\EmailingTextManager
	 */
	public $EmailingTextManager;
	
	/**
	 * @inject
	 * @var \App\Model\UserManager
	 */
	public $UserManager;
	
	/**
	 * @inject
	 * @var \App\Model\PartnersEventManager
	 */
	public $DataManager;
	
	/**
	 * @inject
	 * @var \App\Model\PartnersManager
	 */
	public $PartnersManager;
	
	/**
	 * @inject
	 * @var \App\Model\PartnersCategoryManager
	 */
	public $PartnersCategoryManager;
	
	/**
	 * @inject
	 * @var \App\Model\PartnersBookWorkersManager
	 */
	public $PartnersBookWorkersManager;
	
	/**
	 * @inject
	 * @var \App\Model\PartnersBranchManager
	 */
	public $PartnersBranchManager;
	
	/**
	 * @inject
	 * @var \App\Model\CommissionManager
	 */
	public $CommissionManager;
	
	/**
	 * @inject
	 * @var \App\Model\CommissionWorkManager
	 */
	public $CommissionWorkManager;
	
	/**
	 * @inject
	 * @var \App\Model\PartnersEventTypeManager
	 */
	public $PartnersEventTypeManager;
	
	/**
	 * @inject
	 * @var \App\Model\PartnersEventMethodManager
	 */
	public $PartnersEventMethodManager;
	
	/**
	 * @inject
	 * @var \App\Model\TagsManager
	 */
	public $TagsManager;
	
	/**
	 * @inject
	 * @var \App\Model\CenterManager
	 */
	public $CenterManager;
	
	/**
	 * @inject
	 * @var \App\Model\TextsManager
	 */
	public $TextsManager;
	
	
	public function actionSet()
	{
		parent::actionSet();
		$xml = simplexml_load_string($this->dataxml);
		$array = $xml;
		$httpRequest = $this->getHttpRequest();
		$retVal = $this->insertHelpdeskEvent($array, $this->cl_company_id);
		Debugger::log('SupportForm: ' . $retVal);
		echo('<xml>' . $retVal . '</xml>');
		$this->terminate();
	}
	
	
	private function insertHelpdeskEvent($data, $tmpCompany_id)
	{
		$retVal = "";
		$dataI = [];
		$dataI['cl_company_id'] = $tmpCompany_id;
		$dataI['work_label'] = $data->subject;
		$dataI['description_original'] = $data->message . PHP_EOL . PHP_EOL . $data->user_name ;
		$dataI['create_by'] = ($data->user_name != '') ? $data->user_name : 'helpdesk API';
		$dataI['created'] = new \Nette\Utils\DateTime;
		$dataI['date_rcv'] = new \Nette\Utils\DateTime;
		if ($data->email != '') {
			$dataI['email_rcv'] = $data->email;
		}
		$subject = $data->subject;
		$from_email = $data->email;
		$from_email_domain = explode("@", $data->email);
		(count($from_email_domain) > 0) ? $from_domain = $from_email_domain[1] : $from_domain = "";
		$retVal = $tmpCompany_id . "  ";
		//at first exact match on company email or worker email
		$tmpPartnersBookAll = $this->PartnersManager->findAllTotal()->
														where(['cl_partners_book.cl_company_id' => $tmpCompany_id])->
														where('cl_partners_book.email LIKE ? OR :cl_partners_book_workers.worker_email LIKE ? OR (cl_partners_book.web LIKE ? AND NOT (cl_partners_book.web ?))',
															'%' . $from_email . '%', '%' . $from_email . '%', '%' . $from_domain . '%', ['www.gmail.com', 'gmail.com', 'www.seznam.cz', 'seznam.cz']);
		
		$tmpPartnersBookWorker = $this->PartnersBookWorkersManager->findAllTotal()->
																		where(['cl_partners_book_workers.cl_company_id' => $tmpCompany_id])->
																		where('cl_partners_book_workers.worker_email LIKE ?', '%' . $from_email . '%')->fetch();
		$tmpPartnersBook = $tmpPartnersBookAll->fetch();
		
		//26.12.2019 - anonymous user
		if (!$tmpPartnersBook && $this->settings->hd_anonymous == 1) {
			$tmpPartnersBook = $this->PartnersManager->findAllTotal()->where('id = ?', $this->settings->hd_cl_partners_book_id)->fetch();
		}

		if ($tmpPartnersBook) {
			
			$dataI['cl_partners_book_id'] = $tmpPartnersBook->id;
			$dataI['cl_partners_category_id'] = $tmpPartnersBook->cl_partners_category_id;
			$dataRcv = $dataI['date_rcv']->getTimestamp();
			
			//06.05.2016 - find worker and set it
			if ($tmpPartnersBookWorkers = $this->PartnersBookWorkersManager->findAllTotal()->
																	where(['cl_company_id' => $tmpCompany_id])->
																	where('worker_email LIKE ?', '%' . $from_email . '%')->fetch())
																		$data['cl_partners_book_workers_id'] = $tmpPartnersBookWorkers->id;
			
			//if in subject is at first [number] we must find parent event and set event type to not main_event
			$parts = explode("]", $subject);
			//$event_number = substr($parts[0],5);  Re: [
			//02.03. correction, we need take whole part between [ and ]
			$firstPos = strpos($parts[0], "[");
			$event_number = substr($parts[0], $firstPos + 1);
//					fwrite($handle,  'event_number: '.$event_number);
			$isResponse = FALSE;
			
			if ($event_number != '' && $parentEvent = $this->DataManager->findAllTotal()->where(['cl_company_id' => $tmpCompany_id, 'event_number' => $event_number])->fetch()) {
				//received message is response to existed event
				$dataI['cl_partners_event_id'] = $parentEvent->id;
				//02.03.2019 - if email is not from our users it is response
				$tmpEventType = FALSE;
				$tmpUsers = $this->UsersManager->findAllTotal()->where(['cl_company_id' => $tmpCompany_id])->
																where('email LIKE ?', '%' . $from_email . '%')->fetch();
				if (!$tmpUsers) {
					if ($tmpEventType = $this->PartnersEventTypeManager->findAllTotal()->where(['cl_company_id' => $tmpCompany_id, 'default_event' => 0, 'main_event' => 0, 'response_event' => 1])->order('event_order')->fetch())
						$dataI['cl_partners_event_type_id'] = $tmpEventType->id;
				}
				//02.03.2019 - cannot find event type for response, or email is from one of our users so it is another type of event
				if (!$tmpEventType) {
					$tmpEventType = $this->PartnersEventTypeManager->findAllTotal()->where(['cl_company_id' => $tmpCompany_id, 'default_event' => 0, 'main_event' => 0])->order('event_order')->fetch();
					$dataI['cl_partners_event_type_id'] = $tmpEventType->id;
				}
				$dataI['cl_partners_book_id'] = $parentEvent->cl_partners_book_id;
				$dataI['date'] = new \Nette\Utils\DateTime;
				$dataI['date_to'] = new \Nette\Utils\DateTime;
				$isResponse = TRUE;
			} else {
				//received message is new event
				$dataI['date'] = new \Nette\Utils\DateTime;
				if (isset($tmpPartnersBook->cl_partners_category->react_time) && $dataI['date_rcv'] != NULL) {
					//$dataI['date_to'] = new \Nette\Utils\DateTime;
					$dataI['date_end'] = new \Nette\Utils\DateTime;
					$dataI['date_end']->setTimestamp($dataRcv);
					$dataI['date_end'] = $dataI['date_end']->modify('+' . ($tmpPartnersBook->cl_partners_category->react_time) . ' hours');
				}
				
				if ($tmpEventType = $this->PartnersEventTypeManager->findAllTotal()->where(['cl_company_id' => $tmpCompany_id, 'default_event' => 1, 'main_event' => 1])->fetch()) {
					$dataI['cl_partners_event_type_id'] = $tmpEventType->id;
				}
			}
			
			
			if ($tmpStatus = $this->StatusManager->findAllTotal()->where(['cl_company_id' => $tmpCompany_id, 'status_use' => 'partners_event', 's_new' => 1])->fetch()) {
				$dataI['cl_status_id'] = $tmpStatus->id;
			}
			
			//22.05.2016 - if request is response, set status to NULL
			if ($isResponse) {
				$dataI['cl_status_id'] = NULL;
			}
			
			if (!$isResponse && $nSeries = $this->NumberSeriesManager->getNewNumber('partners_event', NULL, NULL, $tmpCompany_id)) {
				//$this->defValues[$this->numberSeries['table_key']] = $nSeries->id;
				//$this->defValues[$this->numberSeries['table_number']] = $nSeries->number;
				$dataI['event_number'] = $nSeries['number'];
				$dataI['cl_number_series_id'] = $nSeries['id'];
			}
			//$data['date_end'] =
			//$dumpFile = __DIR__."/../../data/test.txt";
			//file_put_contents($dumpFile, $data['work_label']);
			$row = $this->DataManager->insertForeign($dataI);
			
			//bdump($data);
			//Debugger::log(dump($data));
			$retFile = "";
			if (isset($data->screenshot) && !empty($data->screenshot)) {
				$retFile = $this->base64ToFileToEvent($data->screenshot, $row);
				
			}
			
			if (isset($data->file1)) {
				$this->fileToEvent($data->file1, $row);
			}
			if (isset($data->file2)) {
				$this->fileToEvent($data->file2, $row);
			}
			if (isset($data->file3)) {
				$this->fileToEvent($data->file3, $row);
			}
			
			$retEml = $this->DataManager->sendCustomerEmailNew($row);
			
			$retEml2 = $this->DataManager->sendAdminEmail($row);
			
			($retEml == 'OK') ? $retEml = $retEml2  : TRUE;
			
			Debugger::log('retEml: '.$retEml);
			if ($retFile != ''){
				$retVal .= '<status>ERROR</status><error_message> Screenshot se nepodařilo zpracovat.</error_message>';
			}elseif ($retEml == 'LATTEMISSING'){
				$retVal .= '<status>ERROR</status><error_message> Není definován text pro potvrzení přijetí do helpdesku.</error_message>';
			}elseif ($retEml != 'OK'){
				$retVal .= '<status>ERROR</status><error_message> Email s potvrzením nebyl odeslán.</error_message>';
			}else{
				$retVal .= '<status>OK</status>';
			}

		} else {
			$retVal .= '<status>ERROR</status><error_message> Uživatel není registrován. Kontaktujte správce helpdesku.</error_message>';
		}
		
		return $retVal;
		
	}
	
	private function base64ToFileToEvent($data, $row)
	{
		
		$fileName =  'screenshot.png';
		list($type, $data) = explode(';', $data);
		list(, $data)      = explode(',', $data);
		
		// Obtain the original content (usually binary data)
		$bin = base64_decode($data);
		
		// Load GD resource from binary data
		$im = imageCreateFromString($bin);
		
		// Make sure that the GD library was able to load the image
		// This is important, because you should not miss corrupted or unsupported images
		$ret = '';
		if (!$im) {
			$ret = 'Base64 value is not a valid image';
		}else {
		
			$destFile = NULL;
			$i = 0;
			$arrFile = str_getcsv($fileName, '.');
			while (file_exists($destFile) || is_null($destFile)) {
				if (!is_null($destFile)) {
					$fileName = $arrFile[0] . '-' . $i . '.' . $arrFile[1];
				}
				$dataFolder = $this->CompaniesManager->getDataFolder($this->cl_company_id);
				$subFolder = $this->ArraysManager->getSubFolder(['cl_partners_event_id' => 'cl_partners_event_id']);
				$destFile = $dataFolder . '/' . $subFolder . '/' . $fileName;
				$i++;
			}
			// Save the GD resource as PNG in the best possible quality (no compression)
			// This will strip any metadata or invalid contents (including, the PHP backdoor)
			// To block any possible exploits, consider increasing the compression level
			imagepng($im, $destFile, 1);
			
			// return the path and the filename saved (same strategy available than saveAttachments)
			$dataF = [];
			$dataF['file_name'] = $fileName;
			$dataF['label_name'] = $fileName;
			$dataF['mime_type'] = 'image/png';
			$dataF['file_size'] = filesize($destFile);
			$dataF['create_by'] = $row->create_by;
			$dataF['created'] = new \Nette\Utils\DateTime;
			$dataF['cl_partners_event_id'] = $row->id;
			$dataF['cl_company_id'] = $row->cl_company_id;
			$this->FilesManager->insertForeign($dataF);
			
		}
		return $ret;
	}
	
	
	
	private function fileToEvent($fileName, $row)
	{
		$fileName2 =  str_replace('.', '_', $fileName);
		$fileName2 =  str_replace(' ', '_', $fileName2);
		$file = $_FILES[$fileName2];
		if ($file) {
			$destFile = NULL;
			$tmpName = $file['tmp_name'];
			$i = 0;
			$arrFile = str_getcsv($fileName, '.');
			while (file_exists($destFile) || is_null($destFile)) {
				if (!is_null($destFile)) {
					$fileName = $arrFile[0] . '-' . $i . '.' . $arrFile[1];
				}
				$dataFolder = $this->CompaniesManager->getDataFolder($this->cl_company_id);
				$subFolder = $this->ArraysManager->getSubFolder(['cl_partners_event_id' => 'cl_partners_event_id']);
				$destFile = $dataFolder . '/' . $subFolder . '/' . $fileName;
				$i++;
			}
			move_uploaded_file($tmpName, $destFile);
			// return the path and the filename saved (same strategy available than saveAttachments)
			$dataF = [];
			$dataF['file_name'] = $fileName;
			$dataF['label_name'] = $fileName;
			$dataF['mime_type'] = $file['type'];
			$dataF['file_size'] = $file['size'];
			$dataF['create_by'] = $row->create_by;
			$dataF['created'] = new \Nette\Utils\DateTime;
			//$data['cl_users_id'] =  $this->presenter->getUser()->id;
			//if ($this->event_id != NULL)
			$dataF['cl_partners_event_id'] = $row->id;
			$dataF['cl_company_id'] = $row->cl_company_id;
			$this->FilesManager->insertForeign($dataF);
		}
	}
	
	
	
}

