<?php
require_once 'config.php';
//require_once 'no_cache.php';
try {
    $pdo = new pdo('mysql:host='.$config['mysql_host'].';dbname='.$config['mysql_datebase'], $config['mysql_user'], $config['mysql_password']);
} catch (PDOException $e){
    echo 'Ошибка при подключении к базе данных';
    exit();
}
function generateCode($length=6) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHI JKLMNOPRQSTUVWXYZ0123456789";
    $code = "";
    $clen = strlen($chars) - 1;
    while (strlen($code) < $length) {
        $code .= $chars[mt_rand(0,$clen)];
    }
    return $code;
}
$message = '';
//-----------------------------------------------------------------------------------------------------------------------
if(isset($_COOKIE['session']))
{
    $session = $_COOKIE['session'];
    $stmt = $pdo->prepare("SELECT COUNT(1) as `count` FROM `ghettowar_users` WHERE `cookie_session`=:cookie LIMIT 1");
    $stmt->execute([':cookie' => $session]);
    $check = $stmt->fetch(PDO::FETCH_ASSOC);
    if($check['count'] == 1)
    {
        header('Location: main.php');
        exit();
    }
    else setcookie("session","0",time()-1, "/");
}
//-----------------------------------------------------------------------------------------------------------------------
/*if(isset($_POST['submit']))
{
    $login = $_POST['login'];
    $password = md5($_POST['password']);
    $cookie = generateCode(32);
    $cookietime = time() + 3600 * 24;
    $stmt = $pdo->prepare("UPDATE `ghettowar_users` SET `cookie_session`=:cookiesession, `cookie_time`=:cookietime WHERE `login`=:login AND `pass`=:pass LIMIT 1");
    $stmt->execute([':cookiesession' => $cookie, ':cookietime' => $cookietime, ':login' => $login, ':pass' => $password]);
    $result = $stmt->rowCount();
    if($result)
    {
        setcookie("session", $cookie, $cookietime, "/");
        header('Location: main.php');
        exit();
    }
    else $message = 'Неверный логин или пароль';
}*/
if(isset($_POST['submit']))
{
    if(!empty($_POST['login']) && !empty($_POST['password']))
    {
        if(preg_match('/^[a-zA-Z][a-zA-Z0-9-_\.]{1,20}/', $_POST['login']))
        {
            if(preg_match('/^(?=.*\d)(?=.*[a-z])(?!.*\s).*$/', $_POST['password']))
            {
                $login = htmlspecialchars($_POST['login']);
                $password = md5($_POST['password']);
                $stmt = $pdo->prepare("SELECT `login`, `pass` FROM `ghettowar_users` WHERE `login`=:login AND `pass`=:pass LIMIT 1");
                $stmt->execute([':login' => $login, ':pass' => $password]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if($result['login'] == $login)
                {
                    if($result['pass'] == $password)
                    {
                        //-----------Блок Авторизации-----------
                        $cookie = generateCode(32);
                        $cookietime = time() + 3600 * 24;
                        $stmt = $pdo->prepare("UPDATE `ghettowar_users` SET `cookie_session`=:cookiesession, `cookie_time`=:cookietime WHERE `login`=:login AND `pass`=:pass LIMIT 1");
                        $stmt->execute([':cookiesession' => $cookie, ':cookietime' => $cookietime, ':login' => $login, ':pass' => $password]);
                        setcookie("session", $cookie, $cookietime, "/");
                        header('Location: main.php');
                        exit();
                        //--------------------------------------
                    } else $message = 'Неверный пароль!';
                } else $message = 'Неверный логин!';
            } else $message = 'Неверный пароль!';
        } else $message = 'Неверный логин!';
    } else $message = 'Не все поля заполнены!';
}


$template = str_replace(['{%title%}'],['Авторизация'],file_get_contents('../template/header.tpl')) .
    str_replace(['{%action_page%}', '{%message%}'], [$_SERVER['PHP_SELF'], $message], file_get_contents('../template/auth.tpl')) .
    file_get_contents('../template/footer.tpl');
exit($template);