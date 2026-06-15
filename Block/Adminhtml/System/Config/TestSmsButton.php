<?php

declare(strict_types=1);

namespace Muon\SMSNotification\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Renders a "Send Test SMS" button in the module's system configuration.
 */
class TestSmsButton extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Muon_SMSNotification::system/config/test-sms-button.phtml';

    /**
     * Remove scope label / use-default checkbox for this control row.
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $this->addData([
            'button_label' => (string)__('Send Test SMS'),
            'html_id' => $element->getHtmlId(),
            'ajax_url' => $this->getUrl('muon_sms/test/sendSms'),
        ]);

        return $this->_toHtml();
    }
}
