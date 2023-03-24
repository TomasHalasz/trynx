<?php


namespace App\Console;
require_once __DIR__.'/../../vendor/autoload.php';

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


use Nette\Mail\Message,
    Nette\Utils\Strings;
use Nette\Mail\SendmailMailer;
use Nette\Templating;

class CalendarPlaneCommand extends Command
{
	
	
    protected function configure()
    {
        $this->setName('app:calendarplane')
            ->setDescription('Creates planed events');
    }

    protected function execute(InputInterface $input, OutputInterface $output):void
    {
        try {		
	        $output->writeLn('start');
            $container = \App\Booting::boot()
                ->createContainer();

            $companies          = $container->getByType('App\Model\CompaniesManager');
            $partnersEvent      = $container->getByType('App\Model\PartnersEventManager');
            $partnersBook       = $container->getByType('App\Model\PartnersManager');
            $partnersBookWorkers = $container->getByType('App\Model\PartnersBookWorkersManager');
            $partnersEventType  = $container->getByType('App\Model\PartnersEventTypeManager');
            $status             = $container->getByType('App\Model\StatusManager');
            $numberSeries       = $container->getByType('App\Model\NumberSeriesManager');
            $emailingText       = $container->getByType('App\Model\EmailingTextManager');
            $usersManager       = $container->getByType('App\Model\UsersManager');
            $arraysManager      = $container->getByType('App\Model\ArraysManager');
            $emailService       = $container->getByType('MainServices\EmailService');
            $calendarPlane      = $container->getByType('App\Model\CalendarPlaneManager');
            $linkGenerator      = $container->getByType('Nette\Application\LinkGenerator');

            //$presenter = $this->getHelper('presenter')->getPresenter();
		
		$planed = $calendarPlane->findAllTotal()
			->where('start_date<=NOW() AND end_date>=NOW() AND DATE_ADD(DATE_ADD(DATE_ADD(last_use,INTERVAL repeat_days DAY),INTERVAL repeat_weeks WEEK),INTERVAL repeat_months MONTH)<=NOW()');
		
		foreach($planed as $one)
		{
		    $output->writeLn($one->id);

		    // if is planed for helpdesk, insert planed to cl_partners_event
		    if ($one->type == 1)
		    {
			$data = array();
			$data['cl_company_id'] =  $one->cl_company_id;
			$data['cl_partners_book_id'] = $one->cl_partners_book_id;

			//if is last_use before start_date, we will use start_date as date time of receiving event
			//otherwise we use current datetime
			if ($one['last_use'] < $one['start_date'])
			{
			    $data['date_rcv'] = $one['start_date'];
			}else{
			    $data['date_rcv'] = new \Nette\Utils\DateTime;
			}
			
			$data['work_label'] = $one['event_title'];
			
			if ($tmpEventType = $partnersEventType->findAllTotal()->where(array('cl_company_id' => $one->cl_company_id, 'default_event' => 1, 'main_event' => 1))->fetch())
			{
			    $data['cl_partners_event_type_id'] = $tmpEventType->id;				
			}
			
			$data['create_by'] = 'timer';
			$data['cl_partners_category_id'] = $one->cl_partners_book->cl_partners_category_id;
			$data['date_end'] = new \Nette\Utils\DateTime;
			$data['date_end'] = $data['date_end']->modify('+'.($one->cl_partners_book->cl_partners_category->react_time).' hours');

			if ($tmpStatus = $status->findAllTotal()->where(array('cl_company_id' => $one->cl_company_id, 'status_use' => 'partners_event', 's_new' => 1))->fetch())			
			{
				$data['cl_status_id'] = $tmpStatus->id;
			}
					
			if ($nSeries = $numberSeries->getNewNumber('partners_event',NULL,NULL,$one->cl_company_id))
			{
				$data['event_number'] = $nSeries['number'];
				$data['cl_number_series_id'] = $nSeries['id'];
			}
			
			$tmpCompany = $one->cl_company;
			$tmpPartnersBook = $one->cl_partners_book;
			$row = $partnersEvent->insertForeign($data);

			if ($one->email_enabled && $tmpCompany->hd3_emailing_text_id !== NULL)
			{
			    
				$emailTo  = $usersManager->findAllTotal()->
								where(array('event_manager' => 1, 
									    'cl_company_id' => $tmpCompany->id))->
								select("id, CONCAT(name,' <',email,'>') AS user")->fetchPairs('id','user');
				if (count($emailTo) == 0)
				{
					$emailTo  = $usersManager->findAllTotal()->
								where(array('cl_company_id' => $tmpCompany->id))->
								limit(1)->
								select("id, CONCAT(name,' <',email,'>') AS user")->fetchPairs('id','user');									
				}				    
			    
			    
				$dataEml = array();
				$dataEml['singleEmailTo'] = implode(';', $emailTo);


                if ($tmpCompany->email_income != '') {
                    $dataEml['singleEmailReplyTo'] = $tmpCompany->name . ' <' . $tmpCompany->email_income . '>';
                }
                $dataEml['singleEmailFrom'] = $tmpCompany->name . ' <' . $tmpCompany->email . '>';

                //$dataEml['singleEmailFrom'] = $tmpCompany->email_income;
				
				$tmpEmlText = $emailingText->getEmailingText('','','',$tmpCompany->hd3_emailing_text_id, $tmpCompany->id);
				$dataEml['subject'] = '['.$data['event_number'].']['.$tmpEmlText['subject'].'] '.$data['work_label'];
				//$template= new \Nette\Templating\FileTemplate(__DIR__.'/../ApplicationModule/templates/Emailing/email.latte');
				$latte = new \Latte\Engine;
				//\Latte\Macros\CoreMacros::install($latte->getCompiler());
				//$template->registerFilter($latte);							
				//$link = $presenter->link('//:Application:Helpdesk:edit', array('id' => $row->id));
                $link = $linkGenerator->link('Application:Helpdesk:edit', array('id' => $row->id));
				$tmpKontakt = '<tr><td>Kontakt:</td><td>'.$tmpPartnersBook->person.', Email:'. $tmpPartnersBook->email.', Tel.:'.$tmpPartnersBook->phone.'</td></tr>';

				if (isset($tmpPartnersBook->cl_partners_category['id']))
				{
					$tmpCategory = '<tr><td>Důležitost:</td><td>'.$tmpPartnersBook->cl_partners_category->category_name.'</td></tr>';
				}else{
					$tmpCategory = '<tr><td>Důležitost:</td><td>Není nastavena</td></tr>';
				}

				$paramEml['body'] = $tmpEmlText['body'].
								'<table>'.
								'<tr><td>Odesílatel:</td><td>'. $tmpPartnersBook->company.', </td></tr>'.
								$tmpKontakt.
								$tmpCategory.
								'<tr><td>Odkaz do helpdesku:</td><td><a href="'.$link.'" title="Otevře záznam helpdesku">'.$link.'</a>'.
								'<tr><td>Zadání:</td><td>'.$one['event_title'].'</td></tr>'.
								'</table>';							

				$dataEml['body'] = $latte->renderToString(__DIR__.'/../ApplicationModule/templates/Emailing/email.latte',$paramEml);

				$emailTo  = $usersManager->findAllTotal()->
							where(array('event_manager' => 1, 
										'cl_company_id' => $tmpCompany->id))->
							select("id, CONCAT(name,' <',email,'>') AS user")->fetchPairs('id','user');
				if (count($emailTo) == 0)
				{
					$emailTo  = $usersManager->findAllTotal()->
								where(array('cl_company_id' => $tmpCompany->id))->
								limit(1)->
								select("id, CONCAT(name,' <',email,'>') AS user")->fetchPairs('id','user');									
				}							
				$dataEml['singleEmailTo'] = implode(';', $emailTo);

				//now send email
				$emailService->sendMail2($dataEml,$tmpPartnersBook->cl_company_id);			
			}	
			
			
		    }
		    $one->update(array('last_use' => $data['date_rcv']));
		    
		}
            //return 0; // zero return code means everything is ok

        } catch (\Nette\Mail\SmtpException $e) {
            //$output->writeLn('<error>' . $e->getMessage() . '</error>');
            //return 1; // non-zero return code means error
        }
    }
}