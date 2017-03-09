<?php
/**
 *
 * Improved Template Service. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @copyright (c) 2017, javiexin
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace javiexin\template\template;

/**
 * Improved template context
 */

/**
* Stores variables assigned to template.
*/
class context extends \phpbb\template\context
{
// Redeclaring private variables, all this section is redundant should the base clase element had been protected instead
	/**
	* @var array Reference to template->tpldata
	*/
	protected $tpldata;

	/**
	* @var array Reference to template->tpldata['.'][0]
	*/
	protected $rootref;

	/**
	* @var bool
	*/
	protected $num_rows_is_set;

	public function __construct()
	{
		parent::__construct();
		$this->setup();
	}

	/**
	* Clears template data set.
	*/
	public function clear()
	{
		parent::clear();
		$this->setup();
	}

	/**
	* Setup the current object referencing the parent components
	*/
	protected function setup()
	{
		$this->rootref = parent::get_root_ref();
		$this->tpldata = parent::get_data_ref();
		$this->num_rows_is_set = false;
	}

	/**
	* Returns a reference to template data array. Repeated here to use the current scope num_rows_is_set
	*
	* @return array template data
	*/
	public function &get_data_ref()
	{
		if (!$this->num_rows_is_set)
		{
			/*
			* We do not set S_NUM_ROWS while adding a row, to reduce the complexity
			* If we would set it on adding, each subsequent adding would cause
			* n modifications, resulting in a O(n!) complexity, rather then O(n)
			*/
			foreach ($this->tpldata as $loop_name => &$loop_data)
			{
				if ($loop_name === '.')
				{
					continue;
				}

				$this->set_num_rows($loop_data);
			}
			$this->num_rows_is_set = true;
		}

		return $this->tpldata;
	}

// New method to retrieve template variable values
	/**
	* Retreive a single scalar value from a single key.
	*
	* @param string $varname Variable name
	* @return mixed Variable value, or null if not set
	*/
	public function retrieve_var($varname)
	{
		return isset($this->rootref[$varname]) ? $this->rootref[$varname] : null;
	}

// These methods should not be used/called directly, provided only to comply with interface/class definition
	/**
	* Assign key variable pairs from an array to a specified block
	*
	* @param mixed	$block_selector Selector of block to assign $vararray to
	* @param array	$vararray A hash of variable name => value pairs
	* @return false on error, true on success
	*/
	public function assign_block_vars($block_selector, array $vararray)
	{
		return $this->alter_block_array($block_selector, array($vararray), null, 'multiinsert');
	}

	/**
	* Assign key variable pairs from an array to a whole specified block loop
	*
	* @param mixed	$block_selector Selector of block to assign block vars array to
	* @param array	$block_vars_array An array of hashes of variable name => value pairs
	* @return true on success, false otherwise
	*/
	public function assign_block_vars_array($block_selector, array $block_vars_array)
	{
		return $this->alter_block_array($block_selector, $block_vars_array, null, 'multiinsert');
	}

	/**
	* Retrieve variable values from an specified block
	*
	* @param mixed	$block_selector Selector of block to retrieve $vararray from
	* @param array	$vararray An array with variable names
	* @return array of hashes with variable name as key and retrieved value or null as value, false on error
	*/
	public function retrieve_block_vars($block_selector, array $vararray)
	{
		return $this->alter_block_array($block_selector, $vararray, null, 'retrieve');
	}

	/**
	* Reset/empty complete block
	*
	* @param mixed	$block_selector Selector of block to destroy
	* @return bool					true if successful, false if block is not found
	*/
	public function destroy_block_vars($block_selector)
	{
		return $this->alter_block_array($block_selector, array(), null, 'delete');
	}

