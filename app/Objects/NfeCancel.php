<?php


namespace App\Objects;


use App\Http\Objects\Company;
use Exception;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Complements;
use NFePHP\NFe\Tools;

class NfeCancel
{
    private $model;
    private $arrayNote;
    private $company;
    private $caminhoXml;
    private $patch;
    private $xJust;
    private $chave;
    private $nProt;

    /**
     * NfeObj constructor.
     *
     * @param int    $model
     * @param array  $arrayNote
     *
     * @param string $xJust
     *
     * @throws Exception
     */
    public function __construct(
        int $model,
        array $arrayNote,
        string $xJust = 'CANCELAMENTO DE NOTA DEVIDO A UM ERRNO INESPERADO NO SISTEMA'
    ) {
        $this->model = $model;
        $this->xJust = $xJust;
        $this->arrayNote = $arrayNote;
        $this->nProt = $this->arrayNote['hash']->numprot;
        $this->chave = $this->arrayNote['hash']->chave;
        /** var $company  pego o primeiro produto onde vai ter os dados do clientes **/
        $this->company = new Company($arrayNote['company']);
        $this->caminhoXml = storage_path('xml_fiscais') . DIRECTORY_SEPARATOR .
            $this->company->db_name . DIRECTORY_SEPARATOR .
            $model . DIRECTORY_SEPARATOR;
        $this->patch = $this->caminhoXml . 'canceladas' .
            DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('m');
        if (!file_exists($this->patch)) {
            mkdir($this->patch, 0755, true);
        }
    }

    /**
     * @throws Exception
     */
    public function cancel()
    {
        $tools = new Tools($this->company->getJsonConfig(), $this->company->getCertificate()->getCertificate());
        $tools->model($this->model);
        $response = $tools->sefazCancela($this->chave, $this->xJust, $this->nProt);
        //você pode padronizar os dados de retorno atraves da classe abaixo
        //de forma a facilitar a extração dos dados do XML
        //NOTA: mas lembre-se que esse XML muitas vezes será necessário,
        //      quando houver a necessidade de protocolos
        $stdCl = new Standardize($response);
        //nesse caso $std irá conter uma representação em stdClass do XML retornado
        $std = $stdCl->toStd();
        //nesse caso o $json irá conter uma representação em JSON do XML retornado
        $json = $stdCl->toJson();
        //verifique se o evento foi processado
        if ($std->cStat != 128) {
            throw new Exception($json);
        } else {
            $cStat = $std->retEvento->infEvento->cStat;
            if ($cStat == '101' || $cStat == '135' || $cStat == '155') {
                //SUCESSO PROTOCOLAR A SOLICITAÇÂO ANTES DE GUARDAR
                $xml = Complements::toAuthorize($tools->lastRequest, $response);
                //grave o XML protocolado e prossiga com outras tarefas de seu aplicativo
                file_put_contents($this->patch . DIRECTORY_SEPARATOR . $this->chave . '-nfe-cancel.xml',
                    $xml);
                return $std;
            } else {
                throw new Exception($json);
            }
        }
    }
}