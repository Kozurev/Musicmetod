<?php


namespace Model\Checkout\Facades;

use Model\Checkout\Model;

/**
 * Class A_Checkout
 * @package Model\Checkout
 */
abstract class A_Facade implements I_Facade
{
    /**
     * @var Model
     */
    private Model $checkoutModel;

    /**
     * @var array
     */
    private array $validateErrors = [];

    /**
     * A_Facade constructor.
     * @param Model $model
     * @throws \Exception
     */
    public function __construct(Model $model)
    {
        $this->setModel($model);
//        if (!$this->validateModel()) {
//            throw new \Exception($this->getValidateModelErrorsStr());
//        }
    }

    /**
     * @inheritDoc
     */
    public function getModel(): Model
    {
        return $this->checkoutModel;
    }

    /**
     * @param Model $model
     */
    protected function setModel(Model $model)
    {
        $this->checkoutModel = $model;
    }

    /**
     * Метод валидации модели
     *
     * @return bool
     */
    public function validateModel() : bool
    {
        return false;
    }

    /**
     * @param array $errors
     */
    public function setValidateErrors(array $errors) : void
    {
        $this->validateErrors = $errors;
    }

    /**
     * @return array
     */
    public function getValidateErrors() : array
    {
        return $this->validateErrors;
    }

    public function getValidateModelErrorsStr() : string
    {
        return 'Ошибка кассы: ' . implode('; ', $this->validateErrors);
    }


}