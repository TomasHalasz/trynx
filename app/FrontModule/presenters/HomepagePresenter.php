<?php

namespace App\FrontModule\Presenters;

class HomepagePresenter extends BasePresenter
{

    	
    /**
    * @inject
    * @var \App\Model\BlogArticlesManager
    */
    public $BlogArticlesManager;   	
		
	
	
	public function renderDefault()
	{
		$tmpNew = $this->BlogArticlesManager->findAll()->order('article_date DESC')->limit(3);		
		$this->template->blogNews = $tmpNew;
		//$this->session->destroy();
	}
}