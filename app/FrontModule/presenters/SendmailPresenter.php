<?php

namespace App\FrontModule\Presenters;
use Nette\Mail\Message,
    Nette\Utils\Strings;
use Nette\Mail\SendmailMailer;

class SendmailPresenter extends BasePresenter
{
    public $parameters;

    public function __construct(\Nette\DI\Container $container)
    {

        parent::__construct();

        $this->parameters = $container->getParameters();

    }


    public function actionDefault($email,$name="",$subject="",$department="",$text="")
    {

		//now send email
		//$mail = new Message;
        $mail = new \Nette\Mail\Message;
		if ($name != "")
		{
		    $mail->setFrom('info@klienti.cz')
			->setSubject('Zpráva z klienti.cz')
			->setHtmlBody('Návštěvník '.$name.' Vám zaslal novou zprávu<br><br>email: '.$email.'<br>oblast:'.$department.'<br>Předmět:'.$subject.'<br>Text:'.$text);
		}else{
		    $mail->setFrom('info@klienti.cz')
			->setSubject('Registrace na klienti.cz')
			->setHtmlBody('Byla provedena nová registrace na klienti.cz, <br> registrovaný email: '.$email.'<br> děkuji za spolupráci.');
		}
		$mail->addTo('info@klienti.cz');
		$mail->addTo('tomas.halasz@seznam.cz');

        $settings = $this->parameters['mail'];
        //$mailSet = $this->parameters['mail'];
        $mailSet = ['smtp'              => true,
                    'host'              => $settings['host'],
                    'username'          => $settings['username'],
                    'password'          => $settings['password'],
                    'secure'            => $settings['secure'],
                    'port'              => $settings['port'],
                    'system_mail_from'  => $settings['system_mail_from'],
                    'system_name_from'  => $settings['system_name_from']
        ];

        if ($mailSet['secure'] == 1)
            $mailSet['secure'] = 'ssl';

        if (!$mailSet['smtp']) {
            //not used custom smtp, sender have to be mailer@klienti.cz
            $mail->setFrom($mailSet['system_mail_from'], $mailSet['system_name_from'])
                ->addReplyTo($email);
        }else{

            //used custom smtp, sender have to be client email
            $mail->setFrom($mailSet['system_mail_from'])
                ->addReplyTo($email);


        }
        //$mailer = new \Nette\Mail\SendmailMailer;
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
		$result = $mailer->send($mail);


    }
}