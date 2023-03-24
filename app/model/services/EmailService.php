<?php

namespace MainServices;


use http\Exception;
use \Nette\Mail\Message,
    \Nette\Mail,
    \Nette\Templating\FileTemplate,
    \Nette\Latte\Engine;
use Tracy\Debugger;

use \Nette\Application\UI\ITemplateFactory;

/**
 * E-mail service
 *
 * @author     Tomas Halasz
 * @package    Klienti
  * @property-read \SystemContainer $context 
 */
class EmailService
{

    /** @var Nette\Application\LinkGenerator */
    private $linkGenerator;

    /** @var Nette\Application\UI\ITemplateFactory */
    private $templateFactory;


    /** @var \App\Model\CompaniesManager */
    private $CompaniesManager;

    private $parameters;


    function __construct(\Nette\Application\UI\ITemplateFactory $templateFactory, \Nette\Application\LinkGenerator $generator, \App\Model\CompaniesManager $companiesManager,
                         \Nette\DI\Container $container)
    {
        $this->templateFactory = $templateFactory;
        $this->linkGenerator = $generator;
        $this->CompaniesManager = $companiesManager;
        $this->parameters = $container->getParameters();

        //bdump($this->mailer, 'EmailServices');

    }

    /**
     * Odoslať registračný e-mail pro potvrzení platnosti emailu
     * @param string $sentTo
     * @param string $activateLink
     * @return void
     */
    public function sendRegistrationEmail($sentTo, $activateLink)
    {
        $arrayValues = array(
            'to' => array(
                '0' => $sentTo
            ),
            'subject' => 'Klienti.cz - dokončení registrace a ověření emailu',
            'bodyLatte' => __DIR__ . '/../../LoginModule/templates/NewRegistration/emailCheck.latte',
            'html-notification' => false,
            'message' => false,
            'attachment' => false,
            'latteValues' => array('activateLink' => $activateLink,
                'greeting' => 'Vážený',
                'userName' => 'uživateli')
        );
        //dump($this->mailer);
        //die;
        $this->sendMailS($arrayValues);
    }

    /**
     * Odešle link na změnu hela
     * @param string $sentTo
     * @param string $activateLink
     * @return void
     */
    public function sendNewPasswordEmail($sentTo, $confirmLink)
    {
        $arrayValues = array(
            'to' => array(
                '0' => $sentTo
            ),
            'subject' => 'Klienti.cz - nové heslo',
            'bodyLatte' => __DIR__ . '/../../LoginModule/templates/LostPassword/email.latte',
            'html-notification' => false,
            'message' => false,
            'attachment' => false,
            'latteValues' => array('confirmLink' => $confirmLink,
                'greeting' => 'Vážený',
                'userName' => 'uživateli')
        );

        $this->sendMailS($arrayValues);
    }


    /**
     * Sending mail method only for system
     * @param array $arrayMessage
     * @return void
     */
    private function sendMailS($arrayMessage)
    {
        if ($arrayMessage['bodyLatte']) {
            if (!file_exists($arrayMessage['bodyLatte'])) {
                throw new \Exception('Template message doesn\'t exist !');
            }
            //$template = new FileTemplate();
            //$latte = new \Latte\Engine();
        }

        $mail = new Message;
        $mailSet = $this->parameters['mail'];
        $mail->setFrom($mailSet['system_mail_from'], !empty($settings->name) ? $settings->name : $mailSet['system_name_from']);

        foreach ($arrayMessage['to'] as $mailTo) {
            $mail->addTo($mailTo);
        }

        $mail->setSubject($arrayMessage['subject']);


        if ($arrayMessage['bodyLatte'] && $arrayMessage['latteValues']) {

            //dump($this->linkGenerator);
            //dump($arrayMessage);
            //die;
            $template = $this->templateFactory->createTemplate();
            $template->setFile($arrayMessage['bodyLatte']);
            $template->data = $arrayMessage['latteValues'];
            //  $template->_control = $this->linkGenerator;
            $template->baseUrl = 'https://klienti.cz';
            $mail->setHtmlBody($template);

            //$mail->setHtmlBody($latte->renderToString($arrayMessage['bodyLatte'], $arrayMessage['latteValues']));

        } elseif ($arrayMessage['html-notification']) {
            $mail->setHtmlBody($arrayMessage['message']);
        } else {
            $mail->setBody($arrayMessage['message']);
        }


        if ($arrayMessage['attachment']) {
            foreach ($arrayMessage['attachmentFiles'] as $filesAttachment) {
                if (!file_exists($filesAttachment)) {
                    throw new \Exception('Attachment files (' . $filesAttachment . ') doesn\'t exist !');
                }

                $mail->addAttachment($filesAttachment);
            }
        }


        //dump($this->parameters);
        $mailSet = $this->parameters['mail'];

        if ($mailSet['host'] != 'localhost' && $mailSet['smtp'] == 'true') {
            $mailer = new \Nette\Mail\SmtpMailer([
                            'host'      => $mailSet['host'],
                            'username'  => $mailSet['username'],
                            'password'  => $mailSet['password'],
                            'secure'    => $mailSet['secure'],
                            'port'      => $mailSet['port']
            ]);
        } else
            $mailer = new \Nette\Mail\SendmailMailer;


        $mailer->send($mail);
    }


