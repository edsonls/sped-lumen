<?php
/**
 * Created by PhpStorm.
 * User: DELL
 * Date: 09/08/2018
 * Time: 16:12
 */

namespace App\Http\Objects;


use Exception;
use NFePHP\Gtin\Gtin;
use NFePHP\NFe\Make;
use stdClass;


class Product
{
    private $item;
    private $uf;
    private $adc_prod;
    private $product_obj;
    private $token_ibpt = '';// token da empresa resgistrada
    private $cnpj_ibpt = '';// CNPJ da empresa resgistrada
    private $ibpt_array;
    private $xPed;

    /**
     * Product constructor.
     *
     * @param string $uf
     * @param int    $xPed
     */
    public function __construct(string $uf, int $xPed)
    {
        $this->uf = $uf;
        $this->xPed = $xPed;
    }

    public function addProductInNfe(Make &$nfe, array $arrayProduct, int $item)
    {
        $this->setProduct($arrayProduct);
        $this->item = $item;
        try {
            $this->nodeNfe($nfe);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    private function nodeNfe(Make &$nfe): void
    {
        $std = new stdClass();
        $quantidade = number_format($this->product_obj['quant'], 4, ".", "");
        $std->item = $this->item; //item da NFe
        $std->cProd = $this->product_obj['id'];
        $std->cEAN = $this->product_obj['gtin'];
        $std->cEANTrib = $std->cEAN;
        $std->xProd = $this->product_obj['titulo'];
        $std->NCM = str_pad($this->product_obj['ncm'], 8, "0", STR_PAD_LEFT);

        // $std->cBenef; //incluido no layout 4.00 Código de Benefício Fiscal na UF
        //$std->EXTIPI; IPI DE EXPORTAÇÃO
        $std->CFOP = $this->product_obj['cfop'];
        $std->uCom = $this->product_obj['unidade'];
        $std->uTrib = $std->uCom;
        $std->qCom = $quantidade;
        $std->qTrib = $quantidade;
        $std->vUnCom = number_format(($this->product_obj['valrUnid']), 3, ".", "");
        $std->vUnTrib = $std->vUnCom;
        $std->vProd = number_format($this->product_obj['valrTotal'], 2, ".", "");
        //$std->vFrete; FRETE
        // $std->vSeg;SEGURO
        if ($this->product_obj['valrDescontoItem'] > 0) {
            $std->vDesc = number_format($this->product_obj['valrDescontoItem'], 2, ".", "");
        }
        //$std->vOutro;
        $std->indTot = 1;
        $std->xPed = $this->xPed;
        $std->nItemPed = ($this->item - 1);
        //  $std->nFCI; FCI (Ficha de conteúdo de importação)
        $elem = $nfe->tagprod($std);
        if (!empty($this->adc_prod)) {
            $txtAdc = '';
            foreach ($this->adc_prod as $adc) {
                $txtAdc .= "Nome: {$adc['nome']}, Valor: R$" . number_format($adc['valor'] / count($this->adc_prod), 2,
                        ',', '') . " \n";
            }
            $std = new stdClass();
            $std->item = $this->item; //item da NFe
            $std->infAdProd = trim($txtAdc);
            $elem = $nfe->taginfAdProd($std);
        }
        if (intval($this->product_obj['cest']) > 0) {
            $std = new stdClass();
            $std->item = $this->item; //item da NFe
            $std->CEST = str_pad($this->product_obj['cest'], 7, "0", STR_PAD_LEFT);
            $elem = $nfe->tagCEST($std);
        }

        /*Node inicial dos Tributos incidentes no Produto ou Serviço do item da NFe*/
        $std = new stdClass();
        $std->item = $this->item; //item da NFe
        $std->vTotTrib = number_format(
            (
                $this->ibpt_array['ValorTributoNacional']
                + $this->ibpt_array['ValorTributoEstadual']
                + $this->ibpt_array['ValorTributoImportado']
                + $this->ibpt_array['ValorTributoMunicipal']
            ),
            2,
            ".",
            "");
        $elem = $nfe->tagimposto($std);

        /*Node inicial dos Tributos incidentes no Produto ou Serviço do item da NFe*/
        if ($this->product_obj['tagICMS']['cst'] >= 100) {
            $this->setIcmsSN($nfe);
        } else {
            //todo
        }
        $this->setPis($nfe, $quantidade);
        $this->setCofins($nfe, $quantidade);
    }

    private function setProduct(array $arrayProduct): void
    {

        $this->product_obj = $arrayProduct;
        $this->product_obj['valor'] = $arrayProduct['valrUnid'];
        $this->product_obj['desc'] = $arrayProduct['valrDescontoItem'];
        $this->product_obj['qtd'] = $arrayProduct['quant'];
        $this->validGtin();
        // $this->setAdc($arrayProduct['adc_prod']);
        $this->setIbpt();
    }

    private function setAdc($adc_prod): void
    {
        //todo
        $db = new DataBase();
        $in = implode(',', array_fill(0, count($adc_prod), '?'));
        $q = "SELECT * FROM adicionais WHERE id IN ( $in )";
        $conditions = [];
        foreach ($adc_prod as $key => $adc) {
            $conditions[$key + 1] = $adc;
        }
        $this->adc_prod = $db->select($q, $conditions, true);
    }

    /**
     * @param Make $nfe
     */
    private function setIcmsSN(Make &$nfe): void
    {
        $std = new stdClass();
        $std->item = $this->item; //item da NFe
        switch ($this->product_obj['tagICMS']['cst']) {
            case '101':
                $std->orig = $this->product_obj['tagICMS']['orig'];
                $std->CSOSN = $this->product_obj['tagICMS']['cst'];
                $std->pCredSN = number_format($this->product_obj['tagICMS']['pCredSN'], 2, ".", "");
                $std->vCredICMSSN = number_format(($this->product_obj['valor'] / 100) * $this->product_obj['tagICMS']['pCredSN'],
                    2, ".", "");
                break;
            case '102':
            case '103':
            case '300':
            case '400':
                $std->orig = $this->product_obj['tagICMS']['orig'];
                $std->CSOSN = $this->product_obj['tagICMS']['cst'];
                break;
            case '201':
                $std->orig = $this->product_obj['tagICMS']['orig'];
                $std->CSOSN = $this->product_obj['tagICMS']['cst'];
                $std->modBCST = $this->product_obj['tagICMS']['modBCST'];
                $std->pMVAST = number_format($this->product_obj['tagICMS']['pMVAST'], 2, ".", "");
                $std->pRedBCST = number_format($this->product_obj['tagICMS']['pRedBCST'], 2, ".", "");
                $std->vBCST = number_format(($this->product_obj['valor'] / 100) * $this->product_obj['tagICMS']['pRedBCST'],
                    2,
                    ".", "");
                $std->pICMSST = number_format($this->product_obj['tagICMS']['pICMSST'], 2, ".", "");
                $std->vICMSST = number_format(($this->product_obj['valor'] / 100) * $this->product_obj['tagICMS']['pICMSST'],
                    2,
                    ".", "");
                $std->pCredSN = number_format($this->product_obj['tagICMS']['pCredSN'], 2, ".", "");
                $std->vCredICMSSN = number_format(($this->product_obj['tagICMS']['vProd'] / 100) * $this->product_obj['tagICMS']['pCredSN'],
                    2, ".", "");
                break;
            case '202':
            case '203':
                $std->orig = $this->product_obj['tagICMS']['orig'];
                $std->CSOSN = $this->product_obj['tagICMS']['cst'];
                $std->modBCST = $this->product_obj['tagICMS']['modBCST'];
                $std->pMVAST = number_format($this->product_obj['tagICMS']['pMVAST'], 2, ".", "");
                $std->pRedBCST = number_format($this->product_obj['tagICMS']['pRedBCST'], 2, ".", "");
                $std->vBCST = number_format(($this->product_obj['valor'] / 100) * $this->product_obj['tagICMS']['pRedBCST'],
                    2,
                    ".", "");
                $std->pICMSST = number_format($this->product_obj['tagICMS']['pICMSST'], 2, ".", "");
                $std->vICMSST = number_format(($this->product_obj['valor'] / 100) * $this->product_obj['tagICMS']['pICMSST'],
                    2,
                    ".", "");
                break;
            case '500':
                $std->orig = $this->product_obj['tagICMS']['orig'];
                $std->CSOSN = $this->product_obj['tagICMS']['cst'];
                $std->vBCSTRet = 0;
                $std->vICMSSTRet = 0;
                break;
            case '900':
                $std->orig = $this->product_obj['tagICMS']['orig'];
                $std->CSOSN = $this->product_obj['tagICMS']['cst'];
                $std->modBCST = (empty($this->product_obj['tagICMS']['modBC'])) ? 0 : $this->product_obj['tagICMS']['modBCST'];
                $std->vBC = number_format($this->product_obj['valor'], 2, ".", "");
                $std->pRedBC = number_format($this->product_obj['tagICMS']['pRedBC'], 2, ".", "");
                $std->pICMS = number_format($this->product_obj['tagICMS']['pICMS'], 2, ".", "");
                $std->vICMS = number_format(($this->product_obj['tagICMS']['vProd'] / 100) * $this->product_obj['tagICMS']['pICMS'],
                    2, ".",
                    "");
                $std->pCredSN = number_format($this->product_obj['tagICMS']['pCredSN'], 2, ".", "");
                $std->vCredICMSSN = number_format(($this->product_obj['valor'] / 100) * $this->product_obj['tagICMS']['pCredSN']);
                break;
        }
        $elem = $nfe->tagICMSSN($std);
    }

    /**
     * @param Make  $nfe
     * @param float $quantity
     */
    private function setPis(Make &$nfe, float $quantity = 1): void
    {
        $std = new stdClass();
        $std->item = $this->item; //item da NFe
        $std->CST = sprintf("%02d", $this->product_obj['tagPIS']['cst']);
        switch ($std->CST) {
            case '01':
            case '02':
                $std->vBC = number_format($this->product_obj['valor'], 2, ".", "");
                $std->pPIS = number_format($this->product_obj['tagPIS']['pPIS'], 2, ".", "");
                $std->vPIS = number_format(($this->product_obj['valor'] / 100) * $this->product_obj['tagPIS']['pPIS'],
                    2, ".",
                    "");
                break;
            case '03':
                $std->qBCProd = $quantity;
                $std->vAliqProd = number_format((($this->product_obj['valor']) / 100) * $this->product_obj['tagPIS']['pPIS'],
                    2,
                    '.', '');
                $std->vPIS = number_format(($this->product_obj['valor'] / 100) * $this->product_obj['tagPIS']['pPIS'],
                    2, ".",
                    "");
                break;
            case '04':
            case '05':
            case '06':
            case '07':
            case '08':
            case '09':
                break;// se chegar aqui ele sai
            case '49':
            case '50':
            case '51':
            case '52':
            case '53':
            case '54':
            case '55':
            case '56':
            case '60':
            case '61':
            case '62':
            case '63':
            case '64':
            case '65':
            case '66':
            case '67':
            case '70':
            case '71':
            case '72':
            case '73':
            case '74':
            case '75':
            case '98':
            case '99':
                $std->vBC = number_format($this->product_obj['valor'], 2, ".", "");
                $std->pPIS = number_format($this->product_obj['tagPIS']['pPIS'], 2, ".", "");
                $std->vPIS = number_format(($this->product_obj['valor'] / 100) * $this->product_obj['tagPIS']['pPIS'],
                    2, ".",
                    "");
                break;
        }
        $elem = $nfe->tagPIS($std);
    }

    /**
     * @param Make  $nfe
     * @param float $quantity
     */
    private function setCofins(Make &$nfe, float $quantity = 1): void
    {
        $std = new stdClass();
        $std->item = $this->item; //item da NFe
        $std->CST = sprintf("%02d", $this->product_obj['tagCOFINS']['cst']);
        switch ($std->CST) {
            case '01' :
            case '02' :
                $std->vBC = number_format($this->product_obj['valor'], 2, ".", "");
                $std->pCOFINS = number_format($this->product_obj['tagCOFINS']['pCOFINS'], 2, ".", "");
                $std->vCOFINS = number_format(($this->product_obj['valor'] / 100) * $this->product_obj['tagCOFINS']['pCOFINS'],
                    2,
                    ".", "");
                break;
            case '03' :
                $std->qBCProd = number_format($quantity, 4, ".", "");
                $std->vAliqProd = number_format((($this->product_obj['valor']) / 100) * $this->product_obj['tagCOFINS']['pCOFINS'],
                    4, ".", "");
                $std->vCOFINS = number_format(($this->product_obj['valor'] / 100) * $this->product_obj['tagCOFINS']['pCOFINS'],
                    2,
                    ".", "");
                break;
            case '04' :
            case '06' :
            case '07' :
            case '08' :
            case '09' :
                break;// se chegar aqui ele sai
            case '99' :
                $std->vBC = number_format($this->product_obj['valor'], 2, ".", "");
                $std->pCOFINS = number_format($this->product_obj['tagCOFINS']['pCOFINS'], 2, ".", "");
                $std->vCOFINS = number_format(($this->product_obj['valor'] / 100) * $this->product_obj['tagCOFINS']['pCOFINS'],
                    2,
                    ".", "");
                break;
        }
        $elem = $nfe->tagCOFINS($std);
    }

    private function validGtin(): void
    {
        if (!array_key_exists('gtin', $this->product_obj)) {
            $this->product_obj['gtin'] = 'SEM GTIN';
        } else {
            try {
                $gtin = new Gtin($this->product_obj['gtin']);
                if (!$gtin->isValid()) {
                    $this->product_obj['gtin'] = 'SEM GTIN';
                }
            } catch (Exception $e) {
                $this->product_obj['gtin'] = 'SEM GTIN';
                //echo $e->getMessage();
            }
        }
    }

    private function setIbpt(): void
    {
        $extarif = 0; //OBRIGATÓRIO indique o numero da exceção tarifaria, se existir ou deixe como zero
        $codigoInterno = ''; //(OPCIONAL) indique o codigo interno do produto
        try {
            $ibpt = new TableIbpt($this->cnpj_ibpt, $this->token_ibpt);
            $impostos = $ibpt->productTaxes(
                $this->uf,
                $this->product_obj['ncm'],
                $extarif,
                $this->product_obj['titulo'],
                $this->product_obj['unidade'],
                ($this->product_obj['valrUnid'] - $this->product_obj['valrDescontoItem']),
                $this->product_obj['gtin'],
                $codigoInterno
            );
            $this->ibpt_array =
                [
                    'Nacional' => $impostos->Nacional,
                    'Estadual' => $impostos->Estadual,
                    'Importado' => $impostos->Importado,
                    'Municipal' => $impostos->Municipal,
                    'ValorTributoNacional' => $impostos->ValorTributoNacional,
                    'ValorTributoEstadual' => $impostos->ValorTributoEstadual,
                    'ValorTributoImportado' => $impostos->ValorTributoImportado,
                    'ValorTributoMunicipal' => $impostos->ValorTributoMunicipal,
                ];
        } catch (Exception $e) {
            dd($e->getMessage());
        }
    }

    /**
     * @return array
     */
    public function getIbptArray(): array
    {
        return $this->ibpt_array;
    }
}
