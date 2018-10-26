<!--Content-->
<div>
    {%message%}
    <form method="post" action="{%action_page%}">
        <input type="text" name="login" placeholder="login" value="{%vlogin%}"><br>
        <input type="text" name="email" placeholder="email" value="{%vemail%}"><br>
        <input type="password" name="password" placeholder="password"><br>
        <input type="submit" name="submit" value="Зарегистрироваться">
    </form>
    <p>Уже есть аккаунт? <a href="/page/auth.php">Авторизоваться!</a></p>
</div>
<!--end-->