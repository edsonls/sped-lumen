<?php
/**
 * Created by PhpStorm.
 * User: DELL
 * Date: 09/08/2018
 * Time: 16:11
 */

namespace App\Http\Objects;

use Exception;

class Company
{
    private $xNome;
    private $xFant;
    private $IE;
    private $CNPJ;
    private $xLgr;
    private $nro;
    private $xCpl;
    private $xBairro;
    private $cMun;
    private $xMun;
    private $UF;
    private $cUF;
    private $CEP;
    private $cPais = '1058';
    private $xPais = 'BRASIL';
    private $fone;
    private $csc;
    private $id_token;
    private $certificate;
    private $nNF;
    private $serie;
    private $tpAmb = 2;
    public $db_name;


    /**
     * Company constructor.
     *
     * @param array $company
     *
     * @throws Exception
     */
    public function __construct(array $company)
    {
        $this->setCompany($company);
    }

    /**
     * @return Certificate
     */
    public function getCertificate(): Certificate
    {
        return $this->certificate;
    }


    /**
     * @return string
     */
    public function getXNome(): string
    {
        return $this->xNome;
    }

    /**
     * @return string
     */
    public function getXFant(): string
    {
        return $this->xFant;
    }

    /**
     * @return string
     */
    public function getIE(): string
    {
        return $this->IE;
    }

    /**
     * @return string
     */
    public function getCNPJ(): string
    {
        return $this->CNPJ;
    }

    /**
     * @return string
     */
    public function getXLgr(): string
    {
        return $this->xLgr;
    }

    /**
     * @return string
     */
    public function getNro(): string
    {
        return $this->nro;
    }

    /**
     * @return string
     */
    public function getXCpl(): string
    {
        return $this->xCpl;
    }

    /**
     * @return string
     */
    public function getXBairro(): string
    {
        return $this->xBairro;
    }

    /**
     * @return string
     */
    public function getCMun(): string
    {
        return $this->cMun;
    }

    /**
     * @return string
     */
    public function getXMun(): string
    {
        return $this->xMun;
    }

    /**
     * @return string
     */
    public function getUF(): string
    {
        return $this->UF;
    }

    /**
     * @return string
     */
    public function getCEP(): string
    {
        return $this->CEP;
    }

    /**
     * @return string
     */
    public function getCPais(): string
    {
        return $this->cPais;
    }

    /**
     * @return string
     */
    public function getXPais(): string
    {
        return $this->xPais;
    }

    /**
     * @return string
     */
    public function getFone(): string
    {
        return $this->fone;
    }

    /**
     * @param array $company
     *
     * @throws Exception
     */
    private function setCompany(array $company)
    {
        /* SETANDO TODOS OS DADOS NESCESSARIOS PARA EMISSAO DA NOTA*/
        $this->checkData($company);
        $this->xFant = trim($company['nomefantasia']);
        $this->xNome = trim($company['razao_social']);
        $this->IE = preg_replace("/[^0-9\s]/", "", trim($company['inscricaoestadual']));
        $this->CNPJ = preg_replace("/[^0-9\s]/", "", trim($company['cnpj']));
        $this->fone = trim($company['telefoneEmpresa']);
        $this->xLgr = trim($company['endereco']);
        $this->CEP = trim($company['cep']);
        $this->UF = trim($company['estado_uf']);
        $this->cUF = trim(self::getCodUfIbge($company['estado_uf']));
        $this->xMun = trim($company['cidade']);
        $this->cMun = trim($company['id_cidade']);
        $this->nro = trim($company['numero']);
        $this->xBairro = trim($company['bairro']);
        $this->xCpl = trim($company['complemento']);
        $this->csc = trim($company['csc']);
        $this->id_token = str_pad(trim($company['id_csc']), 6, "0", STR_PAD_LEFT);
        $this->nNF = trim($company['numerodanota']);
        $this->serie = trim($company['serie']);
        $this->tpAmb = intval($company['tpAmb']);
        $this->db_name = trim(base64_decode($company['certificado']));
        $this->certificate = new Certificate($company['certificado']);


    }

    public function getJsonConfig()
    {
        $config = [
            "atualizacao" => "2018-08-10 15:47:21",
            "tpAmb" => $this->tpAmb,//2-Homologação / 1 - Produção
            "razaosocial" => $this->xNome,
            "siglaUF" => $this->UF,
            "cnpj" => $this->CNPJ,
            "schemes" => "L_009_V4",
            "versao" => "4.00",
            //"tokenIBPT" => "AAAAAAA",
            "CSC" => $this->csc,
            "CSCid" => $this->id_token,
            "proxyConf" => [
                "proxyIp" => "",
                "proxyPort" => "",
                "proxyUser" => "",
                "proxyPass" => ""
            ]
        ];

        return json_encode($config);
    }

