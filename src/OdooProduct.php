<?php

// TODO: Updating each time is slow. We should only update if provided data has changed.

namespace Cosmicvibes\Odoolaravel;

class OdooProduct extends Odoo
{

    public $name;
    public $sku;
    public $variant_ids;
    public $variants;
    public $variant_data;

    public $weight;

    public $image_data;
    public $image_filename;

    public $route_ids;
    public $categ_id;

    // TODO: Rework this to use VAT rates from an Odoo rate specified
    public $pricelist_id;
    public $price;
    public $sale_price_vat_rate = 0;

    public $cost_price;
    public $cost_price_vat_rate = 0;

    public $print_product_template_id;
    public $plain_product_template_id;

    public $type = 'product';
    public $sale_ok = true;
    public $purchase_ok = true;

    protected $product_template_id;
    public $product_variant_ids;

    /**
     * OdooProduct constructor.
     */
    public function __construct()
    {

        parent::__construct();

        $config_extra_product_fields = config('odoolaravel.product_extra_fields');
        foreach ($config_extra_product_fields as $key => $value) {
            $this->{"$value"} = null;
        }

    }

    public function load($product_template_id) {

        // TODO: error check for duplicate records?
        // TODO: Proper exception handling
        $existing_product_template_id = $this->client->where('id', '=', $product_template_id)->search('product.template');
        if ($existing_product_template_id === 1) {
            dd('product id not found');
        } else {

            $existing_product_template = $this->client->where('id', '=', $product_template_id)->get('product.template')->first();

            $this->name                 = $existing_product_template['name'];
            $this->type                 = $existing_product_template['type'];
            $this->route_ids            = $existing_product_template['route_ids'];
            $this->weight               = $existing_product_template['weight'];
            $this->price                = $existing_product_template['list_price'];
            $this->cost_price           = $existing_product_template['standard_price'];
            $this->categ_id             = $existing_product_template['categ_id'];
            $this->image_data           = $existing_product_template['image'];
            $this->sale_ok              = $existing_product_template['sale_ok'];
            $this->purchase_ok          = $existing_product_template['purchase_ok'];

            // Add extra fields from config
            $config_extra_product_fields = config('odoolaravel.product_extra_fields');
            foreach ($config_extra_product_fields as $key => $value) {
                $this->{"$value"}       = $existing_product_template[$key];
            }

            if ($existing_product_template['product_variant_ids']) {
                $this->variant_ids          = $existing_product_template['product_variant_ids'];
                foreach ($this->variant_ids as $variant_id) {
                    $variant_code = $this->client->where('attribute_id', '=', 1)->where('id', '=', $variant_id)->get('product.attribute.value')->first()['name'];
                    $this->variant_data[] =
                        [
                            'id' => $variant_id,
                            'code' => $variant_code
                        ];
                }
            }

        }

        $this->product_template_id = $product_template_id;

    }

