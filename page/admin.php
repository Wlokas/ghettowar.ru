<?php
require_once 'config.php';
try {
    $pdo = new pdo('mysql:host='.$config['mysql_host'].';dbname='.$config['mysql_datebase'], $config['mysql_user'], $config['mysql_password']);
} catch (PDOException $e){
    echo 'Ошибка при подключении к базе данных';
    exit();
}
/*
 * 0 - главная страница
 * 1 - Дуели
 * 2 - Аккаунты
 */
$status_tpl = 0;
$template = '';
$duel_list = '';
$user_id1 = '';
$user_id2 = '';
$nick_1 = '';
$nick_2 = '';
$rate = '';
$status = '';
$input_disabled = false;
if(isset($_COOKIE['session'])) {
    $session = $_COOKIE['session'];
    $stmt = $pdo->prepare("SELECT * FROM `ghettowar_users` WHERE `cookie_session`=:cookie");
    $stmt->execute([':cookie' => $session]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($account['login'] != 'admin') {
        header('Location: /error404.html');
        exit();
    }
}
else
{
    header('Location: /error404.html');
    exit();
}

if(isset($_GET['duels']) && empty($_GET['duels']))
{
    $stmt = $pdo->prepare("SELECT * FROM `ghettowar_duels` WHERE `win` = '0' OR `win` = '4'");
    $stmt->execute();
    if($stmt->rowCount() != 0) {
        $player2 = 'Отсутствует..';
        $duel_status = '';
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['user_id2'] != NULL) $player2 = 'id' . $row['user_id2'];
            switch ($row['win']) {
                case 0:
                    $duel_status = 'Ожидание игроков..';
                    break;
                case 4:
                    $duel_status = 'Ожидание подключение игроков..';
                    break;
            }
            $duel_list .= 'Создатель дуели: id' . $row['user_id1'] . ' | Второй игрок: ' . $player2 . ' | Ставка: ' . $row['win_balance'] . '$ | Статус: ' . $duel_status . ' | <a href="' . $_SERVER['PHP_SELF'] . '?duels=' . $row['duel_id'] . '">Изменить</a><br>';
        }
    } else $duel_list = 'Дуелей не существует..';
    $status_tpl = 1;
}
elseif (isset($_GET['duels']) && !empty($_GET['duels']))
{
    $stmt = $pdo->prepare("SELECT * FROM `ghettowar_duels` WHERE `duel_id`=:id LIMIT 1");
    $stmt->execute([':id' => $_GET['duels']]);
    $duel = $stmt->fetch(PDO::FETCH_ASSOC);
    $user_id1 = $duel['user_id1'];
    $user_id2 = $duel['user_id2'];
    $nick_1 = $duel['nick1'];
    $nick_2 = $duel['nick2'];
    $rate = $duel['win_balance'];
    $status = $duel['win'];
    if($user_id2 == NULL) { $user_id2 = 'Отсутствует..'; $input_disabled = true; }
    if($nick_2 == '0') $nick_2 = 'Отсутствует..';
    switch ($status)
    {
        case 0: $status = 'Ожидание игрока..'; break;
        case 4: $status = 'Ожидане подключение игроков..'; break;
        case 3: $status = 'Идет игра..'; break;
    }
    $status_tpl = 2;
}


if($status_tpl == 0)
{
    $template = str_replace(['{%title%}'],['Адммин панель'],file_get_contents('../template/header.tpl')).
        str_replace(['{%PHP_SELF%}'], [$_SERVER['PHP_SELF']], file_get_contents('../template/admin.tpl')).
        file_get_contents('../template/footer.tpl');
}
elseif ($status_tpl == 1)
{
    $template = str_replace(['{%title%}'],['Список дуелий'],file_get_contents('../template/header.tpl')).
        str_replace(['{%DUELS%}'], [$duel_list], file_get_contents('../template/admin_duels.tpl')).
        file_get_contents('../template/footer.tpl');
}
elseif ($status_tpl == 2)
{
    if($input_disabled == true) $input_disabled_text = 'disabled';
    $template = str_replace(['{%title%}'],['Редактирование дуели'],file_get_contents('../template/header.tpl')).
        str_replace(['{%PHP_REQUEST%}','{%DISABLED%}', '{%USER_ID1%}', '{%USER_ID2%}', '{%RATE%}', '{%STATUS%}', '{%NICK_1%}', '{%NICK_2%}'], [$_SERVER['REQUEST_URI'],$input_disabled_text, $user_id1, $user_id2, $rate, $status, $nick_1, $nick_2], file_get_contents('../template/admin_duels_red.tpl')).
        file_get_contents('../template/footer.tpl');
}
exit($template);