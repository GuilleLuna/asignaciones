
$(document).ready(function(){
    $('.btn-delete').click(function(e){
        e.preventDefault();
        
        var row = $(this).parents('tr');
        
        var id = row.data('id');
        
        // alert(id);
        var form = $('#form-delete');
        
        var url = form.attr('action').replace(':USER_ID', id);
        
        var data = form.serialize();
        // alert(data);
        
        bootbox.confirm(message,function(res){
            if(res==true)
            {
                $('#delete-progress').show(100);
                
                
                $.post(url,data,function(result){
                    $('#delete-progress').hide(100);
                    
                    if(result.remove == 1){
                        row.fadeOut();
                        $('#message').show(200);
                        
                        $('#user-message').text(result.message);
                        
                    }else{
                        $('#message-danger').show(200);
                        $('#user-message-danger').text(result.message);
                    }
                    
                   
                    
                });
            }
        })
        
    });
})