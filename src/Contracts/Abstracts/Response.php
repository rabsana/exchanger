<?php

namespace Rabsana\Exchanger\Contracts\Abstracts;

use Rabsana\Exchanger\Contracts\Interfaces\Response as InterfacesResponse;
use Rabsana\Exchanger\Exceptions\ExchangerResponseInNotValid;

abstract class Response implements InterfacesResponse
{
    private $data = [];

    public function __construct(array $data, bool $nullable = false)
    {
        $this->setData($data);
        if (!$nullable || !empty($data)) {
            $this->checkData();
        }
    }

    public function getData(): array
    {
        return (array) $this->data;
    }

    public function setData(array $data): Response
    {
        $this->data = $data;

        return $this;
    }

    public function checkData(): Response
    {
        $getData = $this->getData();

        // check if the array has 1 dimension
        if (empty($getData[0]) || !is_array($getData[0])) {
            $getData = [$getData];
        }

        foreach ($getData as $data) {
            foreach ($this->getKeys() as $key) {

                if (strpos($key, '.') !== false) {
                    // key has two part
                    $key = explode('.', $key);

                    // check data has array base on part one
                    if (empty($data[$key[0]])) {

                        throw new ExchangerResponseInNotValid("The \"{$key[0]}\" array is missing in response");
                    }

                    // check the array base part two
                    foreach ($data[$key[0]] as $innerData) {

                        if (!isset($innerData[$key[1]])) {
                            throw new ExchangerResponseInNotValid("The \"{$key[1]}\" item is missing in \"{$key[0]}\" array");
                        }
                    }


                    // 
                } else if (!array_key_exists($key, $data)) {


                    throw new ExchangerResponseInNotValid("The \"{$key}\" item is missing in response");

                    // 
                }
            }
        }

        return $this;
    }
}