    public function sendMail($settings, $emailFrom, $emailTo, $subject, $body, $attachment = []){
        try {
            if ($emailFrom == '' ){
                throw new \Exception("Email was not send. Sender missing.");
            }
            //bdump($emailTo);
            if ( count($emailTo) == 0 || is_null($emailTo[0])){
                throw new \Exception("Email was not send. Receiver missing.");
            }


            //now send email
            $mail = new \Nette\Mail\Message;

            //$mailSet = $this->parameters['mail'];
            $mailSet = ['smtp'      => true,
                        'host'      => $settings['smtp_host'],
                        'username'  => $settings['smtp_username'],
                        'password'  => $settings['smtp_password'],
                        'secure'    => $settings['smtp_secure'],
                        'port'      => $settings['smtp_port']
                        ];

            if ($mailSet['secure'] == 1)
                $mailSet['secure'] = 'ssl';
            elseif ($mailSet['secure'] == 2)
                $mailSet['secure'] = 'tls';
            else
                $mailSet['secure'] = null;

            if (!$mailSet['smtp']) {
                //not used custom smtp, sender have to be mailer@klienti.cz
                $mail->setFrom($mailSet['system_mail_from'],!empty($settings->name) ? $settings->name : $mailSet['system_name_from'])
                            ->addReplyTo($emailFrom);
            }else{

                    //used custom smtp, sender have to be client email
                    $mail->setFrom($emailFrom)
                        ->addReplyTo($emailFrom);


            }

            $mail->setSubject($subject)
                        ->setHtmlBody($body);
            foreach($emailTo as $one)
            {
                $mail->addTo($one);
            }
            if (!is_null($attachment)) {
                foreach ($attachment as $one) {
                    $mail->addAttachment($one);
                }
            }

            if ($mailSet['smtp']) {
                $mailer = new \Nette\Mail\SmtpMailer([
                                'host'      => $mailSet['host'],
                                'username'  => $mailSet['username'],
                                'password'  => $mailSet['password'],
                                'secure'    => $mailSet['secure'],
                                'port'      => $mailSet['port']
                            ]);
            } else {
                $mailer = new \Nette\Mail\SendmailMailer;
            }
            //dump($mail->getFrom());
            //dump($mail->getHeaders());
            $mailer->send($mail);
        }
        catch (\Exception $e)
        {
            throw new \Exception("Email nebyl odeslán. Chyba: ".$e->getMessage());
        }
    }





