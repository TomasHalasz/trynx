<?php

namespace App\Controls;
use App\Model\UserManager;
use Contributte\Translation\PrefixedTranslator;
use Nette\Application\UI\Form;
use Nette\Application\UI\Control;
use Nette,
	App\Model;
use Nette\Utils\Image;
use Netpromotion\Profiler\Profiler;
use Tracy\Debugger;
use Exception;

class FilesControl extends Control
{


    private $parent_id;
    private $edit_id;
    private $messageType = "";
    public $cl_company_id;

    private $type, $user_id;    

    /** @var \App\Model\Base*/
    private $FilesManager;
	
	/** @var \App\Model\Base*/
    private $ParentManager;

	/** @var \App\Model\UserManager*/
    private $UserManager;

    /** @var \App\Model\UsersManager*/
    private $UsersManager;

    /** @var \App\Model\FilesAgreementsManager */
    private $FilesAgreementsManager;


    /** @var \App\Model\CompaniesManager*/
    private $CompaniesManager;

    /**
     * @var \App\Model\ArraysManager*/
    private $ArraysManager;

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;
    /** @var App\Model\StatusManager */
    public $StatusManager;



    public function __construct( Nette\Localization\Translator $translator, $FilesManager,$UserManager,$parent_id,$type,$ParentManager = NULL, $cl_company_id = NULL, $user_id = NULL, Model\CompaniesManager $companiesManager,
                                    Model\ArraysManager $arraysManager, Model\FilesAgreementsManager $filesAgreementsManagers = NULL, $usersManager = NULL)
    {
        //arent::__construct(); // vždy je potřeba volat rodičovský konstruktor
        $this->translator       = $translator;
        $this->FilesManager	    = $FilesManager;
	    $this->parent_id	    = $parent_id;
	    $this->type		        = $type;
	    $this->ParentManager	= $ParentManager;
	    $this->UserManager	    = $UserManager;
        $this->UsersManager	    = $usersManager;
	    $this->cl_company_id	= $cl_company_id;
	    $this->user_id		    = $user_id;
	    $this->CompaniesManager = $companiesManager;
	    $this->ArraysManager    = $arraysManager;
        $this->FilesAgreementsManager    = $filesAgreementsManagers;

    }        
    

    
    public function render()
    {
        $edit_id =$this->edit_id;
        if ($this->presenter->user->isLoggedIn() && $this->cl_company_id !== NULL){
            $this->template->isPrivateTable = $this->parent->UserManager->isPrivate($this->presenter->DataManager->tableName,$this->presenter->settings->id,$this->presenter->getUser()->id);
            $this->template->userId = $this->presenter->getUser()->id;
        }else{
            $this->template->isPrivateTable = TRUE;
            $this->template->userId = NULL;
        }

        $this->template->messageType = $this->messageType;
        bdump($this->messageType);
            //!!! PAK ZRUSIT !!!
        //\Tracy\Debugger::$productionMode = FALSE;
        $deny = TRUE;
        //if we are not logged in, we must check if is enabled to upload data
        if (!$this->presenter->user->isLoggedIn() && $this->cl_company_id !== NULL)
        {
            if ($tmpData = $this->ParentManager->findAllTotal()->where('id = ?', $this->parent_id)->fetch())
            {
                if (array_key_exists('public_event', $tmpData)){
                    //if there is a public_event property we must check it and according to this property set $deny
                    if ($tmpData->public_event){
                    $deny = FALSE;
                    }
                }else{
                    //other cases
                    $deny = FALSE;
                }
            }
        }else{
            $deny = FALSE;
        }


        if ($deny)
        {
            $this->template->setFile(__DIR__ . '/disabled.latte');
        }else{
            //die;
            $this->template->setFile(__DIR__ . '/Files.latte');
            $this->template->type = $this->type;
            // required to enable form access in snippets
            $this->template->_form = $this['uploadFile'];
            $this['uploadFile']->setValues(['type' => $this->type, 'parent_id' => $this->parent_id, 'cl_company_id' => $this->cl_company_id]);

            //09.04.2017 - uploading file from public form, we must also work with file_session
            if ($this->presenter->name != "Application:HelpdeskPublic" && $this->presenter->name != "Application:HelpdeskSimple")
            {
                //24.02.2019 - change from findAll() to findAllTotal() - is private mode we have to see every child records
                //bdump($this->type);
                //bdump($this->parent_id, 'render');
                //bdump($this->type, 'type render');
                $this->template->data = $this->FilesManager->findAllTotal()->where($this->type.' = ?',$this->parent_id);
            }else{
                $section = $this->presenter->getSession('helpdeskPublic');
                if (!is_null($section->fileSession)) {
                    $this->template->data = $this->FilesManager->findAllTotal()
                        ->where($this->type . ' = ? AND cl_company_id = ? AND file_session = ?', $this->parent_id, $this->cl_company_id, $section->fileSession);
                }else{
                    $this->template->data = $this->FilesManager->findAllTotal()
                        ->where($this->type . ' = ? AND cl_company_id = ?', $this->parent_id, $this->cl_company_id);
                }
            }

        }
        if ($this->presenter->user->isLoggedIn())
        {
            $this->template->userCanErase =  $this->presenter->isAllowed($this->presenter->name,'erase');
            $this->template->userCanEdit =  $this->presenter->isAllowed($this->presenter->name,'edit');
            //dump($this->template->userCanEdit);
        }else{
            $this->template->userCanErase =  TRUE;
            $this->template->userCanEdit =  TRUE;
        }

        $this->template->edit_id = $edit_id;
        if ($this->template->userCanEdit && !is_null($edit_id)){
            $tmpEdit = $this->FilesManager->findAll()->where('id = ?',$edit_id)->fetch();
            $this->template->dataFile = $tmpEdit;
            $this['descrFile']->setDefaults($tmpEdit);
        }else{
            $this->template->dataFile = FALSE;
        }
        if (!is_null($this->FilesAgreementsManager)){
            $showAgreements = TRUE;
        }else{
            $showAgreements = FALSE;
        }
        $this->template->showAgreements = $showAgreements;
        $this->template->render();
        ////profiler::finish('filesControl');
    }


