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
//    public $lang = 'en_GB';

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
//        $size = 1024;
//        $grav_url = "https://www.gravatar.com/avatar/" . md5( strtolower( trim( $this->email ) ) ) . "&s=" . $size;
//        $image_contents = file_get_contents($grav_url);
//        if($image_contents) {
//            $image_data = base64_encode($image_contents);
//        }

        $country_id = $this->client->where('code', '=', $this->country)->get('res.country')[0]['id'];
        $state_id   = $this->client->where('country_id', '=', $country_id)
            ->where('name', '=', $this->state)
            ->get('res.country.state')[0]['id'];
        $this->customer_id = $this->client->create('res.partner',
            [
//                'title' => $this->title,
                'name' => $this->name,
                'email' => $this->email,
                'street' => $this->street,
                'street2' => $this->street2,
                'city' => $this->city,
                'zip' => $this->zip,
                'country' => $country_id,
                'state' => $state_id,
                'phone' => $this->phone,
                'mobile' => $this->mobile,
                'contact_address' => $this->contact_address,
                'type' => $this->type,
                'customer' => $this->customer,
                'company_type' => $this->company_type,
                'is_company' => $this->is_company,
                'active' => $this->active,
                'opt_out' => $this->opt_out,
//                'lang' => $this->lang,
//                'image' => $image_data,
            ]);

        return $this->customer_id;

    }
}

