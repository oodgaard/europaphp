<?php

/**
 * Tests for validating Europa_Validator_Suite
 * 
 * @category Tests
 * @package  Europa
 * @author   Trey Shugart <treshugart@gmail.com>
 * @license  (c) 2010 Trey Shugart
 * @link     http://europaphp.org/license
 */
class Test_Validator_Suite extends Europa_Unit_Test
{
	/**
	 * Tests to make sure it fails if all sub-tests fail.
	 * 
	 * @return bool
	 */
	public function testFailAllValidators()
	{
		$suite = new Europa_Validator_Suite;
		$suite[] = new Europa_Validator_Required;
		$suite[] = new Europa_Validator_Number;
		return $suite->isValid(null) === false;
	}
	
	/**
	 * Tests to make sure it passes if all tests pass.
	 * 
	 * @return bool
	 */
	public function testPassAllValidators()
	{
		$suite = new Europa_Validator_Suite;
		$suite[] = new Europa_Validator_Required;
		$suite[] = new Europa_Validator_Number;
		return $suite->isValid('1') === true;
	}
	
	/**
	 * Tests to make sure it fails if one or more sub-tests fail.
	 * 
	 * @return bool
	 */
	public function testPassOneValidator()
	{
		$suite = new Europa_Validator_Suite;
		$suite[] = new Europa_Validator_Required;
		$suite[] = new Europa_Validator_Number;
		return $suite->isValid('something') === false;
	}
}