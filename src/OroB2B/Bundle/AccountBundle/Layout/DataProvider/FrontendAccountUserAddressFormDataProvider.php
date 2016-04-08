<?php

namespace OroB2B\Bundle\AccountBundle\Layout\DataProvider;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAction;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use Symfony\Component\Form\FormFactory;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserTypedAddressType;
use Symfony\Component\Form\FormInterface;

class FrontendAccountUserAddressFormDataProvider extends AbstractServerRenderDataProvider
{
    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var array|FormAccessorInterface[]
     */
    private $data = [];

    /**
     * @var array|FormInterface[]
     */
    private $forms = [];

    /**
     * @param FormFactory $formFactory
     */
    public function __construct(FormFactory $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /** {@inheritdoc} */
    public function getData(ContextInterface $context)
    {
        /**
         * @var AccountUserAddress $accountUserAddress
         */
        $accountUserAddress = $context->data()->get('entity');

        $addressId = $accountUserAddress->getId();

        if (!isset($this->data[$addressId])) {
            $this->data[$addressId] = new FormAccessor(
                $this->getForm($accountUserAddress),
                $this->getAction($context->data()->get('accountUser'), $addressId)
            );
        }

        return $this->data[$addressId];
    }

    /**
     * @param AccountUser $accountUser
     * @param integer $addressId
     * @return FormAction
     */
    private function getAction(AccountUser $accountUser, $addressId)
    {
        if ($addressId) {
            return FormAction::createByRoute(
                'orob2b_account_frontend_account_user_address_update',
                ['id' => $addressId, 'entityId' => $accountUser->getId()]
            );
        } else {
            return FormAction::createByRoute(
                'orob2b_account_frontend_account_user_address_create',
                ['entityId' => $accountUser->getId()]
            );
        }
    }

    /**
     * @param AccountUserAddress $accountAddress
     * @return FormInterface
     */
    public function getForm(AccountUserAddress $accountAddress)
    {
        $addressId = $accountAddress->getId();

        if (!isset($this->forms[$addressId])) {
            $this->forms[$addressId] = $this->formFactory->create(
                AccountUserTypedAddressType::NAME,
                $accountAddress
            );
        }

        return $this->forms[$addressId];
    }
}
