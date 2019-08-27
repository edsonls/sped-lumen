<?php
/**
 * Created by PhpStorm.
 * User: DELL
 * Date: 09/08/2018
 * Time: 15:54
 */

namespace App\Http\Objects;


use Exception;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Complements;
use NFePHP\NFe\Make;
use NFePHP\NFe\Tools;
use stdClass;

class NfeObj
{
    public const TRANSFER = 'TRANSFER';
    public const DANFE = 'DANFE';
    public const DEVOLUCAO = 'DEVOLUCAO';
    private $arrayNote = [];
    private $patchAssinado;
    private $model = 65;
    private $patchProtocolos;
    private $patchRecibos;
    private $patchTemp;
    private $patchProtocolados;
    private $chave;
    private $error = '';
    private $objRetorno = [];
    private $company;
    private $arrayibpt;
    private $totalProducts = 0;
    private $addressClient = false;
    private $caminhoXml;
    /**
     * @var string
     */
    public $type_nfe = 'DANFE';


    /**
     * NfeObj constructor.
     *
     * @param $model
     * @param $arrayNote
     *
     * @throws Exception
     */
    public function __construct($model, $arrayNote)
    {
        $this->model = $model;
        $this->arrayNote = $arrayNote;
        $this->arrayNote = $arrayNote;
        /** var $company  pego o primeiro produto onde vai ter os dados do clientes **/
        $this->company = new Company($arrayNote['company']);
        $this->caminhoXml = storage_path('xml_fiscais') . DIRECTORY_SEPARATOR .
            $this->company->db_name . DIRECTORY_SEPARATOR .
            $model . DIRECTORY_SEPARATOR;
        $this->patchAssinado = $this->caminhoXml . 'assinados' .
            DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('m');
        $this->patchProtocolos = $this->caminhoXml . 'protocolos' .
            DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('m');
        $this->patchProtocolados = $this->caminhoXml . 'protocolados' .
            DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('m');
        $this->patchRecibos = $this->caminhoXml . 'recidos' .
            DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('m');
        $this->patchTemp = $this->caminhoXml . 'temp' .
            DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('m');
        $this->checkDir();
    }

    private function checkDir()
    {
        if (!file_exists($this->patchAssinado)) {
            mkdir($this->patchAssinado, 0755, true);
        }
        if (!file_exists($this->patchProtocolos)) {
            mkdir($this->patchProtocolos, 0755, true);
        }
        if (!file_exists($this->patchProtocolados)) {
            mkdir($this->patchProtocolados, 0755, true);
        }
        if (!file_exists($this->patchRecibos)) {
            mkdir($this->patchRecibos, 0755, true);
        }
        if (!file_exists($this->patchTemp)) {
            mkdir($this->patchTemp, 0755, true);
        }
    }

    /**
     * @return string
     */
    private function getNatOP()
    {
        switch ($this->type_nfe) {
            case self::DANFE:
                return 'VENDA';
            case self::TRANSFER:
                return 'Transf. merc. adq.';
            default:
                return 'VENDA';
        }
    }

    /**
     * @return array|string
     * @throws Exception
     */
    public function emitNote(): array
    {
        $xml = ($this->model == 65) ? $this->getXml65() : $this->getXml55();
        if (empty($this->error)) {
            if ($this->transmit($xml)) {
                $aux = $this->objRetorno['xml_note'];
                unset($this->objRetorno['xml_note']);
                $this->objRetorno['model'] = $this->model;
                $this->objRetorno['m'] = date('m');
                $this->objRetorno['y'] = date('Y');
                $this->objRetorno['folder'] = $this->company->db_name;
                $this->objRetorno['hash'] = base64_encode(json_encode($this->objRetorno));
                unset($this->objRetorno['model'], $this->objRetorno['m'], $this->objRetorno['y'], $this->objRetorno['folder']);
                $this->objRetorno['xml_note'] = $aux;
                return $this->objRetorno;
            } else {
                throw new Exception($this->error);
            }
        } else {
            throw new Exception($this->error);
        }
    }

