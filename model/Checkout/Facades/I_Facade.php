<?php


namespace Model\Checkout\Facades;

use Model\Checkout\Model;

/**
 * Interface I_Checkout
 * @package Model\Checkout
 */
interface I_Facade
{
    /**
     * I_Checkout constructor.
     * @param Model $model
     */
    public function __construct(Model $model);

    /**
     * @return Model
     */
    public function getModel() : Model;

    /**
     * @param \Payment $payment
     * @return mixed
     */
    public function makeReceipt(\Payment $payment);

    /**
     * @return array
     */
    public function getAdditionalFieldsList() : array;

    /**
     * @return bool
     */
    public function validateModel() : bool;

    /**
     * @return string
     */
    public function getValidateModelErrorsStr() : string;

}