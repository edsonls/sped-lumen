<?php


namespace App\Objects;


use NFePHP\DA\Legacy\FilesFolders;
use NFePHP\DA\NFe\Danfce;
use NFePHP\DA\NFe\Danfe;
use stdClass;

class NfePrint
{
    /**
     * @var stdClass
     */
    private $stdData;
    private $pach = '';

    public function __construct(stdClass $arrayData)
    {
        $this->stdData = $arrayData;
        $this->pach = storage_path('xml_fiscais') . DIRECTORY_SEPARATOR .
            $this->stdData->folder . DIRECTORY_SEPARATOR .
            $this->stdData->model . DIRECTORY_SEPARATOR .
            'assinados' . DIRECTORY_SEPARATOR . $this->stdData->y .
            DIRECTORY_SEPARATOR . $this->stdData->m . DIRECTORY_SEPARATOR;
    }

    public function printNote()
    {
        if ($this->stdData->model == 55) {
            $this->printNFe();
        } else {
            $this->printNFce();
        }
    }

    /**
     * @return string
     */
    private function getFile()
    {
        return FilesFolders::readFile($this->pach . $this->stdData->chave . '-nfe.xml');
    }

    private function printNFe()
    {
        $danfe = new Danfe($this->getFile(), 'P', 'A4', '', 'I', '');
        $id = $danfe->montaDANFE();
        $pdf = $danfe->render();
        //o pdf porde ser exibido como view no browser
        //salvo em arquivo
        //ou setado para download forçado no browser
        //ou ainda gravado na base de dados
        header('Content-Type: application/pdf');
        echo $pdf;
    }

    private function printNFce()
    {
        $danfce = new Danfce($this->getFile(), '', 0);
        $id = $danfce->monta();
        $pdf = $danfce->render();
        header('Content-Type: application/pdf');
        echo $pdf;
    }
}