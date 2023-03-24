<?php

namespace App\AdministrationModule\Presenters;

use Nette,
	App\Model;


use Nette\Application\UI\Form;
use Nette\Image;
use Exception;
/**
 * Administrace Comments presenter.
 *
 * @author     Tomáš Halász
 * @package    
 */
class AdminCommentsPresenter extends SecuredPresenter
{
	private $comments_id,$main_id,$comments;


	/**
	* @inject
	* @var \App\Model\BlogImagesManager
	*/
	public $BlogImagesManager;        
	
	
	/**
	* @inject
	* @var \App\Model\BlogCommentsManager
	*/
	public $BlogCommentsManager;        
	
  
	public function actionDefault()
	{
		$this->comments = $this->BlogCommentsManager->findAll()->where('admin_answer != 1')->order('answered ASC,date DESC');
				
	}
	
	public function renderDefault()
	{
		$this->template->comments = $this->comments;
	}	
	
	public function actionAnswer($id,$main_id)
	{
	   	//$mySet = $this->getSession('mySet');
		$this->comments_id = $id;
		$this->main_id = $main_id;
	}
  
	public function renderAnswer()
	{
		if ($this->comments_id>0)
	    {
			$this->template->comments =  $this->BlogCommentsManager->findAll()->where('id = ? OR blog_comments_id = ?',$this->comments_id,$this->comments_id)->order('date ASC');
			$this->template->comment =  $this->BlogCommentsManager->find($this->comments_id);
			$def_data = array();
			$def_data['blog_comments_id'] = $this->comments_id;
			$def_data['admin_answer'] = 1;
			$def_data['blog_articles_id'] = $this->template->comment->articles_id;
			$def_data['name'] = $this->user->getIdentity()->username;
			$def_data['email'] = $this->user->getIdentity()->email;
			$def_data['website'] = 'www.klienti.cz';
			$this['editAnswer']->setDefaults($def_data);
	    }else{            
			$this->template->comment = array();
		}
	}		

	protected function createComponentEditAnswer($name)
	{	
		$form = new Form($this, $name);
		$form->addText('name', 'Jméno:', 40, 40)
			->setRequired('Zadejte prosím své jméno')
			->setAttribute('placeholder','Jméno')				
			->setAttribute('aria-required','true')						    
			->setAttribute('class', 'form-control');
		$form->addText('email', 'Email:', 40, 40)
			->setRequired('Zadejte svůj platný email') 
			->addRule($form::EMAIL,'Email musí být v platném formátu')
			->setAttribute('placeholder','Email')
			->setAttribute('aria-required','true')						    		    
			->setAttribute('class', 'form-control');	    
		$form->addText('website', 'Web:', 60, 60)
			->setAttribute('placeholder','web')
			->setAttribute('class', 'form-control');	    	    
		$form->addTextArea('comment', 'Váš komentář:', 70, 7)
			->setAttribute('placeholder','Místo pro váš komentář')				
			->setAttribute('class', 'form-control');	    
		$form->addHidden('blog_articles_id');
	    $form->addHidden('admin_answer');
		$form->addHidden('blog_comments_id');	    
		$form->addSubmit('send', 'Odeslat')->setAttribute('class','btn btn-primary');
		$form->onSuccess[] = array($this,"SubmitCommentSubmitted");
		return $form;
	}
	
	public function SubmitCommentSubmitted(Form $form)
	{
	    $data=$form->values;
	    //dump($data);	    
	    if ($data['blog_comments_id'] == '')
		{
			$data['blog_comments_id'] = null;
		}
	    $data['date']= new \Nette\Utils\DateTime;
		if ($form['send']->isSubmittedBy())
		{
		    $this->BlogCommentsManager->insert($data);
		    //set answered at original comment
		    $data2 = array();
		    $data2['answered'] = 1;
			$data2['id'] = $this->main_id;
		    //$id = $data['comments_id'];
		    $this->BlogCommentsManager->update($data2);
		    //die;
		    $this->flashMessage('Komentář byl odeslán.', 'success');
		}
	    $form->setValues(array(),TRUE);
	    //$this->redrawControl('comments');
	    $this->redirect('this');
	}
	
	
	public function handleEraseComment($id)
	{
	    try{
			$this->BlogCommentsManager->delete($id);
	    }
	    catch (Exception $e) {
			$errorMess = $e->getMessage(); 
			$this->flashMessage($errorMess,'danger');
	    }	    	    
	    $this->redirect('AdminComments:');
	}






}
