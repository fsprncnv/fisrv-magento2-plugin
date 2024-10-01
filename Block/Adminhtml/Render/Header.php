<?php

namespace Fiserv\Checkout\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Header extends Field
{
    protected $_template = 'Fiserv_Checkout::system/config/fieldset/header.phtml';

    public function render(AbstractElement $element): string
    {
        return $this->toHtml();
    }
}