    /**Stamp company form
     * @return Form
     */
    protected function createComponentUploadFile()
    {
		$form = new \Nette\Application\UI\Form();
		$form->getElementPrototype()->class = 'dropzone filedropzone';
		if ($this->type == 'cl_pricelist_image_id'){
		    $form->getElementPrototype()->id = 'imageDropzone';
		}else{
		    $form->getElementPrototype()->id = 'fileDropzone';
		}
		$form->addHidden('parent_id');
		$form->addHidden('cl_company_id');
		$form->addHidden('type');

		$form->onSuccess[] = [$this, 'processUploadFile'];
		return $form;
    }        
    
    /**upload method for process logo
     * @param Form $form
     */
    public function processUploadFile(Form $form)
    {
		$formValues = $form->getValues();
		bdump($formValues);
		$file = $form->getHttpData($form::DATA_FILE, 'file');

		$tmpLicense = $this->FilesManager->findAllTotal()->where('cl_company_id = ?', $formValues['cl_company_id'])
							    ->sum('file_size') / 1024 / 1000;
		

		//dump($tmpUserId);
		//dump($this->user_id);
		if ($tmpLicense < $this->UserManager->trfDiskSpace($this->user_id))
		{
			$result = TRUE;

			if ($file->isOk())
			{


				//Debugger::fireLog($file->getContentType());	
				//next check if file exists, if yes generate new filename
				$destFile=NULL;
                $fileName = $file->getSanitizedName();
                $i = 0;
                $arrFile = str_getcsv($fileName, '.');
				while(file_exists($destFile) || is_null($destFile))
				{
				    if (!is_null($destFile)) {

                        //$fileName = $arrFile[0] . '-' . \Nette\Utils\Random::generate(1, 'A-Za-z0-9') . '.' . $arrFile[1];
                        $fileName = $arrFile[0] . '-' . $i . '.' . $arrFile[1];

                    }

			      //$destFile=__DIR__."/../../../../data/files/".$fileName;
                    $dataFolder = $this->CompaniesManager->getDataFolder($this->cl_company_id);
                    $subFolder  = $this->ArraysManager->getSubFolder([], $formValues['type']);
                    $destFile   =  $dataFolder . '/' . $subFolder . '/' . $fileName;
                    $i++;
				}
                bdump($destFile);
				$file->move($destFile);


				$data  = new \Nette\Utils\ArrayHash;
				$data['file_name'] = $fileName;
				$data['label_name'] = $fileName;
				$data['mime_type'] = $file->getContentType();
				$data['file_size'] = $file->getSize();	    
				$data['create_by'] = 'webform';

				$data['created'] = new \Nette\Utils\DateTime;
				$data['cl_users_id'] =  $this->presenter->getUser()->id;
				//if ($this->event_id != NULL)
				$data[$formValues['type']] = $formValues['parent_id'];
				
				//09.04.2017 - uploading file from public form, we must save also file_session
				if ($this->presenter->name == "Application:HelpdeskPublic" || $this->presenter->name == "Application:HelpdeskSimple")
				{
				    $section = $this->presenter->getSession('helpdeskPublic');					
				    $data['file_session'] = $section->fileSession;
				}
				
				bdump($data);
				//unlink($file);
				//Debugger::fireLog($data);    	
				//die;
				if ($this->presenter->user->isLoggedIn())
				{
					//unset($data['cl_company_id']);
					$this->FilesManager->insert($data);
				}
				else
				{
					$data['cl_company_id'] = $formValues['cl_company_id'];
					$this->FilesManager->insertForeign($data);
				}

				$this->flashMessage($this->translator->translate('Soubor_byl_přidán.'), 'success');
				//$this->presenter->redrawControl('flash');
				if ($this->type == "cl_pricelist_image_id"){
				    $this->redrawControl('imagesPriceList');				
				}else{
				    $this->redrawControl('filestable');
				}
			}else
			{

				$this->flashMessage($this->translator->translate('Soubor_nebyl_přidán.') , 'danger');
                $this->flashMessage($this->getErrorMesage($file->getError()) , 'danger');
                $this->redrawControl('filestable');
			}	    
		
		}else{
			$result = FALSE;
			//$this->flashMessage('Tarif "' . $this->UserManager->trfName($this->getUser()->id) . '" nepovoluje více položek v ceníku', 'danger' );
			//throw new Exception('Tarif "' . $this->UserManager->trfName($this->presenter->getUser()->id) . '" nedovoluje obsadit další prostor pro soubory.');
			$this->flashMessage('Máte zaplněno ' . $tmpLicense . ' MB z ' . $this->UserManager->trfDiskSpace($this->user_id) . $this->translator->translate(' MB, další soubor není možné přidat.'), 'danger');
            $this->redrawControl('filestable');
			//return FALSE;
		}
		$this->redrawControl('uploadStatus');
//        $this->presenter->redrawControl('content');
			
//	$this->CompaniesManager->update($value);
        //} catch (\Exception $e) {
        //     $this->flashMessage($e->getMessage());
        //}
    }    
    
