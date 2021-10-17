<?php

/**
 * Данные пользователя, которые будут проверяться
 */
$userRights = [
    'status' => 'online', // Указатель на статус пользвателя
    'role' => 'manager', // Указатель на роль пользователя
    'permission' => [
        'view' => true,
        'change' => true,
        'delete' => false,
    ], // Указатель на разрешения
    'attempsNumber' => 0,
];

/**
 * Class Chain
 *
 * Класс цепочки
 */
class Chain {

    public $startChainLink = null;

    //В метод передаются объекты звеньев цепи
    //Метод проверяет все звенья на соответствие типу
    //А также задает каждому звену цепи, ссылку на следующее звено
    public function createChain(...$chainLinks)
    {
        if (is_array($chainLinks)) {
            foreach ($chainLinks as $chainLink) {
                if (!($chainLink instanceof ChainLink)) {
                    echo 'Неверный объект цепи обязанностей!';
                    exit;
                }
            }
        }

        for($i=0; $i < count($chainLinks); $i++) {
            if (isset($chainLinks[$i+1])) {
                $chainLinks[$i]->nextChainLink = $chainLinks[$i + 1];
            }
        }

        $this->startChainLink = $chainLinks[0];
    }

    //Запуск цепи, а точнее первого звена цепи, которое потянет за собой все остальные
    public function check($userRights)
    {
        return $this->startChainLink->__check($userRights);
    }
}

/**
 * Class ChainLink
 *
 * Родитель звеньев цепи
 */
abstract class ChainLink
{
    //Ссылка на следующее звено
    public $nextChainLink = null;

    //Метод, который содержит код проверки. Должен быть реализован в каждом звене
    abstract function check($userRights);

    //Метод запуска звена цепи. Дополняет метод check запуском следующего звена
    public function __check($userRights)
    {
        $this->check($userRights);

        if ($this->nextChainLink != null) {
            $this->nextChainLink->__check($userRights);
        }
    }
}

/**
 * Class CheckStatus
 *
 * Звено проверки статуса
 */
class CheckStatus extends ChainLink {

    public function check($userRights)
    {
        // Проверка статуса
        if ($userRights['status'] != 'online') {
            echo 'Доступно только для авторизованных пользователей';
            exit;
        }
    }
}

/**
 * Class CheckRole
 *
 * Звено проверки роли
 */
class CheckRole extends ChainLink {

    /**
     * Роли, которым доступен объект запроса пользователя
     */
    public $allowedRoles = [
        'manager',
        'administrator'
    ];

    public function check($userRights)
    {
        // Проверка роли
        if (!in_array($userRights['role'], $this->allowedRoles)) {
            echo 'Не доступно для вашей роли';
            exit;
        }
    }
}

/**
 * Class CheckPermission
 *
 * Звено проверки разрешений
 */
class CheckPermission extends ChainLink {

    public function check($userRights)
    {
        // Проверка разрешений
        if (!$userRights['permission']['change']) {
            echo 'Нет разрешений';
            exit;
        }
    }
}

/**
 * Class CheckAttempsNumber
 *
 * Звено проверки количества попыток входа
 */
class CheckAttempsNumber extends ChainLink {

    public function check($userRights)
    {
        // Провека количества попыток запроса
        if ($userRights['attempsNumber'] > 10) {
            echo 'Превышен лимит попыток. Попробуйте снова в следующей жизни!';
            exit;
        }
    }
}

/**
 * Class Component
 *
 * Класс приложения
 */
class Component {

    public function __construct($userRights)
    {
        $chain = new Chain();

        //Создаем цепочку обязанностей, передав нужные классы проверки данных
        //Благодаря такому подходу, мы сделали цепочку обязанностей более гибкой
        //Мы можем задавать любое количество проверяющих классов - звеньев цепи
        //А также регулировать последовательность проверок
        $chain->createChain(
            new CheckStatus(),
            new CheckRole(),
            new CheckPermission(),
            new CheckAttempsNumber()
        );

        //Запускаем проверку
        $chain->check($userRights);

        //Если проверка пройдена сработает этот метод
        echo $this->nextWork();
    }

    public function nextWork()
    {
        return 'Дальнейшие действия, которые будут произведены, в случае успешного прохождения проверки!';
    }
}

//Инициализация приложения
$app = new Component($userRights);
