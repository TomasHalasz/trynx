<?php


namespace App\Console;
require_once __DIR__ . '/../../vendor/autoload.php';

//require_once('MimeMailParser.class.php');

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


use Nette\Mail\Message,
    Nette\Utils\Strings;
use Nette\Mail\SendmailMailer;
use Nette\Templating;

class IncomeEmailCommand extends Command
{

    /** @var \App\Model\CompaniesManager */
    public $CompaniesManager;

    /** @var \App\Model\PartnersEventManager */
    public $PartnersEventManager;

    /** @var \App\Model\PartnersManager */
    public $PartnersManager;

    /** @var \App\Model\PartnersBookWorkersManager */
    public $PartnersBookWorkersManager;

    /** @var \App\Model\PartnersEventTypeManager */
    public $PartnersEventTypeManager;

    /** @var \App\Model\StatusManager */
    public $StatusManager;

    /** @var \App\Model\NumberSeriesManager */
    public $NumberSeriesManager;

    /** @var \App\Model\EmailingTextManager */
    public $EmailingTextManager;

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

    /** @var \MainServices\smsService */
    public $smsService;

    /** @var \Nette\Localization\ITranslator */
    public $translator;

    /** @var \Nette\Http\Session */
    public $session;

    public function __construct(\App\Model\CompaniesManager $companiesManager, \App\Model\PartnersEventManager $partnersEventManager, \App\Model\PartnersManager $partnersManager,
                                \App\Model\PartnersBookWorkersManager $partnersBookWorkersManager, \App\Model\PartnersEventTypeManager $partnersEventTypeManager,
                                \App\Model\StatusManager $statusManager, \App\Model\NumberSeriesManager $numberSeriesManager,
                                \App\Model\EmailingTextManager $emailingTextManager, \App\Model\UsersManager $usersManager,
                                \App\Model\ArraysManager $arraysManager, \App\Model\FilesManager $filesManager,
                                \MainServices\EmailService $emailService, \Nette\Application\LinkGenerator $linkGenerator,
                                \MainServices\smsService $smsService, \Nette\Localization\ITranslator $translator,  \Nette\Http\Session $session)
    {
        parent::__construct();
        $this->CompaniesManager = $companiesManager;
        $this->PartnersEventManager = $partnersEventManager;
        $this->PartnersManager = $partnersManager;
        $this->PartnersBookWorkersManager = $partnersBookWorkersManager;
        $this->PartnersEventTypeManager = $partnersEventTypeManager;
        $this->StatusManager = $statusManager;
        $this->NumberSeriesManager = $numberSeriesManager;
        $this->EmailingTextManager = $emailingTextManager;
        $this->UsersManager = $usersManager;
        $this->ArraysManager = $arraysManager;
        $this->FilesManager = $filesManager;
        $this->EmailService = $emailService;
        $this->LinkGenerator = $linkGenerator;
        $this->smsService = $smsService;
        $this->translator           = $translator;
        $this->session = $session;

    }


