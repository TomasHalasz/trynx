<?php
namespace App\AdministrationModule\Presenters;

use Nette\Application\UI;
use Exception;
use Nette\Application\UI\Form;

/**
 * Sign in/out presenters.
 */
class SignPresenter extends BasePresenter
{


	/**
	 * Sign-in form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSignInForm($name)
	{
		//	$form = new UI\Form;
		$form = new Form($this, $name);
		$form->addText('username', 'Jméno:')
			->setRequired('Prosím zadejte jméno.')
			->setAttribute('placeholder','Jméno')				
			->setAttribute('class', 'form-control');			;

		$form->addPassword('password', 'Heslo:')
			->setRequired('Prosím zadejte heslo.')
			->setAttribute('placeholder','Heslo')				
			->setAttribute('class', 'form-control');			;

		//$form->addCheckbox('remember', 'Keep me signed in');
                $form->addHidden('remember',0);
		$form->addSubmit('send', 'Přihlásit')->setAttribute('class','btn btn-default');

		// call method signInFormSucceeded() on success
		//$form->onSuccess[] = $this->signInFormSucceeded;
		$form->onSuccess[] = array($this,'signInFormSucceeded');
		//$form->onSuccess[] = $this->SubmitEditSubmitted;
		return $form;
		
	}
	public function beforeRender()
	{
	    parent::beforeRender();
	    $this->template->opener = false;
	    $this->template->commentsNew = 0;
	    $this->template->ordersNew = 0;
	}


	public function signInFormSucceeded(Form $form)
	{
		$values = $form->getValues();

		if ($values->remember) {
			$this->getUser()->setExpiration('+ 7 days');
		} else {
			$this->getUser()->setExpiration('+ 60 minutes');
		}

		try {
		    if ($values->username == 't.halasz@2hcs.cz' || $values->username == 'info@faktury.cz') {
                $this->getUser()->login($values->username, $values->password);
                //dump($this->user->getIdentity()->getRoles());
                //die;
            }
		} catch (Exception $e) {
		    //Nette\Security\AuthenticationException $e
			$form->addError($e->getMessage());
			return;
		}

		$this->redirect(':Administration:AdminMain:default');
	}



	public function actionOut()
	{
		$this->getUser()->logout();
		$this->flashMessage('Byli jste odhlášeni.');
		$this->redirect('in');
	}

}
