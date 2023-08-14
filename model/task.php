<?php
/**
 * Класс реализующий методы для работы с задачами
 *
 * @author BadWolf
 * @date 16.05.2018 16:47
 * @version 20190329
 * Class Task
 */
class Task extends Task_Model
{
    const PRIORITY_NORMAl = 1;  //Обычный приоритет
    const PRIORITY_MEDIUM = 2;  //Средний приоритет
    const PRIORITY_HIGH = 3;    //Высокий приоритет

    const TYPE_PAYMENT = 1;
    const TYPE_SCHEDULE = 2;
    const TYPE_CLIENT_COMMENT = 3;
    const TYPE_PAYMENT_WHEN_IN_SCHEDULE = 4; //Задача для менеджеров в случае если клиент ставит себя в график не имея занятий на балансе

    /**
     * Поиск комментариев задачи
     *
     * @return array
     */
    public function getNotes() : array
    {
        if (empty($this->id)) {
            return [];
        }

        return Task_Note::query()
            ->where('task_id', '=', $this->getId())
            ->orderBy('date', 'DESC')
            ->findAll();
    }

    /**
     * Геттер для текста комментария при закрытии задачи
     *
     * @return string
     */
    public function doneComment() : string
    {
        return 'Задача закрыта';
    }

    /**
     * Геттер для текста комментария при восстановлении задачи для доработки
     *
     * @return string
     */
    public function doneRevertComment() : string
    {
        return 'Задача восстановлена на доработку';
    }

    /**
     * Добавление комментария к задаче
     *
     * @param string $text - текст комментария
     * @param int|null $authorId - id автора, по умолчанию береться id авторизованного пользователя
     * @param string|null $date - дата комментария, по умолчанию берется текущая
     * @return $this
     */
    public function addNote(string $text, int $authorId = null, string $date = null)
    {
        $note = new Task_Note();

        if (is_null($authorId)) {
            if (is_null(User_Auth::current())) {
                $note->authorId(0);
            } else {
                $note->authorId(User_Auth::parentAuth()->getId());
            }
        } else {
            $note->authorId($authorId);
        }

        if (is_null($date)) {
            $note->date(date('Y-m-d H:i:s'));
        } else {
            $note->date($date);
        }

        $note->taskId($this->id)
            ->text($text)
            ->save();

        return $this;
    }

    /**
     * Пометка завершения задачи
     *
     * @return $this
     */
    public function markAsDone()
    {
        if (empty($this->id)) {
            return $this;
        }

        Core::notify([&$this], 'before.Task.markAsDone');

        $this->doneDate(date('Y-m-d'))
            ->done(1)
            ->save();

        $this->addNote($this->doneComment());
        return $this;
    }

    /**
     * Возвращение задачи на выполнение
     *
     * @return $this
     */
    public function doneRevert()
    {
        if (empty($this->id)) {
            return $this;
        }

        Core::notify([&$this], 'TaskDoneRevert');

        $this->done_date = null;
        $this->done(0)->save();
        return $this;
    }

    /**
     * @param User $client
     * @param string $date
     */
    public static function addClientReminderTask(User $client, string $date)
    {
        $task = Task_Controller::factory()
            ->associate($client->getId())
            ->priorityId(self::PRIORITY_MEDIUM)
            ->date($date)
            ->save();

        $text = $client->getFio() . ', отсутствовал. Уточнить насчет дальнейшего графика.';
        $task->addNote($text);
    }

    /**
     * Создание задачи для каждого нового клиента
     *
     * @param User $client
     * @param string|null $date
     * @return void
     */
    public static function addClientChecklistTask(User $client, string $date = null): void
    {
        if (!$date) {
            $date = date('Y-m-d');
        }

        $task = Task_Controller::factory()
            ->associate($client->getId())
            ->priorityId(self::PRIORITY_MEDIUM)
            ->date($date)
            ->save();

        if (!is_null($task)) {
            $text = $client->getFio() . ', проверить чек-лист: выставлен ли график, начислена премия преподавателю, выставлен график платежей';
            $task->addNote($text);
        }
    }

    /**
     * @return false|int
     */
    public function time()
    {
        return strtotime($this->date);
    }

    /**
     * @param null $obj
     * @return $this
     */
    public function save($obj = null)
    {
        Core::notify([&$this], 'before.Task.save');

        if (empty($this->date)) {
            $this->date = date('Y-m-d');
        }

        if (empty(parent::save())) {
            return null;
        }

        Core::notify([&$this], 'after.Task.save');
        return $this;
    }

    /**
     * @param null $obj
     * @return $this|void
     */
    public function delete($obj = null)
    {
        Core::notify([&$this], 'before.Task.delete');

        (new Property())->clearForObject($this);
        foreach ($this->getNotes() as $note) {
            $note->delete();
        }

        parent::delete();
        Core::notify([&$this], 'after.Task.delete');
    }

}