    public function save() {
        // TODO: Validate all required data before saving

        // TODO: Exceptions when not found!!!
        // TODO: Delete old variants?
        if ($this->variants) {
            foreach ($this->variants as $variant) {
                $variant_id = $this->client->where('attribute_id', '=', 1)->where('name', '=', $variant)->get('product.attribute.value')->first()['id'];
                $this->variant_data[] =
                    [
                        'id'    => $variant_id,
                        'code'  => $variant
                    ];

                $this->variant_ids[] = $variant_id;
            }
        }

        // TODO: Updates are done here when perhaps no data has changed, in those cases just skip updating
        if ($this->image_filename) {
            $image_contents = file_get_contents( config('odoolaravel.image_url_prefix') . $this->image_filename);
            if($image_contents) {
                $this->image_data = base64_encode($image_contents);
            }
        }

        if(is_null($this->route_ids)) {
            $this->route_ids = [
                // Delete all routes
                [5, 0, 0],
            ];
        }

        $divisor = ($this->sale_price_vat_rate / 100) + 1;
        $this->price = $this->price / $divisor;

        $divisor = ($this->cost_price_vat_rate / 100) + 1;
        $this->cost_price = $this->cost_price / $divisor;

        // Standard Odoo Product Data
        $product_data = [
            'name'                  => $this->name,
            'type'                  => $this->type,
            'route_ids'             => $this->route_ids,
            'weight'                => $this->weight,
            'categ_id'              => $this->categ_id,
            'image'                 => $this->image_data,
            'sale_ok'               => $this->sale_ok,
            'purchase_ok'           => $this->purchase_ok,
            'default_code'          => $this->sku
        ];

        // Add extra fields from config
        $config_extra_product_fields = config('odoolaravel.product_extra_fields');
        foreach ($config_extra_product_fields as $key => $value) {
            if ($this->$value != null) {
                $product_data += [ $key => $this->$value ];
            }
        }

        // If a pricelist is not specified,then set the sale price directly
        if ($this->pricelist_id == null) {
            $product_data += [ 'list_price' => $this->price ];
        }

        // Update or Create
        if ($this->product_template_id) {
            $this->client->where('id', '=', $this->product_template_id)->update('product.template', $product_data);
        } else {
            $this->product_template_id = $this->client->create('product.template', $product_data);
        }

        // Create variants if required, TODO: remove them if not?
        if ($this->variant_ids) {

            $product_attribute_line_data = [
                'product_tmpl_id' => $this->product_template_id,
                // Size
                'attribute_id'    => 1,
                'value_ids' => [
                    [6, 0, $this->variant_ids]
                ]
            ];

            $existing_product_attribute_line_id = $this->client
                ->where('product_tmpl_id', '=', $this->product_template_id)
                ->where('attribute_id', '=', 1)
                ->search('product.attribute.line')->first();

            if ($existing_product_attribute_line_id != null) {
                $this->client
                    ->where('id', '=', $existing_product_attribute_line_id)
                    ->update('product.attribute.line', $product_attribute_line_data);
            } else {
                // Attribute line to product template
                $this->client->create('product.attribute.line', $product_attribute_line_data);
            }

            // Create variants
            foreach ($this->variant_data as $id => $code) {

                // Create or update variants;
                // Delete any existing ones not on this list.
                $product_variant_data = [
                    'product_tmpl_id'       => $this->product_template_id,
                    'attribute_value_ids'   => array(array(6, false, array([$id]))),
                    'default_code'          => $this->sku . "-" . $code,
//                    'standard_price'        => $this->cost_price,
                ];

                // If a pricelist is not specified,then set the sale price directly
                if ($this->pricelist_id == null) {
                    $product_variant_data += [ 'list_price' => $this->price ];
                }

                $existing_variant_id = $this->client
                    ->where('product_tmpl_id', '=', $this->product_template_id)
                    ->where('attribute_value_ids', '=', $id)
                    ->search('product.product')->first();

                if ($existing_variant_id != null) {
                    $this->client
                        ->where('id', '=', $existing_variant_id)
                        ->update('product.product', $product_variant_data);
                } else {
                    $this->client->create('product.product', $product_variant_data);
                }

            }

            // Nuke the first variant which is created for unknown reasons
            $variants = $this->client->where('product_tmpl_id', '=', $this->product_template_id)->get('product.product');
            foreach ($variants as $variant) {
                if (empty($variant['attribute_value_ids'])) {
                    $this->client->deleteById('product.product', $variant['id']);
                }
            }

            // Save the ids for later use
            $variants = $this->client->where('product_tmpl_id', '=', $this->product_template_id)->get('product.product');
            foreach ($variants as $variant) {
                $this->product_variant_ids[] = $variant['id'];
            }

        }

//        // Add SKU. For unknown reasons this must be added after the initial creation.
//        if ($this->sku) {
//            $this->client->where('id', '=', $this->product_template_id)
//                ->update('product.template',
//                    ['default_code' => $this->sku]
//                );
//        }

        if ($this->print_product_template_id && $this->plain_product_template_id) {

            $bom_data = [
                'product_tmpl_id' => $this->product_template_id,
                'type' => 'normal',
                'product_qty' => 1.0,
                'picking_type_id' => false,
                'routing_id' => false,
                'ready_to_produce' => 'asap',
                'active' => true,
                'product_id' => false,
                'code' => false,
                'sequence' => 0,
            ];

            $existing_bom_id = $this->client
                ->where('product_tmpl_id', '=', $this->product_template_id)
                ->where('type', '=', 'normal')
                ->where('product_qty', '=', 1.0)
                ->where('picking_type_id', '=', false)
                ->where('routing_id', '=', false)
                ->where('ready_to_produce', '=', 'asap')
                ->where('active', '=', true)
                ->where('product_id', '=', false)
                ->where('code', '=', false)
                ->search('mrp.bom')->first();

            if ($existing_bom_id != null) {
                $this->client
                    ->where('id', '=', $existing_bom_id)
                    ->update('mrp.bom', $bom_data);

                $bom_id = $existing_bom_id;
            } else {
                $bom_id = $this->client->create('mrp.bom', $bom_data);
            }

            // Add PNN Print line
            $print_product_product_id = $this->client->where('id', '=', $this->print_product_template_id)->get('product.template')[0]['product_variant_id'][0];

            $bom_sequence = 0;
            $print_bom_line_data = [
                'product_id' => $print_product_product_id,
                'bom_id' => $bom_id,
                'product_qty' => 1.0,
                'routing_id' => false,
                'operation_id' => false,
                'has_attachments' => false,
                'child_bom_id' => false,
                'sequence' => $bom_sequence,
            ];

            $existing_print_bom_line_id = $this->client
                ->where('product_id', '=', $print_product_product_id)
                ->where('bom_id', '=', $bom_id)
                ->where('product_qty', '=', 1.0)
                ->where('routing_id', '=', false)
                ->where('operation_id', '=', false)
                ->where('has_attachments', '=', false)
                ->where('child_bom_id', '=', false)
                ->search('mrp.bom.line')->first();

            if ($existing_print_bom_line_id != null) {
                $this->client
                    ->where('id', '=', $existing_print_bom_line_id)
                    ->update('mrp.bom.line', $print_bom_line_data);
                $bom_line_ids[] = $existing_print_bom_line_id;
            } else {
                $bom_line_ids[] = $this->client->create('mrp.bom.line', $print_bom_line_data);
            }
            $bom_sequence++;

            $product_variants = $this->client->where('product_tmpl_id', '=', $this->product_template_id)->get('product.product');
            $plain_product_variants = $this->client->where('product_tmpl_id', '=', $this->plain_product_template_id)->get('product.product');
            foreach ($product_variants as $product_variant) {
                foreach ($plain_product_variants as $plain_product_variant) {
                    if ($product_variant['attribute_value_ids'][0] === $plain_product_variant['attribute_value_ids'][0]) {

                        $bom_line_data = [
                            'product_id' => $plain_product_variant['id'],
                            'bom_id' => $bom_id,
                            'product_qty' => 1.0,
                            'routing_id' => false,
                            'operation_id' => false,
                            'has_attachments' => false,
                            'child_bom_id' => false,
//                                'attribute_value_ids' => [6, $product_variant['attribute_value_ids'][0], 0,]
                            'attribute_value_ids'   => array(array(6, false, array([$product_variant['attribute_value_ids'][0]]))),
                            'sequence' => $bom_sequence,
                        ];

                        $existing_bom_line_id = $this->client
                            ->where('product_id', '=', $plain_product_variant['id'])
                            ->where('bom_id', '=', $bom_id)
                            ->where('product_qty', '=', 1.0)
                            ->where('routing_id', '=', false)
                            ->where('operation_id', '=', false)
                            ->where('has_attachments', '=', false)
                            ->where('child_bom_id', '=', false)
                            ->where('attribute_value_ids', '=', $product_variant['attribute_value_ids'][0])
                            ->search('mrp.bom.line')->first();

                        if ($existing_bom_line_id != null) {
                            $this->client
                                ->where('id', '=', $existing_bom_line_id)
                                ->update('mrp.bom.line', $bom_line_data);
                            $bom_line_ids[] = $existing_bom_line_id;
                        } else {
                            $bom_line_ids[] = $this->client->create('mrp.bom.line', $bom_line_data);
                        }

                        $bom_sequence++;
                    }
                }
            }

            $this->client->where('id', '=', $bom_id)
                ->update('mrp.bom',
                    [
                        'bom_line_ids' => $bom_line_ids,
                    ]
                );
        }

        if ($this->pricelist_id != null) {

            // Add pricelist for kodestore.com
            $pnn_product_template_pricelist_item_id = $this->client->
            where('pricelist_id', '=', $this->pricelist_id)
                ->where('product_tmpl_id', '=', $this->product_template_id)
                ->where('currency_id', '=', 147)
                ->where('applied_on', '=', '1_product')
                ->search('product.pricelist.item')->first();

            if ($pnn_product_template_pricelist_item_id != null) {
                $this->client->
                where('pricelist_id', '=', $this->pricelist_id)
                    ->where('product_tmpl_id', '=', $this->product_template_id)
                    ->where('currency_id', '=', 147)
                    ->where('applied_on', '=', '1_product')
                    ->update('product.pricelist.item',
                        [
                            'fixed_price' => $this->price,
                        ]);
            } else {
                $this->client->create('product.pricelist.item',
                    [
                        'pricelist_id' => $this->pricelist_id,
                        'product_tmpl_id' => $this->product_template_id,
                        'currency_id' => 147, // GBP
                        'fixed_price' => $this->price,
                        'applied_on' => '1_product',
                    ]);
            }
        }

        return $this->product_template_id;

    }

}

//(0,_ ,{' field': value}): This creates a new record and links it to this one
//(1, id,{' field': value}): This updates values on an already linked record
//(2, id,_): This unlinks and deletes a related record
//(3, id,_): This unlinks but does not delete a related record
//(4, id,_): This links an already existing record
//(5,_,_): This unlinks but does not delete all linked records
//(6,_,[ ids]): This replaces the list of linked records with the provided list
//
//    The underscore symbol used above represents irrelevant values, usually filled with 0 or False.

// TODO: Add these?

//You have updated Name (English (UK)). Update translations
//You have updated Sale Description (English (UK)). Update translations
//You have updated Purchase Description (English (UK)). Update translations
//You have updated Description on Picking (English (UK)). Update translations
//You have updated Description on Delivery Orders (English (UK)). Update translations
//You have updated Description on Receptions (English (UK)). Update translations