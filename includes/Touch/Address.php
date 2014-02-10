<?php
require_once __DIR__ . '/Object.php';

class Touch_Address extends Touch_Object {

    const COUNTRY_AU = 'au';

     /**
     * @var String
     */
    public $firstName;
    /**
     * @var String
     */
    public $lastName;
    /**
     * @var String
     */
    public $middleName;
    /**
     * @var String
     */
    public $number;

    /**
     * @var String
     */
    public $addressOne;

    /**
     * @var String
     */
    public $addressTwo;

    /**
     * @var String
     */
    public $postcode;

    /**
     * @var String
     */
    public $suburb;

    /**
     * @var String
     */
    public $state;

    /**
     * @var String
     */
    public $country;

    public function __construct($country = self::COUNTRY_AU)
    {
        $this->country = $country;
    }

    public function setState($string) {
        switch ($string) {
            case 'New South Wales':
                $this->state = 'NSW';
                break;
            case 'Victoria':
                $this->state = 'VIC';
                break;
            case 'South Australia':
                $this->state = 'SA';
                break;
            case 'Western Australia':
                $this->state = 'WA';
                break;
            case 'Australian Capital Territory':
                $this->state = 'ACT';
                break;
            case 'Northern Territory':
                $this->state = 'NT';
                break;
            case 'Tasmania':
                $this->state = 'TAS';
                break;
            case 'Queensland':
                $this->state = 'QLD';
                break;
        }
    }

}
