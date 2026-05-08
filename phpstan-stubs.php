<?php

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Global namespace (Zend)
// ---------------------------------------------------------------------------

namespace {
    class Zend_Log
    {
        public const ERR = 3;
        public const WARN = 4;
        public const DEBUG = 7;

        public function __construct() {}
        public function addWriter(object $writer): void {}
        public function log(string $message, int $priority): void {}
    }

    class Zend_Log_Writer_Stream
    {
        public function __construct(string $streamOrUrl, ?string $mode = null) {}
    }
}

// ---------------------------------------------------------------------------
// Magento\Framework\Component
// ---------------------------------------------------------------------------

namespace Magento\Framework\Component {
    class ComponentRegistrar
    {
        public const MODULE = 'Module';
        public static function register(string $type, string $componentName, string $componentPath): void {}
    }
}

// ---------------------------------------------------------------------------
// Magento\Framework\App
// ---------------------------------------------------------------------------

namespace Magento\Framework\App {
    interface RequestInterface
    {
        public function getParam(string $key, mixed $default = null): mixed;
        public function getContent(): string;
        /** @return array<string, mixed> */
        public function getParams(): array;
    }

    interface CsrfAwareActionInterface
    {
        public function validateForCsrf(RequestInterface $request): ?bool;
        public function createCsrfValidationException(RequestInterface $request): ?\Magento\Framework\App\Request\InvalidRequestException;
    }

    interface ProductMetadataInterface
    {
        public function getVersion(): string;
        public function getEdition(): string;
        public function getName(): string;
    }
}

namespace Magento\Framework\App\Action {
    interface HttpGetActionInterface {}
    interface HttpPostActionInterface {}
}

namespace Magento\Framework\App\Request {
    class Http
    {
        public function getParam(string $key, mixed $default = null): mixed { return null; }
        public function getContent(): string { return ''; }
        /** @return array<string, mixed> */
        public function getParams(): array { return []; }
    }

    class InvalidRequestException extends \Exception {}
}

namespace Magento\Framework\App\Response {
    class Http
    {
        public function setContent(string $content): static { return $this; }
        public function setHeader(string $name, string $value, bool $replace = false): static { return $this; }
    }

    interface RedirectInterface
    {
        /** @param array<string, mixed> $arguments */
        public function redirect(Http $response, string $path, array $arguments = []): void;
    }
}

namespace Magento\Framework\App\Config {
    interface ScopeConfigInterface
    {
        public function getValue(string $path, string $scopeType = 'default', mixed $scopeCode = null): mixed;
        public function isSetFlag(string $path, string $scopeType = 'default', mixed $scopeCode = null): bool;
    }
}

namespace Magento\Framework\App\Config\Storage {
    interface WriterInterface
    {
        public function save(string $path, mixed $value, string $scope = 'default', int $scopeId = 0): void;
    }
}

namespace Magento\Framework\App\Cache {
    interface TypeListInterface
    {
        public function cleanType(string $typeCode): void;
    }
}

namespace Magento\Framework\App\Cache\Type {
    class Config
    {
        public const TYPE_IDENTIFIER = 'config';
    }
}

// ---------------------------------------------------------------------------
// Magento\Framework (misc)
// ---------------------------------------------------------------------------

namespace Magento\Framework {
    interface UrlInterface
    {
        /** @param array<string, mixed> $routeParams */
        public function getUrl(string $routePath = '', array $routeParams = []): string;
    }

    class DataObject
    {
        /** @param array<string, mixed> $data */
        public function __construct(array $data = []) {}
        public function toJson(): string { return ''; }
    }

    class Authorization
    {
        public function isAllowed(string $resource): bool { return false; }
    }
}

namespace Magento\Framework\Event {
    interface ManagerInterface {}
}

namespace Magento\Framework\Message {
    interface ManagerInterface
    {
        public function addErrorMessage(string $message): static;
        public function addSuccessMessage(string $message): static;
    }
}

namespace Magento\Framework\Module {
    interface ModuleListInterface {}
}

namespace Magento\Framework\Controller\Result {
    class RedirectFactory
    {
        public function create(): Redirect { return new Redirect(); }
    }

    class Redirect
    {
        public function setUrl(string $url): static { return $this; }
        /** @param array<string, mixed> $params */
        public function setPath(string $path, array $params = []): static { return $this; }
    }

    class JsonFactory
    {
        public function create(): Json { return new Json(); }
    }

    class Json
    {
        public function setData(mixed $data): static { return $this; }
        public function setHeader(string $name, string $value, bool $replace = false): static { return $this; }
    }
}

