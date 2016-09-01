(function($) {
    window.Vrok = window.Vrok || {};
    Vrok.Tools = Vrok.Tools || {};

    /**
     * Issues a JSON(P) request to the given URL and places the result HTML in
     * the container given.
     *
     * @param {string} url              the URL to which the request is sent
     * @param {Node|string} container   the result DOM node
     * @param {object} options          hash of options:
     *     {function} callback       function to call after the load finished
     *     {boolean} jsonp           set true if request should be made as
     *          JSONP, e.g. for cross domain requests, requires the server to
     *          support this
     *     {boolean} scrollTo        whether or not after loading the page is
     *          scrolled to the top of the container
     *     {boolean} showOverlay     whether or not to show the loading overlay
     */
    Vrok.Tools.json = function(url, container, options) {
        var $container = null;
        var defaults = {
            callback: null,
            jsonp: false,
            scrollTo: false,
            showOverlay: true,
            data: {}
        };
        $.extend(defaults, options);

        // assume container is the ID of the DOMNode
        if (typeof(container) === 'string') {
            $container = $('#'+container);
        } else {
            $container = $(container);
        }

        if (defaults.showOverlay) {
            $container.loading(true);
        }

        var request = {
            dataType: "json",
            data: defaults.data,
            url: url,
            success: function (data) {
                Vrok.Tools.processResponse(data, $container, defaults);

                if (typeof(defaults.callback) === 'function') {
                    defaults.callback($container, data);
                }
            },
            error: function (data) {
                console.error('Vrok.Tools.json: Request to "'+url+'" failed!');
                console.debug(data);

                // still try to process, maybe we received a 403 with a
                // redirect in the response.script
                Vrok.Tools.processResponse(
                    data.responseJSON ? data.responseJSON : data.responseText,
                    $container,
                    defaults
                );
            }
        };

        if (defaults.jsonp) {
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
     * @param {object} options        hash of options:
     *     {function} callback       function to call after the load finished
     *     {boolean} jsonp           set true if request should be made as
     *          JSONP, e.g. for cross domain requests, requires the server to
     *          support this
     *     {boolean} scrollTo        whether or not after loading the page is
     *          scrolled to the top of the container
     *     {boolean} showOverlay     whether or not to show the loading overlay
     * @return {boolean}            false to prevent the browser from submitting
     */
    Vrok.Tools.submit = function(element, senderName, senderValue, container, options) {
        var form = null;
        var defaults = {
            callback: null,
            jsonp: false,
            scrollTo: true,
            showOverlay: true
        };
        $.extend(defaults, options);

        if (element.form) {
            // element is a normal input element assigned to a form
            form = $(element.form);
        } else if (element.tagName && element.tagName.toUpperCase() === 'FORM') {
            form = $(element);
        } else if (typeof(element) === 'string') {
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

        // fetch the data before setting the loading animation as this disables
        // all elements and disabled elements aren't serialized...
        var data = form.serialize();

        // add a flag so the server can detect if this was an AJAX submit, which
        // may not be possible otherwise, e.g. when using a hidden iframe as
        // transport method
        data += '&ajaxRequest=1';

        // add a sender element as image elements/buttons are not sent to the
        // server by default but maybe we want to detect which image/button was
        // clicked to trigger separate actions
        if (senderName) {
            data += '&'+senderName+'='+senderValue;
        }

        // the result container, the form itself or the DOM node given via id
        if (typeof(container) === 'string') {
            container = $('#'+container);
        } else if (form.data('target')) {
            container = $(form.data('target'));
        }
        var $container = container ? $(container) : $(form.context.parentNode);

        if (defaults.showOverlay) {
            $container.loading();
        }

        var request = {
            type: "POST",
            dataType: "json",
            data: data,
            url: form.context.action,
            success: function (data) {
                Vrok.Tools.processResponse(data, $container, defaults);

                if (typeof(defaults.callback) === 'function') {
                    defaults.callback($container, data);
                }
            },
            error: function (data) {
                console.error('Vrok.Tools.submit: Request to "'
                    +form.context.action+'" failed!');
                console.debug(data);

                // still try to process, maybe we received a 403 with a
                // redirect in the response.script
                Vrok.Tools.processResponse(
                    data.responseJSON ? data.responseJSON : data.responseText,
                    $container,
                    defaults
                );
            }
        };

        if (defaults.jsonp) {
            request.dataType = 'jsonp';
            request.jsonp    = 'callback';
        }

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
     * @param {object} response     JSON response to an XHR/JSONP request
     * @param {object} container    jQuery object where the response HTML is inserted
     * @param {object} options      hash of additional options:
     *     {boolean} showOverlay     whether or not to show the loading overlay
     *     {boolean} scrollTo        whether or not after loading the page is scrolled
     *          to the top of the container
     */
    Vrok.Tools.processResponse = function(response, container, options) {
        if (typeof(response) === 'string') {
            response = {html: response};
        }

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

            container.html($.parseHTML(response.html));

            if (options.showOverlay) {
                container.loading(false);
            }

            // we trigger a custom event here to allow listeners to take
            // additional actions after the response was loaded into the
            // container, e.g. initialize form, without requiring a callback
            container.trigger('processed');

            if (options.scrollTo) {
                $('html,body').animate({
                    scrollTop: Math.max(container.offset().top - 50, 0)
                });
            }
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
                 // creates the overlay (font-awesome spinner not visible if FA not loaded)
                var $overlay = $('<div><i class="fa fa-spinner fa-pulse fa-4x"></i></div>').addClass("js-loading-overlay");

                // add a class
                if(addClass !== undefined) {
                    $overlay.addClass(addClass);
                }

                // append it to the current element and position it correctly
                $element.append( $overlay ).addClass("js-loading");

                // show the element
                $overlay.stop().hide().fadeIn(400);

            // if we want to destroy this overlay
            } else if(!state) {
                $element.removeClass("js-loading")
                        .find(".js-loading-overlay").remove();
            }
        });

        return this;
    };

    // initialize ajax-forms on page load
    $(document).ready(function() {
        // We do not want to submit ajax forms when the "enter" key is pressed
        // on any other form elements but the submit buttons (which is the
        // default behaviour in all browsers) because most times our forms will
        // have multiple buttons (prev, next, save, cancel, ...) and we need to
        // detect which one was clicked/triggered
        // Because a "click" is triggered on the nearest/next button (in Tab
        // order) from the element where we pressed enter we need to detect
        // if a) the button was directly clicked or triggered via "enter" key
        // directly on the button or b) triggered by "enter" on another element.
        // Gecko browsers would support e.originalEvent.explicitOriginalTarget
        // but all others don't, so we use a workaround: we store the name of
        // the element on which "enter" was pressed in the data attribute on the
        // form and compare it to the buttons name in the "click" handler
        // only if there is no name stored (mouse click) or the name equals the
        // buttons name ("enter" directly on the button) the form is submitted.

        $("body").on('keypress', '.ajax-form', function(e) {
            if (e.which === 13 /* "enter" */) {
                $(this).data('enter-pressed', e.target.name);
            }
        });

        // reset form.data on keyup (triggered after "click")
        $("body").on('keyup', '.ajax-form', function(e) {
            $(this).data('enter-pressed', "");
        });

        // body is the nearest static container we can safely assume to be
        // present for all forms...
        $("body").on('click', '.ajax-form input[type="submit"]', function(e) {
            var enterPressed = $(this.form).data('enter-pressed');
            if (enterPressed && enterPressed !== this.name) {
                e.preventDefault();
                return false;
            }

            return Vrok.Tools.submit(this, this.name, this.value);
        });
    });
}(jQuery));
