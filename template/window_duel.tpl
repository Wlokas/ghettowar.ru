<script>
    function show() {
        $.ajax({
            type: "POST",
            url: "main.php",
            data: "duelinfo={%ID_DUEL%}",
            success: function (html) {
                $("#window_duel").html(html);
            }
        });
    }
    $(document).ready(function(){
        show();
        setInterval('show()',1000);
    });
</script>
<div id="window_duel">
    <span>Подключение..</span>
</div>
