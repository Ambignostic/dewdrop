<?php

/**
 * Dewdrop
 *
 * @link      https://github.com/DeltaSystems/dewdrop
 * @copyright Delta Systems (http://deltasys.com)
 * @license   https://github.com/DeltaSystems/dewdrop/LICENSE
 */

namespace Dewdrop\View\Helper;

use Dewdrop\Fields;

/**
 * This helper renders a Bootstrap that allows the user to select which columns
 * should be visible in a component.
 */
class BootstrapColumnsModal extends AbstractHelper
{
    /**
     * Provide a Fields object with all the available fields the user can select
     * from along with a Fields object containing only those that have been
     * selected to be displayed already.  The helper will use these two Fields
     * objects to render a modal that enables a user to check/un-check columns
     * they'd like to have displayed.
     *
     * @param Fields $availableFields
     * @param Fields $visibleFields
     * @param string $actionUrl
     * @param string $id
     * @return string
     */
    public function direct(Fields $availableFields, Fields $visibleFields, $actionUrl, $id = null)
    {
        return $this->partial(
            'bootstrap-columns-modal.phtml',
            array(
                'id'        => ($id ?: 'adjust-columns-modal'),
                'actionUrl' => $actionUrl,
                'visible'   => $visibleFields->getVisibleFields(),
                'available' => $availableFields->getVisibleFields()
            )
        );
    }
}
