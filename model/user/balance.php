<?php

/**
 * Class User_Balance
 */
class User_Balance extends User_Balance_Model
{
    const LESSONS_INDIVIDUAL = 1;
    const LESSONS_GROUP = 2;

    /**
     * @param float $count
     * @param int $type
     * @param bool $withSave
     */
    public function addLessons(float $count, int $type, bool $withSave = true)
    {
        if ($type === self::LESSONS_INDIVIDUAL) {
            $this->addIndividualLessons($count, $withSave);
        } elseif ($type === self::LESSONS_GROUP) {
            $this->addGroupLessons($count, $withSave);
        }
    }

    /**
     * @param float $count
     * @param bool $withSave
     */
    public function addIndividualLessons(float $count, bool $withSave): void
    {
        $this->individual_lessons_count += $count;
        if ($withSave) {
            $this->save();
        }
    }

    /**
     * @param float $count
     * @param bool $withSave
     */
    public function addGroupLessons(float $count, bool $withSave): void
    {
        $this->group_lessons_count += $count;
        if ($withSave) {
            $this->save();
        }
    }

    /**
     * @param int $type
     * @return float
     */
    public function getCountLessons(int $type): float
    {
        if ($type === self::LESSONS_INDIVIDUAL) {
            return $this->individual_lessons_count;
        } elseif ($type === self::LESSONS_GROUP) {
            return $this->group_lessons_count;
        } else {
            return 0.0;
        }
    }

    /**
     * @param float $price
     * @param int $type
     */
    public function setAvgPrice(float $price, int $type): void
    {
        if ($type === self::LESSONS_INDIVIDUAL) {
            $this->individual_lessons_average_price = $price;
        } elseif ($type === self::LESSONS_GROUP) {
            $this->group_lessons_average_price = $price;
        }
    }

    /**
     * @param int $type
     * @return float
     */
    public function getAvgPrice(int $type): float
    {
        if ($type === self::LESSONS_INDIVIDUAL) {
            return $this->individual_lessons_count;
        } elseif ($type === self::LESSONS_GROUP) {
            return $this->group_lessons_count;
        } else {
            return 0.0;
        }
    }
}