    private function transmit($xmlValido)
    {
        if ($xmlValido === false) {
            return false;
        } else {
            try {
                $tools = new Tools($this->company->getJsonConfig(),
                    $this->company->getCertificate()->getCertificate());
                $tools->model($this->model);
                file_put_contents($this->patchTemp . DIRECTORY_SEPARATOR . $this->chave . '-nfe.xml', $xmlValido);
                $xmlAssinado = $tools->signNFe($xmlValido);
                file_put_contents($this->patchAssinado . DIRECTORY_SEPARATOR . $this->chave . '-nfe.xml', $xmlAssinado);
                $xmlRecibo = $tools->sefazEnviaLote([$xmlAssinado], 1);
                $this->saveObjNfe(['chave' => $this->chave, 'numlote' => $this->company->getNNF()]);
                file_put_contents($this->patchRecibos . DIRECTORY_SEPARATOR . $this->chave . '-nfe.xml', $xmlRecibo);
                $st = new Standardize();
                $std = $st->toStd($xmlRecibo);
                if ($std->cStat != 103) {
                    //erro registrar e voltar
                    $this->setError($std->xMotivo, $std->cStat);
                    return false;
                }
                $numeroRecibo = $std->infRec->nRec;
                $this->saveObjNfe(['chave' => $this->chave, 'numrec' => $numeroRecibo]);
                $xmlProtocolo = $tools->sefazConsultaRecibo($numeroRecibo);
                file_put_contents($this->patchProtocolos . DIRECTORY_SEPARATOR . $this->chave . '-nfe.xml',
                    $xmlProtocolo);
                $stdR = $st->toStd($xmlProtocolo);
                if (!empty($stdR->protNFe->infProt->nProt)) {
                    $this->saveObjNfe(['chave' => $this->chave, 'numprot' => $stdR->protNFe->infProt->nProt]);
                }
                if ($stdR->protNFe->infProt->cStat != 100) {
                    $this->saveObjNfe(['chave' => $this->chave, 'cstat' => $stdR->protNFe->infProt->cStat]);
                    $this->setError("Protocolo:{$stdR->nRec}, Codigo Rejeição:{$stdR->protNFe->infProt->cStat} , Motivo:{$stdR->protNFe->infProt->xMotivo}",
                        $stdR->protNFe->infProt->cStat);
                    return false;
                }
                $xmlTranmitido = Complements::toAuthorize($xmlAssinado, $xmlProtocolo);
                $this->saveObjNfe([
                    'nnota' => $this->company->getNNF(),
                    'chave' => $this->chave,
                    'cstat' => $stdR->protNFe->infProt->cStat,
                    'xml_note' => $xmlTranmitido,
                ]);
                file_put_contents($this->patchProtocolados . DIRECTORY_SEPARATOR . $this->chave . '-nfe.xml',
                    $xmlTranmitido);
                $this->company->updateNumberNote();
                return true;
            } catch (Exception $e) {
                //aqui você trata possiveis exceptions
                $this->setError($e->getMessage());
                return false;
            }
        }
    }

    private function getXml65()
    {
        $nfe = new Make();
        $elem = $nfe->taginfNFe($this->getInfNfe());
        $elem = $nfe->tagide($this->getIde());
        $elem = $nfe->tagemit($this->getEmit());
        $elem = $nfe->tagenderEmit($this->getEndEmit());
        if (!empty($this->arrayNote['client'])) {
            $elem = $nfe->tagdest($this->getDest());
            if ($this->addressClient) {
                $elem = $nfe->tagenderDest($this->getenderDest());
            }
        }
        $this->getProd($nfe);
        $elem = $nfe->tagICMSTot($this->getIcmsTotal());
        $elem = $nfe->tagpag($this->getTroco());
        $this->getFormaPagamento($nfe);
        $std = new stdClass();
        $std->modFrete = 9;
        $elem = $nfe->tagtransp($std);
        $elem = $nfe->taginfAdic($this->tagIbptTotal(array_key_exists('taginfAdic',
            $this->arrayNote) ? $this->arrayNote['taginfAdic'] : ''));
        if (empty($nfe->dom->errors)) {
            $xml = $nfe->getXML();
            $this->chave = $nfe->getChave();
            return $xml;
        } else {
            $this->setError($nfe->dom->errors);
        }
    }

