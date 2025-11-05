<?php

declare(strict_types=1);

namespace Fiserv\Checkout\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;

/**
 * Heading Render class
 */
class Heading extends Field
{
    protected $_template = 'Fiserv_Checkout::system/config/fieldset/heading.phtml';
}
