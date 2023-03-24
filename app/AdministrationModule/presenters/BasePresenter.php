<?php

namespace App\AdministrationModule\Presenters;

use Nette,
	App\Model;



/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
    public $settings;

    /**
    * @inject
    * @var \App\Model\BlogArticlesManager
    */
    public $BlogArticlesManager;   	
    

    /**
    * @inject
    * @var \App\Model\BlogGalleryManager
    */
    public $BlogGalleryManager;        
    
    /**
    * @inject
    * @var \App\Model\BlogImagesManager
    */
    public $BlogImagesManager;

    /**
    * @inject
    * @var \App\Model\BlogCategoriesManager
    */
    public $BlogCategoriesManager;    
    
		
	
		
	public function beforeRender()
	{
		$mySet = $this->getSession('mySet');
		if ($mySet->myLang == NULL || ($mySet->myLang != "cz" && $mySet->myLang != "eng"))
				$mySet->myLang = "cz";
		$this->template->language = $mySet->myLang; 		    
	}	    


	/** Return name of given articles id
	 * 
	 * @param type $id
	 * @return type
	 */
	public function getArticleName($id)
	{
		return $this->ArticlesManager->getArticleById($id)->name;
	}


	public function handleSignOut()
	{
		$this->getUser()->logout();
		//$this->redirect('Sign:in');
		$this->flashMessage('Odhlášení proběhlo v pořádku.', 'success');
	}

    public function handleEraseCache()
    {
        $link = $this->link(':Administration:AdminMain:');
        $cacheDir = APP_DIR.'/../temp/cache';

        if (is_dir($cacheDir)) {
            $this->rrmdir($cacheDir);
        }
        //header("Location: http://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]);
        header("Location: ".$link);
        die();
    }

    public function handleEraseSessions()
    {
        $link = $this->link(':Administration:AdminMain:');
        $cacheDir = APP_DIR.'/../temp/sessions';

        if (is_dir($cacheDir)) {
            $this->rrmdir($cacheDir);
            mkdir($cacheDir);
        }
        header("Location: ".$link);
        die();
    }


    private function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir."/".$object))
                    {
                        $this->rrmdir($dir."/".$object);
                    }else{
                        unlink($dir."/".$object);
                    }
                }
            }
            rmdir($dir);
        }
    }

}
