<?php

/**
 * Payment
 *
 * @category  PublicSquare
 * @package   PublicSquare_Payments
 * @author    PublicSquare <info@publicsquare.com>
 * @copyright 2024 PublicSquare
 * @license   http://opensource.org/licenses/osl-3.0.php (OSL 3.0)
 * @link      https://publicsquare.com/
 */

namespace PublicSquare\Payments\Block\Adminhtml\System\Config\Fieldset;

/**
 * Fieldset renderer publicsquare solution
 */
class Payment extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * @var \Magento\Config\Model\Config
     */
    protected $_backendConfig;

    /**
     * @param \Magento\Backend\Block\Context      $context
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\View\Helper\Js   $jsHelper
     * @param \Magento\Config\Model\Config        $backendConfig
     * @param array                               $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        \Magento\Config\Model\Config $backendConfig,
        array $data = []
    ) {
        $this->_backendConfig = $backendConfig;
        parent::__construct($context, $authSession, $jsHelper, $data);
    } //end __construct()

    /**
     * Add custom css class
     *
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getFrontendClass($element)
    {
        $enabledString = $this->_isPaymentEnabled($element) ? ' enabled' : '';
        return parent::_getFrontendClass($element) . ' with-button' . $enabledString;
    } //end _getFrontendClass()

    /**
     * Check whether current payment method is enabled
     *
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return boolean
     */
    protected function _isPaymentEnabled($element)
    {
        $groupConfig   = $element->getGroup();
        $activityPaths = isset($groupConfig['activity_path']) ? $groupConfig['activity_path'] : [];

        if (!is_array($activityPaths)) {
            $activityPaths = [$activityPaths];
        }

        $isPaymentEnabled = true;
        foreach ($activityPaths as $activityPath) {
            $isPaymentEnabled = $isPaymentEnabled
                || (bool) (string) $this->_backendConfig->getConfigDataValue($activityPath);
        }

        return $isPaymentEnabled;
    } //end _isPaymentEnabled()

    /**
     * Return header title part of html for payment solution
     *
     * @param                                   \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return                                  string
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _getHeaderTitleHtml($element)
    {
        $html = '<div class="config-heading" >';

        $groupConfig = $element->getGroup();

        $disabledAttributeString = $this->_isPaymentEnabled($element) ? '' : ' disabled="disabled"';
        $disabledClassString     = $this->_isPaymentEnabled($element) ? '' : ' disabled';
        $htmlId                  = $element->getHtmlId();
        $html                   .= '<div class="button-container">
        <button type="button"' . $disabledAttributeString . ' class="button action-configure'
            . $disabledClassString . '" id="' . $htmlId . '-head" onclick="publicsquareToggleSolution.
        call(this, \'' . $htmlId . "', '" . $this->getUrl('adminhtml/*/state') . '\');
         return false;"><span class="state-closed">' . __('Configure') . '</span>
         <span class="state-opened">' . __('Close') . '</span></button>';
        if (!empty($groupConfig['more_url'])) {
            $html .= '<a class="link-more" href="' . $groupConfig['more_url'] . '" target="_blank">' . __(
                'Learn More'
            ) . '</a>';
        }
        if (!empty($groupConfig['demo_url'])) {
            $html .= '<a class="link-demo" href="' . $groupConfig['demo_url'] . '" target="_blank">' . __(
                'View Demo'
            ) . '</a>';
        }
        $html .= '</div>';
        $html .= '<div class="heading"><strong>' . $element->getLegend() . '</strong>';

        if ($element->getComment()) {
            $html .= '<span class="heading-intro">' . $element->getComment() . '</span>';
        }
        $html .= '</div></div>';
        return $html;
    } //end _getHeaderTitleHtml()

    /**
     * Return header comment part of html for payment solution
     *
     * @param                                         \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return                                        string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getHeaderCommentHtml($element)
    {
        return '';
    } //end _getHeaderCommentHtml()

    /**
     * Get collapsed state on-load
     *
     * @param                                         \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return                                        false
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _isCollapseState($element)
    {
        return false;
    } //end _isCollapseState()

    /**
     * @param                                         \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return                                        string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getExtraJs($element)
    {
        $script = "require(['jquery', 'prototype'], function(jQuery){
            window.publicsquareToggleSolution = function (id, url) {
                var doScroll = false;
                Fieldset.toggleCollapse(id, url);
                if ($(this).hasClassName(\"open\")) {
                    $$(\".with-button button.button\").each(function(anotherButton) {
                        if (anotherButton != this && $(anotherButton).hasClassName(\"open\")) {
                            $(anotherButton).click();
                            doScroll = true;
                        }
                    }.bind(this));
                }
                if (doScroll) {
                    var pos = Element.cumulativeOffset($(this));
                    window.scrollTo(pos[0], pos[1] - 45);
                }
            }
        });";
        return $this->_jsHelper->getScript($script);
    } //end _getExtraJs()
}//end class
