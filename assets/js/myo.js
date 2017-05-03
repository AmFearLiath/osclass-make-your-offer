$(document).ready(function($) {

    $(document).on("submit", ".myoForm", function(event){        
        event.preventDefault();
               
        var form    = $(this),
            action  = form.attr("action"),
            method  = form.attr("method"),
            data    = form.serialize();
        
        form.fadeOut(400);
            
        $.ajax({
            url: action,
            type: method,
            data: data,
            cache: false,            
            success: function(data){
            
                var source = $('<div>' + data + '</div>');
                    offer   = source.find('#make_your_offer').html();
                $('#make_your_offer').html(offer);
                console.log(offer);
                form.fadeIn(400);
            }
        });        
    });
});