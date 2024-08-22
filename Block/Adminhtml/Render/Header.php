<?php

declare(strict_types=1);

namespace Fisrv\Payment\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Header extends Field
{
    protected $_template = 'Fisrv_Payment::system/config/fieldset/header.phtml';

    public function render(AbstractElement $element): string
    {
        return $this->toHtml();
    }
}