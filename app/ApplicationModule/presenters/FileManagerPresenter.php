<?php
	namespace App\ApplicationModule\Presenters;
	
	use Nette\Application\UI\Form;
	use Nette\Application\UI\Control;
	use Nette,
		App\Model;
	use Nette\Utils\Image;
	use Tracy\Debugger;
	use Exception;
	use Nette\Utils\Finder;
	
	class FileManagerPresenter extends \App\Presenters\BaseAppPresenter {
		
		public $myReadOnly,$txtSearch = NULL, $cl_company_id, $userCanErase, $userCanEdit;
		
		/** @persistent */
		public $id;
		
		
		const
			DEFAULT_STATE = 'Czech Republic';
		
		
		/**
		 * @inject
		 * @var \App\Model\FilesManager
		 */
		public $FilesManager;
		
		/**
		 * @inject
		 * @var \App\Model\CommissionManager
		 */
		public $CommissionManager;
		
		/**
		 * @inject
		 * @var \App\Model\OfferManager
		 */
		public $OfferManager;
		
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
		 * @var \App\Model\InvoiceManager
		 */
		public $InvoiceManager;
		
		/**
		 * @inject
		 * @var \App\Model\InvoiceArrivedManager
		 */
		public $InvoiceArrivedManager;
		
		/**
		 * @inject
		 * @var \App\Model\DeliveryNoteManager
		 */
		public $DeliveryNoteManager;
		
		/**
		 * @inject
		 * @var \App\Model\StoreDocsManager
		 */
		public $StoreDocsManager;
		
		
		/**
		 * @inject
		 * @var \App\Model\PriceListManager
		 */
		public $PriceListManager;
		
		/**
		 * @inject
		 * @var \App\Model\CashManager
		 */
		public $CashManager;
		
		/**
		 * @inject
		 * @var \App\Model\KdbManager
		 */
		public $KdbManager;
		
		/**
		 * @inject
		 * @var \App\Model\OrderManager
		 */
		public $OrderManager;
		
		
		
		protected function createComponentCommissionFileList() {
			return new FileListControl(
                $this->translator,
			    $this->CommissionManager,
				$this->FilesManager,
				$this->CompaniesManager, $this->ArraysManager,
				$this->FilesManager->findAll()->where('cl_commission_id IS NOT NULL'),
				$this->getFileList('cl_commission_id', $this->CommissionManager, 'cm_number'),
				'cl_commission_id', 'cm_number', $this->cl_company_id,  ':Application:Commission:edit');
		}
		
		protected function createComponentOfferFileList() {
			return new FileListControl(
                $this->translator,$this->OfferManager,
				$this->FilesManager,
				$this->CompaniesManager, $this->ArraysManager,
				$this->FilesManager->findAll()->where('cl_offer_id IS NOT NULL'),
				$this->getFileList('cl_offer_id', $this->OfferManager, 'cm_number'),
				'cl_offer_id','cm_number', $this->cl_company_id, ':Application:Offer:edit');
		}
		
		protected function createComponentOrderFileList() {
			return new FileListControl(
                $this->translator,$this->OrderManager,
				$this->FilesManager,
				$this->CompaniesManager, $this->ArraysManager,
				$this->FilesManager->findAll()->where('cl_order_id IS NOT NULL'),
				$this->getFileList('cl_order_id', $this->OrderManager, 'od_number'),
				'cl_order_id','od_number', $this->cl_company_id, ':Application:Order:edit');
		}
		
		protected function createComponentPartnersBookFileList() {
			return new FileListControl(
                $this->translator,$this->PartnersManager,
				$this->FilesManager,
				$this->CompaniesManager, $this->ArraysManager,
				$this->FilesManager->findAll()->where('cl_partners_book_id IS NOT NULL'),
				$this->getFileList('cl_partners_book_main_id', $this->PartnersManager, 'company'),
				'cl_partners_book_main_id','company', $this->cl_company_id,':Application:Partners:edit');
		}
		
		protected function createComponentHelpdeskFileList() {
			return new FileListControl(
                $this->translator,$this->PartnersEventManager,
				$this->FilesManager,
				$this->CompaniesManager, $this->ArraysManager,
				$this->FilesManager->findAll()->where('cl_partners_event_id IS NOT NULL'),
				$this->getFileList('cl_partners_event_id', $this->PartnersEventManager, 'event_number'),
				'cl_partners_event_id', 'event_number', $this->cl_company_id, ':Application:Helpdesk:edit');
		}
		
		protected function createComponentInvoiceFileList() {
			return new FileListControl(
                $this->translator,$this->InvoiceManager,
				$this->FilesManager,
				$this->CompaniesManager, $this->ArraysManager,
				$this->FilesManager->findAll()->where('cl_invoice_id IS NOT NULL'),
				$this->getFileList('cl_invoice_id', $this->InvoiceManager, 'inv_number'),
				'cl_invoice_id', 'inv_number', $this->cl_company_id,  ':Application:Invoice:edit');
		}
		
		protected function createComponentInvoiceArrivedFileList() {
			return new FileListControl(
                $this->translator,$this->InvoiceArrivedManager,
				$this->FilesManager,
				$this->CompaniesManager, $this->ArraysManager,
				$this->FilesManager->findAll()->where('cl_invoice_arrived_id IS NOT NULL'),
				$this->getFileList('cl_invoice_arrived_id', $this->InvoiceArrivedManager, 'inv_number'),
				'cl_invoice_arrived_id', 'inv_number', $this->cl_company_id,':Application:InvoiceArrived:edit');
		}
		
		protected function createComponentDeliveryNoteFileList() {
			return new FileListControl(
                $this->translator,$this->DeliveryNoteManager,
				$this->FilesManager,
				$this->CompaniesManager, $this->ArraysManager,
				$this->FilesManager->findAll()->where('cl_delivery_note_id IS NOT NULL'),
				$this->getFileList('cl_delivery_note_id', $this->DeliveryNoteManager, 'dn_number'),
				'cl_delivery_note_id', 'dn_number', $this->cl_company_id,  ':Application:DeliveryNote:edit');
		}
		
		protected function createComponentStoreDocsFileList() {
			return new FileListControl(
                $this->translator,$this->StoreDocsManager,
				$this->FilesManager,
				$this->CompaniesManager, $this->ArraysManager,
				$this->FilesManager->findAll()->where('cl_store_docs_id IS NOT NULL'),
				$this->getFileList('cl_store_docs_id', $this->StoreDocsManager, 'doc_number'),
				'cl_store_docs_id', 'doc_number', $this->cl_company_id, ':Application:Store:edit');
		}
		
		protected function createComponentPriceListFileList() {
			return new FileListControl(
                $this->translator,$this->PriceListManager,
				$this->FilesManager,
				$this->CompaniesManager, $this->ArraysManager,
				$this->FilesManager->findAll()->where('cl_pricelist_id IS NOT NULL'),
				$this->getFileList('cl_pricelist_id', $this->PriceListManager, 'identification'),
				'cl_pricelist_id', 'identification', $this->cl_company_id,':Application:PriceList:edit');
		}
		
		protected function createComponentPriceListImageFileList() {
			return new FileListControl(
                $this->translator,$this->PriceListManager,
				$this->FilesManager,
				$this->CompaniesManager, $this->ArraysManager,
				$this->FilesManager->findAll()->where('cl_pricelist_image_id IS NOT NULL'),
				$this->getFileList('cl_pricelist_id', $this->PriceListManager, 'cl_pricelist_id'),
				'cl_pricelist_id', 'cl_pricelist_id', $this->cl_company_id, ':Application:PriceList:edit');
		}
		
		protected function createComponentCashFileList() {
			return new FileListControl(
                $this->translator,$this->CashManager,
				$this->FilesManager,
				$this->CompaniesManager, $this->ArraysManager,
				$this->FilesManager->findAll()->where('cl_cash_id IS NOT NULL'),
				$this->getFileList('cl_cash_id', $this->CashManager, 'cash_number'),
				'cl_cash_id', 'cash_number', $this->cl_company_id, ':Application:Cash:edit');
		}
		
		protected function createComponentKdbFileList() {
			return new FileListControl(
                $this->translator,$this->KdbManager,
				$this->FilesManager,
				$this->CompaniesManager, $this->ArraysManager,
				$this->FilesManager->findAll()->where('cl_kdb_id IS NOT NULL'),
				$this->getFileList('cl_kdb_id', $this->KdbManager, 'kdb_number'),
				'cl_kdb_id', 'kdb_number', $this->cl_company_id,  ':Application:Kdb:edit');
		}
		
		public function actionDefault()
		{
			$this->cl_company_id = $this->UserManager->getCompany($this->user->getId())->id;
		}
		
		
		public function renderDefault($modal) {
			
			$this->template->modal                  = $modal;
			$this->template->data                   = FALSE;
			$this->template->data_import            = array();
			$this->userCanErase                     = $this->isAllowed($this->name,'erase');
			$this->userCanEdit                      = $this->isAllowed($this->name,'edit');
			$this->template->data_cash              = $this->FilesManager->findAll()->where('cl_cash_id IS NOT NULL');
			$this->template->data_kdb               = $this->FilesManager->findAll()->where('cl_kdb_id IS NOT NULL');
			$this->template->files                  = $this->FilesManager->findAll();
			$this->template->countCommission        = $this->FilesManager->findAll()->where('cl_commission_id IS NOT NULL')->select('COUNT(id) AS counter')->fetch()->counter;
			$this->template->sizeCommission         = $this->FilesManager->findAll()->where('cl_commission_id IS NOT NULL')->select('SUM(file_size) AS size')->fetch()->size/1000/1000;
			$this->template->countOffer             = $this->FilesManager->findAll()->where('cl_offer_id IS NOT NULL')->select('COUNT(id) AS counter')->fetch()->counter;
			$this->template->sizeOffer              = $this->FilesManager->findAll()->where('cl_offer_id IS NOT NULL')->select('SUM(file_size) AS size')->fetch()->size/1000/1000;
			$this->template->countOrder             = $this->FilesManager->findAll()->where('cl_order_id IS NOT NULL')->select('COUNT(id) AS counter')->fetch()->counter;
			$this->template->sizeOrder              = $this->FilesManager->findAll()->where('cl_order_id IS NOT NULL')->select('SUM(file_size) AS size')->fetch()->size/1000/1000;
			$this->template->countHelpdesk          = $this->FilesManager->findAll()->where('cl_partners_event_id IS NOT NULL')->select('COUNT(id) AS counter')->fetch()->counter;
			$this->template->sizeHelpdesk           = $this->FilesManager->findAll()->where('cl_partners_event_id IS NOT NULL')->select('SUM(file_size) AS size')->fetch()->size/1000/1000;
			$this->template->countKdb               = $this->FilesManager->findAll()->where('cl_kdb_id IS NOT NULL')->select('COUNT(id) AS counter')->fetch()->counter;
			$this->template->sizeKdb                = $this->FilesManager->findAll()->where('cl_kdb_id IS NOT NULL')->select('SUM(file_size) AS size')->fetch()->size/1000/1000;
			$this->template->countPricelistImage    = $this->FilesManager->findAll()->where('cl_pricelist_image_id IS NOT NULL')->select('COUNT(id) AS counter')->fetch()->counter;
			$this->template->sizePricelistImage     = $this->FilesManager->findAll()->where('cl_pricelist_image_id IS NOT NULL')->select('SUM(file_size) AS size')->fetch()->size/1000/1000;
			$this->template->countPricelist         = $this->FilesManager->findAll()->where('cl_pricelist_id IS NOT NULL')->select('COUNT(id) AS counter')->fetch()->counter;
			$this->template->sizePricelist          = $this->FilesManager->findAll()->where('cl_pricelist_id IS NOT NULL')->select('SUM(file_size) AS size')->fetch()->size/1000/1000;
			$this->template->countStoreDocs         = $this->FilesManager->findAll()->where('cl_store_docs_id IS NOT NULL')->select('COUNT(id) AS counter')->fetch()->counter;
			$this->template->sizeStoreDocs          = $this->FilesManager->findAll()->where('cl_store_docs_id IS NOT NULL')->select('SUM(file_size) AS size')->fetch()->size/1000/1000;
			$this->template->countDeliveryNote      = $this->FilesManager->findAll()->where('cl_delivery_note_id IS NOT NULL')->select('COUNT(id) AS counter')->fetch()->counter;
			$this->template->sizeDeliveryNote       = $this->FilesManager->findAll()->where('cl_delivery_note_id IS NOT NULL')->select('SUM(file_size) AS size')->fetch()->size/1000/1000;
			$this->template->countCash              = $this->FilesManager->findAll()->where('cl_cash_id IS NOT NULL')->select('COUNT(id) AS counter')->fetch()->counter;
			$this->template->sizeCash               = $this->FilesManager->findAll()->where('cl_cash_id IS NOT NULL')->select('SUM(file_size) AS size')->fetch()->size/1000/1000;
			$this->template->countInvoiceArrived    = $this->FilesManager->findAll()->where('cl_invoice_arrived_id IS NOT NULL')->select('COUNT(id) AS counter')->fetch()->counter;
			$this->template->sizeInvoiceArrived     = $this->FilesManager->findAll()->where('cl_invoice_arrived_id IS NOT NULL')->select('SUM(file_size) AS size')->fetch()->size/1000/1000;
			$this->template->countInvoice           = $this->FilesManager->findAll()->where('cl_invoice_id IS NOT NULL')->select('COUNT(id) AS counter')->fetch()->counter;
			$this->template->sizeInvoice            = $this->FilesManager->findAll()->where('cl_invoice_id IS NOT NULL')->select('SUM(file_size) AS size')->fetch()->size/1000/1000;
			$this->template->countPartnersBook      = $this->FilesManager->findAll()->where('cl_partners_book_id IS NOT NULL')->select('COUNT(id) AS counter')->fetch()->counter;
			$this->template->sizePartnersBook       = $this->FilesManager->findAll()->where('cl_partners_book_id IS NOT NULL')->select('SUM(file_size) AS size')->fetch()->size/1000/1000;
		}
		
		
		private function getFileList($type, \App\Model\Base $dataManager, $numberCol)
		{
			$dataFolder = $this->CompaniesManager->getDataFolder($this->cl_company_id);
			$subFolder = $this->ArraysManager->getSubFolder(FALSE, $type);
			$dir = $dataFolder . '/' . $subFolder . '/import';
			if (is_dir($dir)) {
				$arrFiles = array();
				//dump($this->parameters['allowed_files']);
				$arrAllowedFiles = str_getcsv($this->parameters['allowed_files'], ',');
				//dump($arrAllowedFiles);
				//dump($dir);
				foreach (Finder::findFiles($arrAllowedFiles)->in($dir) as $key => $file) {
					//echo $key; // $key je řetězec s názvem souboru včetně cesty
					//echo $file; // $file je objektem SplFileInfo
					
					
					//at first get maximum length of number
					
					$maxLength = $dataManager->findAll()->select(' Max(CHAR_LENGTH(' . $numberCol . ')) AS Max')->fetch();
					$fileNamePart = substr($file->getFileName(), 0, $maxLength->Max - 1);
					bdump($fileNamePart);
					$paired = $dataManager->findAll()->where($numberCol . ' LIKE ?', '%' . $fileNamePart . '%')->fetch();
					//dump($paired);
					if (!$paired)  {
						$fileNamePart = str_getcsv($file->getFileName(), "_");
						$paired = $dataManager->findAll()->where($numberCol . ' LIKE ?', '%' . $fileNamePart[0] . '%')->fetch();
					}
					if ($paired) {
						$idPaired = $paired->id;
						$pairedNum = $paired[$numberCol];
					} else {
						
						$idPaired = NULL;
						$pairedNum = "";
					}
					
					$arrFiles[$key] = array('name' => $file->getFileName(),
						'exte' => $file->getExtension(),
						'size' => $file->getSize(),
						'type' => $file->getType(),
						'date_cre' => $file->getCTime(),
						'date_mod' => $file->getMTime(),
						'id' => $idPaired,
						'number' => $pairedNum);
					
					
				}
			}else{
				$arrFiles = array();
			}
			//dump($arrFiles);
			
			return $arrFiles;
		}
		
		
		
		
	}
