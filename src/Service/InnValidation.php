<?php

namespace App\Service;

use Exception;
use Memcached;

class InnValidation
{
    public const INDIVIDUAL_INN_LENGTH = 12;
    public const COMPANY_INN_LENGTH = 10;
    public const API_URL = 'https://statusnpd.nalog.ru:443/api/v1/';
    public const API_TIMEOUT = 60;
    public const API_CONNECTTIMEOUT = 5;
    public const API_SUCCESS_STATUS = 200;
    public const API_ERROR_STATUS = 400;

    /**
     * Check individual INN (https://www.egrul.ru/test_inn.html)
     * @param string $inn
     * @return bool
     */
    public function checkIndividualInn(string $inn): bool
    {
        $innLength = strlen($inn);

        if ($innLength === self::INDIVIDUAL_INN_LENGTH) {
            $controlNumberOne = $this->getControlNumber($inn, [7, 2, 4, 10, 3, 5, 9, 4, 6, 8]);
            $controlNumberTwo = $this->getControlNumber($inn, [3, 7, 2, 4, 10, 3, 5, 9, 4, 6, 8]);

            return ($controlNumberOne === (int) $inn[$innLength-2] && $controlNumberTwo === (int) $inn[$innLength-1]);
        }

        return false;
    }

    /**
     * Check company INN (https://www.egrul.ru/test_inn.html)
     * @param string $inn
     * @return bool
     */
    public function checkCompanyInn(string $inn): bool
    {
        $innLength = strlen($inn);

        if ($innLength === self::COMPANY_INN_LENGTH) {
            $controlNumber = $this->getControlNumber($inn, [2, 4, 10, 3, 5, 9, 4, 6, 8]);
            return $controlNumber === (int) $inn[$innLength-1];
        }

        return false;
    }

    /**
     * @param string $str
     * @param array $multipliers
     * @return int
     */
    private function getControlNumber(string $str, array $multipliers): int
    {
        $sum = 0;
        $strLength = strlen($str);

        foreach ($multipliers as $i => $multiplier) {
            if ($i < $strLength) {
                $sum += $multiplier * (int) $str[$i];
            }
        }

        $controlNumber = $sum % 11;
        if ($controlNumber > 9) {
            $controlNumber %= 10;
        }

        return $controlNumber;
    }

    /**
     * Get TaxPayer status (https://npd.nalog.ru/html/sites/www.npd.nalog.ru/api_statusnpd_nalog_ru.pdf)
     * @param string $inn
     * @param string $requestDate
     * @param Memcached $storage
     * @return array
     */
    public function getTaxPayerStatus(string $inn, string $requestDate, Memcached $storage): array
    {
        try {
            $result = [
                'code' => self::API_ERROR_STATUS,
                'status' => false,
                'message' => ''
            ];
            $innStatusKey = $inn.'_status';
            $queryKey = 'status_last_query';

            if ($status = $storage->get($innStatusKey)) {
                $result = $status;
            } else {
                if ($storage->getResultCode() === Memcached::RES_NOTFOUND) {
                    // Запрет на количество запросов с одного ip адреса: не чаще 2 раз в минуту
                    if ($lastQueryTime = $storage->get($queryKey)) {
                        $result['message'] = 'The requests number to the service from one IP address per unit of time has been exceeded, please try again later.';
                    } else {
                        $result = $this->sendRequestToAPI($inn, $requestDate);
                        if ($result['code'] === self::API_SUCCESS_STATUS) {
                            $storage->set($innStatusKey, $result, time() + 24*60*60); // expiration time = 1 day
                        }
                        $storage->set($queryKey, time(), time() + 30);
                    }
                } else {
                    $result['message'] = 'Storage result code '.$storage->getResultCode();
                }
            }
        } catch(Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * @param string $inn
     * @param string $requestDate
     * @return array
     */
    private function sendRequestToAPI(string $inn, string $requestDate): array
    {
        $data = json_encode([
            "inn" => $inn,
            "requestDate" => $requestDate
        ]);

        $curl = curl_init(self::API_URL.'tracker/taxpayer_status');
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, self::API_CONNECTTIMEOUT);
        curl_setopt($curl, CURLOPT_TIMEOUT, self::API_TIMEOUT);
        curl_setopt($curl, CURLOPT_HTTPGET, FALSE);
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        $result = curl_exec($curl);
        if (curl_errno($curl)) {
            $result = [
                'code' => self::API_ERROR_STATUS,
                'status' => false,
                'message' => curl_error($curl)
            ];
        } else {
            $result = json_decode($result, true);
            $result['code'] = self::API_SUCCESS_STATUS;
        }

        return $result;
    }
}