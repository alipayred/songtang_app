$(function(){
    $('#infoModal').on('show.bs.modal',function(e) {
        var btn = $(e.relatedTarget);
        var id = btn.data("id");

        $.ajax({
            url: '/apply/get-record',
            type: 'post',
            //async : false,
            dataType: 'json',
            data: {
                id: id
            },
            success: function (data) {
                if(data.result){
                    $('#infoContent .content').html(data.html);
                }else{
                    $('#infoContent .errormsg-text').html(data.errormsg).show();

                }
            }
        });

    });




    $('.btn-op-del').click(function(){
        if(confirm('确认是否要撤销这个申请？')){
            var id = $(this).attr('data-id');

            $.ajax({
                url: '/apply/del',
                type: 'post',
                //async : false,
                dataType: 'json',
                data: {
                    id: id
                },
                success: function (data) {
                    if(data.result){
                        location.href = '/apply/my';
                    }else{
                        alert('撤销失败！刷新重试！');

                    }
                }
            });
        }else{
            return false;
        }
    })

    $('#main').on('click','.print-btn',function(){
        var headhtml = "<html><head>";
        //headhtml += '<link href="/css/site.css" rel="stylesheet">'+
        //    '<link href="/css/main/apply/my.css" rel="stylesheet">';
        headhtml +="<title></title></head><body>";
        var foothtml = "</body>";
        // 获取div中的html内容
        //var newhtml = document.all.item(printpage).innerHTML;
        // 获取div中的html内容，jquery写法如下
         //var newhtml= $("#" + printpage).html();
         var newhtml = $("#infoModal .modal-content").html();

        // 获取原来的窗口界面body的html内容，并保存起来
        var oldhtml = document.body.innerHTML;

        // 给窗口界面重新赋值，赋自己拼接起来的html内容
        document.body.innerHTML = headhtml + newhtml + foothtml;
        // 调用window.print方法打印新窗口
        window.print();

        // 将原来窗口body的html值回填展示
        document.body.innerHTML = oldhtml;
        return false;
    });
});