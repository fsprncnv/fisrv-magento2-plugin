<?php

declare(strict_types=1);

namespace Fiserv\Checkout\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class StatusCheck extends Field
{
    protected $_template = 'Fiserv_Checkout::system/config/fieldset/statuscheck.phtml';

    public function render(AbstractElement $element): string
    {
        return $this->toHtml(
            $element->getHtmlId(),
            $element->getData('label')
        );
    }
}