    protected function configure()
    {
        $this->setName('app:incomeemail')
            ->setDescription('Receives the email');
    }


    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        //process through all emails in directory
        $inbox = __DIR__ . "/../../data/email_helpdesk_inbox/new/";

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
     //   $this->session->destroy();
    }

    private function processEmail($fileName, $output)
    {

        try {
            /*$container = \App\Booting::boot()
                            ->createContainer();*/

            $companies = $this->CompaniesManager;
            $partnersEvent = $this->PartnersEventManager;
            $partnersBook = $this->PartnersManager;
            $partnersBookWorkers = $this->PartnersBookWorkersManager;
            $partnersEventType = $this->PartnersEventTypeManager;
            $status = $this->StatusManager;
            $numberSeries = $this->NumberSeriesManager;
            $emailingText = $this->EmailingTextManager;
            $usersManager = $this->UsersManager;
            $arraysManager = $this->ArraysManager;
            $filesManager = $this->FilesManager;
            $emailService = $this->EmailService;
            $linkGenerator = $this->LinkGenerator;

            //$container->getByType('App\Model\CompaniesManager');
            /*$partnersEvent          = $container->getByType('App\Model\PartnersEventManager');
            $partnersBook           = $container->getByType('App\Model\PartnersManager');
            $partnersBookWorkers    = $container->getByType('App\Model\PartnersBookWorkersManager');
            $partnersEventType      = $container->getByType('App\Model\PartnersEventTypeManager');
            $status                 = $container->getByType('App\Model\StatusManager');
            $numberSeries           = $container->getByType('App\Model\NumberSeriesManager');
            $emailingText           = $container->getByType('App\Model\EmailingTextManager');
            $usersManager           = $container->getByType('App\Model\UsersManager');
            $arraysManager          = $container->getByType('App\Model\ArraysManager');
            $filesManager           = $container->getByType('App\Model\FilesManager');
            $emailService           = $container->getByType('MainServices\EmailService');
            $linkGenerator          = $container->getByType('Nette\Application\LinkGenerator');*/

            //$presenter = $container->getgetByType('App\ApplicationModule\Presenters\BaseAppPresenter');
            //$link = $linkGenerator->link('Application:Helpdesk:default', array('id' => 0, 'do' => 'showBsc'));
            //$output->writeLn('Test link:'.$link);


            //emailService: MainServices\EmailService
            //at first extract

            /* 26.07.2020 - old method to process email directly from input
                         $fileName = \Nette\Utils\Random::generate(64,'A-Za-z0-9');
                        $fileTest = __DIR__."/../../data/email_test/".$fileName;
                        $handle = fopen( $fileTest, 'w');
                        while ($line = fgets(STDIN)  )
                        {
                            fwrite($handle, $line);
                        }

                        fclose($handle);
            */


            $path = $fileName;
            //$path = __DIR__."/../../data/email_test/test2.eml";
            //$Parser = new \MimeMailParser();
            $Parser = new \PhpMimeMailParser\Parser();

            $Parser->setText(file_get_contents($path));

            //$to = iconv_mime_decode($Parser->getHeader('to'), 0, "UTF-8");
            //$from = iconv_mime_decode($Parser->getHeader('from'), 0, "UTF-8");
            //$subject =  iconv_mime_decode($Parser->getHeader('subject'), 0, "UTF-8");
            //$html = iconv($Parser->getMessageEncoding('html'), "UTF-8//TRANSLIT", $Parser->getMessageBody('html'));
            //$text = iconv($Parser->getMessageEncoding('text'), "UTF-8//TRANSLIT", $Parser->getMessageBody('text'));
            $to = $Parser->getHeader('to');
            $from = $Parser->getHeader('from');
            $subject = $Parser->getHeader('subject');
            $html = $Parser->getMessageBody('html');
            $text = $Parser->getMessageBody('text');
            $attachments = $Parser->getAttachments(false);


            $output->writeLn('Parsed email:');
            $output->writeLn('To: ' . $to);
            $output->writeLn('From: ' . $from);
            $output->writeLn('Subject: ' . $subject);
            $output->writeLn('Html: ' . $html);
            $output->writeLn('Text: ' . $text);
            foreach ($attachments as $attachment) {
                //$output->writeLn('Attached: ' . $attachment);

                $output->writeLn('Filename : ' . $attachment->getFilename() . '<br />');
                // return logo.jpg

                //$output->writeLn('Filesize : '.filesize($attach_dir.$attachment->getFilename()).'<br />');
                // return 1000

                $output->writeLn('Filetype : ' . $attachment->getContentType() . '<br />');
                // return image/jpeg

                //$output->writeLn('MIME part string : '.$attachment->getMimePartStr().'<br />');
                // return the whole MIME part of the attachment

                //$attachment->save('/path/to/save/myattachment/', Parser::ATTACHMENT_DUPLICATE_SUFFIX);
                // return the path and the filename saved (same strategy available than saveAttachments)

            }


            $parts = explode("<", $from);
            if (isset($parts[1])) {
                $from_email = substr($parts[1], 0, -1);
                if ($from_email == '')
                    $from_email = $from;
            } else {
                $from_email = $from;
            }

            $parts = explode("<", $to);
            if (isset($parts[1])) {
                $to_email = substr($parts[1], 0, -1);
                if ($to_email == '')
                    $to_email = $to;
            } else {
                $to_email = $to;
            }

            //později zapneme mazání
            //unlink($path);


            if ($tmpCompany = $companies->findAllTotal()->where(array('email_income' => $to_email))->fetch()) {
//				fwrite($handle,  'FIRMA: '.$tmpCompany->name);
                $output->writeLn('Company find: ' . $tmpCompany->name);
                $data = array();
                $data['cl_company_id'] = $tmpCompany->id;
                $data['work_label'] = $subject;
                //if ($html != '')
                //	$data['description'] = $html;
                //else
                $data['description_original'] = $text;

                $data['create_by'] = 'automat';
                $data['created'] = new \Nette\Utils\DateTime;
                $data['date_rcv'] = new \Nette\Utils\DateTime;
                $data['email_rcv'] = $from_email;
                $from_email_domain = explode("@", $from_email);


                //at first exact match on company email or worker email
                $tmpPartnersBookAll = $partnersBook->findAllTotal()->
                where(array('cl_partners_book.cl_company_id' => $tmpCompany->id))->
                where('cl_partners_book.email LIKE ? OR :cl_partners_book_workers.worker_email LIKE ?', '%' . $from_email . '%', '%' . $from_email . '%');
                $tmpPartnersBookWorker = $partnersBookWorkers->findAllTotal()->
                where(array('cl_partners_book_workers.cl_company_id' => $tmpCompany->id))->
                where('cl_partners_book_workers.worker_email LIKE ?', '%' . $from_email . '%')->fetch();


                if ($tmpPartnersBook = $tmpPartnersBookAll->fetch()) {
                    $dumpFile = __DIR__ . "/../../data/test0.txt";
                   // file_put_contents($dumpFile, $tmpCompany->id . '<br>' . $subject . '<br>' . $from);

                    $data['cl_partners_book_id'] = $tmpPartnersBook->id;
                    $data['cl_partners_category_id'] = $tmpPartnersBook->cl_partners_category_id;
                    $dataRcv = $data['date_rcv']->getTimestamp();

                    //06.05.2016 - find worker and set it
                    if ($tmpPartnersBookWorkers = $partnersBookWorkers->findAllTotal()->
                    where(array('cl_company_id' => $tmpCompany->id))->
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

                    $dumpFile = __DIR__ . "/../../data/test1.txt";
                    //file_put_contents($dumpFile, $data['work_label']);
                    if ($event_number != '' && $parentEvent = $partnersEvent->findAllTotal()->where(array('cl_company_id' => $tmpCompany->id, 'event_number' => $event_number))->fetch()) {
                        //received message is response to existed event
                        $data['cl_partners_event_id'] = $parentEvent->id;
                        //02.03.2019 - if email is not from our users it is response
                        $tmpEventType = FALSE;
                        $tmpUsers = $usersManager->findAllTotal()->where(array('cl_company_id' => $tmpCompany->id))->
                        where('email LIKE ?', '%' . $from_email . '%')->fetch();
                        if (!$tmpUsers) {
                            if ($tmpEventType = $partnersEventType->findAllTotal()->where(array('cl_company_id' => $tmpCompany->id, 'default_event' => 0, 'main_event' => 0, 'response_event' => 1))->order('event_order')->fetch())
                                $data['cl_partners_event_type_id'] = $tmpEventType->id;
                        }
                        //02.03.2019 - cannot find event type for response, or email is from one of our users so it is another type of event
                        if (!$tmpEventType) {
                            $tmpEventType = $partnersEventType->findAllTotal()->where(array('cl_company_id' => $tmpCompany->id, 'default_event' => 0, 'main_event' => 0))->order('event_order')->fetch();
                            $data['cl_partners_event_type_id'] = $tmpEventType->id;
                        }
                        $data['cl_partners_book_id'] = $parentEvent->cl_partners_book_id;
                        $data['date'] = new \Nette\Utils\DateTime;
                        $data['date_to'] = new \Nette\Utils\DateTime;
                        $isResponse = TRUE;
                    } else {
                        //received message is new event

                        if (isset($tmpPartnersBook->cl_partners_category->react_time) && $data['date_rcv'] != NULL) {

                            $data['date_end'] = new \Nette\Utils\DateTime;
                            $data['date_end']->setTimestamp($dataRcv);
                            $data['date_end'] = $data['date_end']->modify('+' . ($tmpPartnersBook->cl_partners_category->react_time) . ' hours');
                        }


                        if ($tmpEventType = $partnersEventType->findAllTotal()->where(array('cl_company_id' => $tmpCompany->id, 'default_event' => 1, 'main_event' => 1))->fetch()) {
                            $data['cl_partners_event_type_id'] = $tmpEventType->id;
                        }
                    }


                    if ($tmpStatus = $status->findAllTotal()->where(array('cl_company_id' => $tmpCompany->id, 'status_use' => 'partners_event', 's_new' => 1))->fetch()) {
                        $data['cl_status_id'] = $tmpStatus->id;
                    }

                    //22.05.2016 - if request is response, set status to NULL
                    if ($isResponse) {
                        $data['cl_status_id'] = NULL;
                    }

                    if (!$isResponse && $nSeries = $numberSeries->getNewNumber('partners_event', NULL, NULL, $tmpCompany->id)) {
                        //$this->defValues[$this->numberSeries['table_key']] = $nSeries->id;
                        //$this->defValues[$this->numberSeries['table_number']] = $nSeries->number;
                        $data['event_number'] = $nSeries['number'];
                        $data['cl_number_series_id'] = $nSeries['id'];
                    }
                    //$data['date_end'] =
                    $dumpFile = __DIR__ . "/../../data/test.txt";
                   // file_put_contents($dumpFile, $data['work_label']);
                    $row = $partnersEvent->insertForeign($data);

                    //04.05.2019 - work with attachements if are any
                    foreach ($attachments as $attachment) {
                        //$output->writeLn('Attached: ' . $attachment);

                        $output->writeLn('Filename : ' . $attachment->getFilename() . '<br />');
                        //$output->writeLn('Filesize : '.filesize($attach_dir.$attachment->getFilename()).'<br />');
                        $output->writeLn('Filetype : ' . $attachment->getContentType() . '<br />');
                        //$output->writeLn('MIME part string : '.$attachment->getMimePartStr().'<br />');

                        //
                        $destFile = NULL;
                        while (file_exists($destFile) || is_null($destFile)) {
                            $fileName = \Nette\Utils\Random::generate(64, 'A-Za-z0-9');
                            $destFile = __DIR__ . "/../../data/files/" . $fileName;
                        }
                        $tmpFile = __DIR__ . "/../../data/files/tmp/";
                        $output->writeLn('TMP DIR: ' . $tmpFile);
                        if (!file_exists($tmpFile)) {
                            $output->writeLn('TMP DIR not exists, create new');
                            mkdir($tmpFile);
                        }

                        $attachment->save($tmpFile, \PhpMimeMailParser\Parser::ATTACHMENT_DUPLICATE_SUFFIX);
                        $output->writeLn('File saved.');
                        $tmpFileSize = filesize($tmpFile . $attachment->getFilename());
                        rename($tmpFile . $attachment->getFilename(), $destFile);

                        // return the path and the filename saved (same strategy available than saveAttachments)

                        $dataF = array();
                        $dataF['file_name'] = $fileName;
                        $dataF['label_name'] = $attachment->getFilename();
                        $dataF['mime_type'] = $attachment->getContentType();
                        $dataF['file_size'] = $tmpFileSize;
                        $dataF['create_by'] = 'email';
                        $dataF['created'] = new \Nette\Utils\DateTime;
                        //$data['cl_users_id'] =  $this->presenter->getUser()->id;
                        //if ($this->event_id != NULL)
                        $dataF['cl_partners_event_id'] = $row->id;

                        $dataF['cl_company_id'] = $tmpCompany->id;
                        $filesManager->insertForeign($dataF);

                    }


                    //now send confirm email to author
                    //only if it is not response
                    if (!$isResponse) {
                        $dataEml = array();
                        $dataEml['singleEmailTo'] = $from_email;

                        if ($tmpCompany->email_income != '') {
                         //   $dataEml['singleEmailReplyTo'] = $tmpCompany->name . ' <' . $tmpCompany->email_income . '>';
                            $dataEml['singleEmailFrom'] = $tmpCompany->name . ' <' . $tmpCompany->email_income . '>';
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

                        //$dataEml['singleEmailFrom'] = $to_email;

                        if ($tmpCompany->hd1_emailing_text_id !== NULL) {
                            $tmpEmlText = $emailingText->getEmailingText('', '', '', $tmpCompany->hd1_emailing_text_id, $tmpCompany->id);
                            $dataEml['subject'] = '[' . $data['event_number'] . '][' . $tmpEmlText['subject'] . '] ' . $data['work_label'];
                            //$template= new \Nette\Templating\FileTemplate(__DIR__.'/../ApplicationModule/templates/Emailing/email.latte');
                            $latte = new \Latte\Engine;
                            //\Latte\Macros\CoreMacros::install($latte->getCompiler());
                            //$template->registerFilter($latte);
                            //$this->translator->setPrefix(['applicationModule']);
                            $latte->addFilter('translate', $this->translator === null ? null : [$this->translator, 'translate']);

                            if ($tmpPartnersBookWorker != NULL) {
                                $tmpKontakt = '<tr><td>Kontakt:</td><td>' . $tmpPartnersBookWorker->worker_name . ', Email:' . $tmpPartnersBookWorker->worker_email . ', Tel.:' . $tmpPartnersBookWorker->worker_phone . '</td></tr>';
                            } else {
                                $tmpKontakt = '<tr><td>Kontakt:</td><td>' . $tmpPartnersBook->person . ', Email:' . $tmpPartnersBook->email . ', Tel.:' . $tmpPartnersBook->phone . '</td></tr>';
                            }
                            if (isset($tmpPartnersBook->cl_partners_category['id'])) {
                                $tmpCategory = '<tr><td>Důležitost:</td><td>' . $tmpPartnersBook->cl_partners_category->category_name . '</td></tr>';
                            } else {
                                $tmpCategory = '<tr><td>Důležitost:</td><td>Není nastavena</td></tr>';
                            }
                            //$tmpCategory = '<tr><td>Důležitost:</td><td>'.$tmpPartnersBook->cl_partners_category->category_name.'</td></tr>';

                            $paramEml['body'] = $tmpEmlText['body'] .
                                '<table>' .
                                '<tr><td>Odesílatel:</td><td>' . $tmpPartnersBook->company . '</td></tr>' .
                                $tmpKontakt .
                                $tmpCategory .
                                '<tr><td>Předmět:</td><td>' . $data['work_label'] . '</td></tr>' .
                                '<tr><td>Zpráva:</td><td>' . $data['description_original'] . '</td></tr>' .
                                '</table>';

                            $dataEml['body'] = $latte->renderToString(__DIR__ . '/../templates/Emailing/email.latte', $paramEml);

                            //26.01.2018 - send only if there is not excluded email
                            $canSend = TRUE;
                            if (!empty($tmpCompany->email_income_exclude)) {
                                $partsTo = explode(';', $tmpCompany->email_income_exclude);
                                foreach ($partsTo as $onePartTo) {
                                    if (strpos(strtoupper($dataEml['singleEmailTo']), strtoupper($onePartTo)) !== false) {
                                        $canSend = FALSE;
                                    }
                                }
                            }

                            if ($canSend) {
                                try{
                                    $emailService->sendMail2($dataEml, $tmpPartnersBook->cl_company_id, $output);
                                } catch (\Exception $e) {
                                    $output->writeLn('Error sending email: ' . $e->getMessage() );
                                }
                            }
                        }
                        //now email to helpdesk admin about new request
                        if ($tmpCompany->hd3_emailing_text_id !== NULL) {
                            $tmpEmlText = $emailingText->getEmailingText('', '', '', $tmpCompany->hd3_emailing_text_id, $tmpCompany->id);
                            $dataEml['subject'] = '[' . $data['event_number'] . '][' . $tmpEmlText['subject'] . '] ' . $data['work_label'];
                            //$template= new \Nette\Templating\FileTemplate(__DIR__.'/../ApplicationModule/templates/Emailing/email.latte');
                            $latte = new \Latte\Engine;

                            $link = $linkGenerator->link('Application:Helpdesk:default', array('id' => $row->id, 'do' => 'showBsc'));

                            if ($tmpPartnersBookWorker != NULL) {
                                $tmpKontakt = '<tr><td>Kontakt:</td><td>' . $tmpPartnersBookWorker->worker_name . ', Email:' . $tmpPartnersBookWorker->worker_email . ', Tel.:' . $tmpPartnersBookWorker->worker_phone . '</td></tr>';
                            } else {
                                $tmpKontakt = '<tr><td>Kontakt:</td><td>' . $tmpPartnersBook->person . ', Email:' . $tmpPartnersBook->email . ', Tel.:' . $tmpPartnersBook->phone . '</td></tr>';
                            }
                            if (isset($tmpPartnersBook->cl_partners_category['id'])) {
                                $tmpCategory = '<tr><td>Důležitost:</td><td>' . $tmpPartnersBook->cl_partners_category->category_name . '</td></tr>';
                            } else {
                                $tmpCategory = '<tr><td>Důležitost:</td><td>Není nastavena</td></tr>';
                            }
                            //$tmpCategory = '<tr><td>Důležitost:</td><td>'.$tmpPartnersBook->cl_partners_category->category_name.'</td></tr>';

                            //$this->translator->setPrefix(['applicationModule']);
                            $latte->addFilter('translate', $this->translator === null ? null : [$this->translator, 'translate']);

                            $paramEml['body'] = $tmpEmlText['body'] .
                                '<table>' .
                                '<tr><td>Odesílatel:</td><td>' . $tmpPartnersBook->company . ', ' . $from . '</td></tr>' .
                                $tmpKontakt .
                                $tmpCategory .
                                '<tr><td>Odkaz do helpdesku:</td><td><a href="' . $link . '" title="Otevře záznam helpdesku">' . $link . '</a>' .
                                '<tr><td>Předmět:</td><td>' . $data['work_label'] . '</td></tr>' .
                                '<tr><td>Zpráva:</td><td>' . $data['description_original'] . '</td></tr>' .
                                '</table>';

                            $dataEml['body'] = $latte->renderToString(__DIR__ . '/../templates/Emailing/email.latte', $paramEml);
                            //$dataEml['body'] = $template;


                            $emailTo = $usersManager->findAllTotal()->
                            where(array('event_manager' => 1,
                                'cl_company_id' => $tmpCompany->id))->
                            select("id, CONCAT(name,' <',email,'>') AS user")->fetchPairs('id', 'user');
                            if (count($emailTo) == 0) {
                                $emailTo = $usersManager->findAllTotal()->
                                where(array('cl_company_id' => $tmpCompany->id))->
                                limit(1)->
                                select("id, CONCAT(name,' <',email,'>') AS user")->fetchPairs('id', 'user');
                            }
                            $dataEml['singleEmailTo'] = implode(';', $emailTo);
                            //if ($tmpCompany->email_income != '') {
                            //    $dataEml['singleEmailReplyTo'] = $tmpCompany->name . ' <' . $tmpCompany->email_income . '>';
                            //}
                            //$dataEml['singleEmailFrom'] = $tmpCompany->name . ' <' . $tmpCompany->email . '>';

                            if ($tmpCompany->email_income != '') {
                                //   $dataEml['singleEmailReplyTo'] = $tmpCompany->name . ' <' . $tmpCompany->email_income . '>';
                                $dataEml['singleEmailFrom'] = $tmpCompany->name . ' <' . $tmpCompany->email_income . '>';
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

                            //now send email
                            try{
                                $emailService->sendMail2($dataEml, $tmpPartnersBook->cl_company_id, $output);
                            } catch (\Exception $e) {
                                $output->writeLn('Error sending email: ' . $e->getMessage() );
                            }
                        }
                        //12.02.2018 - send SMS to helpdesk admins
                        $userTmpSMSManager = json_decode($tmpCompany->sms_manager, true);
                        if ($userTmpSMSManager['hd_recieved2']) {
                            $this->sendSMS($data, $tmpPartnersBook);
                        }
                    } else {
                        //it is response on existed request, we are mailing to helpdesk admin
                        if ($tmpCompany->hd5_emailing_text_id !== NULL) {
                            $dataEml = array();
                            //find helpdesk admin if there is not one selected at parent event
                            if ($parentEvent->cl_users_id !== NULL) {
                                $emailTo = $usersManager->findAllTotal()->
                                where(array('cl_company_id' => $tmpCompany->id,
                                    'id' => $parentEvent->cl_users_id))->
                                select("id, CONCAT(name,' <',email,'>') AS user")->limit(1)->fetchPairs('id', 'user');
                            } else {
                                $emailTo = $usersManager->findAllTotal()->
                                where(array('event_manager' => 1,
                                    'cl_company_id' => $tmpCompany->id))->
                                select("id, CONCAT(name,' <',email,'>') AS user")->fetchPairs('id', 'user');
                                if (!$emailTo) {
                                    $emailTo = $usersManager->findAllTotal()->
                                    where(array('cl_company_id' => $tmpCompany->id))->
                                    limit(1)->
                                    select("id, CONCAT(name,' <',email,'>') AS user")->fetchPairs('id', 'user');
                                }
                            }

                            $dataEml['singleEmailTo'] = implode(';', $emailTo);
                            //$dataEml['singleEmailFrom'] = $to_email;

                            //if ($tmpCompany->email_income != '') {
                            //    $dataEml['singleEmailReplyTo'] = $tmpCompany->name . ' <' . $tmpCompany->email_income . '>';
                            //}
                            //$dataEml['singleEmailFrom'] = $tmpCompany->name . ' <' . $tmpCompany->email . '>';

                            if ($tmpCompany->email_income != '') {
                                $dataEml['singleEmailFrom'] = $tmpCompany->name . ' <' . $tmpCompany->email_income . '>';
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

                            $tmpEmlText = $emailingText->getEmailingText('', '', '', $tmpCompany->hd5_emailing_text_id, $tmpCompany->id);
                            $arrWork_label = explode('[', $data['work_label']);
                            if (isset($arrWork_label[2])) {
                                $piece = substr($arrWork_label[2], strpos($arrWork_label[2], ']') + 1);
                                $arrWork_label[2] = $tmpEmlText['subject'] . '] ' . $piece;
                            }

                            $dataEml['subject'] = implode('[', $arrWork_label);

                            //'['.$tmpEmlText['subject'].'] '.$data['work_label'];
                            //$template= new \Nette\Templating\FileTemplate(__DIR__.'/../ApplicationModule/templates/Emailing/email.latte');
                            $latte = new \Latte\Engine;
                            //\Latte\Macros\CoreMacros::install($latte->getCompiler());
                            //$template->registerFilter($latte);
                            //$link = $presenter->link('//:Application:Helpdesk:Edit', array('id' => $row->id));
                            //$link = $presenter->link('//:Application:Helpdesk:default', array('id' => $row->cl_partners_event_id, 'do' => 'showBsc'));
                            $link = $linkGenerator->link('Application:Helpdesk:default', array('id' => $row->cl_partners_event_id, 'do' => 'showBsc'));
                            if ($tmpPartnersBookWorker != NULL) {
                                $tmpKontakt = '<tr><td>Kontakt:</td><td>' . $tmpPartnersBookWorker->worker_name . ', Email:' . $tmpPartnersBookWorker->worker_email . ', Tel.:' . $tmpPartnersBookWorker->worker_phone . '</td></tr>';
                            } else {
                                $tmpKontakt = '<tr><td>Kontakt:</td><td>' . $tmpPartnersBook->person . ', Email:' . $tmpPartnersBook->email . ', Tel.:' . $tmpPartnersBook->phone . '</td></tr>';
                            }

                            //$this->translator->setPrefix(['applicationModule']);
                            $latte->addFilter('translate', $this->translator === null ? null : [$this->translator, 'translate']);

                            $paramEml['body'] = $tmpEmlText['body'] .
                                '<table>' .
                                '<tr><td>Odesílatel:</td><td>' . $tmpPartnersBook->company . ', ' . $from . '</td></tr>' .
                                $tmpKontakt .
                                '<tr><td>Odkaz do helpdesku:</td><td><a href="' . $link . '" title="Otevře záznam helpdesku">' . $link . '</a>' .
                                '<tr><td>Předmět:</td><td>' . $data['work_label'] . '</td></tr>' .
                                '<tr><td>Zpráva:</td><td>' . $data['description_original'] . '</td></tr>' .
                                '</table>';
                            $dataEml['body'] = $latte->renderToString(__DIR__ . '/../templates/Emailing/email.latte', $paramEml);

                            try{
                                $emailService->sendMail2($dataEml, $tmpPartnersBook->cl_company_id, $output);
                            } catch (\Exception $e) {
                                $output->writeLn('Error sending email: ' . $e->getMessage() );
                            }


                        }

                    }
                    $output->writeLn('Helpdesk request writen and emailed');
                } else {
                    //not found cl_partners_book record via email
                    $dataEml = array();
                    $dataEml['singleEmailTo'] = $from_email;
                    //$dataEml['singleEmailFrom'] = $to_email;
                    //if ($tmpCompany->email_income != '') {
                    //    $dataEml['singleEmailReplyTo'] = $tmpCompany->name . ' <' . $tmpCompany->email_income . '>';
                    //}
                    //$dataEml['singleEmailFrom'] = $tmpCompany->name . ' <' . $tmpCompany->email . '>';
                    if ($tmpCompany->email_income != '') {
                        $dataEml['singleEmailFrom'] = $tmpCompany->name . ' <' . $tmpCompany->email_income . '>';
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


                    $output->writeLn('$tmpCompany->hd2_emailing_text_id  : ' . $tmpCompany->hd2_emailing_text_id  . '<br />');
                    if ($tmpCompany->hd2_emailing_text_id !== NULL) {
                        $tmpEmlText = $emailingText->getEmailingText('', '', '', $tmpCompany->hd2_emailing_text_id, $tmpCompany->id);
                        $dataEml['subject'] = 'RE: ' . $tmpEmlText['subject'] . ' ' . $subject;
                        //$template= new \Nette\Templating\FileTemplate(__DIR__.'/../ApplicationModule/templates/Emailing/email.latte');
                        $latte = new \Latte\Engine;
                        //\Latte\Macros\CoreMacros::install($latte->getCompiler());
                        //$template->registerFilter($latte);
                        $paramEml['body'] = $tmpEmlText['body'];

                        //$this->translator->setPrefix(['applicationModule']);
                        $latte->addFilter('translate', $this->translator === null ? null : [$this->translator, 'translate']);

                        //$dataEml['body'] = $template;
                        $dataEml['body'] = $latte->renderToString(__DIR__ . '/../templates/Emailing/email.latte', $paramEml);

                        $emailTo = str_getcsv($dataEml['singleEmailTo'], ';');
                        //now send email

                        $output->writeLn('$dataEml[\'singleEmailTo\'] : ' . $dataEml['singleEmailTo']  . '<br />');
                        $output->writeLn('$dataEml[\'singleEmailFrom\'] : ' . $dataEml['singleEmailFrom']  . '<br />');
                        $output->writeLn('$tmpCompany->id  : ' . $tmpCompany->id  . '<br />');
                        try{
                            $emailService->sendMail2($dataEml, $tmpCompany->id, $output);
                        } catch (\Exception $e) {
                            $output->writeLn('Error sending email: ' . $e->getMessage() );
                        }
                        $output->writeLn('Helpdesk request was rejected by email');

                    }else{
                        $output->writeLn('Helpdesk request was not writen nor emailed');
                    }


                }

            } else {
                //not found company to which helpdesk email belongs
                //only die
                $output->writeLn('Error: There is no company for incoming email' );
            }
            //fclose($handle);


            //$newsletterSender->sendNewsletters();

            //return 0; // zero return code means everything is ok
            return TRUE;

            //} catch (\Nette\Mail\SmtpException $e) {
        } catch (\Exception $e) {
            $output->writeLn('Error: ' . $e->getMessage() );
            //return 1; // non-zero return code means error
            return FALSE;

        }
    }


    public function sendSMS($tmpData, $tmpPartner)
    {

        // $container = \App\Booting::boot()
        //                         ->createContainer();
        //$companies              = $container->getByType('App\Model\CompaniesManager');


//	    $usersManager = $container->getByType('App\Model\UsersManager');
//	    $smsService = $container->getByType('MainServices\smsService');
        $usersManager = $this->UsersManager;
        $smsService = $this->SmsService;

	    if ($phoneTo = $usersManager->findAllTotal()->
        where(array('event_manager' => 1, 'cl_company_id' => $tmpPartner->cl_company_id))->select("id, phone")->fetchPairs('id', 'phone')) {

        } else {
            $phoneTo = $usersManager->findAllTotal()->
            where(array('cl_company_id' => $tmpPartner->cl_company_id))->select("id, phone")->limit(1)->fetchPairs('id', 'phone');
        }

	    $smsService->sendSMS('Helpdesk přijal nový požadavek od ' . $tmpPartner->company . ' > ' . $tmpData['work_label'],
            $phoneTo,
            $tmpPartner->cl_company, 2);

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