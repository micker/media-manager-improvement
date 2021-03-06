<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_media
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/type/interface.php';
require_once __DIR__ . '/type/default.php';

/**
 * Media Component File Type Model
 *
 * @since  3.6
 */
class MediaModelFileType
{
	/**
	 * List of available file type objects
	 *
	 * @var    array
	 * @since  3.6
	 */
	protected $availableFileTypes = array();

	/**
	 * List of available file type identifiers
	 *
	 * @var    array
	 * @since  3.6
	 */
	protected $defaultFileTypeIdentifiers = array(
		'default',
		'image',
		'pdf',
		'video',
	);

	/**
	 * Abstraction of the file type of $_file
	 *
	 * @var    MediaModelFileTypeInterface
	 * @since  3.6
	 */
	protected $fileType = null;

	/**
	 * Return a file type object
	 *
	 * @param   string                                 $filePath
	 * @param   MediaModelFileAdapterInterfaceAdapter  $fileAdapter
	 *
	 * @return  MediaModelFileTypeInterface
	 *
	 * @since   3.6
	 */
	public function getFileType($filePath, $fileAdapter)
	{
		/** @var $fileAdapter MediaModelFileAdapterInterfaceAdapter */

		// Loop through the available file types and match this file accordingly
		foreach ($this->getAvailableFileTypes() as $availableFileType)
		{
			/** @var $availableFileType MediaModelFileTypeInterface */

			// Detect the MIME-type
			$mimeType = $fileAdapter->setFilePath($filePath)->getMimeType();

			if (in_array($mimeType, $availableFileType->getMimeTypes()))
			{
				return $availableFileType;
			}

			if (in_array(JFile::getExt($filePath), $availableFileType->getExtensions()))
			{
				return $availableFileType;
			}
		}

		return $this->availableFileTypes['default'];
	}

	/**
	 * Method to get the support file types
	 *
	 * @return  array
	 *
	 * @since   3.6
	 */
	protected function getAvailableFileTypes()
	{
		if (empty($this->availableFileTypes))
		{
			foreach ($this->defaultFileTypeIdentifiers as $defaultFileTypeIdentifier)
			{
				$fileType = $this->getFileTypeObjectFromIdentifier($defaultFileTypeIdentifier);

				if ($fileType == false)
				{
					continue;
				}

				$this->availableFileTypes[$defaultFileTypeIdentifier] = $fileType;
			}

			// Allow plugins to modify this listing of file types
			$this->modifyAvailableFileTypes();
		}

		return $this->availableFileTypes;
	}

	/**
	 * Modify the list of available file types through the plugin event onMediaBuildFileTypes()
	 *
	 * @return  void
	 *
	 * @since   3.6
	 */
	protected function modifyAvailableFileTypes()
	{
		JPluginHelper::importPlugin('media');

		$dispatcher = JEventDispatcher::getInstance();
		$dispatcher->trigger('onMediaBuildFileTypes', array(&$this->availableFileTypes));
	}

	/**
	 * Get a file type object based on an identifier string
	 *
	 * @param   string  $identifier
	 *
	 * @return  bool|MediaModelFileTypeInterface
	 *
	 * @since  3.6
	 */
	protected function getFileTypeObjectFromIdentifier($identifier)
	{
		if (empty($identifier))
		{
			return false;
		}

		$identifierFile = __DIR__ . '/type/' . $identifier . '.php';

		if (!is_file($identifierFile))
		{
			return false;
		}

		include_once $identifierFile;

		$fileTypeClass = 'MediaModelFileType' . ucfirst($identifier);
		$fileType      = new $fileTypeClass;

		return $fileType;
	}
}
