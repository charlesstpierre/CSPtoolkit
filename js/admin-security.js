(function($) {
    $('.security-list-remove-item').click(function(){
        $('#remove_ip').val( $(this).data('ip') );
        $('#remove_list').val( $(this).data('list') );
        $(this).closest('li').fadeOut(500,function(){ $('#csp-security-form').submit(); });
    });
})(jQuery);