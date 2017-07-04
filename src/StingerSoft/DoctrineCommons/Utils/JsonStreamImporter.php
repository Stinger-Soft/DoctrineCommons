<?php

namespace StingerSoft\DoctrineCommons\Utils;

use StingerSoft\DoctrineCommons\Utils\Helper\JsonCountListener;
use StingerSoft\DoctrineCommons\Utils\Helper\ImportListener;

class JsonStreamImporter extends JsonImporter {

	/**
	 * @var bool whether to count the entries of the json first in order to be able to display a progress
	 */
	private $countEntries = true;

	/**
	 * Set whether to count the entries of the json first in order to be able to display a progress.
	 *
	 * Counting the entries means that the whole json file will be parsed once. In order to increase performance, you
	 * should consider setting this to false.
	 *
	 * @param bool $countEntries true in case the entries of the json file shall be counted before importing (default),
	 *                           false otherwise.
	 * @return $this
	 */
	public function setCountEntries($countEntries) {
		$this->countEntries = $countEntries;

		return $this;
	}

	/**
	 * Get whether to count the entries of the json first in order to be able to display a progress.
	 *
	 * Counting the entries means that the whole json file will be parsed once. In order to increase performance, you
	 * should consider setting this to false.
	 *
	 * @return bool true in case the entries of the json file shall be counted before importing (default),
	 * false otherwise.
	 */
	public function getCountEntries() {
		return $this->countEntries;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \StingerSoft\DoctrineCommons\Utils\ImporterService::import()
	 */
	public function import($filename) {
		$stream = fopen($filename, 'r');
		try {
			$maxEntries = null;
			if($this->countEntries) {
				if($this->output) {
					$this->output->writeln('Scanning json file...');
				}
				$countListener = new JsonCountListener();
				$parser = new \JsonStreamingParser\Parser($stream, $countListener, "\n", true, 81920);
				$parser->parse();
				rewind($stream);
				if($this->output) {
					$this->output->writeln('Scan OK! Starting import...');
				}
				$maxEntries = $countListener->getEntryCount();
			} else {
				if($this->output) {
					$this->output->writeln('skipping scanning of json file, no progress will be displayed!');
				}
			}
			$this->connection->beginTransaction();
			$this->before();
			$importListener = new ImportListener($this, $this->schemaManager, $this->connection, $maxEntries, $this->output);
			$parser = new \JsonStreamingParser\Parser($stream, $importListener, "\n", true, 81920);
			$parser->parse();
			$this->after();
			$this->connection->commit();
		} catch(\Exception $e) {
			throw $e;
		} finally {
			fclose($stream);
		}
	}
}