	/**
	* Find the index for a specified key in the innermost specified block
	*
	* @param mixed	$block_selector Selector of block to find
	* @param mixed	$key Key to search for, provided for backward compatibility, only considered if last level block selector value === null
	* @return mixed false if not found, index position otherwise; be sure to test with ===
	*/
	public function find_key_index($block_selector, $key = null)
	{
		return $this->alter_block_array($block_selector, array(), $key, 'find');
	}

// Main function to select a block and execute block actions
	/**
	* Core function to select a block in which we are going to act
	*
	* @param	mixed	$block_selector Selector of block to act on
	* @param	array	$vararray	the var array to operate with
	* @param	mixed	$key		Provided for backward compatibility, only considered if last level block selector value === null, same semantics
	* @param	string	$mode		Mode to execute (valid modes are 'find', 'retrieve', 'insert', 'multiinsert', 'change' and 'delete')
	*			'find'			the vararray is ignored (but must be an array), and the integer index of the last level block is returned
	*			'retrieve'		the vararray is a list of variable names to retrieve from the selected block, empty array gets all block vars
	*			'insert'		the vararray is inserted at the given position (position counting from zero)
	*			'multiinsert'	the vararray is an array of vararrays, inserted at the given position (position counting from zero)
	*			'change'		the current block gets merged with the vararray (resulting in new \key/value pairs be added and existing keys be replaced by the new \value)
	*			'delete'		the vararray is ignored (but must be an array), and the block at the given position is removed
	*
	* @return mixed		bool false on error, true on success, int for mode='find', array of hashes for mode='retrieve'
	*/
	public function alter_block_array($block_selector, array $vararray, $key = false, $mode = 'insert')
	{
		// Convert block selector to array format and validate
		if (is_string($block_selector))
		{
			$block_selector = $this->block_selector_array($block_selector);
		}
		if (!is_array($block_selector))
		{
			return false;
		}

		// If last block selector key is null, then we take into considertion the param, otherwise ignored
		if (!is_null($key) && is_null(end($block_selector)))
		{
			$block_selector[key($block_selector)] = $key;
		}

		$block = &$this->tpldata;

		reset($block_selector);
		while (list($name, $search_key) = each($block_selector))
		{
			// Find the index in the block for the given key
			if (($index = $this->find_block_index(@$block[$name], $search_key)) === false)
			{
				return false;
			}

			// Last iteration, we do not traverse last level, and keep $name and $search_key at its latest values
			if (!key($block_selector))
			{
				break;
			}

			// Traverse this block level
			if (!isset($block[$name]))
			{
				return false;
			}
			$block = &$block[$name];
			$block = &$block[$index];
		}

		// Now we perform the specific action with the selected block; we use call_user_func_array to be able to pass block by ref
		return call_user_func_array(array($this, $mode . '_block_array'), array(&$block, $vararray, $search_key, $name, $index));
	}

// Specific actions executed on the already provided block
	/**
	* Insert an array of template vars into a block
	*
	* @param	array	$block			a reference to the block where we have to insert
	* @param	array	$vararray		the var array to insert
	* @param	mixed	$key			search key used in last block, true or null to insert past the end of the block
	* @param	string	$name			name of the block where we are inserting
	* @param	int		$index			index where we have to insert
	* @return bool false on error, true on success
	*/
	protected function insert_block_array(&$block, array $vararray, $key, $name, $index)
	{
		return $this->multiinsert_block_array($block, array($vararray), $key, $name, $index);
	}

