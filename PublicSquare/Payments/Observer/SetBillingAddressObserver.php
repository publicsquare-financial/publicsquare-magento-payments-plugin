<?php
namespace PublicSquare\Payments\Observer;
class SetBillingAddressObserver implements \Magento\Framework\Event\ObserverInterface {
    protected \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository;
    protected \Magento\Customer\Model\AddressFactory $addressFactory;
    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\AddressFactory $addressFactory,
    ) {
        $this->customerRepository = $customerRepository;
        $this->addressFactory = $addressFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getQuote();
        $customer = $this->customerRepository->getById($quote->getCustomerId());
        if (!$quote->getBillingAddress()) {
            $billingAddressId = $customer->getDefaultBilling();
            if ($billingAddressId) {
                $billingAddress = $this->addressFactory->create()->load($billingAddressId);
                $quote->setBillingAddress($billingAddress)->importCustomerAddressData($billingAddress);
            }
        }
    }
}
