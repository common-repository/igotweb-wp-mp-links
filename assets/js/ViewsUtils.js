// We set the global namespace

igotweb = {};
igotweb.wp = {};
igotweb.wp.utilities = {};

(function( $ ) {
    'use strict';
    
    igotweb.wp.utilities.ViewsUtils = {
        getViewDataFromSection : function(section) {
            section = $(section);
            var viewID = section.data("viewid");
            var viewDataIndex = section.data("viewdataindex");
            var data = igotweb_wp_viewData[viewID][viewDataIndex];
            return data;
        },

        getAjaxURL : function() {
            return igotweb_wp_viewData['ajax_url'];
        },

        getViewAction : function() {
            return igotweb_wp_viewData['get_view_action'];
        },

        updateViewDataInSection : function(viewData, section) {
            section = $(section);
            var viewID = section.data("viewid");
            var viewDataIndex = section.data("viewdataindex");
            igotweb_wp_viewData[viewID][viewDataIndex] = viewData;
        },

        getView : function(view, data, callback) {
            // We generate the input
            var input = {
				'action': this.getViewAction(),
				'view' : view,
				'data': data
            };
            
            jQuery.post(this.getAjaxURL(), input, function (response) {
                // We store the data and update the viewdataindex property of the elements for which we have data.
                var dom = $(response.html);
                var responseData = response.data;
                for(var viewID in responseData) {
                    var listData = responseData[ viewID ];
                    var listViews = $("[data-viewid='"+viewID+"']",dom);
                    listViews.each(function(index) {
                        var viewData = listData[index];
                        if(!igotweb_wp_viewData[viewID]) {
                            igotweb_wp_viewData[viewID] = [];
                        }
                        var viewDataIndex = igotweb_wp_viewData[viewID].length;
                        igotweb_wp_viewData[viewID][viewDataIndex] = viewData;
                        var view = $(this);
                        view.attr("data-viewdataindex", viewDataIndex);
                    });
                }
                response.html = $('<div>').append(dom.clone()).html();
                delete response.data;
                callback.call(null, response);
            });
        },

        handleErrorsFromAjax : function (data, section, genericErrorMessage) {
            var errors = [];
            if(data != null) {
                errors = data.errors; 
            }
            else if(!!genericErrorMessage) {
                errors.push(genericErrorMessage);
            }
            errors.map(function(error) {
                var message = error;
                if(error.formattedMessage) {
                    message = error.formattedMessage;
                }
                $('.iw-errors', section).append($('<li>'+message+'</li>'))
            });
            $('.iw-errors', section).show();
        }
    };

})( jQuery );