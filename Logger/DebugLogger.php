<?php

namespace Fisrv\Payment\Logger;

use Laminas\Diactoros\Exception\SerializationException;
use Magento\Framework\DataObject;
use Throwable;
use Zend_Log;
use Zend_Log_Writer_Stream;

/**
 * Helper class for Logging.
 * Wraps Zends API.
 */
class DebugLogger extends Zend_Log
{
    public function __construct()
    {
        /** @phpstan-ignore constant.notFound */
        $writer = new Zend_Log_Writer_Stream(BP . '/var/log/fisrv-checkout.log');
        $this->addWriter($writer);
        parent::__construct();
    }

    /**
     * Write info or debug log.
     * If message is of generic object type, convert to array.
     * If of array type, convert to Magento DataObject.
     * if DataObject, parse to JSON string.
     *
     * @param mixed $message Message data
     * @param string $type Error log on 'error', info log on anything else
     * @return void
     */
    public function write(mixed $message, string $type = 'info')
    {
        try {
            if (is_object($message)) {
                $message = json_decode(json_encode($message, JSON_THROW_ON_ERROR), true);
            }

            if (is_array($message)) {
                $message = new DataObject($message);
            }

            if ($message instanceof DataObject) {
                $message = $message->toJson();
            }

            if (!$message) {
                throw new SerializationException('Could not parse message object');
            }

            if ($type === 'error') {
                $this->log(strval($message), Zend_Log::ERR);

                return;
            }

            $this->log(strval($message), Zend_Log::DEBUG);
        } catch (Throwable $th) {
            $this->log($th->getMessage(), Zend_Log::WARN);
        }
    }
}