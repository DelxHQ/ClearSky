<?php
/**
 * Saves extra data on runtime for different items
 */
namespace pocketmine\metadata;

use pocketmine\plugin\Plugin;
use pocketmine\utils\PluginException;

abstract class MetadataStore{
	/** @var \WeakMap[] */
	private $metadataMap = [];

	/**
	 * Adds a metadata value to an object.
	 *
	 * @param mixed         $subject
	 * @param string        $metadataKey
	 * @param MetadataValue $newMetadataValue
	 *
	 * @throws \Throwable
	 */
	public function setMetadata($subject, $metadataKey, MetadataValue $newMetadataValue){
		$owningPlugin = $newMetadataValue->getOwningPlugin();
		if($owningPlugin === null){
			throw new PluginException("Plugin cannot be null");
		}

		$key = $this->disambiguate($subject, $metadataKey);
		if(!isset($this->metadataMap[$key])){
			$entry = new \WeakMap();
			$this->metadataMap[$key] = $entry;
		}else{
			$entry = $this->metadataMap[$key];
		}
		$entry[$owningPlugin] = $newMetadataValue;
	}

	/**
	 * Returns all metadata values attached to an object. If multiple
	 * have attached metadata, each will value will be included.
	 *
	 * @param mixed  $subject
	 * @param string $metadataKey
	 *
	 * @return MetadataValue[]
	 *
	 * @throws \Throwable
	 */
	public function getMetadata($subject, $metadataKey){
		$key = $this->disambiguate($subject, $metadataKey);
		if(isset($this->metadataMap[$key])){
			return $this->metadataMap[$key];
		}else{
			return [];
		}
	}

	/**
	 * Tests to see if a metadata attribute has been set on an object.
	 *
	 * @param mixed  $subject
	 * @param string $metadataKey
	 *
	 * @return bool
	 *
	 * @throws \Throwable
	 */
	public function hasMetadata($subject, $metadataKey){
		return isset($this->metadataMap[$this->disambiguate($subject, $metadataKey)]);
	}

	/**
	 * Removes a metadata item owned by a plugin from a subject.
	 *
	 * @param mixed  $subject
	 * @param string $metadataKey
	 * @param Plugin $owningPlugin
	 *
	 * @throws \Throwable
	 */
	public function removeMetadata($subject, $metadataKey, Plugin $owningPlugin){
		$key = $this->disambiguate($subject, $metadataKey);
		if(isset($this->metadataMap[$key])){
			unset($this->metadataMap[$key][$owningPlugin]);
			if($this->metadataMap[$key]->count() === 0){
				unset($this->metadataMap[$key]);
			}
		}
	}

	/**
	 * Invalidates all metadata in the metadata store that originates from the
	 * given plugin. Doing this will force each invalidated metadata item to
	 * be recalculated the next time it is accessed.
	 *
	 * @param Plugin $owningPlugin
	 */
	public function invalidateAll(Plugin $owningPlugin){
		/** @var $values MetadataValue[] */
		foreach($this->metadataMap as $values){
			if(isset($values[$owningPlugin])){
				$values[$owningPlugin]->invalidate();
			}
		}
	}

	/**
	 * Creates a unique name for the object receiving metadata by combining
	 * unique data from the subject with a metadataKey.
	 *
	 * @param Metadatable $subject
	 * @param string      $metadataKey
	 *
	 * @return string
	 *
	 * @throws \InvalidArgumentException
	 */
	public abstract function disambiguate(Metadatable $subject, $metadataKey);
}