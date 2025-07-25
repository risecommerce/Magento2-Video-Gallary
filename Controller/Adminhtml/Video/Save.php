<?php

/**
 * PHP version 7
 *
 * @category  Risecommerce
 * @package   Risecommerce_VideoGallery
 * @author    Risecommerce <magento@risecommerce.com>
 * @copyright 2019 This file was generated by Risecommerce
 * @license   https://www.risecommerce.com  Open Software License (OSL 3.0)
 * @link      https://www.risecommerce.com
 */

namespace Risecommerce\VideoGallery\Controller\Adminhtml\Video;

use Magento\Backend\App\Action;
use Magento\Framework\Filesystem\Driver\File;
use Magento\TestFramework\ErrorLog\Logger;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Image\AdapterFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Request\DataPersistorInterface;


class Save extends \Magento\Backend\App\Action
{

    /**
     * Uploadfactory
     *
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $uploaderFactory;

    /**
     * AdapterFactory
     *
     * @var \Magento\Framework\Image\AdapterFactory
     */
    protected $adapterFactory;

    /**
     * Filesystem
     *
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;
    /**
     * @var File
     */
    private $file;

    /**
     * Initialize
     *
     * @param Action\Context $context Initialize Context
     * @param UploaderFactory $uploaderFactory Initialize uploadfactory
     * @param AdapterFactory $adapterFactory Initialize adapterfactory
     * @param Filesystem $filesystem Initialize  filesystem
     * @param File $file
     */
    public function __construct(
        Action\Context $context,
        UploaderFactory $uploaderFactory,
        AdapterFactory $adapterFactory,
        Filesystem $filesystem,
        File $file
    ) {
        parent::__construct($context);
        $this->uploaderFactory = $uploaderFactory;
        $this->adapterFactory = $adapterFactory;
        $this->filesystem = $filesystem;
        $this->file = $file;
    }

