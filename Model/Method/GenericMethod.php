<?php

declare(strict_types=1);

namespace Fisrv\Payment\Model\Method;

use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Adapter;

/**
 * Creditcard method class
 */
class GenericMethod extends Adapter
{

    public function capture(InfoInterface $payment, $amount)
    {
        echo 'capturing...';
        return $this;
    }

    public function authorize(InfoInterface $payment, $amount)
    {
        echo 'authorizing...';
        return $this;
    }

    public function acceptPayment(InfoInterface $payment)
    {
        echo 'accepting...';

        return $this;
    }
}
