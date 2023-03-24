<?php

namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class ReportManagerPresenter extends \App\Presenters\BaseListPresenter {

   
    /** @persistent */
    public $page_b;
    
    /** @persistent */
    public $filter;        

    /** @persistent */
    public $filterColumn;            

    /** @persistent */
    public $filterValue;                    
    
    /** @persistent */
    public $sortKey;        

    /** @persistent */
    public $sortOrder;          
    
    /**
    * @inject
    * @var \App\Model\ReportManager
    */
    public $DataManager;    

 
    protected function startup()
    {
	parent::startup();
	//$this->translator->setPrefix(['applicationModule.ReportManager']);
	$this->dataColumns = [
					'report_description' => [$this->translator->translate('Popis'), 'format' => 'text'],
					'report_name' => [$this->translator->translate('Použití'), FALSE,'function' => 'getReportName'],
					'active' => [$this->translator->translate('Aktivní'),'format' => 'boolean'],
                    'replace_origin' => [$this->translator->translate('Nahrazuje_originál'),'format' => 'boolean'],
					'created' => [$this->translator->translate('Vytvořeno'), 'format' => 'datetime'],
					'create_by' => [$this->translator->translate('Vytvořil'), 'format' => 'text'],
					'changed' => [$this->translator->translate('Změněno'), 'format' => 'datetime'],
					'change_by' => [$this->translator->translate('Změnil'), 'format' => 'text']
    ];
	$this->FilterC = ' ';
	$this->DefSort = 'report_description';
	$this->defValues = [];
	$this->toolbar = [1 => ['url' => $this->link('new!'), 'rightsFor' => 'write', 'label' => $this->translator->translate('Nový_záznam'), 'class' => 'btn btn-primary']];
    }	
    
    public function renderDefault($page_b = 1,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal, $cxs)  {
		parent::renderDefault($page_b,$idParent,$filter,$sortKey,$sortOrder,$filterColumn,$filterValue,$modal,$cxs);
    }
    
    public function renderEdit($id,$copy,$modal){
		parent::renderEdit($id,$copy,$modal);
		
		$tmpData = $this->DataManager->find($id);
		if ($tmpData['report_file'] != ''){
			//$rptName = $this->getReportFileName($tmpData['report_name']);
			$dataFolder = $this->CompaniesManager->getDataFolder($this->settings->id);
			$subFolder = $this->ArraysManager->getSubFolder(FALSE, 'report');
			$rptFile = $dataFolder . '/' . $subFolder . '/' . $tmpData['report_file'];
			
			if (is_file($rptFile)) {
				$strReport = file_get_contents($rptFile);

				/*$this['edit']->setValues(array('report_code' => '<pre class="language-html line-numbers"><code class="language-html">' . htmlspecialchars($strReport) . '</code></pre>'));*/
				//$this['edit']->setValues(array('report_code' =>nl2br( htmlspecialchars($strReport))));
				$this['edit']->setValues(['report_code' => $strReport]);
			}
		}

    }
    
    
    protected function createComponentEdit($name)
    {	
            $form = new Form($this, $name);
	    //$form->setMethod('POST');
	    $form->addHidden('id',NULL);

		$form->addText('report_description', $this->translator->translate('Popis'), 40, 200)
			->setRequired($this->translator->translate('Zadejte_prosím_název_sestavy'))
			->setHtmlAttribute('placeholder',$this->translator->translate('Název_sestavy'));
    
	    
	    $arrReportNames = $this->getReportAll();
	    //dump($arrCountries);
	    //die;
		$form->addselect('report_name', $this->translator->translate('Výchozí_sestava'),$arrReportNames)
			->setPrompt($this->translator->translate('Zvolte výchozí sestavu'))
			->setHtmlAttribute('data-placeholder',$this->translator->translate('Zvolte_výchozí_sestavu'))
			->setHtmlAttribute('placeholder',$this->translator->translate('Výchozí_sestava'));
	    $form->addCheckbox('active', $this->translator->translate('Aktivní'))
			->setHtmlAttribute('class', 'items-show');

        $form->addCheckbox('replace_origin', $this->translator->translate('Nahrazuje_originál'))
            ->setHtmlAttribute('class', 'items-show');
	  
	    $form->addTextArea('report_code', $this->translator->translate('HTML_kód_sestavy'),40, 40 )
					->setHtmlAttribute('placeholder',$this->translator->translate('Kód_sestavy'));
	    
		$form->addSubmit('send', $this->translator->translate('Uložit'))->setHtmlAttribute('class','btn btn-success');
	    $form->addSubmit('back', $this->translator->translate('Zpět'))
					->setHtmlAttribute('class','btn btn-warning')
					->setValidationScope([])
					->onClick[] = [$this, 'stepBack'];
		$form->onSuccess[] = [$this, 'SubmitEditSubmitted'];
            return $form;
    }

    public function stepBack()
    {	    
		$this->redirect('default');
    }		

    public function SubmitEditSubmitted(Form $form)
    {
		$data=$form->values;
		//dump($data);
		//	die;
		if ($form['send']->isSubmittedBy())
		{
			$tmpData = $this->DataManager->find($data['id']);
			if ($tmpData['report_file'] == '' && $data['report_name'] != ''){
				//$tmpName = basename($tmpData['report_name']) . '.latte';

				
				$dataFolder = $this->CompaniesManager->getDataFolder($this->settings->id);
				$subFolder = $this->ArraysManager->getSubFolder(FALSE, 'report');
				$dir = $dataFolder . '/' . $subFolder;
				if (is_dir($dir)) {
					//$reportFile = $dir . '/' .$tmpName;
					$destFile=NULL;
					//$fileName = $file->getSanitizedName();
					$fileName = $data['report_name'];
					$i = 0;
					$arrFile = str_getcsv($fileName, '.');
					while(file_exists($destFile) || is_null($destFile))
					{
						if (!is_null($destFile)) {
							$fileName = $arrFile[0] . '-' . $i . '.' . $arrFile[1];
						}
						//$dataFolder = $this->CompaniesManager->getDataFolder($this->cl_company_id);
						//$subFolder  = $this->ArraysManager->getSubFolder(array(), $formValues['type']);
						$destFile   =  $dataFolder . '/' . $subFolder . '/' . $fileName;
						$i++;
					}
					$data['report_file'] = $fileName;
					$sourceFile = $this->getReportFileName( $data['report_name']);
					$destFile   =  $dataFolder . '/' . $subFolder . '/' . $fileName;
					$strReport = file_get_contents($sourceFile);
					$strReport = str_replace('../../../', '../../../app/', $strReport);
					
					file_put_contents($destFile, $strReport);
					//copy($sourceFile, $destFile);
				}
				$tmpDestination = 'edit';
			}else{
				bdump($data['report_code']);
				$dataFolder = $this->CompaniesManager->getDataFolder($this->settings->id);
				$subFolder = $this->ArraysManager->getSubFolder(FALSE, 'report');
				$rptFile = $dataFolder . '/' . $subFolder . '/' . $tmpData['report_file'];
				//$data['report_code'] = str_replace('<br>', PHP_EOL,$data['report_code']);
				//$data['report_code'] = htmlspecialchars_decode($data['report_code']);
				//$this->strip_tags_content($data['report_code'], '<span>', TRUE);
				/*$data['report_code'] = str_replace('<pre class="language-html line-numbers">', '', $data['report_code']);
				$data['report_code'] = str_replace('<code class="language-html">', '', $data['report_code']);
				$data['report_code'] = str_replace('</code>', '', $data['report_code']);
				$data['report_code'] = str_replace('</pre>', '', $data['report_code']);*/

				file_put_contents($rptFile, $data['report_code']);
				unset($data['report_name']);
				$tmpDestination = 'default';
			}
			unset($data['report_code']);
			
			if (!empty($data->id)) {
				$this->DataManager->update($data, TRUE);
			}else {
				$this->DataManager->insert($data);
			}
			$this->flashMessage($this->translator->translate('Změny_byly_uloženy'), 'success');
			$this->redirect($tmpDestination);
		}

    }
	
	
	//aditional control before delete from baseList
	public function beforeDeleteBaseList($id)
	{
		try {
			$dataFolder = $this->CompaniesManager->getDataFolder($this->settings->id);
			$subFolder = $this->ArraysManager->getSubFolder(FALSE, 'report');
			$dir = $dataFolder . '/' . $subFolder;
			$tmpData = $this->DataManager->find($id);
			
			if (is_dir($dir)) {
				$file = $dir . '/' . $tmpData['report_file'];
				if (is_file($file)) {
					unlink($file);
				}
			}
			return TRUE;
		}catch (Exception $e) {
			$errorMess = $e->getMessage();
			$this->flashMessage($errorMess,'danger');
			return FALSE;
		}
	}
 
 
	public function strip_tags_content($text, $tags = '', $invert = FALSE) {
		
		preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags);
		$tags = array_unique($tags[1]);
		
		if(is_array($tags) AND count($tags) > 0) {
			if($invert == FALSE) {
				return preg_replace('@<(?!(?:'. implode('|', $tags) .')\b)(\w+)\b.*?>.*?</\1>@si', '', $text);
			}
			else {
				return preg_replace('@<('. implode('|', $tags) .')\b.*?>.*?</\1>@si', '', $text);
			}
		}
		elseif($invert == FALSE) {
			return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
		}
		return $text;
	}
	
}
