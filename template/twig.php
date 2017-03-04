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
 * Modified twig object to use new template and context definitions
 */

/**
* Twig Template class.
*/
class twig extends \phpbb\template\twig\twig implements template
{
// All these methods should be in the improved base template implementation, but moved here to avoid multiple inheritance
	/**
	* {@inheritdoc}
	*/
	public function retrieve_vars(array $vararray)
	{
		$result = array();
		foreach ($vararray as $varname)
		{
			$result[$varname] = $this->retrieve_var($varname);
		}
		return $result;
	}

	/**
	* {@inheritdoc}
	*/
	public function retrieve_var($varname)
	{
		return $this->context->retrieve_var($varname);
	}

	/**
	* {@inheritdoc}
	*/
	public function assign_block_vars($block_selector, array $vararray)
	{
		$this->context->alter_block_array($block_selector, array($vararray), null, 'multiinsert');

		return $this;
	}

	/**
	* {@inheritdoc}
	*/
	public function assign_block_vars_array($block_selector, array $block_vars_array)
	{
		$this->context->alter_block_array($block_selector, $block_vars_array, null, 'multiinsert');

		return $this;
	}

	/**
	* {@inheritdoc}
	*/
	public function retrieve_block_vars($block_selector, array $vararray)
	{
		return $this->context->alter_block_array($block_selector, $vararray, null, 'retrieve');
	}

	/**
	* {@inheritdoc}
	*/
	public function destroy_block_vars($block_selector)
	{
		$this->context->alter_block_array($block_selector, array(), null, 'delete');

		return $this;
	}

	/**
	* {@inheritdoc}
	*/
	public function find_key_index($block_selector, $key)
	{
		return $this->context->alter_block_array($block_selector, array(), $key, 'find');
	}

	/**
	* {@inheritdoc}
	*/
	public function alter_block_array($block_selector, array $vararray, $key = false, $mode = 'insert')
	{
		return $this->context->alter_block_array($block_selector, $vararray, $key, $mode);
	}
}
