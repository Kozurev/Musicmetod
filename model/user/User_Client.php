<?php


namespace Model\User;

use Orm;
use User_Teacher_Assignment;
use Model\User\User_Teacher;

/**
 * Реализация полиморфных связей для разных групп пользователей
 *
 * @method static User_Client|null find(int $id)
 *
 * Class User_Client
 * @package Model\User
 */
class User_Client extends \User
{
    /**
     * @return Orm
     */
    public static function query(): Orm
    {
        return parent::query()->where('group_id', '=', ROLE_CLIENT);
    }

    /**
     * @param User_Teacher $teacher
     * @return bool
     */
    public function hasTeacher(User_Teacher $teacher) : bool
    {
        return User_Teacher_Assignment::query()
            ->where('teacher_id', '=', $teacher->getId())
            ->where('client_id', '=', $this->getId())
            ->exists();
    }

    /**
     * @param User_Teacher $teacher
     * @return User_Teacher_Assignment|null
     */
    public function getTeacherAssignment(User_Teacher $teacher) : ?User_Teacher_Assignment
    {
        return User_Teacher_Assignment::query()
            ->where('teacher_id', '=', $teacher->getId())
            ->where('client_id', '=', $this->getId())
            ->find();
    }

    /**
     * @param User_Teacher $teacher
     * @return User_Teacher_Assignment
     * @throws \Exception
     */
    public function appendTeacher(User_Teacher $teacher) : User_Teacher_Assignment
    {
        if ($this->hasTeacher($teacher)) {
            throw new \Exception('Преподаватель уже добавлен в список');
        } else {
            $assignment = (new User_Teacher_Assignment())
                ->clientId($this->getId())
                ->teacherId($teacher->getId());
            if (is_null($assignment->save())) {
                throw new \Exception($assignment->_getValidateErrorsStr());
            } else {
                return $assignment;
            }
        }
    }

    /**
     * @param User_Teacher $teacher
     */
    public function removeTeacher(User_Teacher $teacher) : void
    {
        $assignment = $this->getTeacherAssignment($teacher);
        if (!is_null($assignment)) {
            $assignment->delete();
        }
    }

    /**
     * @param array $teachersIds
     */
    public function syncTeachers(array $teachersIds)
    {
        $this->clearTeachers();
        if (count($teachersIds) > 0) {
            $query = 'INSERT INTO ' . (new User_Teacher_Assignment())->getTableName() . ' (teacher_id, client_id) VALUES ';
            foreach ($teachersIds as $key => $teacherId) {
                $query .= '(' . $teacherId . ', ' . $this->getId() . ')';
                if ($key + 1 < count($teachersIds)) {
                    $query .= ', ';
                }
            }
            Orm::execute($query);
        }
    }

    /**
     *
     */
    public function clearTeachers()
    {
        Orm::execute('DELETE FROM ' . (new User_Teacher_Assignment())->getTableName() . ' WHERE client_id = ' . $this->getId());
    }

    /**
     * @return \User_Balance
     */
    public function getBalance(): \User_Balance
    {
        return \User_Balance::find($this->getId());
    }

    /**
     * @param \Payment_Tariff $tariff
     * @throws \Exception
     */
    public function buyTariff(\Payment_Tariff $tariff): void
    {
        $balance = $this->getBalance();

        if ($balance->getBalance() < $tariff->price()) {
            throw new \Exception('Недостаточно средств для покупки тарифа');
        }

//        //Создание платежа
//        $payment = (new \Payment())
//            ->type(2)
//            ->user($client->getId())
//            ->value($tariff->price())
//            ->description("Покупка тарифа \"" . $tariff->title() . "\"");
//        if (!$payment->save()) {
//            throw new \Exception($payment->_getValidateErrorsStr());
//        }

//        $balance = $balance->getValues($client)[0];
//        if ($balance->value() < $tariff->price()) {
//            exit(REST::error(1, 'Недостаточно средств для покупки данного тарифа'));
//        }
//
//        //Создание платежа
//        $payment = (new \Payment())
//            ->type(2)
//            ->user($client->getId())
//            ->value($tariff->price())
//            ->description("Покупка тарифа \"" . $tariff->title() . "\"");
//        if (!$payment->save()) {
//            throw new \Exception($payment->_getValidateErrorsStr());
//        }
//
//        //Корректировка кол-ва занятий
//        $countIndivLessons = Property_Controller::factoryByTag('indiv_lessons')->getValues($client)[0];
//        $countGroupLessons = Property_Controller::factoryByTag('group_lessons')->getValues($client)[0];
//        if ($tariff->countIndiv() != 0) {
//            $countIndivLessons->value($countIndivLessons->value() + $tariff->countIndiv())->save();
//        }
//        if ($tariff->countGroup() != 0) {
//            $countGroupLessons->value($countGroupLessons->value() + $tariff->countGroup())->save();
//        }
//
//        //Корректировка пользовательской медианы (средняя стоимость занятия)
//        $clientRate = [];
//        if ($tariff->countIndiv() != 0) {
//            $clientRate['client_rate_indiv'] = $tariff->countIndiv();
//        }
//        if ($tariff->countGroup() != 0) {
//            $clientRate['client_rate_group'] = $tariff->countGroup();
//        }
//
//        foreach ($clientRate as $rateType => $countLessons) {
//            $clientRateProperty = Property_Controller::factoryByTag($rateType);
//            $newClientRateValue = $tariff->price() / $countLessons;
//            $newClientRateValue = round($newClientRateValue, 2);
//            $oldClientRateValue = $clientRateProperty->getValues($client)[0];
//            $oldClientRateValue->value($newClientRateValue)->save();
//            //$response['rate'][$rateType] = $newClientRateValue;
//        }
    }
}