<?php


namespace App\Console;
require_once __DIR__.'/../../vendor/autoload.php';
//require_once('MimeMailParser.class.php');

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


use Nette\Mail\Message,
    Nette\Utils\Strings;
use Nette\Mail\SendmailMailer;
use Nette\Templating;

final class IncomeStoreCommand extends Command
{
	
	/** @var App\Model\StoreDocsManager */
	public $storeDocsManager;


    /** @var \App\Model\CompaniesManager */
    public $CompaniesManager;

    /** @var \App\Model\PartnersManager */
    public $PartnersBook;

    /** @var \App\Model\PartnersBookWorkersManager */
    public $PartnersBookWorkers;

    /** @var \App\Model\EmailingTextManager */
    public $EmailingText;

    /** @var \App\Model\UsersManager */
    public $UsersManager;

    /** @var \App\Model\ArraysManager */
    public $ArraysManager;

    /** @var \App\Model\FilesManager */
    public $FilesManager;

    /** @var \MainServices\EmailService */
    public $EmailService;

    /** @var \Nette\Application\LinkGenerator */
    public $LinkGenerator;

    /** @var \App\Model\StoreManager */
    public $StoreManager;

    /** @var \App\Model\StorageManager */
    public $StorageManager;

    /** @var \App\Model\StoreMoveManager */
    public $StoreMoveManager;

    /** @var \Nette\Localization\ITranslator */
    public $translator;

    /** @var \Nette\Http\Session */
    public $session;

    public function __construct(\App\Model\CompaniesManager $companiesManager,  \App\Model\PartnersManager $partnersBook,
                                \App\Model\PartnersBookWorkersManager $partnersBookWorkers,
                                \App\Model\EmailingTextManager $emailingText, \App\Model\UsersManager $usersManager,
                                \App\Model\ArraysManager $arraysManager, \App\Model\FilesManager $filesManager,
                                \MainServices\EmailService $emailService, \Nette\Application\LinkGenerator $linkGenerator,
                                \App\Model\StoreManager $storeManager, \App\Model\StorageManager $storageManager, \App\Model\StoreMoveManager $storeMoveManager, \App\Model\StoreDocsManager $storeDocsManager,
                                \Nette\Localization\ITranslator $translator,  \Nette\Http\Session $session)
    {
        parent::__construct();
        $this->CompaniesManager     = $companiesManager;
        $this->PartnersBook         = $partnersBook;
        $this->PartnersBookWorkers  = $partnersBookWorkers;
        $this->EmailingText         = $emailingText;
        $this->UsersManager         = $usersManager;
        $this->ArraysManager        = $arraysManager;
        $this->FilesManager         = $filesManager;
        $this->EmailService         = $emailService;
        $this->LinkGenerator        = $linkGenerator;
        $this->StoreManager         = $storeManager;
        $this->StorageManager       = $storageManager;
        $this->StoreMoveManager     = $storeMoveManager;
        $this->storeDocsManager     = $storeDocsManager;
        $this->translator           = $translator;
        $this->session = $session;

    }


