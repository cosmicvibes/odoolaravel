<?php


namespace Cosmicvibes\Odoolaravel;

class OdooOrder extends Odoo
{

    public $name;
    public $customer_id;

    public $amount_total;
    public $amount_tax;

    public $products;

    protected $order_id;

    /**
     * OdooProduct constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function save()
    {

        $this->order_id = $this->client->create('sale.order',
            [
                'name' => $this->name,
                'partner_id' => $this->customer_id,
                'amount_total' => $this->amount_total,
                'amount_tax' => $this->amount_tax,
            ]);

        $sequence = 0;
        foreach ($this->products as $product) {
            $this->client->create('sale.order.line',
                [
                    'price_unit' => $product['price_before_tax'],
                    'product_uom_qty' => 1.0,
                    'price_subtotal' => $product['price_before_tax'],
                    'price_reduce_taxexcl' => $product['price_before_tax'],
                    'price_tax' => $product['tax_amount'],
                    'name' => $this->name,
                    'order_partner_id' => $this->customer_id,
                    'order_id' => $this->order_id,
                    'sequence' => $sequence,
                    'discount' => 0.0,
                    'price_reduce' => $product['price_before_tax'],
                    'price_reduce_taxinc' => $product['price'],
                    'price_total' => $product['price'],
                    'product_id' => $product['id'],
                ]);
            $sequence++;
        }

        return $this->order_id;

    }
}

