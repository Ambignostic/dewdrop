<?php

/**
 * Dewdrop
 *
 * @link      https://github.com/DeltaSystems/dewdrop
 * @copyright Delta Systems (http://deltasys.com)
 * @license   https://github.com/DeltaSystems/dewdrop/LICENSE
 */

namespace Dewdrop\View\Helper;

use Dewdrop\Admin\Component\ComponentAbstract;
use Dewdrop\Admin\Component\CrudInterface;

/**
 * This helper renders the primary nav bar that's used on typical Dewdrop CRUD
 * components.  It includes a button for creating a new item, exporting as CSV,
 * adjusting which columns are visible, etc.
 */
class AdminComponentNav extends AbstractHelper
{
    /**
     * Render the nav for the provided component.
     *
     * @param ComponentAbstract $component
     * @return string
     */
    public function direct(ComponentAbstract $component)
    {
        if ($component instanceof CrudInterface) {
            $title = $component->getPrimaryModel()->getSingularTitle();
        } else {
            $title = $component->getTitle();
        }

        return $this->partial(
            'admin-component-nav.phtml',
            array(
                'permissions'   => $component->getPermissions(),
                'singularTitle' => $title
            )
        );
    }
}