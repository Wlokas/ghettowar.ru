<div>
    <form method="post" action="{%PHP_REQUEST%}">
        <span>Создатель дуели:</span><input type="text" value="{%USER_ID1%}"><br>
        <span>Второй игрок:</span><input type="text" value="{%USER_ID2%}" {%DISABLED%}><br>
        <span>Ник первого игрока:</span><input type="text" value="{%NICK_1%}"><br>
        <span>Ник второго игрока:</span><input type="text" value="{%NICK_2%}" {%DISABLED%}><br>
        <span>Ставка:</span><input type="text" value="{%RATE%}"><br>
        <span>Статус:</span><input type="text" value="{%STATUS%}" disabled><br>
        <input type="submit" value="Изменить">
    </form>
</div>