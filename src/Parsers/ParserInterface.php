<?php

namespace Tomaj\BankMailsParser\Parser;


interface ParserInterface
{
	public function parse($content);
}