    private function getInfNfe()
    {
        $std = new stdClass();
        $std->versao = '4.00';
        $std->Id = null;
        $std->pk_nItem = null;
        return $std;
    }


    private function getIde(): stdClass
    {
        $std = new stdClass();
        $std->cUF = $this->company->getCUF();
        $std->cNF = null;
        $std->natOp = $this->getNatOP();
        $std->mod = $this->model;
        $std->serie = $this->company->getSerie();
        $std->nNF = $this->company->getNNF();
        $std->dhEmi = date('c');
        $std->dhSaiEnt = null;
        $std->tpNF = 1;
        $std->idDest = $this->getIdDest();
        $std->cMunFG = $this->company->getCMun();
        $std->tpImp = ($this->model == 65) ? 4 : 1;
        $std->tpEmis = 1;
        $std->cDV = null;
        $std->tpAmb = $this->company->getTpAmb();
        $std->finNFe = $this->getFinNFE();
        $std->indFinal = 1;
        $std->indPres = 1;
        $std->procEmi = 0;
        $std->verProc = '2.1.1';
        $std->dhCont = null;
        $std->xJust = null;
        return $std;
    }

    private function getEmit(): stdClass
    {
        $std = new stdClass();
        $std->xNome = $this->company->getXNome();
        $std->xFant = $this->company->getXFant();
        $std->IE = $this->company->getIE();
        $std->CRT = 1;
        $std->CNPJ = $this->company->getCNPJ();
        return $std;
    }

    /**
     * @return stdClass
     */
    private function getEndEmit(): stdClass
    {
        $std = new stdClass();
        $std->xLgr = $this->company->getXLgr();
        $std->nro = $this->company->getXLgr();
        $std->xCpl = $this->company->getXCpl();
        $std->xBairro = $this->company->getXBairro();
        $std->cMun = $this->company->getCMun();
        $std->xMun = $this->company->getXMun();
        $std->UF = $this->company->getUF();
        $std->CEP = $this->company->getCEP();
        $std->cPais = $this->company->getCPais();
        $std->xPais = $this->company->getXPais();
        $std->fone = $this->company->getFone();
        return $std;
    }

    private function getXml55()
    {
        $nfe = new Make();
        $elem = $nfe->taginfNFe($this->getInfNfe());
        $elem = $nfe->tagide($this->getIde());
        $elem = $nfe->tagemit($this->getEmit());
        $elem = $nfe->tagenderEmit($this->getEndEmit());
        if (!empty($this->arrayNote['client'])) {
            $elem = $nfe->tagdest($this->getDest());
            if ($this->addressClient) {
                $elem = $nfe->tagenderDest($this->getenderDest());
            }
        }
        $this->getProd($nfe);
        $elem = $nfe->tagICMSTot($this->getIcmsTotal());
        $elem = $nfe->tagpag($this->getTroco());
        $this->getFormaPagamento($nfe);
        $std = new stdClass();
        if (empty($this->arrayNote['frete'])) {
            $std->modFrete = 9;
        } else {
            //todo falta fazer o com frete
        }
        $elem = $nfe->tagtransp($std);
        $elem = $nfe->taginfAdic($this->tagIbptTotal(array_key_exists('taginfAdic',
            $this->arrayNote) ? $this->arrayNote['taginfAdic'] : ''));
        if (empty($nfe->dom->errors)) {
            $xml = $nfe->getXML();
            $this->chave = $nfe->getChave();
            return $xml;
        } else {
            $this->setError($nfe->dom->errors);
        }
    }

