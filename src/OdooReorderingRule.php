<?php

namespace Cosmicvibes\Odoolaravel;

class OdooReorderingRule extends Odoo
{

    public $name;
    public $display_name;
    public $product_max_qty;
    public $product_min_qty;
    public $qty_multiple;
    public $product_id;
    public $lead_days;
    public $active;
    public $lead_type;
    public $group_id;

    public $reordering_rule_id;

    /**
     * OdooProduct constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function load($product_id) {

        // TODO: error check for duplicate records?
        // TODO: Proper exception handling
        $existing_reordering_rule_id = $this->client
            ->where('product_id', '=', $product_id)
            ->search('stock.warehouse.orderpoint')->first();


        dd($existing_reordering_rule_id);

        if ($existing_reordering_rule_id === 1) {
            dd('reordering rule id not found');
        } else {

            $existing_reordering_rule = $this->client->where('id', '=', $existing_reordering_rule_id)->get('stock.warehouse.orderpoint')->first();

            $this->name                 = $existing_reordering_rule['name'];
            $this->display_name         = $existing_reordering_rule['display_name'];
            $this->product_max_qty      = $existing_reordering_rule['product_max_qty'];
            $this->product_min_qty      = $existing_reordering_rule['product_min_qty'];
            $this->qty_multiple         = $existing_reordering_rule['qty_multiple'];
            $this->product_id           = $existing_reordering_rule['product_id'];
            $this->lead_days            = $existing_reordering_rule['lead_days'];
            $this->active               = $existing_reordering_rule['active'];
            $this->lead_type            = $existing_reordering_rule['lead_type'];
            $this->group_id             = $existing_reordering_rule['group_id'];

            $existing_reordering_rule_id = $existing_reordering_rule['id'];

        }

        $this->reordering_rule_id = $existing_reordering_rule_id;

    }

    public function save() {

        $reordering_rule_data = [
            'name' => $this->name,
            'display_name' => $this->display_name,
            'product_max_qty' => $this->product_max_qty,
            'product_min_qty' => $this->product_min_qty,
            'qty_multiple' => $this->qty_multiple,
            'product_id' => $this->product_id,
            'lead_days' => $this->lead_days,
            'active' => $this->active,
            'lead_type' => $this->lead_type,
            'group_id' => $this->group_id,
        ];

        // Update or Create
        if ($this->reordering_rule_id) {
            $this->client->where('id', '=', $this->reordering_rule_id)->update('stock.warehouse.orderpoint', $reordering_rule_data);
        } else {
            $this->reordering_rule_id = $this->client->create('stock.warehouse.orderpoint', $reordering_rule_data);
        }

        return $this->reordering_rule_id;

    }
}

