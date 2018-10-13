<div>
    {%message%}
    <form method="post" name="create_duel" action="{%PHP_SELF%}">
        <input type="text" name="rate" placeholder="Ставка">
        <input type="submit" name="create_duel" value="Создать дуель">
    </form><br>
    <a href="{%PHP_SELF%}">Назад</a>
</div>