namespace Magento\Framework\Locale {
    class Resolver
    {
        public function getLocale(): string { return ''; }
    }
}

namespace Magento\Framework\View\Asset {
    interface Repository
    {
        /** @param array<string, mixed> $params */
        public function getUrl(string $fileId, array $params = []): string;
    }
}

namespace Magento\Framework\View\Element {
    abstract class AbstractBlock {}
}

namespace Magento\Framework\Stdlib\DateTime {
    interface TimezoneInterface
    {
        public function formatDateTime(\DateTimeInterface $date, int $dateType, int $timeType): string;
    }
}

namespace Magento\Framework\Data\Form\Element {
    abstract class AbstractElement
    {
        public function getHtmlId(): string { return ''; }
        public function getData(string $key = '', mixed $index = null): mixed { return null; }
    }
}

namespace Magento\Framework\Exception {
    class NoSuchEntityException extends \Exception {}
}

// ---------------------------------------------------------------------------
// Magento\Checkout
// ---------------------------------------------------------------------------

namespace Magento\Checkout\Model {
    class Session
    {
        public function getLastRealOrder(): \Magento\Sales\Model\Order { return new \Magento\Sales\Model\Order(); }
    }

    interface ConfigProviderInterface
    {
        /** @return array<string, mixed> */
        public function getConfig(): array;
    }
}

// ---------------------------------------------------------------------------
// Magento\Sales\Api
// ---------------------------------------------------------------------------

namespace Magento\Sales\Api\Data {
    interface OrderInterface
    {
        public function getId(): ?int;
        public function getExtOrderId(): ?string;
        public function setExtOrderId(string $id): static;
    }
}

namespace Magento\Sales\Api {
    interface OrderRepositoryInterface
    {
        public function get(int $id): \Magento\Sales\Api\Data\OrderInterface;
        public function save(\Magento\Sales\Api\Data\OrderInterface $entity): \Magento\Sales\Api\Data\OrderInterface;
    }

    interface RefundInvoiceInterface
    {
        /** @param array<string, mixed> $items */
        public function execute(int $invoiceId, array $items = [], bool $isOnline = false): int;
    }
}

// ---------------------------------------------------------------------------
// Magento\Sales\Model
// ---------------------------------------------------------------------------

namespace Magento\Sales\Model {
    class OrderRepository implements \Magento\Sales\Api\OrderRepositoryInterface
    {
        public function get(int $id): \Magento\Sales\Api\Data\OrderInterface { return new Order(); }
        public function save(\Magento\Sales\Api\Data\OrderInterface $entity): \Magento\Sales\Api\Data\OrderInterface { return $entity; }
    }

    class Order implements \Magento\Sales\Api\Data\OrderInterface
    {
        public const STATE_NEW = 'new';
        public const STATE_PENDING_PAYMENT = 'pending_payment';
        public const STATE_PROCESSING = 'processing';
        public const STATE_COMPLETE = 'complete';
        public const STATE_CLOSED = 'closed';
        public const STATE_CANCELED = 'canceled';
        public const STATE_HOLDED = 'holded';

        public function getId(): ?int { return null; }
        public function getIncrementId(): ?string { return null; }
        public function getStatus(): ?string { return null; }
        public function getState(): ?string { return null; }
        public function getStatusLabel(): mixed { return null; }
        public function setState(string $state): static { return $this; }
        public function setStatus(string $status): static { return $this; }
        public function getPayment(): ?\Magento\Sales\Model\Order\Payment { return null; }
        public function getInvoiceCollection(): \Magento\Sales\Model\ResourceModel\Order\Invoice\Collection { return new \Magento\Sales\Model\ResourceModel\Order\Invoice\Collection(); }
        public function addCommentToStatusHistory(string $comment): static { return $this; }
        public function getExtOrderId(): ?string { return null; }
        public function setExtOrderId(string $id): static { return $this; }
        public function getCustomerId(): ?int { return null; }
        public function getCustomerFirstname(): ?string { return null; }
        public function getCustomerLastname(): ?string { return null; }
        public function getGrandTotal(): float { return 0.0; }
        public function getSubtotal(): float { return 0.0; }
        public function getBaseTaxAmount(): float { return 0.0; }
        public function getShippingAmount(): float { return 0.0; }
        public function getOrderCurrencyCode(): ?string { return null; }
        public function getCreatedAt(): ?string { return null; }
        public function getProtectCode(): ?string { return null; }
        public function getBillingAddress(): ?\Magento\Sales\Model\Order\Address { return null; }
        /** @return array<int, mixed> */
        public function getItems(): array { return []; }
    }
}

