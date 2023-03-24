<?php


namespace App\Console;
require_once __DIR__.'/../../vendor/autoload.php';

use Nette\Utils\DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


use Nette\Mail\Message,
    Nette\Utils\Strings;
use Nette\Mail\SendmailMailer;
use Nette\Templating;
use Tracy\Debugger;

class SendEmailCommand extends Command
{
	
	
    protected function configure()
    {
        $this->setName('app:sendemail')
            ->setDescription('Send email from queue');
    }

    protected function execute(InputInterface $input, OutputInterface $output):void
    {
	        $output->writeLn('start');
            $container = \App\Booting::boot()
                ->createContainer();

            $emailService       = $container->getByType('MainServices\EmailService');
            $emailingManager    = $container->getByType('App\Model\EmailingManager');

            $toSend = $emailingManager->findAllTotal()
                        ->where('to_send = 1');

            foreach($toSend as $one)
            {

                $output->writeLn($one->id);
                $dataOne = $one->toArray();
                unset($dataOne['singleEmailReplyTo']);
                //now send email
                try {
                    $emailService->sendMail2($dataOne, $one->cl_company_id);
                    $tmpNow = new DateTime();
                    $one->update(['send' => 1, 'to_send' => 0, 'dt_sent_srvc' => $tmpNow]);
                } catch (\Exception $e) {
                    //$output->writeLn('<error>' . $e->getMessage() . '</error>');
                    $one->update(['send' => 0, 'to_send' => 0, 'dt_sent_srvc' => null]);
                    Debugger::log( $e->getMessage() . ' id: ' . $one->id, 'SendEmailCommand');
                    continue;
                    //return 1; // non-zero return code means error
                }

            }
            //return 0; // zero return code means everything is ok


    }
}