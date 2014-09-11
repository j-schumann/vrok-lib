<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Form;

use Traversable;
use Zend\Form\ElementInterface;
use Zend\Form\Fieldset;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\Stdlib\ArrayUtils;

/**
 * Common functionality for forms that work with doctrine entities.
 *
 * Form class must be instantiated by the formElementManager or the
 * serviceLocator won't be injected!
 *
 * @todo dont use servicelocatoraware, inject only the necessary dependencies. but how
 * can we do that without writing hundreds of factories?
 */
class Form extends \Zend\Form\Form implements ServiceLocatorAwareInterface
{
    use SharedFunctions;

    /**
     * We want to have an option to check everywhere everytime if the form has
     * validation errors.
     *
     * isValid() is not suited as it throws an exception when the form wasn't
     * submitted before and thus has no data set. Using the combination
     * hasValidated() && !isValid() does not work either because $hasValidated
     * is reset to false after calling prepare() in the viewScript.
     * getMessages() is quite expensive but gets the job done.
     *
     * @return boolean  true if one or more elements have validation error
     *     messages, else false
     */
    public function hasErrors()
    {
        \Doctrine\Common\Util\Debug::dump($this->getMessages(), 4);
        return (bool)count($this->getMessages());
    }

    /**
     * Filters the request data before setting, removing empty arrays from
     * composed elements and button values.
     *
     * @see self::filterData
     * @param array|Traversable $data
     * @return self
     * @throws \Zend\Form\Exception\InvalidArgumentException
     */
    public function setData($data)
    {
        if ($data instanceof Traversable) {
            $data = ArrayUtils::iteratorToArray($data);
        }
        if (!is_array($data)) {
            throw new \Zend\Form\Exception\InvalidArgumentException(sprintf(
                '%s expects an array or Traversable argument; received "%s"',
                __METHOD__,
                (is_object($data) ? get_class($data) : gettype($data))
            ));
        }

        // check for empty arrays and clear submit button values
        $data = $this->filterData($data);

        return parent::setData($data);
    }

    /**
     * Removes submitted values for button elements as these would overwrite
     * the default values and may cause empty labels, e.g. for forms with
     * multiple submits.
     *
     * Sets empty arrays of composite elements to null.
     * Workaround for https://github.com/zendframework/zf2/issues/4302
     * For composite elements returning an array the allowEmpty rule is not
     * checked because an array with empty elements is not considered empty
     * by the Zend\InputFilter\BaseInputFilter.
     *
     * @param array $data
     * @return array
     */
    protected function filterData(array $data)
    {
        foreach($this->getElements() as $element) {
            if ($element instanceof \Zend\Form\Element\Submit
                || $element->getAttribute('type') === 'submit')
            {
                unset($data[$element->getName()]);
            }
        }

        return $this->filterArrayData($this, $data);
    }

    /**
     * Recursively checks the element if its data is an empty array, if yes
     * returns null instead.
     *
     * @param ElementInterface $element
     * @param type $data
     * @return null
     */
    protected function filterArrayData(ElementInterface $element, $data)
    {
        if (!is_array($data)) {
            return $data;
        }

        // fieldsets may have elements or nested fieldsets
        if ($element instanceof Fieldset) {
            // Fieldsets are to be recursed
            foreach($element->getIterator() as $child) {
                $name = $child->getName();
                if (!isset($data[$name])) {
                    continue;
                }
                $data[$name] = $this->filterArrayData($child, $data[$name]);
            }

            return $data;
        }

        // array for a normal element, make sure there is ANY data in the array
        foreach ($data as $value) {
            // the array has at least one set element -> return the complete
            // array to avoid notices about missing indexes
            if ($value !== null && $value !== '') {
                return $data;
            }
        }

        return null;
    }
}