    public function handleFileDelete($id)
    {
		if ($this->presenter->user->isLoggedIn())
		{				
			if ($file = $this->FilesManager->find($id))
			{
                if ($file->new_place == 0) {
                    $fileDel = __DIR__ . "/../../../data/files/" . $file->file_name;
                }else{
                    $dataFolder = $this->CompaniesManager->getDataFolder($this->cl_company_id);
                    $subFolder = $this->ArraysManager->getSubFolder($file);
                    $fileDel =  $dataFolder . '/' . $subFolder . '/' . $file->file_name;
                }

				if (file_exists($fileDel)) 
					unlink ($fileDel);

				$file->delete();
                $this->flashMessage($this->translator->translate('Soubor_byl_vymazán.'), 'success');
                $this->messageType = "erase";
                $this->redrawControl('uploadStatus');
			}
		}
		else
		{
			if ($file = $this->FilesManager->findAllTotal()->where('cl_company_id = ? AND id = ?',$this->cl_company_id,$id)->fetch())
			{
                if ($file->new_place == 0) {
                    $fileDel = __DIR__ . "/../../../data/files/" . $file->file_name;
                }else{
                    $dataFolder = $this->CompaniesManager->getDataFolder($this->cl_company_id);
                    $subFolder = $this->ArraysManager->getSubFolder($file);
                    $fileDel =  $dataFolder . '/' . $subFolder . '/' . $file->file_name;
                }
				if (file_exists($fileDel)) 
					unlink ($fileDel);

				$file->delete();
				$this->flashMessage($this->translator->translate('Soubor_byl_vymazán.'), 'success');
                $this->messageType = "erase";
                $this->redrawControl('uploadStatus');
			}			
		}
	if ($this->type == "cl_pricelist_image_id"){
	    $this->redrawControl('imagesPriceList');
	}else{
	    $this->redrawControl('filestable');
	}

    }    
 

