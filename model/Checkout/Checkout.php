<?php


namespace Model\Checkout;

use Model\Checkout\Facades\A_Facade;
use Model\Checkout\Facades\InitPro;

/**
 * Class Checkout
 * @package Model
 */
class Checkout
{
    const INIT_PRO = 1;

    /**
     * @var A_Facade
     */
    protected A_Facade $instance;

    /**
     * @var array|int[]
     */
    protected static array $checkouts = [
        self::INIT_PRO => InitPro::class
    ];

    /**
     * Checkout constructor.
     * @param Model $model
     * @throws \Exception
     */
    public function __construct(Model $model)
    {
        $instance = self::getCheckoutForModel($model);
        if (!is_null($instance)) {
            $this->instance = $instance;
        } else {
            throw new \Exception('Undefined Checkout model type');
        }
    }

    /**
     * @return A_Facade
     */
    public function instance() : A_Facade
    {
        return $this->instance;
    }

    /**
     * @param Model $model
     * @return A_Facade|null
     */
    public static function getCheckoutForModel(Model $model) : ?A_Facade
    {
        if (isset(self::$checkouts[$model->type])) {
            $className = self::$checkouts[$model->type];
            return new $className($model);
        } else {
            return null;
        }
    }

    /**
     * @param \User $user
     * @return static|null
     * @throws \Exception
     */
    public static function makeForUser(\User $user) : ?self
    {
        $model = self::getModelForUser($user);
        return !is_null($model)
            ?   new self($model)
            :   null;
    }

    /**
     * @param array $areasIds
     * @return Model|null
     */
    protected static function getModelForAreasIds(array $areasIds) : ?Model
    {
        /** @var \Schedule_Area_Assignment $assignment */
        $assignment = \Schedule_Area_Assignment::query()
            ->where('model_name', '=', (new Model())->getTableName())
            ->whereIn('area_id', $areasIds)
            ->find();
        return !is_null($assignment)
            ?   $assignment->getObject()
            :   null;
    }

    /**
     * @param \User $user
     * @return Model|null
     */
    public static function getModelForUser(\User $user) : ?Model
    {
        $userAreas = (new \Schedule_Area_Assignment($user))->getAreas();
        $userAreasIds = collect($userAreas)->pluck('id')->toArray();
        return Checkout::getModelForAreasIds($userAreasIds);
    }

    /**
     * @param \User $user
     * @return bool
     */
    public static function hasCheckout(\User $user) : bool
    {
        $model = self::getModelForUser($user);
        return !(is_null($model) || is_null(self::getCheckoutForModel($model)));
    }
}