    /**
     * @return stdClass
     */
    private function getDest(): stdClass
    {
        $std = new stdClass();
        if ($this->model == 65) {
            $this->addressClient = false;// por enquanto nfce não vai ter endereço
            if (array_key_exists('cpf', $this->arrayNote['client'])) {
                $std->CPF = $this->arrayNote['client']['cpf'];
            } else {
                $std->CNPJ = $this->arrayNote['client']['cnpj'];
            }
        } else {
            $this->addressClient = true;
            $std->email = trim($this->arrayNote['client']['nome'] ?? 'CLIENTE NOME');
            $std->email = $this->arrayNote['client']['email'] ?? 'email@email.com';
            if (!empty($this->arrayNote['client']['inscricaoestadual'])) {
                $std->indIEDest = 1;
                $std->IE = trim($this->arrayNote['client']['inscricaoestadual']);
            } else {
                $std->indIEDest = 9;
            }
            if (array_key_exists('cpf', $this->arrayNote['client'])) {
                $std->CPF = $this->arrayNote['client']['cpf'];
            } elseif (array_key_exists('cnpj', $this->arrayNote['client'])) {
                $std->CNPJ = $this->arrayNote['client']['cnpj'];
            } else {
                $std->idEstrangeiro = $this->arrayNote['client']['idEstrangeiro'];
            }
        }
        return $std;
    }

    /**
     * @return stdClass
     */
    private function getenderDest(): stdClass
    {
        $std = new stdClass();
        $std->xLgr = trim($this->arrayNote['client']['endereco'] ?? 'CLIENTE SEM ENDERECO');
        $std->nro = trim($this->arrayNote['client']['numero'] ?? 'CLIENTE SEM NRO');
        $std->xCpl = trim($this->arrayNote['client']['complemento'] ?? 'SEM COMPLEMENTO');
        $std->xBairro = trim($this->arrayNote['client']['bairro'] ?? 'SEM BAIRRO');
        $std->cMun = trim($this->arrayNote['client']['id_cidade'] ?? $this->company->getCMun());
        $std->xMun = trim($this->arrayNote['client']['cidade'] ?? $this->company->getCMun());
        $std->UF = trim($this->arrayNote['client']['estado_uf'] ?? $this->company->getUF());
        $std->CEP = trim($this->arrayNote['client']['cep'] ?? $this->company->getCEP());
        $std->cPais = $this->company->getCPais();
        $std->xPais = $this->company->getXPais();
        $std->fone = trim($this->arrayNote['client']['telefone'] ?? $this->company->getFone());
        return $std;
    }

    /**
     * @param Make $nfe
     *
     * @return void
     */
    private function getProd(Make &$nfe): void
    {
        $product = new Product($this->company->getUF(), $this->arrayNote['company']['codigo_numerico']);
        $qtdProd = count($this->arrayNote['products']);
        foreach ($this->arrayNote['products'] as $key => $prod) {
            $product->addProductInNfe($nfe, $prod, ($key + 1));
            if (empty($this->arrayibpt)) {
                $this->arrayibpt = $product->getIbptArray();
            } else {
                $this->arrayibpt =
                    [
                        'Nacional' => (($product->getIbptArray()['Nacional'] / $qtdProd) + $this->arrayibpt['Nacional']),
                        'Estadual' => (($product->getIbptArray()['Estadual'] / $qtdProd) + $this->arrayibpt['Estadual']),
                        'Importado' => (($product->getIbptArray()['Importado'] / $qtdProd) + $this->arrayibpt['Importado']),
                        'Municipal' => (($product->getIbptArray()['Municipal'] / $qtdProd) + $this->arrayibpt['Municipal']),
                        'ValorTributoNacional' => ($product->getIbptArray()['ValorTributoNacional'] + $this->arrayibpt['ValorTributoNacional']),
                        'ValorTributoEstadual' => ($product->getIbptArray()['ValorTributoEstadual'] + $this->arrayibpt['ValorTributoEstadual']),
                        'ValorTributoImportado' => ($product->getIbptArray()['ValorTributoImportado'] + $this->arrayibpt['ValorTributoImportado']),
                        'ValorTributoMunicipal' => ($product->getIbptArray()['ValorTributoMunicipal'] + $this->arrayibpt['ValorTributoMunicipal'])
                    ];
            }
            $this->totalProducts += $prod['valrTotal'];
        }
    }

