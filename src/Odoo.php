<?php

// TODO: Updating each time is slow. We should only update if provided data has changed.

namespace Cosmicvibes\Odoolaravel;

class Odoo
{

    protected $client;

    /**
     * OdooProduct constructor.
     */
    public function __construct()
    {
        $odoo = new \Edujugon\Laradoo\Odoo();
        $this->client = $odoo->connect();
    }

}