<?php
namespace App\ApplicationModule\Presenters;


use Nette\Application\UI\Form,
    Nette\Image;

class DocumentsPresenter extends \App\Presenters\BaseAppPresenter
{

    public $tmpStamp, $tmpLogo;

    /**
     * @inject
     * @var \App\Model\DocumentsManager
     */
    public $DocumentsManager;

    /**
     * @inject
     * @var \App\Model\CompaniesManager
     */
    public $CompaniesManager;


    public function renderDefault()
    {

    }

    public function renderShowOld($cl_company_id, $key_document)
    {
        if ($data = $this->DocumentsManager->findAllTotal()->where('cl_company_id = ? AND key_document = ? AND valid = ?', $cl_company_id, $key_document, 1)->fetch()) {
            if ($settings = $this->CompaniesManager->findAllTotal($cl_company_id)) {
                $srcLogo = __DIR__ . "/../../../data/pictures/" . $settings->picture_logo;
                if (is_file($srcLogo)) {
                    $tmpDir = __DIR__ . "/../../../www/images/tmp/";
                    if (!is_dir($tmpDir))
                        mkdir($tmpDir);

                    $this->tmpLogo = $tmpDir . $settings->picture_logo;
                    copy($srcLogo, $this->tmpLogo);
                }

                $srcStamp = __DIR__ . "/../../../data/pictures/" . $settings->picture_stamp;
                if (is_file($srcStamp)) {
                    $this->tmpStamp = $tmpDir . $settings->picture_stamp;
                    copy($srcStamp, $this->tmpStamp);
                }


                $pdf = new \PdfResponse\PdfResponse($data->html_document, $this->context);
                //$pdf->mPDF->OpenPrintDialog();

                // Všechny tyto konfigurace jsou volitelné:
                // Orientace stránky
                $pdf->pageOrientation = \PdfResponse\PdfResponse::ORIENTATION_PORTRAIT;
                // Formát stránky
                //$pdf->pageFormat = "A4-L";
                $pdf->pageFormat = "A4";
                // Okraje stránky
                //$pdf->pageMargins = "100,100,100,100,20,60";
                $pdf->pageMargins = "5,5,5,5,20,60";
                // Způsob zobrazení PDF
                //$pdf->displayLayout = "continuous";
                // Velikost zobrazení
                $pdf->displayZoom = "fullwidth";
                // Název dokumentu
                $pdf->documentTitle = $data->doc_title;
                // Dokument vytvořil:
                $pdf->documentAuthor = $data->doc_author;

                // Ignorovat styly v html (v tagu <style>?)
                //$pdf->ignoreStylesInHTMLDocument = true;

                // Další styly mimo HTML dokument
                //$pdf->styles .= "p {font-size: 80%;}";

                // Callback - těsně před odesláním výstupu do prohlížeče
                $pdf->onBeforeComplete[] = array($this, 'pdfBeforeComplete');

                //$pdf->mPDF->IncludeJS("app.alert('This is alert box created by JavaScript in this PDF file!',3);");
                //$pdf->mPDF->IncludeJS("app.alert('Now opening print dialog',1);");
                //$pdf->mPDF->OpenPrintDialog();

                // Ukončíme presenter -> předáme řízení PDFresponse
                //$this->terminate($pdf);
                //$pdf->OpenPrintDialog();

                $this->sendResponse($pdf);
                //erase tmp files

            }
        }
    }

    public function pdfBeforeComplete()
    {
        //dump($this->tmpLogo);
        //die;
        if (is_file($this->tmpStamp))
            unlink($this->tmpStamp);

        if (is_file($this->tmpLogo))
            unlink($this->tmpLogo);
    }

    public function renderShow($cl_company_id, $key_document)
    {
        if ($data = $this->DocumentsManager->findAllTotal()->where('cl_company_id = ? AND key_document = ? AND valid = ?', $cl_company_id, $key_document, 1)->fetch()) {
            //dump($data);
            if ($settings = $this->CompaniesManager->findAllTotal($cl_company_id) && !is_null($data['cl_files_id'])) {
                //dump($settings);
                if ($file = $this->FilesManager->findAllTotal()->where('cl_company_id =? AND id = ?', $cl_company_id, $data->cl_files_id)->fetch())
                {
                    if ($file->new_place == 0) {
                        $fileSend = __DIR__ . "/../../../data/files/" . $file->file_name;
                    }else{
                        $dataFolder = $this->CompaniesManager->getDataFolder($cl_company_id);
                        $subFolder = $this->ArraysManager->getSubFolder($file);
                        $fileSend =  $dataFolder . '/' . $subFolder . '/' . $file->file_name;
                    }
                    if (file_exists($fileSend)) {
                        $this->presenter->sendResponse(new \Nette\Application\Responses\FileResponse($fileSend, $file->label_name, $file->mime_type));
                    }else{
                        $this->redirect('Documents:nofile');
                    }
                 }else{
                    $this->redirect('Documents:nofile');
                }
            }else{
                $this->redirect('Documents:nofile');
            }
        }
     //die;
    }

    public function renderNofile()
    {
        $this->template->text=$this->translator->translate('Dokument_nelze_zobrazit_Kontaktujte_svého_obchodníka');
    }

}
