(function($) {
    window.Vrok = window.Vrok || {};
    Vrok.Tools = Vrok.Tools || {};
    
    /**
     * Issues a JSON(P) request to the given url and places the result HTML in the
     * container given.
     *
     * @param {string} url              the URL to which the request is sent
     * @param {Node|string}container    the result DOM node
     * @param {boolean} showOverlay     whether or not to show the loading overlay
     * @param {function} callback       function to call after the load finished
     * @param {boolean} jsonp           set true if request should be made as 
     *     JSONP, e.g. for cross domain requests, requires the server to support
     *     this
     */
    Vrok.Tools.json = function(url, container, showOverlay, callback, jsonp) {
        var $container = null;
        // assume container is the ID of the DOMNode
        if (typeof(container) === 'string') {
            $container = $('#'+container);
        }
        else {
            $container = $(container);
        }

        if (showOverlay) {
            $container.loading(true);
        }

        var request = {
            dataType: "json",
            url: url,
            success: function (data) {
                Vrok.Tools.processResponse(data, $container);
                if (typeof(callback) === 'function') {
                    callback($container);
                }
            },
            error: function (data) {
                console.error('Vrok.Tools.json: Request to "'+url+'" failed!');
                console.debug(data);
            }
        };
        if (jsonp) {
            request.dataType = 'jsonp';
            request.jsonp    = 'callback';
        }

        $.ajax(request);
    };
    
    /**
     * Submit function for AJAX enabled forms,
     * sends the given form to the address set as the forms action.
     *
     * @param {Node|string} element   the id of the form or the element within the
     *     form which is to be submitted
     * @param {string} senderName     the clicked elements id
     * @param {string} senderValue    the clicked elements value
     * @param {string} container      (optional) result container
     * @return {boolean}              false to prevent the browser from submitting
     */
    Vrok.Tools.submit = function(element, senderName, senderValue, container, callback) {
        var form = null;
        if (element.form) {
            // element is a normal input element assigned to a form
            form = $(element.form);
        }
        else if (element.tagName && element.tagName.toUpperCase() === 'FORM') {
            form = $(element);
        }
        else if (typeof(element) === 'string') {
            // assume element is the Id of the DOMNode of the form to submit
            var result = $('#'+element);
            if (result.length) {
                form = $(result[0]);
            }
        }

        if (!form) {
            console.log('vrok.tools.submit: Could not determine form to submit!');
            return false;
        }

        // add a sender element as image elements / buttons are not sent to the
        // server by default but maybe we want to detect which image/button was
        // clicked to trigger separate actions
        if (senderName) {
            var sender = $('<input type="hidden" value="'+senderValue
                    +'" name="'+senderName+'" />');
            form.append(sender);
        }
        
        // add a flag so the server can detect if this was an AJAX submit, which
        // may not be possible otherwise, e.g. when using a hidden iframe as
        // transport method
        var ajaxRequest = $('<input type="hidden" value="1" name="ajaxRequest" />');
        form.append(ajaxRequest);

        // fetch the data before setting the loading animation as this disables
        // all elements and disabled elements aren't serialized...
        var data = form.serialize();

        // the result container, the form itself or the DOM node given via id
        var $container = container
            ? $('#'+container)
            : $(form.context.parentNode);
        $container.loading();

        var request = {
            type: "POST",
            dataType: "json",
            data: data,
            url: form.context.action,
            success: function (data) {
                Vrok.Tools.processResponse(data, $container);
                if (typeof(callback) === 'function') {
                    callback($container);
                }
            },
            error: function (data) {
                console.error('Vrok.Tools.submit: Request to "'
                    +form.context.action+'" failed!');
                console.debug(data);
            }
        };
        $.ajax(request);
        return false;
    };
    
    /**
     * Processes the JSON response.
     *
     * Response is expected to be a JSON object with optional elements:
     * - html: this is set as the innerHTML of the container
     * - script: this is eval'ed
     *
     * @param {object} response    JSON response to an XHR/JSONP request
     * @param {object} container   jQuery object where the response HTML is inserted
     */
    Vrok.Tools.processResponse = function(response, container) {
        if (response.html && typeof(response.html) === 'string') {
            // allow the response to overwrite the (probably autodetected)
            // result container, e.g. when returning a complete view instead
            // of only the form again
            if (response.container) {
                container = $('#'+response.container);
            }

            if (!container.html) {
                console.error('Vrok.Tools.processResponse: Invalid container!');
                console.debug(container);
                return false;
            }
            container.html($.parseHTML( response.html ));
            $('html,body').animate({
                scrollTop: Math.max(container.offset().top-50, 0)
            });
        }
        
        // execute additional script code
        if (response.script) {
            eval(response.script);
        }
    };
    
    /**
     * Adds a loading overlay on the element.
     *
     * That jQuery function must be run with a CSS style.
     * See the stylesheet below for more informations.
     *
     * @param {Boolean} state [optional] Set to false to remove the overlay.
     * @param {String} addClass [optional] One or several class to add to the overlay
     * @return {jQuery} The current jQuery object (allow chaining)
     * @author Pirhoo (https://gist.github.com/Pirhoo/3676651)
     */
    $.fn.loading = function(state, addClass) {
        // element to animate
        var $this = $(this);
        
        // hide or show the overlay
        state = state === undefined ? true : !!state;
 
        $this.each(function(i, element) {
            var $element = $(element);
 
            // if we want to create an overlay and any one exists
            if( state && $element.find(".js-loading-overlay").length === 0 ) {
                 // creates the overlay
                var $overlay = $("<div/>").addClass("js-loading-overlay");

                // add a class
                if(addClass !== undefined) {
                    $overlay.addClass(addClass);
                }

                // append it to the current element and position it correctly
                $element.append( $overlay ).addClass("js-loading");
                $overlay.css('top', $element.position().top + 'px');
                $overlay.css('left', $element.position().left + 'px');
                $overlay.css('width', $element.width() + 'px');
                $overlay.css('height', $element.height() + 'px');

                // show the element
                $overlay.stop().hide().fadeIn(400);

                // Disables all inputs
                //$this.find("input,button,.btn")
                //     .addClass("disabled")
                //     .prop("disabled", true);
 
            // if we want to destroy this overlay
            } else if(!state) {
                $element.removeClass("js-loading")
                        .find(".js-loading-overlay").remove();
 
                // Enable all inputs
                $this.find("input,button,.btn")
                     .removeClass("disabled")
                     .prop("disabled", false);
            }
        });
 
        return this;
    };
}(jQuery));
