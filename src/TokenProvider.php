<?php
namespace GNOffice\DirectCloud;

interface TokenProvider
{
    public function getToken(): string;
}
