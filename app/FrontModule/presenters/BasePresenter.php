<?php

namespace App\FrontModule\Presenters;

use Nette\Application;
use Nette\Utils\Image;

//extends \App\Presenters\BasePresenter
class BasePresenter extends \Nittro\Bridges\NittroUI\Presenter
{
        //put your code here
	
    /**
    * @inject
    * @var \App\Model\ArraysManager
    */
    public $ArraysManager;   			


    public function shutdown(Application\Response $response)
    {
      //TH12.02.2023  $this->session->start();
      //  $this->session->destroy();
    }

    public function getDate($date)
	{
		$den = $this->ArraysManager->cesky_den($date->format('w'));
		$mesic = $this->ArraysManager->cesky_mesic($date->format('n'));
		$rok = $date->format('Y');
		$now = new \Nette\Utils\DateTime;
		if ($date <= $now->modify('-7 day'))
		{
			$retStr = $date->format('j') . '. ' . $mesic . ' ' . $rok;
		}else{
			$retStr = $den . ', ' . $date->format('j') . '. ' . $mesic . ' ' . $rok;
		}
		return $retStr;
	}
	

	
	
	
	public function getMonth($date)
	{
		$retStr = $this->ArraysManager->cesky_mesic($date->format('n'));	
		
		return $retStr;
	}	
	
	
	/** returns user picture 
     * @param type $type
     */
    public function actionGetUserImage($id)
	{
	    /*$row = $this->UserManager->getUserById($id);

		$img = $row['user_image'];
	    
	    if ($img != '')
	    {
			$file=__DIR__."/../../../data/pictures/".$img;		    
			if (is_file($file))
			{
				$image = Image::fromFile($file);
				$image->send(Image::JPEG);
			}

	    }*/

	    $this->terminate();		

	}    	
	
	
}