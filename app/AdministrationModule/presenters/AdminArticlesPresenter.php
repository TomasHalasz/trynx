<?php

namespace App\AdministrationModule\Presenters;

use Nette,
    App\Model;

use Nette\Application\UI\Form;
use Nette\Image;
use Exception;
use DOMDocument;
use Nette\Utils\Arrays;

/**
 * Administrace presenter.
 *
 * @author     Tomáš Halász
 * @package
 */
class AdminArticlesPresenter extends SecuredPresenter
{
    private $article_list_cz, $article_list_eng, $article_id, $language, $importCount, $addedUrl;

    /** @persistent */
    public $page;

    /** @persistent */
    public $filter;

    /**
     * @inject
     * @var \App\Model\BlogTagsManager
     */
    public $BlogTagsManager;

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
     * @var \App\Model\UsersManager
     */
    public $UsersManager;


    public function actionDefault()
    {
        //  $this->article_list_cz = $this->BlogArticlesManager->getArticles()->order('create_time DESC');

    }

    public function renderDefault($page = 1)
    {

        //$this->template->article_list_cz = $this->article_list_cz;
        //paginator start
        $paginator = new Nette\Utils\Paginator;
        $ItemsOnPage = 15;
        if (!is_null($this->filter)) {
            $totalItems = $this->BlogArticlesManager->findAll()->where('articles.name LIKE ? OR title LIKE ? OR categories.name LIKE ?', '%' . $this->filter . '%', '%' . $this->filter . '%', '%' . $this->filter . '%')->count();
            $this['searchArticle']->setValues(array('search' => $this->filter));
        } else {
            $totalItems = $this->BlogArticlesManager->findAll()->count();
        }

        $paginator->setItemCount($totalItems); // celkový počet položek (např. článků)
        $paginator->setItemsPerPage($ItemsOnPage); // počet položek na stránce
        $paginator->setPage($page); // číslo aktuální stránky, číslováno od 1
        $pages = ceil($totalItems / $ItemsOnPage);
        $this->template->paginator = $paginator;
        $steps = array();
        for ($i = 1; $i <= $pages; $i++) {
            $steps[] = $i;
        }
        $this->template->steps = $steps;

        //$this->template->articles = $this->BlogArticlesManager->getArticles()->where('categories_id = ?', $this->categories_id)->order('create_time DESC')->limit($paginator->getLength(), $paginator->getOffset());
        if (!is_null($this->filter)) {
            $this->template->article_list_cz = $this->BlogArticlesManager->findAll()->
            where('articles.name LIKE ? OR title LIKE ? OR categories.name LIKE ?', '%' . $this->filter . '%', '%' . $this->filter . '%', '%' . $this->filter . '%')->
            order('article_date DESC')->limit($paginator->getLength(), $paginator->getOffset());
        } else {
            $this->template->article_list_cz = $this->BlogArticlesManager->findAll()->order('article_date DESC')->limit($paginator->getLength(), $paginator->getOffset());
        }
        $this->template->counter_recon = $this->BlogArticlesManager->findAll()->where('reconstruction = 1')->count();
        $this->template->counter_notrecon = $this->BlogArticlesManager->findAll()->where('reconstruction = 0')->count();
        $this->template->counter_total = $totalItems;
        //paginator end


    }

    public function renderEditArticle($id)
    {
        $this->article_id = $id;
        if ($this->article_id > 0) {
            $tmpDef_data = $this->BlogArticlesManager->find($this->article_id);
            $def_data = $tmpDef_data->toArray();

        } else {
            $def_data = array();
            $def_data['create_time'] = new \Nette\Utils\DateTime;
            $def_data['article_date'] = new \Nette\Utils\DateTime;
            $def_data['tags'] = '';
        }

        if (!isset($def_data['cl_users_id'])) {
            $def_data['cl_users_id'] = $this->user->getId();
        }
        $def_data['tags'] = json_decode($def_data['tags'], TRUE);

        $this['editArticle']->setDefaults($def_data);
        $now = new Nette\Utils\DateTime; //$def_data->create_time->format('d.m.Y H:i')

        $this['editArticle']['change_time']->setDefaultValue($now->format('d.m.Y H:i'));
        $this['editArticle']['article_date']->setDefaultValue($def_data['article_date']->format('d.m.Y H:i'));
    }

