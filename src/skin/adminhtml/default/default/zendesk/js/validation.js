Validation.add('validate-require-if-not-global', 'This field is required if you want to use your own configuration.', function(the_field_value){
    if( $('use_global_settings').getValue() === '0') {
        return the_field_value.length > 0;
    }
    return true;
});

$jq = jQuery.noConflict();

(function($){
    'use strict';
    
    $(document).ready(function() {
        var
            $usernameRow = $('#username').closest('tr'),
            $passwordRow = $('#password').closest('tr'),
            $rows = $usernameRow.add( $passwordRow );
            
        $('#use_global_settings').change(function() {
            $rows.toggle( $(this).val() === '0');
        }).trigger('change');
    });
})($jq);
