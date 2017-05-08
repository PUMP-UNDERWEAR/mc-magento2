<?php
/**
 * mc-magento2 Magento Component
 *
 * @category Ebizmarts
 * @package mc-magento2
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 5/3/17 3:28 PM
 * @file: Getresponse.php
 */

namespace Ebizmarts\MailChimp\Controller\Adminhtml\Errors;

use Magento\Framework\Controller\ResultFactory;

class Getresponse extends \Magento\Backend\App\Action
{
    /**
     * @var ResultFactory
     */
    protected $_resultFactory;
    /**
     * @var \Ebizmarts\MailChimp\Model\MailChimpErrorsFactory
     */
    protected $_errorsFactory;
    /**
     * @var \Ebizmarts\MailChimp\Model\Api\Result
     */
    protected $_result;
    /**
     * @var \Ebizmarts\MailChimp\Helper\Data
     */
    protected $_helper;

    /**
     * Getresponse constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param ResultFactory $resultFactory
     * @param \Ebizmarts\MailChimp\Model\MailChimpErrorsFactory $errorsFactory
     * @param \Ebizmarts\MailChimp\Helper\Data $helper
     * @param \Ebizmarts\MailChimp\Model\Api\Result $result
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Ebizmarts\MailChimp\Model\MailChimpErrorsFactory $errorsFactory,
        \Ebizmarts\MailChimp\Helper\Data $helper,
        \Ebizmarts\MailChimp\Model\Api\Result $result
    ) {
        parent::__construct($context);
        $this->_resultFactory       = $resultFactory;
        $this->_errorsFactory       = $errorsFactory;
        $this->_result              = $result;
        $this->_helper              = $helper;
    }

    public function execute()
    {
        $errorId    = $this->getRequest()->getParam('id');
        $errors = $this->_errorsFactory->create();
        $errors->getResource()->load($errors, $errorId);
        $batchId = $errors->getBatchId();
        $files = $this->_result->getBatchResponse($batchId,$errors->getStoreId());
        $fileContent = [];
        foreach ($files as $file)
        {
            $items = json_decode(file_get_contents($file));
            foreach ($items as $item)
            {
                $content = array(
                    'status_code'=>$item->status_code,
                    'operation_id'=>$item->operation_id,
                    'response'=>json_decode($item->response)
                );
                $fileContent[] = $content;
            }
            unlink($file);
        }
        $resultJson =$this->_resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setHeader('Content-disposition', 'attachment; filename='.$batchId.'.json');
        $resultJson->setHeader('Content-type', 'application/json');
        $data = json_encode($fileContent, JSON_PRETTY_PRINT);
        $resultJson->setJsonData($data);
        $baseDir = $this->_helper->getBaseDir();
        if (is_dir($baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . \Ebizmarts\MailChimp\Model\Api\Result::MAILCHIMP_TEMP_DIR . DIRECTORY_SEPARATOR . $batchId)) {
            rmdir($baseDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . \Ebizmarts\MailChimp\Model\Api\Result::MAILCHIMP_TEMP_DIR . DIRECTORY_SEPARATOR . $batchId);
        }
        return $resultJson;
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ebizmarts_MailChimp::errors_admin_view');
    }

}