    protected function createComponentEditArticle($name)
    {
        $form = new Form($this, $name);
        $form->addText('name', 'Název stránky (URL):', 200, 200)
            ->setHtmlAttribute('placeholder', 'Název stránky (URL)')
            ->setHtmlAttribute('class', 'form-control')
            ->addRule(Form::FILLED, 'Je nutné zadat název stránky.');
        $form->addHidden('language');
        $form->addHidden('id');
        $form->addHidden('content_txt');
        $form->addText('change_time', 'Datum poslední změny:')
            ->setHtmlAttribute('placeholder', 'Poslední změna')
            ->setHtmlAttribute('autocomplete', 'off')
            ->setHtmlAttribute('class', 'form-control datetimepicker');
        $form->addText('article_date', 'Datum článku:')
            ->setHtmlAttribute('placeholder', 'Datum článku')
            ->setHtmlAttribute('autocomplete', 'off')
            ->setHtmlAttribute('class', 'form-control datetimepicker');
        $form->addText('title', 'Titulek stránky:', 60, 60)
            ->setHtmlAttribute('placeholder', 'Titulek stránky')
            ->setHtmlAttribute('class', 'form-control');
        $form->addText('description', 'Popis stránky:', 100, 200)
            ->setHtmlAttribute('placeholder', 'Popis stránky')
            ->setHtmlAttribute('class', 'form-control');
        $form->addText('keywords', 'Klíčová slova:', 100, 250)
            ->setHtmlAttribute('placeholder', 'Klíčová slova')
            ->setHtmlAttribute('class', 'form-control');
        /*$form->addText('tags', 'Témata:', 100, 250)
            ->setRequired('Alespoň jedno téma musí být zadáno.')
            ->setHtmlAttribute('placeholder','Témata oddělená středníkem')				
            ->setHtmlAttribute('class', 'form-control');*/


        $form->addMultiSelect('tags', 'Značky:', $this->BlogTagsManager->findAll()->order('name')->fetchPairs('id', 'name'))
            ->setHtmlAttribute('multiple', 'multiple')
            ->setHtmlAttribute('placeholder', 'Vyberte značky');

        $form->addCheckbox('reconstruction', 'Stránka je v rekonstrukci');

        $form->addSelect('cl_users_id', 'Autor:', $this->UsersManager->findAll()->where(['role' => 'admin'])->order('name')->fetchPairs('id', 'name'))
            ->setHtmlAttribute('class', 'form-control')
            ->setPrompt('- Vyberte -');

        $form->addSelect('blog_gallery_id', 'Galerie pro článek:', $this->BlogGalleryManager->findAll()->order('name')->fetchPairs('id', 'name'))
            ->setHtmlAttribute('class', 'form-control')
            ->setPrompt('- Vyberte -');
        $form->addSelect('blog_categories_id', 'Kategorie článku:', $this->BlogCategoriesManager->findAll()->order('order_cat')->fetchPairs('id', 'name'))
            ->setRequired('Kategorie musí být vybrána')
            ->setHtmlAttribute('class', 'form-control')
            ->setPrompt('- Vyberte -');
        $form->addCheckbox('default', 'Hlavní stránka');
        $form->addCheckbox('favorite', 'Zobrazit v oblíbených');
        $form->addTextArea('content', 'Text článku:', 85, 20)->setHtmlAttribute('class', 'tinymce')
            ->setHtmlAttribute('placeholder', 'Text článku')
            ->setHtmlAttribute('class', 'form-control');
        $form->addSelect('blog_images_id', 'Obrázek pro aktuality:', $this->BlogImagesManager->findAll()->order('name_cs')->fetchPairs('id', 'name_cs'))
            ->setHtmlAttribute('class', 'form-control')
            ->setPrompt('- Vyberte -');
        $form->addSubmit('create', 'Uložit')->setHtmlAttribute('class', 'btn btn-primary');
        $form->addSubmit('storno', 'Zpět')->setHtmlAttribute('class', 'btn btn-default');
        $form->onSuccess[] = array($this, 'editFormSubmitted');
        return $form;
    }

