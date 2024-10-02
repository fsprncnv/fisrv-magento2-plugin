<?php

declare(strict_types=1);

namespace Fiserv\Checkout\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class StatusCheck extends Field
{
    public function render(AbstractElement $element): string
    {
        ob_start();

        ?>
        <tr id=<?php echo 'row_' . $element->getHtmlId() ?>>
            <td class="label"></td>
            <td class="value">
                <div class="mm-heading-fisrv">
                    <?php echo $element->getData('label') ?>
                </div>
                <div class="mm-comment-fisrv">
                    <div id="content">
                        <?php echo $this->container() ?>
                    </div>
                </div>
            </td>
            <td></td>
        </tr>
        <?php

        return ob_get_clean();
    }

    private function container(): string
    {
        ob_start();

        ?>
        <div style="display: flex; flex-direction: row; justify-content: space-between; align-items: center;">
            <div class="button-container">
                <input type="hidden" name="action" />
                <button type="button" onclick="callback()" class="button action-configure">
                    <span class="state-closed">Check API Status</span>
                </button>
            </div>
            <div style="display: flex; flex-direction: row; justify-content: center; align-items: center;">
                <span id="fisrv-indicator" class="circle-status-fisrv"></span>
                <div id="fisrv-health-text">Untested</div>
            </div>
        </div>
        <script>
            async function callback() {
                const element = document.getElementById("fisrv-indicator");
                document.getElementById("fisrv-health-text").innerHTML = "<span class='loader-status-fisrv'></span>";
                const res = await fetch("/fiserv/checkout/statusaction", {
                    method: "GET",
                });
                const data = await res.json();
                const status = data["status"];
                document.getElementById("fisrv-health-text").innerHTML = status;

                if (data["code"] !== 200) {
                    element.style.background = "OrangeRed";
                } else {
                    element.style.background = "LightGreen";
                }
            }
        </script>
        <?php

        return ob_get_clean();
    }
}