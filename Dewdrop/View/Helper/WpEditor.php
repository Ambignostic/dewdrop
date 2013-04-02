<?php

/**
 * Dewdrop
 *
 * @link      https://github.com/DeltaSystems/dewdrop
 * @copyright Delta Systems (http://deltasys.com)
 * @license   https://github.com/DeltaSystems/dewdrop/LICENSE
 */

namespace Dewdrop\View\Helper;

use Dewdrop\Db\Field;
use Dewdrop\Filter\Stripslashes;

/**
 * Use the WordPress editor in a form.
 *
 * More information the wp_editor() function and the arguments it accepts in
 * its settings parameter is available at the following link.
 *
 * @link http://codex.wordpress.org/Function_Reference/wp_editor
 */
class WpEditor extends AbstractHelper
{
    /**
     * This function will delegate to either directField(), directExplicit(),
     * or directArray() depending upon the supplied arguments.
     *
     * @return string
     */
    public function direct()
    {
        return $this->delegateByArgs(func_get_args(), 'direct');
    }

    /**
     * Use a \Dewdrop\Db\Field object to set the editor's id and content.
     *
     * @param Field $field
     * @return string
     */
    public function directField(Field $field)
    {
        $field->getFilterChain()->attach(new StripSlashes());

        return $this->directArray(
            array(
                'id'      => $field->getControlName(),
                'content' => $field->getValue()
            )
        );
    }

    /**
     * Explicitly specify the editor's $id and $content.
     *
     * @param string $id
     * @param string $content
     * @return string
     */
    public function directExplicit($id, $content)
    {
        return $this->directArray(
            array(
                'id'      => $id,
                'content' => $content
            )
        );
    }

    /**
     * Use an array of key-value pairs to set the editor's arguments.
     *
     * @param array $options
     */
    public function directArray(array $options)
    {
        extract($this->prepareOptionsArray($options));

        ob_start();

        wp_editor(
            $content,
            $id,
            $settings
        );

        return ob_get_clean();
    }

    /**
     * Ensure that the "id" and "content" options are set and the settings
     * option is present and an array.
     *
     * @param array $options
     * @return array
     */
    private function prepareOptionsArray(array $options)
    {
        $this
            ->checkRequired($options, array('id', 'content'))
            ->ensurePresent($options, array('settings'))
            ->ensureArray($options, array('settings'));

        return $options;
    }
}
