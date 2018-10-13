<?php
require_once 'config.php';
try {
    $pdo = new pdo('mysql:host='.$config['mysql_host'].';dbname='.$config['mysql_datebase'], $config['mysql_user'], $config['mysql_password']);
} catch (PDOException $e){
    echo 'Ошибка при подключении к базе данных';
    exit();
}
$i = 0;
$result = $pdo->query("SELECT `duel_id`, `nick1`, `win_balance`, `win` FROM `ghettowar_duels` WHERE `win`= '0'");
if($result->rowCount() != 0)
{
    $nick_query = $pdo->query("SELECT `login` FROM `ghettowar_users` INNER JOIN `ghettowar_duels` ON `ghettowar_users`.`duel_nick` = `ghettowar_duels`.`nick1` WHERE `ghettowar_duels`.`win` = 0 ORDER BY `ghettowar_duels`.duel_id");
    $nick = $nick_query->fetchAll(PDO::FETCH_ASSOC);
    while ($row = $result->fetch(PDO::FETCH_ASSOC))
    {
        echo 'Создатель дуели: '.$nick[$i]['login'].' | ставка: '.$row['win_balance'].' | <a href="'.'main.php'.'?connectduel='.$row['duel_id'].'">Играть!</a>'.'</br>';
        $i++;
    }
    /*while ($row = $result->fetch(PDO::FETCH_ASSOC))
    {
        echo 'Создатель дуели: '.$row['nick1'].' | ставка: '.$row['win_balance'].' | <a href="'.$_SERVER['PHP_SELF'].'?connectduel='.$row['id'].'">Играть!</a>'.'</br>';
    }*/
} else echo 'Дуели не найдены';
