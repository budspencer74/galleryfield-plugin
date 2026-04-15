<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Ajax.Galleryupload
 */

declare(strict_types=1);

namespace Joomla\Plugin\Ajax\Galleryupload\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

final class PlgAjaxGalleryupload extends CMSPlugin
{
    /**
     * @var    boolean
     */
    protected $autoloadLanguage = true;

    /**
     * AJAX endpoint for com_ajax&plugin=galleryupload.
     *
     * @return  array
     *
     * @throws  \RuntimeException
     */
    public function onAjaxGalleryupload(): array
    {
        $app   = Factory::getApplication();
        $input = $app->getInput();

        if (!$app->isClient('administrator')) {
            throw new \RuntimeException('Unauthorized context.', 403);
        }

        $user = $app->getIdentity();

        if (!$user || $user->guest) {
            throw new \RuntimeException('You must be logged in.', 403);
        }

        $upload = $input->files->get('file', null, 'raw');

        if (!$upload || empty($upload['tmp_name']) || (int) ($upload['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('No valid file received.', 400);
        }

        $safeName = File::makeSafe((string) ($upload['name'] ?? ''));

        if ($safeName === '') {
            throw new \RuntimeException('Invalid file name.', 400);
        }

        $extension = strtolower((string) File::getExt($safeName));
        $allowed   = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];

        if (!in_array($extension, $allowed, true)) {
            throw new \RuntimeException('Unsupported file type.', 400);
        }

        $targetFolder = $this->getUploadFolder();

        if (!Folder::exists(JPATH_ROOT . '/' . $targetFolder) && !Folder::create(JPATH_ROOT . '/' . $targetFolder)) {
            throw new \RuntimeException('Unable to create upload folder.', 500);
        }

        $baseName   = File::stripExt($safeName);
        $fileName   = $safeName;
        $counter    = 1;
        $targetPath = JPATH_ROOT . '/' . $targetFolder . '/' . $fileName;

        while (File::exists($targetPath)) {
            $fileName   = $baseName . '-' . $counter . '.' . $extension;
            $targetPath = JPATH_ROOT . '/' . $targetFolder . '/' . $fileName;
            $counter++;
        }

        if (!File::upload($upload['tmp_name'], $targetPath)) {
            throw new \RuntimeException('Failed to move uploaded file.', 500);
        }

        return ['path' => $targetFolder . '/' . $fileName];
    }

    private function getUploadFolder(): string
    {
        $fieldsPlugin = PluginHelper::getPlugin('fields', 'gallery');
        $params       = new Registry($fieldsPlugin->params ?? '');
        $folder       = trim((string) $params->get('upload_folder', 'images/gallery'), '/');

        return $folder !== '' ? $folder : 'images/gallery';
    }
}
