<?php

namespace App\FrontModule\Presenters;

use Nette\Application\UI\Form;

class ArticlePresenter extends BasePresenter
{
        //put your code here



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


    public function renderChangelist(){

        $this->template->changelist = nl2br(file_get_contents( __DIR__ . "/../../../changelist.txt"));

    }

    public function renderChangelistPublic(){

        $this->template->changelist = nl2br(file_get_contents( __DIR__ . "/../../../changelist.txt"));


        $this->template->article = [];
        $this->template->categories = $this->BlogCategoriesManager->findAll()->order('order_cat,name');
        $this->template->category	= $this->BlogCategoriesManager->findAll();
        $this->template->tagsAll	= $this->BlogTagsManager->findAll()->order('name');
        $tmpFav = $this->BlogArticlesManager->findAll()->order('article_date DESC');

        $this->template->favorites	= $tmpFav->limit(6);
        $this->template->tags = $this->BlogTagsManager->findAll();

        $tmpRelatedArticles = $this->BlogArticlesManager->findAll();

        $this->template->relatedArticles = $tmpRelatedArticles;

        $this->template->blog_comments = [];

        $this->template->lastcomments = [];


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