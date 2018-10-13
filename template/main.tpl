<!--Content-->
<div>
{%DUEL_ADD%}
    <script>
        function show()
        {
            $.ajax({
                url: "duels_showlist.php",
                cache: false,
                success: function(html){
                    $("#duels").html(html);
                }
            });
        }

        $(document).ready(function(){
            show();
            setInterval('show()',1000);
        });
    </script>
    <div id="duels">
        Дуели не найдены
    </div>
</div>
<!--end-->
