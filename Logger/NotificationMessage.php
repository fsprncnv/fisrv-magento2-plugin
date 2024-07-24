<?php

namespace Fisrv\Payment\Logger;

use Magento\Framework\Notification\MessageInterface;

class NotificationMessage implements MessageInterface
{
    const MESSAGE_IDENTITY = 'fisrv_system_message';

    public function getIdentity()
    {
        return self::MESSAGE_IDENTITY;
    }

    public function isDisplayed()
    {
        return true;
    }

    public function getText()
    {
        return __('Atwix System Message Text.');
    }

    public function getSeverity()
    {
        return MessageInterface::SEVERITY_MAJOR;
    }
}