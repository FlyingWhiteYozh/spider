<?php
namespace Spider;

class Semaphore extends \Amp\Sync\LocalSemaphore
{
	public function getLocksTotal()
	{
		return count($this->locks);
	}
}