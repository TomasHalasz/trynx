<?php

namespace App\AdministrationModule\Presenters;

use App\Model\BlogGalleryManager;
use App\Model\BlogImagesManager;
use App\Model\UserManager;
use Nette,
	App\Model;

use Nette\Application\UI\Form;
use Nette\Image;
use Exception;


/**
 * Administrace presenter.
 *
 * @author     Tomáš Halász
 * @package    
 */
class AdminImagesPresenter extends SecuredPresenter
{
    protected $section,$id,$show_image_edit_modal;
    //public  $arrCategories = array('fotografie' => 'fotografie', 'bannery' => 'bannery');

    /** @persistent */
    public $gallery_id;                
	
        
    /**
    * @inject
    * @var UserManager
    */
    public $userManager;    
    
    
    /**
    * @inject
    * @var BlogImagesManager
    */
    public $BlogImagesManager;
    
    /**
    * @inject
    * @var BlogGalleryManager
    */
    public $BlogGalleryManager;    

	public function actionDefault()
	{
				
	}
    
	public function renderDefault($page = 1, $opener = '')
	{
	    //, $category = 'hra'
	    if (is_null($this->gallery_id))
	    {
			if ($tmpGallery = $this->BlogGalleryManager->findAll()->limit(1)->fetch())
			{
				$this->gallery_id = $tmpGallery->id;
			}else{
				$this->gallery_id = 0;
			}
				
	    }


	    //set off message in session
		$this->template->show_image_edit_modal = $this->show_image_edit_modal;

	    if (!empty($opener)) //to show only gallery without admin menu
		{
			$this->opener = true;
		}
		
	    $this->template->opener = $opener;

	    
	    //paginator start
	    $paginator = new Nette\Utils\Paginator();
	    $ItemsOnPage = 30;
	    $totalItems = $this->BlogImagesManager->findAll()->where('blog_gallery_id = ?',$this->gallery_id)->count();//where('type > 0')->
	    //dump($totalItems->pocet);
	    //$totalItems = 20;
	    //dump($totalItems);
	    //die;
	    $paginator->setItemCount($totalItems);
	    $paginator->setItemsPerPage($ItemsOnPage);
	    $paginator->setPage($page);
	    $pages = ceil($totalItems/$ItemsOnPage);
	    $this->template->paginator = $paginator; //this is important for achieve to paginator object from template
	    $steps=array();
	    for ($i = 1; $i <= $pages; $i++) {
			$steps[]=$i;
	    }
	    $this->template->steps = $steps;
	    //paginator end

	    //get only records which belongs to selected page  //->where('type > 0')
	    $this->template->images = $this->BlogImagesManager->findAll()->where('blog_gallery_id = ?',$this->gallery_id)->
			limit($paginator->getLength(), $paginator->getOffset());	    
	    $this->template->gallery_id = $this->gallery_id;

	    $this->template->gallery = $this->BlogGalleryManager->findAll()->order('name');
	    if ($this->id != NULL){
		    $activeRow = $this->BlogImagesManager->find($this->id);
		    $values = $activeRow->toArray();
		    $this['imageEditForm']->setDefaults($values);
		    //date('d-m-Y',strtotime($values['date_from']))
		    //$form->setDefaults(array ('date_from' => '01.01.2013'));
	    } 

	}
	
	public function handleNewImage()
	{
	    $this->section = $this->session->getSection('t2q');		
	    $this->section->show_image_edit_modal = true;
	}

	/*public function handleSelectGallery($gallery_id)
	{
	    $this->section = $this->session->getSection('t2q');		
	    $this->section->gallery_id = $gallery_id;
	}*/
	
	public function handleEditImage($id)
	{
	    $this->id = $id;
	    //$this->section = $this->session->getSection('t2q');		
	    $this->show_image_edit_modal = true;
	    $this->redrawControl('editImage');
	}
	
