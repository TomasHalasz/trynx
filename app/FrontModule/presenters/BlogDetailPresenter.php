<?php

namespace App\FrontModule\Presenters;
use Nette\Application\UI\Form;
use App\Model;

use Exception;
use Nette\Mail\Message,
    Nette\Utils\Strings;
use Nette\Mail\SendmailMailer;

class BlogDetailPresenter extends BasePresenter
{
	private $blog_comments_id,$newCommentId = 0;
	
    /** @persistent */
    public $blog_categories_id;	


    /**
    * @inject
    * @var \App\Model\BlogCommentsManager
    */
    public $BlogCommentsManager;    	
	
    /**
    * @inject
    * @var \App\Model\BlogImagesManager
    */
    public $BlogImagesManager;    
      
    /**
    * @inject
    * @var \App\Model\BlogGalleryManager
    */
    public $BlogGalleryManager;  

    /**
    * @inject
    * @var \App\Model\BlogCategoriesManager
    */
    public $BlogCategoriesManager;      
    	
    /**
    * @inject
    * @var \App\Model\BlogArticlesManager
    */
    public $BlogArticlesManager;   	
	
    /**
    * @inject
    * @var \App\Model\BlogTagsManager
    */
    public $BlogTagsManager;   	
	
		


	public function renderDefault($id, $develop = NULL)
	{
                $this->template->develop = $develop;
		$this->template->newCommentId = $this->newCommentId;
		$tmpArticle = $this->BlogArticlesManager->find($id);
		if (!$tmpArticle)
		{
		   //$this->setView('404');
		    throw new \Nette\Application\BadRequestException("Stránka neexistuje",404);
		    //$this->setView('400');
		}else{
		    $this->template->article = $tmpArticle;
		    $this->template->categories = $this->BlogCategoriesManager->findAll()->order('order_cat,name');
		    $this->template->category	= $this->BlogCategoriesManager->find($tmpArticle->blog_categories_id);
		    $this->template->tagsAll	= $this->BlogTagsManager->findAll()->order('name');		
		    $tmpFav = $this->BlogArticlesManager->findAll()->order('article_date DESC');
		    if (!is_null($this->blog_categories_id))
		    {
			    $tmpFav = $tmpFav->where(array('blog_categories_id' => $this->blog_categories_id));
		    }
		    $this->template->favorites	= $tmpFav->limit(6);
		    $this->template->tags = $this->BlogTagsManager->findAll()->where('id IN ?',json_decode($tmpArticle['tags'],TRUE));

		    $tmpRelatedArticles = $this->BlogArticlesManager->findAll();

		    $tmpTags = '';
		    $i = 1;
		    foreach (json_decode($tmpArticle['tags'],TRUE) as $one) {
			    if ($i > 1)
			    {
				    $tmpTags = $tmpTags . ' OR';
			    }
			    $tmpTags = $tmpTags . " tags LIKE '%".$one."%'";
			    $i++;
		    }
		    //dump($tmpTags);
		    //die;
		    if ($tmpTags != '')
		    {
			    $tmpRelatedArticles = $tmpRelatedArticles->where($tmpTags);
		    }
		    $tmpRelatedArticles=$tmpRelatedArticles->where('id != ?', $tmpArticle->id);

		    $this->template->relatedArticles = $tmpRelatedArticles;

		    $this->template->blog_comments = $this->BlogCommentsManager->findAll()->where('blog_articles_id = ?', $id)->where('blog_comments_id IS NULL')->order('date ASC');

		    $this->template->lastcomments = $this->BlogCommentsManager->findAll()->limit(10)->order('date DESC');

		    $def_data = array();
		    $def_data['blog_articles_id'] = $id;
		    $def_data['blog_comments_id'] = $this->blog_comments_id;
		    //$def_data['articles_id'] = 777;
		    //$def_data['comments_id'] = 888;
		    $def_data['name'] = '';
		    $def_data['email'] = '';
		    $def_data['website'] = '';
		    $def_data['comment'] = '';
		    $this['submitComment']->setValues($def_data); //Warning - it's important to use setValues, because setDefaults doesn't fill values on ajax invalidation

		    //increase readed counter
		    $values = array();
		    $values['id'] = $id;
		    $values['readers'] = $tmpArticle->readers + 1;
		    $this->BlogArticlesManager->update($values);		
		}
		
		

	}
	
	
	
