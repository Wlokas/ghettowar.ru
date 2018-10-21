<?php
require_once 'config.php';
require_once 'PHPMailer.php';
use PHPMailer\PHPMailer\PHPMailer;
$mail = new PHPMailer(false);
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
if(isset($_COOKIE['session']))
{
    $session = $_COOKIE['session'];
    $stmt = $pdo->prepare("SELECT * FROM `ghettowar_users` WHERE `cookie_session`=:cookie");
    $stmt->execute([':cookie' => $session]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    if($stmt->rowCount() == NULL && $account['email_validate'] == '1')
    {
        exit(file_get_contents('../error404.html'));
    }
}
else exit(file_get_contents('../error404.html'));
$status_tpl = 0;
$message = '';
if(isset($_GET['change']) && empty($_GET['change']))
{
    $status_tpl = 1;
}
if(isset($_GET['email']) && !empty($_GET['email']))
{
    $email = !empty($_GET['email'])? $_GET['email'] : false;
    if($email !== false)
    {
        if(filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            $email_validate = generateCode(32);
            $stmt = $pdo->prepare("UPDATE `ghettowar_users` SET `email`=:email, `email_validate`=:email_validate WHERE `id`=:id");
            $stmt->execute([':email' => $email, ':email_validate' => $email_validate, ':id' => $account['id']]);
            $mail->setFrom('admin@ghettowar.ru', 'Administrator');
            $mail->addAddress($email, $account['login']);     // Add a recipient
            $mail->addReplyTo('info@@ghettowar.ru', 'Information');

            //Content
            $mail->isHTML(true);                                  // Set email format to HTML
            $mail->Subject = 'Подтверждение Email';
            $mail->Body    = 'Подтверждение Email пользователя: '.$account['login'].'<br>Для этого перейдите по ссылке: <a href="'.$_SERVER['HTTP_HOST'].'/page/main.php'.'?evalidate='.$email_validate.'">Подтвердить</a>';
            $mail->AltBody = 'Подтверждение Email пользователя: '.$account['login'].' Для этого перейдите по ссылке: '.$_SERVER['HTTP_HOST'].'/page/main.php'.'?evalidate='.$email_validate;
            $mail->send();
            header('Location: main.php');
            exit();
        }
        else
        {
            $message = 'Введите корректный email!';
            $status_tpl = 1;
        }
    }
    else
        {
            $message = 'Введите свой email!';
            $status_tpl = 1;
    }

}
if(isset($_GET['repeat']))
{
    $email_validate = generateCode(32);
    $email = $account['email'];
    $stmt = $pdo->prepare("UPDATE `ghettowar_users` SET `email_validate`=:email_validate WHERE `id`=:id");
    $stmt->execute([':email_validate' => $email_validate, ':id' => $account['id'] ]);
    $mail->setFrom('admin@ghettowar.ru', 'Administrator');
    $mail->addAddress($email, $account['login']);     // Add a recipient
    $mail->addReplyTo('info@@ghettowar.ru', 'Information');

    //Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = 'Подтверждение Email';
    $mail->Body    = 'Подтверждение Email пользователя: '.$account['login'].'<br>Для этого перейдите по ссылке: <a href="'.$_SERVER['HTTP_HOST'].'/page/main.php'.'?evalidate='.$email_validate.'">Подтвердить</a>';
    $mail->AltBody = 'Подтверждение Email пользователя: '.$account['login'].' Для этого перейдите по ссылке: '.$_SERVER['HTTP_HOST'].'/page/main.php'.'?evalidate='.$email_validate;
    $mail->send();
    header('Location: main.php');
    exit();
}
if($status_tpl == 0) $template = file_get_contents('../error404.html');
elseif($status_tpl == 1)
{
    $template = str_replace(['{%title%}'], ['Изменение Email'], file_get_contents('../template/header.tpl')) .
        str_replace(['{%MESSAGE%}'], [$message], file_get_contents('../template/email_change.tpl')) .
        file_get_contents('../template/footer.tpl');
}
exit($template);

