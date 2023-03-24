<?php

namespace App\Controls;
use Contributte\Translation\PrefixedTranslator;
use Nette\Application\UI\Form;
use Nette\Application\UI\Control;
use Nette\Mail\Message,
    Nette\Utils\Strings;
use Nette\Mail\SendmailMailer;
use Nette,
	App\Model;
use Netpromotion\Profiler\Profiler;
class EmailControl extends Control
{

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;

    //private $messages;

    /** @var \App\Model\Base*/
    private $EmailingManager;
    
    private $mainTableName, $parentId;

    /** @var array
     */
    public $myEmailData;

    private $tmpAttach = NULL;

    public function __construct( Nette\Localization\Translator $translator, $EmailingManager, $mainTableName, $parentId)
    {
       // parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
        //$this->messages=$messages;
        $this->EmailingManager  = $EmailingManager;
        $this->mainTableName    = $mainTableName;
        $this->parentId         = $parentId;
        $this->translator       = $translator;

       // $this->translator->  prefix(['components.Email']);
    }

    public function render()
    {
        $mySection = $this->presenter->getSession('email-'.$this->parentId);
        $this->myEmailData = $mySection['myEmailData'];

        //$this->myEmailData = $emailData;
        $this->template->setFile(__DIR__ . '/Email.latte');
		$tmpVal = $this->myEmailData;
        //bdump($this->myEmailData, 'render');
		unset($tmpVal['workers']);

		//$this['emailForm']['workers']->setDefaultValue($this->myEmailData['workers']);
	    $this['emailForm']->setValues($tmpVal);
	    $this->template->emailData = $this->myEmailData;
        $this->template->render();

    }
    
    public function setEmailData($emailData)
    {
        $this->myEmailData = $emailData;
        $mySection = $this->presenter->getSession('email-'.$this->parentId);
        $mySection['myEmailData'] = $this->myEmailData;
    }
    
  protected function createComponentEmailForm($name)
    {	
        $form = new Form($this, $name);
	    //$form->setMethod('POST');
	    $form->addHidden('id',NULL);
	    $form->addHidden('attachment', NULL);
        $form->addText('singleEmailFrom', $this->translator->translate("Odesílatel"), 100, 100)
		    	->setHtmlAttribute('class','form-control input-sm')
			    ->setHtmlAttribute('placeholder',$this->translator->translate('Odesílatel'));
        $form->addtext('singleEmailTo', $this->translator->translate('Příjemce'), 100, 200)
		    	->setHtmlAttribute('class','form-control input-sm')
			    ->setHtmlAttribute('placeholder',$this->translator->translate('Příjemce'));
	    $form->addText('subject', $this->translator->translate('Předmět'), 100, 200)
		    	->setHtmlAttribute('class','form-control input-sm')
			    ->setHtmlAttribute('placeholder',$this->translator->translate('Předmět_emailu'));
        $form->addTextArea('body', 'Zpráva', 100,24 )
			    ->setHtmlAttribute('placeholder',$this->translator->translate('Zpráva'));
	
	
		$mySection = $this->presenter->getSession('email-'.$this->parentId);
		$this->myEmailData = $mySection['myEmailData'];
		if (isset($this->myEmailData['workers'])) {
            $arrWorkers = $this->myEmailData['workers'];
        }else{
		    $arrWorkers = [];
        }
		//bdump($arrWorkers, 'arrWorkers in form');
		//bdump($this->myEmailData, 'form');
		$form->addCheckboxList('workers', $this->translator->translate('Pracovníci'), $arrWorkers );
        
        
        $form->addSubmit('send', $this->translator->translate('Odeslat'))->setHtmlAttribute('class','btn btn-primary');
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
                ->setHtmlAttribute('class','btn btn-primary')
                ->setValidationScope([])
                ->onClick[] = array($this, 'stepBack');
	//	    ->onClick[] = callback($this, 'stepSubmit');

		$form->onSuccess[] = array($this,'SubmitEditSubmitted');
		return $form;
    }

    public function stepBack()
    {	    
        $this->presenter->emailModalShow = FALSE;
        $this->redrawcontrol('emailModalHandle');

    }		

    public function SubmitEditSubmitted(Form $form)
    {
        try {
            $mySection = $this->presenter->getSession('email-'.$this->parentId);
            $this->myEmailData = $mySection['myEmailData'];
           // bdump($this->myEmailData, 'senddata myEmailData');
            $data = $form->values;
            if ($form['send']->isSubmittedBy()) {
                //parse $data['singleEmailTo']
				//bdump($data,'send data');
				$arrWorkers = $this->myEmailData['workers'];
				foreach($data['workers'] as $key => $one)
				{
					if ($data['singleEmailTo'] != '') {
						$data['singleEmailTo'] .= ';';
					}
					$data['singleEmailTo'] .= $one . ' <' . $arrWorkers[$one] . '>';
				}
				unset($data['workers']);
				
                $emailTo = str_getcsv($data['singleEmailTo'], ';');

                $emailFrom = $data['singleEmailFrom'];
                $subject = $data['subject'];
                $body = $data['body'];
                //bdump($data, 'send data');
                //if (!is_null($this->tmpAttach)){

                $data['attachment'] = $this->myEmailData['attachment'];
                //}

                $attachment = json_decode($data['attachment'], true);

                $this->presenter->emailService->sendMail($this->presenter->settings, $emailFrom, $emailTo, $subject, $body, $attachment);

                //$email = array();
                //$email[] = array('to_email' => $data['singleEmailTo'], 'to_name' => '');

                //now send email
                //$mail = new Message;
                //$mail->setFrom('mailer@klienti.cz',$this->presenter->settings->name)
                //->addReplyTo($data['singleEmailFrom'])
                //->setSubject($data['subject'])
                //->setHtmlBody($data['body']);
                //foreach($emailTo as $one)
                //{
                //$mail->addTo($one);
                //}


                //				->addCc('info@faktury.cz','2HCS Fakturace')
                //$mailer = new SendmailMailer;
                //$mailer->send($mail);

                //26.07.2018 - connect parent table
                $data[$this->mainTableName . '_id'] = $this->parentId;

                if (is_null($data['attachment']))
                    unset($data['attachment']);

                //save to cl_emailing
                $this->EmailingManager->insert($data);

                $this->presenter->emailSetStatus(); //call setStatus in presenter
                $this->presenter->flashMessage($this->translator->translate('Email_byl_odeslán.'), 'success');
                $this->presenter->redrawControl('flashMessage');
                $this->presenter->emailModalShow = FALSE;
                $this->redrawcontrol('emailModalHandle');
            }
        }
        catch (\Exception $e)
        {
            $this->presenter->flashMessage($e->getMessage());
            $this->presenter->redrawControl('flashMessage', 'warning');
        }
    }

    public function handleRemoveFile(int $key): void
    {
        $mySection = $this->presenter->getSession('email-'.$this->parentId);
        $this->myEmailData = $mySection['myEmailData'];

        bdump($this->myEmailData, 'emailData handleRemoveFile');
        $tmpData = json_decode($this->myEmailData['attachment'], true);
        unset($tmpData[$key]);
        $this->myEmailData['attachment'] = json_encode($tmpData);
        //bdump($this->myEmailData, 'emailData after handleRemoveFile');

        $mySection['myEmailData'] = $this->myEmailData;
        $this->redrawControl('attachment');

    }

}