<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Helper;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadHelper
{
    /**
     * List of allowed file extensions
     * @var array
     */
    private $imageExtensions;

    /**
     * @var array
     */
    private $modVars;

    /**
     * @var string
     */
    private $avatarPath;

    /**
     * UploadHelper constructor.
     *
     * @param array $modVars
     * @param string $avatarPath
     */
    public function __construct(
        $modVars = [],
        $avatarPath = ''
    ) {
        $this->imageExtensions = ['gif', 'jpeg', 'jpg', 'png'];
        $this->modVars = $modVars;
        $this->avatarPath = $avatarPath;
    }

    /**
     * Process a given upload file.
     *
     * @param UploadedFile $file
     * @param int $userId
     * @return The resulting file name
     */
    public function handleUpload(UploadedFile $file, $userId = 0)
    {
        $allowUploads = isset($this->modVars['allowUploads']) && true === boolval($this->modVars['allowUploads']);
        if (!$allowUploads) {
            return '';
        }
        if (!file_exists($this->avatarPath) || !is_readable($this->avatarPath) || !is_writable($this->avatarPath)) {
            return '';
        }

        if (UPLOAD_ERR_OK != $file->getError()) {
            return '';
        }
        if (!is_numeric($userId) || $userId < 1) {
            return '';
        }

        $filePath = $file->getRealPath();

        // check for file size limit
        if (!$this->modVars['shrinkLargeImages'] && filesize($filePath) > $this->modVars['maxSize']) {
            unlink($filePath);

            return '';
        }

        // Get image information
        $imageInfo = getimagesize($filePath);
        if (!$imageInfo) {
            // file is not an image
            unlink($filePath);

            return '';
        }

        $extension = image_type_to_extension($imageInfo[2], false);
        // check for image type
        if (!in_array($extension, $this->imageExtensions)) {
            unlink($filePath);

            return '';
        }

        // check for image dimensions limit
        $isTooLarge = $imageInfo[0] > $this->modVars['maxWidth'] || $imageInfo[1] > $this->modVars['maxHeight'];

        if ($isTooLarge && !$this->modVars['shrinkLargeImages']) {
            unlink($filePath);

            return '';
        }

        // everything's OK, so move the file
        $avatarFileNameWithoutExtension = 'pers_' . $userId;
        $avatarFileName = $avatarFileNameWithoutExtension . '.' . $extension;
        $avatarFilePath = $this->avatarPath . '/' . $avatarFileName;

        // delete old user avatar
        foreach ($this->imageExtensions as $ext) {
            $oldFilePath = $this->avatarPath . '/' . $avatarFileNameWithoutExtension . '.' . $ext;
            if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }
        }

        $file->move($this->avatarPath, $avatarFileName);

        if ($isTooLarge && $this->modVars['shrinkLargeImages']) {
            // resize the image
            $imagine = new Imagine();
            $image = $imagine->open($avatarFilePath);
            $image->resize(new Box($this->modVars['maxWidth'], $this->modVars['maxHeight']))
                  ->save($avatarFilePath);
        }

        chmod($avatarFilePath, 0644);

        return $avatarFileName;
    }
}
