<?php

class WpUsers extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var integer
     */
    public $iD;

    /**
     *
     * @var string
     */
    public $user_login;

    /**
     *
     * @var string
     */
    public $user_pass;

    /**
     *
     * @var string
     */
    public $user_nicename;

    /**
     *
     * @var string
     */
    public $user_email;

    /**
     *
     * @var string
     */
    public $user_url;

    /**
     *
     * @var string
     */
    public $user_registered;

    /**
     *
     * @var string
     */
    public $user_activation_key;

    /**
     *
     * @var integer
     */
    public $user_status;

    /**
     *
     * @var string
     */
    public $display_name;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->setSchema("classicpress");
        $this->setSource("wp_users");
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return WpUsers[]|WpUsers|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null): \Phalcon\Mvc\Model\ResultsetInterface
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return WpUsers|\Phalcon\Mvc\Model\ResultInterface|\Phalcon\Mvc\ModelInterface|null
     */
    public static function findFirst($parameters = null): ?\Phalcon\Mvc\ModelInterface
    {
        return parent::findFirst($parameters);
    }

}