	protected function createComponentImageEditForm()
	{
		$form = new Form();
		$form->addHidden('id', NULL);
		$form->addText('name_cs', 'Název obrázku (cz):')
			->setAttribute('title','Název obrázku (cz)')
			->setAttribute('placeholder','Název obrázku')				
			->setAttribute('class', 'form-control');
		$form->addText('name_en', 'Název obrázku (en):')
			->setAttribute('title','Název obrázku (en)')
			->setAttribute('class', 'form-control');
		$form->addText('name_de', 'Název obrázku (de):')
			->setAttribute('title','Název obrázku (de)')
			->setAttribute('class', 'form-control');
		$form->addText('name_ru', 'Název obrázku (ru):')
			->setAttribute('title','Název obrázku (ru)')
			->setAttribute('class', 'form-control');
		$form->addText('file_name', 'Název souboru:')
			->setDisabled()
			->setAttribute('title','Název obrázku (cz)')
			->setAttribute('class', 'form-control');

		$form->addText('description_cs', 'Popis obrázku (cz):')
			->setAttribute('title','Popis obrázku (cz)')
			->setAttribute('placeholder','Popis obrázku')							
			->setAttribute('class', 'form-control');
		$form->addText('description_en', 'Popis obrázku (en):')
			->setAttribute('title','Popis obrázku (en)')
			->setAttribute('class', 'form-control');
		$form->addText('description_de', 'Popis obrázku (de):')
			->setAttribute('title','Popis obrázku (de)')
			->setAttribute('class', 'form-control');
		$form->addText('description_ru', 'Popis obrázku (ru):')
			->setAttribute('title','Popis obrázku (ru)')
			->setAttribute('class', 'form-control');
		
		//$form->addText('categories', 'Kategorie:')
		//	->setAttribute('title','Kategorie')
		//	->setAttribute('class', 'enWatermark');
		
		//$form->addSelect('categories', 'Kategorie:', $this->arrCategories)
//			->setAttribute('title','Kategorie')
			//->setAttribute('class', 'form-control');

		$form->addSelect('blog_gallery_id', 'Galerie:', $this->BlogGalleryManager->findAll()->order('name')->fetchPairs('id','name'))
			->setAttribute('title','Galerie')
			->setAttribute('class', 'form-control');		
		
				
		$form->addSubmit('send', 'Uložit')->setAttribute('class','btn btn-primary');
		$form->addSubmit('storno', 'Zpět')->setAttribute('class','btn btn-default');
		
		// call method signInFormSucceeded() on success
		$form->onSuccess[] = array($this,'imageEditFormSucceeded');
		//$form->onError[] = $this->prizeEditFormError;
		

		return $form;
	}


	public function imageEditFormSucceeded($form)
	{
		if ($form['send']->submittedBy)
		{	    
			$values = $form->getValues();
			//dump($values);
			//die;
			if ($values->id != NULL)
			{
			    $this->BlogImagesManager->update($values);
			}

		    //$this->section = $this->session->getSection('t2q');				
		    //$this->section->show_image_edit_modal = false;		
		}
		//$this->redirect('this');
		$this->redrawControl('gallery');
	    $this->show_image_edit_modal = false;
	    $this->redrawControl('editImage');		

	}
	
	public function imageEditFormError($form)
	{
		$this->section = $this->session->getSection('t2q');		
		$this->section->show_image_edit_modal = true;		
	}
	


	public function handleDeleteImage($id)
	{
	    try{
		    //first we must delete file physicaly 

		    if ($one = $this->BlogImagesManager->find($id))
		    {
			    $fileDel = APP_DIR.'/../www/images/'.$one->file_name;
			    //$fileSmall = pathinfo($fileDel,PATHINFO_FILENAME).'_small.'.pathinfo($fileDel, PATHINFO_EXTENSION);
			    $fileDel2 = APP_DIR.'/../www/images/S1-'.$one->file_name;
			    if (file_exists($fileDel2)) //delete if exist
				{
					unlink ($fileDel2);
					$fileDel2 = APP_DIR.'/../www/images/S2-'.$one->file_name;
				}
			    if (file_exists($fileDel2)) //delete if exist
				{
					unlink ($fileDel2);		
					$fileDel2 = APP_DIR.'/../www/images/S3-'.$one->file_name;
				}
			    if (file_exists($fileDel2)) //delete if exist
				{
					unlink ($fileDel2);
					$fileDel2 = APP_DIR.'/../www/images/S4-'.$one->file_name;
				}
			    if (file_exists($fileDel2)) //delete if exist
				{
					unlink ($fileDel2);			    
					$fileDel2 = APP_DIR.'/../www/images/S5-'.$one->file_name;
				}
			    if (file_exists($fileDel2)) //delete if exist
				{
					unlink ($fileDel2);			    			    
				}
			    if (file_exists($fileDel)) //delete if exist			    
				{
					unlink ($fileDel);
				}
			
		    }
			    
		    $this->BlogImagesManager->delete($id);
		    $this->redrawControl('gallery');
			$this->flashMessage('Obrázek byl vymazán', 'success');
			$this->redrawControl('flashMessages');
		}
		catch (Exception $e) {
		    if ($e->errorinfo[1] = 2300)
		    {
				$errorMess = 'Není možné vymazat obrázek, je použitý.'; 
		    } else {
				$errorMess = $e->getMessage(); 
			}
		    $this->flashMessage($errorMess, 'danger');		    
			$this->redrawControl('flashMessages');

	    }
	    //$this->redirect('this');
	    
	}



