<?php
/**
 * Created by PhpStorm.
 * User: DELL
 * Date: 09/08/2018
 * Time: 16:16
 */

namespace App\Http\Objects;

use Exception;
use NFePHP\Common\Certificate as CertificateNfe;

class Certificate
{
    private $fileCertificate;
    private $passCertificate = 123456;
    private $bdName;

    public function __construct($bdName)
    {
        $this->bdName = ($bdName);
    }

    /**
     * @return string
     */
    public function getPassCertificate(): string
    {
        return $this->passCertificate;
    }

    /**
     * @return mixed
     */
    public function getFileCertificate()
    {
        return $this->fileCertificate;
    }


    /**
     * @return CertificateNfe
     * @throws Exception
     */
    public function getCertificate()
    {
        $this->fileCertificate = base64_decode($this->certificate());
        return CertificateNfe::readPfx($this->fileCertificate, $this->passCertificate);
    }

    /**
     * @return string com o certificado A1 em base64
     * @throws Exception
     */
    private function certificate(): string
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => env('API_CERTICATE_URL'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "",
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "cache-control: no-cache",
                "userKey: {$this->bdName}"
            ],
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            throw new Exception("cURL Error #:" . $err);
        } else {
            $response = json_decode($response);
            if (intval($response->codigo) !== 200) {
                throw new Exception("Certificado não encontrado", 123456);
            } else {
                return $response->arquivo;
            }

        }
    }
}