	protected function createComponentSubmitComment($name)
	{	
		$form = new Form($this, $name);
	    $form->addText('firstname', 'Jméno:', 40, 40)
			->setAttribute('class', 'firstname');
	    $form->addText('url')
		    ->setAttribute('class','firstname');
	    $form->addText('message')
		    ->setAttribute('class','firstname');	    	    
		$form->addText('name', 'Jméno:', 40, 40)
			->setRequired('Zadejte prosím své jméno')
			->setAttribute('placeholder','Jméno')				
			->setAttribute('aria-required','true');
		$form->addText('email', 'Email:', 40, 40)
			->setRequired('Zadejte svůj platný email') 
			->addRule($form::EMAIL,'Email musí být v platném formátu')
			->setAttribute('placeholder','Email')
			->setAttribute('aria-required','true');	    
		//$form->addText('website', 'Web:', 60, 60)
		//	->setAttribute('placeholder','web')
		//	->setAttribute('class', 'form-control');	    	    

		$form->addTextArea('comment', 'Váš komentář:', 70, 7)
			->setAttribute('placeholder','Místo pro váš komentář');	    
		$form->addHidden('blog_articles_id',NULL);
		$form->addHidden('blog_comments_id',NULL);	    
		$form->addSubmit('send', 'Odeslat')->setAttribute('class','btn btn-primary button');
		$form->onSuccess[] = array($this,'submitCommentSubmitted');
		return $form;
		
	}
	
	public function submitCommentSubmitted(Form $form)
	{
	    $data=$form->values;
	    //dump($data);	    
	    //die;
	    if (!empty($data['firstname'])  || !empty($data['url']) || !empty($data['message']))
		{
			$this->redirect('this');
		}else{
			unset($data['firstname']);		
			unset($data['message']);		
			unset($data['url']);		
			if ($data['blog_comments_id'] == '')
			{
				$data['blog_comments_id'] = null;
			}
			$data['date']= new \Nette\Utils\DateTime;
			$data['user_ip'] = $this->getIp();
			if ($form['send']->isSubmittedBy())
			{
				$newComment = $this->BlogCommentsManager->insert($data);
				//die;
				$this->newCommentId = $newComment->id;
				$this->flashMessage('Komentář byl odeslán.', 'success');
			}
			$form->setValues(array(),TRUE);
			$valuesSend = array();
			$valuesSend['body'] = $data;
			$valuesSend['subject'] = 'Nový komentář na klienti.cz';

			$this->sendEmail($valuesSend);
			//$this->redrawControl('test');
			//$this->redrawControl('flashMessages');					
			$this->redrawControl('commentsSection');			
			//$this->redirect('this','#test');
	    }


	}	
	
	public function getIp()
	{
		$httpRequest = $this->context->getByType('Nette\Http\Request');
		return $httpRequest->getRemoteAddress();
	}
	   
	public function sendEmail($valuesSend)
	{
		$template = $this->createTemplate();
		$template->setFile(__DIR__ .'/../templates/Article/emailComment.latte');
		//prepare of email content
		$template->emlBody = $valuesSend['body'];
		$template->article = $this->BlogArticlesManager->find($valuesSend['body']->blog_articles_id);
		$subject = $valuesSend['subject'];

		//$template->render();
		$mail = new Message;
		$mail->setFrom('web <info@klienti.cz>')
		    ->addTo('info@klienti.cz','2H C.S. s.r.o.')
		    ->setSubject($subject)
		    ->setHtmlBody($template);
		
		$mailer = new SendmailMailer;
		$mailer->send($mail);			    
		
	}	
	
	
	
	protected function createComponentSearchBlog($name)
	{	
		$form = new Form($this, $name);
		$form->addText('search', 'Hledat:', 100, 100)
			->setAttribute('placeholder','Hledat')				
			->setAttribute('class', 'form-control');
		$form->addSubmit('send', 'Hledat')->setAttribute('class','btn btn-primary');
		$form->onSuccess[] = array($this,'searchBlogSubmitted');
		return $form;
	}
	
	public function searchBlogSubmitted(Form $form)
	{
		if ($form['send']->isSubmittedBy())
		{
			$data=$form->values;
			$this->redirect('Blog:Search', array('search' => $data['search']));
		}
	}	
	

	
}