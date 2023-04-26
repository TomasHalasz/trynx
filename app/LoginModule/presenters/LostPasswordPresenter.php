<?php

namespace App\LoginModule\Presenters;

use Nette\Application\UI\Form;

class LostPasswordPresenter extends \App\Presenters\BasePresenter {

    /**
    * @inject
    * @var \App\Model\UserManager
    */
    public $UserManager;       
    

    
    /**
     * Sign-in form factory.
     * @return Nette\Application\UI\Form
     */
    protected function createComponentLostPasswordForm() {
        $form = new Form;
       // $form->setTranslator($this->translator);
        $form->addText('email')
		->setHtmlAttribute('class','form-control')
		->setHtmlAttribute('placeholder','Email')
                ->setRequired('Zadejte svůj email.')
                ->addRule(Form::EMAIL, 'Prosím zadejte svůj platný email.');

        $form->addSubmit('submit', 'Požádat o nové heslo')
            ->setHtmlAttribute('data-sitekey',$this->parameters['sitekeyReCaptcha'])
            ->setHtmlAttribute('data-callback', 'onSubmit')
            ->setHtmlAttribute('data-action','submit')
            ->setHtmlAttribute('class','form-control btn-primary g-recaptcha');

        $form->onSuccess[] = array($this, "lostPasswordFormSubmitted");
        return $form;
    }

    /**
     * Sign in form submitted
     * @param \Nette\Application\UI\Form
     * @return void
     */
    public function lostPasswordFormSubmitted($form) {
	    $values = $form->getValues();

	    //nejdriv overit jestli uzivatel emailem existuje
	    //pokud neexistuje musime vratit 
	    $wasException = false;
	    try {
		$lPuser = $this->UserManager->getUser($values['email']);
		if ($lPuser != false)
		{
		    //posleme email s linkem na zmenu hesla
		    $confirm = $this->UserManager->genConfirmation($lPuser->id);

		    $confirmUrl = $this->link(':Login:LostPassword:NewPassword',$confirm['urlKey'],$values->email );		    
		    //dump(APP_DIR);

		    //send confirmation email		  
		    $this->emailService->sendNewPasswordEmail($values->email, $confirmUrl);		    
		    	
		    $this->flashMessage('Zkontrolujte svou schránku, email s odkazem pro obnovu hesla byl odeslán.', 'success');		    
		    $this->redirect(':Login:Homepage:default',array('email' => $values->email) );
		}else
		{
		    $this->flashMessage('Email nebyl nalezen. Zadejte svůj správný email nebo se znovu zaregistrujte.', 'danger' );
		    $this->redirect(':Login:LostPassword:default',array('email' => $values->email));
		}


		
	    } catch (Exception $e) {
		$this->flashMessage($e->getMessage(), 'warning');
		    
	    }



    }

    public function renderDefault() {
        $this->template->anyVariable = 'any value';
		$this->template->regEnabled =  empty(CMZ_NAME);
    }
    
    public function renderNewPassword($key,$email)
    {
	//dump($key);
	//dump($email);
	//ověříme platnost klíče
	$expDate = new \Nette\Utils\DateTime;
	if ($this->UserManager->getAll()->where('confirm_exp >= ? AND confirm_key = ? AND email = ?', $expDate,$key,$email)->fetch())
		$this['newPasswordForm']->setValues(array('key' => $key, 'email' => $email));
	else
	{
	    $this->flashMessage('Odkaz pro změnu hesla již není platný. Nechte si poslat nový.','danger');
	    $this->redirect(':Login:LostPassword:default');	    
	}
    }
    
    
/**
     * NewPassword form factory.
     * @return Nette\Application\UI\Form
     */
    protected function createComponentNewPasswordForm() {
        $form = new Form;
        //$form->setTranslator($this->translator);
        $form->addHidden('email');
        $form->addHidden('key');
        $form->addPassword('password')
			->setHtmlAttribute('class','form-control')
			->setHtmlAttribute('placeholder','Heslo')
			->setRequired('Prosím zadejte své heslo.')
			->addRule($form::MIN_LENGTH, 'Heslo je příliš krátké. Musí mít alespoň %d znaků.', 5)		
			->addRule($form::PATTERN, 'Heslo je příliš jednoduché. Musí obsahovat číslici.', '.*[0-9].*')
			->addRule($form::PATTERN, 'Heslo je příliš jednoduché. Musí obsahovat malé písmeno.', '.*[a-z].*');
        $form->addPassword('password2')
				->setHtmlAttribute('class','form-control')
				->setHtmlAttribute('placeholder','Heslo znovu')
				->setRequired(FALSE)				
				->addRule($form::EQUAL, 'Hesla se neshodují.',$form['password'])
                ->setRequired('Prosím zadejte znovu své heslo.');	

        $form->addSubmit('submit', 'Změnit heslo')
			->setHtmlAttribute('class','form-control btn-primary');

        $form->onSuccess[] = array($this, "newPasswordFormSubmitted");
        return $form;
    }    
    
	public function newPasswordFormSubmitted($form)
	{
	    try{
		if ($form['submit']->submittedBy)
		    {
			$values = $form->getValues();		    
			$retVal = $this->UserManager->changePass($values);
		    }

	    if ($retVal == 1)
	    {
		$this->flashMessage('Heslo bylo změněno.','success');		    		    		    
		$this->redirect(':Login:Homepage:default',array('email' => $values->email));
	    }
	    else{
		$this->flashMessage('Heslo nebylo změněno. Odkaz pro změnu hesla má platnost 3 hodiny. Nechte si poslat nový.','danger');		    		    		    
		$this->redirect(':Login:LostPassword:default',array('email' => $values->email));
	    }


	    }
	    catch (Exception $e) {
		    $errorMess = $e->getMessage(); 
		    $this->flashMessage($errorMess,'Error');		    		    

	    }	   
	    

	}	    

}
