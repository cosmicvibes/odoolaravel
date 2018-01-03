<?php


namespace Cosmicvibes\Odoolaravel;

class OdooCustomer extends Odoo
{
    public $title;
    public $name;
    public $email;

    public $street;
    public $street2;
    public $city;
    public $state;
    public $zip;
    public $country;

    public $phone;
    public $mobile;

    public $contact_address;

    public $type = 'contact';
    public $customer = true;
    public $company_type = 'person';
    public $is_company = false;
    public $active = true;
    public $opt_out = false;
    public $notify_email = 'none';
    public $pricelist = null;
    public $internal_reference = null;
    public $payment_terms = null;
    public $channel_id = null;
    public $address_ids = null;
    public $currency_id;
    public $lang = 'en_GB';

    protected $customer_id;

    /**
     * OdooProduct constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function save() {

        $image_data = null;

        $country_id = $this->client->where('code', '=', $this->country)->get('res.country')[0]['id'];


        // TODO: Need better state checking, handling of errors
        $this->customer_id = $this->client->create('res.partner',
            [
                'name' => $this->name,
                'email' => $this->email,
                'street' => $this->street,
                'street2' => $this->street2,
                'city' => $this->city,
                'zip' => $this->zip,
                'country_id' => $country_id,
                'state_id' => $state_id,
                'phone' => $this->phone,
                'mobile' => $this->mobile,
                'contact_address' => $this->contact_address,
                'type' => $this->type,
                'customer' => $this->customer,
                'company_type' => $this->company_type,
                'is_company' => $this->is_company,
                'active' => $this->active,
                'opt_out' => $this->opt_out,
                'notify_email' => $this->notify_email,
                'property_product_pricelist' => $this->pricelist,
                'ref' => $this->internal_reference,
                'property_payment_term_id' => $this->payment_terms,
                'channel_ids' => $this->channel_id,
                'child_ids' => [
                    [5, 0, 0],
                    [4, $this->address_ids, 0]
                ],
                'lang' => $this->lang,
                'currency_id' => $this->currency_id,
            ]);

        return $this->customer_id;

    }
}