    /**
     * @return stdClass
     */
    private function getIcmsTotal(): stdClass
    {
        /**
         * Node dos totais referentes ao ICMS
         * NOTA: Esta tag não necessita que sejam passados valores, pois a classe irá calcular esses totais e irá usar
         * essa totalização para complementar e gerar esse node, caso nenhum valor seja passado como parâmetro.
         * */
        $std = new stdClass();
        $std->vBC = null;
        $std->vICMS = null;
        $std->vICMSDeson = null;
        $std->vFCP = null; //incluso no layout 4.00
        $std->vBCST = null;
        $std->vST = null;
        $std->vFCPST = null; //incluso no layout 4.00
        $std->vFCPSTRet = null; //incluso no layout 4.00
        $std->vProd = null;
        $std->vFrete = null;
        $std->vSeg = null;
        $std->vDesc = null;
        $std->vII = null;
        $std->vIPI = null;
        $std->vIPIDevol = null; //incluso no layout 4.00
        $std->vPIS = null;
        $std->vCOFINS = null;
        $std->vOutro = null;
        $std->vNF = null;
        $std->vTotTrib = null;
        return $std;
    }

    private function getTroco(): stdClass
    {
        /**
         * Node referente as formas de pagamento OBRIGATÓRIO para NFCe a partir do layout 3.10 e
         * também obrigatório para NFe (modelo 55) a partir do layout 4.00
         */
        $std = new stdClass();
        if ($this->model == 55) {
            $std->vTroco = null;
        } else {
            $vPag = 0;
            foreach ($this->arrayNote['tagPag'] as $method) {
                $vPag += $method['vPag'];
            }
            $std->vTroco = ($vPag - $this->totalProducts);
        }
        return $std;
    }

    /**
     * @param Make $nfe
     *
     * @return void
     */
    private function getFormaPagamento(Make &$nfe): void
    {
        if ($this->type_nfe == self::TRANSFER) {
            $std = new stdClass();
            $std->tPag = 90;
            $std->vPag = 0;
            $elem = $nfe->tagdetPag($std);
        } else {
            foreach ($this->arrayNote['tagPag'] as $method) {
                $std = new stdClass();
                $std->tPag = $method['tPag'];
                $std->vPag = $method['vPag'];
                $elem = $nfe->tagdetPag($std);
            }
        }
    }

    /**
     * @param string $tagfAdic
     *
     * @return stdClass
     */
    private function tagIbptTotal(string $tagfAdic = null): stdClass
    {
        $std = new stdClass();
        $std->infCpl = "{$tagfAdic}. Valor com base na tabela IBPT. Importado: {$this->arrayibpt['Importado']}%, R$:{$this->arrayibpt['ValorTributoImportado']}, Nacional: {$this->arrayibpt['Nacional']}%, R$:{$this->arrayibpt['ValorTributoNacional']}, Estadual: {$this->arrayibpt['Estadual']}%, R$:{$this->arrayibpt['ValorTributoEstadual']}, Municipal: {$this->arrayibpt['Municipal']}%, R$:{$this->arrayibpt['ValorTributoMunicipal']}";
        $std->infCpl = trim($std->infCpl);
        return $std;
    }

    private function saveObjNfe($array): void
    {
        $this->objRetorno = array_merge($this->objRetorno, $array);
    }

    private function setError($string, $codError = '000'): void
    {
        $this->error = json_encode(
            [
                'error' => $string,
                'codError' => $codError
            ]);
    }

    private function getIdDest()
    {
        if ($this->model == 65) {
            return 1;
        } else {
            if (intval($this->company->getCUF()) === Company::getCodUfIbge($this->arrayNote['client']['estado_uf'])) {
                return 1;
            } else {
                return 2;
            }
        }
    }

    private function getFinNFE()
    {
        return ($this->type_nfe == self::DEVOLUCAO) ? 4 : 1;
    }
}