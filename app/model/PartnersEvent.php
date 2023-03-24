<?php

namespace App\Model;

use Latte\Engine;
use MainServices\EmailService;
use Nette;
use Tracy\Debugger;

/**
 * PartnersEvent management.
 */
class PartnersEventManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_partners_event';
	protected $userAccesTableName = 'cl_partners_event_users';
	/** @var EmailService */
	private $emailService;

	/** @var CompaniesManager */
	private $companiesManager;
	
	/** @var EmailingManager */
	private $EmailingManager;
	
	/** @var UsersManager */
	private $UsersManager;
	
	/** @var EmailingTextManager */
	private $EmailingTextManager;
	
	/** @var Contributte\Translator */
	private $translator;
	
	/** @var Nette\Bridges\ApplicationLatte\ILatteFactory */
	private $latteEngine;
	
	/** @var Nette\Application\LinkGenerator */
	private $linkGenerator;
	
	
	public function __construct(\Nette\Database\Context $db, UserManager $userManager, UsersManager $usersManager, \Nette\Security\User $user,Nette\Application\LinkGenerator $generator, \DatabaseAccessor $accessor,
								EmailService $emailService,  \Nette\Localization\ITranslator $translator, Nette\Bridges\ApplicationLatte\ILatteFactory $latteEngine, \Nette\Http\Session $session,
								EmailingTextManager $emailingTextManager, EmailingManager $emailingManager,  CompaniesManager $CompaniesManager)
	{
		parent::__construct($db, $userManager, $user, $session, $accessor);
		$this->settings = $CompaniesManager->getTable()->fetch();
		$this->companiesManager = $CompaniesManager;
		$this->emailService = $emailService;
		$this->EmailingTextManager = $emailingTextManager;
		$this->EmailingManager = $emailingManager;
		$this->translator = $translator;
		$this->latteEngine = $latteEngine;
		$this->linkGenerator = $generator;
		$this->UsersManager = $usersManager;
	}
	
	
	
	/**
     * Upraví záznam a sečte hodnoty trvání do work parent události v případě že jde o podřízenou událost
     * @param array $data
     */
    public function update($data, $mark = TRUE) {
	    parent::update($data, $mark);
	    //dump($data);
	    //die;
	    if (isset($data['cl_partners_event_id']) && $data['cl_partners_event_id'] != NULL)
	    {
            $workData= $this->findAllTotal()->where([$this->tableName.'.cl_partners_event_id' => $data['cl_partners_event_id'],
                                        $this->tableName.'.finished' => 1])
                            ->select('SUM('.$this->tableName.'.work_time) AS work_time, MAX('.$this->tableName.'.date_to) AS date_to, '.
                                                '(COUNT('.$this->tableName.'.finished)/SUM(CASE WHEN '.$this->tableName.'.finished=1 THEN 1 ELSE 0 END)) = 1 AS total_finished')->fetch();
            $workData2= $this->findAllTotal()->where([$this->tableName.'.cl_partners_event_id' => $data['cl_partners_event_id']])
                            ->select('(COUNT('.$this->tableName.'.finished)/SUM(CASE WHEN '.$this->tableName.'.finished=1 THEN 1 ELSE 0 END)) = 1 AS total_finished')->fetch();

            //dump($workData);
            //dump($workData2);
            $updateParentData = ['work_time' => $workData['work_time'], 'date_to' => $workData['date_to']];
            //dump($updateParentData,'updateparentdata');
            //die;
            if ($workData2['total_finished'] == 0)
            {
                $updateParentData['finished'] = 0;
            }
            $cData = (array) $updateParentData;
            $cData['id'] = $data['cl_partners_event_id'];
            $this->historySave($cData);
            $this->find($data['cl_partners_event_id'])->update($updateParentData);
	    }else{
           // bdump($data,'PartnersEvent');
            $workData= $this->findAllTotal()->where([$this->tableName.'.cl_partners_event_id' => $data['id'],
                                        $this->tableName.'.finished' => 1])->
                                        select('SUM('.$this->tableName.'.work_time) AS work_time, MAX(DATE_ADD('.$this->tableName.'.date, INTERVAL '.$this->tableName.'.work_time MINUTE)) AS date_to, '.
                                                        '(COUNT('.$this->tableName.'.finished)/SUM(CASE WHEN '.$this->tableName.'.finished=1 THEN 1 ELSE 0 END)) = 1 AS total_finished')->fetch();
            $workData2= $this->findAllTotal()->where([$this->tableName.'.cl_partners_event_id' => $data['id']])->
                                        select('(COUNT('.$this->tableName.'.finished)/SUM(CASE WHEN '.$this->tableName.'.finished=1 THEN 1 ELSE 0 END)) = 1 AS total_finished')->fetch();

            $updateParentData = [];

            //dump($workData);
            bdump($workData, "ted");
            //die;
            $updateParentData = ['work_time' => $workData['work_time'], 'date_to' => $workData['date_to']];
            if (is_null($workData['total_finished']))
            {
                $updateParentData['date_to'] = new Nette\Utils\DateTime;
            }

            if ($workData2['total_finished'] == 0 && !is_null($workData['total_finished']))
            {
                $updateParentData['finished'] = 0;
            }
            //dump($updateParentData);
            $cData = (array) $updateParentData;
            $cData['id'] = $data['id'];
            $this->historySave($cData);
            $this->find($data['id'])->update($updateParentData);
	    }
    }
	
	
	/* Email notification to customer about new request
		 *
		 */
	public function sendCustomerEmailNew($dataSource)
	{
		$retVal = "";
		$tmpEvent = $this->findAllTotal()->where('id = ? AND cl_company_id = ?', $dataSource['id'], $dataSource['cl_company_id'])->fetch();
		$settings = $this->companiesManager->findAllTotal()->where('id = ?', $dataSource['cl_company_id'])->fetch();
		if ($tmpEvent) {
			if ($tmpEvent->email_rcv != '')
				$tmpEmail = $tmpEvent->email_rcv;
			else
				$tmpEmail = $tmpEvent->cl_partners_book->email;
			
			/* 07.04.2017 - if is defined center email, we are sending email to this email*/
			if ($tmpEvent->cl_partners_book->cl_center_id !== NULL) {
				$tmpEmail = $tmpEvent->cl_partners_book->cl_center->email;
			}
			
			if ($tmpEmail != '') {
				if ($tmpEvent->cl_partners_book_workers_id !== NULL) {
					$tmpEmail = $tmpEvent->cl_partners_book_workers->worker_email;
					$emailTo = array(0 => $tmpEvent->cl_partners_book_workers->worker_name . ' <' . $tmpEmail . '>');
				} else {
					$emailTo = array(0 => $tmpEvent->cl_partners_book->company . ' <' . $tmpEmail . '>');
				}
				if (!empty($tmpEmail)) {
					$data = new \Nette\Utils\ArrayHash;
					$emails = implode(';', $emailTo);
                    //Debugger::log('sendCustomerEmailNew', $emails);
					$data['cl_company_id'] = $settings->id;
					$data['create_by'] = 'helpdesk API';
					$data['created'] = new \Nette\Utils\DateTime;
					$data['singleEmailTo'] = $emails;
					
					if ($settings->email_income != '') {
                        $data['singleEmailReplyTo'] = $settings->name . ' <' . $settings->email_income . '>';
                        $data['singleEmailFrom'] = $settings->name . ' <' . $settings->email . '>';
                    }else {
                        $data['singleEmailFrom'] = $settings->name . ' <' . $settings->email . '>';
                    }



					if ($settings->hd1_emailing_text_id !== NULL) {
						$tmpEmlText = $this->EmailingTextManager->getEmailingText('', '', '', $settings->hd1_emailing_text_id, $dataSource['cl_company_id']);
						$data['subject'] = '[' . $tmpEvent['event_number'] . '][' . $tmpEmlText['subject'] . '] ' . $tmpEvent['work_label'];

						if ($tmpEvent->cl_partners_book_workers_id !== NULL) {
							$tmpKontakt = '<tr><td>Kontakt: </td><td>' . $tmpEvent->cl_partners_book_workers->worker_name . ', Email:' . $tmpEvent->cl_partners_book_workers->worker_email . ', Tel.:' . $tmpEvent->cl_partners_book_workers->worker_phone . '</td></tr>';
						} else {
							//if (!empty($tmpEvent->cl_partners_book->person) && !empty($tmpEvent->cl_partners_book->email)) {
							if (!empty($tmpEvent->create_by) && !empty($tmpEvent->email_rcv)) {
								$tmpKontakt = '<tr><td>Kontakt: </td><td>' . $tmpEvent->create_by . ', Email:' . $tmpEvent->email_rcv . ', Tel.:' . $tmpEvent->cl_partners_book->phone . '</td></tr>';
							}else{
								$tmpKontakt = '<tr><td>Kontakt: </td><td>' . $tmpEvent->cl_partners_book->person . ', Email:' . $tmpEvent->cl_partners_book->email . ', Tel.:' . $tmpEvent->cl_partners_book->phone . '</td></tr>';
							}
						}
						//Debugger::log(dump($data));
						$tmpBody = $tmpEmlText['body'] .
							'<table>' .
							'<tr><td>' . $this->translator->translate('cl_partners_book') . ' </td><td>' . $tmpEvent->cl_partners_book->company . '</td></tr>' .
							$tmpKontakt .
							'<tr><td>Datum vytvoření: </td><td>' . $tmpEvent['date_rcv']->format('d.m.Y H:i:s') . '</td></tr>' .
							'<tr><td>Důležitost:</td><td>' . (!is_null($tmpEvent->cl_partners_category_id)) ? $tmpEvent->cl_partners_category->category_name : 'není definována v nastavení' . '</td></tr>' .
							'<tr><td>Nová zpráva: </td><td>' . $tmpEvent->work_label . '</td></tr>' .
							'<tr><td>Obsah: </td><td>' . $tmpEvent['description_original'] . '<br>' . $tmpEvent['description'] . '</td></tr>' .
							'</table>';
						
						$template = $this->latteEngine->create();
						$data['body'] = $template->renderToString(__DIR__ . '/../templates/Emailing/email.latte', array('body' => $tmpBody));
						try {
							//send email
							$this->emailService->sendMail2($data, $settings->id);
							
							//save to cl_emailing
							$this->EmailingManager->insertForeign($data);
							
							//$this->flashMessage('Informace o novém zápisu byla klientovi byl odeslána.','success');
							$retVal = "OK";
						} catch (\Exception $e) {
							//$this->flashMessage($e->getMessage(),'danger');
							Debugger::log($e->getMessage());
							$retVal = "ERROR";
						}
						
					}else{
						$retVal = "LATTEMISSING";
					}
				} else {
					//$this->flashMessage('Klient nemá zapsán email, zpráva nebyla odeslána,','danger');
					$retVal = "NO";
				}
			}
		}else{
			$retVal = "ERROR";
		}
		return $retVal;
	}
	
	
	/* Email nofitication to helpdesk admin about new submessage
		 *
		 */
	public function sendAdminEmail($dataSource)
	{
		//we are sending email to admin only when the submessage is saved by another user then parent owner
		
		//$tmpEvent = $this->PartnersEventManager->find($dataSource['cl_partners_event_id']);
		$retVal = "";
		$tmpEvent = $this->findAllTotal()->where('id = ? AND cl_company_id = ?', $dataSource['id'], $dataSource['cl_company_id'])->fetch();
		$settings = $this->companiesManager->findAllTotal()->where('id = ?', $dataSource['cl_company_id'])->fetch();
		if ($tmpEvent) {
			//02.03.2019 - it was wrong because datasource[cl_users_id] is in most cases same as current user
			//we have to compare with parentevent[cl_users_id]
			//if ($dataSource['cl_users_id'] != $tmpEvent['cl_users_id']) {
				// bdump('ano');
				//find helpdesk admin if there is not one selected at parent event
				if ($tmpEvent->cl_users_id !== NULL) {
					$emailTo = $this->UsersManager->findAllTotal()->
											where(array('id' => $tmpEvent->cl_users_id, 'cl_company_id' => $dataSource['cl_company_id']))->
                                            where('email != ?', "")->
											select('id, CONCAT(name," <",IF(email2 !="", email2, email),">") AS user')->limit(1)->fetchPairs('id', 'user');
				} else {
					$emailTo = $this->UsersManager->findAllTotal()->
													where(array('event_manager' => 1, 'cl_company_id' => $dataSource['cl_company_id']))->
                                                    where('email != ?', "")->
													select('id, CONCAT(name," <",IF(email2 !="", email2, email),">") AS user')->fetchPairs('id', 'user');
					if (!$emailTo) {
						$emailTo = $this->UsersManager->findAllTotal()->
														where(array('cl_company_id' => $dataSource['cl_company_id']))->
                                                        where('email != ?', "")->
														limit(1)->
														select('id, CONCAT(name," <",IF(email2 !="", email2, email),">") AS user')->fetchPairs('id', 'user');
                        if (!$emailTo && $tmpEvent->cl_company['email'] != '') {
                            $emailTo = [0 => $tmpEvent->cl_company['name'] . ' <' . $tmpEvent->cl_company['email'] . '>'];
                        }elseif(!$emailTo){
                            $emailTo = [0 => 'helpdesk admin email not found <info@klienti.cz>'];
                        }
					}
                }
			//	}
				$data = new \Nette\Utils\ArrayHash;
				$emails = implode(';', $emailTo);
				$data['singleEmailTo'] = $emails;
				/*if ($settings->email_income != '')
					$data['singleEmailFrom'] = $settings->name . ' <' . $settings->email_income . '>';
				else
					$data['singleEmailFrom'] = $settings->name . ' <' . $settings->email . '>';
                */

                if ($settings->email_income != '') {
                    $data['singleEmailReplyTo'] = $settings->name . ' <' . $settings->email_income . '>';
                    $data['singleEmailFrom'] = $settings->name . ' <' . $settings->email . '>';
                }else {
                    $data['singleEmailFrom'] = $settings->name . ' <' . $settings->email . '>';
                }



				//bdump($data);
				if ($settings->hd3_emailing_text_id !== NULL) {
					//prepare email data
					$tmpEmlText = $this->EmailingTextManager->getEmailingText('', '', '', $settings->hd3_emailing_text_id, $dataSource['cl_company_id']);
					$data['subject'] = '[' . $tmpEvent->event_number . ']' . '[' . $tmpEmlText['subject'] . '] ' . $tmpEvent['work_label'];

					//$link = $this->link('//showBsc!', array('id' => $tmpEvent->id, 'copy' => false));
					
					$link = $this->linkGenerator->link('Application:Helpdesk:edit', array('do' => 'showBsc','id' => $tmpEvent->id, 'copy' => false ));
					if ($tmpEvent->cl_partners_book_workers_id !== NULL) {
						$tmpKontakt = '<tr><td>Kontakt: </td><td>' . $tmpEvent->cl_partners_book_workers->worker_name . ', Email:' . $tmpEvent->cl_partners_book_workers->worker_email . ', Tel.:' . $tmpEvent->cl_partners_book_workers->worker_phone . '</td></tr>';
					} else {
					
						if (!empty($tmpEvent->create_by) && !empty($tmpEvent->email_rcv)) {
							$tmpKontakt = '<tr><td>Kontakt: </td><td>' . $tmpEvent->create_by . ', Email:' . $tmpEvent->email_rcv . ', Tel.:' . $tmpEvent->cl_partners_book->phone . '</td></tr>';
						}else{
							$tmpKontakt = '<tr><td>Kontakt: </td><td>' . $tmpEvent->cl_partners_book->person . ', Email:' . $tmpEvent->cl_partners_book->email . ', Tel.:' . $tmpEvent->cl_partners_book->phone . '</td></tr>';
						}
						
					}
					if (isset($tmpEvent->cl_partners_category['id'])) {
						$tmpCategory = '<tr><td>Důležitost:</td><td>' . $tmpEvent->cl_partners_category->category_name . '</td></tr>';
					} else {
						$tmpCategory = '';
					}
					
					$tmpBody = $tmpEmlText['body'] .
						'<table>' .
						'<tr><td>' . $this->translator->translate('cl_partners_book') . ' </td><td>' . $tmpEvent->cl_partners_book->company . '</td></tr>' .
						$tmpKontakt .
						$tmpCategory .
						'<tr><td>Datum vytvoření: </td><td>' . $tmpEvent['date_rcv']->format('d.m.Y H:i:s') . '</td></tr>' .
						'<tr><td>Původní požadavek: </td><td>' . $tmpEvent->work_label . '</td></tr>' .
						'<tr><td>Odkaz do helpdesku:</td><td><a href="' . $link . '" title="Otevře záznam helpdesku">' . $link . '</a>' .
						'<tr><td>Nová zpráva: </td><td>' . $tmpEvent['description_original'] . '<br>' . $tmpEvent['description'] . '</td></tr>' .
						'</table>';
					
					$template = $this->latteEngine->create();
					$data['body'] = $template->renderToString(__DIR__ . '/../templates/Emailing/email.latte', array('body' => $tmpBody));
					try {
						//send email
						$this->emailService->sendMail2($data, $settings->id);
						
						//save to cl_emailing
						$this->EmailingManager->insertForeign($data);
						
						//$this->flashMessage('Informace o novém zápisu byla klientovi byl odeslána.','success');
						$retVal = "OK";
					} catch (\Exception $e) {
						//$this->flashMessage($e->getMessage(),'danger');
						Debugger::log($e->getMessage());
						$retVal = "ERROR";
					}
					
				}else{
					$retVal = "LATTEMISSING";
				}
				
			//}
		}else{
			$retVal = "ERROR";
		}
		return $retVal;
	}
	
	
}

