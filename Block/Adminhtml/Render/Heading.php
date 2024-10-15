<?php

declare(strict_types=1);

namespace Fiserv\Checkout\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Heading Render class
 */
class Heading extends Field
{
    protected $_template = 'Fiserv_Checkout::system/config/fieldset/heading.phtml';

    /**
     * Render block: table heading
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        return $this->toHtml();
    }
}
