<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

use Magento\Backend\App\Action\Context;

class Ajax extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Magento\Framework\App\Response\Http
     */
    private $http;

    /**
     * @var \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Type
     */
    private $ruleType;

    /**
     * @var \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Condition
     */
    private $ruleCondition;

    /**
     * @var \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value
     */
    private $ruleValue;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonEncoder;

    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * Ajax constructor.
     *
     * @param Context                                                       $context
     * @param \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Type      $ruleType
     * @param \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Condition $ruleCondition
     * @param \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value     $ruleValue
     * @param \Magento\Framework\Json\Helper\Data                           $jsonEncoder
     * @param \Magento\Framework\App\Response\Http                          $http
     * @param \Magento\Framework\Escaper                                    $escaper
     */
    public function __construct(
        Context $context,
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Type $ruleType,
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Condition $ruleCondition,
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Rules\Value $ruleValue,
        \Magento\Framework\Json\Helper\Data $jsonEncoder,
        \Magento\Framework\App\Response\Http $http,
        \Magento\Framework\Escaper $escaper
    ) {
        $this->ruleType = $ruleType;
        $this->ruleCondition = $ruleCondition;
        $this->ruleValue = $ruleValue;
        $this->jsonEncoder = $jsonEncoder;
        $this->escaper = $escaper;
        parent::__construct($context);
        $this->http = $http;
    }

    /**
     * Check the permission to run it.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Dotdigitalgroup_Email::exclusion_rules');
    }

    /**
     * Execute method.
     *
     * @return void
     */
    public function execute()
    {
        $attribute = $this->getRequest()->getParam('attribute');
        $conditionName = $this->getRequest()->getParam('condition');
        $valueName = $this->getRequest()->getParam('value');
        if ($attribute && $conditionName && $valueName) {
            $type = $this->ruleType->getInputType($attribute);
            $conditionOptions = $this->ruleCondition->getInputTypeOptions($type);
            $response['condition'] = $this->_getOptionHtml(
                'conditions',
                $conditionName,
                $conditionOptions
            );

            $elmType = $this->ruleValue->getValueElementType($attribute);
            if ($elmType == 'select') {
                $valueOptions = $this->ruleValue->getValueSelectOptions($attribute);
                $response['cvalue'] = $this->_getOptionHtml(
                    'cvalue',
                    $valueName,
                    $valueOptions
                );
            } elseif ($elmType == 'text') {
                $html = "<input title='cvalue' class='ddg-rules-conditions' id='' name=$valueName />";
                $response['cvalue'] = $html;
            }
            $this->http->getHeaders()->clearHeaders();
            $this->http->setHeader('Content-Type', 'application/json')
                ->setBody(
                    $this->jsonEncoder->jsonEncode($response)
                );
        }
    }

    /**
     * Get select options.
     *
     * @param string $title
     * @param string $name
     * @param array $options
     *
     * @return string
     */
    private function _getOptionHtml($title, $name, $options)
    {
        $block = $this->_view->getLayout()->createBlock(
            \Magento\Framework\View\Element\Html\Select::class
        );
        $block->setOptions($options)
            ->setId('')
            ->setClass('ddg-rules-conditions')
            ->setTitle($title)
            ->setName($name);

        return $block->toHtml();
    }
}
