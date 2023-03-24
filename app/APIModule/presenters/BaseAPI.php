<?php
namespace App\APIModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Nette\Utils\DateTime;
use Tracy\Debugger;

class BaseAPI  extends \App\Presenters\BasePresenter {
    
    public $dataxml = '' , $data = array(), $cl_company_id = NULL, $settings = NULL, $id = NULL, $sync_last = NULL, $uri = NULL;

    public $search, $search_type, $username, $password;

    /**
    * @inject
    * @var \App\Model\CompaniesManager
    */
    public $CompaniesManager;      
    
    /**
    * @inject
    * @var \App\Model\StatusManager
    */
    public $StatusManager;   
    
    
	
	public $DataManager;

	protected function startup()
	{
		parent::startup();

        \Tracy\Debugger::$productionMode = true;
        \Tracy\Debugger::$showBar = false;
        /*pro TESTY vypnuto!!!*/
		//  \Tracy\Debugger::$productionMode = false;
        //  \Tracy\Debugger::$showBar = true;




	}
	
	

   public function authorize() {
	   //$httpRequest = $this->context->getByType('Nette\Http\Request');
	   $httpRequest = $this->getHttpRequest();
	   
	   $this->uri = $httpRequest->getPost();
       $tmpStr = 'POST' . PHP_EOL;
       foreach($this->uri as $key => $one) {
           $tmpStr .= $key . ': ' . $one . PHP_EOL;
       }
       Debugger::log($tmpStr, 'APIAuthorize');

	   if (!isset($this->uri['sync_token']))
	   {
	       $this->uri = $httpRequest->getQuery();
	   }

       $tmpStr = 'GET' . PHP_EOL;
       foreach($this->uri as $key => $one) {
           $tmpStr .= $key . ': ' . $one . PHP_EOL;
       }
       Debugger::log($tmpStr, 'APIAuthorize');


	   if (!isset($this->uri['sync_token']))
	   {
		   echo('<error>No sync token!</error>');
		   $this->terminate();		   
	   }
	   
	   if (empty($this->uri['sync_token']))
	   {
		   echo('<error>Empty sync token!</error>');
		   $this->terminate();		   
	   }	   
	   
	   if ($tmpCompany = $this->CompaniesManager->findAllTotal()->where(['sync_token' => $this->uri['sync_token']])->fetch())
	   {
			$this->cl_company_id = $tmpCompany->id ;
			$this->settings = $tmpCompany;
	   }else{
          // echo( '<BR>' . $this->uri['sync_token']. '<BR>');
		   echo('<error>No rights, wrong sync token!</error>');
		   $this->terminate();
	   }
   }	
   
	public function actionSet()
	{
	   $this->authorize();
	   if (!isset($this->uri['dataxml']) && !isset($this->uri['datazip'])) {
		   foreach($this->uri as $key => $one){
		   		echo('<'.$key.'>'.$one.'</'.$key.'>');
		   }
		   echo('<error>No dataxml or datazip !!</error>');
		   $this->terminate();
	   } elseif(isset($this->uri['datazip'])) {
            $this->dataxml = $this->extract($this->uri['datazip']);
       } else {
           $this->dataxml = $this->uri['dataxml'];
       }


	}

    /**Extract zip archive into string
     * @param $data
     * @return string
     *
     */
    private function extract($data): string
    {
        $retVal = FALSE;
        //prepare folder
        $dataFolder = $this->CompaniesManager->getDataFolder($this->cl_company_id);
        $fileZip =  $dataFolder . '/tmp_apif.zip';
        $destFolder = $dataFolder . '/tmp_apif/';
        $destFile = $dataFolder . '/tmp_apif/data.xml';
        $i = 1;
        while(file_exists($fileZip) || is_null($fileZip)) {
            if (!is_null($fileZip)) {
                $fileZip = $dataFolder . '/tmp_apif' . '-' . $i . '.zip';
                $destFolder = $dataFolder . '/tmp_apif'. '-' . $i . '/';
                $destFile = $dataFolder . '/tmp_apif'. '-' . $i . '/data.xml';
            }
            $i++;
        }
        if (!is_dir($destFolder))
            mkdir($destFolder);

        $data = $this->base64UrlDecode($data);
        if (file_put_contents($fileZip, $data)){
            $zip = new \ZipArchive;
            if ($zip->open($fileZip) === TRUE) {
                $zip->extractTo($destFolder);
                $zip->close();
                if (file_exists($destFile)) {
                    $retVal = file_get_contents($destFile, $retVal);
                    unlink($destFile);
                }
            }
            unlink($fileZip);
        }
        rmdir($destFolder);
        return $retVal;
    }

    private function base64UrlEncode(string $data): string
    {
        $base64Url = strtr(base64_encode($data), '+/', '-_');

        return rtrim($base64Url, '=');
    }

    private function base64UrlDecode(string $base64Url): string
    {
        return base64_decode(strtr($base64Url, '-_', '+/'));
    }

    private function copyDir(string $src, string $dst)
    {
        $dir = opendir($src);
        @mkdir($dst);
        while (($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->copyDir($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }




   public function actionGet() {
		$this->authorize();
		if (!isset($this->uri['id_klienti']))
		{
		   echo('<error>No ID!</error>');
		   $this->terminate();		   
		}
		$this->id = $this->uri['id_klienti'];
   }	   
   
   public function actionGetNew() {
		$this->authorize();
		//$httpRequest = $this->context->getByType('Nette\Http\Request');
		//$uri = $httpRequest->getPost();

		/*pro TESTY vypnuto!!!*/
		
		if (!isset($this->uri['sync_last']))
		{
		   echo('<error>No sync_last!</error>');
		   $this->terminate();		   
		}

		if (isset($this->uri['dataxml']))
		{		
			$this->dataxml = $this->uri['dataxml'];
		}
		
		$this->sync_last = $this->uri['sync_last'];
		
		
		/*pro TESTY !!!*/
	   	/*$this->sync_last = new \Nette\Utils\DateTime;
		$this->sync_last = $this->sync_last->modify('-1 day');*/
		
   }	   
      
   
   public function actionGetAll() {
        $this->authorize();
       if (isset($this->uri['dataxml']))
       {
           $this->dataxml = $this->uri['dataxml'];
           $this->data = json_decode($this->dataxml, TRUE);
       }
        //$httpRequest = $this->context->getByType('Nette\Http\Request');
        $httpRequest = $this->getHttpRequest();

   }


    public function actionLogin() {
        $this->authorize();
        if (!isset($this->uri['username']))
        {
            echo('<error>No username!</error>');
            $this->terminate();
        }
        if (!isset($this->uri['password']))
        {
            echo('<error>No password!</error>');
            $this->terminate();
        }

        $this->username = $this->uri['username'];
        $this->password = $this->uri['password'];
    }


    final public function getUserService(): \Nette\Security\User
    {
        if (!$this->user) {
            throw new \Nette\InvalidStateException('Service User has not been set.');
        }
        return $this->user;
    }


}
