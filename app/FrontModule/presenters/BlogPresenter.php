<?php

namespace App\FrontModule\Presenters;
use Nette\Application\UI\Form;
use App\Model;

use Nette\Mail\Message,
    Nette\Utils\Strings;
use Nette\Mail\SendmailMailer;

class BlogPresenter extends BasePresenter
{
	private $blog_comments_id;
	
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
	




	
	public function renderDefault($page = 1)
	{
		$this->template->categories = $this->BlogCategoriesManager->findAll()->order('order_cat,name');
		$this->template->category	= $this->BlogCategoriesManager->find($this->blog_categories_id);
		$this->template->tagsAll	= $this->BlogTagsManager->findAll()->order('name');		
		$tmpFav = $this->BlogArticlesManager->findAll()->where(array('favorite' => 1))->order('article_date DESC');
		$tmpBlog = $this->BlogArticlesManager->findAll()->order('article_date DESC');
		if (!is_null($this->blog_categories_id))
		{
			$tmpFav = $tmpFav->where(array('blog_categories_id' => $this->blog_categories_id));
			$tmpBlog = $tmpBlog->where(array('blog_categories_id' => $this->blog_categories_id));
		}
		$this->template->favorites	= $tmpFav->limit(6);
		$this->template->lastcomments = $this->BlogCommentsManager->findAll()->limit(10)->order('date DESC');

		
		
		//paginator start
		$paginator = new \Nette\Utils\Paginator;
		$ItemsOnPage = 5;
		$totalItems = $tmpBlog->count();
		$paginator->setItemCount($totalItems); // celkový počet položek (např. článků)
		$paginator->setItemsPerPage($ItemsOnPage); // počet položek na stránce
		$paginator->setPage($page); // číslo aktuální stránky, číslováno od 1
		$pages = ceil($totalItems/$ItemsOnPage);
		$this->template->paginator = $paginator;
		$steps=array();		
		for ($i = 1; $i <= $pages; $i++) {
		    $steps[]=$i;
		}
		$this->template->steps = $steps;
		$this->template->articles = $tmpBlog->limit($paginator->getLength(), $paginator->getOffset());
		
		//paginator end		
		
		
	}
	
	
	public function renderTags($page = 1, $blog_tags_id = 0)
	{
		$this->template->categories = $this->BlogCategoriesManager->findAll()->order('order_cat,name');
		$this->template->tagsAll	= $this->BlogTagsManager->findAll()->order('name');
		$this->template->tags	= $this->BlogTagsManager->find($blog_tags_id);
		$tmpFav = $this->BlogArticlesManager->findAll()->where(array('favorite' => 1))->order('article_date DESC');
		$tmpBlog = $this->BlogArticlesManager->findAll()->order('article_date DESC');
		if ( $blog_tags_id > 0 )
		{
			$tmpFav = $tmpFav->where('tags LIKE ?', '%'.$blog_tags_id.'%');
			$tmpBlog = $tmpBlog->where('tags LIKE ?', '%'.$blog_tags_id.'%');
		}
		$this->template->favorites	= $tmpFav->limit(6);
		$this->template->lastcomments = $this->BlogCommentsManager->findAll()->limit(10)->order('date DESC');
		
		
		
		//paginator start
		$paginator = new \Nette\Utils\Paginator;
		$ItemsOnPage = 5;
		$totalItems = $tmpBlog->count();
		$paginator->setItemCount($totalItems); // celkový počet položek (např. článků)
		$paginator->setItemsPerPage($ItemsOnPage); // počet položek na stránce
		$paginator->setPage($page); // číslo aktuální stránky, číslováno od 1
		$pages = ceil($totalItems/$ItemsOnPage);
		$this->template->paginator = $paginator;
		$steps=array();		
		for ($i = 1; $i <= $pages; $i++) {
		    $steps[]=$i;
		}
		$this->template->steps = $steps;
		$this->template->articles = $tmpBlog->limit($paginator->getLength(), $paginator->getOffset());
		//paginator end		
	}	


	public function renderSearch($page = 1, $search = "")
	{
		$this->template->categories = $this->BlogCategoriesManager->findAll()->order('order_cat,name');
		$this->template->tagsAll	= $this->BlogTagsManager->findAll()->order('name');
		$this->template->tags	= 0;
		$this->template->searchString = $search;
		$tmpFav = $this->BlogArticlesManager
							->findAll()->where(array('favorite' => 1))
							->where('title LIKE ? OR description LIKE ? OR content_txt LIKE ?', '%'.$search.'%', '%'.$search.'%', '%'.$search.'%' )
							->order('article_date DESC');
		$tmpBlog = $this->BlogArticlesManager
							->findAll()
							->where('title LIKE ? OR description LIKE ? OR content_txt LIKE ?', '%'.$search.'%', '%'.$search.'%', '%'.$search.'%' )
							->order('article_date DESC');

		$this->template->searchCount = $tmpBlog->count();
		$this->template->favorites	= $tmpFav->limit(6);
		$this->template->lastcomments = $this->BlogCommentsManager->findAll()->limit(10)->order('date DESC');
		
		
		
		//paginator start
		$paginator = new \Nette\Utils\Paginator;
		$ItemsOnPage = 5;
		$totalItems = $tmpBlog->count();
		$paginator->setItemCount($totalItems); // celkový počet položek (např. článků)
		$paginator->setItemsPerPage($ItemsOnPage); // počet položek na stránce
		$paginator->setPage($page); // číslo aktuální stránky, číslováno od 1
		$pages = ceil($totalItems/$ItemsOnPage);
		$this->template->paginator = $paginator;
		$steps=array();		
		for ($i = 1; $i <= $pages; $i++) {
		    $steps[]=$i;
		}
		$this->template->steps = $steps;
		$this->template->articles = $tmpBlog->limit($paginator->getLength(), $paginator->getOffset());
		//paginator end		
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