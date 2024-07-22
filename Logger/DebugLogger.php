<?php

namespace Fisrv\Payment\Logger;

use DASPRiD\Enum\Exception\IllegalArgumentException;
use Laminas\Diactoros\Exception\SerializationException;
use Magento\Framework\DataObject;
use Throwable;
use Zend_Log;
use Zend_Log_Writer_Stream;

class DebugLogger extends Zend_Log
{

    public function __construct()
    {
        $writer = new Zend_Log_Writer_Stream(BP . '/var/log/custom.log');
        $this->addWriter($writer);
        parent::__construct();
    }

    /**
     * Write info or debug log
     *
     * @param string | DataObject $message String message
     * @param string $type Error log on 'error', info log on anything else
     * @return void
     */
    public function write(string | DataObject $message, string $type = 'info')
    {
        try {
            if ($message instanceof DataObject) {
                $message = $message->toJson();
            }

            if (!is_string($message)) {
                throw new IllegalArgumentException('Logger message is of illegal type: ' . gettype($message));
            }

            if (!$message) {
                throw new SerializationException('Could not parse message object');
            }

            if ($type === 'error') {
                $this->log($message, Zend_Log::ERR);
                return;
            }

            $this->log($message, Zend_Log::DEBUG);
        } catch (Throwable $th) {
            $this->log($th->getMessage(), Zend_Log::WARN);
        }
    }
}
