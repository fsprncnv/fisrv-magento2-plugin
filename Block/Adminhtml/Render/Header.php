<?php
namespace Fisrv\Payment\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Header extends Field
{

    /**
     * @var string
     */
    // protected $_template = 'Fisrv_Payment::system/config/fieldset/header.phtml';

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $element->addClass('fisrv-payment');
        return $this->toHtml();
    }
}
