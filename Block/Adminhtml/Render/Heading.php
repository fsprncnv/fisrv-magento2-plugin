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
    /**
     * Render block: table heading
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        ob_start();

        ?>
        <tr id=<?php echo 'row_' . $element->getHtmlId() ?>>
            <td class="label"></td>
            <td class="value">
                <div class="mm-heading-ginger">
                    <?php echo $element->getData('label') ?>
                </div>
                <div class="mm-comment-ginger">
                    <div id="content">
                        <?php echo $element->getData('comment') ?>
                    </div>
                </div>
            </td>
            <td></td>
        </tr>
        <?php

        return ob_get_clean();
    }
}