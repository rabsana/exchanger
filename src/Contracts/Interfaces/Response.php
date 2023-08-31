<?php

namespace Rabsana\Exchanger\Contracts\Interfaces;

interface Response
{
    public function __construct(array $data);

    public function getData(): array;

    public function setData(array $data): Response;

    public function checkData(): Response;

    public function getKeys(): array;
}
