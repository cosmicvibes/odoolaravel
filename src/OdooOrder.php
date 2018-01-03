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
    public $payment_terms = null;
    public $carrier_id = null;
    public $delivery_price;
    public $currency_id;
    public $order_datetime;
    public $sales_channel;
    public $shipping_policy = 'one'; // 'one' = ship all at once, 'direct' = ship each item when available

    /**
     * OdooProduct constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    // TODO - allow updates!!
    public function save()
    {

        $this->order_id = $this->client->create('sale.order',
            [
                'name' => $this->name,
                'date_order' => $this->order_datetime,
                'partner_id' => $this->customer_id,
                'amount_total' => $this->amount_total,
                'amount_tax' => $this->amount_tax,
                'carrier_id' => $this->carrier_id,
                'delivery_price' => $this->delivery_price,
                'payment_term_id' => $this->payment_terms,
                'currency_id' => $this->currency_id,
                'team_id'   => $this->sales_channel,
                'picking_policy' => $this->shipping_policy,
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
                    'name' => $product['name'],
                    'order_partner_id' => $this->customer_id,
                    'order_id' => $this->order_id,
                    'sequence' => $sequence,
                    'discount' => 0.0,
                    'price_reduce' => $product['price_before_tax'],
                    'price_reduce_taxinc' => $product['price'],
                    'price_total' => $product['price'],
                    'product_id' => $product['id'],
                    'tax_id'    => [
                        [5, 0, 0],
                        [4, $product['tax_id'], 0]
                    ]
                ]);
            $sequence++;
        }

        return $this->order_id;

    }
}