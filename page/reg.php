<?php
require_once 'config.php';
require_once 'PHPMailer.php';
use PHPMailer\PHPMailer\PHPMailer;
//require_once 'no_cache.php';
try {
    $pdo = new pdo('mysql:host='.$config['mysql_host'].';dbname='.$config['mysql_datebase'], $config['mysql_user'], $config['mysql_password']);
} catch (PDOException $e){
    echo 'Ошибка при подключении к базе данных';
    exit();
}
function generateCode($length=6) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPRQSTUVWXYZ0123456789";
    $code = "";
    $clen = strlen($chars) - 1;
    while (strlen($code) < $length) {
        $code .= $chars[mt_rand(0,$clen)];
    }
    return $code;
}
$mail = new PHPMailer(false);                              // Passing `true` enables exceptions
$mail->CharSet = 'utf-8';
$message = '';
$vlogin = '';
$vemail = '';
//--------------------------------------------------------------------------------------------------------------------
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
/*if(isset($_POST['submit']))
{
    $login = isset($_POST['login'])? htmlspecialchars($_POST['login']) : false;
    $email = isset($_POST['email'])? htmlspecialchars($_POST['email']) : false;
    $password = isset($_POST['password'])? md5($_POST['password']) : false;
    if(!empty($_POST['login']) && !empty($_POST['email']) && !empty($_POST['password']))
    {
        if ($login !== false && $email !== false && $password !== false && !empty($login) && !empty($email) && !empty($password))
        {
            $stmt = $pdo->prepare("SELECT COUNT(1) as `count` FROM `ghettowar_users` WHERE `login`=:login");
            $stmt->execute([':login' => $login]);
            $check = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($check['count'] == 0)
            {
                $stmt = $pdo->prepare("SELECT COUNT(1) as `count` FROM `ghettowar_users` WHERE `email`=:email");
                $stmt->execute([':email' => $email]);
                $check = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($check['count'] == 0)
                {
                    $hash = generateCode(32);
                    $data = time();
                    $cookie_time = (time() + 3600 * 24);
                    $stmt = $pdo->prepare("INSERT INTO `ghettowar_users` (login, pass, email, date_reg, balance, cookie_session, cookie_time, duel_nick, duel_online) VALUES (:login, :pass, :email, :datareg, 0, :cookie, :cookietime, NULL, NULL)");
                    $stmt->execute([':login' => $login, ':pass' => $password, ':email' => $email, ':datareg' => $data, 'cookie' => $hash, 'cookietime' => $cookie_time]);
                    setcookie("session", $hash, $cookie_time, "/");
                    header('Location: main.php');
                    exit();
                } else $message = 'Пользователь с таким email существует';

            } else $message = 'Пользователь с таким логином существует';
        }
    }    else $message = 'Введены не все поля';
}*/
if(isset($_POST['submit']))
{
    if(!empty($_POST['login']) && !empty($_POST['email']) && !empty($_POST['password']))
    {
        if(preg_match('/^[a-zA-Z][a-zA-Z0-9-_\.]{1,20}/', $_POST['login']))
        {
            $vlogin = $_POST['login'];
            if(filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
            {
                $vemail = $_POST['email'];
                if(preg_match('/^(?=.*\d)(?=.*[a-z])(?!.*\s).*$/', $_POST['password']))
                {
                    $login = htmlspecialchars($_POST['login']);
                    $email = htmlspecialchars($_POST['email']);
                    $password = md5($_POST['password']);
                    $vlogin = '';
                    $vemail = '';
                    $stmt = $pdo->prepare("SELECT `login`, `email` FROM `ghettowar_users` WHERE `login`=:login OR `email`=:email LIMIT 1");
                    $stmt->execute([':login' => $login, ':email' => $email]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    if($result['login'] != $login)
                    {
                        if($result['email'] != $email)
                        {
                           //----------Блок регистрации----------
                            $hash = generateCode(32);
                            $email_validate = generateCode(32);
                            $data = time();
                            $cookie_time = (time() + 3600 * 24);
                            $stmt = $pdo->prepare("INSERT INTO `ghettowar_users` (login, pass, email, email_validate, date_reg, balance, cookie_session, cookie_time, duel_nick, duel_online) VALUES (:login, :pass, :email, :email_validate, :datareg, 0, :cookie, :cookietime, NULL, NULL)");
                            $stmt->execute([':login' => $login, ':pass' => $password, ':email' => $email, 'email_validate' => $email_validate, ':datareg' => $data, 'cookie' => $hash, 'cookietime' => $cookie_time]);
                            setcookie("session", $hash, $cookie_time, "/");
                                //Recipients
                                $mail->setFrom('admin@ghettowar.ru', 'Administrator');
                                $mail->addAddress($email, $login);     // Add a recipient
                                $mail->addReplyTo('info@@ghettowar.ru', 'Information');

                                //Content
                                $mail->isHTML(true);                                  // Set email format to HTML
                                $mail->Subject = 'Подтверждение Email';
                                $mail->Body    = 'Подтверждение Email пользователя: '.$login.'<br>Для этого перейдите по ссылке: <a href="'.$_SERVER['HTTP_HOST'].'/page/main.php'.'?evalidate='.$email_validate.'">Подтвердить</a>';
                                $mail->AltBody = 'Подтверждение Email пользователя: '.$login.' Для этого перейдите по ссылке: '.$_SERVER['HTTP_HOST'].'/page/main.php'.'?evalidate='.$email_validate;
                                $mail->send();
                            header('Location: main.php');
                            exit();
                            //-----------------------------------
                        } else $message = 'Пользователь с таким email существует';
                    } else $message = 'Пользователь с таким логином уже существует';
                } else $message = 'Пароль должен начинаться с маленькой буквы и иметь в себе цифры!';
            } else $message = 'Email некорректен';
        } else $message = 'Логин должен быть от 2 до 20 символов и начинаться с буквы!';
    } else $message = 'Введены не все поля';
}





$template = str_replace(['{%title%}'],['Регистрация'],file_get_contents('../template/header.tpl')).str_replace(['{%action_page%}', '{%message%}', '{%vlogin%}', '{%vemail%}'], [$_SERVER['PHP_SELF'], $message, $vlogin, $vemail], file_get_contents('../template/reg.tpl')).file_get_contents('../template/footer.tpl');
exit($template);