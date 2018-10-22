<!--Content-->
<div>
    {%message%}
    <form method="post" action="{%action_page%}">
        <p><label for="login">Логин:</label><input type="text" name="login" id="login" placeholder="Login"></p>
        <p><label for="password">Пароль:</label><input type="password" name="password" id="password" placeholder="password"></p>
        <p><label for="remember">Запомнить меня</label><input id="remember" type="checkbox" name="remember" value="yes"></p>
        <button name="submit">Войти</button>
    </form>
</div>
<!--end-->