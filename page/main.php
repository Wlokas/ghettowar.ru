<?php
require_once 'config.php';
try {
    $pdo = new pdo('mysql:host='.$config['mysql_host'].';dbname='.$config['mysql_datebase'], $config['mysql_user'], $config['mysql_password']);
} catch (PDOException $e){
    echo 'Ошибка при подключении к базе данных';
    exit();
}
function generateNick($length=15) {
    $nick = 'User_';
    while (strlen($nick) < $length) {
        $nick .= mt_rand(0, 9);
    }
    return $nick;
}
/*
 * 0 - Неавторизован
 * 1 - Авторизован
 * 2 - Создание дуели
 * 3 - Окно дуеля
 */
$template = ''; // Глобальная переменная
$account = ''; // Глобальная переменная
$message = '';
$status_tpl = 0; // Глобальная переменная
$login = ''; // Глобальная переменная
$connect_nick = '';
$balance = ''; // Глобальная переменная
$button_add_duel = '<a href="'.$_SERVER['PHP_SELF'].'?add_duel">Создать дуель</a>'; // Глобальная переменная
if(isset($_GET['logout']) && isset($_COOKIE['session']))
{
    $session = $_COOKIE['session'];
    $stmt = $pdo->prepare("SELECT COUNT(1) as `count` FROM `ghettowar_users` WHERE `cookie_session`=:cookie");
    $stmt->execute([':cookie' => $session]);
    $check = $stmt->fetch(PDO::FETCH_ASSOC);
    if($check['count'] == 1)
    {
        $stmt = $pdo->prepare("UPDATE `ghettowar_users` SET `cookie_time`='0' WHERE `cookie_session`=:cookie");
        $stmt->execute([':cookie' => $session]);
        setcookie("session","0",time()-1, "/");
        header('Location: /');
    }
}
if(isset($_COOKIE['session']))
{
    $session = $_COOKIE['session'];
    $stmt = $pdo->prepare("SELECT * FROM `ghettowar_users` WHERE `cookie_session`=:cookie");
    $stmt->execute([':cookie' => $session]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    if($account['cookie_time'] > time())
    {
        $login = $account['login'];
        $balance = $account['balance'];
        $status_tpl = 1;
    }
    else setcookie("session","0",time()-1, "/");
}
//-------------------------------------Дуель------------------------------------------
if(isset($_POST['duelinfo']))
{
    $duel_id = $_POST['duelinfo'];
    $duel_ip = ['127.0.0.1', '168.192.0.1']; // IP сереров
    $status = '0';
    $stmt = $pdo->prepare("SELECT * FROM `ghettowar_duels` WHERE duel_id = :id");
    $stmt->execute([':id' => $duel_id]);
    $duel_info = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare("SELECT `login` FROM `ghettowar_users` INNER JOIN `ghettowar_duels` WHERE `ghettowar_users`.duel_nick = `ghettowar_duels`.nick1 OR `ghettowar_users`.duel_nick = `ghettowar_duels`.nick2 LIMIT 2");
    $stmt->execute();
    $player_login = $stmt->fetchAll(PDO::FETCH_ASSOC);
    switch ($duel_info['win'])
    {
        case 0: $status = 'Ожидание игрока..'; break;
        case 4: $status = 'Ожидане подключение игроков..'; break;
        case 3: $status = 'Идет игра..'; break;
        case 2: $status = 'Дуель выйграл: '.$player_login[1]['login']; break;
        case 1: $status = 'Дуель выйграл: '.$player_login[0]['login']; break;
    }
    if($duel_info['user_id2'] == NULL)
    {
    $template_duel = '-----Дуель----<br>'.
                    '1-ый игрок: '.$player_login[0]['login'].
                    '<br>2-ой игрок: Ожидание..'.
                    '<br>Ставка: '.$duel_info['win_balance'].
                    '<br>Статус: '.$status;
    }
    elseif($duel_info['user_id2'] != NULL)
    {
        if($account['id'] == $duel_info['user_id2']) $connect_nick = $duel_info['nick2'];
        if($account['id'] == $duel_info['user_id1']) $connect_nick = $duel_info['nick1'];
        if($duel_info['win'] >= 3)
        {
            $template_duel = '-----Дуель----<br>' .
                '1-ый игрок: ' . $player_login[0]['login'] .
                '<br>2-ой игрок: ' . $player_login[1]['login'] .
                '<br>Ставка: ' . $duel_info['win_balance'] .
                '<br>Статус: ' . $status .
                '<br>Ваш ник для подключение на сервер: <input type="text" disabled value="' . $connect_nick .'">'.
                '<br>IP Сервера: <input type="text" disabled value="' . $duel_ip[$duel_info['ip_server']] . '">';
        }
        elseif ($duel_info['win'] <= 2 && $duel_info['win'] != 0)
        {
            $template_duel = '-----Дуель Окончена----<br>' .
                '1-ый игрок: ' . $player_login[0]['login'] .
                '<br>2-ой игрок: ' . $player_login[1]['login'] .
                '<br>Ставка: ' . $duel_info['win_balance'] .
                '<br>Статус: ' . $status;
        }
    }
    exit($template_duel);
}
//------------------------------------------------------------------------------------
if(isset($_GET['add_duel']) && isset($_COOKIE['session']) && $status_tpl == 1) $status_tpl = 2;
if(isset($_POST['create_duel']))
{
    if(!empty($_POST['rate']))
    {
        if (filter_var($_POST['rate'], FILTER_VALIDATE_INT))
        {
            if($_POST['rate'] <= $account['balance'])
            {
                $nick1 = generateNick();
                $rate = $_POST['rate'];
                $balance = $account['balance'] - $rate;
                $stmt = $pdo->prepare("INSERT INTO `ghettowar_duels` (ip_server, nick1, nick2,user_id1, user_id2, win_balance, win) VALUES ('0', :nick1, '0', :id1, NULL, :rate, '0')");
                $stmt->execute([':nick1' => $nick1, ':id1' => $account['id'], ':rate' => $rate]);
                $stmt = $pdo->prepare("UPDATE `ghettowar_users` SET `duel_nick`=:nick1, `balance`=:balance WHERE `id`=:id");
                $stmt->execute([':nick1' => $nick1, ':id' => $account['id'], ':balance' => $balance]);
                $status_tpl = 3;
            }
        } else $message = 'Укажите целое число!';
    } else $message = 'Не указана ставка';
}
if(isset($_COOKIE['session']) && $account['duel_nick'] != NULL && !isset($_GET['connectduel']))
{
    $stmt = $pdo->prepare("SELECT `duel_id` FROM `ghettowar_duels` WHERE user_id1=:id1 OR user_id2=:id1");
    $stmt->execute([':id1' => $account['id']]);
    $duel_id = $stmt->fetch(PDO::FETCH_ASSOC);
    $status_tpl = 3;
    header('Location: '.$_SERVER['PHP_SELF'].'?connectduel='.$duel_id['duel_id']);
    exit();
}
elseif(isset($_GET['connectduel']) && !empty($_GET['connectduel'])) $status_tpl = 3;
//----------------------------------------Вывод шаблонов---------------------------------------
if($status_tpl == 0)
{
    $template = str_replace(['{%title%}'],['Главная Страница'],file_get_contents('../template/header.tpl')).
        file_get_contents('../template/formregauth.tpl').
        file_get_contents('../template/footer.tpl');
}
elseif($status_tpl == 1)
{
    $template = str_replace(['{%title%}'],['Главная Страница'],file_get_contents('../template/header.tpl')).
        str_replace(['{%login%}', '{%balance%}', '{%PHP_SELF%}'],[$login, $balance, $_SERVER['PHP_SELF']],file_get_contents('../template/formprofile.tpl')).
        '</br>'.
        str_replace(['{%DUEL_ADD%}'],[$button_add_duel], file_get_contents('../template/main.tpl')).
        file_get_contents('../template/footer.tpl');
}
elseif ($status_tpl == 2)
{
    $template = str_replace(['{%title%}'],['Создание дуели'],file_get_contents('../template/header.tpl')).
        str_replace(['{%login%}', '{%balance%}', '{%PHP_SELF%}'],[$login, $balance, $_SERVER['PHP_SELF']],file_get_contents('../template/formprofile.tpl')).
        '</br>'.
        str_replace(['{%PHP_SELF%}', '{%message%}'],[$_SERVER['PHP_SELF'], $message], file_get_contents('../template/add_duel.tpl')).
        file_get_contents('../template/footer.tpl');
}
elseif ($status_tpl == 3)
{
    $duel_id = $_GET['connectduel'];
    $stmt = $pdo->prepare("SELECT `duel_id`, `user_id1`,`user_id2`, `win_balance`, `win` FROM `ghettowar_duels` WHERE duel_id=:duelid");
    $stmt->execute(['duelid' => $duel_id]);
    $duel = $stmt->fetch(PDO::FETCH_ASSOC);
    if($stmt->rowCount() != 0)
    {
        if($duel['user_id1'] != $account['id'] && $duel['user_id2'] != $account['id'] && $account['balance'] >= $duel['win_balance'] && $duel['win'] == '0')
        {
                $nick2 = generateNick();
                $balance = $account['balance'] - $duel['win_balance'];
                $stmt = $pdo->prepare("UPDATE ghettowar_users u, ghettowar_duels d SET d.win = '4', d.nick2=:nick2, u.duel_nick=:nick2, d.user_id2=:id, u.balance=:balance WHERE u.id=:id AND d.duel_id=:duelid");
                $stmt->execute([':nick2' => $nick2, ':id' => $account['id'], 'duelid' => $duel_id, ':balance' => $balance]);
                $template = str_replace(['{%title%}'], ['Дуель'], file_get_contents('../template/header.tpl')) .
                    str_replace(['{%login%}', '{%balance%}', '{%PHP_SELF%}'], [$login, $balance, $_SERVER['PHP_SELF']], file_get_contents('../template/formprofile.tpl')) .
                    '</br>' .
                    str_replace(['{%ID_DUEL%}', '{%PHP_SELF%}'], [$duel_id, $_SERVER['PHP_SELF']], file_get_contents('../template/window_duel.tpl')) .
                    file_get_contents('../template/footer.tpl');
        }
        elseif($duel['user_id1'] == $account['id'] || $duel['user_id2'] == $account['id'])
        {
            $template = str_replace(['{%title%}'],['Дуель'],file_get_contents('../template/header.tpl')).
                str_replace(['{%login%}', '{%balance%}', '{%PHP_SELF%}'],[$login, $balance, $_SERVER['PHP_SELF']],file_get_contents('../template/formprofile.tpl')).
                '</br>'.
                str_replace(['{%ID_DUEL%}', '{%PHP_SELF%}'],[$duel_id, $_SERVER['PHP_SELF']], file_get_contents('../template/window_duel.tpl')).
                file_get_contents('../template/footer.tpl');
        }
    } else { header('Location: '.$_SERVER['PHP_SELF']); exit(); }
    /*elseif(isset($_GET['connectduel']) && !empty($_GET['connectduel']))
    {
        $duel_id = $_GET['connectduel'];
        $nick2 = generateNick();
        $stmt = $pdo->prepare("UPDATE ghettowar_users u, ghettowar_duels d SET d.win = '3', d.nick2=:nick2, u.duel_nick=:nick2 WHERE u.id=:id AND d.duel_id=:duelid");
        $stmt->execute([':nick2' => $nick2, ':id' => $account['id'], ':duelid' => $duel_id]);
    }
    $template = str_replace(['{%title%}'],['Дуель'],file_get_contents('../template/header.tpl')).
        str_replace(['{%login%}', '{%balance%}', '{%PHP_SELF%}'],[$login, $balance, $_SERVER['PHP_SELF']],file_get_contents('../template/formprofile.tpl')).
        '</br>'.
        str_replace(['{%ID_DUEL%}'],[$duel_id], file_get_contents('../template/window_duel.tpl')).
        file_get_contents('../template/footer.tpl');*/
}
if(isset($_COOKIE['session']) && $account['login'] == 'admin') $template .= '<br><a href="admin.php">Войти в админ панель</a>';
exit($template);