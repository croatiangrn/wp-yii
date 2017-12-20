<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\cart;
use Rnd;
use rnd\base\Component;

/**
 * Class Cart provides basic cart functionality (adding, removing, clearing, listing items). You can extend this class and
 * override it in the application configuration to extend/customize the functionality
 *
 * @package yii2mod\cart
 */
class Cart extends Component
{
	/**
	 * @var string CartItemInterface class name
	 */
	const ITEM_PRODUCT = '\rnd\cart\CartItemInterface';

	/**
	 * Override this to provide custom (e.g. database) storage for cart data
	 *
	 * @var StorageInterface
	 */
	public $storageClass = '\rnd\cart\SessionStorage';

	/**
	 * @var array cart items
	 */
	protected $items;

	/**
	 * @var StorageInterface
	 */
	private $_storage;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		$this->clear(false);
		$this->setStorage(Rnd::createObject($this->storageClass));
		$this->items = $this->getStorage()->load($this);
	}

	/**
	 * Delete all items from the cart
	 *
	 * @param bool $save
	 *
	 * @return $this
	 */
	public function clear($save = true): self
	{
		$this->items = [];
		$save && $this->getStorage()->save($this);

		return $this;
	}

	/**
	 * @return StorageInterface
	 */
	public function getStorage(): StorageInterface
	{
		return $this->_storage;
	}

	/**
	 * @param mixed $storage
	 */
	public function setStorage($storage)
	{
		$this->_storage = $storage;
	}

	/**
	 * Add an item to the cart
	 *
	 * @param CartItemInterface $element
	 * @param bool $save
	 *
	 * @return $this
	 */
	public function add(CartItemInterface $element, $save = true): self
	{
		$this->addItem($element);
		$save && $this->getStorage()->save($this);

		return $this;
	}

	/**
	 * @param CartItemInterface $item
	 */
	protected function addItem(CartItemInterface $item)
	{
		$uniqueId = $item->getUniqueId();
		$this->items[$uniqueId] = $item;
	}

	/**
	 * Removes an item from the cart
	 *
	 * @param string $uniqueId
	 * @param bool $save
	 *
	 * @throws \rnd\base\InvalidParamException
	 *
	 * @return $this
	 */
	public function remove($uniqueId, $save = true): self
	{
		if (!isset($this->items[$uniqueId])) {
			return $this;
		}

		unset($this->items[$uniqueId]);

		$save && $this->getStorage()->save($this);

		return $this;
	}

	/**
	 * @param string $itemType If specified, only items of that type will be counted
	 *
	 * @return int
	 */
	public function getCount($itemType = null): int
	{
		return count($this->getItems($itemType));
	}

	/**
	 * Returns all items of a given type from the cart
	 *
	 * @param string $itemType One of self::ITEM_ constants
	 *
	 * @return CartItemInterface[]
	 */
	public function getItems($itemType = null): array
	{
		$items = $this->items;

		if (!is_null($itemType)) {
			$items = array_filter(
				$items,
				function ($item) use ($itemType) {
					/* @var $item CartItemInterface */
					return is_a($item, $itemType);
				}
			);
		}

		return $items;
	}

	/**
	 * Finds all items of type $itemType, sums the values of $attribute of all models and returns the sum.
	 *
	 * @param string $attribute
	 * @param string|null $itemType
	 *
	 * @return int
	 */
	public function getAttributeTotal($attribute, $itemType = null): int
	{
		$sum = 0;
		foreach ($this->getItems($itemType) as $model) {
			$sum += $model->{$attribute};
		}

		return $sum;
	}
}