<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class HelpdeskReviewPresenter extends \App\Presenters\BaseAppPresenter {


   private $event_id, $public_token;
 
    /**
    * @inject
    * @var \App\Model\PartnersManager
    */
    public $PartnersManager;    
    
    /**
    * @inject
    * @var \App\Model\PartnersEventManager
    */
    public $PartnersEventManager;        

    /**
    * @inject
    * @var \App\Model\FilesManager
    */
    public $FilesManager;        
    
    
    protected function startup()
    {
        parent::startup();
	    
    }
    
    
    
    public function actionDefault($event_id,$public_token)
    {
	$this->event_id = $event_id;
	$this->public_token = $public_token;
    }
    
    public function renderDefault()
    {
		//dump($this->event_id);
			
		if (!$this->getUser()->isLoggedIn()) {	

			//if user is not loggedin, we must find event by public_token and id
			$event = $this->PartnersEventManager->findAllTotal()->where(array('cl_partners_event.id' => $this->event_id, 'public_token' => $this->public_token));
			$this->template->logged = FALSE;
		}else{
			//otherwise is enough only by id
			$event = $this->PartnersEventManager->findAll()->where(array('cl_partners_event.id' => $this->event_id));	    
			$this->template->logged = TRUE;
		}
		if ($eventData = $event->fetch())
			$this->template->event = $eventData;
		else
			$this->template->event = false;

		$this->template->eventsModalShow = FALSE;
    }
    
    
   
    public function handleGetFile($id)
    {
	if ($file = $this->FilesManager->find($id))
	{
	    $fileSend = __DIR__."/../../../data/files/".$file->file_name;
	    $this->presenter->sendResponse(new \Nette\Application\Responses\FileResponse($fileSend, $file->label_name, $file->mime_type));
	    //, 'contenttype'
	    //$this->
	    
	}
    }


}
