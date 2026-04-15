<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fields.Gallery
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

$images = [];

if (!empty($value)) {
    $decoded = json_decode((string) $value, true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $images = array_values(array_filter($decoded, static function ($image) {
            return is_array($image) && !empty($image['src']);
        }));
    }
}

$plugin      = PluginHelper::getPlugin('fields', 'gallery');
$params      = new Registry($plugin->params ?? '');
$uploadFolder = trim((string) $params->get('upload_folder', 'images/gallery'), '/');
$fieldId     = $field->id ?? 0;
$fieldName   = $field->name ?? '';
$inputName   = $fieldName !== '' ? 'jform[com_fields][' . $fieldName . ']' : '';
$inputId     = 'field-gallery-' . $fieldId;

HTMLHelper::_('jquery.framework');
?>
<div
    class="plg-fields-gallery"
    id="<?= htmlspecialchars($inputId, ENT_QUOTES, 'UTF-8'); ?>"
    data-upload-url="<?= htmlspecialchars('index.php?option=com_ajax&plugin=galleryupload&format=json', ENT_QUOTES, 'UTF-8'); ?>"
    data-upload-folder="<?= htmlspecialchars($uploadFolder, ENT_QUOTES, 'UTF-8'); ?>"
>
    <div class="plg-fields-gallery__toolbar">
        <label class="btn btn-secondary btn-sm mb-0">
            <span><?= htmlspecialchars('Upload images', ENT_QUOTES, 'UTF-8'); ?></span>
            <input class="plg-fields-gallery__file-input" type="file" accept="image/*" multiple hidden>
        </label>
        <button type="button" class="btn btn-outline-primary btn-sm plg-fields-gallery__media-btn">
            <?= htmlspecialchars('Select from Media Manager', ENT_QUOTES, 'UTF-8'); ?>
        </button>
    </div>

    <div class="plg-fields-gallery__dropzone" tabindex="0">
        <?= htmlspecialchars('Drop images here or click "Upload images".', ENT_QUOTES, 'UTF-8'); ?>
    </div>

    <div class="plg-fields-gallery__list" aria-live="polite" aria-label="Image list">
        <?php foreach ($images as $image) : ?>
            <article class="plg-fields-gallery__item" data-src="<?= htmlspecialchars((string) ($image['src'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <button type="button" class="btn btn-danger btn-sm plg-fields-gallery__remove" aria-label="Remove image">×</button>
                <img
                    class="plg-fields-gallery__thumb"
                    src="<?= htmlspecialchars((string) ($image['src'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                    alt=""
                    loading="lazy"
                >
                <div class="plg-fields-gallery__meta">
                    <input type="text" class="form-control form-control-sm plg-fields-gallery__title" placeholder="Title" value="<?= htmlspecialchars((string) ($image['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="text" class="form-control form-control-sm plg-fields-gallery__alt" placeholder="Alt text" value="<?= htmlspecialchars((string) ($image['alt'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <input
        type="hidden"
        class="plg-fields-gallery__value"
        name="<?= htmlspecialchars($inputName, ENT_QUOTES, 'UTF-8'); ?>"
        value="<?= htmlspecialchars(json_encode($images, JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8'); ?>"
    >
</div>