    /**
     * {@inheritdoc}
     *
     * @return NUll
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Risecommerce_VideoGallery::save');
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = false;
        $data = $this->getRequest()->getPostValue();
        /**
         * ResuleRedirect
         *
         * @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect
         */

        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->_validatedParams($data)) {

            /**
             * Video Model
             *
             * @var \Risecommerce\videoGallery\Model\Video $model
             */

            $model = $this->_objectManager->create(\Risecommerce\VideoGallery\Model\Video::class);

            $id = $this->getRequest()->getParam('id');

            if ($id) {
                $model->load($id);
            }

            if ($data['Video_Upload_Method'] == 'youtube' || $data['Video_Upload_Method'] == 'vimeo') {
                //Delete VIdeo at edit time
                if ($id && ($this->getRequest()->getParam('old_video'))) {
                    $old_video = $this->getRequest()->getParam('old_video');
                    $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
                    $destinationPath = $mediaDirectory->getAbsolutePath('/risecommerce/video_gallery/');
                    if ($old_video && $this->file->isExists($destinationPath . $old_video)) {
                        $this->file->deleteFile($destinationPath . $old_video);
                    }
                }

                $model->setData('file', $data[$data['Video_Upload_Method']]);
            } else {
                if ($id) {
                    $old_video = $this->getRequest()->getParam('old_video');
                    if ($this->getRequest()->getFiles('From_PC')['name'] != '') {
                        $files = $this->getRequest()->getFiles('From_PC');
                        if (isset($files['name']) && $files['name'] != '') {
                            $result = $this->imgUpload($files);
                            if ($result[0]) {
                                //Delete VIdeo at edit time
                                if ($id) {
                                    $old_video = $this->getRequest()->getParam('old_video');
                                    $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
                                    $destinationPath = $mediaDirectory->getAbsolutePath('/risecommerce/video_gallery/');
                                    if ($old_video && $this->file->isExists($destinationPath . $old_video)) {
                                        $this->file->deleteFile($destinationPath . $old_video);
                                    }
                                }
                                $model->setData('file', $result[1]);
                            }
                        } else {
                            $this->messageManager->addErrorMessage(__('Only mp4 files allowed.'));
                        }
                    } else {
                        $model->setData('file', $old_video);
                    }
                } else {
                    $old_video = null;
                    $files = $this->getRequest()->getFiles('From_PC');
                    if (isset($files['name']) && $files['name'] != '') {
                        $result = $this->imgUpload($files);
                        if ($result[0]) {
                            $model->setData('file', $result[1]);
                        }
                    } else {
                        $this->messageManager->addErrorMessage(__('Only mp4 files allowed.'));
                    }
                }
            }

            $stores = in_array(0, $data['stores']) ? [0] : $data['stores'];
            $customer_groups = in_array('all', $data['customer_groups']) ? ['all'] : $data['customer_groups'];

            $model->setData('title', $data['title']);
            $model->setData('description', $data['description']);
            $model->setData('position', $data['position']);
            $model->setData('is_active', $data['is_active']);
            $model->setData('video_upload_method', $data['Video_Upload_Method']);
            $model->setData('stores', implode(',', $stores));
            $model->setData('customer_groups', implode(',', $customer_groups));

            $this->_eventManager->dispatch(
                'videogallery_video_prepare_save',
                ['post' => $model, 'request' => $this->getRequest()]
            );

            if ($data['Video_Upload_Method'] == 'youtube' || $data['Video_Upload_Method'] == 'vimeo' || $result == true || $old_video != '') {
                try {
                    $model->save();
                    $this->messageManager->addSuccess(__('Video Details are saved successfully.'));
                    $this->_objectManager->get(\Magento\Backend\Model\Session::class)->setFormData(false);
                    if ($this->getRequest()->getParam('back')) {
                        return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId(), '_current' => true]);
                    }
                    return $resultRedirect->setPath('*/*/');
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $this->messageManager->addError($e->getMessage());
                } catch (\RuntimeException $e) {
                    $this->messageManager->addError($e->getMessage());
                } catch (\Exception $e) {
                    $this->messageManager->addException($e, __('Something went wrong while saving the post.'));
                }
            }
        } else {
            $this->messageManager->addError(__(implode("<br>", $this->_validatedParams($data))));
        }
        $this->_getSession()->setFormData($data);
        return $resultRedirect->setPath('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
    }

    /**
     * Upload Video File
     *
     * @return boolean
     */
    public function imgUpload()
    {

        $files = $this->getRequest()->getFiles('From_PC');

        if ($files['name']) {
            try {
                if ($files['size'] > 0) {
                    $uploaderFactory = $this->uploaderFactory->create(['fileId' => 'From_PC']);
                    $uploaderFactory->setAllowedExtensions(['mp4']);
                    if ($uploaderFactory->getFileExtension() != 'mp4') {
                        $this->messageManager->addErrorMessage(__('Only mp4 files allowed.'));
                        return false;
                    }
                    $imageAdapter = $this->adapterFactory->create();
                    $uploaderFactory->setAllowRenameFiles(true);
                    $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
                    $destinationPath = $mediaDirectory->getAbsolutePath('/risecommerce/video_gallery/');
                    $uploaderFactory = $uploaderFactory->save($destinationPath);
                } else {
                    $this->messageManager->addErrorMessage(__('The file size should not exceed '.ini_get('upload_max_filesize').'.'));
                    return false;
                }

                if (!$uploaderFactory) {
                    throw new \LocalizedException(
                        __('File cannot be saved to path: $1', $destinationPath)
                    );
                } else {
                    return [true,$uploaderFactory['file']];
                }
            } catch (\Exception $e) {
                $this->messageManager->addError(__($e->getMessage()));
                return false;
            }
        }
    }

    /**
     * Check Validation
     *
     * @param data $data parameter data
     *
     * @return $errors
     */
   private function _validatedParams($data)
{
    $errors = [];

    // Validate Video_Upload_Method
    if (empty($data['Video_Upload_Method'])) {
        $errors[] = __('%fieldName is a required field.', ['fieldName' => 'Video Upload Method']);
    } else {
        if ($data['Video_Upload_Method'] === 'youtube') {
            if (empty($data['youtube'])) {
                $errors[] = __('%fieldName is a required field.', ['fieldName' => 'Youtube Method']);
            }
        }
    }

    // Validate position - required
    if (!isset($data['position']) || $data['position'] === '') {
        $errors[] = __('%fieldName is a required field.', ['fieldName' => 'position']);
    } elseif (!ctype_digit(strval($data['position']))) {
        // Validate position is digits only
        $errors[] = __('%fieldName only accept digits.', ['fieldName' => 'position']);
    }

    return empty($errors) ? false : $errors;
}

}
