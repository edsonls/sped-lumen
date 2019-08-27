<?php
/**
 * Created by PhpStorm.
 * User: DELL
 * Date: 22/08/2018
 * Time: 14:18
 */

namespace App\Http\Objects;


use App\Component\Utils;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;
use NFePHP\Ibpt\Ibpt;
use NFePHP\Ibpt\RestInterface;
use stdClass;

class TableIbpt extends Ibpt
{
    public function __construct(string $cnpj, string $token, array $proxy = [], RestInterface $rest = null)
    {
        parent::__construct($cnpj, $token, $proxy, $rest);
    }

    /**
     * @param string $uf
     * @param string $ncm
     * @param int    $extarif
     * @param string $descricao
     * @param string $unidadeMedida
     * @param number $valor
     * @param string $gtin
     * @param string $codigoInterno
     *
     * @return stdClass
     * @throws Exception
     */
    public function productTaxes(
        $uf = 'SE',
        $ncm,
        $extarif,
        $descricao,
        $unidadeMedida,
        $valor,
        $gtin,
        $codigoInterno = ''
    ) {
        $table = 'uf_' . preg_replace('/[^a-zA-Z_]*/', '', strtolower($uf));
        $ibpt = DB::table($table)
            ->select('*')
            ->where('codigo', '=', $ncm)
            ->first();
        $ibpt = json_decode(json_encode($ibpt), true);
        if (null !== $ibpt && $this->checkVigencia($ibpt)) {
            $ibpt = $this->formatObj($ibpt, $uf, $valor);
            return $ibpt;
        } else {
            $ibptRest = parent::productTaxes($uf,
                $ncm,
                $extarif,
                $descricao,
                $unidadeMedida,
                $valor,
                $gtin,
                $codigoInterno);
            if ($ibpt === null) {
                DB::table($table)
                    ->insert([
                        'codigo' => $ibptRest->Codigo,
                        'ex' => (string)$ibptRest->EX,
                        'tipo' => (string)$ibptRest->Tipo,
                        'descricao' => $ibptRest->Descricao,
                        'nacionalfederal' => $ibptRest->Nacional,
                        'importadosfederal' => $ibptRest->Importado,
                        'estadual' => $ibptRest->Estadual,
                        'municipal' => $ibptRest->Municipal,
                        'vigenciainicio' => $ibptRest->VigenciaInicio,
                        'vigenciafim' => $ibptRest->VigenciaFim,
                        'chave' => $ibptRest->Chave,
                        'versao' => $ibptRest->Versao,
                        'fonte' => $ibptRest->Fonte
                    ]);
            } else {
                DB::table($table)
                    ->where('codigo', '=', $ncm)
                    ->update([
                        'ex' => (string)$ibptRest->EX,
                        'tipo' => (string)$ibptRest->Tipo,
                        'descricao' => $ibptRest->Descricao,
                        'nacionalfederal' => $ibptRest->Nacional,
                        'importadosfederal' => $ibptRest->Importado,
                        'estadual' => $ibptRest->Estadual,
                        'municipal' => $ibptRest->Municipal,
                        'vigenciainicio' => $ibptRest->VigenciaInicio,
                        'vigenciafim' => $ibptRest->VigenciaFim,
                        'chave' => $ibptRest->Chave,
                        'versao' => $ibptRest->Versao,
                        'fonte' => $ibptRest->Fonte
                    ]);
            }
            return $ibptRest;
        }
    }

    private function checkVigencia(array $ibpt): bool
    {
        $now = new DateTime('now');
        $dateVigencia = $ibpt['vigenciafim'];
        $dateVigencia = explode('/', $dateVigencia);
        $dateVigencia = new DateTime($dateVigencia[2] . '-' . $dateVigencia[1] . '-' . $dateVigencia[0]);
        return $dateVigencia >= $now;
    }

    private function formatObj(array $ibpt, string $uf, float $valor): stdClass
    {
        $std = new stdClass();
        $std->Codigo = $ibpt['codigo'];
        $std->UF = $uf;
        $std->EX = $ibpt['ex'];
        $std->Descricao = $ibpt['descricao'];
        $std->Nacional = Utils::formatValue($ibpt['nacionalfederal']);
        $std->Estadual = Utils::formatValue($ibpt['estadual']);
        $std->Importado = Utils::formatValue($ibpt['importadosfederal']);
        $std->Municipal = Utils::formatValue($ibpt['municipal']);
        $std->Tipo = $ibpt['tipo'];
        $std->VigenciaInicio = $ibpt['vigenciainicio'];
        $std->VigenciaFim = $ibpt['vigenciafim'];
        $std->VigenciaFim = $ibpt['vigenciafim'];
        $std->Chave = $ibpt['chave'];
        $std->Versao = $ibpt['versao'];
        $std->Fonte = $ibpt['fonte'];
        $std->Valor = Utils::formatValue($valor);
        $std->ValorTributoNacional = Utils::formatValue(($valor / 100) * $std->Nacional);
        $std->ValorTributoEstadual = Utils::formatValue(($valor / 100) * $std->Estadual);
        $std->ValorTributoImportado = Utils::formatValue(($valor / 100) * $std->Importado);
        $std->ValorTributoMunicipal = Utils::formatValue(($valor / 100) * $std->Municipal);
        return $std;
    }
}