    /**
     * @return mixed
     */
    public function getCUF()
    {
        return $this->cUF;
    }

    /**
     * @return mixed
     */
    public function getCsc()
    {
        return $this->csc;
    }

    /**
     * @return mixed
     */
    public function getIdToken()
    {
        return $this->id_token;
    }

    /**
     * @return mixed
     */
    public function getNNF()
    {
        return $this->nNF;
    }

    /**
     * @return mixed
     */
    public function getSerie()
    {
        return $this->serie;
    }

    /**
     * @return int
     */
    public function getTpAmb(): int
    {
        return $this->tpAmb;
    }

    public function updateNumberNote()
    {
        //TODO
    }

    /**
     * @param array $company
     *
     * @return bool
     * @throws Exception
     */
    private function checkData(array &$company)
    {
        if (!array_key_exists('nomefantasia', $company) || empty($company['nomefantasia'])) {
            throw new Exception('Dado inexistente ou vazio: nomefantasia');
        } elseif (!array_key_exists('razao_social', $company) || empty($company['razao_social'])) {
            throw new Exception('Dado inexistente ou vazio: razao_social');
        } elseif (!array_key_exists('inscricaoestadual', $company) || empty($company['inscricaoestadual'])) {
            throw new Exception('Dado inexistente ou vazio: inscricaoestadual');
        } elseif (!array_key_exists('cnpj', $company) || empty($company['cnpj'])) {
            throw new Exception('Dado inexistente ou vazio: cnpj');
        } elseif (!array_key_exists('telefoneEmpresa', $company) || empty($company['telefoneEmpresa'])) {
            throw new Exception('Dado inexistente ou vazio: telefoneEmpresa');
        } elseif (!array_key_exists('endereco', $company) || empty($company['endereco'])) {
            throw new Exception('Dado inexistente ou vazio: endereco');
        } elseif (!array_key_exists('cep', $company) || empty($company['cep'])) {
            throw new Exception('Dado inexistente ou vazio: cep');
        } elseif (!array_key_exists('estado_uf', $company) || empty($company['estado_uf'])) {
            throw new Exception('Dado inexistente ou vazio: estado_uf');
        } elseif (!array_key_exists('cidade', $company) || empty($company['cidade'])) {
            throw new Exception('Dado inexistente ou vazio: cidade');
        } elseif (!array_key_exists('id_cidade', $company) || empty($company['id_cidade'])) {
            throw new Exception('Dado inexistente ou vazio: cod_municipio');
        } elseif (!array_key_exists('numero', $company) || empty($company['numero'])) {
            throw new Exception('Dado inexistente ou vazio: numero');
        } elseif (!array_key_exists('bairro', $company) || empty($company['bairro'])) {
            throw new Exception('Dado inexistente ou vazio: bairro');
        } elseif (!array_key_exists('complemento', $company) || empty($company['complemento'])) {
            $company['complemento'] = 'SEM COMPLEMENTO';
        } elseif (!array_key_exists('csc', $company) || empty($company['csc'])) {
            throw new Exception('Dado inexistente ou vazio: csc');
        } elseif (!array_key_exists('id_csc', $company) || empty($company['id_csc'])) {
            throw new Exception('Dado inexistente ou vazio: id_csc');
        } elseif (!array_key_exists('numerodanota', $company) || empty($company['numerodanota'])) {
            throw new Exception('Dado inexistente ou vazio: numerodanota');
        } elseif (!array_key_exists('serie', $company) || empty($company['serie'])) {
            throw new Exception('Dado inexistente ou vazio: seriedanota');
        } elseif (!array_key_exists('tpAmb', $company) || empty($company['tpAmb'])) {
            throw new Exception('Dado inexistente ou vazio: tpAmb');
        } elseif (!array_key_exists('certificado', $company) || empty($company['certificado'])) {
            throw new Exception('Dado inexistente ou vazio: certificado');
        } else {
            return true;
        }
    }

    /**
     * @param $uf
     *
     * @return int
     */
    public static function getCodUfIbge($uf): int
    {
        $ibg = [
            11 => 'RO',
            12 => 'AC',
            13 => 'AM',
            14 => 'RR',
            15 => 'PA',
            16 => 'AP',
            17 => 'TO',
            21 => 'MA',
            22 => 'PI',
            23 => 'CE',
            24 => 'RN',
            25 => 'PB',
            26 => 'PE',
            27 => 'AL',
            28 => 'SE',
            29 => 'BA',
            31 => 'MG',
            32 => 'ES',
            33 => 'RJ',
            35 => 'SP',
            41 => 'PR',
            42 => 'SC',
            43 => 'RS',
            50 => 'MS',
            51 => 'MT',
            52 => 'GO',
            53 => 'DF'
        ];
        foreach ($ibg as $key => $item) {
            if (strtoupper($uf) == $item) {
                return $key;
            }
        }
    }
}