	public function handleGetImagePicture($id)
	{
	    $row = $this->BlogImagesManager->find($id);
	    if ($row->file_name != NULL)
	    {	    
			$image = Nette\Utils\Image::fromFile(APP_DIR.'/../data/'.$row->file_name);
			//$image = $image->resize(200, NULL);		

			if ($row->file_ext == 'png')
				$image->send(Nette\Utils\Image::PNG);
			else
				$image->send(Nette\Utils\Image::JPEG);

			$image = NULL;
	    }else{
			$this->terminate();
		}
	}
	
	public function GetImageName($id)
	{
	    if ($img = $this->BlogImagesManager->find($id))
		{
		    echo($img->file_name);
		}

	}		

/*	public function actionGetImagePicture($id)
	{
	    $row = $this->imagesManager->getImage($id)->fetch();
	    if ($row->image != NULL)
	    {	    
		$image = \Nette\Utils\Image::fromString($row->image);
		if ($row->file_ext == 'png')
		    $image->send(\Nette\Utils\Image::PNG);
		else
		    $image->send(\Nette\Utils\Image::JPEG);		

		$image = NULL;
	    }else
		$this->terminate();
	}	*/
	


	/**dropzone form
     * @return Form
     */
    protected function createComponentUploadFile()
    {
		$form = new Form();
		$form->getElementPrototype()->class = 'dropzone filedropzone';
		$form->getElementPrototype()->id = 'fileDropzone';
		$form->addHidden('blog_gallery_id', $this->gallery_id);
		$form->onSuccess[] = [$this, 'processUploadFile'];
		return $form;
    }        
    
