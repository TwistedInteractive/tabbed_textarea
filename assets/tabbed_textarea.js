// Add CKEditor support:
var ckFound    = false;
var intervalID = -1;

jQuery(function($){
    ckFound = $("div.field-tabbed_textarea textarea.ckeditor").length > 0;

    $("div.field-tabbed_textarea ul.tabs li:first").addClass("active");
    $("div.field-tabbed_textarea textarea:not(:first)").hide();

    if(ckFound)
    {
        intervalID = setInterval('checkForCKEditor()', 100);
    }

    $("div.field-tabbed_textarea ul.tabs li").click(function(){
        if(!($(this).hasClass("new")))
        {
            $("div.field-tabbed_textarea ul.tabs li").removeClass("active");
            if(!ckFound)
            {
                // Plain textarea:
                $("div.field-tabbed_textarea textarea").hide();
                $("div.field-tabbed_textarea textarea." + $(this).attr("class")).show();
            } else {
                // CKEditor:
                $("div.field-tabbed_textarea textarea").each(function(){
                    $(this).next().hide();
                });
                $("div.field-tabbed_textarea textarea." + $(this).attr("class")).next().show();
            }
            $(this).addClass("active");
        } else {
            // New tab:
            var total = $("div.field-tabbed_textarea ul.tabs li").length;
            var deleteStr = $("input[name=delete]", $(this).parent().parent().parent()).val() == 1 ? '<a href="#" class="delete">×</a>' : '';
            var elemName = $("input[name=element_name]", $(this).parent().parent().parent()).val();
            $(this).before('<li class="tab' + total + '"><input type="text" name="fields['+ elemName +'][tabs][' + total + ']" value="enter name..." />' + deleteStr + '</li>');
            $("div.field-tabbed_textarea > div").append('<textarea rows="15" cols="50" name="fields['+ elemName +'][content][' + total + ']" class="tab' + total + '"></textarea>');
            if(ckFound)
            {
                // Convert new textarea to a ckeditor instance:
                var instance = $("div.field-tabbed_textarea > div > textarea:last")[0];
                CKEDITOR.replace(instance);
            }
            $("div.field-tabbed_textarea li.tab" + total + " input").focus();
            $("div.field-tabbed_textarea li.tab" + total).click(function(){
                $("div.field-tabbed_textarea ul.tabs li").removeClass("active");
                if(!ckFound)
                {
                    // Plain textarea:
                    $("div.field-tabbed_textarea textarea").hide();
                    $("div.field-tabbed_textarea textarea." + $(this).attr("class")).show();
                } else {
                    // CKEditor:
                    $("div.field-tabbed_textarea textarea").each(function(){
                        $(this).next().hide();
                    });
                    $("div.field-tabbed_textarea textarea." + $(this).attr("class")).next().show();
                }
                $(this).addClass("active");
            }).click();

            bindDelete();
        }
    });
    bindDelete();
    $("div.field-tabbed_textarea ul.tabs li input, div.field-tabbed_textarea textarea").unbind("click").click(function(){
        $(this).focus();
        return false;
    }).focus(function(){
        $(this).parent().click();
        $(this).addClass("focus");
        return false;
    }).blur(function(){
        $(this).removeClass("focus");
        return false;
    });
});

function bindDelete()
{
    var $ = jQuery;
    $("div.field-tabbed_textarea ul.tabs li a.delete").unbind("click").click(function(){
        $("div.field-tabbed_textarea ul.tabs li").removeClass("active");
        if(!ckFound)
        {
            // Plain textarea:
            $("div.field-tabbed_textarea textarea." + $(this).parent().attr("class")).remove();
            $("div.field-tabbed_textarea textarea").hide();
            $("div.field-tabbed_textarea textarea:first").show();
        } else {
            // CKEditor:
            $("div.field-tabbed_textarea textarea").each(function(){
                $(this).next().hide();
            });
            var instance = $("div.field-tabbed_textarea textarea." + $(this).parent().attr("class"))[0];
            CKEDITOR.remove(instance);
            $("div.field-tabbed_textarea textarea:first").next().show();
        }
        $(this).parent().remove();
        $("div.field-tabbed_textarea ul.tabs li:first").addClass("active");
        return false;
    });
}

function checkForCKEditor()
{
    var $ = jQuery;
    
    if($("div.field-tabbed_textarea > div > span[id^=cke_]").length > 0)
    {
        // Hide all textareas, only show the first CKEditor:
        $("div.field-tabbed_textarea textarea").each(function(){
            $(this).hide();
            $(this).next().hide();
        });
        $("div.field-tabbed_textarea textarea:first").next().show();
        clearInterval(intervalID);
    }
}