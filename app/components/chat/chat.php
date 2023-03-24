<?php

namespace App\Controls;

use Contributte\Translation\PrefixedTranslator;
use Nette\Application\UI\Control;
use Netpromotion\Profiler\Profiler;
use Nette,
	App\Model;
use Nette\Application\UI\Form;

class ChatControl extends Control
{

    private $cl_company_id, $cl_user_id, $parent_id, $arrEmlSendTo;

    /** @var \App\Model\UserManager*/
    private $UserManager;

    /** @var \App\Model\Base*/
    private $DataManager;

    /** @var \App\Model\ChatManager*/
    private $ChatManager;

    /** @var \App\Model\ArraysManager*/
    private $ArraysManager;

    /** @var \App\Model\EmailingManager*/
    private $EmailingManager;

    /** @var Nette\Localization\ITranslator @inject */
    public $translator;

    public function __construct( Nette\Localization\Translator $translator, Model\ChatManager $chatManager, Model\Base $dataManager, Model\ArraysManager $arraysManager,
                                 Model\UserManager $userManager, Model\EmailingManager $emailingManager,
                                 $parent_id, $cl_company_id, $cl_user_id, $arrEmlSendTo)
    {
        //parent::__construct(); // vždy je potřeba volat rodičovský konstruktor
        $this->ChatManager       = $chatManager;
        $this->DataManager       = $dataManager;
        $this->translator        = $translator;
        $this->cl_company_id     = $cl_company_id;
        $this->cl_user_id        = $cl_user_id;
        $this->parent_id         = $parent_id;
        $this->arrEmlSendTo      = $arrEmlSendTo;
        $this->ArraysManager     = $arraysManager;
        $this->UserManager       = $userManager;
        $this->EmailingManager   = $emailingManager;

    }

    public function setId($id)
    {
        $this->parent_id = $id;
    }


    protected function startup()
    {
        parent::startup();
    }
    
    public function render()
    {
        $this->template->setFile(__DIR__ . '/chat.latte');
    	$this->template->chat = $this->ChatManager->findAll()->where($this->DataManager->tableName . '_id = ?', $this->parent_id)->order('created DESC');
    	$this->template->messCount = $this->template->chat->count();
        $this->template->cl_users_id = $this->cl_user_id ;
        $this['edit']->setValues(NULL);
        $this->template->render();
    }


    protected function createComponentEdit($name)
    {
        $form = new Form($this, $name);
        $form->addHidden('id', NULL);
        $form->addTextArea('message', 'Zpráva', 60, 3)
            ->setRequired('Nejprve zapište zprávu a až poté odesílejte :-)')
            ->setHtmlAttribute('placeholder','Zpráva');

        $form->addSubmit('send', 'Odeslat')->setHtmlAttribute('class','btn btn-success btn-large');
        $form->addSubmit('back', 'Zpět')
            ->setHtmlAttribute('class','btn btn-warning')
            ->setValidationScope([])
            ->onClick[] = [$this, 'stepBack'];
        $form->onSuccess[] = [$this,'SubmitEditSubmitted'];
        return $form;
    }

    public function stepBack()
    {
        //$this->redirect('default');
    }

    public function SubmitEditSubmitted(Form $form)
    {
        $data = $form->values;
        if ($form['send']->isSubmittedBy())
        {
            $data[$this->DataManager->tableName . '_id'] = $this->parent_id;
            $data['cl_users_id'] = $this->cl_user_id;
            if (!empty($data->id))
                $this->ChatManager->update($data, TRUE);
            else {
                $newRow = $this->ChatManager->insert($data);
                $data['id'] = $newRow->id;
            }
            $tmpTotal = $this->ChatManager->findAll()->where($this->DataManager->tableName . '_id = ?', $this->parent_id)->count();
            $tmpParent = $this->DataManager->find($this->parent_id);
            if ($tmpParent && isset($tmpParent['chat_count'])) {
                $tmpParent->update(['chat_count' => $tmpTotal]);
            }

            $this->sendEmlNotify($data);
        }


        $this->flashMessage('Uloženo.', 'success');
        //$this->redirect('default');
        $this['edit']->setValues(['message' => '']);

        $this->presenter->redrawControl('flash');
        $this->redrawControl('messCount');
        $this->redrawControl('snpChat');
    }

