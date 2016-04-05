jQuery( document ).ready( function( $ )
{

    var $modelHtml = '<div id="myModal" class="modal">';
        $modelHtml += '<div class="modal-content">';
        $modelHtml += '<span class="close">x</span>';
        $modelHtml += '<select name="copy_select">';
        $modelHtml += '<option value="1">Copy Full Post Meta</option>';
        $modelHtml += '<option value="2">Copy Post Entry</option>';
        $modelHtml += '</select>';
        $modelHtml += ' <a href="" class="button">COPY</a>';
        $modelHtml += '</div>';
        $modelHtml += '</div>';

    $( $modelHtml ).insertBefore( $('#wpadminbar') );

    var modal = document.getElementById('myModal');
    var span = document.getElementsByClassName("close")[0];

    $( '.epik_copy_post' ).on( 'click' , function (e)
    {

        e.preventDefault();

        var link = $(this);
        var $href = link.attr( 'href' );

        $( '.button' ).attr( 'href' , $href + '&copy=1' );
        modal.style.display = 'block';

    });

    $( '.epik_copy_page' ).on( 'click' , function (e)
    {

        e.preventDefault();

        var link = $(this);
        var $href = link.attr( 'href' );

        $( '.button' ).attr( 'href' , $href + '&copy=1' );
        modal.style.display = 'block';

    });

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    $( document ).on( 'change' , 'select[name="copy_select"]' , function()
    {
        var link = $( '.button' );
        var value = $(this).val();

        link.attr( 'href' , link.attr( 'href' ) + '&copy=' + value );

    });

});
