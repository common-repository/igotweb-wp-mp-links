/*
 * @import jquery;
 * @import jquery-form;
 */
(function( $ ) {
    'use strict';
    

	function onImportFormSubmit(e) {
        e.preventDefault();
        
        var section = $(e.delegateTarget);
        var viewData = igotweb.wp.utilities.ViewsUtils.getViewDataFromSection(section);
        var ajaxURL = igotweb.wp.utilities.ViewsUtils.getAjaxURL();

        $('.iw-errors', section).html('').hide();

        var form = $(e.currentTarget);
        var fileData = $('input[name=file]',form).prop('files')[0];
     
        form.ajaxSubmit({
            
            dataType:"json",

            data: { 
                'action': viewData["importAction"]
            },

            url: ajaxURL,
            method: 'POST',
            
            beforeSubmit: function(arr, $form, options) {
                var errors = [];

                for(var index in arr) {
                    var input = arr[index];
                    if(input.name == "file" && input.value == "") {
                        errors.push(viewData["viewResources"].fileMandatory);
                    }
                }
        
                if(errors.length > 0) {
                    errors.map(function(error) {
                        $('.iw-errors', section).append($('<li>'+error+'</li>'))
                    });
                    $('.iw-errors', section).show();
                    return false;
                }

            },
                        
            complete:function(response) {
                var data = response.responseJSON;
                if(data == null || (data.errors && data.errors.length > 0)) {
                    igotweb.wp.utilities.ViewsUtils.handleErrorsFromAjax(data, section, viewData["viewResources"]["importGenericError"]);
                }
                if(data.imported) {
					window.location.reload();
				} 
            }
        });
	}
	
	function onExportFormSubmit(e) {
        e.preventDefault();

        $('.iw-errors', section).html('').hide();

		var section = $(e.delegateTarget);
        var viewData = igotweb.wp.utilities.ViewsUtils.getViewDataFromSection(section);
        var ajaxURL = igotweb.wp.utilities.ViewsUtils.getAjaxURL();
        
        var input = {
            'action': viewData["exportAction"]
        };

        jQuery.post(ajaxURL, input, function (response) {
            console.log(response);
            if(response.errors && response.errors.length > 0) {
                igotweb.wp.utilities.ViewsUtils.handleErrorsFromAjax(response, section, viewData["viewResources"]["exportGenericError"]);
			}
			if(response.json) {
				download("settings.json",response.json);
			} 
        });
	}
	
	function download(filename, text) {
		var element = document.createElement('a');
		element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
		element.setAttribute('download', filename);
	  
		element.style.display = 'none';
		document.body.appendChild(element);
	  
		element.click();
	  
		document.body.removeChild(element);
	  }

	$(function() {
		
		// We initialize all the components
		var sections = $('.admin-options-import-export');
		sections.each(function(index) {
			var section = $(this);
			var viewData = igotweb.wp.utilities.ViewsUtils.getViewDataFromSection(section);

			section.on('submit', 'form[name=export]', viewData, onExportFormSubmit);
			section.on('submit','form[name=import]', viewData, onImportFormSubmit);
		});

	});

})( jQuery );