	/**
	* Multi-insert an array of arrays of template vars into a block, single call for performance
	*
	* @param	array	$block			a reference to the block where we have to insert
	* @param	array	$vararrays		the array of var arrays to insert
	* @param	mixed	$key			search key used in last block, true or null to insert past the end of the block
	* @param	string	$name			name of the block where we are inserting
	* @param	int		$index			index where we have to insert
	* @return bool false on error, true on success
	*/
	protected function multiinsert_block_array(&$block, array $vararrays, $key, $name, $index)
	{
		if (($numarrays = count($vararrays)) == 0)
		{
			return false; // Nothing to insert
		}

		$this->num_rows_is_set = false;

		if (!isset($block[$name]))
		{
			$block[$name] = array();
		}
		$block = &$block[$name];

		// If inserting at the end, we need to reposition
		if ($key === null || $key === true)
		{
			$index++;
		}

		// Fix S_FIRST_ROW and S_LAST_ROW
		if ($index == count($block))
		{
			unset($block[($index - 1)]['S_LAST_ROW']);
			$vararrays[($numarrays - 1)]['S_LAST_ROW'] = true;
		}
		if ($index == 0)
		{
			unset($block[0]['S_FIRST_ROW']);
			$vararrays[0]['S_FIRST_ROW'] = true;
		}

		// Re-position template blocks to make room for the new one
		for ($i = count($block) + $numarrays - 1; $i > $index + $numarrays - 1; $i--)
		{
			$block[$i] = $block[$i - $numarrays];

			$block[$i]['S_ROW_COUNT'] = $block[$i]['S_ROW_NUM'] = $i;
		}

		// Insert vararrays at given position
		foreach ($vararrays as $vararray)
		{
			// Assign S_BLOCK_NAME and S_ROW_COUNT and S_ROW_NUM
			$vararray['S_BLOCK_NAME'] = $name;
			$vararray['S_ROW_COUNT'] = $vararray['S_ROW_NUM'] = $index;

			// Insert vararray at given position and move the position
			$block[$index] = $vararray;
			$index++;
		}

		return true;
	}

	/**
	* Change an array of template vars into a block, the block must exist, but the template vars will be merged
	*
	* @param	array	$block			a reference to the block where we have to change
	* @param	array	$vararray		the var array to change
	* @param	mixed	$key			search key used in last block, ignored
	* @param	string	$name			name of the block where we are changing
	* @param	int		$index			index where we have to change
	* @return bool false on error, true on success
	*/
	protected function change_block_array(&$block, array $vararray, $key, $name, $index)
	{
		if (!isset($block[$name]))
		{
			return false;
		}

		$this->num_rows_is_set = false;

		$block[$name][$index] = array_merge($block[$name][$index], $vararray);

		return true;
	}

	/**
	* Delete a block of template vars, the block must exist
	*
	* @param	array	$block			a reference to the block where we have to delete
	* @param	array	$vararray		the var array is ignored, but must be an array
	* @param	mixed	$key			search key used in last block, used to identify full-block deletion
	* @param	string	$name			name of the block where we are deleting
	* @param	mixed	$index			index we have to delete
	* @return bool false on error, true on success
	*/
	protected function delete_block_array(&$block, array $vararray, $key, $name, $index)
	{
		if (!isset($block[$name]))
		{
			return false;
		}

		$this->num_rows_is_set = false;

		// Delete the whole block if so specified, or when deleting the only element in block
		if (is_null($key) || count($block[$name]) === 1)
		{
			unset($block[$name]);
			return true;
		}

		$block = &$block[$name];

		// Re-position template blocks to fill the gap
		for ($i = $index; $i < count($block)-1; $i++)
		{
			$block[$i] = $block[$i+1];
			$block[$i]['S_ROW_COUNT'] = $block[$i]['S_ROW_NUM'] = $i;
		}

		// Remove the last element now duplicate
		unset($block[$i]);

		// Set first and last elements again, in case they were removed
		$block[0]['S_FIRST_ROW'] = true;
		$block[count($block)-1]['S_LAST_ROW'] = true;

		return true;
	}

