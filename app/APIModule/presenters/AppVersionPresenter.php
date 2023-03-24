<?php

namespace App\APIModule\Presenters;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form,
    Nette\Image;
use Nette\Utils\DateTime;
use Nette\Utils\Strings;
use Tracy\Debugger;
use Nette\Application\Responses;

class AppVersionPresenter extends \App\APIModule\Presenters\BaseAPI
{

    /**
     * @inject
     * @var \App\Model\ArraysManager
     */
    public $ArraysManager;

    /**
     * @inject
     * @var \App\Model\VersionsManager
     */
    public $versionsManager;


    public function actionGetLast()
    {
        //parent::actionGetAll();
        $httpRequest = $this->getHttpRequest();
        $this->uri = $httpRequest->getPost();
        if (!isset($this->uri['ic'])) {
            $this->uri = $httpRequest->getQuery();
        }
        $tmpIc = $this->uri['ic'];
        $tmpVersion1 = substr($this->uri['version'], 0, 4);
        $tmpVersion2 = substr($this->uri['version'], 5, 3);

        try {

            $tmpData = $this->versionsManager->findAllTotal()->where('allowed_ic LIKE ?', '%' . $tmpIc . '%')->
            where('SUBSTR(version,1,4) > ? OR (SUBSTR(version,1,4) = ? AND SUBSTR(version,6,3) > ?)', $tmpVersion1, $tmpVersion1, $tmpVersion2)->
            limit(1)->order('version_date ASC');
            if (!($data = $tmpData->fetch())) {
                throw new \Exception('Pro dané IČ není download k dispozici');
            }

            $dir = __DIR__ . "/../../../upgrades/";
            $versionFile = $dir . 'upgrade-' . $data['version'] . '.zip';

            $httpResponse = $this->getHttpResponse();
            $httpResponse->setContentType('application/zip');
            $httpResponse->sendAsFile('upgrade-' . $data['version'] . '.zip');
            echo(file_get_contents($versionFile));
            $this->terminate();

        } catch (\Exception $e) {
            echo($e->getMessage());
        }


    }

    public function actionCheckLast()
    {
        //parent::actionGetAll();
        $httpRequest = $this->getHttpRequest();
        $this->uri = $httpRequest->getPost();
        if (!isset($this->uri['ic'])) {
            $this->uri = $httpRequest->getQuery();
        }
        $tmpIc = $this->uri['ic'];
        $tmpVersion1 = substr($this->uri['version'], 0, 4);
        $tmpVersion2 = substr($this->uri['version'], 5, 3);

        try {
            $tmpData = $this->versionsManager->findAllTotal()->where('allowed_ic LIKE ?', '%' . $tmpIc . '%')->
                    where('SUBSTR(version,1,4) > ? OR (SUBSTR(version,1,4) = ? AND SUBSTR(version,6,3) > ?)', $tmpVersion1, $tmpVersion1, $tmpVersion2)->
                    limit(1)->order('version_date ASC');

            $httpResponse = $this->getHttpResponse();
            $httpResponse->setContentType('application/json');
            $arrResult = [];
            if (!($data = $tmpData->fetch())) {
                $arrResult['status'] = 'NO';
                $arrResult['message'] = 'Máte nejnovější verzi dostupnou pro vaše IČ ' . $this->uri['ic'];
            } else {
                $arrResult['status'] = 'OK';
                $arrResult['message'] = 'K dispozici je nová verze ';
                $arrResult['version'] = $data['version'];
                $arrResult['version_date'] = $data['version_date'];
                $arrResult['md5_checksum'] = $data['md5_checksum'];
            }
            //$this->sendJson($arrResult);
            echo(json_encode($arrResult));
            $this->terminate();
        } catch (\Exception $e) {
            echo($e->getMessage());
        }


    }


}
