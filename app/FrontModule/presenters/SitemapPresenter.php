<?php

namespace App\FrontModule\Presenters;
use Nette\Forms\Container;
use Nette\Application\UI\Form;

use Nette\Mail\Message,
    Nette\Utils\Strings;

use Nette,
	App\Model;

/**
 * Sitemap presenter.
 */
class SitemapPresenter extends BasePresenter
{
    private $articles,$itemAction,$tags;
	
	
    /**
    * @inject
    * @var \App\Model\BlogArticlesManager
    */
    public $BlogArticlesManager;   	
	
    /**
    * @inject
    * @var \App\Model\BlogCategoriesManager
    */
    public $BlogCategoriesManager;   		
	
    /**
    * @inject
    * @var \App\Model\BlogTagsManager
    */
    public $BlogTagsManager;   		
	
	
	public function renderDefault()
	{
	    $this->template->articles = $this->BlogArticlesManager->findAll();
		$this->template->categories = $this->BlogCategoriesManager->findAll();
		$this->template->tags = $this->BlogTagsManager->findAll();		

	}

	
	        
        

}