namespace Magento\Sales\Model\Order {
    class Payment
    {
        public function getMethod(): string { return ''; }
        public function getMethodInstance(): \Magento\Payment\Model\MethodInterface
        {
            return new class implements \Magento\Payment\Model\MethodInterface {
                public function getTitle(): string { return ''; }
            };
        }
    }

    class Address
    {
        public function getFirstname(): ?string { return null; }
        public function getLastname(): ?string { return null; }
        public function getEmail(): ?string { return null; }
        /** @return string[] */
        public function getStreet(): array { return ['']; }
        public function getCity(): ?string { return null; }
        public function getCountryId(): ?string { return null; }
        public function getPostcode(): ?string { return null; }
    }

    class Invoice
    {
        public function getId(): ?int { return null; }
    }
}

namespace Magento\Sales\Model\ResourceModel\Order\Invoice {
    class Collection
    {
        public function getFirstItem(): ?\Magento\Sales\Model\Order\Invoice { return null; }
    }
}

namespace Magento\Sales\Block\Adminhtml\Order {
    class View extends \Magento\Backend\Block\Template
    {
        public function getOrderId(): ?int { return null; }
        public function getOrder(): \Magento\Sales\Model\Order { return new \Magento\Sales\Model\Order(); }
        /** @param array<string, mixed> $data */
        public function addButton(string $buttonId, array $data): void {}
    }
}

// ---------------------------------------------------------------------------
// Magento\Backend
// ---------------------------------------------------------------------------

namespace Magento\Backend\App {
    class Action
    {
        /** @var \Magento\Framework\Authorization */
        protected $_authorization;

        public function __construct(\Magento\Backend\App\Action\Context $context) {}
        public function getRequest(): \Magento\Framework\App\Request\Http { return new \Magento\Framework\App\Request\Http(); }
    }
}

namespace Magento\Backend\App\Action {
    class Context {}
}

namespace Magento\Backend\Block {
    class Template extends \Magento\Framework\View\Element\AbstractBlock
    {
        /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface */
        protected $_localeDate;

        /** @param array<string, mixed> $data */
        public function __construct(\Magento\Backend\Block\Template\Context $context, array $data = []) {}
        public function getRequest(): \Magento\Framework\App\Request\Http { return new \Magento\Framework\App\Request\Http(); }
        public function toHtml(...$args): string { return ''; }
    }
}

namespace Magento\Backend\Block\Template {
    class Context {}
}

// ---------------------------------------------------------------------------
// Magento\Config
// ---------------------------------------------------------------------------

namespace Magento\Config\Block\System\Config\Form {
    class Field extends \Magento\Backend\Block\Template
    {
        public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element): string { return ''; }
    }
}

// ---------------------------------------------------------------------------
// Magento\Payment
// ---------------------------------------------------------------------------

namespace Magento\Payment\Model {
    interface MethodInterface
    {
        public function getTitle(): string;
    }

    interface InfoInterface {}
}

namespace Magento\Payment\Model\Method {
    class Adapter
    {
        public function __construct(
            \Magento\Framework\Event\ManagerInterface $eventManager,
            \Magento\Payment\Gateway\Config\ValueHandlerPoolInterface $valueHandlerPool,
            \Magento\Payment\Gateway\Data\PaymentDataObjectFactory $paymentDataObjectFactory,
            string $code,
            string $formBlockType,
            string $infoBlockType
        ) {}

        public function getCode(): string { return ''; }
        public function isActive(?int $storeId = null): bool { return false; }
        public function getTitle(): string { return ''; }
    }
}

namespace Magento\Payment\Gateway\Config {
    interface ValueHandlerPoolInterface {}
}

namespace Magento\Payment\Gateway\Data {
    class PaymentDataObjectFactory {}
}

// ---------------------------------------------------------------------------
// Magento\PageCache
// ---------------------------------------------------------------------------

namespace Magento\PageCache\Model\Cache {
    class Type
    {
        public const TYPE_IDENTIFIER = 'full_page';
    }
}

// ---------------------------------------------------------------------------
// Magento\Store
// ---------------------------------------------------------------------------

namespace Magento\Store\Model {
    class Store
    {
        public function getBaseCurrencyCode(): string { return ''; }
    }

    class StoreManager
    {
        public function getStore(): Store { return new Store(); }
    }

    interface ScopeInterface
    {
        public const SCOPE_STORE = 'store';
    }
}
