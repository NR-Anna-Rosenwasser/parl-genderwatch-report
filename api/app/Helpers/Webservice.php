<?php

namespace App\Helpers;
use SaintSystems\OData\ODataClient;
use SaintSystems\OData\GuzzleHttpProvider;

class Webservice
{
    public $HttpProvider;
    public $ODataClient;

    public function __construct()
    {
        $this->HttpProvider = new GuzzleHttpProvider();
        $this->ODataClient = new ODataClient("https://ws.parlament.ch/odata.svc", null, $this->HttpProvider);
    }

    public function query(
        string $model,
        string $filter = "",
        $top = 100,
        $select = "*",
        $orderby = "",
        $countOnly = false,
        $skip = 0
    ){
        $response = $this->ODataClient->get
            (
                $model .
                ($countOnly ? '/$count' : "") .
                '?$skip=' . $skip .
                ($filter ? '&$filter=' . urlencode($filter) : "") .
                ($top != 0 ? '&$top=' . $top : "") .
                ($select ? '&$select=' . $select : "") .
                ($orderby ? '&$orderby=' . $orderby : "")
            );
        if (!$countOnly) {
            return $response[0]["properties"]["d"];
        } else {
            return $response;
        }
    }

    public static function parseODataDate($odataDate)
    {
        if (preg_match('/\/Date\(([-]?\d+)([+-]\d{4})?\)\//', $odataDate, $matches)) {
            $timestamp = $matches[1] / 1000;
            return new \DateTime("@$timestamp");
        }
        return null;
    }
}
