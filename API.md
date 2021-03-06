# Improved Template Service

Improved template system for phpBB 3.2, implementing a number of proposed core changes and additional methods.

## New template functionality

### New methods to retrieve template variables

	/**
	* Retrieve multiple template values
	*
	* @param array $vararray An array with variable names
	* @return array A hash of variable name => value pairs (value is null if not set)
	*/
	public function retrieve_vars(array $vararray);

	/**
	* Retreive a single scalar value from a single key.
	*
	* @param string $varname Variable name
	* @return mixed Variable value, or null if not set
	*/
	public function retrieve_var($varname);

### New block selector specification

Consistent throughout the interface, using name (string) or selector (array).

	/**
	* Block selector parameter description
	*
	* @param	mixed	$block_selector Selector of block to manipulate, with two possible formats
	*			string		blockname of the block to act on; can be
	*							* simple ('loop')
	*							* multilevel ('loop.inner')
	*							* indexed ('loop[1].inner', or 'loop.inner[0]', or even 'loop[1].inner[2]')
	*							*		also allows 'loop[]' to refer to the last element of a loop
	*							*		if index is ommited, last element is also taken
	*			array		complete block selector, with one hash per (ordered) nesting block level
	*							* array key is the (string) name of the block
	*							* array value is the index within that block, as follows
	*								- false refers to the first element of the block (index 0)
	*								- true refers to the end of the block
	*									last element, index == count(block)-1 for most operations
	*									or after it for last level of insertion, index == count(block)
	*								- int refers to the exact position of index to take; valid values 0..count(block)-1
	*								- array('KEY' => value) search block for index where block[index]['KEY'] === value
	*								- null is equivalent to true except for last level deletion, where it is used to delete whole block (all indexes)
	*/

Some examples of block selectors in string and array formats

	/**
	*		'loop'					== array('loop' => null)
	*		'loop.inner'			== array('loop' => null, 'inner' => null)
	*		'loop[1].inner[]'		== array('loop' => 1, 'inner' => true)
	*		not possible as string	== array(array('loop' => array('VARNAME' => varvalue), 'inner' => true)
	*/

### Consistent set of block manipulation methods

	/**
	* Assign key variable pairs from an array to a specified block
	*/
	public function assign_block_vars($block_selector, array $vararray);

	/**
	* Assign key variable pairs from an array to a whole specified block loop
	*/
	public function assign_block_vars_array($block_selector, array $block_vars_array);

	/**
	* Reset/empty complete block
	*/
	public function destroy_block_vars($block_selector);

### Extended set of block manipulation methods

	/**
	* Retrieve variable values from an specified block
	*/
	public function retrieve_block_vars($block_selector, array $vararray);

	/**
	* Find the index for a specified key in the innermost specified block, provided for backward compatibility, redundant
	* @return mixed false if not found, index position otherwise; be sure to test with ===
	*/
	public function find_key_index($block_selector, $key);

### Generic function to perform any operation on a block (expanded functionality)

	/**
	* Generic function to perform any operation on a block
	*
	* @param	mixed	$block_selector Selector of block to operate on
	* @param	array	$vararray	the var array to operate with
	* @param	mixed	$key		Provided for backward compatibility, only considered if last level block selector value === null
	* @param	string	$mode		Mode to execute (valid modes are 'find', 'retrieve', 'insert', 'multiinsert', 'change' and 'delete')
	*			'find'			the vararray is ignored (but must be an array), and the integer index of the last block is returned; synonym for find_key_index.
	*			'retrieve'		the vararray is a list of variable names to retrieve from the selected block; synonym for retrieve_block_vars.
	*			'insert'		the vararray is inserted at the given position (position counting from zero); synonym for assign_block_vars.
	*			'multiinsert'	the vararray is an array of arrays, inserted at the given position (position counting from zero); synonym for assign_block_vars_array.
	*			'change'		the current block gets merged with the vararray (resulting in new \key/value pairs be added and existing keys be replaced by the new \value).
	*			'delete'		the vararray is ignored (but must be an array), and the block at the given position is removed; synonym for destroy_block_vars.
	* @return mixed	bool false on error, true on success, int for mode='find', array of hashes for mode='retrieve'
	*/
	public function alter_block_array($block_selector, array $vararray, $key = false, $mode = 'insert');

Some examples of the generic use of alter_block_array

	/**
	*	alter_block_array('loop', array('NAME'=>'first')) // Insert the vararray in the loop block, at the beginning (if it exists, if not, creates the 'loop' block)
	*	alter_block_array('loop.inner', array('INSIDE'=>11), true, 'insert')
	*	alter_block_array('loop', array('NAME'=>'zero')) // Inserted BEFORE the existing block in loop
	*	alter_block_array('loop', array('NAME'=>'second'), true) // Insert at the end
	*	alter_block_array('loop.inner', array('INSIDE'=>21)) // Create new block inside last one
	*	alter_block_array(array('loop' => array('NAME'=>'first'), 'inner' => null), array('S_INSIDE'=>12), null, 'insert')
	*	alter_block_array(array('loop' => array('NAME'=>'zero'), 'inner' => 0), array('S_INSIDE'=>01), null, 'insert')
	*	alter_block_array(array('loop' => array('NAME'=>'first'), 'inner' => null), array(), array('INSIDE'=>11), 'delete') // Deletes single block entry
	*	alter_block_array(array('loop' => array('NAME'=>'first'), 'inner' => null), array(), null, 'delete') // Deletes the whole block
	*	alter_block_array('loop[1]', array('NAME'=>'newfirst', 'WAS'=>'second'), null, 'change') // Changes the block, changing one var and adding another
	*/
