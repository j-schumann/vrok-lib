<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Form;

/**
 * Base class for forms that should be submitted via AJAX per default.
 * Sets onclick handlers for all submit elements to automatically submit to
 * the forms action URL.
 */
class AjaxForm extends Form
{
    use SharedAjaxFunctions;

    /**
     * Prevents accidentally submitting the form via enter key.
     *
     * @inheritdoc
     */
    public function __construct($name = null, $options = array())
    {
        parent::__construct($name, $options);

        // this handler is only called when the form is not submitted using a
        // button with the onclick(tools.submit()) handler, e.g. when the enter
        // key is pressed. This is to avoid submitting the form via normal POST
        // instead via XHR and showing the response in the site.
        // Forms that want to allow quick submitting via Enter should overwrite
        // the onsubmit handler in their init()
        //
        // we add this here instead of init() as it's easy to forget to call
        // parent::init() in the subclasses' init()
        $this->setAttribute('onsubmit', 'return false;');
    }
}