    public function handleGetFile($id)
    {
        if ($file = $this->FilesManager->findAllTotal()->where('cl_company_id =? AND id = ?', $this->cl_company_id, $id)->fetch())
        {
            if ($file->new_place == 0) {
                $fileSend = __DIR__ . "/../../../data/files/" . $file->file_name;
            }else{
                $dataFolder = $this->CompaniesManager->getDataFolder($this->cl_company_id);
                $subFolder = $this->ArraysManager->getSubFolder($file);
                $fileSend =  $dataFolder . '/' . $subFolder . '/' . $file->file_name;
            }
            if (file_exists($fileSend)) {
                $this->presenter->sendResponse(new Nette\Application\Responses\FileResponse($fileSend, $file->label_name, $file->mime_type));
            }

        }
    }
    
    
	
    /** returns picture 
     * @param type $type
     */
    public function handleGetImage($id)
    {
	    //$row = $this->UserManager->getUserById($id);
        //bdump($id);
        //die;
	    if ($tmpFile = $this->FilesManager->find($id))
	    {
	        if ($tmpFile['new_place'] == 0) {
                $file = __DIR__ . "/../../../data/files/" . $tmpFile->file_name;
            }else{
                $dataFolder = $this->CompaniesManager->getDataFolder($this->cl_company_id);
                $subFolder = $this->ArraysManager->getSubFolder($tmpFile);
                $file =  $dataFolder . '/' . $subFolder . '/' . $tmpFile['file_name'];
            }

			/*if (file_exists($file) && ($tmpFile['mime_type'] != "image/tiff"))
			{
				$image = Image::fromFile($file);
				$image->send(Image::JPEG);
			}else{
			    $this->presenter->sendResponse(new \Nette\Application\Responses\FileResponse($file, $tmpFile['label_name'], $tmpFile['mime_type']));
            }*/
            bdump($file);
            if (file_exists($file))
            {
                $httpResponse = $this->presenter->getHttpResponse();
                $httpResponse->setHeader('Pragma', "public");
                $httpResponse->setHeader('Expires', 0);
                $httpResponse->setHeader('Cache-Control', "must-revalidate, post-check=0, pre-check=0");
                $httpResponse->setHeader('Content-Transfer-Encoding', "binary");
                $httpResponse->setHeader('Content-Description', "File Transfer");
                $httpResponse->setHeader('Content-Type', filetype($file));
                $httpResponse->setHeader('Content-Length', filesize($file));
                $httpResponse->setHeader('Content-Disposition', "inline; filename=\"{$file}\"\n");  //to force browser to download file not display content
                $this->presenter->sendResponse(new \Nette\Application\Responses\FileResponse($file, $tmpFile['label_name'], $tmpFile['mime_type']));

            }

	    }
       // die();
    }


