/*
 * @import jquery;
 */
(function( $ ) {
    'use strict';
    
    function checkNoPageNotice(section) {
        if($('.iw-list-pages .iw-page',section).length == 0) {
            $('.no-page', section).show();
        }
        else {
            $('.no-page', section).hide();
        }
    }

    function generatePage(setting, viewData, viewResources) {
        pageIndex++;
        var html = '<div class="iw-page">';
        html += '   <label for="'+viewData["pageFieldID"]+'_'+pageIndex+'">';
		html += '     '+viewResources["pageLabel"];
		html += '     <select id="'+viewData["pageFieldID"]+'_'+pageIndex+'" name="'+viewData["fieldNamePrefix"]+'['+pageIndex+'][ID]">';
		for(var index in viewData["pages"]) {
            var option = viewData["pages"][index];
            html += '     <option value="'+option["value"]+'" ';
            if(option["value"] == setting["ID"]) { 
                html += 'selected=selected ' 
            } 
            html += '>'+option["label"]+'</option>';
        }
		html += '     </select>';
        html += '   </label><br/>';
        html += '   <label for="'+viewData["menuTitleFieldID"]+'_'+pageIndex+'">';
		html += '     '+viewResources["menuTitleLabel"];
		html += '     <input type="text" id="'+viewData["menuTitleFieldID"]+'_'+pageIndex+'" name="'+viewData["fieldNamePrefix"]+'['+pageIndex+'][menuTitle]" value="'+setting["menuTitle"]+'" />';
        html += '   </label>';
        html += '   <a href="javascript:void(0);" class="remove" title="'+viewResources["removeTitle"]+'"><i class="fas fa-minus"></i></a>';
        html += '</div>';
		
		return $(html);
    }
    
    function onPageAdd(e) {
        e.preventDefault();
        var section = $(e.delegateTarget);
        var viewData = igotweb.wp.utilities.ViewsUtils.getViewDataFromSection(section);
        var viewResources = viewData["viewResources"];
        var setting = {
            'ID' : null,
            'menuTitle' : ''
        }

        var $html = generatePage(setting, viewData, viewResources);
        $('.iw-list-pages',section).append($html);

        checkNoPageNotice(section);
    }

    function onPageRemove(e) {
        e.preventDefault();
        var section = $(e.delegateTarget);

        $(e.currentTarget).closest(".iw-page").remove();

        // We check to display the no page notification
        checkNoPageNotice(section);
    }

    var pageIndex = 0;

	$(function() {
		
		// We initialize all the components
		var sections = $('.admin-options-list-pages');
		sections.each(function(index) {
			var section = $(this);
            var viewData = igotweb.wp.utilities.ViewsUtils.getViewDataFromSection(section);
            var viewResources = viewData["viewResources"];

            // We generate the current settings
            for(var index in viewData["settings"]) {
                var setting = viewData["settings"][index];
                var $html = generatePage(setting, viewData, viewResources);
                $('.iw-list-pages',section).append($html);
            }

            // We check to display the no page notification
            checkNoPageNotice(section);

            section.on('click', '.add', viewData, onPageAdd);
            section.on('click', '.remove', viewData, onPageRemove);
           
		});

	});

})( jQuery );