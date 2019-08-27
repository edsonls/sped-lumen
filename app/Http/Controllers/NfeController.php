<?php

namespace App\Http\Controllers;

use App\Http\Objects\NfeObj;
use App\Objects\NfeCancel;
use App\Objects\NfePrint;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NfeController extends Controller
{

    /**
     * The request instance.
     *
     * @var Request
     */
    private $request;

    /**
     * @param Request $request
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param int $recursive
     *
     * @return JsonResponse
     */
    public function nfce($recursive = 0): JsonResponse
    {
        $array['products'] = $this->request->get('produtos');
        $array['tagPag'] = $this->request->get('tagPag');
        $array['company'] = $this->request->get('empresa');
        $array['client'] = $this->request->get('cliente');
        $array['taginfAdic'] = $this->request->get('obs');
        if ($recursive > 0) {
            $array['company']['numerodanota'] = $array['company']['numerodanota'] + $recursive;
        }
        try {
            return response()->json($this->sendNfce($array));
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'codError') !== false) {
                $r = json_decode($e->getMessage());
                if ($r->codError == 539 && $recursive <= 10) {
                    $recursive++;
                    return $this->nfce($recursive);
                }
            }
            return response()->json(
                [
                    'msg' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ], 400);

        }
    }

    /**
     * @param $array
     *
     * @return string
     * @throws Exception
     */
    private function sendNfce($array)
    {
        $objNfe = new NfeObj(65, $array);
        return $objNfe->emitNote();
    }

    public function nfeTransfer($recursive = 0)
    {
        $array['products'] = $this->request->get('produtos');
        $array['tagPag'] = $this->request->get('tagPag');
        $array['company'] = $this->request->get('empresa');
        $array['client'] = $this->request->get('cliente');
        $array['taginfAdic'] = $this->request->get('obs');
        if ($recursive > 0) {
            $array['company']['numerodanota'] = $array['company']['numerodanota'] + $recursive;
        }
        try {
            return response()->json($this->sendNfe($array, NfeObj::TRANSFER));
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'codError') !== false) {
                $r = json_decode($e->getMessage());
                if ($r->codError == 539 && $recursive <= 10) {
                    $recursive++;
                    return $this->nfeTransfer($recursive);
                }
            }
            return response()->json(
                [
                    'msg' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ], 400);

        }
    }

    /**
     * @param array  $array
     * @param string $type
     *
     * @return array|string
     * @throws Exception
     */
    private function sendNfe(array $array, string $type = NfeObj::DANFE)
    {
        $objNfe = new NfeObj(55, $array);
        $objNfe->type_nfe = $type;
        return $objNfe->emitNote();
    }

    public function printNfe()
    {
        try {
            $dd = json_decode(base64_decode($this->request->get('hash')));
            $prinNote = new NfePrint($dd);
            $prinNote->printNote();
            exit();
        } catch (Exception $e) {
            return response()->json(
                [
                    'msg' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ], 400);

        }
    }

    public function cancelNfe()
    {
        $array['company'] = $this->request->get('empresa');
        $array['hash'] = json_decode(base64_decode($this->request->get('hash')));
        $just = $this->request->get('just');
        try {
            $cancelNote = new NfeCancel(intval($array['hash']->model), $array, $just);
            return response()->json($cancelNote->cancel());
        } catch (Exception $e) {
            return response()->json(
                [
                    'msg' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ], 400);
        }
    }
}