	/**
	* Retrieve key variable pairs from a block
	*
	* @param	array	$block			a reference to the block where we have to retrieve the key variable pairs
	* @param	array	$vararray		an array of variablle names to be retrieved, empty array retrieves all vars
	* @param	mixed	$key			search key used in last block, ignored
	* @param	string	$name			name of the block where we are retrieving
	* @param	int		$index			index were we have to retrieve the vars
	* @return bool false on error, an array of hashes with variable name as key and retrieved value or null as value
	*/
	protected function retrieve_block_array(&$block, array $vararray, $key, $name, $index)
	{
		if (!isset($block[$name][$index]))
		{
			return false;
		}

		$result = array();
		if ($vararray === array())
		{
			// The calculated vars that depend on the block position are excluded from the complete block returned results
			$excluded_vars = array('S_FIRST_ROW', 'S_LAST_ROW', 'S_BLOCK_NAME', 'S_NUM_ROWS', 'S_ROW_COUNT', 'S_ROW_NUM');

			foreach ($block[$name][$index] as $varname => $varvalue)
			{
				if ($varname === strtoupper($varname) && !is_array($varvalue) && !in_array($varname, $excluded_vars))
				{
					$result[$varname] = $varvalue;
				}
			}
		}
		else
		{
			foreach ($vararray as $varname)
			{
				$result[$varname] = isset($block[$name][$index][$varname]) ? $block[$name][$index][$varname] : null;
			}
		}
		return $result;
	}

	/**
	* Find the index for a specified key in the given block
	*
	* @param	array	$block			a reference to the block where we have to get the index
	* @param	array	$vararray		ignored, but must be an array
	* @param	mixed	$key			search key used in last block, ignored
	* @param	string	$name			name of the block where we are finding
	* @param	int		$index			index
	* @return 	int 					index position within the block
	*/
	protected function find_block_array(&$block, array $vararray, $key, $name, $index)
	{
		if (!isset($block[$name]))
		{
			return false;
		}

		return $index;
	}

// Support functions for common interface and block selection mechanism
	/**
	* Converts a string block selector to the equivalent array block selector
	*
	* @param	string	$block_selector		The string format of the block selector
	* @return	array						The same block selector, in the equivalent array format
	*/
	protected function block_selector_array($block_selector)
	{
		// For nested block, $blockcount > 0, for top-level block, $blockcount == 0
		$blocks = explode('.', $block_selector);
		$blockcount = count($blocks);
		$block_selector = array();

		for ($i = 0; $i < $blockcount; $i++)
		{
			if (($pos = strpos($blocks[$i], '[')) !== false)
			{
				$name = substr($blocks[$i], 0, $pos);

				if (strpos($blocks[$i], '[]') === $pos)
				{
					$index = true;
				}
				else
				{
					$index = (int) substr($blocks[$i], $pos + 1, -1);
				}
			}
			else
			{
				$name = $blocks[$i];
				$index = null;
			}
			$block_selector[$name] = $index;
		}
		return $block_selector;
	}

	/**
	* Finds a specific key within a block of variables.
	*
	* @param	array	$block		The block of variables where the key is searched for
	* @param	mixed	$key		The search key to find in the block
	*			bool					true for last element, false for first element
	*			int						the actual index number of the element
	*			array					VARNAME => varvalue to search for in the block
	*			null					last element
	* @return	mixed				false if not found or out of bounds, index position otherwise; be sure to test with ===
	*								note that in case the $block is empty (non-existent), the function returns 0
	*									except in case the $key is an array, that the function returns false
	*/
	protected function find_block_index($block, $key)
	{
		$index = false;

		// Change key to zero if false and to last position if true or null
		if ($key === false || $key === true || $key === null)
		{
			$index = ($key === false) ? 0 : count($block) - 1;
		}

		// Get correct position if array given
		if (is_array($key) && is_array($block))
		{
			// Search array to get correct position
			list($search_key, $search_value) = each($key);

			foreach ($block as $i => $val_ary)
			{
				if (isset($val_ary[$search_key]) && ($val_ary[$search_key] === $search_value))
				{
					$index = $i;
					break;
				}
			}
		}

		if (is_int($key) && ($key == (int) min(max($key, 0), count($block) - 1)))
		{
			$index = $key;
		}

		// Now return the index
		return $index;
	}
}
