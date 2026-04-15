<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fields.Gallery
 */

declare(strict_types=1);

use Joomla\CMS\Factory;
use Joomla\Component\Fields\Administrator\Plugin\FieldsPlugin;

defined('_JEXEC') or die;

/**
 * Legacy plugin entry class required for plugin discovery in Joomla admin.
 */
final class PlgFieldsGallery extends FieldsPlugin
{
    /**
     * @var    boolean
     */
    protected $autoloadLanguage = true;

    /**
     * Returns the custom field type handled by this plugin.
     */
    public function onCustomFieldsGetTypes(): array
    {
        return ['gallery'];
    }

    /**
     * Prepare custom field DOM and ensure backend assets are loaded.
     *
     * @param   \SimpleXMLElement  $field   Field XML element
     * @param   \DOMElement        $parent  Parent DOM element
     * @param   \JForm             $form    Form object
     *
     * @return  \DOMElement|null
     */
    public function onCustomFieldsPrepareDom($field, \DOMElement $parent, $form)
    {
        $result = parent::onCustomFieldsPrepareDom($field, $parent, $form);

        if (!$result) {
            return $result;
        }

        if (Factory::getApplication()->isClient('administrator')) {
            $wa = Factory::getApplication()->getDocument()->getWebAssetManager();

            $wa->registerAndUseScript(
                'plg_fields_gallery.sortable',
                'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js',
                [],
                ['defer' => true]
            );

            $wa->registerAndUseScript(
                'plg_fields_gallery.gallery',
                'plg_fields_gallery/gallery.js',
                ['core', 'media-manager', 'plg_fields_gallery.sortable'],
                ['type' => 'module', 'defer' => true],
                ['version' => 'auto', 'relative' => true]
            );

            $wa->registerAndUseStyle(
                'plg_fields_gallery.gallery',
                'plg_fields_gallery/gallery.css',
                [],
                ['version' => 'auto', 'relative' => true]
            );
        }

        return $result;
    }
}