    public function editFormSubmitted(Form $form)
    {
        if ($form['create']->isSubmittedBy()) {
            $data = $form->values;
            $data['change_time'] = date('Y-m-d H:i', strtotime($data['change_time']));
            $data['article_date'] = date('Y-m-d H:i', strtotime($data['article_date']));
            $data['tags'] = json_encode($data['tags']);

            if ($form->values['id'] == NULL) {
                $this->BlogArticlesManager->insert($data);
            } else {
                $this->BlogArticlesManager->update($data);
            }

            //$this->updateTags($data);

            $this->flashMessage('Článek uložen', 'success');
        }

        $this->redirect('AdminArticles:');
    }

    private function updateTags($data)
    {
        //save new tags to settings
        $Settings_id = $this->Settings->getSettings()->limit(1)->fetch();
        $dataSettings = array();
        $settingArray = array();
        //array_flip(str_getcsv($Settings_id->tags,';'));

        $articles = $this->BlogArticlesManager->findAll()->where('reconstruction=0');
        foreach ($articles as $oneArticle) {
            if ($oneArticle['tags'] != '') {
                $newArray = array_flip(str_getcsv($oneArticle['tags'], ';'));
            } else {
                $newArray = array();
            }

            $settingArray = \Nette\Utils\Arrays::mergeTree($settingArray, $newArray);
        }
        if (!empty($data['tags'])) {
            $newArray = array_flip(str_getcsv($data['tags'], ';'));
        } else {
            $newArray = array();
        }

        $arrResult = \Nette\Utils\Arrays::mergeTree($settingArray, $newArray);
        $oneCsv = '';
        foreach ($arrResult as $one => $key) {
            if ($one != '') {
                $oneCsv .= $one . ';';
            }
        }

        $dataSettings['tags'] = $oneCsv;
        $this->Settings->saveSettings($Settings_id->id, $dataSettings);

        //save new tags to categories
        if (!is_null($data['categories_id'])) {
            $categories = $this->CategoriesManager->getById($data['categories_id']);
            $dataSettings = array();
            //if ($categories->tags != '')
            //  $categoriesArray = array_flip(str_getcsv($categories->tags,';'));
            //else 
            $categoriesArray = array();
            $articles = $this->BlogArticlesManager->findAll()->where('reconstruction=0 AND categories_id = ?', $data['categories_id']);
            foreach ($articles as $oneArticle) {
                if ($oneArticle['tags'] != '') {
                    $newArray = array_flip(str_getcsv($oneArticle['tags'], ';'));
                } else
                    $newArray = array();

                $categoriesArray = \Nette\Utils\Arrays::mergeTree($categoriesArray, $newArray);
            }

            if (!empty($data['tags']))
                $newArray = array_flip(str_getcsv($data['tags'], ';'));
            else
                $newArray = array();

            $arrResult = \Nette\Utils\Arrays::mergeTree($categoriesArray, $newArray);

            $oneCsv = '';
            foreach ($arrResult as $one => $key) {
                if ($one != '') {
                    $oneCsv .= $one . ';';
                }
            }
            $dataSettings['tags'] = $oneCsv;
            $this->CategoriesManager->save($data['categories_id'], $dataSettings);
        }
    }


    public function handleEraseArticle($id)
    {
        try {
            $this->BlogArticlesManager->delete($id);
            $this->flashMessage('Článek byl vymazán', 'success');
        } catch (Exception $e) {
            if ($e->errorinfo[1] = 1451) {
                $errorMess = 'Není možné vymazat článek, je aktuálně použitý.';
            } else
                $errorMess = $e->getMessage();
            $this->flashMessage($errorMess, 'danger');

        }
        $this->redirect('AdminArticles:');

    }


    protected function createComponentSearchArticle()
    {
        $form = new Nette\Application\UI\Form;
        $form->addText('search', 'Hledat:')
            ->setHtmlAttribute('class', 'form-control')
            ->setHtmlAttribute('placeholder', 'Hledaný text');
        $form->addSubmit('send', 'Hledat')->setHtmlAttribute('class', 'btn btn-primary');
        $form->addSubmit('storno', 'Zrušit')->setHtmlAttribute('class', 'btn btn-primary');
        $form->onSuccess[] = array($this, 'SearchArticleSubmitted');
        return $form;
    }

    public function SearchArticleSubmitted($form)
    {
        if ($form['send']->submittedBy) {
            $values = $form->getValues();
            $this->filter = $values->search;
            $this->redirect('this');
        }
        if ($form['storno']->submittedBy) {
            $this->filter = '';
            $form->setValues(array('search' => ''));
            $this->redirect('this');
        }
    }


}