    public function handleErase($id)
    {
        $tmpData = $this->ChatManager->find($id);
        if ($tmpData['cl_users_id'] == $this->cl_user_id){
            $this->ChatManager->delete($id);
            $tmpTotal = $this->ChatManager->findAll()->where($this->DataManager->tableName . '_id = ?', $this->parent_id)->count();
            $tmpParent = $this->DataManager->find($this->parent_id);
            if ($tmpParent){
                $tmpParent->update(['chat_count' => $tmpTotal]);
            }

        }
        $this->redrawControl('messCount');
        $this->redrawControl('snpChat');
    }

    private function sendEmlNotify($data){
        $dataEml = [];
        $dataEml['singleEmailTo'] = implode(';', $this->arrEmlSendTo);

        if (!is_null($this->cl_user_id)) {
            $tmpEmail = $this->UserManager->getEmail($this->cl_user_id);
            $tmpUser = $this->UserManager->getUserById($this->cl_user_id);
            if ($tmpEmail != '') {
                if ($dataEml['singleEmailTo'] != '') {
                    $dataEml['singleEmailTo'] .= ';';
                }
                $dataEml['singleEmailTo'] .= $tmpUser['name'] . ' <' . $tmpEmail . '>';
            }
        }
        //bdump($dataEml['singleEmailTo']);
        if ($dataEml['singleEmailTo'] != '') {
            $tmpData = $this->ChatManager->find($data['id']);

            if ($tmpData){
                $dataEml['singleEmailFrom'] = $this->presenter->settings->name . ' <' . $this->presenter->settings->email . '>';
                if ($this->presenter->settings['smtp_email_global'] == 1)
                {
                    $emailFrom = $this->presenter->validateEmail($this->presenter->settings['smtp_username']);
                    if (!empty($emailFrom)){
                        $dataEml['singleEmailFrom'] = $this->presenter->settings['name'] . ' <' . $emailFrom . '>';
                    }
                }

                $tmpParent = $this->DataManager->find($this->parent_id);
                $docNumber = $tmpParent[$this->ArraysManager->getDocNumberName($this->DataManager->tableName)];
                $dataEml['subject'] = '[' . $docNumber . ']' . ' - ' . $this->translator->translate('Uživatel') . ' ' . $tmpData->cl_users['name'] . ' ' . $this->translator->translate('přidal_komentář');
                $link = $this->presenter->link('//showBsc!', ['id' => $this->parent_id, 'copy' => false]);
                //$tmpEmlText = $this->EmailingTextManager->getEmailingText('complaint', $link, $tmpNewData, NULL);
                $tmpEmlText['body'] = '<h3>' . $this->translator->translate('Nový_komentář') . '</h3>';
                $tmpEmlText['body'] .= 'Autor: ' . $tmpData->cl_users['name'] . '<br>';
                $tmpEmlText['body'] .= 'Datum a čas: ' . date_format($data['created'], 'd.m.Y H:i')  . '<br>';
                $tmpEmlText['body'] .= 'Zpráva: ' . $data['message'] . '<br>';
                $tmpEmlText['body'] .= 'Odkaz: <a href="' . $link . '">' . $link . '</a>';

                $template = $this->createTemplate()->setFile(__DIR__.'/../../templates/Emailing/email.latte');
                $template->body = $tmpEmlText['body'];

                $dataEml['body'] = $template;
                $dataEml['to_send'] = 1;
                $this->EmailingManager->insert($dataEml);
            }

        }
    }

}