    protected function configure()
    {
        $this->setName('app:incomestore')
            ->setDescription('Receives the email and giveout from store');

    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        //process through all emails in directory
        $inbox = __DIR__ . "/../../data/email_store_inbox/new/";

        $files = scandir($inbox);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $output->writeLn('File: ' . $inbox . $file);
                $ret = $this->processEmail($inbox . $file, $output);
                if ($ret) {
                    unlink($inbox . $file);
                }
            }
        }
       // $this->session->destroy();
    }

    private function processEmail($fileName, $output)
    {

        try {
           // $container = \App\Booting::boot()
             //               ->createContainer();
            //$output->writeLn('ted');
            $companies              = $this->CompaniesManager;
		    $partnersBook           = $this->PartnersBook;
			$partnersBookWorkers    = $this->PartnersBookWorkers;
			$emailingText           = $this->EmailingText;
			$usersManager           = $this->UsersManager;
			$storeManager           = $this->StoreManager;
			$storageManager         = $this->StorageManager;
			$storeMoveManager       = $this->StoreMoveManager;
			//$this->storeDocsManager = $this->StoreDocsManager;
			$arraysManager          = $this->ArraysManager;
			$emailService           =  $this->EmailService;

            //$output->writeLn('ted2');
			/*$fileName = \Nette\Utils\Random::generate(64,'A-Za-z0-9');
			$fileTest = __DIR__."/../../data/email_test/".$fileName;
			$handle = fopen( $fileTest, 'w');

			while ($line = fgets(STDIN)  )
			{
				fwrite($handle, $line);			
			}

			fclose($handle);
            $output->writeLn('File:'.$fileTest);
            */
            $path = $fileName;
					//'../data/attachments-mail.txt';
			//$Parser = new \MimeMailParser();
            $Parser = new \PhpMimeMailParser\Parser();
			$Parser->setText(file_get_contents($path));

			//$to             = iconv_mime_decode($Parser->getHeader('to'), 0, "UTF-8");
			//$from           = iconv_mime_decode($Parser->getHeader('from'), 0, "UTF-8");
			//$subject        =  iconv_mime_decode($Parser->getHeader('subject'), 0, "UTF-8");
            $to               = $Parser->getHeader('to');
            $from             = $Parser->getHeader('from');
            $subject          = $Parser->getHeader('subject');

			//$html           = iconv($Parser->getMessageEncoding('html'), "UTF-8//TRANSLIT", $Parser->getMessageBody('html'));
			//$text           = iconv($Parser->getMessageEncoding('text'), "UTF-8//TRANSLIT", $Parser->getMessageBody('text'));
            $html           = $Parser->getMessageBody('html');
            $text           = $Parser->getMessageBody('text');
			$attachments    = $Parser->getAttachments(false);

            $output->writeLn('Parsed email:');
            $output->writeLn('To: '.$to);
            $output->writeLn('From: '.$from);
            $output->writeLn('Subject: '.$subject);
            $output->writeLn('Html: '.$html);
            $output->writeLn('Text: '.$text);
            /*foreach($attachments as $attachment) {
                //$output->writeLn('Attached: ' . $attachment);

                $output->writeLn('Filename : '.$attachment->getFilename().'<br />');
                // return logo.jpg

                $output->writeLn('Filesize : '.filesize($attach_dir.$attachment->getFilename()).'<br />');
                // return 1000

                $output->writeLn('Filetype : '.$attachment->getContentType().'<br />');
                // return image/jpeg

                //$output->writeLn('MIME part string : '.$attachment->getMimePartStr().'<br />');
                // return the whole MIME part of the attachment

                //$attachment->save('/path/to/save/myattachment/', Parser::ATTACHMENT_DUPLICATE_SUFFIX);
                // return the path and the filename saved (same strategy available than saveAttachments)

            }*/

			$parts = explode("<", $from);
			if (isset($parts[1]))
			{
				$from_email = substr($parts[1],0,-1);
				if ($from_email == '')
					$from_email = $from;
			}else{
				$from_email = $from;
			}

			$parts = explode("<", $to);
			if (isset($parts[1]))
			{
				$to_email = substr($parts[1],0,-1);
				if ($to_email == '')
					$to_email = $to;
			}else{
				$to_email = $to;
			}

			//později zapneme mazání
			//unlink($path);

	//		var_dump($attachments[0]->content, $attachments[0]->extension);



/*			    $fileTest2 = __DIR__."/../../data/email_test/teststore.txt";
				$handle = fopen( $fileTest2, 'w');			  
				fwrite($handle,  '\n komu: '.$to_email);
				fwrite($handle,  '\n od: '.$from_email);
				fwrite($handle,  '\n text: '.$text);
				fwrite($handle,  '\n html: '.$html);
*/
            $output->writeLn('Search for email: '.$to_email);

			if ($tmpCompany = $companies->findAllTotal()->where(array('email_store_income' => $to_email))->fetch())
			{
                $output->writeLn('Company found: '.$tmpCompany->name);
				//fwrite($handle,  'FIRMA: '.$tmpCompany->name);

				$data = array();
				$data['cl_company_id'] = $tmpCompany->id;
				
				//1.new store_doc
				/*									'cl_company_id',
					*								'doc_date',
					*								'cl_partners_name',
					*								'currency_code',
					*								'doc_title',
					*								'storage_name',
					*								'doc_type' => array('store_in','store_out')				
				 * 
				 */
				$data= array();
				$data['cl_company_id'] = $tmpCompany->id;
				$data['doc_date'] = new \Nette\Utils\DateTime;
				$titlText = explode('</IP>',$text);
				//.$titlText[0].'</customer>'
				$data['doc_title'] = 'automatický výdej';
				$data['doc_type'] = 'store_out';

				if ($tmpCompany->cl_currencies_id != NULL)
					$data['currency_code'] = $tmpCompany->cl_currencies->currency_code;
				else 
					$data['currency_code'] = NULL;

				//fwrite($handle,  'currency_code: '.$data['currency_code'] );
				$from_email_domain = explode("@", $from_email);

				//at first exact match on company email or worker email
				if ($tmpPartnersBookAll = $partnersBook->findAllTotal()->
							where(array('cl_partners_book.cl_company_id' => $tmpCompany->id))->
							where('cl_partners_book.email LIKE ? OR :cl_partners_book_workers.worker_email LIKE ?','%'.$from_email.'%','%'.$from_email.'%'))
				{				
					if ($tmpPartnersBookAll->count() == 0)
					{
						//if there is no match, find match domain in company email
						if ($tmpPartnersBookAll = $partnersBook->findAllTotal()->
									where(array('cl_partners_book.cl_company_id' => $tmpCompany->id))->
									where('cl_partners_book.email LIKE ?','%@'.$from_email_domain[1].'%'))
						{
							if ($tmpPartnersBookAll->count() > 1)
							{
								//if are more results then 1 then
								//email is from some freemail, because there is more then 1 record in partners book
								//we must search again and only by exact email
								$tmpPartnersBookAll = $partnersBook->findAllTotal()->
										where(array('cl_company_id' => $tmpCompany->id))->
										where('email LIKE ?','%'.$from_email.'%');
							}
						}						
						
						$tmpPartnersBookWorker = array();
					}
					else
						{
						$tmpPartnersBookWorker = $partnersBookWorkers->findAllTotal()->
													where(array('cl_partners_book_workers.cl_company_id' => $tmpCompany->id))->
													where('cl_partners_book_workers.worker_email LIKE ?','%'.$from_email.'%')->fetch();
					}
				}


				
				if ($tmpPartnersBook = $tmpPartnersBookAll->fetch())
				{	
					$tmpStorageNotification = '';
					$data['cl_partners_name'] = $tmpPartnersBook->company;
					if ( $tmpPartnersBook->cl_storage_id != NULL)
					{
						$data['storage_name'] = $tmpPartnersBook->cl_storage->name;
						$data['storage_description'] = $tmpPartnersBook->cl_storage->description;
						$tmpStorageNotification = $tmpPartnersBook->cl_storage->email_notification;
					}
					else
					{
						$data['storage_name'] = NULL;
						//client have not defined storage, we can see if location is defined as storage, if yes use this storage name
                                                //11.01.2017 - from now, we must take location from ip 
                                                // xxx.yyy.zzz.www  -> location is yyyzzz
						if ($arrStorage = explode('<IP>"', $text))
						{
							if (isset($arrStorage[1]) && $arrStorage = explode('"</IP>', $arrStorage[1]))
							{
								//$storage_arr = explode(".",$arrStorage[0]);
                                                                //if (isset($storage_arr[1]) && isset($storage_arr[2]))
                                                                //{

								    //if ($storage_arr[0] == "192" &&  $storage_arr[1] == "168")
								    //{
									//29.04.2017 - exception when is IP 192.168.100.xx then is major last segment   									
									//$storage_name = $storage_arr[2].$storage_arr[3];
								    //}else{
									//$storage_name = $storage_arr[1].$storage_arr[2];
								    //}
								    $storage_name = $arrStorage[0];
								    
                                                                    if ($tmpStorage = $storageManager->findAllTotal()->
                                                                                    where(array('cl_company_id' => $tmpCompany->id))->
                                                                                    where('name LIKE ?',$storage_name)->fetch())
                                                                    {
                                                                                    $data['storage_name'] = $storage_name;
										    $data['storage_description'] = $tmpStorage->description;
                                                                                    $tmpStorageNotification = $tmpStorage->email_notification;
                                                                //    }
                                                                }else{
                                                                    $data['storage_name'] = '';
								    $data['storage_description'] = '';
                                                                }
							}
						}

					}

						
				//we are searching first occurence of <product>"CE278A"</product> in text
                //<product>"CE278A"</product><location>"Liberec - Nisa"</location><MAC>"645106236b11"</MAC><customer>Bata</customer><IP>"10.51.159.125"</IP>
                $arrProduct = explode('<product>"', $text);
				if (count($arrProduct) > 1)
				{
                    $arrProduct = explode('"</product>', $arrProduct[1]);
					if (count($arrProduct) >= 1)
					{
						$identification = $arrProduct[0];
						$tmpDoc = $this->storeDocsManager->ApiCreateDoc($data);
						//fwrite($handle,  'identification: '.$identification);
						//fwrite($handle,  'cl_store_docs_id: '.$tmpDoc->id);

						//prepare data for giveout
						$dataApi = array('cl_company_id' => $tmpCompany->id,
								'identification' => $identification,
								'quantity' => 1,
								'price' => NULL,
								'storage_name' => $data['storage_name'],
								'description' => $titlText[0].'</IP>',
								'cl_store_docs_id' => $tmpDoc->id);

						$result = $storeManager->ApiGiveOut($dataApi);
						//fwrite($handle,  ' result: '.$result[0]);
						//fclose($handle);
						if ($result[0] == 9999)
						{
							//unsuccesfull giveout, send notice
							$txtSubject = 'Nebylo odepsáno ze skladu '.$data['storage_name'];														

						}  else {
							//succesfull giveout, send notice
							$txtSubject = 'Úspěšně odepsáno ze skladu '.$data['storage_name'];
							
						}

						
						$emailToSrc  = $usersManager->findAllTotal()->
									where(array('store_manager' => 1, 
												'cl_company_id' => $tmpCompany->id))->
									select("id, CONCAT(name,' <',email,'>') AS user")->fetchPairs('id','user');
						if (!$emailToSrc)
						{
							$emailToSrc  = $usersManager->findAllTotal()->
										where(array('cl_company_id' => $tmpCompany->id))->
										limit(1)->
										select("id, CONCAT(name,' <',email,'>') AS user")->fetchPairs('id','user');									
						}
						
						$dataEml = array();
						$dataEml['singleEmailTo'] = implode(';', $emailToSrc);
						//$dataEml['singleEmailFrom'] = $to_email;

                        //if ($tmpCompany->email_store_income != '') {
                        //    $dataEml['singleEmailReplyTo'] = $tmpCompany->name . ' <' . $tmpCompany->email_store_income . '>';
                        //}
                        //$dataEml['singleEmailFrom'] = $tmpCompany->name . ' <' . $tmpCompany->email . '>';
                        if ($tmpCompany->email_store_income != '') {
                            $dataEml['singleEmailFrom'] = $tmpCompany->name . ' <' . $tmpCompany->email_store_income . '>';
                        }else {
                            $dataEml['singleEmailFrom'] = $tmpCompany->name . ' <' . $tmpCompany->email . '>';
                        }

                        //29.07.2021 - global email
                        if ($tmpCompany->smtp_email_global == 1)
                        {
                            $emailFrom = $this->validateEmail($tmpCompany->smtp_username);
                            if (empty($emailFrom)){
                                $emailFrom = $this->validateEmail($tmpCompany->email);
                            }
                            $emailFrom = $tmpCompany->name . ' <' . $emailFrom . '>';
                            $dataEml['singleEmailFrom'] = $emailFrom;
                        }


						//if ($tmpCompany->hd2_emailing_text_id !== NULL)
						//{
							//$tmpEmlText = $emailingText->getEmailingText('','','',$tmpCompany->hd2_emailing_text_id, $tmpCompany->id);
							//$dataEml['subject'] = 'RE: '.$tmpEmlText['subject'].' '.$subject;
							$dataEml['subject'] = $txtSubject;
                                                        
							//$template= new \Nette\Templating\FileTemplate(__DIR__.'/../ApplicationModule/templates/Emailing/email.latte');
                                                        //$template = new 
							$latte = new \Latte\Engine;
							//\Latte\Macros\CoreMacros::install($latte->getCompiler());
							//$template->registerFilter($latte);							
							//$tmpEmlText['body'].
							//$template->body =	'<table>'.
                                                        $paramEml['body'] =	'<table>'.
													'<tr><td>Firma:</td><td>'. $tmpPartnersBook->company.' </td></tr>'.
													'<tr><td>Sklad:</td><td>'.$data['storage_name'].' / '.$data['storage_description'].'</td></tr>'.
													'<tr><td>Kód:</td><td>'.$identification.'</td></tr>'.
													'<tr><td>Stav:</td><td>'.$result[1].'</td></tr>'.
												'</table>';
                            //$this->translator->setPrefix(['applicationModule']);
                            $latte->addFilter('translate', $this->translator === null ? null : [$this->translator, 'translate']);
							$dataEml['body'] = $latte->renderToString(__DIR__.'/../templates/Emailing/email.latte',$paramEml);

							$emailTo = str_getcsv($dataEml['singleEmailTo'],';');

							try{
							    $emailService->sendMail2($dataEml,$tmpCompany->id, $output);
                            } catch (\Exception $e) {
                                $output->writeLn('Error sending email: ' . $e->getMessage() );
                            }

							//04.06.2016 - email to client if is set notification email on storage definition
							if ($tmpStorageNotification != '')
							{
								$dataEml['singleEmailTo'] = $tmpStorageNotification;
								try{
								    $emailService->sendMail2($dataEml,$tmpCompany->id, $output);
                                } catch (\Exception $e) {
                                    $output->writeLn('Error sending email: ' . $e->getMessage() );
                                }
							}
							
						//}						
						
						//send alert in case of low state
						if ($result[0] <= 0)
						{
							$dataEml = array();
							$dataEml['singleEmailTo'] = implode(';', $emailToSrc);
							//$dataEml['singleEmailFrom'] = $to_email;

                           // if ($tmpCompany->email_store_income != '') {
                           //     $dataEml['singleEmailReplyTo'] = $tmpCompany->name . ' <' . $tmpCompany->email_store_income . '>';
                           // }
                           // $dataEml['singleEmailFrom'] = $tmpCompany->name . ' <' . $tmpCompany->email . '>';

                            if ($tmpCompany->email_store_income != '') {
                                $dataEml['singleEmailFrom'] = $tmpCompany->name . ' <' . $tmpCompany->email_store_income . '>';
                            }else {
                                $dataEml['singleEmailFrom'] = $tmpCompany->name . ' <' . $tmpCompany->email . '>';
                            }

                            //29.07.2021 - global email
                            if ($tmpCompany->smtp_email_global == 1)
                            {
                                $emailFrom = $this->validateEmail($tmpCompany->smtp_username);
                                if (empty($emailFrom)){
                                    $emailFrom = $this->validateEmail($tmpCompany->email);
                                }
                                $emailFrom = $tmpCompany->name . ' <' . $emailFrom . '>';
                                $dataEml['singleEmailFrom'] = $emailFrom;
                            }



						//if ($tmpCompany->hd2_emailing_text_id !== NULL)
						//{
							//$tmpEmlText = $emailingText->getEmailingText('','','',$tmpCompany->hd2_emailing_text_id, $tmpCompany->id);
							//$dataEml['subject'] = 'RE: '.$tmpEmlText['subject'].' '.$subject;
							$dataEml['subject'] = 'Nízký stav zásoby '.$identification.' na skladu '.$data['storage_name'];
							//$template= new \Nette\Templating\FileTemplate(__DIR__.'/../ApplicationModule/templates/Emailing/email.latte');
							$latte = new \Latte\Engine;
							//\Latte\Macros\CoreMacros::install($latte->getCompiler());
							//$template->registerFilter($latte);							
							//$tmpEmlText['body'].
							$paramEml['body'] =	'<table>'.
													'<tr><td>Firma:</td><td>'. $tmpPartnersBook->company.' </td></tr>'.
													'<tr><td>Sklad:</td><td>'.$data['storage_name'].' / '.$data['storage_description'].'</td></tr>'.
													'<tr><td>Kód:</td><td>'.$identification.'</td></tr>'.
													'<tr><td>Stav:</td><td>'.$result[1].'</td></tr>'.									
												'</table>';		
													
							//$dataEml['body'] = $template;
                            //$this->translator->setPrefix(['applicationModule']);
                            $latte->addFilter('translate', $this->translator === null ? null : [$this->translator, 'translate']);
                            $dataEml['body'] = $latte->renderToString(__DIR__.'/../templates/Emailing/email.latte',$paramEml);

							$emailTo = str_getcsv($dataEml['singleEmailTo'],';');
							
							try{
							    $emailService->sendMail2($dataEml,$tmpCompany->id, $output);
                            } catch (\Exception $e) {
                                $output->writeLn('Error sending email: ' . $e->getMessage() );
                            }
							
						}							
						
					}
				}
                    $output->writeLn('Job done.');

                    return true;
                }else{
                    $output->writeLn('Not found cl_partners_book for this email.');
                    return false;
                }
			}else{
                $output->writeLn('Not found cl_company for this email.');
                return false;
            }

            //return 0; // zero return code means everything is ok

        } catch (\Exception $e) {
            $output->writeLn('Error: ' . $e->getMessage());
            //return 1; // non-zero return code means error
            return false;
        }
    }


    /** check validity of email, return empty string if it is not valid
     * @param $eml
     * @return mixed|string
     */
    public function validateEmail($eml){
        if (!filter_var($eml, FILTER_VALIDATE_EMAIL)) {
            $eml = "";
        }
        return $eml;
    }

}