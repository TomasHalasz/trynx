<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Exception;

/**
 * Workplaces management.
 */
class WorkplacesManager extends Base
{
	const COLUMN_ID = 'id';
	public $tableName = 'cl_workplaces';
	
	/** @var Nette\Database\Context */
	public $database;
	/** @var Nette\Http\Request */
	private $httpRequest;
	/** @var Nette\Http\Response */
	private $httpResponse;
	public $userManager;
	public $user;
	
	private $session;
	
	public function __construct(Nette\Database\Context $database, Nette\DI\Container $service, Nette\Http\Session $session,  UserManager $userManager,  \Nette\Security\User $user, \DatabaseAccessor $accessor)
	{
        parent::__construct($database, $userManager, $user, $session, $accessor);
		$this->database = $database;
		$this->httpRequest = $service->getService('httpRequest');
		$this->httpResponse = $service->getService('httpResponse');
		$this->session = $session;
		$this->userManager = $userManager;
		$this->user = $user;
		
		
	}

	
	public function checkCurrent()
	{
		$tmpIp = $this->httpRequest->getRemoteAddress();
		$tmpHost = $this->httpRequest->getRemoteHost();
		$tmpAgent = $this->httpRequest->getHeader('User-Agent');
		$tmpCookie = $this->httpRequest->getCookie('TrynxID');
		if (is_null($tmpCookie)){
			$tmpCookie = \Nette\Utils\Random::generate(128,'A-Za-z0-9');
			$this->httpResponse->setCookie('TrynxID', $tmpCookie, '30 days');
		}
		//$this->httpResponse->deleteCookie('TrynxID'); // smaže cookie
		//die;
		//$httpResponse->setCookie('lang', 'cs', '100 days'); // odešle cookie
		// deleteCookie($name, [$path, [$domain, [$secure]]])
		//$httpResponse->deleteCookie('lang'); // smaže cookie
		$tmpWorkplace = $this->findAll()->where('trynx_id = ?', $tmpCookie)->fetch();
		if ($tmpWorkplace){
			//workplace allready exists, set a new 30days
			$this->httpResponse->setCookie('TrynxID', $tmpCookie, '30 days');
			$now = new Nette\Utils\DateTime();
			$tmpWorkplace->update(array('last_activity' => $now));
			$retVal = ($tmpWorkplace->disabled) ? FALSE : TRUE;
			
		}else{
			//workplace doesn't exists, create a new one
			$this->insert(array('trynx_id' => $tmpCookie, 'remote_address' => $tmpIp, 'remote_host' => $tmpHost, 'user_agent' => $tmpAgent));
			$retVal = TRUE;
		}
		
		return $retVal;

	}
		

	
}