    /**upload method for process logo
     * @param Form $form
     */
    public function processUploadFile(Form $form)
    {
		$formValues = $form->getValues();
		$file = $form->getHttpData($form::DATA_FILE, 'file');

			if ($file->isOk())
			{

				$fileName = $file->getSanitizedName();
				//$fileSrc = APP_DIR.'/../www/files/'.$fileName;
				$values = array();
				$values['type'] = 1;
				$values['name_cs'] = $fileName;
				$values['file_name'] = $fileName;
				$values['file_ext'] = pathinfo($fileName, PATHINFO_EXTENSION);
				$values['mime_type'] = $file->getContentType();
				$values['file_size'] = $file->getSize();	    
				
				$values['blog_gallery_id'] = $formValues['blog_gallery_id'];
				if  (strtolower($values['file_ext']) == 'jpg' || strtolower($values['file_ext']) == 'jpeg' ) 
				{
					$values['file_ext'] = 'jpg';
				}

				if  (strtolower($values['file_ext']) == 'png') 		
				{
					$values['file_ext'] = 'png';
				}

				$values['date'] = new Nette\Utils\DateTime();
				//file_put_contents('log.txt', dump($values), FILE_APPEND);
				if ($ret = $this->BlogImagesManager->insert($values))
				{
					//$fileNew = APP_DIR.'/../../www/images/'.$fileName;
					//$fileNew = $this->context->parameters['wwwDir'].'/images/'.$fileName;
					$fileNew = APP_DIR.'/../www/images/'.$fileName;
					//dump($fileNew)
					//copy image file - original size
					//$res = copy($fileSrc,$fileNew);
					$file->move($fileNew);

					//S1 Banner 1024x350 
					$image = Nette\Utils\Image::fromFile($fileNew);
					$image = $image->resize(1024,NULL);		
					$image->save(APP_DIR.'/../www/images/S1-'.$fileName);
					//S2 Titulek článku 1024x288
					$image = Nette\Utils\Image::fromFile($fileNew);
					$image = $image->resize(1024,NULL);		
					$image->save(APP_DIR.'/../www/images/S2-'.$fileName);		
					//S3 aktuality 900x400
					$image = Nette\Utils\Image::fromFile($fileNew);
					$image = $image->resize(900,NULL);		
					$image->save(APP_DIR.'/../www/images/S3-'.$fileName);				
					//S4 náhled galerie 400x400
					$image = Nette\Utils\Image::fromFile($fileNew);
					$image = $image->resize(400,400, Nette\Utils\Image::EXACT);
					$image->save(APP_DIR.'/../www/images/S4-'.$fileName);				
					//S5 Logo sponzoru 192x65
					$image = Nette\Utils\Image::fromFile($fileNew);
					$image = $image->resize(NULL,65);		
					$image->save(APP_DIR.'/../www/images/S5-'.$fileName);				

					//unlink (APP_DIR.'/../www/files/'.$fileName);
					//unlink (APP_DIR.'/../www/files/thumbnail/'.$fileName);
				}
				$this->redrawControl('gallery');				
				
			}else
			{
				$this->presenter->flashMessage('Soubor nebyl přidán.', 'warning');
			}	    

		$this->presenter->redrawControl('flash');	    		    		
    }    
	
	
	public function handleMoveFile($fileName)
	{
	    $this->section = $this->session->getSection('t2q');		
	    
	    //$image = Image::fromFile(APP_DIR.'/../www/files/'.$fileName);
	    $fileSrc = APP_DIR.'/../www/files/'.$fileName;
	    $values = array();
	    $values['type'] = 1;
	    $values['name_cs'] = $fileName;
	    $values['file_name'] = $fileName;
	    $values['file_ext'] = pathinfo($fileName, PATHINFO_EXTENSION);
	    $values['gallery_id'] = $this->gallery_id;
	    if  (strtolower($values['file_ext']) == 'jpg' || strtolower($values['file_ext']) == 'jpeg' ) 
	    {
			$values['file_ext'] = 'jpg';
	    }
	    
	    if  (strtolower($values['file_ext']) == 'png') 		
	    {
			$values['file_ext'] = 'png';
		}

		$values['date'] = new Nette\Utils\DateTime();
		//file_put_contents('log.txt', dump($values), FILE_APPEND);
		if ($ret = $this->imagesManager->addImage($values))
		{
			$fileNew = APP_DIR.'/../www/images/'.$fileName;

			//copy image file - original size
			$res = copy($fileSrc,$fileNew);

			//S1 Banner 1024x350 
			$image = Nette\Utils\Image::fromFile($fileSrc);
			$image = $image->resize(1024,NULL);		
			$image->save(APP_DIR.'/../www/images/S1-'.$fileName);
			//S2 Titulek článku 1024x288
			$image = Nette\Utils\Image::fromFile($fileSrc);
			$image = $image->resize(1024,NULL);		
			$image->save(APP_DIR.'/../www/images/S2-'.$fileName);		
			//S3 aktuality 900x400
			$image = Nette\Utils\Image::fromFile($fileSrc);
			$image = $image->resize(900,NULL);		
			$image->save(APP_DIR.'/../www/images/S3-'.$fileName);				
			//S4 náhled galerie 400x400
			$image = Nette\Utils\Image::fromFile($fileSrc);
			$image = $image->resize(400,400, Nette\Utils\Image::EXACT);
			$image->save(APP_DIR.'/../www/images/S4-'.$fileName);				
			//S5 Logo sponzoru 192x65
			$image = Nette\Utils\Image::fromFile($fileSrc);
			$image = $image->resize(NULL,65);		
			$image->save(APP_DIR.'/../www/images/S5-'.$fileName);				

			unlink (APP_DIR.'/../www/files/'.$fileName);
			unlink (APP_DIR.'/../www/files/thumbnail/'.$fileName);
	    }
	    $this->redrawControl('gallery');
	    //$this->terminate();
	}

	
}