<?php

class API_Export {

    public function __construct(API $master) {
        $this->master = $master;
    }

    /**
     * Export product to Pixelshop through API
     * @param products array an array of product information.
     *           - product[] struct a single product information.
     *               - link string url to the product to purchase
     *               - title string the product title
     *               - description string the product description
     *               - price integer the product price
     *               - thumbnail string url to the thumbnail
     *               - tags string comma seperated tags
     * @return string status of the export
     */
    public function products($products, $async=false) {
        $_params = array("products" => $products, "async" => $async);
        return $this->master->call('export', $_params);
    }
}