    /** sending emails from CLI
     * @param $data
     * @param null $cl_company_id
     * @throws \Exception
     */
	public function sendMail2($data, $cl_company_id = NULL, $output = FALSE)
	{
        try {
            if ($cl_company_id !== NULL)
                $settings = $this->CompaniesManager->findAllTotal()->where('id = ?',$cl_company_id)->fetch();
            else
                $settings = $this->CompaniesManager->getTable()->fetch();


            if ($settings)
            {
                //now send email
                $mail = new \Nette\Mail\Message;

                //$mailSet = $this->parameters['mail'];
                $mailSet = ['smtp'      => true,
                            'host'      => $settings['smtp_host'],
                            'username'  => $settings['smtp_username'],
                            'password'  => $settings['smtp_password'],
                            'secure'    => $settings['smtp_secure'],
                            'port'      => $settings['smtp_port']
                            ];

                if ($mailSet['secure'] == 1)
                    $mailSet['secure'] = 'ssl';
                elseif ($mailSet['secure'] == 2)
                    $mailSet['secure'] = 'tls';
                else
                    $mailSet['secure'] = null;

                $tmpReplyToEml = '';
                $tmpFromName = '';
                $tmpFromEml =  '';
                if (!$mailSet['smtp']) {
                    //not used custom smtp, sender has to be mailer@klienti.cz
                    $tmpFromName = $mailSet['system_mail_from'];
                    $tmpFromEml =  !empty($settings->name) ? $settings->name : $mailSet['system_name_from'];
                    $mail->setFrom($tmpFromName, $tmpFromEml)
                        ->addReplyTo($data['singleEmailFrom'])
                        ->setSubject($data['subject']);
                }else{
                    //used custom smtp, sender have to be client email
                    $tmpFromName = '';
                    $tmpFromEml =  $data['singleEmailFrom'];
                    $mail->setFrom($tmpFromEml)
                        ->setSubject($data['subject']);

                    if (isset($data['singleEmailReplyTo'])){
                        $tmpReplyToEml =  $data['singleEmailReplyTo'];
                        $mail->addReplyTo($tmpReplyToEml);
                    }


                }
                if ($output) {
                    $output->writeLn('FromName: ' . $tmpFromName);
                    $output->writeLn('FromEml: ' . $tmpFromEml);
                    $output->writeLn('ReplyToEml: ' . $tmpReplyToEml);
                }


                //bdump($data['singleEmailTo']);

                foreach(str_getcsv($data['singleEmailTo'], ';') as $one)
                {
                    $mail->addTo($one);
                }
                //add logo company
                $tmpLogo  = "";
                $tmpDir = "";
                if ($settings->picture_logo != '')
                {
                    $srcLogo  = __DIR__."/../../../data/pictures/".$settings->picture_logo;
                    if (file_exists($srcLogo))
                    {
                        $random = \Nette\Utils\Random::generate(12,'A-Za-z0-9');
                        $tmpDir = __DIR__."/../../../www/images/tmp/".$random."/";
                        if (!is_dir($tmpDir))
                            mkdir ($tmpDir);

                        $tmpLogoName	= "logo".substr($srcLogo,strrpos($srcLogo, '.'));
                        $tmpLogo		= $tmpDir."logo".substr($srcLogo,strrpos($srcLogo, '.'));
                        copy($srcLogo, $tmpLogo);
                        $logoName = $mail->addEmbeddedFile($tmpLogo)->getHeader('Content-ID');
                        $logoName = trim($logoName,'<>');
                        $data['body'] = str_replace('[logo]', $tmpLogoName , $data['body']);
                        $mail->setHtmlBody($data['body'],$tmpDir);
                    }else{
                        //hide logo img attrib when there is no logo
                        $data['body'] = str_replace('<img class="logo', '<img class="logo hidden', $data['body']);
                        $mail->setHtmlBody($data['body']);
                    }
                }else {
                    //hide logo img attrib when there is no logo
                    $data['body'] = str_replace('<img class="logo', '<img class="logo hidden', $data['body']);
                    $mail->setHtmlBody($data['body']);
                }


                if (isset($data['attachment'])) {
                    $attachment = json_decode($data['attachment'], true);
                    foreach ($attachment as $one) {
                        if (file_exists($one))
                            $mail->addAttachment($one);
                    }
                }


                if ($mailSet['smtp']) {
                    $mailer = new \Nette\Mail\SmtpMailer([
                        'host'      => $mailSet['host'],
                        'username'  => $mailSet['username'],
                        'password'  => $mailSet['password'],
                        'secure'    => $mailSet['secure'],
                        'port'      => $mailSet['port']
                    ]);
                } else {
                    $mailer = new \Nette\Mail\SendmailMailer;
                }

                if ($output){
                    $output->writeLn('Host: ' . $mailSet['host']);
                    $output->writeLn('Username: ' . $mailSet['username']);
                    $output->writeLn('Password: xxxxxxx' ); //. $mailSet['password']
                    $output->writeLn('Secure: ' . $mailSet['secure']);
                    $output->writeLn('Port: ' . $mailSet['port']);
                    $output->writeLn('Port: ' . $mailSet['port']);
                }
                $mailer->send($mail);
                if ($output){
                    $output->writeLn('Send: OK');
                }
                if ($tmpDir != '') {
                    if (file_exists($tmpLogo)) {
                        if (unlink($tmpLogo))
                            if (is_dir($tmpDir))
                                rmdir($tmpDir);
                    }
                }
            }else{
                if ($output){
                    $output->writeLn('Send: ERROR');
                }
                throw new \Exception('Email nebyl odeslán. Chybí nastavení firmy.');
            }
        }catch(\Exception $e){
            Debugger::Log('Chyba odesílání: ' . $e->getMessage(), 'EmailService');
            throw new \Exception('Email nebyl odeslán');
        }
		
	}

    /**sending email from system to users.
     * @param $data
     */
	public function sendMailSystem($data)
	{
			$mail = new \Nette\Mail\Message;
            $mailSet = $this->parameters['mail'];
			$mail->setFrom($mailSet['system_mail_from'],!empty($settings->name) ? $settings->name : $mailSet['system_name_from'])
					->setSubject($data['subject']);
			foreach(str_getcsv($data['singleEmailTo'], ';') as $one) 
			{
				if ($one != '')
				{
					$mail->addTo($one);
				}
			}
			//add logo company
				$srcLogo  = __DIR__."/../../../www/images/klienti-slogan 400x127.png";		    
				$logoName = 'klienti-slogan 400x127.png';
				if (file_exists($srcLogo))
				{
					$logoName2 = trim($mail->addEmbeddedFile($srcLogo)->getHeader('Content-ID'),'<>');
					$data['body'] = str_replace('[logo]', 'cid:'.$logoName2 , $data['body']);
					$mail->setHtmlBody($data['body']);
				}else{
					$mail->setHtmlBody($data['body']);	
				}
				
				$mailSet = $this->parameters['mail'];
				if ($mailSet['smtp']) {
					$mailer = new \Nette\Mail\SmtpMailer([
						'host'      => $mailSet['host'],
						'username'  => $mailSet['username'],
						'password'  => $mailSet['password'],
						'secure'    => $mailSet['secure'],
						'port'      => $mailSet['port']
					]);
				} else {
					$mailer = new \Nette\Mail\SendmailMailer;
				}
				
			$mailer->send($mail);		
	}

    /*
     * Prepare email to send
     */
    public function mailToSend($data){

    }
	

}

