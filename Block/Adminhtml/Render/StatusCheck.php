<?php

declare(strict_types=1);

namespace Fisrv\Payment\Block\Adminhtml\Render;

use Fisrv\Payment\Controller\Checkout\OrderContext;
use Fisrv\Payments\PaymentsClient;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;


class StatusCheck extends Field
{
    private static PaymentsClient $client;
    private OrderContext $orderContext;

    public function __construct(
        OrderContext $orderContext,
    ) {
        $this->orderContext = $orderContext;
    }

    public function render(AbstractElement $element): string
    {
        return
            '<tr id="row_' . $element->getHtmlId() . '">
                <td class="label"></td>
                <td class="value">
                <div class="mm-heading-fisrv">' . $element->getData('label') . '</div>
                    <div class="mm-comment-fisrv">
                        <div id="content">' . $this->container() . '</div>
                    </div>
                </td>
                <td></td>
            </tr>';
    }

    private function container(): string
    {
        return
            '<div style="display: flex; flex-direction: row; justify-content: space-between; align-items: center;">
                <div class="button-container">
                    <input type="hidden" name="action" />
                    <button type="button" onclick="callback()" class="button action-configure" >
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
                    document.getElementById("fisrv-health-text").innerHTML =  ' . "'<span class=\"loader-status-fisrv\"></span>';" . '
                    const res = await fetch("/fisrv/checkout/statusaction", {
                        method: "GET",
                    });
                    const data = await res.json();
                    const status = data["status"];
                    document.getElementById("fisrv-health-text").innerHTML = status;
                    
                    if(status !== "All good!") {
                        element.style.background = "red";
                    } else {
                        element.style.background = "green";
                    }
                }
            </script>
            ';
    }
}