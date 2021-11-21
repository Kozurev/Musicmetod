<?php
/**
 * Класс реализующий методы для работы с пользователями
 *
 * @author BadWolf
 * @version 20191020
 * @version 20191027 - методы, связанные с авторизацией, вынесены теперь в отдельный класс
 *
 * @method static User|null find(int $id)
 *
 * Class User
 */
class User extends User_Model
{
    /**
     * Список скрываемых свйойств при отобржаении объекта либо при передаче его данных на клиент
     *
     * @var array
     */
    private static array $hidden = ['password', 'auth_token', 'push_id'];

    /**
     * @return array
     */
    public static function getHiddenProps(): array
    {
        return self::$hidden;
    }

    /**
     * @param array $forbiddenProps
     * @return stdClass
     */
    public function toStd(array $forbiddenProps = []): stdClass
    {
        return parent::toStd(count($forbiddenProps) == 0 ? self::getHiddenProps() : $forbiddenProps);
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return 'User';
    }

    /**
     * Возвращает объект группы, которой принадлежит пользователь.
     * Также служит для ассоциации групп пользователей с доп. свойствами в админ меню
     *
     * @return User_Group|null
     */
    public function getParent(): ?User_Group
    {
        if (!empty($this->id)) {
            return User_Group::find($this->groupId());
        } else {
            return null;
        }
    }

	/**
	 * Проверка для избежания создания пользователей с одинаковыми логинами
     *
     * @param string $login
	 * @return bool
     * @deprecated заменен на isUnique
	 */
	public function isUserExists(string $login): bool
	{
		$user = self::query()
			->where('login', '=', $login)
			->find();
		return !is_null($user);
	}

    /**
     * Проверка пользователя на уникальность
     *
     * @param string $uniqueVal
     * @param string $uniqueField
     * @param int $exceptId
     * @return bool
     */
	public static function isUnique(string $uniqueVal, string $uniqueField = 'login', int $exceptId = 0): bool
    {
        if (empty($uniqueVal)) {
            return true;
        }
        $userQuery = self::query()->where($uniqueField, '=', $uniqueVal);
        if ($exceptId > 0) {
            $userQuery->where('id', '<>', $exceptId);
        }
        $user = $userQuery->find();
        return is_null($user);
    }

    /**
     * @return string
     */
    public function getFio(): string
    {
        $fio = $this->surname() . ' ' . $this->name();
        if (!empty($this->patronymic())) {
            $fio .= ' ' . $this->patronymic();
        }
        return $fio;
    }

	/**
	 * При сохранении пользователя необходима проверка на заполненность логина и пароля,
     * а также проверка на совпадение логина с уже существующим пользователем
     *
     * @return self
	 */
	public function save(): ?self
	{
        if (empty($this->register_date)) {
            $this->register_date = date('Y-m-d');
        }

        if (!empty($this->login)) {
            if (!self::isUnique($this->login, 'login', $this->getId())) {
                $this->_setValidateErrorStr('Пользователь с таким логином уже существует');
                return null;
            }
        }
        if (!empty($this->email)) {
            if (!self::isUnique($this->email, 'email', $this->getId())) {
                $this->_setValidateErrorStr('Пользователь с таким email уже существует');
                return null;
            }
        }

        if (empty($this->authToken())) {
            $this->authToken(uniqidReal(self::getMaxAuthTokenLength()));
        }

        Core::notify([&$this], 'before.User.save');
		if (empty(parent::save())) {
		    return null;
        }
        Core::notify([&$this], 'after.User.save');
        return $this;
	}

    /**
     * @param null $obj
     * @throws Exception
     * @return $this|void
     */
	public function delete($obj = null)
    {
        Core::notify([&$this], 'before.User.delete');
        parent::delete();
        Core::notify([&$this], 'after.User.delete');
    }

    /**
     * Статический аналог метода getCurrent для получение данных текущего авторизованного пользователя
     *
     * @return User|null
     * @deprecated
     */
    public static function current(): ?User
    {
        return User_Auth::current();
    }

	/**
	 * Метод выхода из учетной записи
     *
     * @deprecated
	 */
	static public function disauthorize(): void
	{
		User_Auth::logout();
	}

    /**
     * Проверка авторизации пользователя (объявляется в самом начале страницы)
     *
     * @param array $params - ассоциативный массив параметров -> список идентификаторов допустимых групп пользователей и проверка на свойство superuser
     * @param User|null $User - объект пользователя (по умолчанию используется авторизованный пользователь)
     * @return bool
     * @deprecated
     */
	static public function checkUserAccess(array $params, User $User = null): bool
    {
        $groups =       Core_Array::getValue($params, 'groups', null, PARAM_ARRAY);
        $forSuperuser = Core_Array::getValue($params, 'superuser', null, PARAM_BOOL);

        if (is_null($User)) {
            $CurrentUser = User_Auth::current();
        } else {
            $CurrentUser = $User;
        }
        if (is_null($CurrentUser)) {
            return false;
        }
        if (!is_null($groups) && !in_array($CurrentUser->groupId(), $groups)) {
            return false;
        }
        if ($forSuperuser == true && $CurrentUser->superuser() != 1) {
            return false;
        }
        return true;
    }

