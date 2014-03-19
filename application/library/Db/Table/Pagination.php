<?php
/**
 * Api.cloud
 *
 * @copyright Copyright (c) 2013 Beijing CmsTop Technology Co.,Ltd. (http://www.cmstop.com)
 */

namespace Db\Table;

class Pagination implements \Countable
{
	/**
	 * @var int page size
	 */
	protected $pageSize = 10;

	/**
	 * @var int current page queried, defined
	 */
	protected $currentPage = 1;

	/**
	 * @var int pages count
	 */
	protected $pageCount;

	/**
	 * @var int records count
	 */
	protected $recordCount;

	public function __construct($currentPage = 1, $pageSize = 10)
	{
		$this->setCurrentPage($currentPage);
		$this->setPageSize($pageSize);
	}

	/**
	 * Set page size
	 *
	 * @param int $val
	 * @return $this
	 */
	public function setPageSize($val)
	{
		if (($val = abs((int)$val)) > 0) {
			$this->pageSize = $val;
		}
		return $this;
	}

	/**
	 * Set current page
	 *
	 * @param int $val
	 * @return $this
	 */
	public function setCurrentPage($val)
	{
		if (($val = abs((int)$val)) > 0) {
			$this->currentPage = $val;
		}
		return $this;
	}

	/**
	 * Get current page
	 *
	 * @return int
	 */
	public function getCurrentPage()
	{
		return $this->currentPage;
	}

	/**
	 * Get page size
	 *
	 * @return int
	 */
	public function getPageSize()
	{
		return $this->pageSize;
	}

	/**
	 * Get records count
	 *
	 * @return int
	 */
	public function getRecordCount()
	{
		return $this->recordCount;
	}

	/**
	 * Set records count.
	 *
	 * @param int $recordCount
	 * @return $this
	 */
	public function setRecordCount($recordCount)
	{
		$this->recordCount = (int)$recordCount;
		return $this;
	}

	/**
	 * Returns the number of pages.
	 *
	 * @return integer
	 */
	public function count()
	{
		if (!$this->pageCount) {
			$this->pageCount = ceil($this->getRecordCount() / $this->getPageSize());
		}

		return $this->pageCount;
	}
}