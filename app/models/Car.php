<?php

class Car extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var integer
     * @Primary
     * @Identity
     * @Column(type="integer", length=11, nullable=false)
     */
    public $id;

    /**
     *
     * @var string
     * @Column(type="string", length=255, nullable=false)
     */
    public $tag;

    /**
     *
     * @var string
     * @Column(type="string", length=45, nullable=false)
     */
    public $marca;

    /**
     *
     * @var string
     * @Column(type="string", length=45, nullable=false)
     */
    public $nome;

    /**
     *
     * @var string
     * @Column(type="string", length=45, nullable=false)
     */
    public $placa;

    /**
     *
     * @var string
     * @Column(type="string", length=45, nullable=false)
     */
    public $status;

    /**
     *
     * @var string
     * @Column(type="string", length=255, nullable=true)
     */
    public $ptag;

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'car';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return Car[]
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return Car
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