    /**
     * Метод авторизации под видом другой учетной записи
     * Особенностью является то, что сохраняется исходный id
     * и есть возможность вернуться к предыдущей учетной записи при помощи метода authRevert
     *
     * @param int $userId - id пользователя, от имени которого происходит авторизация
     * @deprecated
     */
    public static function authAs(int $userId): void
    {
        User_Auth::authAs($userId);
    }

    /**
     * Метод обратной авторизации - возвращение к предыдущей учетной записи
     * после использования метода authAs
     *
     * @deprecated
     */
    public static function authRevert(): void
    {
        User_Auth::authRevert();
    }

    /**
     * Проверка на авторизованность под чужим именем
     *
     * @return bool
     * @deprecated
     */
    public static function isAuthAs(): bool
    {
        return User_Auth::isAuthAs();
    }

    /**
     * Получение пользователя, под которым происходила самая первая рекурсивная авторизация
     *
     * @return User
     * @deprecated
     */
    public static function parentAuth(): User
    {
        return User_Auth::parentAuth();
    }

    /**
     * Получение объекта пользователя директора в независимости отстепени углубления авторизации
     * Используется в наблюдателях для определения значения свойства subordinated у различных объектах
     *
     * @return User
     */
    public function getDirector(): ?User
    {
        if ($this->groupId() == ROLE_DIRECTOR || $this->subordinated() == 0) {
            return $this;
        } else {
            return User::find($this->subordinated());
        }
    }

    /**
     * Добавление комментария к пользователю
     *
     * @param string $text
     * @param int|null $authorId
     * @param string|null $date
     * @return Comment|null
     * @throws Exception
     */
    public function addComment(string $text, int $authorId = null, string $date = null): ?Comment
    {
        Core::notify([&$this], 'before.User.addComment');

        $newComment = Comment::create($this, $text, $authorId, $date);
        if (is_null($newComment)) {
            return null;
        }

        Core::notify([&$newComment, &$this], 'after.User.addComment');
        return $newComment;
    }

    /**
     * Геттер для названия организации, которой принадлежит пользователь
     * Название организации это дначение доп. свойства директора
     *
     * @return string
     */
    public function getOrganizationName(): string
    {
        $director = $this->getDirector();
        if ($director->groupId() !== ROLE_DIRECTOR) {
            return '';
        } else {
            $property = Property_Controller::factoryByTag('organization');
            return $property->getPropertyValues($director)[0]->value();
        }
    }

    /**
     * Проверка на принадлежность объекта и пользователя одному и тому же директору
     *
     * @param $object
     * @param User|null $user
     * @return bool
     */
    public static function isSubordinate($object, User $user = null): bool
    {
        if (is_null($user)) {
            $user = User_Auth::current();
        }
        if (is_null($user)) {
            return false;
        }
        if ($user->groupId() === ROLE_ADMIN) {
            return true;
        }
        if (!is_object($object)) {
            return false;
        }
        if (!method_exists($object, 'subordinated')) {
            return true;
        }
        if ($user->getId() > 0 && $user->groupId() == ROLE_DIRECTOR && $object->subordinated() == $user->getId()) {
            return true;
        }
        if ($user->subordinated() == $object->subordinated()) {
            return true;
        }
        return false;
    }

    /**
     * Создание авторизационного токена для пользователя
     */
    public function createAuthToken(): void
    {
        $this->authToken(uniqidReal(self::getMaxAuthTokenLength()));
        $this->save();
    }

    /**
     * Геттер для авторизационного токена пользователя
     *
     * @return string
     */
    public function getAuthToken(): string
    {
        if (empty($this->authToken())) {
            $this->createAuthToken();
        }
        return $this->authToken();
    }

    /**
     * @return int
     */
    public static function getMaxAuthTokenLength(): int
    {
        return 50;
    }

    /**
     * @return bool
     */
    public function isManagementStaff(): bool
    {
        return in_array($this->groupId(), [ROLE_ADMIN, ROLE_DIRECTOR, ROLE_MANAGER]);
    }

    /**
     * @return bool
     */
    public function isDirector(): bool
    {
        return $this->groupId() === ROLE_DIRECTOR;
    }

    /**
     * @return bool
     */
    public function isTeacher(): bool
    {
        return $this->groupId() === ROLE_TEACHER;
    }

    /**
     * @return bool
     */
    public function isClient(): bool
    {
        return $this->groupId() === ROLE_CLIENT;
    }

    /**
     * @param int $groupId
     * @return string
     */
    public static function getGroupNameById(int $groupId): string
    {
        switch ($groupId)
        {
            case ROLE_ADMIN:    return 'Администратор';
            case ROLE_DIRECTOR: return 'Директор';
            case ROLE_MANAGER:  return 'Менеджер';
            case ROLE_TEACHER:  return 'Преподаватель';
            case ROLE_CLIENT:   return 'Клиент';
            default:            return 'Неизвестная группа';
        }
    }

    /**
     * @return string
     */
    public function getGroupName(): string
    {
        return self::getGroupNameById($this->groupId());
    }

    /**
     * @return int
     */
    public function getLessonTime(): int
    {
        $minutesValue = Property_Controller::factoryByTag('lesson_time')
            ->getValues($this)[0];
        return !empty($minutesValue->getId())
            ?   (int)$minutesValue->value()
            :   SCHEDULE_DEFAULT_LESSON_DURATION;
    }
}