    public function handleGetPDF($id)
    {
        $type = 1; //1 for PDF
        if ($tmpFile = $this->FilesManager->find($id))
        {
            $dataFolder = $this->CompaniesManager->getDataFolder($this->presenter->getUser()->getIdentity()->cl_company_id);
            $subFolder = $this->ArraysManager->getSubFolder($tmpFile);
            $fileName = $dataFolder . '/' . $subFolder . '/' . $tmpFile->file_name;
            if (is_file($fileName))
                $this->presenter->pdfPreviewData = file_get_contents($fileName);
            else
                $this->presenter->pdfPreviewData = NULL;
        }
        $this->presenter->redrawControl('pdfPreview');
        $this->presenter->showModal('pdfModal');
    }


    protected function createComponentDescrFile($name)
    {
        $form = new Form($this, $name);
        $form->addHidden('id',NULL);
        $form->addText('file_name', $this->translator->translate('Název souboru'), 20, 20)
            ->setHtmlAttribute('placeholder', $this->translator->translate('Název souboru'))
            ->setDisabled(TRUE);
        if (!is_null($this->FilesAgreementsManager)) {
            $form->addCheckbox('users_agreement', $this->translator->translate('Sbírat souhlasy uživatelů'));
            $form->addCheckbox('after_login', $this->translator->translate('Vyžadovat hned po přihlášení'));
        }

        $form->addTextArea('description', $this->translator->translate('Poznámka'), 30, 8)
            ->setHtmlAttribute('placeholder',$this->translator->translate('Poznámka'));

        $form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class','btn btn-success');
        $form->addSubmit('back', $this->translator->translate('Zpět'))
            ->setHtmlAttribute('class','btn btn-warning')
            ->setValidationScope([])
            ->onClick[] = array($this, 'stepBack');
        $form->onSuccess[] = array($this,'SubmitDescrSubmitted');
        return $form;
    }

    public function stepBack()
    {
        //$this->redirect('default');
        $this->redrawControl('hideFileDesc');
    }

    public function SubmitDescrSubmitted(Form $form)
    {
        $data=$form->values;
        if ($form['send']->isSubmittedBy())
        {
            if (!empty($data['id'])) {
                $this->FilesManager->update($data);
                if (isset($data['users_agreement']) && $data['users_agreement'] == 1) {
                    $this->FilesAgreementsManager->setToUsers($data->id, $this->UsersManager->findAll());
                }
                $this->flashMessage($this->translator->translate('Změny byly uloženy.'), 'success');
            }
            //$this->redirect('default', array('id' => $this->id));
            if ($this->type == "cl_pricelist_image_id"){
                $this->redrawControl('imagesPriceList');
            }else{
                $this->redrawControl('filestable');
            }
            $this->redrawControl('hideFileDesc');
        }

    }

    public function handleEditDescr($edit_id){
        $this->edit_id = $edit_id;
        $this->redrawControl('modalFileDesc');
        $this->redrawControl('showFileDesc');
    }

    private function getErrorMesage($i = NULL){
        if (!is_null($i)) {
            $phpFileUploadErrors = array(
                0 => 'There is no error, the file uploaded with success',
                1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
                3 => 'The uploaded file was only partially uploaded',
                4 => 'No file was uploaded',
                6 => 'Missing a temporary folder',
                7 => 'Failed to write file to disk.',
                8 => 'A PHP extension stopped the file upload.',
            );
            $retStr = $phpFileUploadErrors[$i];
        }else{
            $retStr = "Neznámá chyba";
        }
        return $retStr;
    }


}