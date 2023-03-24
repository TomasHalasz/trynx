<?php
namespace App\APIModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Tracy\Debugger;
use App\XML;


class UsersPresenter  extends \App\APIModule\Presenters\BaseAPI{
    /**
     * @inject
     * @var \App\Model\UserManager
     */
    public $DataManager;

    public function actionLogin()
    {
        parent::actionLogin();
        try {
            //Debugger::log($this->username . " " . $this->password);
            $this->getUserService()->login($this->username, $this->password);
            $tmpData = $this->user->getIdentity();
            $arrData[] = $tmpData->getData();
            unset($arrData[0]['created']);
            unset($arrData[0]['changed']);
            unset($arrData[0]['create_by']);
            unset($arrData[0]['change_by']);
            //Debugger::log($arrData);
            $this->sendJson($arrData, \Nette\Utils\Json::PRETTY);
        } catch (Exception $e) {
            $arrData = ['error' => 'Chyba přihlášení'];
            $this->sendJson($arrData, \Nette\Utils\Json::PRETTY);
        }

    }




}

