jQuery(document).ready(function($){
    var check_badge= $('.enable-badge .rwmb-radio:checked').val();

        

        $('.enable-badge .rwmb-radio').change(function(){
            if($(this).val() == 'Enabled'){
                $('#cwpt_badge_text').fadeIn('slow');
            }
            if($(this).val() == 'Disabled'){
                $('.badge-text').fadeOut('slow');
                $('.badge-background-color').fadeOut('slow');
                $('.badge-color').fadeOut('slow');
        
            }else{
                $('.badge-text').fadeIn('slow');
                $('.badge-background-color').fadeIn('slow');
                $('.badge-color').fadeIn('slow');
    
            }
            // console.log($(this).val());
        });
        if(check_badge == 'Disabled'){
            $('.badge-text').hide();
            $('.badge-background-color').hide();
            $('.badge-color').hide();
        }else{
            // console.log(check_badge);
            $('.badge-text').show();
            $('.badge-background-color').show();
            $('.badge-color').show();
        }
    

});
